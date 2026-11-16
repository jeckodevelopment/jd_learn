#!/usr/bin/env python3
"""FE-10: contract smoke check for shared public cards and existing JS hooks.

Run: python3 tests/frontend-css-contract.py
No Joomla fixture required; browser/override acceptance remains manual.
"""
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
LAYOUTS = {
    "component/site/tmpl/courses/default.php": ('jlms-card jlms-course-card', 'jlms-course-card-'),
    "component/site/tmpl/pathways/default.php": ('jlms-card jlms-course-card jlms-pathway-card', 'jlms-pathway-'),
    "component/site/tmpl/categories/default.php": ('jlms-card jlms-category-card', 'jlms-category-card-'),
    "component/site/tmpl/dashboard/_courses.php": ('jlms-card jlms-my-course', 'jlms-my-course-'),
}
for path, (classes, id_fragment) in LAYOUTS.items():
    source = (ROOT / path).read_text()
    assert f'class="{classes}"' in source, path
    assert id_fragment in source, path

css = (ROOT / "component/media/css/site.css").read_text()
assert ".jlms-card { box-sizing: border-box; min-width: 0; }" in css
assert ".jlms-card :where(h2, h3, p)" in css
for root in ("#jlms-courses", "#jlms-categories", "#jlms-dashboard", "#jlms-pathways"):
    assert root in css.split(".jlms-card {", 1)[0], root

# Critical ID and data-hook compatibility for the classroom and catalogs.
classroom = (ROOT / "component/site/tmpl/classroom/default.php").read_text()
js = (ROOT / "component/media/js/classroom-outline.js").read_text()
for hook in ("jlms-classroom-outline-toggle", "jlms-classroom-outline", "data-show-label", "data-hide-label"):
    assert hook in classroom, hook
for hook in ("#jlms-classroom-outline-toggle", "#jlms-classroom-outline", "aria-expanded"):
    assert hook in js, hook
assert 'data-course-id=' in (ROOT / "component/site/tmpl/courses/default.php").read_text()
assert 'data-enrollment-id=' in (ROOT / "component/site/tmpl/dashboard/_courses.php").read_text()
assert "require __DIR__ . '/../dashboard/_courses.php'" in (ROOT / "component/site/tmpl/mycourses/default.php").read_text()
assert 'jlms-dashboard-summary' in (ROOT / "component/site/tmpl/dashboard/default.php").read_text()
print("FE-10 shared CSS hooks and existing classroom JS contract: OK")
