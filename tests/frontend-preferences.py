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
assert "dashboard/_preferences.php" in page and "view=preferences" in dashboard
assert "view=preferences" in dashboard and "view=mycourses" in page
assert "mailconsent.request" in shared and "mailconsent.revoke" in shared
assert "form.token" in shared and 'name="consent"' in shared
assert "=== 'confirmed'" in shared and "=== 'pending'" in shared
assert "RouterViewConfiguration('preferences')" in router
if "$privateViews = [" in controller:
    private_routes = controller.split("$privateViews = [", 1)[1].split("];", 1)[0]
    assert "in_array($this->input->getCmd('view'), $privateViews, true)" in controller
else:
    private_routes = controller.split("if (in_array($this->input->getCmd('view'),", 1)[-1]
for name in ("preferences", "lessonpreview", "classroom"):
    assert "'" + name + "'" in private_routes
assert "private, no-store" in controller and "noindex, nofollow" in controller
assert consent.count("view=preferences") == 4
assert "checkToken('post', false)" in consent and "getIdentity()" in consent
# SEC-P1: redirecting a token-bearing confirmation GET must not leak the
# confirmation URL as a Referer, and must not be indexed or cached.
confirmation = consent.split("public function confirm(): void", 1)[1].split("public function revokeLink(): void", 1)[0]
assert "getString('token', '')" in confirmation
assert "->confirm($token)" in confirmation
for header, value in (("Referrer-Policy", "no-referrer"), ("X-Robots-Tag", "noindex, nofollow"), ("Cache-Control", "no-store")):
    assert f"setHeader('{header}', '{value}', true)" in confirmation, (header, value)
assert confirmation.index("setHeader('Referrer-Policy'") < confirmation.index("->confirm($token)")
assert ET.parse(site / "tmpl/preferences/default.xml").getroot().find("layout") is not None
for lang in ("en-GB", "it-IT"):
    strings = read(f"language/{lang}/com_jlms.ini")
    system = read(f"language/{lang}/com_jlms.sys.ini")
    for key in ("COM_JLMS_MENU_PREFERENCES_TITLE", "COM_JLMS_MENU_PREFERENCES_DESC"):
        assert strings.count(key + "=") == 1 and system.count(key + "=") == 1
    assert strings.count("COM_JLMS_PREFERENCES=") == 1
print("FE-05 preferences privacy, consent and menu contracts: OK")
