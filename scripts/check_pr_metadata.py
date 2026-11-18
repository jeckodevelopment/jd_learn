#!/usr/bin/env python3
"""Validate pull request content and repository metadata."""
from __future__ import annotations

import argparse
import json
import os
import re
import subprocess
from pathlib import Path

PRIORITIES = {"P0", "P1", "P2"}
REQUIRED_SECTIONS = (
    "Cosa è stato fatto", "Risolve un bug?", "Requisiti implementati",
    "Documentazione allineata", "Collaudo manuale Joomla",
    "Comportamenti da testare", "Controlli automatici",
)
DOC_FILES = {"README.md", "CHANGELOG.md", "AGENTS.md", "LICENSE", ".github/pull_request_template.md"}
DOC_PREFIXES = ("docs/", "component/administrator/docs/")


def extract_sections(body: str) -> dict[str, str]:
    sections: dict[str, str] = {}
    current = None
    lines: list[str] = []
    for line in body.splitlines():
        match = re.match(r"^##\s+(.+?)\s*$", line)
        if match:
            if current is not None:
                sections[current] = "\n".join(lines).strip()
            current, lines = match.group(1), []
        elif current is not None:
            lines.append(line)
    if current is not None:
        sections[current] = "\n".join(lines).strip()
    return sections


def visible(text: str) -> str:
    return re.sub(r"<!--.*?-->", "", text, flags=re.S).strip()


def is_documentation_only(paths: set[str]) -> bool:
    return bool(paths) and all(path in DOC_FILES or path.startswith(DOC_PREFIXES) for path in paths)


def validate(body: str, milestone: str | None, labels: list[str], paths: set[str]) -> list[str]:
    errors: list[str] = []
    sections = extract_sections(body)
    for title in REQUIRED_SECTIONS:
        if title not in sections:
            errors.append(f"PR body is missing the '## {title}' section")
        else:
            lines = [line.strip() for line in visible(sections[title]).splitlines()]
            filled = any(line and not re.fullmatch(r"(?:[-*]\\s*)?\\[[^\\]]+\\]", line) for line in lines)
            if not filled:
                errors.append(f"PR section '{title}' is empty or still has a placeholder")
    bug = visible(sections.get("Risolve un bug?", ""))
    selected = re.findall(r"(?im)^\s*-\s*\[[xX]\]\s*(Sì|No|Non applicabile)\b", bug)
    if len(selected) != 1:
        errors.append("Select exactly one answer in 'Risolve un bug?'")
    requirements = visible(sections.get("Requisiti implementati", ""))
    if not re.search(r"\bN/A\b|\b[A-Z][A-Z0-9]*(?:-[A-Z0-9]+)+\b", requirements):
        errors.append("List requirement IDs, or N/A with a reason")
    manual = visible(sections.get("Collaudo manuale Joomla", ""))
    if not re.search(r"(?im)^\s*Stato:\s*(In attesa|Non applicabile|Eseguito)\b", manual):
        errors.append("Set manual Joomla test status to In attesa, Non applicabile, or Eseguito")
    manual_cases = manual.split("Casi da provare", 1)[-1]
    case_items = [
        line.strip()[2:].strip() for line in manual_cases.splitlines()
        if re.match(r"^\s*-\s+", line)
    ]
    if not any(item and not re.fullmatch(r"\[[^\]]+\]", item) for item in case_items):
        if not re.search(r"(?im)^\s*(?:-\s*)?N/A\b.+", manual_cases):
            errors.append("List manual Joomla cases to run later, or N/A with a reason")
    behaviors = visible(sections.get("Comportamenti da testare", ""))
    behavior_items = [
        line.strip()[2:].strip() for line in behaviors.splitlines()
        if re.match(r"^\s*-\s+", line)
    ]
    if not any(item and not re.fullmatch(r"\[[^\]]+\]", item) for item in behavior_items):
        errors.append("List concrete behaviors and expected results to test")
    documentation = visible(sections.get("Documentazione allineata", ""))
    if not re.search(r"(?im)^\s*-\s*\[[xX]\]", documentation):
        errors.append("Select the documentation updates completed, or N/A with a reason")
    # Pending manual acceptance is intentionally allowed; it is not a merge blocker.
    if milestone != "1.0.0":
        errors.append("PR must target milestone 1.0.0 for the current stable baseline")
    priorities = [label for label in labels if label in PRIORITIES]
    if len(priorities) != 1:
        errors.append("Apply exactly one priority label: P0, P1, or P2")
    if is_documentation_only(paths) and "documentation" not in labels:
        errors.append("Documentation-only PRs must have the documentation label")
    return errors


def changed_paths(base_ref: str) -> set[str]:
    output = subprocess.check_output(
        ["git", "diff", "--name-only", "--diff-filter=ACDMRT", f"{base_ref}...HEAD"],
        text=True,
    )
    return set(output.splitlines())


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--base-ref", required=True, help="Fetched base branch, e.g. origin/main")
    args = parser.parse_args()
    event = json.loads(Path(os.environ["GITHUB_EVENT_PATH"]).read_text(encoding="utf-8"))
    pr = event.get("pull_request")
    if not pr:
        print("No pull request event; metadata validation skipped.")
        return 0
    labels = [label["name"] for label in pr.get("labels", [])]
    errors = validate(pr.get("body") or "", (pr.get("milestone") or {}).get("title"),
                      labels, changed_paths(args.base_ref))
    if errors:
        raise SystemExit("\n".join(f"::error::{error}" for error in errors))
    print("PR content, milestone, priority and documentation label are valid.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
