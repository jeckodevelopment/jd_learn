#!/usr/bin/env python3
"""Fail-closed wiring check for the SEC-P5 negative Joomla E2E suite.

The listed behavioral tests execute in the verify integration job. This gate
makes their presence and the concrete negative assertions reviewable and
prevents the CI wiring from silently dropping a suite.
"""
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
MANIFEST = ROOT / "docs/SEC_P5_NEGATIVE_E2E_1.0.0.json"
WORKFLOW = ROOT / ".github/workflows/ci.yml"


def inspect(manifest: dict, workflow: str, root: Path = ROOT) -> None:
    assert manifest.get("schema") == 1
    assert manifest.get("baseline") == "1.0.0"
    assert "not a penetration test" in manifest.get("purpose", "")
    suites = manifest.get("suites")
    assert isinstance(suites, list) and len(suites) >= 5
    ids = [suite.get("id") for suite in suites]
    assert len(ids) == len(set(ids)), "SEC-P5 suite IDs must be unique"
    for suite in suites:
        command = suite.get("command", "")
        test_path = suite.get("test", "")
        assert command and test_path and command in workflow, f"CI does not execute {suite.get('id')}"
        source = (root / test_path).read_text(encoding="utf-8")
        markers = suite.get("assertions")
        assert isinstance(markers, list) and markers
        assert all(marker in source for marker in markers), f"negative assertion missing: {suite.get('id')}"


def main() -> None:
    manifest = json.loads(MANIFEST.read_text(encoding="utf-8"))
    workflow = WORKFLOW.read_text(encoding="utf-8")
    inspect(manifest, workflow)
    # Negative fixtures prove that removing a runtime command or assertion fails closed.
    import copy
    broken = copy.deepcopy(manifest)
    broken["suites"][0]["command"] = "php tests/not-run.php"
    try:
        inspect(broken, workflow)
    except AssertionError:
        pass
    else:
        raise AssertionError("missing CI security suite was accepted")
    broken = copy.deepcopy(manifest)
    broken["suites"][0]["assertions"] = ["ASSERTION_THAT_DOES_NOT_EXIST"]
    try:
        inspect(broken, workflow)
    except AssertionError:
        pass
    else:
        raise AssertionError("missing negative assertion was accepted")
    print(f"SEC-P5 negative E2E wiring: {len(manifest['suites'])} suites, passed")


if __name__ == "__main__":
    main()
