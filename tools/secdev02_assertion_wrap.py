#!/usr/bin/env python3
"""SECDEV-02: mass-wrap one-line test assertion calls without changing PHP tokens.

Only allowlisted test functions with complete one-line statements qualify.
Every changed file must pass PHP lint, lexer token identity and PHPCS PSR12
with zero introduced violations per rule, or it is restored byte-for-byte.
"""
from __future__ import annotations
import argparse
import re
import subprocess
from pathlib import Path
from secdev02_lexer_bulk_wrap import ROOT, lex, lint, phpcs_counts

FUNCS = {
    "expect", "rejects", "stateDenied", "stateExpect",
    "attemptExpect", "attemptReject", "learningExpect", "learningDenied",
    "manualExpect", "manualDenied", "migrationExpect", "migrationDenied",
    "quizExpect", "quizReject", "reviewExpect", "reviewReject",
    "assocExpect",
}
LINE = re.compile(r"^(\s*)([A-Za-z_][A-Za-z0-9_]*)\((.*)\);(\s*)$")
LIMIT = 120


def split_args(source: str) -> list[str] | None:
    """Split top-level PHP call arguments, retaining every non-whitespace byte."""
    args = []
    depth = [0, 0, 0]  # parentheses, brackets, braces
    start = 0
    quote = ""
    escaped = False
    for i, char in enumerate(source):
        if quote:
            if escaped:
                escaped = False
            elif char == "\\":
                escaped = True
            elif char == quote:
                quote = ""
            continue
        if char in ("'", '"', "\x60"):
            quote = char
            continue
        if char == "(":
            depth[0] += 1
        elif char == ")":
            depth[0] -= 1
        elif char == "[":
            depth[1] += 1
        elif char == "]":
            depth[1] -= 1
        elif char == "{":
            depth[2] += 1
        elif char == "}":
            depth[2] -= 1
        elif char == "," and depth == [0, 0, 0]:
            args.append(source[start:i + 1])
            start = i + 1
        if any(value < 0 for value in depth):
            return None
    if quote or any(depth) or not args:
        return None
    args.append(source[start:])
    if any(not item.strip().rstrip(",").strip() for item in args):
        return None
    return args


def rewrite(line: str, indentation: int) -> str:
    if len(line) <= LIMIT or line.lstrip().startswith(("//", "#")):
        return line
    found = LINE.match(line)
    if not found:
        return line
    prefix, func, expression, trailer = found.groups()
    if func not in FUNCS or len(prefix) > 20 or trailer.strip():
        return line
    args = split_args(expression)
    if args is None:
        return line
    spacer = prefix + " " * indentation
    # Preserve argument text, including comma separators. Existing indentation
    # just after each comma is whitespace; lexical equality is checked later.
    parts = [spacer + item.lstrip() for item in args]
    return f"{prefix}{func}(\n" + "\n".join(parts) + f"\n{prefix});"


def apply(text: str, indent: int) -> str:
    return "\n".join(rewrite(line, indent) for line in text.split("\n"))


def run() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--apply", action="store_true")
    parser.add_argument("--self-test", action="store_true")
    parser.add_argument("--phpcs", type=Path)
    args = parser.parse_args()
    if args.self_test:
        line = "    attemptExpect($thing->foo($id, ['key' => 1, 'extra' => 2]) && $test->all($id), 'an intentionally very long assertion description that goes beyond PSR twelve');"
        output = rewrite(line, 4)
        assert output.count("\n") == 3
        assert "\n        'an intentionally" in output
        assert rewrite("echo 1;", 4) == "echo 1;"
        assert split_args("'value, with comma', fn() => [1, 2], 'message'") is not None
        print("SECDEV-02 test assertion fixtures OK")
        return 0
    if not args.apply or not args.phpcs or not args.phpcs.is_file():
        parser.error("--apply and a valid --phpcs required")
    originals = subprocess.check_output(
        ["git", "ls-files", "-z", "--", "tests/*.php"], cwd=ROOT
    ).split(b"\0")
    accepted = rejected = removed = changed_calls = 0
    for item in originals:
        if not item:
            continue
        path = ROOT / item.decode()
        if not path.is_file():
            continue
        try:
            source = path.read_text(encoding="utf-8")
        except UnicodeDecodeError:
            continue
        candidates = []
        for spacing in (4, 8):
            candidate = apply(source, spacing)
            if candidate != source and candidate not in candidates:
                candidates.append(candidate)
        if not candidates:
            continue
        before_lex = lex(path)
        old = phpcs_counts(args.phpcs, path)
        best, minimum = source, sum(old.values())
        for candidate in candidates:
            path.write_text(candidate, encoding="utf-8")
            if not lint(path) or lex(path)["hash"] != before_lex["hash"]:
                continue
            current = phpcs_counts(args.phpcs, path)
            if any(current[r] > count for r, count in old.items()) or any(
                r not in old for r in current
            ):
                continue
            score = sum(current.values())
            if score < minimum:
                best, minimum = candidate, score
        path.write_text(best, encoding="utf-8")
        if best == source:
            rejected += 1
        else:
            accepted += 1
            removed += sum(old.values()) - minimum
            changed_calls += best.count("\n") - source.count("\n")
    print(f"SECDEV-02 assertions: accepted_files={accepted} rejected_files={rejected} "
          f"net_removed={removed} added_lines={changed_calls}")


if __name__ == "__main__":
    raise SystemExit(run())
