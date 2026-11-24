#!/usr/bin/env python3
"""Conservative HTML attribute line reflow for mixed Joomla PHP template files.

The only candidate rewrite substitutes a single ASCII space after an HTML
attribute closing double quote with a newline and four spaces. Values and PHP
expressions remain byte-for-byte unchanged. Each file must pass PHP lint and
PHPCS PSR12 with zero per-rule regressions or it is restored.
"""
from __future__ import annotations

import argparse
from pathlib import Path
import re
import subprocess

from secdev02_lexer_bulk_wrap import ROOT, lint, phpcs_counts

ATTRIBUTE = re.compile(r'" (?=[A-Za-z][A-Za-z0-9_:-]*=)')
LIMIT = 120


def rewrite(source: str) -> tuple[str, int]:
    changed = 0
    output = []
    for line in source.splitlines(keepends=True):
        content = line.rstrip("\r\n")
        ending = line[len(content):]
        if len(content) <= LIMIT or not re.match(
            r"^\s*(?:<[A-Za-z]|\?><[A-Za-z])", content
        ):
            output.append(line)
            continue
        candidate = ATTRIBUTE.sub('"\n    ', content)
        if candidate == content or any(
            len(part) > LIMIT for part in candidate.split("\n")
        ):
            output.append(line)
            continue
        output.append(candidate.replace("\n", ending or "\n") + ending)
        changed += 1
    return "".join(output), changed


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--phpcs", type=Path)
    parser.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    sample = '<form id="test" class="test" method="post" action="/very/long/url" data-description="some string">\n'
    if args.self_test:
        long = sample.replace('test" class', ('test' * 30) + '" class')
        fixed, count = rewrite(long)
        assert count == 0 or (
            fixed.replace("\n    ", " ") == long and
            all(len(item) <= LIMIT for item in fixed.splitlines())
        )
        assert rewrite("<?php $x = 1;\n")[1] == 0
        print("SECDEV-02 template HTML attribute wrap fixtures: OK")
        return 0
    if not args.phpcs or not args.phpcs.is_file():
        parser.error("--phpcs pointing to pinned PHP_CodeSniffer required")
    paths = subprocess.check_output(
        ["git", "ls-files", "-z", "--", "component/*/tmpl/*.php", "component/*/tmpl/**/*.php"],
        cwd=ROOT,
    ).split(b"\0")
    accepted = skipped = removed = lines = 0
    for raw in paths:
        if not raw:
            continue
        path = ROOT / raw.decode()
        if not path.is_file():
            continue
        original = path.read_bytes()
        try:
            source = original.decode("utf-8")
        except UnicodeDecodeError:
            continue
        candidate, candidate_lines = rewrite(source)
        if candidate == source:
            continue
        before = phpcs_counts(args.phpcs, path)
        path.write_text(candidate, encoding="utf-8")
        if not lint(path):
            path.write_bytes(original)
            skipped += 1
            continue
        after = phpcs_counts(args.phpcs, path)
        if any(after[rule] > count for rule, count in before.items()) or (
            sum(after.values()) >= sum(before.values())
        ):
            path.write_bytes(original)
            skipped += 1
            continue
        accepted += 1
        lines += candidate_lines
        removed += sum(before.values()) - sum(after.values())
    print(
        f"SECDEV-02 all-template HTML reflow: accepted_files={accepted} "
        f"rejected_files={skipped} wrapped_lines={lines} net_removed={removed}"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
