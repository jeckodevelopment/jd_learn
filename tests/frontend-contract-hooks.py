#!/usr/bin/env python3
"""FE-10 frontend contracts: preserve the IDs/data hooks consumed by JS.

Pure structural check: Joomla browser/ACL and third-party overrides need manual QA.
"""
from pathlib import Path
import json
import re

ROOT = Path(__file__).resolve().parents[1]
SITE = ROOT / "component/site/tmpl"
JS = ROOT / "component/media/js"

# Literal IDs consumed by existing scripts. The source parser below checks that
# the inventory cannot drift silently when JS adds or removes selectors.
PAIRS = {
    "classroom-outline.js": {
        "layout": "classroom",
        "ids": {
            "jlms-classroom-outline-toggle", "jlms-classroom-outline",
        },
        "attrs": {"data-show-label", "data-hide-label", "aria-expanded"},
    },
    "lesson-progress.js": {
        "layout": "classroom",
        "ids": {"jlms-lesson-progress-status"},
        "dynamic": {"jlms-classroom-lesson-"},
        "attrs": set(),
    },
    "audio-progress.js": {
        "layout": "classroom",
        "ids": {"jlms-audio-player", "jlms-audio-progress-status"},
        "attrs": {"data-resume-ms"},
    },
    "video.js": {
        "layout": "classroom",
        "ids": {
            "jlms-player", "jlms-player-status", "jlms-video-region",
            "jlms-video-speed", "jlms-video-speed-control",
            "jlms-video-language", "jlms-video-language-control",
            "jlms-progress-meter",
        },
        "attrs": set(),
    },
    "quiz.js": {
        "layout": "quiz",
        "ids": {
            "jlms-quiz", "jlms-quiz-form", "jlms-quiz-status",
            "jlms-quiz-warning", "jlms-quiz-extension", "jlms-quiz-duration",
            "jlms-quiz-save", "jlms-quiz-start", "jlms-quiz-timer",
        },
        "classes": {"jlms-quiz-clear"},
        "attrs": {"data-question-id"},
    },
}
for file, spec in PAIRS.items():
    js = (JS / file).read_text(encoding="utf-8")
    markup = (SITE / spec["layout"] / "default.php").read_text(encoding="utf-8")
    # JS selectors and ID strings are guarded by the inventory and must have
    # matching HTML producers; dynamic records use a separately declared prefix.
    for hook in spec["ids"]:
        assert any(q + hook + q in js or q + "#" + hook + q in js for q in ("\u0027", "\u0022", "\u0060")), (file, hook, "JS selector missing")
        assert re.search(r'id="' + re.escape(hook) + r'"', markup), (file, hook, "HTML id missing")
    for prefix in spec.get("dynamic", set()):
        assert prefix in js and 'id="' + prefix in markup, (file, prefix, "dynamic id prefix")
    for name in spec.get("classes", set()):
        assert "." + name in js and name in markup, (file, name, "JS class hook")
    for name in spec["attrs"]:
        assert name in markup, (file, name, "HTML attribute missing")

# The JS modules are registered in Joomla WAM and loaded from the component,
# not duplicated in a theme-specific asset bundle.
asset = json.loads((ROOT / "component/media/joomla.asset.json").read_text(encoding="utf-8"))
js_uris = {v["uri"] for v in asset["assets"] if v["type"] == "script"}
for jsfile in PAIRS:
    assert "com_jlms/" + jsfile in js_uris, (jsfile, "WAM asset missing")
assert any(v["name"] == "com_jlms.site" and v["uri"] == "com_jlms/site.css"
           for v in asset["assets"]), "frontend stylesheet missing"

# Existing public roots and their overrideable layout files.
roots = {
    "courses": "jlms-courses",
    "course": "jlms-course",
    "pathways": "jlms-pathways",
    "pathway": "jlms-pathway",
    "categories": "jlms-categories",
    "category": "jlms-category",
    "dashboard": "jlms-dashboard",
    "classroom": "jlms-classroom",
    "quiz": "jlms-quiz",
    "teacher": "jlms-teacher-profile",
    "teacheredit": "jlms-teacher-profile-form",
    "lessonpreview": "jlms-public-lesson-preview",
    "mailconsentrevoke": "jlms-mail-consent-revoke",
}
for view, element_id in roots.items():
    source = (SITE / view / "default.php").read_text(encoding="utf-8")
    assert 'id="' + element_id + '"' in source, (view, "root not preserved")
inventory = (ROOT / "docs/FRONTEND_CONTRACT_INVENTORY.md").read_text(encoding="utf-8")
for view in ("lessonpreview", "mailconsentrevoke"):
    assert f"`{view}/default.php`" in inventory, view
for hook in ("jlms-public-preview-materials", "jlms-mail-consent-revoke-form",
             "jlms-mail-consent-link-completed", "jlms-mail-consent-link-invalid"):
    assert hook in inventory, hook
revoke = (SITE / "mailconsentrevoke/default.php").read_text(encoding="utf-8")
assert 'method="post"' in revoke and "HTMLHelper::_('form.token')" in revoke
assert 'name="token"' in revoke
assert (ROOT / "docs/FRONTEND_CONTRACT_INVENTORY.md").is_file()
print(f"FE-10 frontend inventory contracts: {len(roots)} views, {len(PAIRS)} JS modules, OK")
