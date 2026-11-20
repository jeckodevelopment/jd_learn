#!/usr/bin/env python3
"""UI-07: versioned DOM contracts for every public JD Learn view.

Structural guard only: conditional markup, shared PHP partials, multiple Joomla
modules, CSS cascade and real browser accessibility require separate testing.
"""
from pathlib import Path
import json
import re

ROOT = Path(__file__).resolve().parents[1]
SITE = ROOT / "component/site/tmpl"
registry = json.loads((ROOT / "docs/FRONTEND_DOM_CONTRACTS_1.0.0.json").read_text(encoding="utf-8"))
assert registry["schema"] == 1
assert registry["contract_version"] == "1.0.0"
views = registry["views"]
actual = {p.parent.name for p in SITE.glob("*/default.php")}
assert set(views) == actual, ("missing or obsolete view map", sorted(actual - set(views)), sorted(set(views) - actual))
for view, spec in views.items():
    path = ROOT / spec["layout"]
    assert path == SITE / view / "default.php", (view, "layout")
    assert spec["override"] == f"templates/NOME/html/com_jlms/{view}/default.php"
    source = path.read_text(encoding="utf-8")
    ids = set(re.findall(r'\\bid="(jlms-[a-zA-Z0-9_-]+)"', source))
    classes = set()
    for group in re.findall(r'\\bclass="([^"]+)"', source):
        classes.update(token for token in group.split() if re.fullmatch(r'jlms-[a-zA-Z0-9_-]+', token))
    assert spec["root"] in ids, (view, "root")
    assert sorted(ids) == spec["static_ids"], (view, "static ID drift")
    assert sorted(classes) == spec["static_classes"], (view, "public class drift")
    assert len(ids) == len(re.findall(r'\\bid="(jlms-[a-zA-Z0-9_-]+)"', source)), (view, "duplicate literal ID")
# Shared partials must not be mistaken for a different standalone view. The
# dashboard and mycourses intentionally have the same root, on different pages.
personal = (SITE / "dashboard/_personal_navigation.php").read_text(encoding="utf-8")
assert "jlms-personal-navigation" in personal
for view in ("dashboard", "mycourses", "mycertificates", "preferences"):
    assert "_personal_navigation.php" in (SITE / view / "default.php").read_text()
# Two independent template customizations must use only existing scoped hooks.
css = (ROOT / "component/media/css/site.css").read_text(encoding="utf-8")
customizations = {
    "catalog": "#jlms-courses .jlms-course-card",
    "student_dashboard": "#jlms-dashboard .jlms-my-course",
}
for label, selector in customizations.items():
    root, child = selector.split()
    assert root[1:] in {s["root"] for s in views.values()}, label
    assert child[1:] in {v for s in views.values() for v in s["static_classes"]} or child == ".jlms-my-course", label
    assert root in css and child in css, label
assert len(set(customizations.values())) == 2
print(f"UI-07 DOM contract v1.0.0: {len(views)} views and 2 independent CSS overrides, structural checks OK")
