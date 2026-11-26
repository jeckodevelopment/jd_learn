#!/usr/bin/env python3
"""MAIL-05: ensure the Joomla form and global configuration cover every mail event."""
from __future__ import annotations
import re
from pathlib import Path
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "component/administrator/src/Application/MailPreferences.php"
COURSE = ROOT / "component/administrator/forms/course.xml"
CONFIG = ROOT / "component/administrator/config.xml"

def inspect(source: str, course: str, config: str) -> list[str]:
    problems: list[str] = []
    match = re.search(r"public const EVENTS\s*=\s*\[([^]]+)\]", source)
    if not match:
        return ["missing canonical event list"]
    events = re.findall(r"'([a-z_]+)'", match.group(1))
    if len(events) != 16 or len(set(events)) != 16:
        problems.append("mail event inventory must contain 16 distinct events")
    course_fields = ET.fromstring(course)
    config_fields = ET.fromstring(config)
    actual_course = {f.get("name")[5:] for f in course_fields.iter("field")
                     if (f.get("name") or "").startswith("mail_")}
    expected_course = set(events) - {"announcement"}
    if actual_course != expected_course:
        problems.append(f"per-course mail switches out of sync: {sorted(actual_course ^ expected_course)}")
    global_fields = {f.get("name")[5:-8] for f in config_fields.iter("field")
                     if (f.get("name") or "").startswith("mail_")
                     and (f.get("name") or "").endswith("_enabled")}
    if not expected_course.issubset(global_fields):
        problems.append(f"missing global mail switches: {sorted(expected_course - global_fields)}")
    for field in course_fields.iter("field"):
        if (field.get("name") or "") not in {"mail_" + x for x in expected_course}:
            continue
        options = {o.get("value") for o in field.findall("option")}
        if options != {"inherit", "enabled", "disabled"} or field.get("default") != "inherit":
            problems.append("invalid inherit/enabled/disabled options for " + str(field.get("name")))
    if "if (!is_array($preferences))" not in source:
        problems.append("malformed stored mail preferences must fail closed")
    return problems

def main() -> None:
    source, course, config = (p.read_text(encoding="utf-8") for p in (SOURCE, COURSE, CONFIG))
    assert inspect(source, course, config) == [], inspect(source, course, config)
    assert inspect(source.replace("'enrollment',", "", 1), course, config)
    assert inspect(source.replace("if (!is_array($preferences))", "if (false)", 1), course, config)
    assert inspect(source, course.replace('name="mail_enrollment"', 'name="mail_enrolment"', 1), config)
    assert inspect(source, course.replace('default="inherit"', 'default="enabled"', 1), config)
    print("MAIL-05: 16 events, 15 per-course/global switches, override semantics and negative fixtures: OK")

if __name__ == "__main__":
    main()
