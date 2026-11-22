#!/usr/bin/env python3
"""FE-05: static contract for the private certificates page."""
from pathlib import Path

root = Path(__file__).resolve().parents[1]
read = lambda path: (root / path).read_text(encoding="utf-8")
controller = read("component/site/src/Controller/DisplayController.php")
router = read("component/site/src/Service/Router.php")
model = read("component/site/src/Model/MycertificatesModel.php")
view = read("component/site/src/View/Mycertificates/HtmlView.php")
page = read("component/site/tmpl/mycertificates/default.php")
dashboard = read("component/site/tmpl/dashboard/default.php")
partial = read("component/site/tmpl/dashboard/_certificates.php")
menu = read("component/site/tmpl/mycertificates/default.xml")
assert "'mycertificates'" in controller
assert "['dashboard', 'mycourses', 'mycertificates', 'preferences', 'lessonpreview', 'teacheredit', 'classroom', 'quiz']" in controller
assert "RouterViewConfiguration('mycertificates')" in router
assert "getCertificateService()->listForStudent($app->getIdentity())" in model
assert "get('Items')" in view and "noindex, nofollow" in view
assert "useStyle('com_jlms.site')" in view
assert "dashboard/_certificates.php" in page
assert "view=mycertificates" in dashboard and "certificateCount" in dashboard
assert "certificate.download&enrollment_id=" in partial
assert "$this->escape($certificate['serial'])" in partial
assert "COM_JLMS_MENU_MYCERTIFICATES_TITLE" in menu
for lang in ("en-GB", "it-IT"):
    ini = read(f"component/site/language/{lang}/com_jlms.ini")
    sysini = read(f"component/site/language/{lang}/com_jlms.sys.ini")
    for key in ("COM_JLMS_MENU_MYCERTIFICATES_TITLE", "COM_JLMS_MENU_MYCERTIFICATES_DESC"):
        assert key + "=" in ini and key + "=" in sysini
    assert "COM_JLMS_MY_CERTIFICATES_EMPTY=" in ini
print("FE-05 private certificates frontend contract OK")
