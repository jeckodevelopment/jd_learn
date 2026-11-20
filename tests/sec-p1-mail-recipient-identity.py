#!/usr/bin/env python3
"""SEC-P1: enumerate every outbox producer and fail closed on missing Joomla recipient IDs.

The inventory and syntax guard are review gates, not an SMTP/ACL penetration test.
"""
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "component/administrator/src/Application"
EXPECTED = {
    "CertificateIssuer.php": 1,
    "CourseCommunicationService.php": 2,
    "CourseExpiryReminderService.php": 1,
    "CourseVersionChangeNotice.php": 1,
    "EnrollmentApprovalService.php": 1,
    "EnrollmentManagerNotice.php": 1,
    "EnrollmentStateService.php": 1,
    "LearningService.php": 3,
    "LessonReleaseNotice.php": 1,
    "ManualEnrollmentService.php": 1,
    "QuizAttemptService.php": 1,
    "TeacherAssignmentNotice.php": 1,
    "VideoProgressService.php": 1,
}
# Course announcements use enqueueAnnouncement(), which has a mandatory user ID
# in its signature and an independent consent-revision + account recheck.
ANNOUNCEMENTS = {"CourseAnnouncementService.php": 1}


def arguments(source: str, start: int) -> list[str]:
    """Parse a PHP invocation without splitting on arrays, nested calls, or strings."""
    depth = []
    quote = None
    escape = False
    chunks = []
    begin = start
    for i in range(start, len(source)):
        ch = source[i]
        if quote is not None:
            if escape:
                escape = False
            elif ch == "\\":
                escape = True
            elif ch == quote:
                quote = None
            continue
        if ch in "'\"":
            quote = ch
        elif ch in "([{":
            depth.append(ch)
        elif ch in ")]}":
            if not depth:
                if ch != ")":
                    raise AssertionError("Unexpected PHP delimiter")
                chunks.append(source[begin:i].strip())
                return chunks
            opener = depth.pop()
            assert (opener, ch) in {("(", ")"), ("[", "]"), ("{", "}")}, "Unbalanced call"
        elif ch == "," and not depth:
            chunks.append(source[begin:i].strip())
            begin = i + 1
    raise AssertionError("Unterminated PHP enqueue call")


def calls(source: str, name: str) -> list[list[str]]:
    # Match method calls only; do not count method declarations and documentation.
    return [
        arguments(source, match.end())
        for match in re.finditer(r"->" + re.escape(name) + r"\s*\(", source)
    ]


for filename, count in EXPECTED.items():
    source = (APP / filename).read_text(encoding="utf-8")
    invocations = calls(source, "enqueue")
    assert len(invocations) == count, (filename, "mail queue call count changed", len(invocations), count)
    for args in invocations:
        assert len(args) == 8, (filename, "outbox call must supply recipient_user_id", len(args))
        assert re.fullmatch(r"\(int\)\s*\$(?:actor|learner|recipient|row|e|userId)[A-Za-z0-9_>\-]*", args[-1]) or re.fullmatch(
            r"\$userId", args[-1]
        ), (filename, "recipient ID must be a reviewed Joomla identity expression", args[-1])

for filename, count in ANNOUNCEMENTS.items():
    source = (APP / filename).read_text(encoding="utf-8")
    assert len(calls(source, "enqueueAnnouncement")) == count, (filename, "announcement inventory changed")

found = {
    path.name for path in APP.glob("*.php")
    if "->enqueue(" in path.read_text(encoding="utf-8")
}
assert found == set(EXPECTED), ("New or removed mail producer needs SEC-P1 review", sorted(found ^ set(EXPECTED)))

mail = (ROOT / "component/administrator/src/Infrastructure/MailQueue.php").read_text(encoding="utf-8")
assert "($recipientUserId ?? 0) < 1" in mail, "New unbound outbox recipients must be rejected"
assert "if (!self::currentRecipient($this->db, $row))" in mail, "All mail templates require account recheck"
assert "recipient_email = ''" in mail and "template_data = '{}'" in mail, "Rejected outbox PII redaction removed"
print("SEC-P1 outbox inventory: all 13 email producers bind Joomla recipient ID; dispatch rejects stale/unbound recipients")
