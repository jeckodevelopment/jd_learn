#!/usr/bin/env python3
"""Conservative PHPCS-gated Prettier trial on pure PHP files only.

Formatter output is accepted if and only if PHP tokens (including literal
spelling) remain byte-identical, php -l passes, source contracts remain intact
and there are zero new per-rule PHPCS findings with a strict net improvement.
Mixed PHP/HTML is never processed.
"""
from __future__ import annotations

import argparse
from pathlib import Path
import subprocess

from secdev02_lexer_bulk_wrap import ROOT, lex, lint, phpcs_counts
from secdev02_calls_reflow_bulk import required_source_fragments

LIMIT = 120


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--self-test", action="store_true")
    parser.add_argument("--phpcs", type=Path)
    parser.add_argument("--prettier", type=Path)
    args = parser.parse_args()
    if args.self_test:
        assert b"?>" not in b"<?php $x = 1;"
        assert b"?>" in b"<?php echo 'Hi'; ?> <p>Hello</p>"
        print("SECDEV-02 pure-PHP formatter exclusions: OK")
        return 0
    if not args.phpcs or not args.phpcs.is_file():
        parser.error("--phpcs pointing to pinned executable required")
    if not args.prettier or not args.prettier.is_file():
        parser.error("--prettier pointing to pinned formatter required")
    files = subprocess.check_output(
        ["git", "ls-files", "-z", "--", "*.php"], cwd=ROOT
    ).split(b"\0")
    contracts = required_source_fragments()
    accepted = rejected = removed = 0
    formatter_root = args.prettier.parent.parent.parent.resolve()
    for entry in files:
        if not entry:
            continue
        name = entry.decode()
        path = ROOT / name
        if not path.is_file():
            continue
        original = path.read_bytes()
        if b"?>" in original or not original.startswith(b"<?php"):
            continue
        if not any(len(line) > LIMIT for line in original.splitlines()):
            continue
        process = subprocess.run(
            [
                str(args.prettier.resolve()), "--plugin=@prettier/plugin-php",
                "--parser=php", "--print-width=120", str(path.resolve()),
            ],
            cwd=formatter_root, capture_output=True, timeout=45, check=False,
        )
        if process.returncode or not process.stdout or process.stdout == original:
            continue
        candidate = process.stdout
        required = contracts.get(name, set())
        if any(part in original and part not in candidate for part in required):
            rejected += 1
            continue
        before = phpcs_counts(args.phpcs, path)
        prior_tokens = lex(path)["hash"]
        path.write_bytes(candidate)
        if not lint(path) or lex(path)["hash"] != prior_tokens:
            path.write_bytes(original)
            rejected += 1
            continue
        after = phpcs_counts(args.phpcs, path)
        if any(after[k] > before[k] for k in after) or sum(after.values()) >= sum(before.values()):
            path.write_bytes(original)
            rejected += 1
            continue
        accepted += 1
        removed += sum(before.values()) - sum(after.values())
    print(
        f"SECDEV-02 full PHP formatter: accepted_files={accepted} "
        f"rejected_files={rejected} net_removed={removed}"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
