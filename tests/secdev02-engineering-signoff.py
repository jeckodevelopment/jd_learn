#!/usr/bin/env python3
"""SECDEV-02: evidence-backed developer-side signoff, separate from site QA.

This is a regression/traceability gate over reviewed implementation contracts.
The full CI executes the referenced tests; the gate alone cannot substitute
for those tests, all real-world permutations, or manual acceptance.
"""
from __future__ import annotations

import argparse
from collections import Counter
from copy import deepcopy
import json
from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[1]

# Two independent checked scenarios for EACH of the 15 technical residuals,
# reviewed against merged native Joomla / HTTP integration regressions.
# format: source file, ((executable test, exact scenario marker), ...)
CASES = {
    "CORE-01/all_hierarchy": (
        "component/administrator/src/Table/TeacherTable.php",
        ("tests/joomla-categories.php", "CORE-01: full three-level native ancestor order and IDs preserved"),
        ("tests/joomla-categories.php", "CORE-01: moving native subtree back restores original ancestor ordering"),
    ),
    "CORE-03/cross_context": (
        "component/administrator/src/Application/CatalogService.php",
        ("tests/joomla-categories.php", "CORE-03: restoring "),
        ("tests/joomla-editor-access.php", "CORE-03/04: same-session state permission revocation denies previously editable course"),
    ),
    "CORE-04/record_acl": (
        "component/administrator/src/Model/CourseModel.php",
        ("tests/joomla-editor-access.php", "CORE-04: mixed record-level allow and explicit deny rejects whole course lifecycle batch"),
        ("tests/joomla-teachers.php", "CORE-04: foreign checkout in mixed teacher batch rejects entire restoration"),
    ),
    "CORE-04/teacher_edit": (
        "component/administrator/src/Model/TeacherModel.php",
        ("tests/joomla-teachers.php", "CORE-04: learner cannot forge teacher administrative fields"),
        ("tests/joomla-teachers.php", "CORE-04: rejected teacher field edit preserves profile and native tag associations"),
    ),
    "CORE-05/layout_coverage": (
        "component/media/css/site.css",
        ("tests/secdev02-core05-layout-inventory.py", "layout inventory drift"),
        ("tests/secdev02-core05-layout-inventory.py", "module layout inventory drift"),
    ),
    "CORE-06/strings": (
        "component/site/language/it-IT/com_jlms.ini",
        ("tests/secdev02-core06-runtime-keys.py", "missing literal UI key"),
        ("tests/secdev02-core06-runtime-keys.py", "static_joined_keys("),
    ),
    "CORE-07/menu_matrix": (
        "component/site/src/Controller/DisplayController.php",
        ("tests/categories-http.py", "CORE-07: explicit menu Itemid selects the authorised English category"),
        ("tests/joomla-categories.php", "native category and category-index menus in both languages"),
    ),
    "CORE-10/teacher_scoping": (
        "component/site/src/Model/TeacherModel.php",
        ("tests/joomla-course-fields.php", "CORE-10: native course field output excludes the other locale"),
        ("tests/secdev02-core-native-contracts.py", "CORE-10: teacher field language scoping lost"),
    ),
    "CORE-11/content_events": (
        "component/site/src/Helper/PublicContentPreparation.php",
        ("tests/joomla-course-fields.php", "CORE-11: pre-event input and post-event HTML enforce the public allowlist"),
        ("tests/joomla-course-fields.php", "CORE-11: content plugin preparation rejects mismatched source identity"),
    ),
    "CORE-13/all_routes": (
        "component/site/src/Controller/DisplayController.php",
        ("tests/joomla-pathways.php", "CORE-13: valid editorial route"),
        ("tests/joomla-pathways.php", "CORE-13: malformed editorial route rejected"),
    ),
    "CORE-14/index_acl": (
        "plugins/finder/jlms/src/Extension/Jlms.php",
        ("tests/joomla-categories.php", "CORE-14: real Joomla Finder index contains the published unrestricted course"),
        ("tests/joomla-categories.php", "CORE-14: native category event removes descendant Finder entry"),
    ),
    "CORE-15/native_workflow": (
        "component/administrator/src/Model/PathwayModel.php",
        ("tests/joomla-pathways.php", "CORE-15: invalid transition rejected"),
        ("tests/joomla-pathways.php", "CORE-15: rejected transition preserves states, revisions and course links"),
    ),
    "CORE-16/all_entities": (
        "component/administrator/src/Model/CourseModel.php",
        ("tests/joomla-courses-ui.php", "CORE-16: concurrent checkout prevents course lifecycle mutation"),
        ("tests/joomla-courses-ui.php", "CORE-16: denied checkout does not append phantom Joomla content history"),
    ),
    "CORE-19/idempotent_tasks": (
        "plugins/task/jlms/src/Extension/Jlms.php",
        ("tests/secdev02-core19-mail-batch-bound.py", "CORE-19 Scheduler batch-bound negative fixtures"),
        ("tests/secdev02-core19-mail-batch-bound.py", "CORE-19: 4 enqueue services"),
    ),
    "CORE-20/native_completeness": (
        "component/administrator/src/Model/CourseModel.php",
        ("tests/joomla-courses-ui.php", "CORE-20: malformed admin filter and total fail closed: "),
        ("tests/joomla-courses-ui.php", "CORE-20: clearing malformed filter restores valid list and total: "),
    ),
}
SITE_EXTERNAL = {
    "CORE-02/auth_variants",
    "CORE-08/scope",
    "CORE-12/media_providers",
    "CORE-18/smtp",
    "CORE-22/release_updates",
    "CORE-23/event_contracts",
}
MANDATORY_CI = (
    "python3 tests/secdev02-psr12-phpcs.py",
    "python3 tests/secdev02-package-core-boundary.py",
    "php tests/secdev02-joomla-api-inventory.php",
    "php tests/secdev02-compat6-disabled.php",
    "python3 tests/secdev02-core-ci-wiring.py",
    "python3 tests/secdev02-core-residual-split.py",
)
EXPECTED_SECDEV = {"criterion_01", "criterion_02", "criterion_03", "criterion_04"}


def inspect(root: Path, cases: dict = CASES,
            external: set = SITE_EXTERNAL, override: dict | None = None
            ) -> tuple[list[str], dict]:
    errors = []
    replacements = override or {}

    def content(path: str) -> str:
        if path in replacements:
            return replacements[path]
        return (root / path).read_text(encoding="utf-8")

    try:
        plan = json.loads(content("docs/SECDEV_02_CORE_RESIDUAL_CLASSIFICATION_2026-11-25.json"))
        criteria = json.loads(content("docs/FUNCTIONAL_CRITERIA_1.0.0.json"))
        workflow = content(".github/workflows/ci.yml")
        checks = content("tools/check.py")
    except (OSError, ValueError) as exc:
        return [f"Cannot verify canonical SECDEV-02 input: {exc}"], {}
    expected = {f"{p['service']}/{p['criterion']}": p for p in plan["criteria"]}
    actual = {
        f"{service}/{item['id']}": item
        for service, units in criteria["requirements"].items()
        if service.startswith("CORE-")
        for item in units if item["status"] != "implemented"
    }
    if set(actual) != set(expected) or len(plan["criteria"]) != len(expected):
        errors.append("Historical residual ledger differs from the canonical CORE inventory")
    if set(cases) & set(external) or set(cases) | set(external) != set(expected):
        errors.append("Every pending CORE criterion must be assigned to development or site exactly once")
    if len(cases) != 15 or len(external) != 6:
        errors.append("Technical/site separation must be reviewed: expected 15/6")
    if {x["id"] for x in criteria["requirements"]["SECDEV-02"]} != EXPECTED_SECDEV:
        errors.append("SECDEV-02 general criteria inventory drifted")
    if any(x["status"] != "unverifiable" for x in criteria["requirements"]["SECDEV-02"][:3]):
        errors.append("Site/technical acceptance cannot be silently promoted in the canonical register")
    if any(x["status"] != "implemented" for x in criteria["requirements"]["SECDEV-02"][3:]):
        errors.append("Signed-off Joomla core decision criterion unexpectedly downgraded")

    for key, row in expected.items():
        target = "developer_work_pending" if key in cases else "site_external_only"
        if row.get("acceptance_class") != target:
            errors.append(f"{key}: technical vs external historical classification changed")
        if actual.get(key, {}).get("status") != "unverifiable":
            errors.append(f"{key}: canonical manual acceptance silently completed")

    for gate in MANDATORY_CI:
        if gate not in workflow:
            errors.append("Mandatory API, PSR-12, package or CORE CI gate absent: " + gate)

    for key, evidence in cases.items():
        source, *tests = evidence
        if len(tests) < 2 or len(set(tests)) != len(tests):
            errors.append(f"{key}: at least two distinct technical scenarios are required")
        if not source.startswith(("component/", "libraries/", "plugins/")) or not (root / source).is_file():
            errors.append(f"{key}: implementation source missing: {source}")
        for path, marker in tests:
            if not path.startswith("tests/") or len(marker) < 18:
                errors.append(f"{key}: invalid scenario test")
                continue
            try:
                code = content(path)
            except OSError:
                errors.append(f"{key}: test absent: {path}")
                continue
            if marker not in code:
                errors.append(f"{key}: named regression scenario missing in {path}: {marker}")
            if path not in workflow and path not in checks:
                errors.append(f"{key}: scenario test not invoked by mandatory CI: {path}")

    return errors, {
        "baseline": "1.0.0",
        "technical_criteria_reviewed": len(cases),
        "technical_evidence_scenarios": sum(len(v) - 1 for v in cases.values()),
        "technical_scope_ready_for_manual_qa": len(cases) if not errors else 0,
        "environmental_criteria_pending_manual_acceptance": len(external),
        "canonical_core_criteria_pending": len(actual),
        "canonical_secdev_criteria_pending": sum(
            item["status"] != "implemented"
            for item in criteria["requirements"]["SECDEV-02"]
        ),
        "note": (
            "Engineering signoff is limited to documented CI behavioral scenarios, "
            "code and target runtime. It does NOT prove the full cross-product of "
            "roles, provider services, hosting, real templates, or site acceptance."
        ),
    }


def self_test() -> None:
    failures, _ = inspect(ROOT)
    assert not failures, failures
    evidence = deepcopy(CASES)
    evidence["CORE-01/all_hierarchy"] = (
        evidence["CORE-01/all_hierarchy"][0],
        ("tests/joomla-categories.php", "NONEXISTENT_CORE_ASSERTION"),
        evidence["CORE-01/all_hierarchy"][2],
    )
    assert any("scenario missing" in x for x in inspect(ROOT, evidence)[0])
    evidence = deepcopy(CASES)
    evidence.pop("CORE-09/tags_context")
    assert any("exactly once" in x or "expected 17/6" in x for x in inspect(ROOT, evidence)[0])
    workflow = (ROOT / ".github/workflows/ci.yml").read_text(encoding="utf-8")
    assert any("Mandatory" in x for x in inspect(
        ROOT, override={".github/workflows/ci.yml": workflow.replace(MANDATORY_CI[0], "")}
    )[0])
    criteria_path = "docs/FUNCTIONAL_CRITERIA_1.0.0.json"
    criteria = json.loads((ROOT / criteria_path).read_text(encoding="utf-8"))
    criteria["requirements"]["SECDEV-02"][0]["status"] = "implemented"
    assert any("silently promoted" in x for x in inspect(
        ROOT, override={criteria_path: json.dumps(criteria)}
    )[0])


def main() -> int:
    p = argparse.ArgumentParser()
    p.add_argument("--self-test", action="store_true")
    p.add_argument("--require-technical-signoff", action="store_true")
    args = p.parse_args()
    if args.self_test:
        self_test()
        print("SECDEV-02 16-criterion engineering evidence negative fixtures: OK")
        return 0
    failures, report = inspect(ROOT)
    print(json.dumps(report, ensure_ascii=False, sort_keys=True))
    for failure in failures:
        print("SECDEV-02 technical evidence: " + failure, file=sys.stderr)
    if args.require_technical_signoff and failures:
        print("SECDEV-02 implementation signoff BLOCKED", file=sys.stderr)
    return 1 if failures else 0


if __name__ == "__main__":
    raise SystemExit(main())
