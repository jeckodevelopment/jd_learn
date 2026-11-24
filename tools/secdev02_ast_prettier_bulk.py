#!/usr/bin/env python3
"""Bulk php-src AST-preserving formatter of PHP-only sources.

The candidate is not accepted for merely having an identical lexer stream:
prettier can structurally change layout, parentheses and expression syntax.
We compare normalized PHP AST for exact equivalence, unchanged PHP comments,
the repository's exact source contracts, syntax, and PHPCS per-rule counts.
No manual waiver or lowering of PSR12 rules is allowed.
"""
from __future__ import annotations

import argparse
import json
from pathlib import Path
import subprocess

from secdev02_lexer_bulk_wrap import ROOT, lint, phpcs_counts
from secdev02_calls_reflow_bulk import required_source_fragments

PHP_COMMENTS = r'''
$src = file_get_contents($argv[1]);
$comments = [];
foreach (token_get_all($src) as $token) {
    if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
        $comments[] = $token[1];
    }
}
echo json_encode($comments, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
'''


def run(command: list[str], cwd: Path | None = None, timeout: int = 40) -> subprocess.CompletedProcess:
    return subprocess.run(
        command, cwd=cwd, capture_output=True, text=True, timeout=timeout,
        check=False
    )


def ast_hash(path: Path, parser_dir: Path) -> str:
    result = run(
        ["php", str(ROOT / "tools/secdev02_php_ast_hash.php"),
         str(parser_dir), str(path)], timeout=35
    )
    if result.returncode != 0 or len(result.stdout.strip()) != 64:
        raise RuntimeError(f"PHP AST parser failed: {path}: {result.stderr[:300]}")
    return result.stdout.strip()


def comments(path: Path) -> list[str]:
    result = run(["php", "-r", PHP_COMMENTS, str(path)])
    if result.returncode:
        raise RuntimeError(f"PHP token comment scan failed for {path}")
    return json.loads(result.stdout)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--phpcs", type=Path)
    parser.add_argument("--prettier", type=Path)
    parser.add_argument("--parser-dir", type=Path)
    parser.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    if args.self_test:
        assert b"?>" not in b"<?php $x = 1;\n"
        assert b"?>" in b"<?php echo 1; ?>some html"
        print("SECDEV-02 AST-first formatter eligibility test OK")
        return 0
    if not args.phpcs or not args.phpcs.is_file():
        parser.error("PHPCS binary missing")
    if not args.prettier or not args.prettier.is_file():
        parser.error("Prettier binary missing")
    if not args.parser_dir or not (args.parser_dir / "vendor/autoload.php").is_file():
        parser.error("AST parser install missing")
    prettier = args.prettier.resolve()
    npm_root = prettier.parent.parent.parent.resolve()
    parser_dir = args.parser_dir.resolve()
    tracked = subprocess.check_output(
        ["git", "ls-files", "-z", "--", "*.php"], cwd=ROOT
    ).split(b"\0")
    contracts = required_source_fragments()
    accepted = rejected = removed = 0
    for item in tracked:
        if not item:
            continue
        name = item.decode()
        path = ROOT / name
        if not path.is_file():
            continue
        original = path.read_bytes()
        if not original.startswith(b"<?php") or b"?>" in original:
            continue
        if not any(len(line) > 120 for line in original.splitlines()):
            continue
        protected = contracts.get(name, set())
        initial_ast = ast_hash(path, parser_dir)
        initial_comments = comments(path)
        before = phpcs_counts(args.phpcs, path)
        least = sum(before.values())
        best = original
        for width in (120, 108):
            command = [
                str(prettier), "--plugin=@prettier/plugin-php", "--parser=php",
                f"--print-width={width}", str(path.resolve())
            ]
            formatted = run(command, cwd=npm_root, timeout=65)
            if formatted.returncode or not formatted.stdout:
                continue
            candidate = formatted.stdout.encode()
            if candidate == original or any(
                part in original and part not in candidate for part in protected
            ):
                continue
            path.write_bytes(candidate)
            if not lint(path) or ast_hash(path, parser_dir) != initial_ast:
                continue
            if comments(path) != initial_comments:
                continue
            after = phpcs_counts(args.phpcs, path)
            if any(after[key] > before[key] for key in after):
                continue
            score = sum(after.values())
            if score < least:
                least, best = score, candidate
        path.write_bytes(best)
        if best != original:
            accepted += 1
            removed += sum(before.values()) - least
        else:
            rejected += 1
    print(
        f"SECDEV-02 AST-preserving formatted accepted_files={accepted} "
        f"rejected_files={rejected} net_removed={removed}"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
