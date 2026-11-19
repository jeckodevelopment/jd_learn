#!/usr/bin/env python3
"""LIFE-01: destructive course purge requires server preview and protected POST."""
from pathlib import Path
root = Path(__file__).resolve().parents[1]
model = (root / 'component/administrator/src/Model/CourseModel.php').read_text()
controller = (root / 'component/administrator/src/Controller/CourseController.php').read_text()
view = (root / 'component/administrator/src/View/Courses/HtmlView.php').read_text()
listing = (root / 'component/administrator/tmpl/courses/default.php').read_text()
preview = (root / 'component/administrator/tmpl/courses/purgepreview.php').read_text()
assert 'public function purgeImpact(int $id): array' in model
assert 'public function purgeEmpty(int $id, int $expectedRevision = 0): bool' in model
assert '(int) $record->revision !== $expectedRevision' in model
assert 'array_sum($counts) === 0' in model
assert "'#__jlms_course_versions'" in model
assert 'purgepreview' in view and 'purgepreview' in listing
assert "ToolbarHelper::deleteList('COM_JLMS_COURSE_PURGE_CONFIRM'" not in view
assert "checkToken('post', false)" in controller
assert "getInt('confirm_purge', 0) !== 1" in controller
assert "getInt('expected_revision', 0)" in controller
assert "purgeEmpty((int) $raw[0], $expectedRevision)" in controller
for name in ('cid[]', 'expected_revision', 'confirm_purge'):
    assert name in preview
assert "form.token" in preview and "if ($impact['eligible'])" in preview
for lang in ('it-IT','en-GB'):
    ini = (root / f'component/administrator/language/{lang}/com_jlms.ini').read_text()
    for key in ('COM_JLMS_PURGE_PREVIEW','COM_JLMS_PURGE_BLOCKED','COM_JLMS_PURGE_CONFIRM_ACTION'):
        assert key + '="' in ini
print('LIFE-01: preview, POST/CSRF, permission and revision guard checked')
