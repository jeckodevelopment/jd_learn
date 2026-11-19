#!/usr/bin/env python3
"""DOC-05: every shipped administrator HTML view has bilingual offline help."""
from pathlib import Path
from html.parser import HTMLParser

ROOT = Path(__file__).resolve().parents[1]
admin = ROOT / "component/administrator"
manifest = (ROOT / "component/jlms.xml").read_text(encoding="utf-8")
assert "<folder>help</folder>" in manifest, "help files are not packaged"

class HelpPage(HTMLParser):
    def __init__(self):
        super().__init__()
        self.lang = None
        self.has_main = False
        self.has_h1 = False
    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == "html":
            self.lang = attrs.get("lang")
        if tag == "main":
            self.has_main = True
        if tag == "h1":
            self.has_h1 = True

views = sorted((admin / "src/View").glob("*/HtmlView.php"))
assert len(views) >= 29, f"administrator view inventory shrank unexpectedly: {len(views)}"
seen = set()
for path in views:
    view = path.parent.name
    key = view.lower()
    assert key not in seen, f"duplicate help route: {view}"
    seen.add(key)
    source = path.read_text(encoding="utf-8")
    assert "ToolbarHelper::help('" + key + "', true)" in source, view
    for lang in ("it-IT", "en-GB"):
        page_path = admin / "help" / lang / (key + ".html")
        page = HelpPage()
        raw = page_path.read_text(encoding="utf-8")
        page.feed(raw)
        assert (page.lang, page.has_main, page.has_h1) == (lang, True, True), page_path
        assert "http://" not in raw and "https://" not in raw, "help must work offline"
        assert "<title>JD Learn" in raw, "each page must have a descriptive title"

for lang in ("it-IT", "en-GB"):
    shipped = {p.stem for p in (admin / "help" / lang).glob("*.html")}
    assert shipped == seen, f"orphan or missing {lang} help pages: {shipped ^ seen}"
print(f"DOC-05: all {len(views)} administrator views, {len(views) * 2} bilingual offline help pages OK")
