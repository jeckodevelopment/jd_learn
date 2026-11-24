#!/usr/bin/env python3
"""Fix high-debt PHP files expression by expression, not whole file by file.

Previous source-wide candidates often failed PSR12 or source-contract gates
because one expression regressed while others improved. This tool ranks files
by real long-line count, selects individual lengthy expressions, tries existing
lexer-confirmed whitespace/operator layouts, and accepts only strict per-file
PHPCS improvements with identical significant tokens and valid PHP syntax.

No suppressions, standards changes, or error-budget exemptions are used.
"""
from __future__ import annotations

import argparse
from pathlib import Path
import subprocess

from secdev02_lexer_bulk_wrap import ROOT, lex, lint, phpcs_counts
from secdev02_calls_reflow_bulk import required_source_fragments
from secdev02_adaptive_operator_batch import operator_points, propose
from secdev02_whitespace_bulk_refactor import spaces, transformed

MAX_WIDTH = 120


def longlines(source: bytes) -> list[tuple[int, int, int]]:
    result = []
    position = 0
    for line in source.splitlines(keepends=True):
        size = len(line.rstrip(b"\r\n"))
        if size > MAX_WIDTH:
            result.append((position, position + size, size))
        position += len(line)
    return sorted(result, key=lambda x: x[2], reverse=True)


def fix_file(
    path: Path, phpcs: Path, required: set[bytes], max_examined: int, max_fixes: int
) -> tuple[int, int]:
    current = path.read_bytes()
    attempts = 0
    successes = 0
    removed = 0
    skipped: set[bytes] = set()
    while attempts < max_examined and successes < max_fixes:
        choices = [
            (start, end, size)
            for start, end, size in longlines(current)
            if current[start:end] not in skipped
        ]
        if not choices:
            break
        start, end, _ = choices[0]
        line = current[start:end]
        skipped.add(line)
        attempts += 1
        baseline_hash = lex(path)["hash"]
        before = phpcs_counts(phpcs, path)
        old_score = sum(before.values())
        operator_locations = [
            item for item in operator_points(path)
            if start <= int(item[0]) < end
        ]
        whitespace_locations = [
            item for item in spaces(path)
            if start <= int(item[0]) < end
        ]
        candidates = []
        for width, offset, after in (
            (112, 4, False), (105, 4, True), (100, 4, False),
            (112, 8, True), (96, 4, True)
        ):
            if operator_locations:
                candidate = propose(
                    current, operator_locations, width, offset, after
                )
                if candidate != current and candidate not in candidates:
                    candidates.append(candidate)
        for width, extra in ((108, 4), (104, 8), (96, 4)):
            if whitespace_locations:
                candidate = transformed(
                    current, whitespace_locations, width, extra
                )
                if candidate != current and candidate not in candidates:
                    candidates.append(candidate)
        best, best_score = current, old_score
        for candidate in candidates:
            if any(part in current and part not in candidate for part in required):
                continue
            path.write_bytes(candidate)
            if not lint(path) or lex(path)["hash"] != baseline_hash:
                continue
            after_counts = phpcs_counts(phpcs, path)
            if any(after_counts[rule] > before[rule] for rule in after_counts):
                continue
            score = sum(after_counts.values())
            if score < best_score:
                best, best_score = candidate, score
        path.write_bytes(best)
        if best != current:
            successes += 1
            removed += old_score - best_score
            current = best
            skipped.clear()
    return successes, removed


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--phpcs", type=Path)
    parser.add_argument("--top-files", type=int, default=65)
    parser.add_argument("--max-examined", type=int, default=14)
    parser.add_argument("--max-fixes", type=int, default=9)
    parser.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    if args.self_test:
        assert longlines(b"$x=1;\n") == []
        example = b"$x = " + b"variable_name" * 12 + b";\n"
        assert len(longlines(example)) == 1
        print("SECDEV-02 granular expression bulk fixtures: OK")
        return 0
    if not args.phpcs or not args.phpcs.is_file():
        parser.error("--phpcs must refer to the pinned executable")
    entries = subprocess.check_output(
        ["git", "ls-files", "-z", "--", "*.php"], cwd=ROOT,
    ).split(b"\0")
    paths = [(ROOT / item.decode()) for item in entries if item]
    ranked = sorted(
        (
            (len(longlines(path.read_bytes())), path)
            for path in paths if path.is_file()
        ),
        key=lambda item: item[0], reverse=True
    )
    contracts = required_source_fragments()
    files = total_fixes = total_removed = 0
    for _, path in ranked[:args.top_files]:
        initial = path.read_bytes()
        if not longlines(initial):
            continue
        fixes, removed = fix_file(
            path, args.phpcs, contracts.get(path.relative_to(ROOT).as_posix(), set()),
            args.max_examined, args.max_fixes
        )
        if fixes:
            files += 1
            total_fixes += fixes
            total_removed += removed
        print(
            f"SECDEV-02 granular {path.relative_to(ROOT)} "
            f"accepted={fixes} removed={removed}",
            flush=True
        )
    print(
        f"SECDEV-02 granular bulk accepted_files={files} "
        f"accepted_expressions={total_fixes} net_removed={total_removed}",
        flush=True
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
