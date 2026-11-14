#!/usr/bin/env python3
"""Check the basic hygiene of README and the official versioned changelog."""
from pathlib import Path
import re
import sys

ROOT = Path(__file__).resolve().parents[1]
readme = (ROOT / "README.md").read_text(encoding="utf-8")
changelog = (ROOT / "CHANGELOG.md").read_text(encoding="utf-8")
problems = []

if readme.count("# JD Learn") != 1:
    problems.append("README: expected exactly one project title")
if re.search(r"(?im)^\*\*(?:post-merge|pr #|main 0\.)", readme):
    problems.append("README: chronological PR notes belong in CHANGELOG/history")
if len(readme) > 12000:
    problems.append("README: too long; move operational history to dedicated docs")

if len(re.findall(r"(?m)^# Changelog\s*$", changelog)) != 1:
    problems.append("CHANGELOG: expected exactly one title")
headings = re.findall(r"(?m)^## (\d+\.\d+\.\d+-dev)(?:\s|$)", changelog)
if len(headings) != len(set(headings)):
    problems.append("CHANGELOG: duplicate version headings")
parts = [tuple(map(int, version[:-4].split("."))) for version in headings]
if parts != sorted(parts, reverse=True):
    problems.append("CHANGELOG: versions are not in descending order")
if not headings:
    problems.append("CHANGELOG: no development version headings")

for problem in problems:
    print(f"ERROR: {problem}", file=sys.stderr)
if problems:
    sys.exit(1)
print(f"Documentation hygiene OK: {len(headings)} unique version entries")
