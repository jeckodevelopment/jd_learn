#!/usr/bin/env python3
"""SECDEV-02 criterion_04: enforce 23 explicit Joomla-core reuse decisions.

This is a *decision evidence* gate, not a test of all runtime combinations.
It cannot approve the other three SECDEV-02 criteria or any pending CORE test.
"""
from __future__ import annotations

import json
from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[1]
IDS = {f"CORE-{i:02d}" for i in range(1, 24)}
VALID = {"native", "native_plus_domain", "not_applicable"}
SITES = {"CORE-02", "CORE-08", "CORE-12", "CORE-18", "CORE-22", "CORE-23"}
PLACEHOLDERS = ("todo", "tbd", "da definire", "non specificato", "lorem ipsum")


def inspect(services: list[dict], secdev: list[dict]) -> tuple[list[str], dict]:
    errors: list[str] = []
    names = [row.get("id") for row in services]
    if len(names) != 23 or set(names) != IDS or len(set(names)) != 23:
        errors.append("All 23 CORE services need exactly one reviewed decision")
    explanations: set[str] = set()
    for row in services:
        core = row.get("id")
        decision = row.get("decision")
        reason = row.get("rationale")
        if decision not in VALID:
            errors.append(f"{core}: decision absent or invalid")
        if not isinstance(reason, str) or len(reason.strip()) < 40:
            errors.append(f"{core}: substantive rationale required")
            continue
        normalized = " ".join(reason.casefold().split())
        if normalized in explanations or any(marker in normalized for marker in PLACEHOLDERS):
            errors.append(f"{core}: generic, duplicate or placeholder explanation")
        explanations.add(normalized)
        if decision == "not_applicable" and len(reason.strip()) < 80:
            errors.append(f"{core}: explicit technical reason for non-applicability required")
        sources = row.get("implementation_evidence")
        if not isinstance(sources, list) or not sources:
            errors.append(f"{core}: implementation source evidence missing")
        recon = row.get("reconciliation", {})
        if recon.get("acceptance_mode") not in {
            "ci_plus_site", "site_and_external", "code_complete_site_pending"
        }:
            errors.append(f"{core}: manual/external acceptance mode missing")
        if core in SITES and recon.get("acceptance_mode") != "site_and_external":
            errors.append(f"{core}: external acceptance cannot be silently downgraded to CI")
    matched = [unit for unit in secdev if unit.get("id") == "criterion_04"]
    if len(matched) != 1:
        errors.append("SECDEV-02 criterion_04 must appear exactly once")
    elif matched[0].get("status") == "implemented":
        proofs = matched[0].get("evidence", [])
        if ("docs/SECDEV_02_CORE_SERVICE_DECISIONS_1.0.0.json" not in proofs
                or "tests/secdev02-core-decision-signoff.py" not in proofs):
            errors.append("Implemented criterion_04 must cite decision inventory and CI gate")
        review = matched[0].get("review", {})
        if "23" not in review.get("reason", ""):
            errors.append("Decision sign-off must document all 23 CORE services")
    return errors, {"services_with_decisions": len(set(names) & IDS),
                    "site_external_services": len(SITES),
                    "non_applicable_services": sum(
                        row.get("decision") == "not_applicable" for row in services),
                    "signoff_status": matched[0].get("status") if len(matched) == 1 else "missing",
                    "scope": "CORE reuse decisions only; other implementation and QA pending"}


def self_test() -> None:
    rows = [
        {"id": f"CORE-{i:02d}", "decision": "native",
         "rationale": f"Joomla supplies service {i:02d} and JD Learn integrates it through native APIs",
         "implementation_evidence": ["component/administrator/services/provider.php"],
         "reconciliation": {"acceptance_mode": "site_and_external" if f"CORE-{i:02d}" in SITES
                            else "ci_plus_site"}}
        for i in range(1, 24)
    ]
    criterion = [{"id": "criterion_04", "status": "implemented",
                  "evidence": ["docs/SECDEV_02_CORE_SERVICE_DECISIONS_1.0.0.json",
                               "tests/secdev02-core-decision-signoff.py"],
                  "review": {"reason": "All 23 CORE decisions were evaluated"}}]
    assert not inspect(rows, criterion)[0]
    rows[1]["rationale"] = rows[0]["rationale"]
    assert any("duplicate" in error for error in inspect(rows, criterion)[0])
    rows[1]["rationale"] = "TODO"
    assert any("substantive" in error for error in inspect(rows, criterion)[0])
    rows[1]["rationale"] = "Joomla supplies service 02 and JD Learn integrates it through native APIs"
    rows.pop()
    assert any("exactly one" in error for error in inspect(rows, criterion)[0])
    rows.append({"id": "CORE-23", "decision": "native",
                 "rationale": "Native Joomla event dispatch used by JD Learn with domain integration",
                 "implementation_evidence": ["component/administrator/services/provider.php"],
                 "reconciliation": {"acceptance_mode": "ci_plus_site"}})
    assert any("cannot be silently downgraded" in error for error in inspect(rows, criterion)[0])
    rows[-1]["reconciliation"]["acceptance_mode"] = "site_and_external"
    criterion[0]["evidence"] = []
    assert any("must cite" in error for error in inspect(rows, criterion)[0])


def main() -> int:
    if "--self-test" in sys.argv:
        self_test()
        print("SECDEV-02 23-service decision negative fixtures: OK")
        return 0
    try:
        ledger = json.loads((ROOT / "docs/SECDEV_02_CORE_SERVICE_DECISIONS_1.0.0.json").read_text())
        criteria = json.loads((ROOT / "docs/FUNCTIONAL_CRITERIA_1.0.0.json").read_text())
        errors, report = inspect(ledger["services"], criteria["requirements"]["SECDEV-02"])
    except (KeyError, TypeError, ValueError, OSError) as exc:
        print(f"SECDEV-02 decision sign-off inputs invalid: {exc}", file=sys.stderr)
        return 1
    print(json.dumps(report, ensure_ascii=False, sort_keys=True))
    for error in errors:
        print(error, file=sys.stderr)
    return 1 if errors else 0


if __name__ == "__main__":
    raise SystemExit(main())
