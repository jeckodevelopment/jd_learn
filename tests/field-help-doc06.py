#!/usr/bin/env python3
"""DOC-06 native Joomla field descriptions for the course editor."""
from pathlib import Path
from xml.etree import ElementTree as ET

root = Path(__file__).resolve().parents[1]
form = ET.parse(root / "component/administrator/forms/course.xml").getroot()
fields = {field.get("name"): field for field in form.iter("field")}
keys = ["presentation_image_alt","presentation_image_decorative","presentation_objectives","presentation_audience","presentation_difficulty","catalog_access","content_access","presentation_seo_title","presentation_seo_description","calendar_publish_up","calendar_publish_down","calendar_starts_at","calendar_ends_at"]
translations = {}
for lang in ("it-IT", "en-GB"):
    content = (root / "component/administrator/language" / lang / "com_jlms.ini").read_text(encoding="utf-8")
    translations[lang] = content
    for key in keys:
        token = "COM_JLMS_DOC06_" + key.upper()
        assert content.count(token + '="') == 1, (lang, token)
        value = next(row.split('="', 1)[1].rsplit('"', 1)[0]
                     for row in content.splitlines() if row.startswith(token + '="'))
        assert len(value) >= 35 and "<" not in value and ">" not in value, (lang, token)
for key in keys:
    assert key in fields, key
    assert fields[key].get("description") == "COM_JLMS_DOC06_" + key.upper(), key
    assert fields[key].get("label"), key
print(f"DOC-06: {len(keys)} native editor field descriptions validated in it-IT and en-GB")
