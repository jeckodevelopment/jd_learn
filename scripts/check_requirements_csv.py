#!/usr/bin/env python3
"""Validate the JD Learn requirements/PR traceability CSV (issue #259)."""
import csv
import re
import sys
from pathlib import Path

PATH = Path(__file__).resolve().parents[1] / "docs" / "REQUIREMENTS_STATUS.csv"
REQ = re.compile(r"^[A-Z][A-Z0-9]*-[0-9]{2,3}$")
PRS = re.compile(r"^[1-9][0-9]*(;[1-9][0-9]*)*$")


def main() -> int:
    errors = []
    with PATH.open("r", encoding="utf-8-sig", newline="") as stream:
        reader = csv.reader(stream, strict=True)
        header = next(reader, [])
        if len(header) != len(set(header)):
            errors.append("Duplicate column names")
        for required in ("Requisito", "PR implementazione"):
            if required not in header:
                errors.append(f"Missing column: {required}")
        if errors:
            print("\n".join(errors), file=sys.stderr)
            return 1
        id_col, pr_col = header.index("Requisito"), header.index("PR implementazione")
        seen = set()
        count = 0
        for line, cells in enumerate(reader, start=2):
            count += 1
            if len(cells) != len(header):
                errors.append(f"CSV record {line}: expected {len(header)} columns, got {len(cells)}")
                continue
            requirement = cells[id_col].strip()
            if not REQ.fullmatch(requirement):
                errors.append(f"CSV record {line}: malformed requirement ID {requirement!r}")
            if requirement in seen:
                errors.append(f"CSV record {line}: duplicate requirement {requirement}")
            seen.add(requirement)
            prs = cells[pr_col].strip()
            if prs and not PRS.fullmatch(prs):
                errors.append(f"CSV record {line} ({requirement}): expected PR numbers separated by semicolons")
            elif prs:
                numbers = prs.split(";")
                if len(numbers) != len(set(numbers)):
                    errors.append(f"CSV record {line} ({requirement}): duplicate PR number")
        if not count:
            errors.append("Requirements table is empty")
    if errors:
        print("\n".join(errors), file=sys.stderr)
        return 1
    print(f"Validated {count} requirements, {len(header)} columns. Unmapped PR cells allowed.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
