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
    assert inspect(source.replace("min(100, (int) ($params->limit ?? 25))", "min(5000, (int) ($params->limit ?? 25))"), form)
    assert inspect(source, form.replace('max="100"', 'max="5000"'))



def inspect_retry_and_tasks(source: str, queue: str) -> list[str]:
    """Verify concurrent claims, diagnostic bounds and three Scheduler routines."""
    problems: list[str] = []
    for routine, handler in (
        ("expireQuizzes", "expireDue"),
        ("cleanupMaterials", "purgeOrphaned"),
    ):
        marker = f"protected function {routine}("
        if marker not in source:
            problems.append(f"{routine}: missing task")
            continue
        body = source.split(marker, 1)[1].split("protected function ", 1)[0]
        if not re.search(
            r"\\$limit\\s*=\\s*max\\(1,\\s*min\\(100,\\s*\\(int\\)\\s*\\(\\$params->limit\\s*\\?\\?\\s*100\\)\\)\\);",
            body,
        ) or f"->{handler}($limit)" not in body:
            problems.append(f"{routine}: missing normalized 1..100 budget")
        if "batch_limit=" not in body or "has_more" not in body:
            problems.append(f"{routine}: bounded progress not logged")

    mail = source.split("protected function dispatchMail(", 1)
    if len(mail) != 2:
        problems.append("missing mail Scheduler method")
    else:
        if "Mail scheduler: batch_limit=" not in mail[1] or "at_limit=" not in mail[1]:
            problems.append("mail Scheduler saturation diagnostic missing")
        if "skipped=" not in mail[1] or "$result['skipped']" not in mail[1]:
            problems.append("mail Scheduler skipped count diagnostic missing")
        if mail[1].count("['processed'] >= $limit") < 6:
            problems.append("mail Scheduler must report saturation for all six stages")

    dispatcher = queue.split("public function dispatch(int $limit", 1)
    if len(dispatcher) != 2:
        problems.append("missing mail queue dispatch")
        return problems
    candidate = dispatcher[1]
    claim = candidate.split("$claim =", 1)
    if len(claim) != 2:
        problems.append("missing mail queue atomic claim")
    else:
        claim_sql = claim[1].split("->execute();", 1)[0]
        if "state IN ('pending','retry')" not in claim_sql or (
            "available_at <= UTC_TIMESTAMP(6)" not in claim_sql
        ):
            problems.append("atomic claim must recheck retry availability")
    failed = candidate.split("$safeError =", 1)
    if len(failed) != 2 or '->where("state = \'sending\'")' not in (
        failed[1].split("$failed++;", 1)[0]
    ):
        problems.append("retry transition must only update a sending row")
    return problems


def retry_self_test(source: str, queue: str) -> None:
    assert not inspect_retry_and_tasks(source, queue), inspect_retry_and_tasks(source, queue)
    assert inspect_retry_and_tasks(source.replace("Mail scheduler: batch_limit=", "Mail scheduler: limit="), queue)
    assert inspect_retry_and_tasks(source.replace("->purgeOrphaned($limit)", "->purgeOrphaned(5000)"), queue)
    assert inspect_retry_and_tasks(source.replace("->expireDue($limit)", "->expireDue(5000)"), queue)
    assert inspect_retry_and_tasks(source.replace("at_limit=", "batch_full="), queue)
    start = queue.index("$claim =")
    tail = queue[start:]
    altered = queue[:start] + tail.replace(
        "->where('available_at <= UTC_TIMESTAMP(6)')", "", 1
    )
    assert inspect_retry_and_tasks(source, altered)
    retry_start = queue.index("$safeError =")
    retry_tail = queue[retry_start:]
    altered_retry = queue[:retry_start] + retry_tail.replace(
        '->where("state = \'sending\'")', '', 1
    )
    assert inspect_retry_and_tasks(source, altered_retry)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    source = (ROOT / TASK).read_text(encoding="utf-8")
    form = (ROOT / FORM).read_text(encoding="utf-8")
    queue = (ROOT / "component/administrator/src/Infrastructure/MailQueue.php").read_text(encoding="utf-8")
    if args.self_test:
        self_test(source, form)
        retry_self_test(source, queue)
        print("CORE-19 Scheduler batch-bound negative fixtures: OK")
        return 0
    problems = inspect(source, form) + inspect_retry_and_tasks(source, queue)
    for item in problems:
        print("CORE-19: " + item, file=sys.stderr)
    print(f"CORE-19: 4 enqueue services, communication and dispatcher bounded; issues={len(problems)}")
    return 1 if problems else 0


if __name__ == "__main__":
    raise SystemExit(main())
