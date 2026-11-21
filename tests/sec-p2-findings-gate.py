#!/usr/bin/env python3
"""SEC-P2 findings gate: fail on unresolved high/critical shipped-code issues.

This checks the committed evidence ledger against current source and tests.
It explicitly does NOT clear hosted Joomla, PHP, logs or legacy static files.
"""
from pathlib import Path
import json
import runpy
import tempfile

ROOT = Path(__file__).resolve().parents[1]
LEDGER = ROOT / "docs/SEC_P2_FINDINGS_1.0.0.json"
REQUIRED = {
    "MAILQUEUE-GLOBAL-ACL", "VIDEO-ROOT", "VIDEO-LEGACY-STORAGE",
    "JOOMLA-CORE-DEPENDENCIES-NOV2026", "PHP-PATCH-FLOOR-20261119",
    "DISTRIBUTABLE-ARCHIVE", "HISTORICAL-SECRET-SIGNATURES",
}
SEVERITIES = {"low", "medium", "high", "critical", "unknown"}
STATUSES = {"mitigated", "unresolved", "deployment_verification_pending"}
SOFTWARE = {"first_party_runtime", "first_party_package", "first_party_source", "ci_runtime"}
EXTERNAL = {"existing_hosted_installations", "hosted_joomla_platform"}


def inspect(root: Path, ledger: dict) -> tuple[int, int]:
    assert ledger.get("schema") == 1 and ledger.get("baseline") == "1.0.0"
    assert ledger.get("decision_policy") == (
        "candidate_source_no_confirmed_unresolved_critical_high; "
        "installed_platform_is_separate_release_acceptance"
    )
    findings = ledger.get("findings", [])
    assert {f.get("id") for f in findings} == REQUIRED
    unresolved_source = 0
    external_pending = 0
    for f in findings:
        assert f.get("severity") in SEVERITIES
        assert f.get("status") in STATUSES
        assert f.get("surface") in SOFTWARE | EXTERNAL
        assert isinstance(f.get("summary"), str) and len(f["summary"]) >= 40
        assert f.get("evidence") and f.get("checks") and f.get("test_suite")
        if f["surface"] in SOFTWARE:
            if f["severity"] in {"high", "critical"} and f["status"] != "mitigated":
                unresolved_source += 1
            assert f["status"] != "deployment_verification_pending"
        else:
            assert f["status"] == "deployment_verification_pending"
            assert f.get("release_blocker") is True
            external_pending += 1
        for name in f["evidence"]:
            path = root / name
            assert path.is_file() and path.resolve().is_relative_to(root.resolve())
        for check in f["checks"]:
            path = root / check["path"]
            assert path.is_file() and path.resolve().is_relative_to(root.resolve())
            body = path.read_text(encoding="utf-8")
            assert check.get("contains")
            assert all(marker in body for marker in check["contains"]), f["id"]
    assert external_pending >= 2, "Hosted risk cannot be silently declared cleared"
    return unresolved_source, external_pending


def main():
    ledger = json.loads(LEDGER.read_text(encoding="utf-8"))
    source, external = inspect(ROOT, ledger)
    # Negative regressions: an unresolved high finding must not pass, and
    # an unverified hosted risk must never be silently marked mitigated.
    import copy
    broken = copy.deepcopy(ledger)
    next(f for f in broken["findings"] if f["id"] == "MAILQUEUE-GLOBAL-ACL")["status"] = "unresolved"
    assert inspect(ROOT, broken)[0] == 1
    broken = copy.deepcopy(ledger)
    next(f for f in broken["findings"] if f["id"] == "VIDEO-LEGACY-STORAGE")["status"] = "mitigated"
    try:
        inspect(ROOT, broken)
    except AssertionError:
        pass
    else:
        raise AssertionError("Unverified deployment risk accepted")
    broken = copy.deepcopy(ledger)
    next(f for f in broken["findings"] if f["id"] == "VIDEO-ROOT")["checks"][0]["contains"] = ["NO_SUCH_GUARD"]
    try:
        inspect(ROOT, broken)
    except AssertionError:
        pass
    else:
        raise AssertionError("Missing mitigation guard accepted")
    print(f"SEC-P2 reviewed: {len(ledger['findings'])} findings, "
          f"{source} unresolved critical/high source, "
          f"{external} externally gated findings")
    if source:
        raise SystemExit(2)


if __name__ == "__main__":
    main()
