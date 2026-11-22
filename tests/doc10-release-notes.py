#!/usr/bin/env python3
"""DOC-10 pre-release notes contract; does not certify a stable release."""
from pathlib import Path
import re
import xml.etree.ElementTree as ET

root = Path(__file__).resolve().parents[1]
notes = (root / "docs/RELEASE_NOTES_CANDIDATE_1.0.0.md").read_text(encoding="utf-8")
manifest = ET.parse(root / "packages/pkg_jlms.xml").getroot()
version = manifest.findtext("version")
assert re.fullmatch(r"[0-9]+\.[0-9]+\.[0-9]+", version or ""), "Invalid package manifest version"
assert ET.parse(root / "component/jlms.xml").getroot().findtext("version") == version
for heading in (
    "## Novità nel perimetro approvato",
    "## Compatibilità dichiarata e verifiche aperte",
    "## Incompatibilità e limitazioni note",
    "## Pacchetto, checksum e corrispondenza versione",
    "## Gate prima di pubblicare le note definitive",
):
    assert heading in notes, f"Missing DOC-10 section: {heading}"
for path in ("RELEASE_PROCEDURE.md", "REQUIREMENTS_STATUS.csv", "FUNCTIONAL_AUDIT_CURRENT.md", "IMPLEMENTATION_STATUS.md"):
    assert f"({path})" in notes, f"Missing authoritative reference: {path}"
assert "non" in notes.lower() and "pubblicata" in notes.lower()
assert "SHA-256" in notes and "ZIP Joomla interno" in notes
assert "Open Badges" in notes and "fuori" in notes
assert "nessun checksum" in notes.lower()
print(f"DOC-10 candidate notes contract OK (source version {version}; no acceptance claimed)")
