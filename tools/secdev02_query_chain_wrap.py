#!/usr/bin/env python3
"""SECDEV-02: targeted fluent database-query formatting.

Only PHP lexer-confirmed object operators in long query/bind chains are
separated. Reject any lexical, syntactic or PHPCS-per-rule regression.
"""
from __future__ import annotations

import argparse
from collections import Counter
from pathlib import Path
import subprocess

from secdev02_lexer_bulk_wrap import ROOT, lex, phpcs_counts, lint

ROOTS = (
    "component/administrator/src/Application",
    "component/administrator/src/Model",
    "component/site/src",
    "libraries/jlms/src",
)
LIMIT = 120


def apply(source: bytes, points: list[list], extra: int) -> bytes:
    inserts = []
    offsets = []
    cursor = 0
    for line in source.splitlines(keepends=True):
        begin, end = cursor, cursor + len(line.rstrip(b"\r\n"))
        cursor += len(line)
        if end - begin <= LIMIT:
            continue
        if b"->" not in line:
            continue
        if not any(hint in line for hint in (
            b"createQuery()", b"->where(", b"->bind(", b"->order(",
            b"->join(", b"->leftJoin(", b"->from(", b"->select(",
        )):
            continue
        indent = len(line) - len(line.lstrip(b" \t"))
        if indent > 24:
            continue
        safe = [
            p for p, op in points if op in ("->", "?->")
            and begin + 35 <= p < end - 12
        ]
        # Start after the first method of the query chain, preserving receiver.
        if not safe:
            continue
        prefix = b" " * min(indent + extra, 28)
        for pos in safe:
            # A short last segment doesn't need to be split.
            if pos > end - 35:
                continue
            inserts.append((pos, b"\n" + prefix))
    if not inserts:
        return source
    updated = bytearray(source)
    for pos, content in sorted(set(inserts), reverse=True):
        updated[pos:pos] = content
    return bytes(updated)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--phpcs", type=Path)
    parser.add_argument("--apply", action="store_true")
    parser.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    if args.self_test:
        sample = b"        $q = $db->createQuery()->select('*')->from('t')->where('id = :id')->where('course = :course')->bind(':id', $id)->bind(':course', $course);\n"
        pos = [[i, "->"] for i in range(len(sample)) if sample[i:i+2] == b"->"]
        result = apply(sample, pos, 4)
        assert result != sample
        assert b"\n            ->" in result
        print("SECDEV-02 query-chain fixture OK")
        return 0
    if not args.apply or args.phpcs is None or not args.phpcs.is_file():
        parser.error("--apply and valid --phpcs required")
    accepted = removed = rejected = 0
    files = subprocess.check_output(["git", "ls-files", "-z", "--", "*.php"], cwd=ROOT).split(b"\0")
    for item in files:
        if not item:
            continue
        name = item.decode("utf-8")
        if not name.startswith(ROOTS):
            continue
        path = ROOT / name
        if not path.is_file():
            continue
        original = path.read_bytes()
        if not any(len(line) > LIMIT for line in original.splitlines()):
            continue
        manifest = lex(path)
        variants = []
        for extra in (4, 8, 0, 12):
            candidate = apply(original, manifest["points"], extra)
            if candidate != original and candidate not in variants:
                variants.append(candidate)
        if not variants:
            continue
        old = phpcs_counts(args.phpcs, path)
        best, score = original, sum(old.values())
        for candidate in variants:
            path.write_bytes(candidate)
            if not lint(path) or lex(path)["hash"] != manifest["hash"]:
                continue
            new = phpcs_counts(args.phpcs, path)
            if any(new[k] > v for k, v in old.items()) or any(k not in old for k in new):
                continue
            if sum(new.values()) < score:
                best, score = candidate, sum(new.values())
        path.write_bytes(best)
        if best == original:
            rejected += 1
        else:
            accepted += 1
            removed += sum(old.values()) - score
    print(f"SECDEV-02 fluent query refactoring: accepted_files={accepted} "
          f"rejected_files={rejected} net_removed={removed}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
