#!/usr/bin/env python3
"""One coordinated PHP line-layout remediation using PHP-token-aware breaks.

Unlike earlier generic wrappers, this targets long fluent and boolean/concat
expressions with adaptive line-level split selection. All unchanged significant
PHP tokens, lint, complete source contracts and PHPCS per-rule nonregression
are mandatory before accepting any file.
"""
from __future__ import annotations

import argparse
import json
from pathlib import Path
import subprocess

from secdev02_lexer_bulk_wrap import ROOT, lex, lint, phpcs_counts
from secdev02_calls_reflow_bulk import required_source_fragments

MAX_WIDTH = 120
TOKEN_POINTS = r'''
$source = file_get_contents($argv[1]);
$offset = 0; $points = [];
foreach (token_get_all($source) as $token) {
    $id = is_array($token) ? $token[0] : 0;
    $value = is_array($token) ? $token[1] : $token;
    if ($id === T_OBJECT_OPERATOR || $id === T_NULLSAFE_OBJECT_OPERATOR ||
        $id === T_BOOLEAN_AND || $id === T_BOOLEAN_OR ||
        $id === T_COALESCE || $id === T_DOUBLE_ARROW ||
        $id === T_IS_IDENTICAL || $id === T_IS_NOT_IDENTICAL ||
        $id === T_IS_EQUAL || $id === T_IS_NOT_EQUAL ||
        $id === T_SPACESHIP || $id === T_CONCAT_EQUAL || $value === '.' || $value === '?' ||
        $value === ':' || $value === ',') {
        $points[] = [$offset, $offset + strlen($value), $value];
    }
    $offset += strlen($value);
}
echo json_encode($points, JSON_THROW_ON_ERROR);
'''


def operator_points(path: Path) -> list:
    p = subprocess.run(
        ["php", "-r", TOKEN_POINTS, str(path)],
        capture_output=True, text=True, check=True, timeout=30,
    )
    return json.loads(p.stdout)


def propose(source: bytes, points: list, width: int, offset: int, after: bool) -> bytes:
    edits = []
    cursor = 0
    for line in source.splitlines(keepends=True):
        begin, end = cursor, cursor + len(line.rstrip(b"\r\n"))
        cursor += len(line)
        if end - begin <= MAX_WIDTH:
            continue
        text = source[begin:end]
        prefix = text.lstrip(b" \t")
        indentation = len(text) - len(prefix)
        if indentation > 28 or prefix.startswith((b"//", b"#", b"/*", b"*")):
            continue
        if b"<?" in text or b"?>" in text:
            continue
        spacer = b" " * min(28, indentation + offset)
        possibilities = sorted({
            end_pos if after else start_pos
            for start_pos, end_pos, op in points
            if begin + 18 < (end_pos if after else start_pos) < end - 10
            and op in ("->", "?->", "&&", "||", "??", "=>", ".", "===", "!==", "==", "!=", "<=>", ".=", "?", ":", ",")
        })
        if not possibilities:
            continue
        position = begin
        for _ in range(10):
            current_prefix = len(spacer) if position > begin else 0
            if end - position + current_prefix <= MAX_WIDTH:
                break
            upper = position + width - current_prefix
            available = [pos for pos in possibilities if pos > position + 24 and pos <= upper]
            if not available:
                break
            chosen = available[-1]
            # Inserting a newline at a lexer-confirmed PHP token boundary
            # never edits literals, comments, HTML or statement text.
            edits.append((chosen, b"\n" + spacer))
            position = chosen
    output = bytearray(source)
    for position, insert in sorted(set(edits), reverse=True):
        output[position:position] = insert
    return bytes(output)


def main() -> int:
    p = argparse.ArgumentParser()
    p.add_argument("--self-test", action="store_true")
    p.add_argument("--phpcs", type=Path)
    args = p.parse_args()
    if args.self_test:
        example = b"$value = $one->getSomething()->getAnotherMethod()->getMoreData()->getValue()->getState()->getUser()->getPreferences()->getPermissions();\n"
        locations = [[i, i + 2, "->"] for i in range(len(example)) if example[i:i + 2] == b"->"]
        assert propose(example, locations, 92, 4, False) != example
        assert propose(b"$x = 1;\n", [], 92, 4, False) == b"$x = 1;\n"
        print("SECDEV-02 adaptive operator fixtures: OK")
        return 0
    if not args.phpcs or not args.phpcs.is_file():
        p.error("--phpcs executable required")
    protected = required_source_fragments()
    tracked = subprocess.check_output(
        ["git", "ls-files", "-z", "--", "*.php"], cwd=ROOT
    ).split(b"\0")
    accepted = rejected = removed = 0
    for raw in tracked:
        if not raw:
            continue
        name = raw.decode()
        path = ROOT / name
        if not path.is_file():
            continue
        original = path.read_bytes()
        if not any(len(line) > MAX_WIDTH for line in original.splitlines()):
            continue
        points = operator_points(path)
        if not points:
            continue
        required = protected.get(name, set())
        baseline = phpcs_counts(args.phpcs, path)
        signature = lex(path)["hash"]
        best, minimum = original, sum(baseline.values())
        for width, extra, after in (
            (112, 4, False), (106, 4, False), (110, 8, False),
            (108, 4, True), (100, 4, True), (112, 8, True),
        ):
            candidate = propose(original, points, width, extra, after)
            if candidate == original or any(
                fragment in original and fragment not in candidate
                for fragment in required
            ):
                continue
            path.write_bytes(candidate)
            if not lint(path) or lex(path)["hash"] != signature:
                continue
            after_counts = phpcs_counts(args.phpcs, path)
            if any(after_counts[k] > baseline[k] for k in after_counts):
                continue
            total = sum(after_counts.values())
            if total < minimum:
                best, minimum = candidate, total
        path.write_bytes(best)
        if best == original:
            rejected += 1
        else:
            accepted += 1
            removed += sum(baseline.values()) - minimum
    print(
        f"SECDEV-02 adaptive operator batch: accepted_files={accepted} "
        f"rejected_files={rejected} net_removed={removed}"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
