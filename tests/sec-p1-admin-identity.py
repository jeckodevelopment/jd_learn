#!/usr/bin/env python3
"""SEC-P1: keep privileged certificate and mail-guide entry points on fresh Joomla ACL.

Structural regression only; runtime authorization denial is exercised in
tests/joomla-smoke.php and must not be confused with full E2E acceptance.
"""
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
service = (ROOT / "component/administrator/src/Application/CertificateService.php").read_text()
view = (ROOT / "component/administrator/src/View/Mailvariables/HtmlView.php").read_text()
expected_admin = {
    "adminUnissuedCompletions",
    "adminRecoverHistorical",
    "adminRecoverMissing",
    "adminList",
    "adminDetail",
    "adminDownload",
    "adminRegenerate",
}
# Ensure new privileged public methods cannot bypass the shared fresh-ACL boundary.
public = re.findall(r"public function (\w+)\s*\([^)]*\)\s*:\s*[^\n{]+\{", service)
found_admin = {name for name in public if name.startswith("admin")}
assert found_admin == expected_admin, ("Unreviewed certificate admin operation", found_admin ^ expected_admin)
for name in sorted(expected_admin):
    match = re.search(r"public function " + name + r"\([^)]*\)\s*:\s*[^\n{]+\{", service)
    assert match
    initial = service[match.end():match.end() + 180]
    assert "$this->assertCertificateAdministrator($identity);" in initial, (name, "missing ACL boundary")
guard = service[service.index("private function assertCertificateAdministrator"): ]
for needle in ("new User($userId)", "$actor->clearAccessRights()", "$actor->block",
               "$actor->authorise('core.manage', 'com_jlms')",
               "$actor->authorise('jlms.certificates.manage', 'com_jlms')"):
    assert needle in guard, ("Fresh certificate ACL regression", needle)
assert "CourseEditorAccess::fresh($app->getIdentity())" in view, "Mail variables guide must refresh Joomla ACL"
assert "$app->getIdentity()->authorise(" not in view, "Mail guide trusts stale identity"
print("SEC-P1: fresh ACL required on all seven certificate-admin operations and mail guide")
