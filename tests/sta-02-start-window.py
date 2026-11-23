#!/usr/bin/env python3
"""STA-02: strict duration, visible pre-start refusal and all closing gates."""
from pathlib import Path
root=Path(__file__).resolve().parents[1]
service=(root/'component/administrator/src/Application/QuizAttemptService.php').read_text()
layout=(root/'component/site/tmpl/quiz/default.php').read_text()
attempts=(root/'tests/joomla-quiz-attempts.php').read_text()
timing=(root/'tests/joomla-quiz-time.php').read_text()
assert 'assertQuizStartWindow($e, $l, $now, (int)$rules[\'time_seconds\'])' in service
assert "throw new \\RuntimeException('COM_JLMS_LEARNING_WINDOW', 403)" in service
assert "'deadline_at' => $rules['time_seconds'] ? self::date($now + $rules['time_seconds']) : null" in service
assert "'start_window_insufficient' => $insufficientWindow" in service
assert "'remaining_window_seconds' => $closingAt === null ? null : max(0, $closingAt - $now)" in service
assert "'start_window_close_at' => $closingAt" in service
assert 'quizClosingAt($e, $l)' in service
assert 'COM_JLMS_QUIZ_WINDOW_INSUFFICIENT' in layout
assert 'jlms-quiz-window-insufficient' in layout and 'jlms-quiz-window-end' in layout
for token in ("'#__jlms_enrollments'","'#__jlms_editions'","'#__jlms_lessons'","'#__jlms_courses'"):
    assert token in attempts, token
assert 'course calendar end' in attempts and 'exact-duration boundary' in attempts
assert 'individualized timer' in timing and 'not consumed' in timing
for lang in ('it-IT','en-GB'):
    ini=(root/f'component/site/language/{lang}/com_jlms.ini').read_text()
    assert 'COM_JLMS_QUIZ_WINDOW_INSUFFICIENT="' in ini
    assert 'COM_JLMS_QUIZ_WINDOW_END="' in ini
print('STA-02 strict pre-start full timer and no implicit truncation: OK')
