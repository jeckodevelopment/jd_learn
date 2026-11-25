#!/usr/bin/env python3
"""SECDEV-02: prevent CORE implementation/acceptance scope from drifting.

The canonical register controls the statuses; the reconciliation adds a
separate, exhaustive work split. A pending verification is NOT automatically
a missing implementation, and a green CI job is NOT Joomla manual approval.
"""
from __future__ import annotations

from collections import Counter
from copy import deepcopy
import json
from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[1]
SOURCE = "docs/SECDEV_02_CORE_RESIDUAL_CLASSIFICATION_2026-11-25.json"
CANONICAL = "docs/FUNCTIONAL_CRITERIA_1.0.0.json"
LEDGER = "docs/SECDEV_02_CORE_SERVICE_DECISIONS_1.0.0.json"
CLASSIFICATIONS = {"developer_work_pending", "site_external_only"}
IMPLEMENTATION = {"no_additional_defect_confirmed", "expiry_override_fixed_this_pr"}


def inspect(register: dict, ledger: dict, plan: dict) -> tuple[list[str], dict]:
    failures: list[str] = []
    requirements = register.get("requirements", {})
    expected = {
        f"{core}/{unit['id']}": (core, unit)
        for core, units in requirements.items()
        if core.startswith("CORE-") and isinstance(units, list)
        for unit in units if unit.get("status") != "implemented"
    }
    expected_services = {f"CORE-{i:02d}" for i in range(1, 24)}
    if {core for core in requirements if core.startswith("CORE-")} != expected_services:
        failures.append("CORE service set drift in the canonical register")
    if plan.get("schema_version") != 1 or plan.get("source_of_truth") != CANONICAL:
        failures.append("reconciliation must declare the canonical source of truth")
    entries = plan.get("criteria")
    if not isinstance(entries, list):
        return failures + ["criteria must be an array"], {}
    services = (
        {row.get("id"): row for row in ledger.get("services", [])}
        if isinstance(ledger.get("services"), list) else {}
    )
    reviewed_services = ledger.get("services", [])
    if (
        not isinstance(reviewed_services, list)
        or len(reviewed_services) != len(expected_services)
        or len(services) != len(expected_services)
        or set(services) != expected_services
    ):
        failures.append("CORE decisions missing or duplicated")
    keys = (
        [str(row.get("service")) + "/" + str(row.get("criterion")) for row in entries]
        if all(isinstance(row, dict) for row in entries) else []
    )
    if len(keys) != len(set(keys)) or set(keys) != set(expected):
        failures.append("reconciliation must cover each pending canonical criterion exactly once")
    if plan.get("pending_criteria_reviewed") != len(expected):
        failures.append("declared pending criterion count does not match canonical register")
    if plan.get("services_reviewed") != len(expected_services):
        failures.append("declared service count is not 23")
    if plan.get("implemented_service_without_pending") != "CORE-17" or any(
        key.startswith("CORE-17/") for key in expected
    ):
        failures.append("CORE-17 already has no pending implementation criterion")
    summary = Counter()
    for entry in entries:
        if not isinstance(entry, dict):
            failures.append("non-object criterion")
            continue
        key = str(entry.get("service")) + "/" + str(entry.get("criterion"))
        if key not in expected:
            failures.append(f"unknown pending criterion: {key}")
            continue
        core, unit = expected[key]
        if entry.get("canonical_status") != unit.get("status") or unit.get("status") != "unverifiable":
            failures.append(f"{key}: canonical verification status changed or misrepresented")
        if entry.get("label") != unit.get("label"):
            failures.append(f"{key}: label drift from canonical register")
        category = entry.get("acceptance_class")
        if category not in CLASSIFICATIONS:
            failures.append(f"{key}: unknown verification classification")
        else:
            summary[category] += 1
        finding = entry.get("implementation_finding")
        if finding not in IMPLEMENTATION:
            failures.append(f"{key}: unsupported implementation conclusion")
        if finding == "expiry_override_fixed_this_pr":
            if key not in {"CORE-18/smtp", "CORE-19/idempotent_tasks"}:
                failures.append(f"{key}: fix is assigned to unrelated criterion")
            summary["targeted_code_fix"] += 1
        for field in ("development_action", "manual_action"):
            if not isinstance(entry.get(field), str) or len(entry[field].strip()) < 45:
                failures.append(f"{key}: missing specific {field}")
        service = services.get(core, {})
        evidence = service.get("reconciliation", {}).get("existing_automated_evidence", [])
        if entry.get("automated_evidence") != evidence or not evidence:
            failures.append(f"{key}: automatic proof differs from reviewed CORE service")
        if entry.get("canonical_status") == "implemented":
            failures.append(f"{key}: pending verification cannot be labelled implemented")
    return failures, {
        "services_reviewed": len(expected_services),
        "pending_criteria": len(expected),
        "developer_work_pending": summary["developer_work_pending"],
        "site_external_acceptance_pending": summary["site_external_only"],
        "criteria_touched_by_code_fix": summary["targeted_code_fix"],
        "manual_acceptance_executed": 0,
        "canonical_statuses_modified": 0,
        "note": "La classificazione separa codice, CI e collaudo; non approva automaticamente SECDEV-02.",
    }


def inputs() -> tuple[dict, dict, dict]:
    return (
        json.loads((ROOT / CANONICAL).read_text(encoding="utf-8")),
        json.loads((ROOT / LEDGER).read_text(encoding="utf-8")),
        json.loads((ROOT / SOURCE).read_text(encoding="utf-8")),
    )


def self_test() -> None:
    original = inputs()
    assert inspect(*original)[0] == [], inspect(*original)
    register, ledger, plan = deepcopy(original)
    plan["criteria"].pop()
    assert any("exactly once" in x for x in inspect(register, ledger, plan)[0])
    register, ledger, plan = deepcopy(original)
    ledger["services"].append(deepcopy(ledger["services"][0]))
    assert any("CORE decisions missing or duplicated" in x for x in inspect(register, ledger, plan)[0])
    register, ledger, plan = deepcopy(original)
    plan["criteria"][0]["canonical_status"] = "implemented"
    assert any("canonical verification status" in x for x in inspect(register, ledger, plan)[0])
    register, ledger, plan = deepcopy(original)
    plan["criteria"][0]["implementation_finding"] = "expiry_override_fixed_this_pr"
    assert any("unrelated" in x for x in inspect(register, ledger, plan)[0])
    register, ledger, plan = deepcopy(original)
    plan["criteria"][0]["automated_evidence"] = ["tests/not-reviewed.php"]
    assert any("automatic proof" in x for x in inspect(register, ledger, plan)[0])
    register, ledger, plan = deepcopy(original)
    plan["criteria"][0]["manual_action"] = ""
    assert any("missing specific manual_action" in x for x in inspect(register, ledger, plan)[0])


if __name__ == "__main__":
    if "--self-test" in sys.argv:
        self_test()
        print("SECDEV-02 CORE residual classification negative fixtures: OK")
    else:
        problems, summary = inspect(*inputs())
        print(json.dumps(summary, ensure_ascii=False, sort_keys=True))
        for problem in problems:
            print("SECDEV-02: " + problem, file=sys.stderr)
        sys.exit(1 if problems else 0)
