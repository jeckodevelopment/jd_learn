#!/usr/bin/env python3
"""CAT-04 indexing contract: guard every site route that can render owner data."""
from pathlib import Path

root = Path(__file__).resolve().parents[1] / "component/site/src"
def read(path):
    return (root / path).read_text(encoding="utf-8")

controller = read("Controller/DisplayController.php")
private = ("dashboard", "mycourses", "mycertificates", "preferences", "lessonpreview", "teacheredit", "classroom", "quiz")
public = ("courses", "course", "categories", "category", "teachers", "teacher", "pathways", "pathway")
private_line = next(line for line in controller.splitlines() if "setHeader('X-Robots-Tag'" in line or "if (in_array($this->input->getCmd('view')," in line)
assert all("'" + name + "'" in controller.split("setHeader('X-Robots-Tag'",1)[0].split("if (in_array($this->input->getCmd('view'),")[-1] for name in private)
assert all("'" + name + "'" not in controller.split("setHeader('X-Robots-Tag'",1)[0].split("if (in_array($this->input->getCmd('view'),")[-1] for name in public)
assert "setHeader('Cache-Control', 'private, no-store', true)" in controller
for view in ("Dashboard", "Mycourses", "Mycertificates", "Preferences", "Lessonpreview", "Teacheredit", "Classroom", "Quiz"):
    code = read(f"View/{view}/HtmlView.php")
    assert "noindex, nofollow" in code, view
for view in ("Course", "Category", "Pathway"):
    code = read(f"View/{view}/HtmlView.php")
    assert "setMetaData('robots', 'noindex, nofollow')" in code, view
    assert "setHeader('X-Robots-Tag', 'noindex, nofollow', true)" in code, view
    assert "PublicMetadataHelper::apply(" in code, view
for view in ("Teacher", "Teachers", "Courses", "Categories", "Pathways"):
    code = read(f"View/{view}/HtmlView.php")
    assert "setHeader('X-Robots-Tag', 'noindex, nofollow'" not in code, view

for view in ("Course", "Pathway"):
    code = read(f"View/{view}/HtmlView.php")
    assert "PublicBreadcrumbHelper::addDetail(" in code, view
breadcrumb = read("Helper/PublicBreadcrumbHelper.php")
assert "getPathway()" in breadcrumb and "getActive()" in breadcrumb
assert "addItem($title)" in breadcrumb

helper = read("Helper/PublicMetadataHelper.php")
for required in ("'canonical'", "'og:url'", "'og:image'", "'twitter:image'", "Route::_($url, false)", "preg_match(", "str_contains($image, '..')"):
    assert required in helper, required
router = read("Service/Router.php")
for entity in ("Course", "Category", "Pathway"):
    assert f"get{entity}Segment" in router and f"get{entity}Id" in router
assert "new MenuRules($this)" in router and "new StandardRules($this)" in router
print("CAT-04 private indexing and public SEO source contract: OK")
