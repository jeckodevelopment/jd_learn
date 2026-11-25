#!/usr/bin/env python3
"""FE-05 first tranche: autonomous course view with shared ACL-aware rendering."""
from pathlib import Path
from xml.etree import ElementTree
ROOT = Path(__file__).resolve().parents[1]
SITE = ROOT / "component/site"
MODEL = (SITE / "src/Model/MycoursesModel.php").read_text()
BASE = (SITE / "src/Model/DashboardModel.php").read_text()
VIEW = (SITE / "src/View/Mycourses/HtmlView.php").read_text()
LAYOUT = (SITE / "tmpl/mycourses/default.php").read_text()
SHARED = (SITE / "tmpl/dashboard/_courses.php").read_text()
DASHBOARD = (SITE / "tmpl/dashboard/default.php").read_text()
ROUTER = (SITE / "src/Service/Router.php").read_text()
CONTROLLER = (SITE / "src/Controller/DisplayController.php").read_text()
XML = ElementTree.parse(SITE / "tmpl/mycourses/default.xml").getroot()
assert XML.find("layout") is not None
assert "class MycoursesModel extends DashboardModel" in MODEL
assert "class DashboardModel extends ListModel" in BASE
assert "getDashboardService()->query" in BASE and "getDashboardService()->cards" in BASE
assert "freshActor(" in BASE and "getState('filter.progress'" in BASE
assert "get('Items')" in VIEW and "get('Pagination')" in VIEW
assert "getProgressForm()" in VIEW
assert "noindex, nofollow" in VIEW
assert "com_jlms.site" in VIEW
assert "require __DIR__ . '/../dashboard/_courses.php'" in LAYOUT
assert "view=mycourses" in DASHBOARD and "jlms-dashboard-summary" in DASHBOARD
assert "jlms-my-course-resume-" in SHARED and "jlms-my-course-progress-" in SHARED
assert "data-enrollment-id=" in SHARED
assert "task=enrollment.resume" in SHARED
assert "if ($item['access'] === 'available')" in SHARED
assert "getPagesLinks()" in LAYOUT
assert 'name="view" value="mycourses"' in LAYOUT
assert "view=mycourses" in LAYOUT and "view=mycourses" in DASHBOARD
assert "RouterViewConfiguration('mycourses')" in ROUTER
assert "$privateViews = [" in CONTROLLER
private_routes = CONTROLLER.split("$privateViews = [", 1)[1].split("];", 1)[0]
assert all("'" + name + "'" in private_routes for name in (
    "dashboard", "mycourses", "mycertificates", "preferences", "lessonpreview"
))
assert "in_array($this->input->getCmd('view'), $privateViews, true)" in CONTROLLER
manifest = (ROOT / "component/jlms.xml").read_text()
assert '<folder>tmpl</folder>' in manifest and '<folder>src</folder>' in manifest
for lang in ("it-IT", "en-GB"):
    ini=(SITE / "language" / lang / "com_jlms.ini").read_text()
    assert ini.count("COM_JLMS_MENU_MYCOURSES_TITLE=") == 1
    assert ini.count("COM_JLMS_MENU_MYCOURSES_DESC=") == 1
print("FE-05 standalone My Courses, shared cards, ACL, routing, menu and i18n: OK")
