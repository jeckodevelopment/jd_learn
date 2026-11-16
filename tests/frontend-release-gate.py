#!/usr/bin/env python3
"""FE-12: fail-closed structural frontend release gate.

This checks source contracts. It does NOT certify WCAG, template compatibility,
cross-user authorization or acceptance on a real Joomla site.
"""
from pathlib import Path
import json
import re
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]
SITE = ROOT / "component/site"
MEDIA = ROOT / "component/media"
views = {
    "courses": "jlms-courses",
    "course": "jlms-course",
    "categories": "jlms-categories",
    "category": "jlms-category",
    "pathways": "jlms-pathways",
    "pathway": "jlms-pathway",
    "teachers": "jlms-teachers",
    "teacher": "jlms-teacher-profile",
    "dashboard": "jlms-dashboard",
    "mycourses": "jlms-dashboard",
    "mycertificates": "jlms-my-certificates-view",
    "preferences": "jlms-preferences-view",
    "classroom": "jlms-classroom",
}
# Only views intended for a Joomla menu (not classroom or privileged editing).
menu_views = set(views) - {"classroom"}
for view, root_id in views.items():
    layout_path = SITE / "tmpl" / view / "default.php"
    view_path = SITE / "src" / "View" / view.title() / "HtmlView.php"
    assert layout_path.is_file() and view_path.is_file(), (view, "missing MVC/layout")
    layout = layout_path.read_text()
    source = view_path.read_text()
    assert 'id="' + root_id + '"' in layout, (view, "root missing")
    assert "useStyle('com_jlms.site')" in source, (view, "missing WAM frontend style")
    assert "\\defined('_JEXEC') or die" in layout or "defined('_JEXEC') or die" in layout, (view, "guard missing")
    if view in menu_views:
        xml = SITE / "tmpl" / view / "default.xml"
        assert xml.is_file(), (view, "not selectable as menu")
        metadata = ET.parse(xml).getroot()
        title = metadata.find("layout").get("title")
        for lang in ("it-IT", "en-GB"):
            language = (SITE / "language" / lang / "com_jlms.ini").read_text()
            assert re.search(r"^" + re.escape(title) + r"=", language, re.M), (view, lang, title)

for view in ("dashboard", "mycourses", "mycertificates", "preferences"):
    source = (SITE / "src" / "View" / view.title() / "HtmlView.php").read_text()
    assert "noindex, nofollow" in source, (view, "private page indexed")
    assert "useStyle('com_jlms.site')" in source, view

router = (SITE / "src/Service/Router.php").read_text()
for view in views:
    assert "new RouterViewConfiguration('" + view + "')" in router, (view, "no native router")
for view in ("teachers", "pathways", "category", "courses"):
    layout = (SITE / "tmpl" / view / "default.php").read_text()
    assert "Route::_(" in layout, (view, "native routing missing")

css = (MEDIA / "css/site.css").read_text()
assert "--jlms-content-width" in css and "--jlms-color-primary" in css
assert "@container" in css, "responsive container behavior missing"
assert "focus-visible" in css, "keyboard focus styling missing"
assert "max-width:100%" in css, "fluid media missing"
# Confirm no new full-screen/viewport takeover or forced template navigation reset.
for token in ("body{overflow:hidden", "html{overflow:hidden", "header{display:none",
              ".navbar{display:none", "main{display:none"):
    assert token not in re.sub(r"\\s+", "", css), ("global template override", token)
for root_id in ("jlms-courses", "jlms-category", "jlms-teachers", "jlms-pathways",
                "jlms-dashboard", "jlms-classroom"):
    assert "#" + root_id in css, (root_id, "missing component CSS")

asset = json.loads((MEDIA / "joomla.asset.json").read_text())
assets = {record["name"] for record in asset["assets"]}
assert "com_jlms.site" in assets, "native stylesheet not registered"
for path in ("tests/frontend-contract-hooks.py", "tests/frontend-css-contract.py",
             "tests/frontend-menu-inheritance.py", "tests/frontend-teachers-directory.py",
             "tests/frontend-classroom-layout.py", "tests/frontend-personal-navigation.py"):
    assert (ROOT / path).is_file(), ("missing prerequisite automated gate", path)

print(f"FE-12 structural gate: {len(views)} MVC views, {len(menu_views)} menu metadata, "
      "private SEO, CSS, native router and WAM contracts OK")
print("NOT VERIFIED: WCAG 2.2 AA, responsive rendering, user ACL/HTTP, overrides "
      "and real Joomla acceptance; see docs/FRONTEND_FE12_ACCEPTANCE.md")
