#!/usr/bin/env python3
"""P2/P3 regression and non-destructive audit fixture, without Joomla credentials."""
from pathlib import Path
import subprocess
import tempfile
import sys

root = Path(__file__).resolve().parents[1]
privacy = (root / "plugins/privacy/jdlearn/src/Extension/Jdlearn.php").read_text()
assert "private function hasRemovalDependencies(int $userId)" in privacy
assert privacy.count("$this->hasRemovalDependencies((int) $user->id)") == 2
remove = privacy.split("public function onPrivacyRemoveData(", 1)[1]
assert remove.index("hasRemovalDependencies") < remove.index("$db->transactionStart()")
assert "throw new \\RuntimeException(" in remove

# SEC-P3: privacy erasure must clear optional identity references across
# authored records and teacher/subject profiles without deleting coursework.
for table in (
    "#__jlms_courses", "#__jlms_course_versions", "#__jlms_modules",
    "#__jlms_lessons", "#__jlms_editions", "#__jlms_teachers",
    "#__jlms_pathways", "#__jlms_assets", "#__jlms_attachments",
    "#__jlms_quizzes", "#__jlms_questions", "#__jlms_question_revisions",
    "#__jlms_enrollments", "#__jlms_enrollment_requests",
    "#__jlms_certificate_templates",
):
    assert table in remove, table
for column in ("created_by", "modified_by", "reconciliation_by"):
    assert "quoteName('" + column + "')" in remove, column
for related in ("#__jlms_subjects", "#__jlms_assessment_revisions"):
    assert related in remove, related
assert "->set($db->quoteName('user_id') . ' = NULL')" in remove
dependency_guard = privacy.split("private function hasRemovalDependencies(", 1)[1]
for immutable in ("#__jlms_certificate_audit", "#__jlms_version_equivalences", "#__jlms_course_announcement_campaigns"):
    assert immutable in dependency_guard, immutable
assert dependency_guard.index("foreach ([") < dependency_guard.index("return $documents > 0"), "Immutable actor guard must precede final decision"

export = privacy.split("public function onPrivacyExportRequest(", 1)[1].split("public function onPrivacyCanRemoveData(", 1)[0]
mail = export.split("$mailQuery = $db->createQuery()", 1)[1].split("foreach ($db->setQuery($mailQuery)", 1)[0]
for expected in ("template_id", "state", "created_at", "sent_at", "recipient_user_id", ":userId"):
    assert expected in mail, expected
for forbidden in ("recipient_email", "recipient_name", "template_data", "last_error", "token_hash", "reconciliation_note"):
    assert forbidden not in mail, forbidden
redaction = remove.split("->update($db->quoteName('#__jlms_mail_queue'))", 1)[1].split("->execute();", 1)[0]
for field in ("recipient_user_id", "recipient_email", "recipient_name", "recipient_consent_at", "template_data", "reconciliation_note", "reconciliation_by", "last_error"):
    assert "quoteName('" + field + "')" in redaction, field
assert "NOT IN ('pending','retry','sending')" in redaction
for locale in ("it-IT", "en-GB"):
    language = (root / f"plugins/privacy/jdlearn/language/{locale}/plg_privacy_jdlearn.ini").read_text()
    assert language.count("PLG_PRIVACY_JDLEARN_DOMAIN_MAIL_DELIVERIES=") == 1
# PRIV-C2: current consent projection must be narrowly scoped, read-only,
# explicitly authorized and free from token/email/SMTP fields.
audit = (root / "component/administrator/src/Application/ConsentAuditService.php").read_text()
view = (root / "component/administrator/src/View/Mailqueue/HtmlView.php").read_text()
layout = (root / "component/administrator/tmpl/mailqueue/default.php").read_text()
assert "CourseEditorAccess::fresh($identity)" in audit
for guard in ("authorise('core.manage', 'com_jlms')", "authorise('jlms.audit.view', 'com_jlms')"):
    assert guard in audit, guard
for field in ("user_id", "state", "origin", "policy_version", "requested_at", "confirmed_at", "revoked_at"):
    assert "'" + field + "'" in audit, field
for sensitive in ("token_hash", "recipient_email", "template_data", "last_error", "recipient_name"):
    assert sensitive not in audit, sensitive
assert "new ConsentAuditService(" in view
assert "id=\"jlms-consent-audit\"" in layout
assert "id=\"jlms-consent-audit-table\"" in layout
consent_block = layout.split('<details id="jlms-consent-audit"', 1)[1].split('</details>', 1)[0]
assert 'method="get"' in consent_block and 'method="post"' not in consent_block
for locale in ("it-IT", "en-GB"):
    language = (root / f"component/administrator/language/{locale}/com_jlms.ini").read_text()
    for key in ("TITLE", "HELP", "USER", "STATE", "ORIGIN", "VERSION", "REQUESTED", "CONFIRMED", "REVOKED", "TOTAL", "EMPTY"):
        assert language.count("COM_JLMS_PRIVACY_CONSENTS_" + key + "=") == 1, (locale, key)

audit_script = root / "tools/audit_legacy_video_storage.py"
with tempfile.TemporaryDirectory() as temp:
    web = Path(temp)
    managed = web / "media/com_jlms/private"
    managed.mkdir(parents=True)
    def check(expected):
        result = subprocess.run([sys.executable, str(audit_script), str(web)],
                                capture_output=True, text=True)
        assert result.returncode == expected, (result.returncode, result.stdout, result.stderr)
        assert str(web) not in result.stdout
    check(0)
    (managed / "old.blob").write_bytes(b"test non-personal fixture")
    check(2)
    assert (managed / "old.blob").exists(), "audit must not delete files"
    (managed / "old.blob").unlink()
    (managed / "link").symlink_to(web, target_is_directory=True)
    check(2)
print("SEC-P2/SEC-P3 structural and read-only fixture OK")
