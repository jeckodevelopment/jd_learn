#!/usr/bin/env python3
"""FE-02 source contract: public outline, course CTA and responsive layout.

A structural check only. Joomla/ACL and cross-template acceptance remain manual.
"""
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
VIEW = (ROOT / "component/site/tmpl/course/default.php").read_text()
SERVICE = (ROOT / "component/administrator/src/Application/CatalogService.php").read_text()
STYLE = (ROOT / "component/media/css/site.css").read_text()

# Existing enrollment flows must survive moving the form; no second form/CTA.
for hook in ('id="jlms-course-access"', 'id="jlms-course-login"',
             'id="jlms-start-form"', 'id="jlms-enrollment-request-form"',
             'id="jlms-course-facts"', 'id="jlms-course-outline"',
             'id="jlms-course-materials"', 'id="jlms-course-teachers"',
             'id="jlms-course-back"'):
    assert VIEW.count(hook) == 1, (hook, VIEW.count(hook))
for task in ('enrollment.start', 'enrollment.request'):
    assert VIEW.count('name="task" value="' + task + '"') == 1, task
assert VIEW.count("HTMLHelper::_('form.token')") == 2
assert VIEW.index('id="jlms-course-summary"') < VIEW.index('id="jlms-course-sidebar"')
assert VIEW.index('id="jlms-course-main"') < VIEW.index('id="jlms-course-sidebar"')
assert VIEW.index('id="jlms-course-description"') < VIEW.index('id="jlms-course-fields-after-content"') < VIEW.index('id="jlms-course-sidebar"')
assert VIEW.index('id="jlms-course-sidebar"') < VIEW.index('id="jlms-course-outline"')
assert VIEW.count('<details id="jlms-module-') == 1
assert VIEW.count('</details>') == 1
assert 'moduleIndex++ === 0' in VIEW
assert 'id="jlms-lesson-' in VIEW and 'jlms-lesson__title' in VIEW
assert 'jlms-lesson__public-preview' in VIEW
assert "CoursePresentation::fromSnapshot(" not in VIEW  # view model still supplies safe presentation

# Public server projection: only identifiers, titles and schedule metadata,
# no lesson body, attachment URL, provider video details or private quiz.
outline = SERVICE.split('public function outline(', 1)[1].split('public function publicLessonPreview', 1)[0]
outline = re.sub(r"""(['"])\s*\.\s*\1""", '', outline)
assert re.search(r"->select\(\s*\(*\s*'id, module_id, title, kind, required, preview, publish_up, publish_down, release_after_days'", outline)
assert 'LessonAvailability::state(' in outline
assert "=== 'available'" in outline and "if (!$publishable)" in outline
assert "catch (\InvalidArgumentException $exception)" in outline
assert "->select('id, title')" in outline
assert re.search(r"->select\(\s*\(*\s*'id, module_id, title, kind, required, preview, publish_up, publish_down, release_after_days'", outline)
assert 'l.body' not in outline and "l.video" not in outline
assert "TeacherProfiles::current(" in outline

for css in ("#jlms-course-layout", "grid-template-areas: \"main sidebar\" \"supplement sidebar\"",
            'grid-template-areas: "main" "sidebar" "supplement"', '#jlms-course-sidebar',
            "position: sticky", "position: static", "@container jlms-course-page",
            "#jlms-course-outline .jlms-module__summary:focus-visible"):
    assert css in STYLE, css
print("FE-02 public course detail layout and protection contracts: OK")
