#!/usr/bin/env python3
"""PBASE-02: provider-local README coverage tied to distributed Joomla manifest fields."""
from pathlib import Path
import xml.etree.ElementTree as ET
ROOT = Path(__file__).resolve().parents[1]
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
print("PBASE-02: all four plugin README files cover manifest parameters and operational limitations")
