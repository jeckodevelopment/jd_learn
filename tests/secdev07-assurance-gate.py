#!/usr/bin/env python3
"""SECDEV-07: fail closed when security evidence is unlinked, unexecuted or overstated.

Tests the *traceability process*, not security of a deployed host. The only
accepted claims are repository-scoped checks with explicit known limitations.
"""
from __future__ import annotations
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
LEDGER = ROOT / "docs/SECDEV_07_SECURITY_ASSURANCE_1.0.0.json"
PRE = (ROOT / "tools/check.py").read_text(encoding="utf-8")
WORKFLOW = (ROOT / ".github/workflows/ci.yml").read_text(encoding="utf-8")
CATEGORIES = {
    "threat_model", "controllers", "extensions", "static_analysis",
    "secrets", "dependencies", "findings", "privacy", "standard",
}
EXPECTED = {
    "THREAT-MODEL", "DISPATCH-CSRF", "PLUGINS-JOBS", "PHP-SINKS",
    "SECRET-WORKFLOWS", "PACKAGE-DEPENDENCIES", "CRITICAL-HIGH",
    "PRIVACY-SCHEMA", "PRIVACY-EVENTS", "ASVS-PIN",
}
GAPS = {"EXTERNAL-SCA", "HOSTED-SECRET-SCAN", "PENETRATION-E2E", "FULL-SAST-STYLE"}


def inspect(root: Path, ledger: dict, preflight: str, workflow: str) -> tuple[int, int]:
    if (ledger.get("schema") != 1 or ledger.get("baseline") != "1.0.0"
        or ledger.get("criterion") != "SECDEV-07"
        or ledger.get("policy") != "reviewed_control_must_have_source_test_execution_and_limitations; no_claim_of_release_security"):
        raise ValueError("Invalid SECDEV-07 policy")
    controls = ledger.get("controls")
    gaps = ledger.get("gaps")
    if not isinstance(controls, list) or {c.get("id") for c in controls} != EXPECTED:
        raise ValueError("Missing or unreviewed security control")
    if not isinstance(gaps, list) or {g.get("id") for g in gaps} != GAPS:
        raise ValueError("Unacknowledged security release gap")
    if len(controls) != len(EXPECTED) or len(gaps) != len(GAPS):
        raise ValueError("Duplicate assurance item")
    for control in controls:
        if control.get("area") not in CATEGORIES:
            raise ValueError("Unknown threat classification")
        for field in ("threat", "scope", "assertion"):
            value = control.get(field)
            if not isinstance(value, str) or len(value.strip()) < (3 if field == "assertion" else 5):
                raise ValueError("Incomplete control evidence")
        for key in ("source", "test"):
            relative = Path(control[key])
            if relative.is_absolute() or ".." in relative.parts or not (root / relative).is_file():
                raise ValueError("Invalid evidence path")
        if control["assertion"] not in (root / control["test"]).read_text(encoding="utf-8"):
            raise ValueError("Stale test evidence")
        runner = control.get("runner")
        if runner == "preflight":
            if control["test"] not in preflight:
                raise ValueError("Security test absent from mandatory preflight")
        elif runner == "workflow":
            if control["test"] not in workflow:
                raise ValueError("Security test absent from CI workflow")
        elif runner == "joomla":
            if "tests/joomla-smoke.php" not in workflow and "joomla-smoke.php" not in workflow:
                raise ValueError("Joomla fixture absent from CI")
        else:
            raise ValueError("Unverified test runner")
    for gap in gaps:
        if gap.get("blocking") not in {"release", "development"}:
            raise ValueError("Unclassified release/development gap")
        if not gap.get("owner") or len(gap.get("detail", "")) < 80:
            raise ValueError("Gap missing owner, limitation or follow-up")
    return len(controls), len(gaps)


if __name__ == "__main__":
    record = json.loads(LEDGER.read_text(encoding="utf-8"))
    assert inspect(ROOT, record, PRE, WORKFLOW) == (10, 4)
    import copy
    for altered, preflight, workflow in (
        ({**record, "controls": record["controls"][:-1]}, PRE, WORKFLOW),
        ({**record, "gaps": record["gaps"][:-1]}, PRE, WORKFLOW),
        (record, PRE.replace("sec-p2-findings-gate.py", ""), WORKFLOW),
        ({**record, "controls": [{**record["controls"][0], "test": "../../private.txt"}, *record["controls"][1:]]}, PRE, WORKFLOW),
    ):
        try:
            inspect(ROOT, altered, preflight, workflow)
        except (ValueError, KeyError):
            pass
        else:
            raise AssertionError("SECDEV-07 accepted a missing security proof or undisclosed risk")
    print("SECDEV-07 security process: 10 source-backed controls, 4 explicit gaps and negative fixtures OK")
