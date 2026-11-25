#!/usr/bin/env python3
"""CORE-19: every stage of the Joomla mail task respects one bounded limit.

The task may prepare messages for several independent event types in a run.
The per-stage budget is intentional; this checks no stage silently uses its
larger default (up to 5,000) instead of the user-configured Joomla task limit.
SMTP delivery and scheduling intervals remain separate acceptance criteria.
"""
from __future__ import annotations

import argparse
from pathlib import Path
import re
import sys

ROOT = Path(__file__).resolve().parents[1]
COMPONENTS = (
    "CourseExpiryReminderService",
    "LessonReleaseNotice",
    "CourseVersionChangeNotice",
    "EnrollmentManagerNotice",
)
TASK = "plugins/task/jlms/src/Extension/Jlms.php"
FORM = "plugins/task/jlms/forms/expire.xml"


def inspect(source: str, form: str) -> list[str]:
    problems: list[str] = []
    start = source.find("protected function dispatchMail(ExecuteTaskEvent $event)")
    if start < 0:
        return ["Missing Joomla mail task"]
    task = source[start:]
    if not re.search(
        r"\$limit\s*=\s*max\(1,\s*min\(100,\s*\(int\)\s*\(\$params->limit\s*\?\?\s*25\)\)\);",
        task,
    ):
        problems.append("mail task must normalize 1..100 and default to 25")
    if not re.search(r'name="limit".*min="1".*max="100"', form):
        problems.append("Joomla task form must expose identical 1..100 bounds")
    for service in COMPONENTS:
        pattern = (
            r"new\s+\\Jlms\\Component\\Jlms\\Administrator\\Application\\"
            + service + r"\(\$db\)\)\s*->enqueueDue\(\$limit\)"
        )
        if not re.search(pattern, task):
            problems.append(f"{service} does not receive the configured batch limit")
    for name, pattern in {
        "communications": r"getCourseCommunicationService\(\)->process\(\$limit\)",
        "mail transport": r"new\s+\\Jlms\\Component\\Jlms\\Administrator\\Infrastructure\\"
        r"MailQueue\(\$db\)\)\s*->dispatch\(\$limit\)",
    }.items():
        if not re.search(pattern, task):
            problems.append(f"{name} does not receive the configured batch limit")
    if re.search(r"enqueueDue\(\s*\)", task):
        problems.append("unbounded default enqueue routine remains in mail task")
    return problems


def self_test(source: str, form: str) -> None:
    assert not inspect(source, form), inspect(source, form)
    assert inspect(source.replace("->enqueueDue($limit)", "->enqueueDue()", 1), form)
    assert inspect(source.replace("->process($limit)", "->process(5000)"), form)
    assert inspect(source.replace("->dispatch($limit)", "->dispatch()", 1), form)
    assert inspect(source.replace("min(100,", "min(5000,", 1), form)
    assert inspect(source, form.replace('max="100"', 'max="5000"'))


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    source = (ROOT / TASK).read_text(encoding="utf-8")
    form = (ROOT / FORM).read_text(encoding="utf-8")
    if args.self_test:
        self_test(source, form)
        print("CORE-19 Scheduler batch-bound negative fixtures: OK")
        return 0
    problems = inspect(source, form)
    for item in problems:
        print("CORE-19: " + item, file=sys.stderr)
    print(f"CORE-19: 4 enqueue services, communication and dispatcher bounded; issues={len(problems)}")
    return 1 if problems else 0


if __name__ == "__main__":
    raise SystemExit(main())
