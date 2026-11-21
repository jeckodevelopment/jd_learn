#!/usr/bin/env python3
"""SEC-P3: reject unclassified JD Learn tables and unreviewed retention metadata."""
from __future__ import annotations
import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "component/administrator/sql/install.mysql.utf8mb4.sql"
INVENTORY = ROOT / "docs/SEC_P3_DATA_INVENTORY_1.0.0.json"
FIELDS = ("processing", "disposition", "retention", "accountable")
GROUPS = {"editorial", "learner", "certificate", "asynchronous", "email"}


def validate(ddl: str, inventory: dict) -> None:
    actual = re.findall(r"CREATE TABLE IF NOT EXISTS\s+`(#__jlms_[a-z0-9_]+)`", ddl, re.I)
    if not actual or len(set(actual)) != len(actual):
        raise ValueError("Missing or duplicate JD Learn DDL table")
    rows = inventory.get("rows")
    if inventory.get("schema") != 1 or inventory.get("baseline") != "1.0.0" or not isinstance(rows, list):
        raise ValueError("Invalid SEC-P3 inventory schema or baseline")
    documented = [row.get("table") for row in rows if isinstance(row, dict)]
    if len(documented) != len(set(documented)) or set(documented) != set(actual):
        raise ValueError("SEC-P3 table inventory does not match distributable install schema")
    for row in rows:
        if row.get("group") not in GROUPS:
            raise ValueError("Unclassified SEC-P3 data group")
        for field in FIELDS:
            value = row.get(field)
            if not isinstance(value, str) or len(value.strip()) < 12 or value.strip().lower() in {"todo", "tbd", "unknown"}:
                raise ValueError("Incomplete SEC-P3 data policy for " + row["table"])


if __name__ == "__main__":
    ddl = SOURCE.read_text(encoding="utf-8")
    inv = json.loads(INVENTORY.read_text(encoding="utf-8"))
    validate(ddl, inv)
    # Fixtures must demonstrate fail-closed behaviour, not merely a happy-path check.
    for bad_ddl, bad_inventory in (
        (ddl.replace("CREATE TABLE IF NOT EXISTS", "CREATE TABLE IF NOT EXISTS `#__jlms_unreviewed` (\n `id` INT\n) ENGINE=InnoDB;\nCREATE TABLE IF NOT EXISTS", 1), inv),
        (ddl, {**inv, "rows": inv["rows"][:-1]}),
        (ddl, {**inv, "rows": [{**inv["rows"][0], "retention": "TBD"}, *inv["rows"][1:]]}),
    ):
        try:
            validate(bad_ddl, bad_inventory)
        except ValueError:
            pass
        else:
            raise AssertionError("SEC-P3 gate accepted an unreviewed inventory change")
    print("SEC-P3 complete schema inventory and fail-closed negative fixtures OK")
