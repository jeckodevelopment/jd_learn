#!/usr/bin/env python3
"""DOC-02: bundled offline IT/EN operations, matched anchors and safe guardrails."""
from html.parser import HTMLParser
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SECTIONS = ("video", "mail", "certificates", "security")
class Manual(HTMLParser):
    def __init__(self):
        super().__init__()
        self.ids = set()
        self.links = []
    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if "id" in attrs:
            assert attrs["id"] not in self.ids, ("duplicate id", attrs["id"])
            self.ids.add(attrs["id"])
        if tag == "a" and attrs.get("href", "").startswith("#"):
            self.links.append(attrs["href"][1:])
for language in ("it-IT", "en-GB"):
    path = ROOT / "component/administrator/help" / language / "manual.html"
    body = path.read_text(encoding="utf-8")
    parser = Manual()
    parser.feed(body)
    assert len(parser.ids) >= 33, (language, len(parser.ids))
    assert set(parser.links) <= parser.ids, (language, set(parser.links) - parser.ids)
    for name in SECTIONS:
        anchor = "operations-" + name
        assert anchor in parser.ids and anchor in parser.links, (language, anchor)
    for marker in ("YouTube", "Vimeo", "Cloudflare", "HTML5", "SMTP", "PDF", "ACL"):
        assert marker in body, (language, marker)
    for marker in ("SQL", "consent", "backup") if language == "en-GB" else ("SQL", "consenso", "backup"):
        assert marker.lower() in body.lower(), (language, marker)
print("DOC-02: four matching offline IT/EN chapters, valid anchors and security guidance")
