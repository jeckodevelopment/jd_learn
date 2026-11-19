#!/usr/bin/env python3
"""DOC-05: all administrator views have bilingual offline help and valid manual links."""
from html.parser import HTMLParser
from pathlib import Path

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
        self.hrefs = []
        self.sections = []
    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == "html":
            self.lang = attrs.get("lang")
        if tag == "main":
            self.has_main = True
        if tag == "h1":
            self.has_h1 = True
        if tag == "a":
            self.hrefs.append(attrs.get("href", ""))
        if tag == "section":
            self.sections.append(attrs.get("id", ""))

views = sorted((admin / "src/View").glob("*/HtmlView.php"))
assert len(views) >= 29, f"administrator view inventory shrank: {len(views)}"
seen = set()
for path in views:
    key = path.parent.name.lower()
    assert key not in seen, f"duplicate help route: {key}"
    seen.add(key)
    source = path.read_text(encoding="utf-8")
    assert "ToolbarHelper::help('" + key + "', true)" in source, path
    assert ("use Joomla\\CMS\\Toolbar\\ToolbarHelper;" in source
            or "\\\\Joomla\\CMS\\Toolbar\\ToolbarHelper::help(" in source), path
    assert "return ToolbarHelper::help(" not in source, path

for lang in ("it-IT", "en-GB"):
    help_dir = admin / "help" / lang
    shipped = {p.stem for p in help_dir.glob("*.html")}
    assert shipped == seen | {"manual"}, f"missing/orphan {lang} help pages: {shipped ^ (seen | {'manual'})}"

    manual_file = help_dir / "manual.html"
    manual_raw = manual_file.read_text(encoding="utf-8")
    manual = HelpPage()
    manual.feed(manual_raw)
    assert (manual.lang, manual.has_main, manual.has_h1) == (lang, True, True), manual_file
    assert len(manual.sections) == len(set(manual.sections)), f"duplicate manual anchors: {lang}"
    assert set(manual.sections) == seen, f"manual chapters differ from views: {lang}"
    assert len(manual.sections) == len(views), f"missing manual chapters: {lang}"
    assert "http://" not in manual_raw and "https://" not in manual_raw, manual_file

    for key in sorted(seen):
        page_file = help_dir / (key + ".html")
        raw = page_file.read_text(encoding="utf-8")
        page = HelpPage()
        page.feed(raw)
        assert (page.lang, page.has_main, page.has_h1) == (lang, True, True), page_file
        assert "<title>JD Learn" in raw, "each page requires its own descriptive title"
        assert "http://" not in raw and "https://" not in raw, page_file
        assert page.hrefs == [f"manual.html#{key}"], f"invalid relative manual link: {page_file}"
        assert f'id="{key}"' in manual_raw, f"missing destination chapter: {key}/{lang}"

print(f"DOC-05: {len(views)}/{len(views)} views, {2 * len(views)} localized help links and 2 offline manuals validated")
