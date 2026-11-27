#!/usr/bin/env python3
"""DOC-03: accessible, source-controlled illustrations are linked from user guides."""
from __future__ import annotations

from pathlib import Path
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]
ASSETS = {
    "docs/assets/doc03/learner-catalog.svg": "docs/LEARNING.md",
    "docs/assets/doc03/learner-dashboard.svg": "docs/LEARNING.md",
    "docs/assets/doc03/learner-classroom.svg": "docs/LEARNING.md",
    "docs/assets/doc03/teacher-profile.svg": "docs/TEACHERS.md",
}
SVG_NS = "{http://www.w3.org/2000/svg}"


def validate() -> None:
    for asset, guide in ASSETS.items():
        path = ROOT / asset
        assert path.is_file(), f"DOC-03: missing illustration {asset}"
        root = ET.parse(path).getroot()
        assert root.tag == f"{SVG_NS}svg", f"DOC-03: {asset} is not an SVG"
        assert root.attrib.get("role") == "img", f"DOC-03: {asset} lacks image semantics"
        labelled_by = root.attrib.get("aria-labelledby", "").split()
        ids = {element.attrib.get("id") for element in root.iter()}
        assert len(labelled_by) == 2 and set(labelled_by) <= ids, (
            f"DOC-03: {asset} must expose title and description"
        )
        assert any(element.tag == f"{SVG_NS}title" for element in root.iter()), (
            f"DOC-03: {asset} has no accessible title"
        )
        assert any(element.tag == f"{SVG_NS}desc" for element in root.iter()), (
            f"DOC-03: {asset} has no accessible description"
        )
        source = path.read_text(encoding="utf-8")
        assert "<script" not in source.lower() and "href=" not in source.lower(), (
            f"DOC-03: {asset} must not load active or external content"
        )
        markdown = (ROOT / guide).read_text(encoding="utf-8")
        relative = asset.removeprefix("docs/")
        lines = [line for line in markdown.splitlines() if relative in line]
        assert len(lines) == 1 and lines[0].startswith("!["), (
            f"DOC-03: {asset} needs one Markdown image with non-empty alternative text"
        )
        alt = lines[0].split("](", 1)[0][2:].strip()
        assert len(alt) >= 40 and "." in alt, f"DOC-03: {asset} alternative text is incomplete"


if __name__ == "__main__":
    validate()
    print(f"DOC-03: {len(ASSETS)} accessible screen illustrations verified")
