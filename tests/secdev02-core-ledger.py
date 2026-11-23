#!/usr/bin/env python3
"""SECDEV-02: reconcile every Joomla core service against the functional register.

This confirms traceability and identifies *unverified* controls, not runtime approval.
"""
from __future__ import annotations

from collections import Counter
import json
from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[1]
LEDGER = "docs/SECDEV_02_CORE_SERVICE_DECISIONS_1.0.0.json"
CRITERIA = "docs/FUNCTIONAL_CRITERIA_1.0.0.json"
DECISIONS = {"native", "native_plus_domain", "not_applicable"}
STATES = {"implemented", "missing", "unverifiable", "unassessed"}


def validate(root: Path) -> tuple[list[str], dict]:
    errors: list[str] = []
    try:
        ledger = json.loads((root / LEDGER).read_text(encoding="utf-8"))
        register = json.loads((root / CRITERIA).read_text(encoding="utf-8"))
    except (OSError, ValueError) as exc:
        return [f"SECDEV-02 input unavailable or invalid: {exc}"], {}
    if ledger.get("schema_version") != 2:
        errors.append("CORE evidence ledger requires schema version 2")
    rows = ledger.get("services")
    requirements = register.get("requirements", {})
    if not isinstance(rows, list):
        return ["services must be an array"], {}
    expected = {f"CORE-{i:02d}" for i in range(1, 24)}
    seen = set()
    summary = Counter()
    for row in rows:
        if not isinstance(row, dict):
            errors.append("Service entry is not an object")
            continue
        key = row.get("id")
        if key in seen:
            errors.append(f"Duplicate core decision: {key}")
        seen.add(key)
        if key not in expected:
            errors.append(f"Unknown core decision: {key}")
            continue
        if row.get("decision") not in DECISIONS:
            errors.append(f"{key}: invalid core reuse decision")
        for field in ("service", "rationale", "remaining_verification"):
            if not isinstance(row.get(field), str) or len(row[field].strip()) < (3 if field == "service" else 12):
                errors.append(f"{key}: {field} missing or too short")
        if row.get("decision") == "not_applicable" and not row.get("rationale"):
            errors.append(f"{key}: explicit non-applicability rationale required")
        units = requirements.get(key)
        if not isinstance(units, list) or not units:
            errors.append(f"{key}: criteria missing from canonical register")
            continue
        reviewed = {
            path for unit in units if unit.get("status") == "implemented"
            for path in unit.get("evidence", [])
        }
        implementation = row.get("implementation_evidence")
        tests = row.get("automated_test_evidence")
        if not isinstance(implementation, list) or not implementation:
            errors.append(f"{key}: implementation evidence must be a nonempty list")
            implementation = []
        if not isinstance(tests, list):
            errors.append(f"{key}: automated test evidence must be a list")
            tests = []
        if len(set(implementation)) != len(implementation) or len(set(tests)) != len(tests):
            errors.append(f"{key}: duplicate evidence paths")
        if set(implementation) != reviewed:
            errors.append(f"{key}: evidence ledger differs from implemented canonical criteria")
        if set(tests) != {p for p in reviewed if p.startswith("tests/")}:
            errors.append(f"{key}: automated test evidence differs from reviewed tests")
        for item in [*implementation, *tests]:
            if not isinstance(item, str) or item.startswith("/") or ".." in Path(item).parts:
                errors.append(f"{key}: unsafe implementation evidence path")
            elif not (root / item).is_file():
                errors.append(f"{key}: implementation evidence file not found: {item}")
        if not any(not p.startswith("tests/") for p in implementation):
            errors.append(f"{key}: missing source-level implementation evidence")
        for unit in units:
            status = unit.get("status")
            if status not in STATES:
                errors.append(f"{key}/{unit.get('id')}: invalid review status")
            else:
                summary[status] += 1
            evidence = unit.get("evidence", [])
            if status == "implemented" and not evidence:
                errors.append(f"{key}/{unit.get('id')}: implemented without evidence")
            for item in evidence:
                if not isinstance(item, str) or item.startswith("/") or ".." in Path(item).parts:
                    errors.append(f"{key}: unsafe evidence path {item}")
                elif not (root / item).is_file():
                    errors.append(f"{key}: evidence file not found: {item}")
    for missing in sorted(expected - seen):
        errors.append(f"Missing core service decision: {missing}")
    # SECDEV-02 is not approved simply because the ledger is complete.
    secdev = requirements.get("SECDEV-02", [])
    if len(secdev) != 4:
        errors.append("SECDEV-02 must retain four explicitly reviewed criteria")
    return errors, {"services": len(seen & expected), "criteria": dict(summary),
                    "secdev02_status": [u.get("status") for u in secdev]}


def self_test() -> None:
    from tempfile import TemporaryDirectory
    with TemporaryDirectory() as folder:
        root = Path(folder)
        (root / "docs").mkdir()
        rows = [{"id": f"CORE-{i:02d}", "service": f"Service {i:02d} Joomla",
                 "decision": "native", "rationale": "Native Joomla integration verified in code",
                 "remaining_verification": "Acceptance in the target environment pending"}
                for i in range(1, 24)]
        register = {"requirements": {
            **{f"CORE-{i:02d}": [{"id": "native", "status": "implemented",
                                   "evidence": ["docs/evidence.txt"]}]
               for i in range(1, 24)},
            "SECDEV-02": [{"id": f"criterion_{i:02d}", "status": "unverifiable"}
                          for i in range(1, 5)]
        }}
        (root / "docs/evidence.txt").write_text("proof", encoding="utf-8")
        for row in rows:
            row["implementation_evidence"] = ["docs/evidence.txt"]
            row["automated_test_evidence"] = []
        (root / LEDGER).write_text(json.dumps({"schema_version": 2, "services": rows}), encoding="utf-8")
        (root / CRITERIA).write_text(json.dumps(register), encoding="utf-8")
        assert validate(root)[0] == [], validate(root)
        rows[0]["implementation_evidence"] = ["docs/unknown.txt"]
        (root / LEDGER).write_text(json.dumps({"schema_version": 2, "services": rows}), encoding="utf-8")
        assert any("evidence ledger differs" in item for item in validate(root)[0])
        rows[0]["implementation_evidence"] = ["docs/evidence.txt"]
        rows.pop()
        (root / LEDGER).write_text(json.dumps({"services": rows}), encoding="utf-8")
        assert any("CORE-23" in s for s in validate(root)[0])
        rows.append(rows[0].copy())
        (root / LEDGER).write_text(json.dumps({"services": rows}), encoding="utf-8")
        assert any("Duplicate" in s for s in validate(root)[0])
        rows[-1]["id"] = "CORE-23"
        register["requirements"]["CORE-23"][0]["evidence"] = ["docs/not-there.txt"]
        (root / LEDGER).write_text(json.dumps({"services": rows}), encoding="utf-8")
        (root / CRITERIA).write_text(json.dumps(register), encoding="utf-8")
        assert any("not found" in s for s in validate(root)[0])


if __name__ == "__main__":
    if "--self-test" in sys.argv:
        self_test()
        print("SECDEV-02 core ledger negative fixtures: OK")
    else:
        failures, report = validate(ROOT)
        for item in failures:
            print(f"SECDEV-02: {item}", file=sys.stderr)
        print(json.dumps(report, ensure_ascii=False, sort_keys=True))
        sys.exit(1 if failures else 0)
