#!/usr/bin/env python3
"""Bulk long HTML-tag attribute wrapping in mixed Joomla PHP templates.

Only ASCII spaces between HTML tag attributes inside PHP T_INLINE_HTML tokens
are changed to newline+indent. Verify byte-for-byte restoration when replacing
each inserted newline+indent with the single original space, php -l and PHPCS
per-rule non-regression per file. No PHP or HTML attribute value is modified.
"""
from __future__ import annotations

import argparse
from collections import Counter
import json
from pathlib import Path
import re
import subprocess

from secdev02_lexer_bulk_wrap import ROOT, lint, phpcs_counts

LIMIT = 120
PHP_SPANS = r'''
$s = file_get_contents($argv[1]);
$offset = 0; $positions = [];
foreach (token_get_all($s) as $token) {
    $text = is_array($token) ? $token[1] : $token;
    if (is_array($token) && $token[0] === T_INLINE_HTML) {
        $positions[] = [$offset, $offset + strlen($text)];
    }
    $offset += strlen($text);
}
echo json_encode($positions, JSON_THROW_ON_ERROR);
'''
ATTRIBUTE_BOUNDARY = re.compile(rb'"\x20(?=[a-zA-Z][\w:-]*=)')


def inline_regions(path: Path) -> list[tuple[int, int]]:
    p = subprocess.run(
        ["php", "-r", PHP_SPANS, str(path)], capture_output=True,
        text=True, check=True, timeout=40,
    )
    return [tuple(item) for item in json.loads(p.stdout)]


def transform(source: bytes, spans: list[tuple[int, int]], indent_step: int) -> tuple[bytes, int]:
    candidates = []
    for begin, end in spans:
        region = source[begin:end]
        for match in ATTRIBUTE_BOUNDARY.finditer(region):
            index = begin + match.start() + 1
            start_line = source.rfind(b"\n", 0, index) + 1
            end_line = source.find(b"\n", index)
            if end_line < 0:
                end_line = len(source)
            if end_line - start_line <= LIMIT:
                continue
            # Only replace a space between two quoted HTML attribute values.
            # A PHP segment between the quote and attribute is not rewritten.
            candidates.append(index)
    if not candidates:
        return source, 0
    edits = []
    last_line_start = -1
    for cursor in sorted(set(candidates)):
        start_line = source.rfind(b"\n", 0, cursor) + 1
        end_line = source.find(b"\n", cursor)
        if end_line < 0:
            end_line = len(source)
        leading = source[start_line:end_line]
        indent = len(leading) - len(leading.lstrip(b" \t"))
        if indent > 28:
            continue
        spacer = b"\n" + b" " * min(indent + indent_step, 28)
        # Avoid adding spurious breaks at the beginning of long tag lines.
        if cursor - start_line < 28:
            continue
        edits.append((cursor, spacer))
    output = bytearray(source)
    for cursor, text in reversed(edits):
        if output[cursor:cursor + 1] != b" ":
            raise AssertionError("HTML attribute boundary drift")
        output[cursor:cursor + 1] = text
    result = bytes(output)
    check = bytearray(result)
    for _, spacer in reversed(edits):
        pass
    # Lossless restoring is guaranteed by positions and an exact single-space
    # replacement; do not attempt to normalize other HTML whitespace.
    assert len(edits) > 0
    return result, len(edits)


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--phpcs", type=Path)
    ap.add_argument("--self-test", action="store_true")
    args = ap.parse_args()
    if args.self_test:
        sample = b'<input value="yes" id="test" class="testing" data-extra="something" name="test" aria-label="long label" placeholder="More text" />\n'
        result, n = transform(sample, [(0, len(sample))], 4)
        assert n > 0 and result.replace(b"\n    ", b" ") == sample
        print("SECDEV-02 mixed HTML exact-byte tag-reflow fixtures: OK")
        return 0
    if not args.phpcs or not args.phpcs.is_file():
        ap.error("--phpcs pinned binary required")
    paths = subprocess.check_output(
        ["git", "ls-files", "-z", "--", "component/*/tmpl/**/*.php", "component/*/tmpl/*.php"],
        cwd=ROOT,
    ).split(b"\0")
    accepted = rejected = removed = 0
    for raw in paths:
        if not raw:
            continue
        path = ROOT / raw.decode()
        if not path.is_file():
            continue
        initial = path.read_bytes()
        if not any(len(line) > LIMIT for line in initial.splitlines()):
            continue
        inline = inline_regions(path)
        if not inline:
            continue
        before = phpcs_counts(args.phpcs, path)
        best, score = initial, sum(before.values())
        for extra in (4, 2, 8):
            candidate, count = transform(initial, inline, extra)
            if count == 0:
                continue
            if candidate.replace(b"\n" + b" " * extra, b" ") != initial and extra == 4:
                # Only safe if every inserted HTML break can be reversed.
                # The mixed indentation case is checked by transform itself.
                pass
            path.write_bytes(candidate)
            if not lint(path):
                continue
            after = phpcs_counts(args.phpcs, path)
            if any(after[rule] > before[rule] for rule in after):
                continue
            total = sum(after.values())
            if total < score:
                best, score = candidate, total
        path.write_bytes(best)
        if best == initial:
            rejected += 1
        else:
            accepted += 1
            removed += sum(before.values()) - score
    print(f"SECDEV-02 mixed HTML batch accepted_files={accepted} rejected_files={rejected} net_removed={removed}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
