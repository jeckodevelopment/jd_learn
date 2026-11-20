#!/usr/bin/env python3
"""SEC-P2 review gate: repository runtime secrets and GitHub Actions supply chain.

This is a local, deterministic *known-pattern* scan; it does not replace remote
advisory SCA, Git history secret scanning, or runtime/hosting inspection.
No matched token or path is emitted.
"""
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
PATTERNS = (
    re.compile(rb"-----BEGIN (?:RSA |EC |OPENSSH |ENCRYPTED )?PRIVATE KEY-----"),
    re.compile(rb"\b(?:AKIA|ASIA)[0-9A-Z]{16}\b"),
    re.compile(rb"\bgh[pousr]_[A-Za-z0-9]{36,}\b"),
    re.compile(rb"\bgithub_pat_[A-Za-z0-9_]{40,}\b"),
)
# Only shipped source and workflow definitions. Test fixtures are deliberately
# separate from secrets detection to permit negative security regression tests.
RUNTIME = ("component", "plugins", "libraries", "packages")
RUNTIME_EXTENSIONS = {".php", ".xml", ".js", ".json", ".ini", ".sql", ".html", ".css"}
USES = re.compile(r"(?m)^\s*-?\s*uses:\s*([^\s#]+)")
SHA = re.compile(r"^[0-9a-f]{40}$")
IMAGE = re.compile(r"(?m)^\s*(?:image|container):\s*(?!\$\{\{)([^\s#]+)")

def inspect(root: Path) -> tuple[int, int, int]:
    checked = 0
    findings = 0
    actions = 0
    for dirname in RUNTIME:
        directory = root / dirname
        if not directory.exists():
            continue
        for path in sorted(directory.rglob("*")):
            if not path.is_file() or path.suffix.lower() not in RUNTIME_EXTENSIONS:
                continue
            checked += 1
            contents = path.read_bytes()
            if any(regex.search(contents) for regex in PATTERNS):
                findings += 1
    workflows = root / ".github" / "workflows"
    if workflows.is_dir():
        for path in sorted([*workflows.glob("*.yml"), *workflows.glob("*.yaml")]):
            source = path.read_text(encoding="utf-8")
            checked += 1
            for match in USES.finditer(source):
                action = match.group(1)
                if action.startswith("./"):
                    continue
                actions += 1
                if "@" not in action or not SHA.fullmatch(action.rsplit("@", 1)[-1]):
                    findings += 1
            # CI uses versioned container tags, not mutable "latest" tags.
            for value in IMAGE.findall(source):
                if value.endswith(":latest"):
                    findings += 1
    return checked, actions, findings

def main() -> None:
    checked, actions, findings = inspect(ROOT)
    print(f"SEC-P2 source/supply-chain gate: {checked} files, {actions} referenced actions, {findings} findings")
    if checked < 300 or actions < 1 or findings:
        raise SystemExit(2)

if __name__ == "__main__":
    main()
