#!/usr/bin/env python3
"""Project only formatter whitespace into original pure-PHP tokens.

The external formatter can rewrite expressions; we never accept those edits.
Only whitespace between identical adjacent significant PHP tokens is eligible.
PHP lint, full token identity, existing raw source contracts and PHPCS per-rule
zero-regression make this a conservative mechanical remediation.
"""
from __future__ import annotations

import argparse
import base64
from difflib import SequenceMatcher
import json
from pathlib import Path
import subprocess
import tempfile

from secdev02_lexer_bulk_wrap import ROOT, lex, lint, phpcs_counts
from secdev02_calls_reflow_bulk import required_source_fragments

LIMIT = 120
TOKENS = r'''
$source = file_get_contents($argv[1]);
$offset = 0; $items = [];
foreach (token_get_all($source) as $token) {
    $id = is_array($token) ? $token[0] : 0;
    $value = is_array($token) ? $token[1] : $token;
    $len = strlen($value);
    if ($id !== T_WHITESPACE) {
        $items[] = [$id, base64_encode($value), $offset, $offset + $len];
    }
    $offset += $len;
}
echo json_encode($items, JSON_THROW_ON_ERROR);
'''


def manifest(path: Path) -> list:
    run = subprocess.run(
        ["php", "-r", TOKENS, str(path)], capture_output=True,
        check=True, text=True, timeout=30,
    )
    return [
        (int(kind), base64.b64decode(content), int(start), int(end))
        for kind, content, start, end in json.loads(run.stdout)
    ]


def project(original: bytes, old: list, generated: bytes, new: list) -> bytes:
    identity_old = [(kind, content) for kind, content, _a, _b in old]
    identity_new = [(kind, content) for kind, content, _a, _b in new]
    alignment = SequenceMatcher(None, identity_old, identity_new, autojunk=True)
    patches = []
    for a, b, size in alignment.get_matching_blocks():
        for delta in range(size - 1):
            left, right = old[a + delta], old[a + delta + 1]
            candidate_left, candidate_right = new[b + delta], new[b + delta + 1]
            before = original[left[3]:right[2]]
            after = generated[candidate_left[3]:candidate_right[2]]
            if not after or after == before or b"\n" not in after or b"\n" in before:
                continue
            if any(byte not in b" \t\r\n" for byte in after):
                continue
            line_start = original.rfind(b"\n", 0, left[3]) + 1
            line_end = original.find(b"\n", left[3])
            if line_end < 0:
                line_end = len(original)
            if line_end - line_start <= LIMIT:
                continue
            patches.append((left[3], right[2], after))
    result = bytearray(original)
    for start, end, text in reversed(patches):
        result[start:end] = text
    return bytes(result)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--self-test", action="store_true")
    parser.add_argument("--phpcs", type=Path)
    parser.add_argument("--prettier", type=Path)
    args = parser.parse_args()
    if args.self_test:
        old = b"$a = foo(1, 2, 3);"
        formatted = b"$a = foo(\n    1,\n    2,\n    3\n);"
        # The synthetic sample is under 120, hence no edit is permitted.
        first = [(0, b"foo", 5, 8), (0, b"(", 8, 9), (0, b"1", 9, 10)]
        assert project(old, first, formatted, first) == old
        print("SECDEV-02 unchanged-token whitespace projection fixtures: OK")
        return 0
    if not args.phpcs or not args.phpcs.is_file() or not args.prettier or not args.prettier.is_file():
        parser.error("--phpcs and --prettier binaries required")
    paths = subprocess.check_output(
        ["git", "ls-files", "-z", "--", "*.php"], cwd=ROOT,
    ).split(b"\0")
    contracts = required_source_fragments()
    formatter_root = args.prettier.parent.parent.parent.resolve()
    accepted = rejected = removed = 0
    with tempfile.TemporaryDirectory(prefix="secdev02-format-") as folder:
        tmp = Path(folder) / "original.php"
        for item in paths:
            if not item:
                continue
            name = item.decode()
            path = ROOT / name
            if not path.is_file():
                continue
            original = path.read_bytes()
            if b"?>" in original or not original.startswith(b"<?php"):
                continue
            if not any(len(line) > LIMIT for line in original.splitlines()):
                continue
            run = subprocess.run(
                [
                    str(args.prettier.resolve()), "--plugin=@prettier/plugin-php",
                    "--parser=php", "--print-width=120", str(path.resolve()),
                ], cwd=formatter_root, capture_output=True, timeout=45, check=False,
            )
            if run.returncode or not run.stdout or run.stdout == original:
                continue
            tmp.write_bytes(run.stdout)
            candidate = project(original, manifest(path), run.stdout, manifest(tmp))
            if candidate == original:
                continue
            required = contracts.get(name, set())
            if any(part in original and part not in candidate for part in required):
                rejected += 1
                continue
            prior_hash = lex(path)["hash"]
            before = phpcs_counts(args.phpcs, path)
            path.write_bytes(candidate)
            if not lint(path) or lex(path)["hash"] != prior_hash:
                path.write_bytes(original)
                rejected += 1
                continue
            after = phpcs_counts(args.phpcs, path)
            if any(after[k] > before[k] for k in after) or sum(after.values()) >= sum(before.values()):
                path.write_bytes(original)
                rejected += 1
                continue
            accepted += 1
            removed += sum(before.values()) - sum(after.values())
    print(
        f"SECDEV-02 projected PHP whitespace: accepted_files={accepted} "
        f"rejected_files={rejected} net_removed={removed}"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
