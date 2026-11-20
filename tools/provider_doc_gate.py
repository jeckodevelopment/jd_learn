"""Fail closed when the four bundled video plugins have no usable local guide.

This validation runs in the package builder itself, not only in the CI test gate.
No network requests or provider secrets are required.
"""
from pathlib import Path
import xml.etree.ElementTree as ET

PROVIDERS = ("youtube", "vimeo", "cloudflarestream", "html5")
SECTIONS = (
    "## Configurazione e sorgente",
    "## Capacità e limiti",
    "## Errori, manutenzione e collaudo",
)


def validate_provider_guides(root: Path) -> None:
    for provider in PROVIDERS:
        directory = root / "plugins" / "jlmsvideo" / provider
        manifest_path = directory / f"{provider}.xml"
        guide_path = directory / "docs" / "README.md"
        if not manifest_path.is_file():
            raise ValueError(f"PBASE-03: missing video plugin manifest: {provider}")
        if not guide_path.is_file():
            raise ValueError(f"PBASE-03: missing bundled plugin documentation: {provider}")
        try:
            manifest = ET.parse(manifest_path).getroot()
            guide = guide_path.read_text(encoding="utf-8")
        except (ET.ParseError, UnicodeError, OSError) as error:
            raise ValueError(f"PBASE-03: unreadable manifest or guide: {provider}") from error
        if manifest.attrib.get("type") != "plugin" or manifest.attrib.get("group") != "jlmsvideo":
            raise ValueError(f"PBASE-03: unexpected video plugin manifest: {provider}")
        fields = [field.attrib.get("name") for field in manifest.findall(".//field")]
        if not fields or any(not name for name in fields):
            raise ValueError(f"PBASE-03: incomplete manifest fields: {provider}")
        detailed_sections = (
            "## Identità, dipendenze e contratto di installazione",
            "## Parametri del manifest, tipi e valori iniziali",
            "## Privacy, tracciamento e ciclo di vita",
            "## Prestazioni, errori e matrice di accettazione",
        )
        required = [f"plg_jlmsvideo_{provider}", *SECTIONS, *detailed_sections, *(f"`{name}`" for name in fields)]
        missing = [item for item in required if item not in guide]
        if missing:
            raise ValueError(f"PBASE-03: incomplete bundled plugin documentation for {provider}: {', '.join(missing)}")

        # PBASE-02: ensure the versioned operational table cannot silently
        # diverge from field types, defaults or numeric bounds in the manifest.
        parameter_table = guide.split("## Parametri del manifest, tipi e valori iniziali", 1)[1].split("\\n## ", 1)[0]
        for field in manifest.findall(".//field"):
            name = field.attrib["name"]
            kind = field.attrib.get("type", "n/d")
            default = field.attrib.get("default")
            expected = f"| \`{name}\` | \`{kind}\` | "
            if expected not in parameter_table:
                raise ValueError(f"PBASE-02: missing field/type row for {provider}: {name}")
            row = next(line for line in parameter_table.splitlines() if line.startswith(expected))
            if default is None:
                if "Non dichiarato" not in row:
                    raise ValueError(f"PBASE-02: undocumented missing default for {provider}: {name}")
            elif f"\`{default}\`" not in row:
                raise ValueError(f"PBASE-02: wrong default for {provider}: {name}")
            for attribute in ("min", "max"):
                if attribute in field.attrib and f"{attribute} {field.attrib[attribute]}" not in row:
                    raise ValueError(f"PBASE-02: wrong {attribute} for {provider}: {name}")
