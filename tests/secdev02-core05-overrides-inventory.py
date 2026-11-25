#!/usr/bin/env python3
"""CORE-05: verify every shipped Joomla view has a registered native override.

This inventories *all* site/admin MVC views, not just known frontend cards.
It proves source/packaging structure and detects unreviewed additions; actual
theme overrides, responsive layouts and custom modules still need site QA.
"""
from __future__ import annotations

import argparse
import json
from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[1]
FILE = "docs/SECDEV_02_CORE05_VIEW_OVERRIDE_INVENTORY_2026-11-25.json"


def inspect(root: Path, manifest: dict) -> tuple[list[str], dict]:
    errors: list[str] = []
    clients = manifest.get("clients", {})
    if manifest.get("schema_version") != 1 or set(clients) != {"site", "administrator"}:
        errors.append("CORE-05 missing canonical site/admin override inventory")
        return errors, {}
    report: dict[str, int] = {}
    for client in ("site", "administrator"):
        base = root / "component" / client
        sources = base / "tmpl"
        views = base / "src" / "View"
        rows = clients[client]
        if not isinstance(rows, list):
            errors.append(f"CORE-05 invalid inventory for {client}")
            continue
        expected = [row.get("view") for row in rows]
        actual = sorted(p.name for p in sources.iterdir() if p.is_dir())
        if sorted(expected) != actual or len(expected) != len(set(expected)):
            errors.append(
                f"CORE-05 {client} view drift: not registered={sorted(set(actual)-set(expected))}"
                f"; missing={sorted(set(expected)-set(actual))}; duplicates={len(expected)-len(set(expected))}"
            )
        mvc = sorted(p.name.lower() for p in views.iterdir() if p.is_dir() and (p / "HtmlView.php").exists())
        if mvc != actual:
            errors.append(f"CORE-05 {client} MVC View/HTML layout mismatch: {set(mvc) ^ set(actual)}")
        for row in rows:
            view, layout = row.get("view"), row.get("layout")
            if not isinstance(view, str) or not isinstance(layout, str):
                errors.append(f"CORE-05 {client} malformed inventory entry")
                continue
            if layout not in {"default.php", "edit.php"}:
                errors.append(f"CORE-05 {client}/{view} invalid native entry layout")
            elif not (sources / view / layout).is_file():
                errors.append(f"CORE-05 {client}/{view} missing {layout} override source")
            elif not (views / view.capitalize() / "HtmlView.php").is_file():
                errors.append(f"CORE-05 {client}/{view} missing Joomla MVC HtmlView")
        report[client] = len(actual)
    return errors, report


def self_test() -> None:
    from tempfile import TemporaryDirectory

    with TemporaryDirectory() as folder:
        root = Path(folder)
        manifest = {"schema_version": 1, "clients": {
            "site": [{"view": "courses", "layout": "default.php"}],
            "administrator": [{"view": "course", "layout": "edit.php"}],
        }}
        for client, view, name in (
            ("site", "courses", "default.php"), ("administrator", "course", "edit.php"),
        ):
            tmpl = root / "component" / client / "tmpl" / view
            mvc = root / "component" / client / "src" / "View" / view.capitalize()
            tmpl.mkdir(parents=True)
            mvc.mkdir(parents=True)
            (tmpl / name).write_text("<?php", encoding="utf-8")
            (mvc / "HtmlView.php").write_text("<?php", encoding="utf-8")
        assert inspect(root, manifest)[0] == []
        (root / "component/site/tmpl/extra").mkdir()
        assert any("view drift" in e for e in inspect(root, manifest)[0])
        (root / "component/site/tmpl/extra").rmdir()
        (root / "component/administrator/tmpl/course/edit.php").unlink()
        assert any("missing edit.php" in e for e in inspect(root, manifest)[0])


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    if args.self_test:
        self_test()
        print("CORE-05 native override inventory negative fixtures: OK")
        return 0
    manifest = json.loads((ROOT / FILE).read_text(encoding="utf-8"))
    errors, report = inspect(ROOT, manifest)
    for error in errors:
        print(error, file=sys.stderr)
    print(f"CORE-05 Joomla overrides: {report}, discrepancies={len(errors)}")
    return 1 if errors else 0


if __name__ == "__main__":
    raise SystemExit(main())
