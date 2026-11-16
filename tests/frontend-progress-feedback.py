#!/usr/bin/env python3
"""FE-04: progress-feedback source, translations and retry contract."""
from pathlib import Path
ROOT = Path(__file__).resolve().parents[1]
LESSON = (ROOT / "component/media/js/lesson-progress.js").read_text()
AUDIO = (ROOT / "component/media/js/audio-progress.js").read_text()
VIEW = (ROOT / "component/site/src/View/Classroom/HtmlView.php").read_text()
CSS = (ROOT / "component/media/css/site.css").read_text()
for path, source in (("lesson", LESSON), ("audio", AUDIO)):
    for pattern in ("dataset.state", "'alert'", "'status'",
                     "'assertive'", "'polite'",
                    "5000", "clearTimeout", "textContent = ''"):
        assert pattern in source, (path, pattern)
    assert ("status.dataset.state === 'saved'" in source or "feedbackState === 'saved'" in source)
    assert ("setAttribute('role'," in source or "setAttribute?.('role'," in source)
    assert ("setAttribute('aria-live'," in source or "setAttribute?.('aria-live'," in source)
    assert "state === 'error'" in LESSON if path == 'lesson' else "key === 'error'" in AUDIO
assert "send(0).then(" in LESSON and "send(1)" in LESSON
assert "config.idempotencyKey" in LESSON
assert "if (feedbackState !== 'error') setStatus('saving')" in AUDIO
assert "if (value === lastSent) return;" in AUDIO
assert AUDIO.index("if (value === lastSent) return;") < AUDIO.index("setStatus('saving')")
assert "COM_JLMS_AUDIO_RESUME_SAVING" in VIEW
assert "jlms-lesson-progress-status" in CSS and "jlms-audio-progress-status" in CSS
for language in ("it-IT", "en-GB"):
    messages = (ROOT / f"component/site/language/{language}/com_jlms.ini").read_text()
    assert messages.count("COM_JLMS_AUDIO_RESUME_SAVING=") == 1
print("FE-04 progress feedback state contracts: OK")
