#!/usr/bin/env python3
"""SEC-P2: fail closed on unsafe distributable Joomla ZIP members.

All nested child ZIPs share the same decompression and entry budget. No paths,
credentials or matching payloads are printed to CI logs.
"""
from __future__ import annotations

import argparse
import io
import re
from pathlib import Path
import stat
import sys
import zipfile

MAX_MEMBER = 16 * 1024 * 1024
MAX_TOTAL = 160 * 1024 * 1024
MAX_ENTRIES = 20000
MAX_DEPTH = 2
FORBIDDEN = re.compile(r"(^|/)(?:\.env(?:\.[^/]*)?|\.git|\.ssh|id_rsa|id_ed25519|[^/]+\.(?:pem|key|p12|pfx))$", re.I)
SECRET = [
    re.compile(rb"-----BEGIN (?:RSA |EC |OPENSSH |ENCRYPTED )?PRIVATE KEY-----"),
    re.compile(rb"\b(?:AKIA|ASIA)[0-9A-Z]{16}\b"),
    re.compile(rb"\bgh[pousr]_[a-zA-Z0-9]{36,}\b"),
    re.compile(rb"\bgithub_pat_[a-zA-Z0-9_]{40,}\b"),
]


def scan(data: bytes, depth: int = 0, budget: dict | None = None) -> tuple[int, int]:
    if depth > MAX_DEPTH:
        raise ValueError("Nested ZIP depth exceeded")
    if budget is None:
        budget = {"bytes": 0, "entries": 0}
    findings = 0
    members = 0
    with zipfile.ZipFile(io.BytesIO(data)) as archive:
        seen = set()
        for item in archive.infolist():
            if item.is_dir():
                continue
            members += 1
            budget["entries"] += 1
            if budget["entries"] > MAX_ENTRIES:
                raise ValueError("Archive entry count exceeded")
            name = item.filename.replace("\\", "/")
            parts = name.split("/")
            # Absolute paths, drive prefixes, traversal, NUL, ambiguous slashes
            # and case-folded collisions differ across extraction platforms.
            folded = name.casefold()
            if (name.startswith("/") or re.match(r"^[A-Za-z]:", name) or
                    any(part in (".", "..", "") for part in parts) or
                    "\x00" in name or folded in seen):
                findings += 1
                continue
            seen.add(folded)
            # Symlinks and non-regular UNIX nodes can escape target directories.
            mode = (item.external_attr >> 16) & 0xFFFF
            kind = stat.S_IFMT(mode)
            if kind not in (0, stat.S_IFREG):
                findings += 1
                continue
            if item.flag_bits & 1:
                findings += 1
                continue
            if FORBIDDEN.search(name):
                findings += 1
                continue
            budget["bytes"] += item.file_size
            if item.file_size > MAX_MEMBER or budget["bytes"] > MAX_TOTAL:
                raise ValueError("Archive expanded size limits exceeded")
            # Validate stream CRC via read and avoid trusting the header alone.
            content = archive.read(item)
            if name.lower().endswith(".zip"):
                nested_findings, nested_members = scan(content, depth + 1, budget)
                findings += nested_findings
                members += nested_members
            elif any(regex.search(content) for regex in SECRET):
                findings += 1
    return findings, members


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("package", type=Path)
    args = parser.parse_args()
    try:
        findings, members = scan(args.package.read_bytes())
    except (OSError, zipfile.BadZipFile, ValueError, RuntimeError, NotImplementedError):
        print("SEC-P2 package inspection failed (invalid or oversized archive).", file=sys.stderr)
        return 3
    print(f"SEC-P2: scanned {members} package members; findings: {findings}")
    return 2 if findings else 0


if __name__ == "__main__":
    sys.exit(main())
