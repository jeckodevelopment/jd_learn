#!/usr/bin/env python3
"""CORE-06: bilingual Joomla catalog integrity, with negative fixtures.

Checks are per client (site/administrator), not an artificial requirement to
duplicate backend strings in the public site catalog. UI/error/email/template
runtime coverage is a separate acceptance criterion.
"""
from __future__ import annotations

from pathlib import Path
import argparse
import re
import sys
import tempfile

ROOT = Path(__file__).resolve().parents[1]
CLIENTS = ("site", "administrator")
LOCALES = ("it-IT", "en-GB")
CATALOGS = ("com_jlms.ini", "com_jlms.sys.ini")
KEY = re.compile(r"^([A-Z][A-Z0-9_]+)=(.*)$")
FORMAT = re.compile(r"%(?:\d+\$)?[-+ #0]*(?:\d+|\*)?(?:\.(?:\d+|\*))?[bcdeEfFgGosuxX]")
PLACEHOLDER = re.compile(r"\{[A-Za-z][A-Za-z0-9_]*\}")
# Disallow unescaped quote delimiters and dangling escapes inside Joomla INI values.
QUOTED_VALUE = re.compile(r'"(?:[^"\\]|\\.)*"')


def parse(contents: str, name: str) -> tuple[dict[str, str], list[str]]:
    result: dict[str, str] = {}
    errors: list[str] = []
    for line_number, line in enumerate(contents.splitlines(), start=1):
        if not line.strip() or line.lstrip().startswith((";", "#")):
            continue
        match = KEY.fullmatch(line.strip())
        if not match:
            errors.append(f"{name}:{line_number}: malformed catalog entry")
            continue
        key, raw = match.groups()
        if key in result:
            errors.append(f"{name}:{line_number}: duplicate key {key}")
        elif not QUOTED_VALUE.fullmatch(raw) or len(raw) < 3:
            errors.append(f"{name}:{line_number}: malformed quoted value {key}")
        elif not raw[1:-1].strip():
            errors.append(f"{name}:{line_number}: blank translation {key}")
        result[key] = raw[1:-1]
    return result, errors


def signature(value: str) -> tuple[tuple[str, ...], tuple[str, ...]]:
    without_escaped_percent = value.replace("%%", "")
    formats = tuple(FORMAT.findall(without_escaped_percent))
    placeholders = tuple(sorted(PLACEHOLDER.findall(value)))
    return formats, placeholders


def audit(root: Path) -> tuple[list[str], dict[str, dict[str, int]]]:
    issues: list[str] = []
    totals: dict[str, dict[str, int]] = {}
    for client in CLIENTS:
        for filename in CATALOGS:
            trans: dict[str, dict[str, str]] = {}
            for locale in LOCALES:
                path = root / "component" / client / "language" / locale / filename
                try:
                    content = path.read_text(encoding="utf-8-sig")
                except OSError:
                    issues.append(f"Missing language catalog: {path.relative_to(root)}")
                    continue
                catalog, errors = parse(content, path.relative_to(root).as_posix())
                trans[locale] = catalog
                issues.extend(errors)
            if len(trans) != len(LOCALES):
                continue
            italian, english = trans["it-IT"], trans["en-GB"]
            for key in sorted(set(italian) - set(english)):
                issues.append(f"{client}/{filename}: missing en-GB key {key}")
            for key in sorted(set(english) - set(italian)):
                issues.append(f"{client}/{filename}: missing it-IT key {key}")
            for key in sorted(set(english) & set(italian)):
                if signature(italian[key]) != signature(english[key]):
                    issues.append(f"{client}/{filename}: printf/template placeholders differ for {key}")
            totals[f"{client}/{filename}"] = {
                "it_IT": len(italian),
                "en_GB": len(english),
                "paired": len(set(italian) & set(english)),
            }
    return issues, totals


def self_test() -> None:
    good = 'COM_JLMS_EXAMPLE="Hello %1$s, {NAME}!"\nCOM_JLMS_SECOND="Saved"\n'
    parsed, errors = parse(good, "fixture")
    assert not errors and len(parsed) == 2
    assert signature("Ciao %1$s, {NAME}!") == signature("Hello %1$s, {NAME}!")
    assert signature("A %s {NAME}") != signature("B %d {NAME}")
    assert signature("A {USER}") != signature("A {NAME}")
    assert parse(good + 'COM_JLMS_SECOND="Second"\n', "fixture")[1]
    assert parse('COM_JLMS_MISSING="  "\n', "fixture")[1]
    assert parse('COM_JLMS_BROKEN="A "raw" quote"\n', "fixture")[1]
    assert parse('COM_JLMS_BROKEN="Value" trailing"\n', "fixture")[1]
    assert not parse(r'COM_JLMS_VALID="An escaped \"quote\" is valid"' + "\n", "fixture")[1]
    with tempfile.TemporaryDirectory(prefix="jdlearn-locale-") as folder:
        root = Path(folder)
        for client in CLIENTS:
            for loc in LOCALES:
                destination = root / "component" / client / "language" / loc
                destination.mkdir(parents=True)
                for file in CATALOGS:
                    (destination / file).write_text(good, encoding="utf-8")
        problems, totals = audit(root)
        assert not problems and len(totals) == 4
        target = root / "component/site/language/en-GB/com_jlms.ini"
        target.write_text(good.replace("COM_JLMS_SECOND", "COM_JLMS_EXTRA"), encoding="utf-8")
        assert any("missing en-GB" in p for p in audit(root)[0])
        assert any("missing it-IT" in p for p in audit(root)[0])
        target.write_text(good.replace("Hello %1$s", "Hello %d"), encoding="utf-8")
        assert any("placeholders differ" in p for p in audit(root)[0])


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    if args.self_test:
        self_test()
        print("CORE-06 bilingual locale catalog negative fixtures: OK")
        return 0
    failures, totals = audit(ROOT)
    for error in failures[:50]:
        print(f"CORE-06: {error}", file=sys.stderr)
    print(f"CORE-06 Joomla IT/EN catalog integrity: {totals}; failures={len(failures)}")
    return 1 if failures else 0


if __name__ == "__main__":
    raise SystemExit(main())
