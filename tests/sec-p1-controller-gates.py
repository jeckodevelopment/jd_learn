#!/usr/bin/env python3
"""SEC-P1 structural inventory of Joomla controller dispatch and method/CSRF guards.

This is a regression gate, NOT an ACL/IDOR or penetration test. Any new controller
must be classified and reviewed before the package can pass its preflight.
"""
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SITE = ROOT / "component/site/src/Controller"
ADMIN = ROOT / "component/administrator/src/Controller"

# Each writable controller must use an explicit method gate and Joomla CSRF
# protection (possibly via a shared helper). ACL checks remain service-specific.
WRITE_SITE = {
    "AudioController.php", "VideoController.php", "QuizController.php",
    "EnrollmentController.php", "LessonController.php", "MailconsentController.php",
}
WRITE_ADMIN = {
    "QuizController.php", "CourseController.php", "LessonController.php",
    "CoursesController.php", "TeacherController.php", "PathwayController.php",
    "QuiztimeController.php", "MaterialController.php", "VideofileController.php",
    "MailqueueController.php", "CurriculumController.php", "QuizextendController.php",
    "QuizreviewController.php", "CertificateController.php",
    "QuestionbankController.php", "CoursemessageController.php",
    "CoursepreviewController.php", "EnrollmentstateController.php",
    "CourseoperationsController.php", "ManualenrollmentController.php",
    "EnrollmentapprovalController.php", "CourseannouncementController.php",
    "EnrollmentmigrationController.php", "CategoryreassignmentController.php",
}
READ_SITE = {
    "DisplayController.php", "AttachmentController.php",
    "MediaController.php", "CertificateController.php",
}
READ_ADMIN = {"DisplayController.php"}
DELEGATE_SITE = {"TeacherController.php"}  # Joomla FormController + allowEdit
DELEGATE_ADMIN = {"ModuleController.php"}   # inherits CurriculumController.perform

def check_surface(folder: Path, writable: set[str], readonly: set[str],
                  delegate: set[str]) -> int:
    actual = {file.name for file in folder.glob("*Controller.php")}
    expected = writable | readonly | delegate
    assert actual == expected, (
        str(folder), "controller inventory drift",
        "unclassified: " + ", ".join(sorted(actual - expected)),
        "deleted: " + ", ".join(sorted(expected - actual)),
    )
    for name in sorted(writable):
        code = (folder / name).read_text(encoding="utf-8")
        assert "checkToken(" in code, (name, "missing explicit CSRF guard")
        assert "getMethod()" in code, (name, "missing HTTP method guard")
        assert "POST" in code, (name, "no explicit POST restriction")
    return len(actual)

site_count = check_surface(SITE, WRITE_SITE, READ_SITE, DELEGATE_SITE)
admin_count = check_surface(ADMIN, WRITE_ADMIN, READ_ADMIN, DELEGATE_ADMIN)

# Shared mutation and Joomla FormController inheritance must stay explicit.
module = (ADMIN / "ModuleController.php").read_text(encoding="utf-8")
curriculum = (ADMIN / "CurriculumController.php").read_text(encoding="utf-8")
assert "extends CurriculumController" in module and "$this->perform(" in module
assert "checkToken(" in curriculum and "getMethod()" in curriculum
teacher = (SITE / "TeacherController.php").read_text(encoding="utf-8")
assert "FormController" in teacher and "allowEdit(" in teacher and "allowAdd(" in teacher
assert "return false;" in teacher, "frontend teacher creation must remain disabled"

# Media and document delivery are read-only HTTP endpoints. A new write
# route must be deliberately moved to the reviewed WRITE set.
for name in ("AttachmentController.php", "MediaController.php", "CertificateController.php"):
    code = (SITE / name).read_text(encoding="utf-8")
    assert "GET" in code and "HEAD" in code and "getMethod()" in code, name

# Confirm that the token-bearing double-opt-in redirect retains privacy headers.
consent = (SITE / "MailconsentController.php").read_text(encoding="utf-8")
confirm = consent.split("public function confirm(): void", 1)[1].split("public function revokeLink(): void", 1)[0]
assert "setHeader('Referrer-Policy', 'no-referrer', true)" in confirm
assert "setHeader('X-Robots-Tag', 'noindex, nofollow', true)" in confirm

print(f"SEC-P1 controller inventory: {site_count} site, {admin_count} administrator; structural guards PASS")
