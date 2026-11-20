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

# The public method set is an explicit reviewed snapshot of the shipped
# controller surface. Joomla may route an otherwise harmless new public helper
# unless it is deliberately kept behind a dispatch allowlist.
# Keep this inventory synchronized with the technical SEC-P1 report.
PUBLIC_METHODS = {
    "site": {
        "AudioController.php": {"execute", "save"},
        "VideoController.php": {"execute", "playback", "renew", "save", "close"},
        "QuizController.php": {"execute", "start", "save", "submit", "state"},
        "EnrollmentController.php": {"execute", "request", "start", "resume"},
        "LessonController.php": {"complete", "begin"},
        "MailconsentController.php": {"execute", "request", "confirm", "revokeLink", "revoke"},
        "DisplayController.php": {"display"},
        "AttachmentController.php": {"execute", "publicpreview", "publiclessonpreview", "preview", "audio", "download", "inline"},
        "MediaController.php": {"execute", "stream"},
        "CertificateController.php": {"execute", "download"},
        "TeacherController.php": {"execute", "getModel"},
    },
    "administrator": {
        "QuizController.php": {"execute", "save", "delete"},
        "CourseController.php": {"execute", "trash", "restore", "purge"},
        "LessonController.php": {"execute", "metadata", "move", "duplicate", "save", "delete", "reorder"},
        "CoursesController.php": {"execute", "programme"},
        "TeacherController.php": {"execute", "trash", "restore", "publish", "unpublish", "purge"},
        "PathwayController.php": {"execute", "trash", "restore", "archive", "publish", "unpublish", "purge"},
        "QuiztimeController.php": {"execute", "save"},
        "MaterialController.php": {"execute", "upload", "reuse", "save", "delete", "scan", "download", "preview"},
        "VideofileController.php": {"execute", "upload", "scan"},
        "MailqueueController.php": {"execute", "reconcile"},
        "CurriculumController.php": {"execute", "saveOrder", "saveTeachingRules", "restoreVersion", "duplicateCourse", "transition", "createVersion"},
        "QuizextendController.php": {"execute", "apply"},
        "QuizreviewController.php": {"execute", "cancel"},
        "CertificateController.php": {"execute", "recoverHistorical", "recoverMissing", "regenerate", "download"},
        "QuestionbankController.php": {"execute", "save", "checkout", "checkin", "duplicate", "delete", "trash", "restore"},
        "CoursemessageController.php": {"execute", "preview", "test", "queue"},
        "CoursepreviewController.php": {"execute", "playback"},
        "EnrollmentstateController.php": {"execute", "apply"},
        "CourseoperationsController.php": {"execute", "apply"},
        "ManualenrollmentController.php": {"execute", "apply"},
        "EnrollmentapprovalController.php": {"execute", "decide"},
        "CourseannouncementController.php": {"execute", "notify"},
        "EnrollmentmigrationController.php": {"execute", "apply"},
        "CategoryreassignmentController.php": {"execute", "reassign"},
        "DisplayController.php": {"display"},
        "ModuleController.php": {"execute", "duplicate", "save", "delete", "reorder"},
    },
}

# Explicit task names are separate from PHP public methods: Joomla FormController
# exposes inherited actions and CurriculumController is a deliberate shared
# base. New task names require review even if no new PHP method is introduced.
DISPATCH_TASKS = {
    "site": {
        "AudioController.php": {"save"},
        "VideoController.php": {"playback", "renew", "save", "close"},
        "QuizController.php": {"start", "save", "submit", "state"},
        "EnrollmentController.php": {"start", "resume", "request", "complete", "begin"},
        "MailconsentController.php": {"request", "confirm", "revoke", "revokelink"},
        "AttachmentController.php": {"publicpreview", "publiclessonpreview", "preview", "audio", "download", "inline"},
        "MediaController.php": {"stream"},
        "CertificateController.php": {"download"},
        "TeacherController.php": {"edit", "save", "apply", "cancel"},
    },
    "administrator": {
        "QuizController.php": {"save", "delete"},
        "CourseController.php": {"add", "edit", "apply", "save", "cancel", "loadhistory", "programme", "programmerules", "trash", "restore", "purge"},
        "LessonController.php": {"metadata", "save", "delete", "reorder", "duplicate", "move"},
        "CoursesController.php": {"programme"},
        "TeacherController.php": {"add", "edit", "apply", "save", "cancel", "loadhistory", "trash", "restore", "publish", "unpublish", "purge"},
        "PathwayController.php": {"add", "edit", "apply", "save", "cancel", "trash", "restore", "archive", "publish", "unpublish", "purge"},
        "QuiztimeController.php": {"save"},
        "MaterialController.php": {"upload", "reuse", "save", "delete", "scan", "download", "preview"},
        "VideofileController.php": {"upload", "scan"},
        "MailqueueController.php": {"reconcile", "display"},
        "CurriculumController.php": {"createversion", "restoreversion", "saveteachingrules", "transition", "duplicatecourse", "saveorder"},
        "QuizextendController.php": {"apply"},
        "QuizreviewController.php": {"cancel"},
        "CertificateController.php": {"download", "regenerate", "recoverhistorical", "recovermissing"},
        "QuestionbankController.php": {"save", "checkout", "checkin", "duplicate", "delete", "trash", "restore"},
        "CoursemessageController.php": {"preview", "test", "queue"},
        "CoursepreviewController.php": {"playback"},
        "EnrollmentstateController.php": {"apply"},
        "CourseoperationsController.php": {"apply"},
        "ManualenrollmentController.php": {"apply"},
        "EnrollmentapprovalController.php": {"decide"},
        "CourseannouncementController.php": {"notify"},
        "EnrollmentmigrationController.php": {"apply"},
        "CategoryreassignmentController.php": {"reassign"},
        "ModuleController.php": {"save", "delete", "reorder", "duplicate"},
    },
}

def check_dispatch_inventory(folder: Path, expected: dict[str, set[str]], writable: set[str],
                             delegated: set[str]) -> None:
    import re
    assert {path.name for path in folder.glob("*Controller.php")} == expected.keys(), (
        folder, "controller method inventory drift"
    )
    for filename, approved_methods in sorted(expected.items()):
        code = (folder / filename).read_text(encoding="utf-8")
        actual = set(re.findall(r"\bpublic\s+function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(", code))
        assert actual == approved_methods, (
            folder, filename, "public method surface changed",
            sorted(actual - approved_methods), sorted(approved_methods - actual)
        )
        if filename not in writable | delegated:
            continue
        if folder == SITE and filename == "LessonController.php":
            assert "extends EnrollmentController" in code, "lesson inherits the reviewed enrollment task gate"
            continue
        opening = re.search(r"\bpublic\s+function\s+execute\s*\(\s*\$task\s*\)", code)
        assert opening is not None, (filename, "missing explicit dispatch guard")
        # Require a fail-closed gate before the framework dispatches the task.
        finish = code.find("return parent::execute($task)", opening.end())
        assert finish > opening.end(), (filename, "missing guarded Joomla dispatch")
        guard = code[opening.end():finish]
        assert "strtolower" in guard and "JERROR_LAYOUT_PAGE_NOT_FOUND" in guard, (
            filename, "missing dispatch allowlist and 404 rejection"
        )
        declared = set(re.findall(r"'([a-z][a-z0-9]*)'", guard))
        approved = DISPATCH_TASKS["site" if folder == SITE else "administrator"][filename]
        assert declared == approved, (
            filename, "routable Joomla tasks changed without security review",
            sorted(declared - approved), sorted(approved - declared)
        )

assert set(DISPATCH_TASKS["site"]) == (WRITE_SITE | DELEGATE_SITE) - {"LessonController.php"}
assert set(DISPATCH_TASKS["administrator"]) == WRITE_ADMIN | DELEGATE_ADMIN
# EnrollmentController separates its own tasks from those inherited by the
# LessonController; do not let lesson.start/lesson.resume become routable.
enrollment_dispatch = (SITE / "EnrollmentController.php").read_text(encoding="utf-8")
assert "static::class === self::class" in enrollment_dispatch
check_dispatch_inventory(SITE, PUBLIC_METHODS["site"], WRITE_SITE, DELEGATE_SITE)
check_dispatch_inventory(ADMIN, PUBLIC_METHODS["administrator"], WRITE_ADMIN, DELEGATE_ADMIN)
category_dispatch = (ADMIN / "CategoryreassignmentController.php").read_text(encoding="utf-8")
assert "strtolower((string) $task) !== 'reassign'" in category_dispatch
print("SEC-P1 fail-closed dispatch: public methods and task allowlists reviewed for all "
      f"{site_count + admin_count} controllers")


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
