#!/usr/bin/env python3
"""CORE-21: fail-closed private task dispatch and frontend view caching policy.

Source security contract: tests every declared private Joomla task controller,
including rejected tasks. Cross-account leaks must still be proven via HTTP
tests; this cannot close the global CORE-21 acceptance criterion alone.
"""
from __future__ import annotations

import argparse
from pathlib import Path
import re
import sys

ROOT = Path(__file__).resolve().parents[1]
TASKS = (
    "Enrollment", "Teacher", "Quiz", "Video", "Media", "Attachment",
    "Certificate", "Mailconsent",
)
CACHE = "$this->app->setHeader('Cache-Control', 'private, no-store', true);"


def inspect_source(name: str, contents: str) -> list[str]:
    faults: list[str] = []
    dispatch = re.search(
        r"public function execute\(\$task\)\s*\{(?P<body>.*?)\breturn parent::execute\(\$task\);",
        contents,
        flags=re.S,
    )
    if dispatch is None:
        return [f"{name}: missing bounded Joomla execute($task) dispatch"]
    before_parent = dispatch.group("body")
    if CACHE not in before_parent:
        faults.append(f"{name}: task dispatch lacks no-store before parent::execute")
    elif before_parent.index(CACHE) > before_parent.index("if ("):
        faults.append(f"{name}: no-store must precede task allowlist checks")
    return faults



def inspect_raw_json(name: str, contents: str) -> list[str]:
    """A raw JSON task must flush Joomla's pending headers before echo/close."""
    start = contents.find("private function respond(")
    if start < 0:
        return [f"{name}: missing raw JSON response handler"]
    end = contents.find("$this->app->close();", start)
    if end < 0:
        return [f"{name}: raw JSON handler does not close the Joomla request"]
    body = contents[start:end]
    ordered = [
        body.rfind("$response = new JsonResponse("),
        body.find("$this->app->sendHeaders();"),
        body.find("echo $response;"),
    ]
    if min(ordered) < 0 or ordered != sorted(ordered):
        return [f"{name}: raw JSON must flush no-store headers before body output"]
    if body.count("$response = new JsonResponse(") != 2:
        return [f"{name}: success/error branches must both defer JSON body output"]
    return []


def check(root: Path) -> list[str]:
    faults: list[str] = []
    for name in TASKS:
        relative = Path("component/site/src/Controller") / f"{name}Controller.php"
        try:
            source = (root / relative).read_text(encoding="utf-8")
        except OSError:
            faults.append(f"Missing protected controller: {relative}")
            continue
        faults.extend(inspect_source(name, source))
    for name in ("Quiz", "Video"):
        source = (root / "component/site/src/Controller" / f"{name}Controller.php").read_text(
            encoding="utf-8"
        )
        faults.extend(inspect_raw_json(name, source))
    display = root / "component/site/src/Controller/DisplayController.php"
    try:
        source = display.read_text(encoding="utf-8")
    except OSError:
        faults.append("Missing frontend display controller")
    else:
        if "return parent::display(false, $urlparams);" not in source:
            faults.append("Frontend display no longer enforces noncacheable Joomla rendering")
        if CACHE not in source:
            faults.append("Frontend display no longer sets a no-store policy")
        for view in ("dashboard", "mycourses", "mycertificates", "preferences",
                     "classroom", "quiz", "teacheredit"):
            if f"'{view}'" not in source:
                faults.append(f"Missing private view route: {view}")
    return faults


def self_test() -> None:
    source = """public function execute($task)
    {
        $this->app->setHeader('Cache-Control', 'private, no-store', true);
        if ($task !== 'allowed') {
            throw new \\RuntimeException('denied', 404);
        }
        return parent::execute($task);
    }"""
    assert not inspect_source("fixture", source)
    assert any("lacks no-store" in fault for fault in inspect_source(
        "fixture", source.replace(CACHE, "")
    ))
    bad = source.replace(
        CACHE + "\n        if ($task", "if ($task"
    )
    assert any("lacks" in fault or "missing" in fault for fault in inspect_source("fixture", bad))
    late = source.replace(
        CACHE + "\n        if ($task !== 'allowed') {",
        "if ($task !== 'allowed') {\n            return;\n        }\n        " + CACHE
    )
    assert any("must precede" in fault for fault in inspect_source("fixture", late))
    assert inspect_source("fixture", "public function execute($task) { return false; }")
    raw = (
        "private function respond() { "
        "$response = new JsonResponse(1); "
        "$response = new JsonResponse(2); "
        "$this->app->sendHeaders(); echo $response; $this->app->close(); }"
    )
    assert not inspect_raw_json("fixture", raw)
    assert inspect_raw_json("fixture", raw.replace("$this->app->sendHeaders();", ""))
    assert inspect_raw_json("fixture", raw.replace(
        "$this->app->sendHeaders(); echo $response;",
        "echo $response; $this->app->sendHeaders();",
    ))


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    if args.self_test:
        self_test()
        print("CORE-21 private task dispatch negative fixtures: OK")
    else:
        errors = check(ROOT)
        for error in errors:
            print("CORE-21: " + error, file=sys.stderr)
        print(f"CORE-21 private Joomla cache contract: {len(TASKS)} task controllers "
              f"and display routing; failures={len(errors)} (HTTP/manual acceptance separate)")
        sys.exit(1 if errors else 0)
