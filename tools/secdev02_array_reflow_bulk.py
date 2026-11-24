#!/usr/bin/env python3
"""Bulk format single-line PHP bracket lists, preserving PHP tokens.

Only commas at top level in one-line bracket pairs are wrapped. PHP lexer
token identity, PHP syntax and per-rule PHPCS counts must pass per file.
Unchanged or unsafe files are restored immediately.
"""
from __future__ import annotations

import argparse
import json
from pathlib import Path
import subprocess

from secdev02_lexer_bulk_wrap import ROOT, lex, lint, phpcs_counts

LIMIT = 120
ARRAYS = r'''
$src = file_get_contents($argv[1]);
$offset = 0; $stack = []; $pairs = [];
$paren = 0; $curly = 0;
foreach (token_get_all($src) as $token) {
    $text = is_array($token) ? $token[1] : $token;
    if (!is_array($token)) {
        if ($text === '(') $paren++;
        if ($text === ')') $paren--;
        if ($text === '{') $curly++;
        if ($text === '}') $curly--;
        if ($text === '[') {
            $stack[] = ['start' => $offset, 'paren' => $paren, 'curly' => $curly, 'commas' => []];
        }
        if ($text === ',' && $stack) {
            $index = count($stack) - 1;
            if ($stack[$index]['paren'] === $paren && $stack[$index]['curly'] === $curly) {
                $stack[$index]['commas'][] = $offset;
            }
        }
        if ($text === ']' && $stack) {
            $top = array_pop($stack);
            $pairs[] = [$top['start'], $offset, $top['commas']];
        }
    }
    $offset += strlen($text);
}
echo json_encode($pairs, JSON_THROW_ON_ERROR);
'''


def scan(path: Path) -> list:
    result = subprocess.run(
        ["php", "-r", ARRAYS, str(path)], check=True,
        capture_output=True, text=True, timeout=30,
    )
    return json.loads(result.stdout)


def propose(source: bytes, pairs: list, max_width: int) -> bytes:
    patches = []
    selected = []
    # Favor larger bracket spans, but never select overlapping edits.
    for begin, end, commas in sorted(pairs, key=lambda item: item[1] - item[0], reverse=True):
        if len(commas) < 2 or end <= begin:
            continue
        first = source.rfind(b"\n", 0, begin) + 1
        last = source.find(b"\n", begin)
        if last < 0:
            last = len(source)
        if end >= last or last - first <= LIMIT:
            continue
        if any(begin < right and end > left for left, right in selected):
            continue
        prefix = source[first:begin]
        if b"<?" in prefix or b"?>" in prefix:
            continue
        line_indent = len(prefix) - len(prefix.lstrip(b" "))
        if line_indent > 28:
            continue
        indent = b" " * line_indent
        nested = indent + b" " * 4
        bounds = [begin + 1] + [offset + 1 for offset in commas] + [end]
        parts = [
            source[bounds[i]:bounds[i + 1]].strip(b" \t")
            for i in range(len(bounds) - 1)
        ]
        if not all(parts):
            continue
        if any(len(p) + len(nested) > max_width for p in parts):
            continue
        # Every comma belongs to the preceding segment; surrounding whitespace
        # moves but token spelling and order are unchanged.
        replacement = b"[\n" + nested + (b"\n" + nested).join(parts) + b"\n" + indent + b"]"
        patches.append((begin, end + 1, replacement))
        selected.append((begin, end))
    changed = bytearray(source)
    for begin, end, value in sorted(patches, reverse=True):
        changed[begin:end] = value
    return bytes(changed)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--self-test", action="store_true")
    parser.add_argument("--phpcs", type=Path)
    args = parser.parse_args()
    if args.self_test:
        sample = b"$values = ['alpha' => 1, 'beta' => 2, 'gamma' => 3];"
        begin, end = sample.index(b"["), sample.index(b"]")
        commas = [i for i, x in enumerate(sample) if x == 44]
        assert propose(sample, [[begin, end, commas]], 90) == sample
        long = b"$values = [" + b"'a' => 'something', " * 12 + b"'z' => 'last'];\n"
        begin, end = long.index(b"["), long.index(b"]")
        commas = [i for i, x in enumerate(long) if x == 44]
        updated = propose(long, [[begin, end, commas]], 120)
        assert updated != long and b"[\n    'a'" in updated
        print("SECDEV-02 token-preserving PHP array reflow fixtures: OK")
        return 0
    if args.phpcs is None or not args.phpcs.is_file():
        parser.error("--phpcs required")
    paths = subprocess.check_output(
        ["git", "ls-files", "-z", "--", "*.php"], cwd=ROOT
    ).split(b"\0")
    accepted = rejected = eliminated = 0
    for raw in paths:
        if not raw:
            continue
        path = ROOT / raw.decode()
        if not path.is_file():
            continue
        original = path.read_bytes()
        if not any(len(line) > LIMIT for line in original.splitlines()):
            continue
        matches = scan(path)
        if not matches:
            continue
        old_tokens = lex(path)["hash"]
        old = phpcs_counts(args.phpcs, path)
        best, least = original, sum(old.values())
        for width in (120, 110):
            updated = propose(original, matches, width)
            if updated == original:
                continue
            path.write_bytes(updated)
            if not lint(path) or lex(path)["hash"] != old_tokens:
                continue
            after = phpcs_counts(args.phpcs, path)
            if any(after[k] > old[k] for k in after):
                continue
            score = sum(after.values())
            if score < least:
                best, least = updated, score
        path.write_bytes(best)
        if best == original:
            rejected += 1
        else:
            accepted += 1
            eliminated += sum(old.values()) - least
    print(
        f"SECDEV-02 PHP arrays: accepted_files={accepted} "
        f"rejected_files={rejected} net_removed={eliminated}"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
