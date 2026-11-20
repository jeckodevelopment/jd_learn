#!/usr/bin/env python3
"""UI-07: actual rendered Joomla DOM contract checks, not source-only assertions.

Checks that JD Learn IDs do not collide with modules/other component output and
that the two independent public CSS customization selectors are effective in
the rendered ancestor/descendant structure.
"""
from collections import Counter
from html.parser import HTMLParser

VOID = {"area", "base", "br", "col", "embed", "hr", "img", "input", "link", "meta", "param", "source", "track", "wbr"}

class Document(HTMLParser):
    def __init__(self, markup):
        super().__init__(convert_charrefs=True)
        self.ids = []
        self.elements = []
        self.stack = []
        self.feed(markup)

    def handle_starttag(self, tag, attrs):
        attr = dict(attrs)
        ancestor_ids = tuple(a["id"] for a in self.stack if a["id"])
        element = {
            "tag": tag,
            "id": attr.get("id", ""),
            "classes": set(attr.get("class", "").split()),
            "ancestors": ancestor_ids,
        }
        self.elements.append(element)
        if element["id"]:
            self.ids.append(element["id"])
        if tag not in VOID:
            self.stack.append(element)

    def handle_startendtag(self, tag, attrs):
        self.handle_starttag(tag, attrs)
        if tag not in VOID:
            self.handle_endtag(tag)

    def handle_endtag(self, tag):
        for position in range(len(self.stack) - 1, -1, -1):
            if self.stack[position]["tag"] == tag:
                del self.stack[position:]
                return

def verify(markup, *, root, scoped_class=None):
    page = Document(markup)
    ids = Counter(page.ids)
    duplicates = sorted(key for key, count in ids.items() if key.startswith("jlms-") and count > 1)
    assert not duplicates, ("Duplicate JD Learn IDs on the rendered page", duplicates)
    assert ids[root] == 1, ("Expected exactly one JD Learn root", root, ids[root])
    if scoped_class:
        matches = [element for element in page.elements
                   if scoped_class in element["classes"] and root in element["ancestors"]]
        assert matches, ("CSS customization selector missing from real DOM", root, scoped_class)
    return len(page.ids)

if __name__ == "__main__":
    samples = [
        ('<div id="jlms-courses"><article class="jlms-course-card"></article></div>',
         "jlms-courses", "jlms-course-card"),
        ('<section id="jlms-dashboard"><div class="jlms-my-course"></div></section>',
         "jlms-dashboard", "jlms-my-course"),
    ]
    for html, root, css_class in samples:
        verify(html, root=root, scoped_class=css_class)
        for invalid in (html.replace(f'id="{root}"', f'id="{root}" id="{root}"'),
                        html + f'<aside id="{root}"></aside>'):
            try:
                verify(invalid, root=root, scoped_class=css_class)
            except AssertionError:
                continue
            raise AssertionError("Fixture with duplicate ID was accepted")
    try:
        verify('<div id="jlms-courses"></div><article class="jlms-course-card"></article>',
               root="jlms-courses", scoped_class="jlms-course-card")
    except AssertionError:
        pass
    else:
        raise AssertionError("Unscoped custom styling was accepted")
    print("UI-07 rendered DOM: duplicate-ID and two independent CSS customization fixtures passed")
