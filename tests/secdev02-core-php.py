#!/usr/bin/env python3
"""SECDEV-02: fail-closed checks for the packaged PHP/Joomla integration contract.

This is a static gate, not a replacement for Joomla runtime/API compatibility tests.
"""
from __future__ import annotations

import json
from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[1]
EXPECTED_PSR4 = {
    "Jlms\\Contracts\\": "libraries/jlms/src/",
    "Jlms\\Component\\Jlms\\Administrator\\": "component/administrator/src/",
    "Jlms\\Component\\Jlms\\Site\\": "component/site/src/",
}
FORBIDDEN_CORE = (
    "libraries/joomla",
    "libraries/src",
    "administrator/components/com_categories",
    "administrator/components/com_users",
    "administrator/components/com_privacy",
    "components/com_users",
    "components/com_content",
)


def issues(root: Path) -> list[str]:
    failures: list[str] = []
    try:
        composer = json.loads((root / "composer.json").read_text(encoding="utf-8"))
    except (OSError, ValueError) as exc:
        return [f"composer.json unavailable or invalid: {exc}"]
    required = composer.get("require", {})
    if required.get("php") != ">=8.3":
        failures.append("PHP requirement changed: review supported CI/runtime matrix")
    if required.get("ext-json") != "*":
        failures.append("Required ext-json contract changed")
    autoload = composer.get("autoload", {}).get("psr-4", {})
    for namespace, folder in EXPECTED_PSR4.items():
        if autoload.get(namespace) != folder:
            failures.append(f"PSR-4 mapping altered: {namespace}")
        if not (root / folder).is_dir():
            failures.append(f"PSR-4 mapped source directory missing: {folder}")
    for folder in FORBIDDEN_CORE:
        if (root / folder).exists():
            failures.append(f"Unexpected vendored/modified Joomla core path: {folder}")
    workflow = root / ".github/workflows/ci.yml"
    try:
        ci = workflow.read_text(encoding="utf-8")
    except OSError:
        return failures + ["CI runtime matrix missing"]
    for token in ("php:${{ matrix.php }}-cli-bookworm", "php tests/sec-p2-php-security-floor.php",
                  "Lint every PHP source", "python3 tests/secdev02-core-php.py"):
        if token not in ci:
            failures.append(f"CI compatibility gate missing: {token}")
    return failures


def self_test() -> None:
    from tempfile import TemporaryDirectory

    with TemporaryDirectory() as tmp:
        root = Path(tmp)
        (root / ".github/workflows").mkdir(parents=True)
        for folder in EXPECTED_PSR4.values():
            (root / folder).mkdir(parents=True)
        (root / "composer.json").write_text(json.dumps({
            "require": {"php": ">=8.3", "ext-json": "*"},
            "autoload": {"psr-4": EXPECTED_PSR4},
        }), encoding="utf-8")
        (root / ".github/workflows/ci.yml").write_text(
            "php:${{ matrix.php }}-cli-bookworm\n"
            "php tests/sec-p2-php-security-floor.php\n"
            "Lint every PHP source\n"
            "python3 tests/secdev02-core-php.py\n", encoding="utf-8")
        assert not issues(root), issues(root)
        (root / "libraries/joomla").mkdir()
        assert any("core path" in x for x in issues(root))
        (root / "libraries/joomla").rmdir()
        data = json.loads((root / "composer.json").read_text(encoding="utf-8"))
        data["require"]["php"] = ">=8.1"
        (root / "composer.json").write_text(json.dumps(data), encoding="utf-8")
        assert any("PHP requirement changed" in x for x in issues(root))
        (root / ".github/workflows/ci.yml").write_text("", encoding="utf-8")
        assert any("CI compatibility gate missing" in x for x in issues(root))


if __name__ == "__main__":
    if "--self-test" in sys.argv:
        self_test()
    else:
        problems = issues(ROOT)
        for problem in problems:
            print(f"SECDEV-02: {problem}", file=sys.stderr)
        if problems:
            sys.exit(1)
        print("SECDEV-02 static contract checks passed (runtime/API/manual audit separate)")
