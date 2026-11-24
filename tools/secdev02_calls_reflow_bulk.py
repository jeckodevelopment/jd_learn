#!/usr/bin/env python3
"""Safe source-wide reflow of lengthy PHP function argument and parameter lists.

Only real parenthesized PHP tokens with top-level comma separators qualify.
The full PHP token sequence, syntax and PHPCS per-rule baselines must survive;
otherwise every changed file is restored byte-for-byte.
"""
from __future__ import annotations

import argparse
import ast
import json
import re
from pathlib import Path
import subprocess

from secdev02_lexer_bulk_wrap import ROOT, lex, lint, phpcs_counts

LIMIT = 120
CALLS = r'''
$src = file_get_contents($argv[1]);
$offset = 0; $stack = []; $pairs = [];
$brackets = 0; $curly = 0;
foreach (token_get_all($src) as $token) {
    $text = is_array($token) ? $token[1] : $token;
    if (!is_array($token)) {
        if ($text === '[') $brackets++;
        if ($text === ']') $brackets--;
        if ($text === '{') $curly++;
        if ($text === '}') $curly--;
        if ($text === '(') {
            $stack[] = ['start' => $offset, 'brackets' => $brackets, 'curly' => $curly, 'commas' => []];
        }
        if ($text === ',' && $stack) {
            $index = count($stack) - 1;
            if ($stack[$index]['brackets'] === $brackets && $stack[$index]['curly'] === $curly) {
                $stack[$index]['commas'][] = $offset;
            }
        }
        if ($text === ')' && $stack) {
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
        ["php", "-r", CALLS, str(path)], check=True,
        capture_output=True, text=True, timeout=30,
    )
    return json.loads(result.stdout)


def propose(data: bytes, pairs: list, max_width: int) -> bytes:
    patches = []
    chosen = []
    # Outer calls first. Overlapping inner calls are left to later passes.
    for begin, end, commas in sorted(pairs, key=lambda item: item[1] - item[0], reverse=True):
        if not commas or end <= begin:
            continue
        first = data.rfind(b"\n", 0, begin) + 1
        last = data.find(b"\n", begin)
        if last < 0:
            last = len(data)
        if end >= last or last - first <= LIMIT:
            continue
        if any(begin < right and end > left for left, right in chosen):
            continue
        prefix = data[first:begin]
        if b"<?" in prefix or b"?>" in prefix:
            continue
        spaces = len(prefix) - len(prefix.lstrip(b" "))
        if spaces > 28:
            continue
        indent = b" " * spaces
        continuation = indent + b" " * 4
        bounds = [begin + 1] + [offset + 1 for offset in commas] + [end]
        parts = [
            data[bounds[index]:bounds[index + 1]].strip(b" \t")
            for index in range(len(bounds) - 1)
        ]
        if not all(parts):
            continue
        if any(len(part) + len(continuation) > max_width for part in parts):
            continue
        replacement = b"(\n" + continuation + (b"\n" + continuation).join(parts) + b"\n" + indent + b")"
        patches.append((begin, end + 1, replacement))
        chosen.append((begin, end))
    updated = bytearray(data)
    for begin, end, value in sorted(patches, reverse=True):
        updated[begin:end] = value
    return bytes(updated)



def required_source_fragments() -> dict[str, set[bytes]]:
    """Preserve raw PHP fragments enforced by existing structural check.py.

    The syntax-preserving formatter must not break byte-exact contracts relied
    upon by the existing safety suite. Never relax these test assertions.
    """
    checks = (ROOT / "tools/check.py").read_text(encoding="utf-8")
    bindings = {}
    for found in re.finditer(
        r'(?m)^\s*(\w+)\s*=\s*\(\s*ROOT\s*/\s*[\'"]([^\'"]+)[\'"]\s*\)\.read_text\(',
        checks,
    ):
        bindings[found.group(1)] = found.group(2)
    snippets: dict[str, set[bytes]] = {}
    for node in ast.walk(ast.parse(checks)):
        if not isinstance(node, ast.Assert):
            continue
        for expr in ast.walk(node.test):
            if isinstance(expr, ast.Compare):
                if (
                    isinstance(expr.left, ast.Constant)
                    and isinstance(expr.left.value, str)
                    and expr.ops and isinstance(expr.ops[0], ast.In)
                    and expr.comparators and isinstance(expr.comparators[0], ast.Name)
                ):
                    name = expr.comparators[0].id
                    if name in bindings:
                        snippets.setdefault(bindings[name], set()).add(
                            expr.left.value.encode("utf-8")
                        )
            if (
                isinstance(expr, ast.Call)
                and isinstance(expr.func, ast.Attribute)
                and expr.func.attr == "count"
                and isinstance(expr.func.value, ast.Name)
                and expr.func.value.id in bindings
                and expr.args and isinstance(expr.args[0], ast.Constant)
                and isinstance(expr.args[0].value, str)
            ):
                snippets.setdefault(bindings[expr.func.value.id], set()).add(
                    expr.args[0].value.encode("utf-8")
                )
    return snippets


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--self-test", action="store_true")
    parser.add_argument("--phpcs", type=Path)
    args = parser.parse_args()
    if args.self_test:
        sample = b"$data = call(" + b"$veryLongVariableName, " * 12 + b"$finalParameter);\n"
        begin, end = sample.index(b"("), sample.index(b")")
        commas = [i for i, x in enumerate(sample) if x == 44]
        output = propose(sample, [[begin, end, commas]], 120)
        assert output != sample and b"(\n    $veryLongVariableName" in output
        assert propose(b"foo($x);\n", [[3, 6, []]], 120) == b"foo($x);\n"
        print("SECDEV-02 call argument reflow fixtures: OK")
        return 0
    if args.phpcs is None or not args.phpcs.is_file():
        parser.error("--phpcs required")
    entries = subprocess.check_output(
        ["git", "ls-files", "-z", "--", "*.php"], cwd=ROOT
    ).split(b"\0")
    accepted = rejected = removed = 0
    protected = required_source_fragments()
    for item in entries:
        if not item:
            continue
        path = ROOT / item.decode()
        if not path.is_file():
            continue
        original = path.read_bytes()
        required = protected.get(item.decode(), set())
        if not any(len(line) > LIMIT for line in original.splitlines()):
            continue
        matches = scan(path)
        if not matches:
            continue
        old_tokens = lex(path)["hash"]
        old = phpcs_counts(args.phpcs, path)
        best, best_score = original, sum(old.values())
        for width in (120, 110):
            candidate = propose(original, matches, width)
            if candidate == original or any(
                fragment in original and fragment not in candidate
                for fragment in required
            ):
                continue
            path.write_bytes(candidate)
            if not lint(path) or lex(path)["hash"] != old_tokens:
                continue
            after = phpcs_counts(args.phpcs, path)
            if any(after[rule] > old[rule] for rule in after):
                continue
            score = sum(after.values())
            if score < best_score:
                best, best_score = candidate, score
        path.write_bytes(best)
        if best == original:
            rejected += 1
        else:
            accepted += 1
            removed += sum(old.values()) - best_score
    print(
        f"SECDEV-02 call lists: accepted_files={accepted} "
        f"rejected_files={rejected} net_removed={removed}"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
