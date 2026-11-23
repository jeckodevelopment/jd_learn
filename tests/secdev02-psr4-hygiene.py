#!/usr/bin/env python3
"""SECDEV-02: source-wide PSR-4 and PHP hygiene inventory with no-new-debt gate.

Pre-existing violations are reported, not silently reclassified as compliant.
The exact mapped PHP source tree is scanned; only newly introduced violations
relative to the base ref fail CI.
"""
from __future__ import annotations

from collections import Counter
from pathlib import Path
import argparse
import re
import subprocess
import sys
import tempfile

ROOT = Path(__file__).resolve().parents[1]
MAPPINGS = {
    "component/administrator/src/": r"Jlms\Component\Jlms\Administrator",
    "component/site/src/": r"Jlms\Component\Jlms\Site",
    "libraries/jlms/src/": r"Jlms\Contracts",
}
NAMESPACE = re.compile(r"(?m)^\s*namespace\s+([A-Za-z_\\][A-Za-z0-9_\\]*)\s*;")
DECLARATION = re.compile(
    r"(?m)^\s*(?:(?:final|abstract|readonly)\s+)*(?:class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)\b"
)


def inspect(path: str, content: str) -> Counter[tuple[str, str]]:
    errors: Counter[tuple[str, str]] = Counter()
    mapping = next(((prefix, ns) for prefix, ns in MAPPINGS.items()
                    if path.startswith(prefix) and path.endswith(".php")), None)
    if mapping is None:
        return errors
    prefix, namespace = mapping
    relative = Path(path[len(prefix):])
    expected = namespace + ("\\" + "\\".join(relative.parts[:-1])
                            if len(relative.parts) > 1 else "")
    found = NAMESPACE.search(content)
    if not found or found.group(1) != expected:
        errors[(path, "psr4-namespace")] += 1
    declarations = DECLARATION.findall(content)
    if declarations and (len(declarations) != 1 or declarations[0] != relative.stem):
        errors[(path, "psr4-declaration")] += 1
    if not re.match(r"^<\?php(?:\r?\n)", content):
        errors[(path, "php-open-tag")] += 1
    if not re.search(r"(?m)^\s*declare\(strict_types=1\);", content):
        errors[(path, "strict-types")] += 1
    if not content.endswith("\n"):
        errors[(path, "final-newline")] += 1
    if re.search(r"(?m)[\t ]+$", content):
        errors[(path, "trailing-whitespace")] += 1
    return errors


def run_git(*args: str) -> bytes:
    return subprocess.run(["git", "-C", str(ROOT), *args],
                          check=True, capture_output=True, timeout=90).stdout


def source_paths() -> list[str]:
    return [p.decode("utf-8") for p in run_git("ls-files", "-z").split(b"\0")
            if p and any(p.decode("utf-8").startswith(k) for k in MAPPINGS)
            and p.endswith(b".php")]


def scan_worktree() -> Counter[tuple[str, str]]:
    result: Counter[tuple[str, str]] = Counter()
    for path in source_paths():
        result.update(inspect(path, (ROOT / path).read_text(encoding="utf-8")))
    return result


def introduced(current: Counter, base_ref: str) -> Counter:
    old: Counter[tuple[str, str]] = Counter()
    for path in source_paths():
        file = subprocess.run(["git", "-C", str(ROOT), "show", f"{base_ref}:{path}"],
                              capture_output=True, timeout=20, check=False)
        if file.returncode == 0:
            old.update(inspect(path, file.stdout.decode("utf-8")))
    return current - old


def self_test() -> None:
    path = "component/site/src/Model/Example.php"
    good = ("<?php\n\ndeclare(strict_types=1);\n\n"
            "namespace Jlms\\Component\\Jlms\\Site\\Model;\n"
            "final class Example {}\n")
    assert not inspect(path, good), inspect(path, good)
    assert ("psr4-namespace" in {code for _, code in inspect(path, good.replace(
        "Site\\Model", "Administrator\\Model"))})
    assert ("psr4-declaration" in {code for _, code in inspect(path, good.replace(
        "class Example", "class Other"))})
    assert ("strict-types" in {code for _, code in inspect(path, good.replace(
        "declare(strict_types=1);", ""))})
    assert ("trailing-whitespace" in {code for _, code in inspect(path, good + " \n")})
    assert not inspect("plugins/task/something.php", good)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--self-test", action="store_true")
    parser.add_argument("--base-ref", default="origin/main")
    args = parser.parse_args()
    if args.self_test:
        self_test()
        print("SECDEV-02 PHP namespace/hygiene negative fixtures: OK")
        return 0
    try:
        current = scan_worktree()
        delta = introduced(current, args.base_ref)
        total = len(source_paths())
    except (OSError, UnicodeError, subprocess.CalledProcessError, subprocess.TimeoutExpired) as err:
        print(f"SECDEV-02 inventory unavailable: {err}", file=sys.stderr)
        return 2
    print(f"SECDEV-02: checked {total} PSR-4-mapped PHP files; "
          f"pre-existing/current findings={sum(current.values())}; new violations={sum(delta.values())}")
    for (path, code), count in sorted(delta.items()):
        print(f"New {code}: {path} ({count})", file=sys.stderr)
    return 1 if delta else 0


if __name__ == "__main__":
    raise SystemExit(main())
