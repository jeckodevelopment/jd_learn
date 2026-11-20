#!/usr/bin/env python3
"""SEC-P2: fail on sensitive files or known credential forms in distributable ZIPs.

Read-only, deterministic, no secret contents or member names printed to logs.
Scans nested Joomla child ZIPs, with bounds to prevent decompression abuse.
"""
from __future__ import annotations
import argparse
import io
import re
from pathlib import Path
import sys
import zipfile

MAX_MEMBER = 16 * 1024 * 1024
MAX_TOTAL = 160 * 1024 * 1024
FORBIDDEN = re.compile(r"(^|/)(?:\.env(?:\.[^/]*)?|\.git|\.ssh|id_rsa|id_ed25519|[^/]+\.(?:pem|key|p12|pfx))$", re.I)
SECRET = [
    re.compile(rb"-----BEGIN (?:RSA |EC |OPENSSH |ENCRYPTED )?PRIVATE KEY-----"),
    re.compile(rb"\b(?:AKIA|ASIA)[0-9A-Z]{16}\b"),
    re.compile(rb"\bgh[pousr]_[a-zA-Z0-9]{36,}\b"),
    re.compile(rb"\bgithub_pat_[a-zA-Z0-9_]{40,}\b"),
]

def scan(data: bytes, depth: int = 0) -> tuple[int, int]:
    if depth > 2:
        raise ValueError("Nested ZIP depth exceeded")
    findings = 0
    members = 0
    with zipfile.ZipFile(io.BytesIO(data)) as z:
        total = 0
        for item in z.infolist():
            if item.is_dir():
                continue
            members += 1
            name = item.filename.replace("\\", "/")
            parts = name.split("/")
            if name.startswith("/") or any(part in (".", "..", "") for part in parts):
                findings += 1
                continue
            if FORBIDDEN.search(name):
                findings += 1
                continue
            total += item.file_size
            if item.file_size > MAX_MEMBER or total > MAX_TOTAL:
                raise ValueError("Archive size limits exceeded")
            content = z.read(item)
            if name.lower().endswith(".zip"):
                nested_findings, nested_members = scan(content, depth + 1)
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
    except (OSError, zipfile.BadZipFile, ValueError, RuntimeError):
        print("SEC-P2 package inspection failed (invalid or oversized archive).", file=sys.stderr)
        return 3
    print(f"SEC-P2: scanned {members} package members; findings: {findings}")
    return 2 if findings else 0

if __name__ == "__main__":
    sys.exit(main())
