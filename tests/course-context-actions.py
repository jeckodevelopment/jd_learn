#!/usr/bin/env python3
"""CAT-02/SCR-03: structural gate for safe course call-to-action precedence."""
from pathlib import Path

root = Path(__file__).resolve().parents[1]
view = (root / "component/site/src/View/Course/HtmlView.php").read_text()
model = (root / "component/site/src/Model/CourseModel.php").read_text()
template = (root / "component/site/tmpl/course/default.php").read_text()

assert "->catalogStates($actor," in view
assert "$this->enrollmentState = $states[$courseId] ?? null;" in view
assert "$this->enrollmentState === null" in view
assert "hasPendingApproval($courseId, (int) $actor->id)" in view
assert "request.user_id = :pendingOwner" in model
assert "edition.course_id = :pendingCourse" in model
assert "edition.course_version_id = course.current_version_id" in model
assert "request.status = 'pending'" in model
assert "$this->isGuest" in template
assert "$this->enrollmentState !== null" in template
assert "$this->pendingApproval" in template
assert "task=enrollment.resume&id=" in template
assert "enrollment.start" in template and "enrollment.request" in template
assert template.index("$this->enrollmentState !== null") < template.index("$this->pendingApproval") < template.index("$course->enrollment_mode === 'auto'")
assert "COM_JLMS_COURSE_EXISTING_UNAVAILABLE" in template
assert "COM_JLMS_COURSE_ENROLL_START" in template
assert "COM_JLMS_COURSE_BEGIN" in template
for lang in ("it-IT", "en-GB"):
    translations = (root / "component/site/language" / lang / "com_jlms.ini").read_text()
    for key in ("COM_JLMS_COURSE_BEGIN", "COM_JLMS_COURSE_ENROLL_START",
                "COM_JLMS_COURSE_EXISTING_UNAVAILABLE", "COM_JLMS_COURSE_APPROVAL_PENDING"):
        assert key + "=" in translations, (lang, key)
print("Course contextual action contracts OK")
