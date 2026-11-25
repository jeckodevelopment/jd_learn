#!/usr/bin/env python3
"""SECDEV-02: prevent private Joomla raw-JSON handlers from dropping headers.

Discover all shipped backend and frontend controllers, rather than listing
only known response methods. Installed HTTP/proxy acceptance remains separate.
"""
from __future__ import annotations

import argparse
from pathlib import Path
import re
import sys

ROOT = Path(__file__).resolve().parents[1]
RAW_ECHO = re.compile(
    r"\becho\s+(?:new\s+(?:\\Joomla\\CMS\\Response\\)?JsonResponse\s*\(|\$response\s*;)"
)
METHOD = re.compile(
    r"\b(?:public|protected|private)\s+(?:static\s+)?function\s+"
    r"[A-Za-z_][A-Za-z_0-9]*\s*\("
)
CACHE = "$this->app->setHeader('Cache-Control', 'private, no-store', true);"
FLUSH = "$this->app->sendHeaders();"


def inspect(source: str, path: str) -> tuple[list[str], int]:
    errors: list[str] = []
    responses = 0
    for match in RAW_ECHO.finditer(source):
        responses += 1
        methods = list(METHOD.finditer(source, 0, match.start()))
        if not methods:
            errors.append(f"{path}: JSON output is outside a declared method")
            continue
        body = source[methods[-1].start():match.start()]
        if CACHE not in body:
            errors.append(f"{path}: JSON handler missing private/no-store header")
        if FLUSH not in body:
            errors.append(f"{path}: JSON handler emitted before Joomla sendHeaders")
    return errors, responses


def validate(root: Path) -> tuple[list[str], int]:
    issues: list[str] = []
    total = 0
    for client in ("site", "administrator"):
        directory = root / "component" / client / "src" / "Controller"
        files = sorted(directory.glob("*Controller.php"))
        if not files:
            issues.append(f"{client}: no Joomla controllers found")
        for path in files:
            failures, count = inspect(
                path.read_text(encoding="utf-8"),
                str(path.relative_to(root)),
            )
            total += count
            issues.extend(failures)
    if total < 7:
        issues.append("SECDEV-02 raw JSON inventory unexpectedly reduced")
    return issues, total


def self_test() -> None:
    good = (
        "public function save(): void { " + CACHE
        + " $response = new JsonResponse(['ok' => true]); "
        + FLUSH + " echo $response; }"
    )
    assert inspect(good, "fixture") == ([], 1)
    faults, count = inspect(good.replace(FLUSH, ""), "fixture")
    assert count == 1 and any("sendHeaders" in item for item in faults)
    wrong_order = good.replace(FLUSH + " echo $response;", "echo $response; " + FLUSH)
    faults, count = inspect(wrong_order, "fixture")
    assert count == 1 and any("sendHeaders" in item for item in faults)
    faults, count = inspect(good.replace(CACHE, ""), "fixture")
    assert count == 1 and any("private/no-store" in item for item in faults)
    assert inspect("public function view() { return ''; }", "fixture") == ([], 0)
    raw = (
        r"public function save() { " + CACHE
        + r" echo new \Joomla\CMS\Response\JsonResponse([]); }"
    )
    faults, count = inspect(raw, "fixture")
    assert count == 1 and any("sendHeaders" in item for item in faults)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    if args.self_test:
        self_test()
        print("SECDEV-02 raw JSON negative fixtures: OK")
        return 0
    issues, count = validate(ROOT)
    for issue in issues:
        print(issue, file=sys.stderr)
    print(f"SECDEV-02 raw Joomla JSON: {count} outputs, "
          f"failures={len(issues)}; site/proxy acceptance separate")
    return 1 if issues else 0


if __name__ == "__main__":
    raise SystemExit(main())
