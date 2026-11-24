#!/usr/bin/env python3
"""Run upstream PSR-12 PHPCBF over pure PHP under full AST and source gates.

The PSR-12 bundled code beautifier is the authoritative style source. Accept
only a decrease of PHPCS findings *per rule*, no changed normalized PHP AST,
identical comments, preserved literal source contracts, and successful PHP lint.
Do not modify mixed Joomla PHP/HTML templates in this pass.
"""
from __future__ import annotations

import argparse
import json
from pathlib import Path
import subprocess

from secdev02_lexer_bulk_wrap import ROOT, lint, phpcs_counts
from secdev02_ast_prettier_bulk import ast_hash, comments
from secdev02_calls_reflow_bulk import required_source_fragments


def run() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--phpcs", type=Path)
    parser.add_argument("--phpcbf", type=Path)
    parser.add_argument("--parser-dir", type=Path)
    parser.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    if args.self_test:
        assert b"?>" not in b"<?php $x = 2;"
        print("SECDEV-02 upstream PHPCBF AST guards OK")
        return 0
    if not args.phpcs or not args.phpcs.is_file():
        parser.error("--phpcs missing")
    if not args.phpcbf or not args.phpcbf.is_file():
        parser.error("--phpcbf missing")
    if not args.parser_dir or not (args.parser_dir / "vendor/autoload.php").is_file():
        parser.error("--parser-dir missing")
    tracked = subprocess.check_output(
        ["git", "ls-files", "-z", "--", "*.php"], cwd=ROOT
    ).split(b"\0")
    protected = required_source_fragments()
    accepted = rejected = removed = 0
    for entry in tracked:
        if not entry:
            continue
        name = entry.decode()
        path = ROOT / name
        if not path.is_file():
            continue
        original = path.read_bytes()
        if not original.startswith(b"<?php") or b"?>" in original:
            continue
        before = phpcs_counts(args.phpcs, path)
        if not before:
            continue
        old_ast = ast_hash(path, args.parser_dir.resolve())
        old_comments = comments(path)
        outcome = subprocess.run(
            [str(args.phpcbf), "--standard=PSR12", "--no-cache", str(path)],
            capture_output=True, text=True, timeout=60, check=False,
        )
        updated = path.read_bytes()
        if outcome.returncode not in (0, 1, 2) or updated == original:
            path.write_bytes(original)
            continue
        required = protected.get(name, set())
        if any(part in original and part not in updated for part in required):
            path.write_bytes(original)
            rejected += 1
            continue
        if not lint(path) or ast_hash(path, args.parser_dir.resolve()) != old_ast:
            path.write_bytes(original)
            rejected += 1
            continue
        if comments(path) != old_comments:
            path.write_bytes(original)
            rejected += 1
            continue
        after = phpcs_counts(args.phpcs, path)
        if (
            any(after[key] > before[key] for key in after)
            or sum(after.values()) >= sum(before.values())
        ):
            path.write_bytes(original)
            rejected += 1
            continue
        accepted += 1
        removed += sum(before.values()) - sum(after.values())
    print(
        f"SECDEV-02 PHPCBF AST batch accepted_files={accepted} "
        f"rejected_files={rejected} net_removed={removed}"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(run())
