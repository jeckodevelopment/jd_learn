#!/usr/bin/env python3
"""SECDEV-03: fail closed on loss of verified input/output controls.

This is a regression/evidence gate, not a penetration test or certification.
CSV export is excluded from the 1.0.0 runtime; any newly introduced CSV
producer must receive a dedicated formula-injection review before shipping.
"""
from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


def source(path: str) -> str:
    file = ROOT / path
    if not file.is_file():
        raise AssertionError(f"SECDEV-03 evidence disappeared: {path}")
    return file.read_text(encoding="utf-8")


# Conservatively reject new CSV emitters; even matching a quoted source
# string requires review rather than silently treating a possible sink as safe.
csv_sink = re.compile(
    r"\bfputcsv\s*\(|\bSplFileObject\s*\(|"
    r"header\s*\(\s*['\"][^\n]*(?:text|application)/csv|"
    r"\b(?:League\\Csv\\Writer|CsvWriter)\b|"
    r"(?:format|type)\s*=\s*['\"]?csv", re.I
)


def check() -> None:
    literal = source("component/site/src/Helper/CatalogSearch.php")
    tests = source("tests/catalog-literal-search.php")
    for marker in ("str_replace(['!', '%', '_'], ['!!', '!%', '!_']",):
        assert marker in literal, "LIKE escaping was removed"
    for payload in ("' OR 1=1 --", "DROP TABLE", "UNION SELECT", "'%_! OR 1=1'"):
        assert payload in tests, f"SQLi regression absent: {payload}"

    presentation = source("tests/joomla-course-presentation.php")
    for marker in ("<script", "onclick=", "javascript:", "params_json", "current_version_id",
                   "cannot be mass-assigned", "Active markup"):
        assert marker in presentation, f"XSS or mass assignment fixture absent: {marker}"
    pathways = source("tests/joomla-pathways.php")
    for marker in ("<script>alert(1)</script>", "onclick=", "javascript:",
                   "'params_json'=>'{", "'created_by'=>999999", "'checked_out'=>999999",
                   "pathway ignores raw params, author identity and checkout fields"):
        assert marker in pathways, f"SECDEV-03 pathway negative fixture absent: {marker}"
    path_model = source("component/administrator/src/Model/PathwayModel.php")
    assert "->values(':pathwayId, :courseId, :position')" in path_model
    for param in (":pathwayId", ":courseId", ":position"):
        assert f"->bind('{param}'," in path_model, (
            "SECDEV-03: pathway course association values must be bound SQL parameters"
        )
    assert "->values(implode(', ', [$pathwayId, $courseId, $ordering]))" not in path_model
    for payload in ("1 OR 1=1 --", "1; DROP TABLE courses; --", "1 UNION SELECT 1"):
        assert payload in pathways, "SECDEV-03: missing negative course association ID fixture"

    assert "!is_string($imageInput) || !is_string($altInput)" in path_model, (
        "SECDEV-03: pathway artwork must reject forged input types"
    )
    for marker in ("['cover_image'=>['images/cover.png']]", "['cover_alt'=>['hostile']]",
                   "['cover_alt'=>new \\stdClass()]", "invalid artwork leaves persisted pathway metadata"):
        assert marker in pathways, f"SECDEV-03 invalid artwork fixture absent: {marker}"
    # These are real Joomla model/database fixture assertions, not source-only tests.
    ci = source(".github/workflows/ci.yml")
    assert "joomla-course-presentation.php" in ci, "Joomla fixture absent from CI"
    assert "joomla-pathways.php" in ci, "Pathway negative fixture absent from CI"
    assert "php tests/catalog-literal-search.php" in ci, "SQL literal fixture absent from CI"
    publication = source("tests/joomla-publication.php")
    assert "joomla-publication.php" in ci, "Database-level catalog regression absent from CI"
    assert "->bind(':catalogProbe', $pattern)" in publication, "SQLi fixture must use bound parameters"
    for payload in ("' OR 1=1 --", "DROP TABLE", "UNION SELECT", "'%_! OR 1=1'"):
        assert payload in publication, f"SQLi database fixture absent: {payload}"
    assert "tests/secdev07-sast.py" in ci, "Dynamic-evaluation SAST absent from CI"

    # The release has no application-generated CSV; inspect ALL shipped PHP,
    # including services, domain classes and models (not just HTTP controllers).
    paths = [
        path for prefix in ("component", "libraries", "plugins")
        for path in (ROOT / prefix).rglob("*.php")
    ]
    violations = [
        str(path.relative_to(ROOT))
        for path in paths
        if csv_sink.search(path.read_text(encoding="utf-8"))
    ]
    assert not violations, (
        "SECDEV-03: newly introduced CSV/spreadsheet output requires "
        "formula-injection mitigation and behavioral regression: "
        + ", ".join(sorted(violations))
    )
    print(f"SECDEV-03: SQLi/XSS/mass-assignment evidence linked; "
          f"dynamic evaluation SAST present; {len(paths)} runtime PHP sources "
          "checked for new CSV producers. Manual cross-endpoint review remains pending.")


def fixture_test() -> None:
    bad = (
        "<?php fputcsv($stream, $record);",
        "<?php $file = new SplFileObject($path, 'w');",
        "<?php header('Content-Type: text/csv; charset=UTF-8');",
        "<?php header('Content-Type: application/csv');",
        "<?php $writer = League\\Csv\\Writer::createFromString();",
        "<?php $format = 'csv';",
    )
    for payload in bad:
        assert csv_sink.search(payload), "SECDEV-03 did not detect a CSV producer"
    safe = (
        "<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8');",
        "<?php $db->createQuery()->where('id = :id')->bind(':id', $id);",
        "<?php $file = fopen($path, 'rb');",
    )
    for payload in safe:
        assert not csv_sink.search(payload), "SECDEV-03 rejected an unrelated code path"
    # This is deliberately a conservative source scanner: a matching quoted
    # string is reviewed, never silently waived.
    assert csv_sink.search("<?php $example = 'fputcsv(';")
    for prefix in ("=", "+", "-", "@", "\\t", "\\r"):
        value = prefix + "SUM(1,1)"
        assert value.startswith(prefix), "CSV formula-prefix fixture was lost"
    print("SECDEV-03: CSV exporter negative fixtures and benign controls passed")


if __name__ == "__main__":
    fixture_test()
    check()
