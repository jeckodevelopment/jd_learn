#!/usr/bin/env python3
"""FE-05 private preferences and unchanged double-opt-in consent contract."""
from pathlib import Path
from xml.etree import ElementTree as ET

site = Path(__file__).resolve().parents[1] / "component/site"
read = lambda path: (site / path).read_text(encoding="utf-8")
model = read("src/Model/PreferencesModel.php")
view = read("src/View/Preferences/HtmlView.php")
page = read("tmpl/preferences/default.php")
shared = read("tmpl/dashboard/_preferences.php")
dashboard = read("tmpl/dashboard/default.php")
controller = read("src/Controller/DisplayController.php")
consent = read("src/Controller/MailconsentController.php")
router = read("src/Service/Router.php")
assert "getCourseAnnouncementConsent()->state((int) $identity->id)" in model
assert "if (!$identity || $identity->guest" in model and "COM_JLMS_LOGIN_REQUIRED" in model
assert "get('AnnouncementConsent')" in view and "noindex, nofollow" in view
assert "useStyle('com_jlms.site')" in view
assert "dashboard/_preferences.php" in page and "_preferences.php" in dashboard
assert "view=preferences" in dashboard and "view=mycourses" in page
assert "mailconsent.request" in shared and "mailconsent.revoke" in shared
assert "form.token" in shared and "name=\\"consent\\"" in shared
assert "=== 'confirmed'" in shared and "=== 'pending'" in shared
assert "RouterViewConfiguration('preferences')" in router
assert "'preferences', 'lessonpreview'" in controller and "'preferences', 'classroom'" in controller
assert "private, no-store" in controller and "noindex, nofollow" in controller
assert consent.count("view=preferences") == 4
assert "checkToken('post', false)" in consent and "getIdentity()" in consent
assert ET.parse(site / "tmpl/preferences/default.xml").getroot().find("layout") is not None
for lang in ("en-GB", "it-IT"):
    strings = read(f"language/{lang}/com_jlms.ini")
    system = read(f"language/{lang}/com_jlms.sys.ini")
    for key in ("COM_JLMS_MENU_PREFERENCES_TITLE", "COM_JLMS_MENU_PREFERENCES_DESC"):
        assert strings.count(key + "=") == 1 and system.count(key + "=") == 1
    assert strings.count("COM_JLMS_PREFERENCES=") == 1
print("FE-05 preferences privacy, consent and menu contracts: OK")
