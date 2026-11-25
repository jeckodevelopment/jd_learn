#!/usr/bin/env python3
"""SECDEV-02: verify CI evidence wiring for every CORE-01..23 service.

This checks that the canonical reviewed test candidates are actually referenced
from the CI workflow or its mandatory tools/check.py runner. It does not
pretend to replace installed-site or external-provider acceptance.
"""
from __future__ import annotations

import argparse
import json
from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[1]
EXPECTED = {f"CORE-{i:02d}" for i in range(1, 24)}
MODES = {"ci_plus_site", "site_and_external", "code_complete_site_pending"}


def inspect(services: list[dict], workflow: str, checks: str) -> tuple[list[str], dict]:
    errors: list[str] = []
    ids = [row.get("id") for row in services]
    if len(ids) != 23 or set(ids) != EXPECTED or len(set(ids)) != 23:
        errors.append("CORE-01..23 must each appear exactly once in the CI evidence register")
    covered = 0
    referenced = set()
    environmental = []
    manual = []
    for row in services:
        core = str(row.get("id"))
        reconciliation = row.get("reconciliation", {})
        if not isinstance(reconciliation, dict):
            errors.append(f"{core}: missing reconciliation")
            continue
        mode = reconciliation.get("acceptance_mode")
        if mode not in MODES:
            errors.append(f"{core}: invalid acceptance mode")
        candidates = reconciliation.get("existing_automated_evidence", [])
        if not isinstance(candidates, list) or not candidates:
            errors.append(f"{core}: no reviewed CI test candidates")
            continue
        wired = []
        for path in candidates:
            if not isinstance(path, str) or not path.startswith("tests/"):
                errors.append(f"{core}: invalid CI test path")
                continue
            if path not in workflow and path not in checks:
                errors.append(f"{core}: listed evidence is not wired to CI: {path}")
            else:
                wired.append(path)
                referenced.add(path)
        if not wired:
            errors.append(f"{core}: no runnable test linked to the mandatory CI pipeline")
        else:
            covered += 1
        if mode == "site_and_external":
            environmental.append(core)
        if reconciliation.get("pending_criteria"):
            manual.append(core)
    return errors, {
        "services": len(set(ids) & EXPECTED),
        "services_with_ci_evidence": covered,
        "distinct_test_paths_wired": len(referenced),
        "needs_external_environment": sorted(environmental),
        "criteria_still_unverified": sorted(manual),
        "warning": (
            "A CI reference is traceability, not evidence of passing a runtime "
            "matrix or completing Joomla/manual/external acceptance."
        ),
    }


def self_test() -> None:
    rows = [
        {"id": core, "reconciliation": {
            "acceptance_mode": "ci_plus_site",
            "pending_criteria": ["fixture"],
            "existing_automated_evidence": ["tests/fixture.py"],
        }}
        for core in sorted(EXPECTED)
    ]
    errors, report = inspect(rows, "python3 tests/fixture.py", "")
    assert not errors and report["services_with_ci_evidence"] == 23
    errors, _ = inspect(rows, "", "")
    assert len([error for error in errors if "not wired" in error]) == 23
    assert len([error for error in errors if "no runnable" in error]) == 23
    rows[0]["reconciliation"]["existing_automated_evidence"] = ["tests/missing.py"]
    errors, _ = inspect(rows, "python3 tests/fixture.py", "")
    assert any("not wired" in error for error in errors)
    rows[0]["id"] = rows[1]["id"]
    errors, _ = inspect(rows, "python3 tests/fixture.py", "")
    assert any("exactly once" in error for error in errors)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    if args.self_test:
        self_test()
        print("SECDEV-02 CORE 23-service wiring negative fixtures: OK")
        return 0
    try:
        ledger = json.loads(
            (ROOT / "docs/SECDEV_02_CORE_SERVICE_DECISIONS_1.0.0.json")
            .read_text(encoding="utf-8")
        )
        workflow = (ROOT / ".github/workflows/ci.yml").read_text(encoding="utf-8")
        checks = (ROOT / "tools/check.py").read_text(encoding="utf-8")
    except (OSError, ValueError) as exc:
        print(f"SECDEV-02 CORE pipeline evidence unavailable: {exc}", file=sys.stderr)
        return 1
    errors, report = inspect(ledger["services"], workflow, checks)
    for error in errors:
        print(error, file=sys.stderr)
    print(json.dumps(report, ensure_ascii=False, sort_keys=True))
    return 1 if errors else 0


if __name__ == "__main__":
    raise SystemExit(main())
