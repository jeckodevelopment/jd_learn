#!/usr/bin/env python3
"""CORE-05/20: exhaustive shipped Joomla override surface inventory.

A template/file-level regression gate; browser rendering of third-party
overrides remains a separate acceptance activity, not a CI sign-off.
"""
from __future__ import annotations

import argparse
import json
from pathlib import Path
import re
import sys
import tempfile

ROOT = Path(__file__).resolve().parents[1]
INVENTORY = "docs/SECDEV_02_CORE05_LAYOUT_INVENTORY_1.0.0.json"
GUARD = re.compile(r"""\\?defined\s*\(\s*['"]_JEXEC['"]\s*\)\s*or\s*die""")
CLIENTS = ("site", "administrator")


def check(root: Path, inventory: dict) -> tuple[list[str], dict]:
    errors: list[str] = []
    if inventory.get("schema_version") != 1:
        errors.append("invalid CORE-05 template inventory schema")
    result = {}
    for client in CLIENTS:
        folder = root / "component" / client / "tmpl"
        actual = sorted(path.relative_to(folder).as_posix()
                        for path in folder.rglob("*.php"))
        approved = inventory.get(client)
        if not isinstance(approved, list) or len(approved) != len(set(approved)):
            errors.append(f"{client}: missing or duplicated override inventory")
            approved = []
        expected = sorted(approved)
        if actual != expected:
            errors.append(
                f"{client}: layout inventory drift "
                f"new={sorted(set(actual)-set(expected))}, "
                f"missing={sorted(set(expected)-set(actual))}"
            )
        for rel in actual:
            source = (folder / rel).read_text(encoding="utf-8")
            if not source.lstrip().startswith("<?php"):
                errors.append(f"{client}/{rel}: Joomla layout lacks PHP entry")
            if not GUARD.search(source):
                errors.append(f"{client}/{rel}: Joomla _JEXEC direct-access guard missing")
        result[client] = len(actual)
    module_root = root / "modules"
    actual_modules = (
        sorted(p.relative_to(module_root).as_posix()
               for p in module_root.rglob("tmpl/*.php")) if module_root.exists() else []
    )
    if inventory.get("modules") != actual_modules:
        errors.append("module layout inventory drift (new/removed shipped modules)")
    result["modules"] = len(actual_modules)
    if not (root / "docs/FRONTEND_CSS.md").is_file():
        errors.append("CSS and Joomla override contract missing")
    return errors, result


def self_test() -> None:
    with tempfile.TemporaryDirectory() as tmp:
        root = Path(tmp)
        approved = {
            "schema_version": 1, "site": ["example/default.php"],
            "administrator": ["dashboard/default.php"], "modules": [],
        }
        for client, paths in (
            ("site", ["example/default.php"]),
            ("administrator", ["dashboard/default.php"]),
        ):
            path = root / "component" / client / "tmpl" / paths[0]
            path.parent.mkdir(parents=True)
            path.write_text("<?php \\defined('_JEXEC') or die;", encoding="utf-8")
        docs = root / "docs"
        docs.mkdir()
        (docs / "FRONTEND_CSS.md").write_text("override contract", encoding="utf-8")
        assert not check(root, approved)[0]
        (root / "component/site/tmpl/example/extra.php").write_text(
            "<?php \\defined('_JEXEC') or die;", encoding="utf-8"
        )
        assert any("inventory drift" in x for x in check(root, approved)[0])
        (root / "component/site/tmpl/example/extra.php").unlink()
        (root / "component/site/tmpl/example/default.php").write_text(
            "<?php echo 1;", encoding="utf-8"
        )
        assert any("_JEXEC" in x for x in check(root, approved)[0])
        approved["administrator"].append("dashboard/default.php")
        assert any("duplicated" in x for x in check(root, approved)[0])


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    if args.self_test:
        self_test()
        print("CORE-05/20 exhaustive Joomla layout negative fixtures: OK")
        return 0
    inv = json.loads((ROOT / INVENTORY).read_text(encoding="utf-8"))
    errors, report = check(ROOT, inv)
    for err in errors:
        print("CORE-05/20: " + err, file=sys.stderr)
    print(json.dumps(report, sort_keys=True))
    return 1 if errors else 0


if __name__ == "__main__":
    raise SystemExit(main())
