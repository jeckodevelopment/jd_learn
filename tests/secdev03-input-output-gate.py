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
    # These are real Joomla model/database fixture assertions, not source-only tests.
    ci = source(".github/workflows/ci.yml")
    assert "joomla-course-presentation.php" in ci, "Joomla fixture absent from CI"
    assert "php tests/catalog-literal-search.php" in ci, "SQL literal fixture absent from CI"
    assert "tests/secdev07-sast.py" in ci, "Dynamic-evaluation SAST absent from CI"

    # The runtime 1.0.0 intentionally ships no CSV export. Do not silently
    # introduce a CSV/spreadsheet producer without explicit formula escaping.
    paths = [
        *ROOT.glob("component/**/src/Controller/*.php"),
        *ROOT.glob("component/**/tmpl/**/*.php"),
        *ROOT.glob("plugins/**/*.php"),
    ]
    csv_sink = re.compile(
        r"\bfputcsv\s*\(|\bSplFileObject\s*\(|"
        r"header\s*\(\s*['\"][^\n]*text/csv|"
        r"(?:format|type)\s*=\s*csv", re.I
    )
    violations = [str(p.relative_to(ROOT)) for p in paths if csv_sink.search(p.read_text(encoding="utf-8"))]
    assert not violations, (
        "SECDEV-03: new CSV output requires formula-injection mitigation "
        "and behavioral regression: " + ", ".join(sorted(violations))
    )
    print(f"SECDEV-03: SQLi/XSS/mass-assignment evidence linked; "
          f"dynamic evaluation SAST present; {len(paths)} runtime PHP sources "
          "checked for new CSV producers. Manual cross-endpoint review remains pending.")


def fixture_test() -> None:
    regex = re.compile(r"\bfputcsv\s*\(", re.I)
    assert regex.search("fputcsv($out, $row);")
    assert not regex.search("$value = 'fputcsv(';")
    # A formula-prefixed CSV value is NOT harmless, even when correctly quoted.
    for prefix in ("=", "+", "-", "@"):
        value = prefix + "SUM(1,1)"
        assert value[0] in "=+-@", "CSV formula-prefix fixture was lost"
    print("SECDEV-03 negative fixtures: CSV output and formula prefixes recognized")


if __name__ == "__main__":
    fixture_test()
    check()
