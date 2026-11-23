#!/usr/bin/env python3
"""SECDEV-02: conservative repository-wide wrapping of long // comments.

Only standalone // comment lines are candidates. PHPCS and PHP lint must
confirm a strictly lower count without adding any violation per rule.
"""
from __future__ import annotations

import argparse
from collections import Counter
import json
from pathlib import Path
import re
import subprocess

ROOT = Path(__file__).resolve().parents[1]
MAX_LINE = 120
COMMENT = re.compile(r"^(\s*//\s+)(\S.*)$")
UNSAFE = re.compile(r"https?://|\b(?:phpcs|phpstan|noinspection|noqa|@(?:var|param|return)|TODO:)\b", re.I)


def wrap_line(line: str) -> list[str]:
    if len(line) <= MAX_LINE:
        return [line]
    match = COMMENT.match(line)
    if not match:
        return [line]
    prefix, body = match.groups()
    if len(prefix) > 28 or UNSAFE.search(body):
        return [line]
    words = body.split(" ")
    if not all(words) or any(len(word) > MAX_LINE - len(prefix) for word in words):
        return [line]
    result = []
    current = prefix
    for word in words:
        sep = "" if current == prefix else " "
        if len(current) + len(sep) + len(word) > MAX_LINE:
            result.append(current)
            current = prefix + word
        else:
            current += sep + word
    result.append(current)
    return result if len(result) > 1 else [line]


def counts(phpcs: Path, path: Path) -> Counter[str]:
    process = subprocess.run(
        [str(phpcs), "--standard=PSR12", "--report=json", "--no-cache", str(path)],
        capture_output=True, text=True, timeout=90, check=False,
    )
    if process.returncode not in (0, 1, 2, 3):
        raise RuntimeError(f"PHPCS failed on {path.name}: {process.returncode}")
    try:
        data = json.loads(process.stdout)
        messages = next(iter(data["files"].values()))["messages"]
        return Counter(msg["source"] for msg in messages)
    except (ValueError, KeyError, StopIteration, TypeError) as exc:
        raise RuntimeError(f"Invalid PHPCS report for {path.name}") from exc


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--phpcs", type=Path)
    parser.add_argument("--apply", action="store_true")
    parser.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    if args.self_test:
        assert wrap_line("// " + "word " * 30)[0].startswith("// ")
        assert len(wrap_line("// " + "word " * 30)) > 1
        assert wrap_line("echo '" + "x" * 140 + "';") == ["echo '" + "x" * 140 + "';"]
        assert wrap_line("// https://example.org/" + "x" * 130) == ["// https://example.org/" + "x" * 130]
        print("SECDEV-02 comment wrapper negative fixtures OK")
        return 0
    if not args.apply or args.phpcs is None or not args.phpcs.is_file():
        parser.error("--apply and a valid --phpcs are required")
    files = subprocess.check_output(
        ["git", "ls-files", "-z", "--", "*.php"], cwd=ROOT,
    ).split(b"\0")
    accepted = 0
    eliminated = 0
    rejected = 0
    for raw in files:
        if not raw:
            continue
        path = ROOT / raw.decode()
        if not path.is_file():
            continue
        try:
            original = path.read_text(encoding="utf-8")
        except UnicodeDecodeError:
            continue
        transformed = "\n".join(
            part for line in original.split("\n") for part in wrap_line(line)
        )
        if transformed == original:
            continue
        old = counts(args.phpcs, path)
        path.write_text(transformed, encoding="utf-8")
        lint = subprocess.run(["php", "-l", str(path)], capture_output=True)
        if lint.returncode != 0:
            path.write_text(original, encoding="utf-8")
            rejected += 1
            continue
        new = counts(args.phpcs, path)
        if any(new[key] > value for key, value in old.items()) or any(
            key not in old for key in new
        ) or sum(new.values()) >= sum(old.values()):
            path.write_text(original, encoding="utf-8")
            rejected += 1
            continue
        accepted += 1
        eliminated += sum(old.values()) - sum(new.values())
    print(f"SECDEV-02 bulk comments: accepted_files={accepted} "
          f"rejected_files={rejected} net_removed={eliminated}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
