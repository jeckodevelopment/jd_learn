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
