#!/usr/bin/env python3
"""SECDEV-02: fail-closed acceptance inventory, not an automatic sign-off.

The routine CI reports remaining CORE/SECDEV criteria without failing merely
because work remains. A false 'Completato' claim, malformed register or drift
of the pinned Joomla security patch *does* fail CI. --require-complete is the
strict acceptance check used only when all evidence is actually available.
"""
from __future__ import annotations

import argparse
import csv
import io
import json
from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[1]
EXPECTED_CORE = {f"CORE-{number:02d}" for number in range(1, 24)}
EXPECTED_SECDEV = {f"criterion_{number:02d}" for number in range(1, 5)}
TARGET_JOOMLA = "6.2.1"
PACKAGE_SHA256 = "1587c4ec74131ac4cbcf44bce20af22f364486c4169d5c569fb53cf8029c169c"
IMPLEMENTED = "implemented"
ALLOWED = {"implemented", "unverifiable", "missing", "unassessed"}


def evaluate(
    criteria: dict,
    ledger: dict,
    csv_status: str,
    workflow: str,
    native: str,
    inventory: str,
) -> tuple[list[str], dict]:
    errors: list[str] = []
    requirements = criteria.get("requirements")
    if not isinstance(requirements, dict):
        return ["Missing canonical requirements register"], {}
    cores = {key for key in requirements if key.startswith("CORE-")}
    if cores != EXPECTED_CORE:
        errors.append(f"CORE matrix mismatch: missing={sorted(EXPECTED_CORE - cores)} "
                      f"extra={sorted(cores - EXPECTED_CORE)}")
    decisions = ledger.get("services")
    if not isinstance(decisions, list) or ledger.get("schema_version") != 2:
        return errors + ["CORE ledger schema invalid"], {}
    ids = [row.get("id") for row in decisions if isinstance(row, dict)]
    if len(ids) != 23 or set(ids) != EXPECTED_CORE or len(set(ids)) != len(ids):
        errors.append("CORE ledger must contain exactly one decision for each CORE-01 through CORE-23")
    pending_core: list[dict] = []
    statuses: dict[str, int] = {}
    for key in sorted(EXPECTED_CORE):
        items = requirements.get(key, [])
        if not isinstance(items, list) or not items:
            errors.append(f"{key}: missing criteria")
            continue
        for item in items:
            state = item.get("status")
            if state not in ALLOWED:
                errors.append(f"{key}/{item.get('id')}: invalid status")
            statuses[state] = statuses.get(state, 0) + 1
            if state != IMPLEMENTED:
                pending_core.append({"core": key, "criterion": item.get("id"),
                                     "status": state, "label": item.get("label", "")})
    secdev_items = requirements.get("SECDEV-02", [])
    secdev_ids = [unit.get("id") for unit in secdev_items if isinstance(unit, dict)]
    if len(secdev_ids) != 4 or set(secdev_ids) != EXPECTED_SECDEV:
        errors.append("SECDEV-02 must have exactly four canonical criteria")
    pending_secdev = []
    for item in secdev_items:
        state = item.get("status")
        if state not in ALLOWED:
            errors.append(f"SECDEV-02/{item.get('id')}: invalid status")
        if state != IMPLEMENTED:
            pending_secdev.append({"criterion": item.get("id"), "status": state})
    pins = (
        (workflow, f"Joomla_{TARGET_JOOMLA.replace('.', '-')}-Stable-Full_Package.zip"),
        (workflow, PACKAGE_SHA256),
        (native, f"$version !== '{TARGET_JOOMLA}'"),
        (inventory, f"$version !== '{TARGET_JOOMLA}'"),
    )
    for source, token in pins:
        if token not in source:
            errors.append("Current Joomla security patch pin is inconsistent across CI/API gates")
            break
    if csv_status == "Completato" and (pending_core or pending_secdev or errors):
        errors.append("SECDEV-02 falsely marked Completato without all acceptance evidence")
    if not csv_status:
        errors.append("SECDEV-02 missing from status CSV")
    return errors, {
        "target_joomla": TARGET_JOOMLA,
        "core_services": len(EXPECTED_CORE),
        "core_criteria_statuses": dict(sorted(statuses.items())),
        "outstanding_core_criteria": pending_core,
        "secdev02_outstanding": pending_secdev,
        "csv_status": csv_status,
        "closure_ready": not errors and not pending_core and not pending_secdev
                         and csv_status == "Completato",
        "notes": "CI covers a bounded installed Joomla API subset; dynamic calls, "
                 "hosting and manual Joomla acceptance require independent evidence.",
    }


def read(root: Path) -> tuple[list[str], dict]:
    try:
        criteria = json.loads((root / "docs/FUNCTIONAL_CRITERIA_1.0.0.json").read_text())
        ledger = json.loads((root / "docs/SECDEV_02_CORE_SERVICE_DECISIONS_1.0.0.json").read_text())
        with (root / "docs/REQUIREMENTS_STATUS.csv").open(newline="", encoding="utf-8") as fp:
            rows = list(csv.DictReader(fp))
        match = [row for row in rows if row.get("Requisito") == "SECDEV-02"]
        if len(match) != 1:
            return ["SECDEV-02 must appear exactly once in requirements CSV"], {}
        status = match[0].get("Stato implementazione", "")
        workflow = (root / ".github/workflows/ci.yml").read_text()
        native = (root / "tests/secdev02-joomla-native-runtime.php").read_text()
        inventory = (root / "tests/secdev02-joomla-api-inventory.php").read_text()
    except (OSError, ValueError, UnicodeError) as exc:
        return [f"SECDEV-02 input cannot be inspected: {exc}"], {}
    return evaluate(criteria, ledger, status, workflow, native, inventory)


def self_test() -> None:
    requirements = {
        **{key: [{"id": "native", "label": "Native Joomla", "status": "implemented"}]
           for key in EXPECTED_CORE},
        "SECDEV-02": [
            {"id": key, "status": "implemented"} for key in sorted(EXPECTED_SECDEV)
        ],
    }
    ledger = {"schema_version": 2, "services": [
        {"id": key} for key in sorted(EXPECTED_CORE)
    ]}
    workflow = "Joomla_6-2-1-Stable-Full_Package.zip " + PACKAGE_SHA256
    check = "$version !== '6.2.1'"
    errors, report = evaluate({"requirements": requirements}, ledger, "Completato",
                              workflow, check, check)
    assert not errors and report["closure_ready"], (errors, report)
    requirements["CORE-02"][0]["status"] = "unverifiable"
    errors, report = evaluate({"requirements": requirements}, ledger, "Completato",
                              workflow, check, check)
    assert any("falsely" in error for error in errors)
    assert not report["closure_ready"] and len(report["outstanding_core_criteria"]) == 1
    errors, report = evaluate({"requirements": requirements}, ledger, "Parziale",
                              workflow, check, check)
    assert not errors and not report["closure_ready"]
    errors, _ = evaluate({"requirements": requirements}, ledger, "Parziale",
                         workflow, "$version !== '6.2.0'", check)
    assert any("pin is inconsistent" in error for error in errors)
    ledger["services"].pop()
    errors, _ = evaluate({"requirements": requirements}, ledger, "Parziale",
                         workflow, check, check)
    assert any("exactly one" in error for error in errors)


def main() -> int:
    args = argparse.ArgumentParser()
    args.add_argument("--self-test", action="store_true")
    args.add_argument("--require-complete", action="store_true")
    opts = args.parse_args()
    if opts.self_test:
        self_test()
        print("SECDEV-02 readiness: false-closure, missing-CORE, patch-drift negative fixtures OK")
        return 0
    errors, report = read(ROOT)
    print(json.dumps(report, ensure_ascii=False, sort_keys=True))
    for error in errors:
        print("SECDEV-02 readiness error: " + error, file=sys.stderr)
    if opts.require_complete and not report.get("closure_ready", False):
        print("SECDEV-02 cannot be signed off: outstanding CORE/API/manual evidence", file=sys.stderr)
        return 1
    return 1 if errors else 0


if __name__ == "__main__":
    raise SystemExit(main())
