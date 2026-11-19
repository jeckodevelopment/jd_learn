#!/usr/bin/env python3
"""FE-05: private learner overview; full history remains on My Courses."""
from pathlib import Path

root = Path(__file__).resolve().parents[1]
read = lambda name: (root / name).read_text(encoding="utf-8")
service = read("component/administrator/src/Application/DashboardService.php")
view = read("component/site/src/View/Dashboard/HtmlView.php")
page = read("component/site/tmpl/dashboard/default.php")
courses = read("component/site/tmpl/mycourses/default.php")
http = read("tests/dashboard-http.py")
css = read("component/media/css/site.css")
assert "public function summary(User $identity, string $uiLanguage): array" in service
assert "$base = $this->query($identity);" in service
assert "COUNT(*) AS history_total" in service and "completed_total" in service and "active_total" in service
assert "cards($identity, $uiLanguage, $recent)" in service and "['access'] === 'available'" in service
assert "setQuery($recentQuery, $offset, 50)" in service
assert "$offset += count($recent)" in service
assert "while ($latest === null && count($recent) === 50)" in service
assert "getDashboardService()" in view and "->summary($app->getIdentity()" in view
assert "getCertificateService()->listForStudent($app->getIdentity())" in view
assert "COM_JLMS_MY_TRAINING" in view and "noindex, nofollow" in view
assert "jlms-dashboard-summary" in page and "jlms-dashboard-latest" in page
for target in ("view=mycourses", "view=mycertificates", "view=preferences", "view=courses"):
    assert target in page, target
assert "task=enrollment.resume&id=" in page
assert "jlms-dashboard-filters" not in page
assert "require __DIR__ . '/_courses.php'" not in page
assert "require __DIR__ . '/_certificates.php'" not in page
assert "require __DIR__ . '/_preferences.php'" not in page
assert "getPagesLinks()" not in page
assert "require __DIR__ . '/../dashboard/_courses.php'" in courses
assert "overview='/index.php?option=com_jlms&view=dashboard&lang=en'" in http
assert "dashboard='/index.php?option=com_jlms&view=mycourses&lang=en'" in http
assert "jlms-dashboard-summary" in http
assert "#jlms-dashboard .jlms-summary-stats" in css
for lang in ("it-IT", "en-GB"):
    ini = read(f"component/site/language/{lang}/com_jlms.ini")
    sys = read(f"component/site/language/{lang}/com_jlms.sys.ini")
    for key in ("COM_JLMS_MY_TRAINING", "COM_JLMS_MY_TRAINING_INTRO",
                "COM_JLMS_MY_TRAINING_CONTINUE", "COM_JLMS_MY_TRAINING_EMPTY",
                "COM_JLMS_MY_CERTIFICATES_COUNT"):
        assert ini.count(key + "=") == 1, (lang, key)
    assert sys.count("COM_JLMS_MENU_DASHBOARD_TITLE=") == 1
print("FE-05 private learner overview, summary and dedicated views: OK")
