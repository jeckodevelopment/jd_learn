#!/usr/bin/env python3
"""SEC-P2: inspect every historical distributable source blob for known secrets.

Only tracked source/workflow paths are examined. No matched contents, filenames
or Git objects are printed. This is a known-signature scan, not a guarantee
that arbitrary credentials were never committed.
"""
from __future__ import annotations

from pathlib import Path
import re
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[1]
SOURCE_ROOTS = ("component", "plugins", "libraries", "packages")
SOURCE_SUFFIXES = {".php", ".xml", ".js", ".json", ".ini", ".sql", ".html", ".css"}
WORKFLOW_SUFFIXES = {".yaml", ".yml"}
SIGNATURES = (
    re.compile(rb"-----BEGIN (?:RSA |EC |OPENSSH |ENCRYPTED )?PRIVATE KEY-----"),
    re.compile(rb"\b(?:AKIA|ASIA)[0-9A-Z]{16}\b"),
    re.compile(rb"\bgh[pousr]_[A-Za-z0-9]{36,}\b"),
    re.compile(rb"\bgithub_pat_[A-Za-z0-9_]{40,}\b"),
)
MAX_BLOB_BYTES = 8 * 1024 * 1024


def is_target(path: str) -> bool:
    path = path.replace("\\", "/")
    parts = path.split("/")
    if len(parts) < 2:
        return False
    if parts[:2] == [".github", "workflows"]:
        return Path(path).suffix.lower() in WORKFLOW_SUFFIXES
    return parts[0] in SOURCE_ROOTS and Path(path).suffix.lower() in SOURCE_SUFFIXES


def git(root: Path, *args: str) -> bytes:
    return subprocess.check_output(["git", "-C", str(root), *args], stderr=subprocess.DEVNULL)


def scan(root: Path) -> tuple[int, int, int]:
    if not (root / ".git").exists():
        raise ValueError("Full Git history is required for SEC-P2 scanning")
    # Revisions across all local and fetched remote refs, including deleted
    # source paths reachable through commits. CI checkout uses fetch-depth: 0.
    objects = git(root, "rev-list", "--objects", "--all", "--",
                  *SOURCE_ROOTS, ".github/workflows")
    selected: set[str] = set()
    for raw in objects.splitlines():
        blob, separator, path = raw.partition(b" ")
        if separator and is_target(path.decode("utf-8", errors="replace")):
            selected.add(blob.decode("ascii"))
    if not selected:
        raise ValueError("No historical runtime source blobs found")
    process = subprocess.Popen(
        ["git", "-C", str(root), "cat-file", "--batch"],
        stdin=subprocess.PIPE,
        stdout=subprocess.PIPE,
        stderr=subprocess.DEVNULL,
    )
    checked = 0
    oversized = 0
    findings = 0
    try:
        assert process.stdin is not None and process.stdout is not None
        # Use a temporary input file to avoid deadlocks when many objects
        # exceed the pipe input buffer; read the output stream sequentially.
        for oid in sorted(selected):
            process.stdin.write((oid + "\n").encode("ascii"))
            process.stdin.flush()
            header = process.stdout.readline().decode("ascii", errors="replace").strip().split()
            if len(header) != 3 or header[1] != "blob":
                raise ValueError("Git history contains an unreadable blob")
            size = int(header[2])
            if size < 0 or size > MAX_BLOB_BYTES:
                oversized += 1
                # Drain and fail closed without exposing content.
                remaining = size + 1
                while remaining:
                    chunk = process.stdout.read(min(65536, remaining))
                    if not chunk:
                        raise ValueError("Truncated Git blob")
                    remaining -= len(chunk)
                continue
            data = process.stdout.read(size)
            if len(data) != size or process.stdout.read(1) != b"\n":
                raise ValueError("Invalid Git blob stream")
            checked += 1
            if any(pattern.search(data) for pattern in SIGNATURES):
                findings += 1
    finally:
        if process.stdin:
            process.stdin.close()
        if process.stdout:
            process.stdout.close()
        process.wait(timeout=20)
    return checked, findings, oversized


def main() -> int:
    try:
        checked, findings, oversized = scan(ROOT)
    except (OSError, subprocess.CalledProcessError, ValueError, TimeoutError):
        print("SEC-P2 history scan incomplete; no clearance granted.", file=sys.stderr)
        return 2
    print(f"SEC-P2 history: {checked} source blobs reviewed; "
          f"{findings} signature findings; {oversized} oversized/unreviewed")
    if findings or oversized:
        return 2
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
