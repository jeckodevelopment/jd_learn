#!/usr/bin/env python3
"""DOC-07: safe dashboard operational guidance is localized and ACL scoped.

This is a structural guard, not a claim that real Joomla users or third-party
provider credentials were manually tested.
"""
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
view = (ROOT / "component/administrator/src/View/Dashboard/HtmlView.php").read_text()
page = (ROOT / "component/administrator/tmpl/dashboard/default.php").read_text()
operations = ("STORAGE", "MAIL", "CERTIFICATES", "COURSE", "SCHEDULER")
assert "CourseEditorAccess::fresh(Factory::getApplication()->getIdentity())" in view
assert "$this->safeOperations = [];" in view
assert "if ($actor->authorise('core.manage', 'com_jlms'))" in view
assert "if ($actor->authorise('jlms.audit.view', 'com_jlms'))" in view
assert "if ($actor->authorise('jlms.certificates.manage', 'com_jlms'))" in view
assert "if ($actor->authorise('core.manage', 'com_scheduler'))" in view
assert 'id="jlms-admin-safe-operations"' in page
assert 'data-jlms-safe-operation=' in page
assert 'foreach ($this->safeOperations as $operation)' in page
assert "Text::_('COM_JLMS_SAFE_OPERATIONS_" in page
assert 'method="post"' not in page[page.index('id="jlms-admin-safe-operations"'):]
assert 'task=' not in page[page.index('id="jlms-admin-safe-operations"'):]
for locale in ("it-IT", "en-GB"):
    lang = (ROOT / "component/administrator/language" / locale / "com_jlms.ini").read_text()
    for key in ("TITLE", "INTRO", "NOTE"):
        assert len(re.findall(r"^COM_JLMS_SAFE_OPERATIONS_" + key + "=", lang, re.M)) == 1, (locale, key)
    for op in operations:
        for suffix in ("TITLE", "STEPS"):
            key = f"COM_JLMS_SAFE_OPERATIONS_{op}_{suffix}"
            assert len(re.findall(r"^" + key + "=", lang, re.M)) == 1, (locale, key)
        line = next(x for x in lang.splitlines() if x.startswith(f"COM_JLMS_SAFE_OPERATIONS_{op}_STEPS="))
        assert all(f"{n}." in line for n in ("1", "2", "3")), (locale, op)
        assert len(line) > 140, (locale, op, "actionable procedures required")
# Existing error-specific scheduler and video guidance stays available.
assert "SchedulerHealth::guidanceKey(" in page
assert "DashboardProviderStatus::guidanceKey(" in page
print("DOC-07 safe procedures: five ACL-scoped operations, IT/EN and non-mutating dashboard OK")
