#!/usr/bin/env python3
"""SECDEV-02 CORE-05/06/09: fail-closed contracts for native UI, languages and tags.

Source-level checks only: no claim that every override, locale or tag ACL has
been accepted in a real Joomla installation.
"""
from __future__ import annotations

from pathlib import Path
import re
import sys

ROOT = Path(__file__).resolve().parents[1]
VARIABLES = (
    "--jlms-color-primary", "--jlms-color-text", "--jlms-color-surface",
    "--jlms-space-unit", "--jlms-radius", "--jlms-content-width",
)
VIEW_ROOTS = ("#jlms-courses", "#jlms-categories", "#jlms-dashboard", "#jlms-pathways")
REQUIRED_LANG_KEYS = (
    "COM_JLMS", "COM_JLMS_COURSES", "COM_JLMS_INVALID_COURSE",
)


def ini_keys(contents: str) -> dict[str, int]:
    counts: dict[str, int] = {}
    for line in contents.splitlines():
        if not line or line.lstrip().startswith((";", "#")):
            continue
        match = re.match(r"^([A-Z][A-Z0-9_]+)=", line)
        if match:
            key = match.group(1)
            counts[key] = counts.get(key, 0) + 1
    return counts


def validate(root: Path) -> list[str]:
    errors: list[str] = []
    css = (root / "component/media/css/site.css").read_text(encoding="utf-8")
    guide = (root / "docs/FRONTEND_CSS.md").read_text(encoding="utf-8")
    for variable in VARIABLES:
        if variable not in css or variable not in guide:
            errors.append(f"CORE-05: missing documented CSS variable {variable}")
    for selector in VIEW_ROOTS:
        if selector not in css:
            errors.append(f"CORE-05: missing public view root {selector}")
    if "templates/NOME/html/com_jlms/courses/default.php" not in guide:
        errors.append("CORE-05: native Joomla override example missing")
    for locale in ("it-IT", "en-GB"):
        admin = root / f"component/administrator/language/{locale}/com_jlms.ini"
        site = root / f"component/site/language/{locale}/com_jlms.ini"
        for language_file in (admin, site):
            keys = ini_keys(language_file.read_text(encoding="utf-8"))
            for key in REQUIRED_LANG_KEYS:
                if keys.get(key) != 1:
                    errors.append(f"CORE-06: missing or duplicate {key} in {language_file.relative_to(root)}")
    table = (root / "component/administrator/src/Table/CourseTable.php").read_text(encoding="utf-8")
    installer = (root / "component/administrator/src/Infrastructure/EditorialInstaller.php").read_text(encoding="utf-8")
    for token in (
        "TaggableTableInterface", "TaggableTableTrait",
        "use TaggableTableTrait;", "com_jlms.course",
    ):
        if token not in table:
            errors.append(f"CORE-09: native table tag integration removed: {token}")
    for token in ("new ContentType($db)", "com_jlms.", "core_content_item_id", "core_catid"):
        if token not in installer:
            errors.append(f"CORE-09: Joomla content-type mapping removed: {token}")
    return errors


def self_test() -> None:
    assert ini_keys("AA=one\nBB=two\nAA=three\n; AA=comment\n") == {"AA": 2, "BB": 1}
    assert ini_keys("COM_JLMS=\"JD Learn\"\n# note\n") == {"COM_JLMS": 1}
    assert not ini_keys("only prose\n;ignored=true\n")


if __name__ == "__main__":
    if "--self-test" in sys.argv:
        self_test()
        print("SECDEV-02 native UI, language and tags negative fixtures: OK")
    else:
        issues = validate(ROOT)
        for issue in issues:
            print(issue, file=sys.stderr)
        if issues:
            sys.exit(1)
        print("SECDEV-02 CORE-05/06/09 native source contracts: OK (Joomla acceptance separate)")
