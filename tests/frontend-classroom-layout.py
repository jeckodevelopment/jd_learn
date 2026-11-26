#!/usr/bin/env python3
"""FE-03: classroom presentation and CSS/JS contract smoke checks."""
from pathlib import Path
import re
ROOT = Path(__file__).resolve().parents[1]
view = (ROOT / "component/site/tmpl/classroom/default.php").read_text()
service = (ROOT / "component/administrator/src/Application/LearningService.php").read_text()
js = (ROOT / "component/media/js/classroom-outline.js").read_text()
css = (ROOT / "component/media/css/site.css").read_text()

for old in ('id="jlms-classroom"', 'id="jlms-classroom-outline-toggle"',
            'id="jlms-classroom-outline"', 'id="jlms-lesson-content"',
            'id="jlms-progress-meter"', 'id="jlms-classroom-dashboard"'):
    assert old in view, old
assert 'class="jlms-classroom-module"' in view
assert '<details id="jlms-classroom-module-' in view
assert '<summary class="jlms-classroom-module__summary">' in view
assert re.search(r"if\s*\(\$currentModule\)\s*:\s*\?>\s*</ol></details>", view)
assert '$selectedModule === $currentModule' in view
assert 'id="jlms-classroom-teachers"' in view
assert "TeacherProfiles::current($this->db, $actor, $language, (int) $course->id)" in service
assert "'teachers' => $publicTeachers" in service
assert 'ResizeObserverClass' in js and 'observer.observe(classroom)' in js
assert "matchMedia?.('(max-width: 48rem)')" in js
assert "outline.hidden = !expanded" in js and "toggle.hidden = !compact" in js
assert 'aria-expanded' in js
assert 'container: jlms-classroom / inline-size' in css
assert '@container jlms-classroom (max-width: 48rem)' in css
assert '--jlms-classroom-sticky-offset' in css
assert 'position: static' in css and 'position: sticky' in css
for task in ('lesson.complete', 'attachment.download', 'video', 'quiz'):
    assert task in view, task
# SCR-06: manual video completion exists only for an explicitly authored
# manual video rule and still uses the CSRF-protected native completion action.
assert "$selected->completion_rule === 'manual'" in view
assert "$selected->provider_key === 'youtube' && $selected->completion_rule === 'video_coverage'" in view
video_region = view.split('id="jlms-video-region"', 1)[1].split('id="jlms-video-speed-control"', 1)[0]
assert 'id="jlms-complete-form"' in video_region
assert 'name="task" value="lesson.complete"' in video_region
assert "HTMLHelper::_('form.token')" in video_region
# SCR-08: explicit local alternative is a same-site course-scoped navigation,
# not an external player link and not a completion endpoint.
assert 'id="jlms-video-alternative"' in view
assert "if (!empty($item['video_alternative']))" in view
alt_section = view.split('id="jlms-video-alternative"', 1)[1].split('id="jlms-player-status"', 1)[0]
assert "'&lesson_id=' . (int) $item['video_alternative']['id']" in alt_section
assert "COM_JLMS_VIDEO_ALTERNATIVE_NO_CREDIT" in alt_section
assert 'task=video.playback' not in alt_section
assert 'video_alternative' in service
assert "in_array($entry->kind, ['text', 'document', 'audio'], true)" in service
print('FE-03 classroom responsive outline and compatibility: OK')
