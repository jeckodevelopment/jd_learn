#!/usr/bin/env python3
"""CORE-18/19: scheduled expiry reminders must not starve after retries.

A source-level regression check for a bounded SQL query. A real SMTP delivery
and large hosting datasets require separate acceptance tests.
"""
from __future__ import annotations

from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[1]


def inspect(source: str) -> list[str]:
    faults: list[str] = []
    required = {
        "previously queued event exclusion": "existing.event_key = SHA2(CONCAT(",
        "identical expiry event discriminator": "'course.expiring:', e.id, ':'",
        "identical deadline fallback": "COALESCE(e.ends_at, ed.ends_at)",
        "exclude duplicate before LIMIT": "->where('existing.id IS NULL')",
        "do not batch disabled course settings": "$.mail_preferences.expiry",
        "global switch is not an unconditional veto": "$globallyEnabled = (bool) $params->get('mail_expiry_enabled', false);",
        "explicit course opt-in when site default is off": "eligiblePreferenceSql($globallyEnabled)",
        "SQL policy branches by global default": "$globallyEnabled",
        "opt-in explicit policy": "$preference . \" = 'enabled'\"",
        "PHP policy uses real global default": "MailPreferences::enabled('expiry', $globallyEnabled",
        "retry-safe event key in PHP": "hash('sha256', 'course.expiring:'",
        "bounded workload": "max(1, min(5000, $limit))",
        "database bounded select": "setQuery($query, 0, $limit)",
    }
    for name, token in required.items():
        if token not in source:
            faults.append(f"{name}: missing {token}")
    if "if (!(bool) $params->get('mail_expiry_enabled', false))" in source:
        faults.append("global-off short-circuit blocks explicitly enabled courses")
    if "MailPreferences::enabled('expiry', true" in source:
        faults.append("legacy hard-coded global-on policy breaks explicit override")
    if (
        "->where('existing.id IS NULL')" in source
        and "setQuery($query, 0, $limit)" in source
        and source.index("->where('existing.id IS NULL')")
        >= source.index("setQuery($query, 0, $limit)")
    ):
        faults.append("deduplication runs after the scheduler LIMIT")
    if (
        "$.mail_preferences.expiry" in source
        and "setQuery($query, 0, $limit)" in source
        and source.index("$.mail_preferences.expiry")
        >= source.index("setQuery($query, 0, $limit)")
    ):
        faults.append("preference filtering runs after LIMIT")
    return faults


def self_test() -> None:
    base = (
        "existing.event_key = SHA2(CONCAT("
        "'course.expiring:', e.id, ':', COALESCE(e.ends_at, ed.ends_at))"
        "->where('existing.id IS NULL')"
        "$.mail_preferences.expiry"
        "$globallyEnabled = (bool) $params->get('mail_expiry_enabled', false);"
        "eligiblePreferenceSql($globallyEnabled)"
        "$preference . \" = 'enabled'\""
        "MailPreferences::enabled('expiry', $globallyEnabled"
        "max(1, min(5000, $limit))"
        "setQuery($query, 0, $limit)"
        "hash('sha256', 'course.expiring:'"
    )
    assert not inspect(base)
    assert inspect(base.replace("->where('existing.id IS NULL')", ""))
    assert inspect(base.replace("$.mail_preferences.expiry", ""))
    assert inspect(base.replace("SHA2(CONCAT(", "SHA1(CONCAT("))
    assert inspect(base.replace("max(1, min(5000, $limit))", "$limit"))
    assert inspect(base + "if (!(bool) $params->get('mail_expiry_enabled', false))")
    assert inspect(base + "MailPreferences::enabled('expiry', true")


def main() -> int:
    if "--self-test" in sys.argv:
        self_test()
        print("CORE-18/19 expiry reminder query negative fixtures: OK")
        return 0
    source = (
        ROOT / "component/administrator/src/Application/CourseExpiryReminderService.php"
    ).read_text(encoding="utf-8")
    errors = inspect(source)
    for error in errors:
        print("CORE-18/19: " + error, file=sys.stderr)
    print(f"CORE-18/19: native expiry SQL batching/dedup checks, failures={len(errors)}")
    return 1 if errors else 0


if __name__ == "__main__":
    raise SystemExit(main())
