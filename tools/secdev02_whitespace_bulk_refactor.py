#!/usr/bin/env python3
"""Bulk SECDEV-02 PHP line repair, preserving PHP tokens and source contracts.

Use *only* existing PHP T_WHITESPACE tokens (not regex matches in PHP strings,
comments, heredocs or mixed HTML) to wrap long lines. Try three full-file
layouts and accept one if syntax, exact significant tokens, the repository's
source-gate literal contracts, and PHPCS per-rule counts all pass.
"""
from __future__ import annotations

import argparse
from collections import Counter
import json
from pathlib import Path
import subprocess

from secdev02_lexer_bulk_wrap import ROOT, lex, lint, phpcs_counts
from secdev02_calls_reflow_bulk import required_source_fragments

MAX_WIDTH = 120
WHITESPACE = r'''
$src = file_get_contents($argv[1]);
$offset = 0; $whites = [];
foreach (token_get_all($src) as $token) {
    $id = is_array($token) ? $token[0] : 0;
    $text = is_array($token) ? $token[1] : $token;
    if ($id === T_WHITESPACE && strpbrk($text, "\r\n") === false && strlen($text) > 0) {
        $whites[] = [$offset, $offset + strlen($text)];
    }
    $offset += strlen($text);
}
echo json_encode($whites, JSON_THROW_ON_ERROR);
'''


def spaces(path: Path) -> list[list[int]]:
    p = subprocess.run(
        ["php", "-r", WHITESPACE, str(path)], capture_output=True,
        check=True, text=True, timeout=30
    )
    return json.loads(p.stdout)


def transformed(source: bytes, whitespace: list, width: int, extra: int) -> bytes:
    changes: list[tuple[int, int, bytes]] = []
    line_start = 0
    spans_by_line: dict[int, list[tuple[int, int]]] = {}
    for start, end in whitespace:
        pos = source.rfind(b"\n", 0, start) + 1
        spans_by_line.setdefault(pos, []).append((start, end))
    for line in source.splitlines(keepends=True):
        raw = line.rstrip(b"\r\n")
        end_line = line_start + len(raw)
        if len(raw) <= MAX_WIDTH:
            line_start += len(line)
            continue
        stripped = raw.lstrip(b" \t")
        if stripped.startswith((b"//", b"/*", b"*", b"#")) or b"<?" in raw or b"?>" in raw:
            line_start += len(line)
            continue
        indent = len(raw) - len(stripped)
        if indent > 28:
            line_start += len(line)
            continue
        following = b" " * min(indent + extra, 28)
        choices = [
            (a, b) for a, b in spans_by_line.get(line_start, [])
            if a >= line_start + 30 and b <= end_line - 12 and b - a <= 4
        ]
        if not choices:
            line_start += len(line)
            continue
        position = line_start
        for _ in range(18):
            remaining = end_line - position + (len(following) if position > line_start else 0)
            if remaining <= MAX_WIDTH:
                break
            # Minimize unnecessary extra lines. Prefer a split close to width.
            max_pos = position + width - (len(following) if position > line_start else 0)
            eligible = [(a, b) for a, b in choices if position + 25 < a <= max_pos]
            if not eligible:
                break
            a, b = eligible[-1]
            changes.append((a, b, b"\n" + following))
            position = b
        line_start += len(line)
    result = bytearray(source)
    for a, b, insert in sorted(changes, reverse=True):
        result[a:b] = insert
    return bytes(result)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--phpcs", type=Path)
    parser.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    if args.self_test:
        sample = b"$result = call(" + b"$alphaValue, " * 12 + b"$lastItem);\n"
        whites = [
            (i, i + 1) for i, char in enumerate(sample) if char == 32
        ]
        assert b"\n" in transformed(sample, whites, 104, 4)
        assert transformed(b"<?php $x=1;\n", [], 104, 4) == b"<?php $x=1;\n"
        print("SECDEV-02 whitespace-only bulk fixtures: OK")
        return 0
    if not args.phpcs or not args.phpcs.is_file():
        parser.error("Pinned PHP_CodeSniffer binary required")
    required = required_source_fragments()
    entries = subprocess.check_output(
        ["git", "ls-files", "-z", "--", "*.php"], cwd=ROOT,
    ).split(b"\0")
    accepted = rejected = removed = 0
    for raw in entries:
        if not raw:
            continue
        name = raw.decode()
        path = ROOT / name
        if not path.is_file():
            continue
        initial = path.read_bytes()
        if not any(len(row) > MAX_WIDTH for row in initial.splitlines()):
            continue
        positions = spaces(path)
        if not positions:
            continue
        protected = required.get(name, set())
        baseline = phpcs_counts(args.phpcs, path)
        signature = lex(path)["hash"]
        best, score = initial, sum(baseline.values())
        for width, extra in ((112, 4), (104, 4), (114, 8), (95, 4)):
            candidate = transformed(initial, positions, width, extra)
            if candidate == initial or any(
                part in initial and part not in candidate for part in protected
            ):
                continue
            path.write_bytes(candidate)
            if not lint(path) or lex(path)["hash"] != signature:
                continue
            now = phpcs_counts(args.phpcs, path)
            if any(now[key] > baseline[key] for key in now):
                continue
            total = sum(now.values())
            if total < score:
                best, score = candidate, total
        path.write_bytes(best)
        if best != initial:
            accepted += 1
            removed += sum(baseline.values()) - score
        else:
            rejected += 1
    print(
        f"SECDEV-02 whitespace batch accepted_files={accepted} "
        f"rejected_files={rejected} net_removed={removed}"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
