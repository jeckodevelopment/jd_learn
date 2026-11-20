#!/usr/bin/env python3
"""UI-07: versioned DOM contracts for every public JD Learn view.

Structural guard only: conditional markup, shared PHP partials, multiple Joomla
modules, CSS cascade and real browser accessibility require separate testing.
"""
from pathlib import Path
import json
import re
import xml.etree.ElementTree as ET

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
    ids = set(re.findall(r'\bid="(jlms-[a-zA-Z0-9_-]+)"', source))
    classes = set()
    for group in re.findall(r'\bclass="([^"]+)"', source):
        classes.update(token for token in group.split() if re.fullmatch(r'jlms-[a-zA-Z0-9_-]+', token))
    assert spec["root"] in ids, (view, "root")
    assert sorted(ids) == spec["static_ids"], (view, "static ID drift")
    assert sorted(classes) == spec["static_classes"], (view, "public class drift")
    # Repeated literal IDs can occur in mutually exclusive PHP branches.
    # Assert rendered DOM uniqueness separately in Joomla/browser acceptance.
# Partial templates are distributed public markup too. Inventory every file,
# enforce its literal contract and the precise set of consuming views.
partials = registry["partials"]
actual_partials = {p.stem: p for p in SITE.glob("*/_*.php")}
assert set(partials) == set(actual_partials), (
    "unregistered partial", sorted(set(actual_partials) - set(partials)),
    "obsolete partial", sorted(set(partials) - set(actual_partials)))

def literals(source):
    found_ids = sorted(set(re.findall(r'\\bid="(jlms-[a-zA-Z0-9_-]+)"', source)))
    found_classes = sorted({token for group in re.findall(r'\\bclass="([^"]+)"', source)
                            for token in group.split()
                            if re.fullmatch(r'jlms-[a-zA-Z0-9_-]+', token)})
    return found_ids, found_classes

for name, spec in partials.items():
    file = actual_partials[name]
    assert spec["layout"] == file.relative_to(ROOT).as_posix(), (name, "layout")
    assert spec["included_by"] and len(set(spec["included_by"])) == len(spec["included_by"])
    text = file.read_text(encoding="utf-8")
    actual_ids, actual_classes = literals(text)
    assert actual_ids == spec["static_ids"], (name, "partial ID contract drift")
    assert actual_classes == spec["static_classes"], (name, "partial CSS contract drift")
    consumers = sorted(view for view in views if file.name in
                       (SITE / view / "default.php").read_text(encoding="utf-8"))
    assert sorted(spec["included_by"]) == consumers, (name, "partial consumers drift")
    assert not set(actual_ids).intersection(views[consumers[0]]["static_ids"]), (
        name, "partial collides with its first page")

for view, spec in views.items():
    # All literally declared IDs in this page and its linked partials must
    # be unique. Dynamic IDs and mutually exclusive branches are additionally
    # guarded by the rendered Joomla DOM tests.
    static_ids = list(spec["static_ids"])
    for partial in partials.values():
        if view in partial["included_by"]:
            static_ids.extend(partial["static_ids"])
    assert len(static_ids) == len(set(static_ids)), (view, "page/partial ID collision")

# Negative fixtures prevent an accidentally relaxed comparison from passing:
# a new public class and an undeclared shared ID must both count as drift.
ids, classes = literals('<section id="jlms-fixture" class="jlms-fixture jlms-added"></section>')
assert ids == ["jlms-fixture"] and classes == ["jlms-added", "jlms-fixture"]
assert (ids, classes) != ([], []), "partial drift fixture was accepted"

# Shared partials must not be mistaken for a different standalone view. The
# dashboard and mycourses intentionally have the same root, on different pages.
personal = (SITE / "dashboard/_personal_navigation.php").read_text(encoding="utf-8")
assert "jlms-personal-navigation" in personal
for view in ("dashboard", "mycourses", "mycertificates", "preferences"):
    assert "_personal_navigation.php" in (SITE / view / "default.php").read_text()
# JD Learn 1.0.0 distributes component views and plugins but no Joomla
# module extension of its own. Third-party modules are checked for collisions
# in the rendered HTML fixture, not attributed as package-owned source.
package = ET.parse(ROOT / "packages/pkg_jlms.xml").getroot()
assert not package.findall(".//file[@type='module']"), "new module requires a versioned DOM map"

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
print(f"UI-07 DOM contract v1.0.0: {len(views)} views, {len(partials)} shared partials and 2 independent CSS overrides, structural checks OK")
