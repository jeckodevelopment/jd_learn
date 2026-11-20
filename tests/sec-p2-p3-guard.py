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
