#!/usr/bin/env python3
"""Require each runtime pull request to update the canonical requirements CSV.

The test enforces traceability, NOT functional acceptance or PR semantics.
Run in a pull_request job with fetch-depth: 0.
"""
import argparse
import csv
import io
import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
REGISTRY = "docs/REQUIREMENTS_STATUS.csv"
RUNTIME_PREFIXES = ("component/", "libraries/", "plugins/", "packages/")
EXTRA_RUNTIME = {"tools/build.py", "tools/ci_plan.py"}

def changed_paths(base_ref: str) -> set[str]:
    output = subprocess.check_output(
        ["git", "diff", "--name-only", "--diff-filter=ACDMRT", f"{base_ref}...HEAD"],
        cwd=ROOT, text=True,
    )
    return set(output.splitlines())

def requirement_map(content: str) -> dict[str, str]:
    reader = csv.DictReader(io.StringIO(content))
    if not {"Requisito", "PR implementazione"}.issubset(reader.fieldnames or []):
        raise ValueError("Missing required columns in requirements registry")
    return {row["Requisito"]: row["PR implementazione"] for row in reader}

def check(pr_number: int, changed: set[str], base_csv: str, candidate_csv: str) -> tuple[bool, str]:
    runtime = sorted(
        p for p in changed
        if p.startswith(RUNTIME_PREFIXES) or p in EXTRA_RUNTIME
    )
    if not runtime:
        return True, "No runtime changes: no requirement implementation claimed"
    if REGISTRY not in changed:
        return False, f"Runtime PR #{pr_number} must update {REGISTRY}"
    original, current = requirement_map(base_csv), requirement_map(candidate_csv)
    newly_linked = []
    for key, text in current.items():
        now = set(filter(None, text.split(";")))
        previous = set(filter(None, original.get(key, "").split(";")))
        if str(pr_number) in now - previous:
            newly_linked.append(key)
    if not newly_linked:
        return False, (
            f"Runtime PR #{pr_number} changed {REGISTRY} but did not add "
            "its PR number to any requirement's 'PR implementazione' column"
        )
    return True, f"PR #{pr_number} linked to: {', '.join(newly_linked)}; validation does not establish functional coverage"

def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--base-ref", required=True, help="Remote tracking branch, e.g. origin/main")
    parser.add_argument("--pr", required=True, type=int)
    args = parser.parse_args()
    changed = changed_paths(args.base_ref)
    if not any(p.startswith(RUNTIME_PREFIXES) or p in EXTRA_RUNTIME for p in changed):
        print("No runtime changes: no requirement implementation claimed")
        return 0
    base_csv = subprocess.check_output(
        ["git", "show", f"{args.base_ref}:{REGISTRY}"], cwd=ROOT, text=True
    )
    current_csv = (ROOT / REGISTRY).read_text(encoding="utf-8-sig")
    passed, message = check(args.pr, changed, base_csv.lstrip("\ufeff"), current_csv)
    print(("PASS: " if passed else "ERROR: ") + message)
    return 0 if passed else 1

if __name__ == "__main__":
    raise SystemExit(main())
