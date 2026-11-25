#!/usr/bin/env python3
"""CORE-06: resolve literal Joomla Text keys across all shipped component PHP.

Language-file parity alone cannot detect a source reference missing from *both*
translations. This gate checks static COM_JLMS_ calls in every component PHP
file against both locale catalogs for the corresponding Joomla client.
Computed keys and Mail Templates configured on a real site remain separate.
"""
from __future__ import annotations

import argparse
from pathlib import Path
import re
import sys
import tempfile

ROOT = Path(__file__).resolve().parents[1]
CLIENTS = ("site", "administrator")
LOCALES = ("it-IT", "en-GB")
TEXT_CALL = re.compile(
    r"""(?:\\?Joomla\\CMS\\Language\\)?Text\s*::\s*"""
    r"""(?:_|sprintf|plural|script)\s*\(\s*(['"])(COM_JLMS_[A-Z0-9_]+)\1""",
)
LITERAL = re.compile(r"^([A-Z][A-Z0-9_]+)\s*=")


def translated_keys(root: Path, client: str, locale: str) -> set[str]:
    directory = root / "component" / client / "language" / locale
    keys: set[str] = set()
    for file in ("com_jlms.ini", "com_jlms.sys.ini"):
        for line in (directory / file).read_text(encoding="utf-8-sig").splitlines():
            match = LITERAL.match(line)
            if match:
                keys.add(match.group(1))
    return keys


def source_keys(source: str) -> set[str]:
    return {match.group(2) for match in TEXT_CALL.finditer(source)}


def inspect(root: Path) -> tuple[list[str], dict]:
    failures: list[str] = []
    report: dict[str, dict] = {}
    for client in CLIENTS:
        basedir = root / "component" / client
        sources = list(basedir.rglob("*.php"))
        if not sources:
            failures.append(f"{client}: no PHP source files to inspect")
        discovered: dict[str, list[str]] = {}
        for filename in sources:
            for key in source_keys(filename.read_text(encoding="utf-8")):
                discovered.setdefault(key, []).append(str(filename.relative_to(root)))
        locales = {loc: translated_keys(root, client, loc) for loc in LOCALES}
        for key, paths in sorted(discovered.items()):
            for locale in LOCALES:
                if key not in locales[locale]:
                    failures.append(
                        f"{client}/{locale}: missing literal UI key {key}"
                        f" referenced by {paths[0]}"
                    )
        # Computed keys require a separate runtime/catalog exercise, not
        # silent inclusion in this static claim of coverage.
        dynamic_count = sum(
            len(re.findall(r"\bText\s*::\s*_\s*\(\s*(?!['\"])", p.read_text(encoding="utf-8")))
            for p in sources
        )
        report[client] = {
            "php_files": len(sources),
            "literal_keys": len(discovered),
            "references": sum(map(len, discovered.values())),
            "computed_text_sites": dynamic_count,
        }
    return failures, report


def self_test() -> None:
    assert source_keys("Text::_('COM_JLMS_HELLO')") == {"COM_JLMS_HELLO"}
    assert source_keys('Text::sprintf("COM_JLMS_WORLD", $value)') == {"COM_JLMS_WORLD"}
    assert source_keys(r"\Joomla\CMS\Language\Text::_('COM_JLMS_A')") == {"COM_JLMS_A"}
    assert not source_keys("Text::_(implode('_', ['COM', 'JLMS', 'HELLO']))")
    with tempfile.TemporaryDirectory() as d:
        root = Path(d)
        for client in CLIENTS:
            php = root / "component" / client / "tmpl" / "view.php"
            php.parent.mkdir(parents=True)
            php.write_text("<?php Text::_('COM_JLMS_HELLO');", encoding="utf-8")
            for locale in LOCALES:
                folder = root / "component" / client / "language" / locale
                folder.mkdir(parents=True)
                (folder / "com_jlms.ini").write_text(
                    'COM_JLMS_HELLO="Hello"\n', encoding="utf-8"
                )
                (folder / "com_jlms.sys.ini").write_text("", encoding="utf-8")
        errors, report = inspect(root)
        assert not errors and all(r["literal_keys"] == 1 for r in report.values())
        missing = root / "component/site/language/it-IT/com_jlms.ini"
        missing.write_text('COM_JLMS_DIFFERENT="No"\n', encoding="utf-8")
        errors, _ = inspect(root)
        assert len(errors) == 1 and "site/it-IT" in errors[0]


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--self-test", action="store_true")
    opts = parser.parse_args()
    if opts.self_test:
        self_test()
        print("CORE-06 source key negative fixtures: OK")
        return 0
    failures, report = inspect(ROOT)
    for msg in failures[:100]:
        print("CORE-06: " + msg, file=sys.stderr)
    print(f"CORE-06 distributed PHP literal key coverage: {report}; failures={len(failures)}")
    return 1 if failures else 0


if __name__ == "__main__":
    raise SystemExit(main())
