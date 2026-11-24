#!/usr/bin/env python3
"""Reduce long PHP expressions through safe, explicit FQCN imports.

Convert fully-qualified Joomla/JD Learn class names only when their PHP lexer
tokens occur on lines over 120 characters, using a unique namespaced alias.
Each file must preserve PHP syntax, exact source-gate contracts, and improve
the complete per-rule PHPCS score. Never alter strings or HTML comments.
Aliases are unique within the file and never shadow existing identifiers.
"""
from __future__ import annotations

import argparse
import json
from pathlib import Path
import re
import subprocess

from secdev02_lexer_bulk_wrap import ROOT, lint, phpcs_counts
from secdev02_calls_reflow_bulk import required_source_fragments

MAX_LINE = 120
TOKEN_LIST = r'''
$source = file_get_contents($argv[1]);
$offset = 0; $items = [];
foreach (token_get_all($source) as $token) {
    $id = is_array($token) ? $token[0] : 0;
    $text = is_array($token) ? $token[1] : $token;
    if ($id === T_NAME_FULLY_QUALIFIED && (str_starts_with($text, '\\\\Joomla\\\\') || str_starts_with($text, '\\\\Jlms\\\\'))) {
        $items[] = [$offset, strlen($text), $text];
    }
    $offset += strlen($text);
}
echo json_encode($items, JSON_THROW_ON_ERROR);
'''


def manifest(path: Path) -> list:
    result = subprocess.run(
        ["php", "-r", TOKEN_LIST, str(path)],
        capture_output=True, text=True, timeout=30, check=True,
    )
    return json.loads(result.stdout)


def injection_point(code: str) -> int | None:
    if not code.startswith("<?php") or "?>" in code:
        return None
    # Avoid multiple/nested namespaces and ambiguous PHP use scopes.
    namespaces = list(re.finditer(r"(?m)^\s*namespace\s+([\w\\]+)\s*(;|\{)", code))
    if len(namespaces) > 1 or (namespaces and namespaces[0].group(2) == "{"):
        return None
    if namespaces:
        return namespaces[0].end()
    first_use = re.search(r"(?m)^use\s+(?:(?:function|const)\s+)?[\w\\]+", code)
    if first_use:
        return first_use.start()
    declaration = re.search(r"(?m)^\s*declare\s*\([^;]+\)\s*;", code)
    if declaration:
        return declaration.end()
    # Beginning of the first pure PHP file statement.
    return len("<?php")


def proposal(code: str, tokens: list) -> str | None:
    insert = injection_point(code)
    if insert is None:
        return None
    # Any alias used in source already, regardless of case, is prohibited.
    selected: dict[str, str] = {}
    for position, length, original in tokens:
        if not original.startswith(("\\Joomla\\", "\\Jlms\\")):
            continue
        # PHP tokenizer offsets are bytes, and all source edits use UTF-8 bytes.
        content_bytes = code.encode("utf-8")
        begin = content_bytes.rfind(b"\n", 0, position) + 1
        end = content_bytes.find(b"\n", position)
        if end < 0:
            end = len(content_bytes)
        if end - begin <= MAX_LINE:
            continue
        short = original.rsplit("\\", 1)[-1]
        if not re.fullmatch(r"[A-Za-z_][A-Za-z0-9_]*", short):
            continue
        alias = ("Native" if original.startswith("\\Joomla\\") else "Jd") + short
        if len(alias) >= len(original) - 8:
            continue
        if re.search(r"\b" + re.escape(alias) + r"\b", code, flags=re.IGNORECASE):
            continue
        if alias in selected.values() or alias.lower() in (x.lower() for x in selected.values()):
            continue
        selected[original] = alias
    if not selected:
        return None
    data = code.encode("utf-8")
    patches = []
    for position, length, original in tokens:
        if original in selected:
            assert data[position:position + length] == original.encode()
            patches.append((position, length, selected[original].encode()))
    for position, length, replacement in reversed(patches):
        data = data[:position] + replacement + data[position + length:]
    result = data.decode("utf-8")
    imports = "".join(
        "\nuse " + name.lstrip("\\") + " as " + alias + ";"
        for name, alias in sorted(selected.items())
    ) + "\n"
    # Original insertion offset changes only if any affected FQCN comes first.
    # All fully-qualified expressions occur after import insertion.
    if insert > min(pos for pos, _, _ in patches):
        return None
    return result[:insert] + imports + result[insert:]


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--phpcs", type=Path)
    parser.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    if args.self_test:
        assert injection_point("<?php\ndeclare(strict_types=1);\n$x = 1;\n") is not None
        assert injection_point("<?php\nnamespace A {\nclass B {}\n}") is None
        print("SECDEV-02 PHP explicit import fixtures OK")
        return 0
    if not args.phpcs or not args.phpcs.is_file():
        parser.error("--phpcs must be installed")
    tracked = subprocess.check_output(
        ["git", "ls-files", "-z", "--", "*.php"], cwd=ROOT
    ).split(b"\0")
    contracts = required_source_fragments()
    accepted = rejected = removed = 0
    for raw in tracked:
        if not raw:
            continue
        name = raw.decode()
        path = ROOT / name
        if not path.is_file():
            continue
        initial = path.read_bytes()
        if not any(len(line) > MAX_LINE for line in initial.splitlines()):
            continue
        try:
            source = initial.decode("utf-8")
        except UnicodeDecodeError:
            continue
        tokens = manifest(path)
        if not tokens:
            continue
        candidate = proposal(source, tokens)
        if not candidate or candidate == source:
            continue
        protected = contracts.get(name, set())
        if any(fragment in initial and fragment not in candidate.encode() for fragment in protected):
            continue
        before = phpcs_counts(args.phpcs, path)
        path.write_text(candidate, encoding="utf-8")
        if not lint(path):
            path.write_bytes(initial)
            rejected += 1
            continue
        after = phpcs_counts(args.phpcs, path)
        if (
            any(after[k] > before[k] for k in after)
            or sum(after.values()) >= sum(before.values())
        ):
            path.write_bytes(initial)
            rejected += 1
            continue
        accepted += 1
        removed += sum(before.values()) - sum(after.values())
    print(
        f"SECDEV-02 explicit PHP class imports: accepted_files={accepted} "
        f"rejected_files={rejected} net_removed={removed}"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
