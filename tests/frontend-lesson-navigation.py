#!/usr/bin/env python3
"""FE-04 manual lesson navigation: structural safety and i18n checks."""
from pathlib import Path
ROOT = Path(__file__).resolve().parents[1]
VIEW = (ROOT / "component/site/tmpl/classroom/default.php").read_text()
CSS = (ROOT / "component/media/css/site.css").read_text()
for fragment in (
    "$orderedLessons = array_values($item['outline']);",
    "$previousLesson = $orderedLessons[$position - 1] ?? null;",
    "$nextLesson = $orderedLessons[$position + 1] ?? null;",
    'id="jlms-lesson-navigation"',
    "COM_JLMS_PREVIOUS_LESSON", "COM_JLMS_NEXT_LESSON",
    "$adjacent->schedule_state === 'available'",
    "empty($adjacent->prerequisite_blocked)",
    "COM_JLMS_LESSON_CLOSED",
    "COM_JLMS_PREREQUISITES_INCOMPLETE",
    'rel="<?= $direction === \'previous\' ? \'prev\' : \'next\' ?>"',
):
    assert fragment in VIEW, fragment
nav = VIEW.split('id="jlms-lesson-navigation"', 1)[1].split('</nav>', 1)[0]
assert 'name="task"' not in nav, "Navigation must be read-only"
assert 'method="post"' not in nav, "Navigation must not change lesson progress"
assert "lesson.complete" not in nav
assert 'class="jlms-lesson-navigation__link"' in nav
assert 'class="jlms-lesson-navigation__unavailable"' in nav
for lang in ('it-IT', 'en-GB'):
    data = (ROOT / f"component/site/language/{lang}/com_jlms.ini").read_text()
    for key in ("COM_JLMS_LESSON_NAVIGATION", "COM_JLMS_PREVIOUS_LESSON", "COM_JLMS_NEXT_LESSON"):
        assert data.count(key + '=') == 1, (lang, key)
assert '#jlms-classroom .jlms-lesson-navigation' in CSS
assert '@container jlms-classroom (max-width: 34rem)' in CSS
assert '#jlms-classroom .jlms-lesson-navigation__link:focus-visible' in CSS
print("FE-04 manual adjacent navigation, ACL states and i18n: OK")
