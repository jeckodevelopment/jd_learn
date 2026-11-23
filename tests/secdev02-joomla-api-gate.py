#!/usr/bin/env python3
"""SECDEV-02: reject known Joomla legacy aliases in distributed PHP.

This is a narrow source gate, NOT a complete Joomla API-compatibility analysis.
"""
from __future__ import annotations

from pathlib import Path
import re
import sys

ROOT = Path(__file__).resolve().parents[1]
SOURCE_ROOTS = ("component", "libraries", "plugins", "packages")
# These global J-prefixed classes require historical aliases and must not be
# introduced into first-party distributed sources for Joomla 6.
LEGACY_CLASS = re.compile(r"(?<![A-Za-z0-9_])(?:JFactory|JText|JInput|JDatabase|JPlugin|JTable|JModelLegacy|JControllerLegacy|JViewLegacy)(?![A-Za-z0-9_])")
# The old input namespace was removed from the Joomla 6 core; don't require
# the compatibility plugin to resurrect it.
REMOVED_NAMESPACE = re.compile(r"(?<![A-Za-z0-9_])(?:Joomla\\CMS\\Input\\Input)(?![A-Za-z0-9_])")


# Clear Joomla 6 removals and compat6-only names from the official 5.4->6 guide.
# This is a conservative, non-exhaustive source scan, not an AST/type audit.
REMOVED_JOOMLA6 = re.compile(
    r"(?<![A-Za-z0-9_])(?:"
    r"Joomla\\CMS\\Filesystem\\[A-Za-z_]\w*|"
    r"Joomla\\CMS\\Adapter\\[A-Za-z_]\w*|"
    r"Joomla\\CMS\\Application\\(?:BaseApplication|CliApplication)|"
    r"JPATH_PLATFORM|JCOMPAT_UNICODE_PROPERTIES|FinderIndexerHelper"
    r")(?![A-Za-z0-9_])"
)
REMOVED_CALLS = re.compile(
    r"(?:->|::)\s*(?:isCli|createThumbs|fetchExtensionCompatibility|"
    r"getLogContentTypeParams)\s*\("
)


def scan(root: Path) -> list[str]:
    findings: list[str] = []
    for folder in SOURCE_ROOTS:
        base = root / folder
        if not base.is_dir():
            continue
        for path in sorted(base.rglob("*.php")):
            if any(part in {"vendor", "node_modules", "dist"} for part in path.parts):
                continue
            text = path.read_text(encoding="utf-8")
            for number, line in enumerate(text.splitlines(), 1):
                # Only executable references and use/imports; standalone
                # comments are not a guarantee, and this scanner is deliberately conservative.
                stripped = line.lstrip()
                if stripped.startswith(("//", "*", "#")):
                    continue
                if (LEGACY_CLASS.search(line) or REMOVED_NAMESPACE.search(line)
                        or REMOVED_JOOMLA6.search(line) or REMOVED_CALLS.search(line)):
                    findings.append(f"{path.relative_to(root)}:{number}: legacy Joomla API reference")
    return findings


def self_test() -> None:
    from tempfile import TemporaryDirectory
    with TemporaryDirectory() as directory:
        root = Path(directory)
        base = root / "component/site/src"
        base.mkdir(parents=True)
        source = base / "Sample.php"
        source.write_text("<?php\nuse Joomla\\CMS\\Factory;\n", encoding="utf-8")
        assert not scan(root)
        source.write_text("<?php\n$x = JFactory::getApplication();\n", encoding="utf-8")
        assert scan(root)
        source.write_text("<?php\nuse Joomla\\CMS\\Input\\Input;\n", encoding="utf-8")
        assert scan(root)
        for removed in (
            r"use Joomla\CMS\Filesystem\File;",
            "defined('JPATH_PLATFORM');",
            "$app->isCli();",
            "$image->createThumbs('50x50');",
            r"use Joomla\CMS\Adapter\Adapter;",
        ):
            source.write_text("<?php\\n" + removed + "\\n", encoding="utf-8")
            assert scan(root), removed
        source.write_text("<?php\n// JFactory is a legacy alias\n", encoding="utf-8")
        assert not scan(root)


if __name__ == "__main__":
    if "--self-test" in sys.argv:
        self_test()
        print("SECDEV-02 legacy API fixtures: OK")
    else:
        hits = scan(ROOT)
        for hit in hits:
            print(hit, file=sys.stderr)
        if hits:
            sys.exit(1)
        print("SECDEV-02: no selected known Joomla legacy API references in distributed PHP")
