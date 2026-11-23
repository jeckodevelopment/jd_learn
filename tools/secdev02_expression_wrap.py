#!/usr/bin/env python3
"""SECDEV-02: conservative mass line wrapping, guarded by PHP token identity.

Changes are accepted file-by-file only if PHP lint succeeds and PHPCS PSR-12
decreases without increasing the count of any rule. No semantic PHP token,
including literal strings, comments, or HTML, may change.
"""
from __future__ import annotations

import argparse
from collections import Counter
import hashlib
import json
from pathlib import Path
import re
import subprocess

ROOT = Path(__file__).resolve().parents[1]
LIMIT = 120
# Matches candidate line breakpoints; token equality rejects anything located
# within PHP strings, heredocs, comments, or inline HTML.
POINT = re.compile(r"(?<=,)(?=\s*\S)|(?=->)|(?=\?->)|(?=\s+&&\s+)|(?=\s+\|\|\s+)|(?=\s+\?\?\s+)|(?=\s+\.\s+)|(?=\s+=>\s*)")

PHP_TOKENS = r"""$tokens=token_get_all(file_get_contents($argv[1]));
$hash=hash_init("sha256");
foreach($tokens as $token) {
  if(is_array($token)) {
    if($token[0]===T_WHITESPACE) continue;
    hash_update($hash, $token[0] . ":" . strlen($token[1]) . ":" . $token[1] . "\0");
  } else {
    hash_update($hash, "c:" . $token . "\0");
  }
}
echo hash_final($hash);"""


def signature(path: Path) -> str:
    command = ["php", "-r", PHP_TOKENS, str(path)]
    result = subprocess.run(command, text=True, capture_output=True, timeout=25, check=True)
    return result.stdout


def lint(path: Path) -> bool:
    return subprocess.run(["php", "-l", str(path)], capture_output=True, timeout=25).returncode == 0


def counts(phpcs: Path, path: Path) -> Counter[str]:
    result = subprocess.run(
        [str(phpcs), "--standard=PSR12", "--report=json", "--no-cache", str(path)],
        capture_output=True, text=True, timeout=120,
    )
    if result.returncode not in (0, 1, 2, 3):
        raise RuntimeError(f"PHPCS failed: {path}")
    try:
        report = json.loads(result.stdout)
        return Counter(m["source"] for m in next(iter(report["files"].values()))["messages"])
    except (ValueError, KeyError, TypeError, StopIteration) as exc:
        raise RuntimeError(f"Invalid PHPCS report: {path}") from exc


def segments(line: str, width: int, offset: int) -> list[str]:
    if len(line) <= LIMIT or line.lstrip().startswith(("//", "#", "*", "/*")):
        return [line]
    indent = len(line) - len(line.lstrip())
    # Do not modify inline HTML or PHP open/close transitions; untrusted
    # source-line spans are conservatively ignored.
    if "<?" in line or "?>" in line:
        return [line]
    points = [match.start() for match in POINT.finditer(line)]
    if not points:
        return [line]
    result = []
    remaining = line
    continuation = " " * min(indent + offset, 24)
    for _ in range(20):
        if len(remaining) <= LIMIT:
            break
        # Select rightmost useful split below width to avoid tiny fragments.
        choices = [p for p in points if 35 <= p <= width and p < len(remaining) - 8]
        if not choices:
            break
        split = choices[-1]
        left, right = remaining[:split].rstrip(), remaining[split:].lstrip()
        if not left or not right:
            break
        result.append(left)
        remaining = continuation + right
        points = [match.start() for match in POINT.finditer(remaining)]
    result.append(remaining)
    return result if all(x for x in result) else [line]


def transform(source: str, width: int, offset: int) -> str:
    return "\n".join(
        piece for line in source.split("\n")
        for piece in segments(line, width, offset)
    )


def run() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--phpcs", type=Path)
    parser.add_argument("--apply", action="store_true")
    parser.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    if args.self_test:
        example = "    $q = $this->db->createQuery()->select('*')->from('#__table')->where('id = :id')->where('owner = :owner')->where('published = 1');"
        assert len(example) > LIMIT
        assert transform(example, 110, 4) != example
        assert transform("echo 'text';", 105, 4) == "echo 'text';"
        print("SECDEV-02 token-preserving wrapper self-test OK")
        return 0
    if not args.apply or args.phpcs is None or not args.phpcs.is_file():
        parser.error("--apply and valid --phpcs required")
    paths = subprocess.check_output(["git", "ls-files", "-z", "--", "*.php"], cwd=ROOT).split(b"\0")
    accepted = removed = rejected = 0
    for item in paths:
        if not item:
            continue
        path = ROOT / item.decode()
        if not path.is_file():
            continue
        try:
            source = path.read_text(encoding="utf-8")
        except UnicodeDecodeError:
            continue
        if not any(len(line) > LIMIT for line in source.splitlines()):
            continue
        variants = [(110, 4), (105, 4), (110, 8), (95, 4), (115, 0)]
        candidates = []
        for width, offset in variants:
            candidate = transform(source, width, offset)
            if candidate != source and candidate not in candidates:
                candidates.append(candidate)
        if not candidates:
            continue
        before = counts(args.phpcs, path)
        original_token = signature(path)
        best = source
        best_score = sum(before.values())
        for candidate in candidates:
            path.write_text(candidate, encoding="utf-8")
            if not lint(path) or signature(path) != original_token:
                continue
            after = counts(args.phpcs, path)
            if any(after[rule] > count for rule, count in before.items()) or any(
                rule not in before for rule in after
            ):
                continue
            score = sum(after.values())
            if score < best_score:
                best, best_score = candidate, score
        path.write_text(best, encoding="utf-8")
        if best == source:
            rejected += 1
        else:
            accepted += 1
            removed += sum(before.values()) - best_score
    print(f"SECDEV-02 token-safe mass wrap: accepted_files={accepted} "
          f"rejected_files={rejected} net_removed={removed}")
    return 0


if __name__ == "__main__":
    raise SystemExit(run())
