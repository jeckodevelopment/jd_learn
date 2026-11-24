#!/usr/bin/env python3
"""SECDEV-02: bulk reflow of plain PHP single-quoted string constants.

Convert only long plain string literals with whitespace to adjacent concatenated
literals. No escapes, backslashes, interpolation or non-ASCII transformations.
Compare PHP lint + PHPCS per-rule counts; restore any file without improvement.
All parts preserve the source literal byte sequence *exactly*.
"""
from __future__ import annotations

import argparse
import json
from pathlib import Path
import subprocess

from secdev02_lexer_bulk_wrap import ROOT, lint, phpcs_counts

LIMIT = 120
TOKENS = r'''
$source = file_get_contents($argv[1]);
$offset = 0;
$items = [];
foreach (token_get_all($source) as $token) {
    $id = is_array($token) ? $token[0] : 0;
    $value = is_array($token) ? $token[1] : $token;
    if ($id === T_CONSTANT_ENCAPSED_STRING && str_starts_with($value, "'") && str_ends_with($value, "'")) {
        $items[] = [$offset, strlen($value)];
    }
    $offset += strlen($value);
}
echo json_encode($items, JSON_THROW_ON_ERROR);
'''


def locations(path: Path) -> list[list[int]]:
    result = subprocess.run(
        ["php", "-r", TOKENS, str(path)],
        capture_output=True, text=True, check=True, timeout=30,
    )
    return json.loads(result.stdout)


def split_literal(token: bytes, width: int, indentation: int) -> bytes | None:
    if len(token) <= width or not token.startswith(b"'") or not token.endswith(b"'"):
        return None
    content = token[1:-1]
    # Only plain one-byte literal characters, no PHP escapes or embedded quotes.
    if b"\\" in content or b"'" in content or b"\n" in content or b"\r" in content:
        return None
    pieces = []
    remaining = content
    while len(remaining) > width - 2:
        place = remaining.rfind(b" ", 1, width - 2)
        if place < 20:
            return None
        pieces.append(remaining[:place + 1])
        remaining = remaining[place + 1:]
        if len(pieces) >= 16:
            return None
    pieces.append(remaining)
    if len(pieces) < 2 or any(not part for part in pieces):
        return None
    # Concatenating adjacent constant fragments reconstructs the exact bytes.
    assert b"".join(pieces) == content
    indentation_bytes = b" " * min(28, indentation + 4)
    return (b" .\n" + indentation_bytes).join(b"'" + part + b"'" for part in pieces)


def propose(content: bytes, matches: list[list[int]], width: int) -> bytes:
    patches = []
    for start, size in matches:
        line_start = content.rfind(b"\n", 0, start) + 1
        line_end = content.find(b"\n", start)
        if line_end < 0:
            line_end = len(content)
        if line_end - line_start <= LIMIT:
            continue
        prefix = content[line_start:start]
        indent = len(prefix) - len(prefix.lstrip(b" "))
        # Avoid string literals embedded inside PHP/HTML transition lines.
        if b"<?" in prefix or b"?>" in prefix or indent > 28:
            continue
        change = split_literal(content[start:start + size], width, indent)
        if change is not None:
            patches.append((start, size, change))
    updated = bytearray(content)
    for start, size, change in reversed(patches):
        updated[start:start + size] = change
    return bytes(updated)


def self_test() -> None:
    sample = b"'" + b"hello world " * 20 + b"'"
    output = split_literal(sample, 90, 4)
    assert output is not None and output.count(b" .\n") > 0
    parts = output.split(b" .\n        ")
    assert b"".join(part[1:-1] for part in parts) == sample[1:-1]
    assert split_literal(b"'C:\\\\foo bar " + b"a " * 80 + b"'", 90, 0) is None
    assert split_literal(b"'hello'", 90, 0) is None
    print("SECDEV-02 plain literal reflow fixtures: OK")


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--self-test", action="store_true")
    parser.add_argument("--phpcs", type=Path)
    args = parser.parse_args()
    if args.self_test:
        self_test()
        return 0
    if args.phpcs is None or not args.phpcs.is_file():
        parser.error("Pinned PHPCS binary required")
    files = subprocess.check_output(
        ["git", "ls-files", "-z", "--", "*.php"], cwd=ROOT
    ).split(b"\0")
    accepted = rejected = removed = 0
    for item in files:
        if not item:
            continue
        path = ROOT / item.decode()
        if not path.is_file():
            continue
        original = path.read_bytes()
        if not any(len(line) > LIMIT for line in original.splitlines()):
            continue
        matches = locations(path)
        if not matches:
            continue
        options = []
        for width in (104, 90, 76):
            changed = propose(original, matches, width)
            if changed != original and changed not in options:
                options.append(changed)
        if not options:
            continue
        before = phpcs_counts(args.phpcs, path)
        best, best_count = original, sum(before.values())
        for candidate in options:
            path.write_bytes(candidate)
            if not lint(path):
                continue
            after = phpcs_counts(args.phpcs, path)
            if any(after[k] > count for k, count in before.items()):
                continue
            score = sum(after.values())
            if score < best_count:
                best, best_count = candidate, score
        path.write_bytes(best)
        if best == original:
            rejected += 1
        else:
            accepted += 1
            removed += sum(before.values()) - best_count
    print(
        f"SECDEV-02 plain strings: accepted_files={accepted} "
        f"rejected_files={rejected} net_removed={removed}"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
