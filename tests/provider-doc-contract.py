#!/usr/bin/env python3
"""PBASE-02: provider-local README coverage tied to distributed Joomla manifest fields."""
from pathlib import Path
from tempfile import TemporaryDirectory
from shutil import copy2
import sys
import xml.etree.ElementTree as ET
ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "tools"))
from provider_doc_gate import validate_provider_guides
validate_provider_guides(ROOT)
for name in ("youtube", "vimeo", "cloudflarestream", "html5"):
    base = ROOT / "plugins" / "jlmsvideo" / name
    manifest = ET.parse(base / f"{name}.xml").getroot()
    fields = [field.attrib["name"] for field in manifest.findall(".//field")]
    guide = (base / "docs" / "README.md").read_text(encoding="utf-8")
    assert f"plg_jlmsvideo_{name}" in guide, name
    for heading in ("## Configurazione e sorgente", "## Capacità e limiti", "## Errori, manutenzione e collaudo"):
        assert heading in guide, (name, heading)
    for field in fields:
        assert f"`{field}`" in guide, (name, field)
    assert "collaudo" in guide.lower()
# Negative fixture: remove a complete lifecycle chapter from one distributed
# README. The package validator must fail instead of approving a thin stub.
with TemporaryDirectory(prefix="jlms-provider-spec-") as temporary:
    isolated = Path(temporary)
    for name in ("youtube", "vimeo", "cloudflarestream", "html5"):
        target = isolated / "plugins" / "jlmsvideo" / name
        (target / "docs").mkdir(parents=True)
        source = ROOT / "plugins" / "jlmsvideo" / name
        copy2(source / f"{name}.xml", target / f"{name}.xml")
        copy2(source / "docs" / "README.md", target / "docs" / "README.md")
    target = isolated / "plugins" / "jlmsvideo" / "youtube" / "docs" / "README.md"
    target.write_text(target.read_text(encoding="utf-8").replace(
        "## Prestazioni, errori e matrice di accettazione",
        "## Capitolo eliminato", 1), encoding="utf-8")
    try:
        validate_provider_guides(isolated)
    except ValueError as error:
        assert "youtube" in str(error), "incorrect rejected provider"
    else:
        raise AssertionError("PBASE-02 package gate accepted missing lifecycle specifications")
print("PBASE-02: four provider guides and negative lifecycle fixture passed")
