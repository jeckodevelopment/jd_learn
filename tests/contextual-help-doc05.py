#!/usr/bin/env python3
"""DOC-05 contextual help: offline Joomla contracts for core administration."""
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

for view in ("Courses", "Course", "Enrollments", "Curriculum", "Dashboard",
             "Mailqueue", "Mailvariables", "Coursemessage", "Certificates", "Certificate"):
    source = (admin / "src/View" / view / "HtmlView.php").read_text(encoding="utf-8")
    key = view.lower()
    assert "ToolbarHelper::help('" + key + "', true)" in source, view
    for lang in ("it-IT", "en-GB"):
        path = admin / "help" / lang / (key + ".html")
        page = HelpPage()
        raw = path.read_text(encoding="utf-8")
        page.feed(raw)
        assert (page.lang, page.has_main, page.has_h1) == (lang, True, True), path
        assert "http://" not in raw and "https://" not in raw, "help must work offline"
print("DOC-05: 10 backend views, 20 localized packaged help pages, OK")
