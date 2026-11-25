#!/usr/bin/env python3
"""CORE-21: maintain a complete, automatically checked private HTTP route manifest.

The runtime exercise is in dashboard-http.py on installed Joomla with two
independent student sessions. Only read-only or deliberately rejected requests
are listed; this test must not change user consent or teaching progress.
"""
from __future__ import annotations

import argparse
from pathlib import Path
import re
import sys

ROOT = Path(__file__).resolve().parents[1]
TASKS = {
    "audio": {"save": "GET"},
    "enrollment": {
        "start": "GET", "resume": "POST", "request": "GET",
    },
    "lesson": {"complete": "GET", "begin": "GET"},
    "teacher": {
        "edit": "GET", "save": "POST", "apply": "POST", "cancel": "POST",
    },
    "quiz": {
        "start": "GET", "save": "GET", "submit": "GET", "state": "GET",
    },
    "video": {
        "playback": "GET", "renew": "GET", "save": "GET", "close": "GET",
    },
    "media": {"stream": "POST"},
    "attachment": {
        "publicpreview": "POST", "publiclessonpreview": "POST",
        "preview": "POST", "audio": "POST", "download": "POST", "inline": "POST",
    },
    "certificate": {"download": "POST"},
    "mailconsent": {
        "request": "POST", "confirm": "GET", "revoke": "POST",
        "revokelink": "POST",
    },
}
PRIVATE_VIEWS = (
    "dashboard", "mycourses", "mycertificates", "preferences",
    "lessonpreview", "teacheredit", "classroom", "quiz",
    "mailconsentrevoke",
)
ALLOWLIST_WORD = re.compile(r"'([a-z][a-z0-9]*)'")


def names_in_execute(source: str) -> set[str]:
    start = source.find("public function execute($task)")
    if start < 0:
        return set()
    finish = source.find("return parent::execute($task);", start)
    if finish < 0:
        return set()
    return set(ALLOWLIST_WORD.findall(source[start:finish]))


def allowed_views(source: str) -> set[str]:
    start = source.find("$allowedViews = [")
    finish = source.find("];", start)
    if start < 0 or finish < 0:
        return set()
    return set(ALLOWLIST_WORD.findall(source[start:finish]))


def private_views(source: str) -> set[str]:
    start = source.find("$privateViews = [")
    finish = source.find("];", start)
    if start < 0 or finish < 0:
        return set()
    return set(ALLOWLIST_WORD.findall(source[start:finish]))


def shipped_task_controllers(controller_dir: Path) -> set[str]:
    return {
        source.name.removesuffix("Controller.php").lower()
        for source in controller_dir.glob("*Controller.php")
        if source.name not in {"DisplayController.php", "LessonController.php"}
    }


def validate(root: Path) -> list[str]:
    failures: list[str] = []
    controller_dir = root / "component/site/src/Controller"
    # LessonController inherits the bounded execute() of EnrollmentController;
    # its task names are audited separately by the shared task allowlist.
    expected_controllers = set(TASKS) - {"lesson"}
    actual_controllers = shipped_task_controllers(controller_dir)
    if actual_controllers != expected_controllers:
        failures.append(
            "CORE-21 untracked task controller: "
            f"new={sorted(actual_controllers - expected_controllers)} "
            f"removed={sorted(expected_controllers - actual_controllers)}"
        )
    for class_key, tasks in TASKS.items():
        # Lesson extends EnrollmentController and borrows its whitelist.
        classname = "Enrollment" if class_key == "lesson" else (
            class_key[0].upper() + class_key[1:]
        )
        filename = controller_dir / (classname + "Controller.php")
        try:
            source = filename.read_text(encoding="utf-8")
        except OSError:
            failures.append(f"CORE-21 missing controller: {filename}")
            continue
        expected = set(tasks)
        if class_key == "enrollment":
            expected |= set(TASKS["lesson"])
        if class_key == "lesson":
            expected |= set(TASKS["enrollment"])
        actual = names_in_execute(source)
        if actual != expected:
            failures.append(
                f"CORE-21 {class_key} task drift: "
                f"unprobed={sorted(actual - expected)} "
                f"removed={sorted(expected - actual)}"
            )
    path = controller_dir / "DisplayController.php"
    try:
        source = path.read_text(encoding="utf-8")
    except OSError:
        failures.append("CORE-21 display controller missing")
    else:
        actual_views = private_views(source)
        expected_views = set(PRIVATE_VIEWS)
        allowed = allowed_views(source)
        if not expected_views.issubset(allowed):
            failures.append(
                f"CORE-13/21 private views forbidden by display allowlist: {sorted(expected_views - allowed)}"
            )
        # The native bearer unsubscribe confirmation view must be reachable
        # without login and set the privacy headers before rendering its token.
        consent = root / "component/site/src/View/Mailconsentrevoke/HtmlView.php"
        if consent.is_file():
            view_source = consent.read_text(encoding="utf-8")
            for header in ("Referrer-Policy", "no-referrer", "Cache-Control", "private, no-store",
                           "X-Robots-Tag", "noindex, nofollow"):
                if header not in view_source:
                    failures.append(f"CORE-13/21 consent route lost protected header: {header}")
        else:
            failures.append("CORE-13/21 missing bearer unsubscribe view")
        if actual_views != expected_views:
            failures.append(
                f"CORE-21 private view drift: "
                f"unprobed={sorted(actual_views - expected_views)} "
                f"removed={sorted(expected_views - actual_views)}"
            )
    return failures


def self_test() -> None:
    assert names_in_execute(
        """public function execute($task) {
           if (!in_array($task, ['first', 'second'], true)) {
              throw new RuntimeException('ERROR', 404);
           }
           return parent::execute($task);
        }"""
    ) == {"first", "second"}
    assert allowed_views("$allowedViews = ['mailconsentrevoke', 'courses'];") == {
        "mailconsentrevoke", "courses",
    }
    assert not allowed_views("$privateViews = ['dashboard'];")
    assert private_views("$privateViews = ['dashboard', 'quiz'];") == {
        "dashboard", "quiz",
    }
    assert not names_in_execute("public function something() {}")
    assert not private_views("$allowedViews = ['course'];")
    from tempfile import TemporaryDirectory
    with TemporaryDirectory() as folder:
        controllers = Path(folder)
        (controllers / "AudioController.php").write_text("<?php", encoding="utf-8")
        assert shipped_task_controllers(controllers) == {"audio"}
        (controllers / "UnexpectedController.php").write_text("<?php", encoding="utf-8")
        assert shipped_task_controllers(controllers) - set(TASKS) == {"unexpected"}


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    if args.self_test:
        self_test()
        print("CORE-21 private route inventory self-test: OK")
        return 0
    failures = validate(ROOT)
    for failure in failures:
        print(failure, file=sys.stderr)
    print("CORE-21 exhaustive private HTTP inventory: "
          f"{sum(len(items) for items in TASKS.values())} task routes, "
          f"{len(PRIVATE_VIEWS)} views, discrepancies={len(failures)}")
    return 1 if failures else 0


if __name__ == "__main__":
    raise SystemExit(main())
