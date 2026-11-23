#!/usr/bin/env python3
"""FE-06: optional per-menu Joomla personal navigation, with scoped accessible markup."""
from pathlib import Path
import re
from xml.etree import ElementTree
root = Path(__file__).resolve().parents[1]
site = root / "component/site"
nav = (site / "tmpl/dashboard/_personal_navigation.php").read_text()
assert "$app->getMenu()->getActive()" in nav
assert "($menuQuery['option'] ?? '') === 'com_jlms'" in nav
assert "($menuQuery['view'] ?? '') === $currentView" in nav
assert "getParams()->get('show_personal_navigation', '0')" in nav
assert "=== '1'" in nav
assert "aria-label=" in nav and 'aria-current="page"' in nav
assert "Route::_('index.php?option=com_jlms&view='" in nav
assert "Route::_('index.php?option=com_jlms&view='.$view)" in re.sub(r"\s+", "", nav)
assert "if ($view === $currentView)" in nav and "else" in nav
assert nav.count("=> 'COM_JLMS_") == 4
for view in ("dashboard", "mycourses", "mycertificates", "preferences"):
    page = (site / "tmpl" / view / "default.php").read_text()
    assert f"$currentView = '{view}'" in page
    assert "dashboard/_personal_navigation.php" in page
    metadata = ElementTree.parse(site / "tmpl" / view / "default.xml").getroot()
    field = metadata.find("./fields/fieldset/field[@name='show_personal_navigation']")
    assert field is not None and field.attrib.get("default") == "0"
    options = {(o.attrib["value"], o.text) for o in field.findall("option")}
    assert ("0", "JNO") in options and ("1", "JYES") in options
for language in ("it-IT", "en-GB"):
    ini = (site / "language" / language / "com_jlms.ini").read_text()
    for key in ("COM_JLMS_SHOW_PERSONAL_NAV", "COM_JLMS_SHOW_PERSONAL_NAV_DESC",
                "COM_JLMS_PERSONAL_NAV_LABEL"):
        assert ini.count(key + "=") == 1
css = (root / "component/media/css/site.css").read_text()
assert ".jlms-dashboard .jlms-personal-navigation__list" in css
assert ".jlms-personal-navigation__link:focus-visible" in css
print("FE-06 optional per-menu accessible personal navigation contracts: OK")
