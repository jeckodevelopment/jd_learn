#!/usr/bin/env python3
"""SECDEV-02: token-position-aware line wrapping on all tracked PHP sources.

Only whitespace is inserted at PHP lexer-confirmed boundaries. Each candidate
must retain all significant token IDs/text, pass php -l, and decrease PHPCS
PSR12 findings without increasing any per-rule count.
"""
from __future__ import annotations

import argparse
from collections import Counter
import json
from pathlib import Path
import subprocess

ROOT = Path(__file__).resolve().parents[1]
LIMIT = 120
LEXER = r'''
$src = file_get_contents($argv[1]);
$offset = 0; $points = []; $hash = hash_init('sha256');
foreach (token_get_all($src) as $token) {
    $id = is_array($token) ? $token[0] : 0;
    $text = is_array($token) ? $token[1] : $token;
    $length = strlen($text);
    if ($id !== T_WHITESPACE) {
        hash_update($hash, pack('N', $id) . pack('N', $length) . $text);
    }
    if ($text === ',' || $text === ';') {
        $points[] = [$offset + $length, $text];
    } elseif (in_array($text, ['->', '?->', '&&', '||', '??', '=>', '.', '?'], true)) {
        $points[] = [$offset, $text];
    }
    $offset += $length;
}
echo json_encode(['hash' => hash_final($hash), 'points' => $points], JSON_THROW_ON_ERROR);
'''


def lex(path: Path) -> dict:
    result = subprocess.run(
        ["php", "-r", LEXER, str(path)],
        capture_output=True, text=True, timeout=35, check=True,
    )
    return json.loads(result.stdout)


def phpcs_counts(exe: Path, path: Path) -> Counter[str]:
    result = subprocess.run(
        [str(exe), "--standard=PSR12", "--no-cache", "--report=json", str(path)],
        capture_output=True, text=True, timeout=100,
    )
    if result.returncode not in (0, 1, 2, 3):
        raise RuntimeError(f"PHPCS failed for {path.name}: {result.returncode}")
    try:
        payload = json.loads(result.stdout)
        return Counter(x["source"] for x in next(iter(payload["files"].values()))["messages"])
    except (ValueError, KeyError, StopIteration, TypeError) as exc:
        raise RuntimeError(f"Invalid PHPCS report for {path.name}") from exc


def lint(path: Path) -> bool:
    return subprocess.run(
        ["php", "-l", str(path)], capture_output=True, timeout=35
    ).returncode == 0


def propose(data: bytes, points: list[list], width: int, extra: int) -> bytes:
    edits: list[tuple[int, bytes]] = []
    by_line: dict[int, list[int]] = {}
    for offset, operator in points:
        if operator not in (",", ";", "->", "?->", "&&", "||", "??", "=>", ".", "?"):
            continue
        if offset < 0 or offset > len(data):
            continue
        line_start = data.rfind(b"\n", 0, offset) + 1
        by_line.setdefault(line_start, []).append(offset)
    cursor = 0
    for line in data.splitlines(keepends=True):
        start, end = cursor, cursor + len(line.rstrip(b"\r\n"))
        cursor += len(line)
        if end - start <= LIMIT:
            continue
        stripped = line.lstrip(b" \t")
        if stripped.startswith((b"//", b"#", b"/*", b"*")):
            continue
        indent = len(line) - len(stripped)
        if indent > 32:
            continue
        continuation = b" " * min(28, indent + extra)
        candidates = sorted(set(p for p in by_line.get(start, []) if start + 30 <= p <= end - 9))
        position = start
        for _ in range(18):
            if end - position + (len(continuation) if position > start else 0) <= LIMIT:
                break
            max_width = width - (len(continuation) if position > start else 0)
            available = [p for p in candidates if position + 30 <= p <= position + max_width]
            if not available:
                break
            split = available[-1]
            # Inserting into bytes at a confirmed PHP token boundary leaves
            # comments, strings and emitted HTML completely unchanged.
            if split in (start, end):
                break
            edits.append((split, b"\n" + continuation))
            position = split
    if not edits:
        return data
    output = bytearray(data)
    for position, addition in sorted(edits, reverse=True):
        output[position:position] = addition
    return bytes(output)


def main() -> int:
    p = argparse.ArgumentParser()
    p.add_argument("--phpcs", type=Path)
    p.add_argument("--apply", action="store_true")
    p.add_argument("--self-test", action="store_true")
    args = p.parse_args()
    if args.self_test:
        sample = b"$x = $db->select('foo')->from('bar')->where('a')->where('b')->where('c')->where('d')->where('e')->where('f');\n"
        locations = [[i, "->"] for i in range(len(sample)) if sample[i:i+2] == b"->"]
        assert propose(sample, locations, 90, 4) != sample
        assert propose(b"<?php echo 1;\n", [[12, ";"]], 90, 4) == b"<?php echo 1;\n"
        print("SECDEV-02 lexer-position wrappers self-test passed")
        return 0
    if not args.apply or args.phpcs is None or not args.phpcs.is_file():
        p.error("--apply and a valid --phpcs are required")
    tracked = subprocess.check_output(
        ["git", "ls-files", "-z", "--", "*.php"], cwd=ROOT,
    ).split(b"\x00")
    accepted = rejected = removed = 0
    for filename in tracked:
        if not filename:
            continue
        path = ROOT / filename.decode("utf-8")
        if not path.is_file():
            continue
        original = path.read_bytes()
        if not any(len(line) > LIMIT for line in original.splitlines()):
            continue
        before_lex = lex(path)
        variants = []
        for width, extra in ((106, 4), (100, 4), (112, 4), (98, 8)):
            value = propose(original, before_lex["points"], width, extra)
            if value != original and value not in variants:
                variants.append(value)
        if not variants:
            continue
        before = phpcs_counts(args.phpcs, path)
        best, best_score = original, sum(before.values())
        for variant in variants:
            path.write_bytes(variant)
            if not lint(path) or lex(path)["hash"] != before_lex["hash"]:
                continue
            after = phpcs_counts(args.phpcs, path)
            if any(after[rule] > count for rule, count in before.items()):
                continue
            if any(rule not in before for rule in after):
                continue
            if sum(after.values()) < best_score:
                best, best_score = variant, sum(after.values())
        path.write_bytes(best)
        if best == original:
            rejected += 1
        else:
            accepted += 1
            removed += sum(before.values()) - best_score
    print(f"SECDEV-02 lexer bulk: accepted_files={accepted} "
          f"rejected_files={rejected} net_removed={removed}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
