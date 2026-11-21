#!/usr/bin/env python3
"""SEC-P2: fail closed if new bundled third-party dependency code enters JD Learn.

The candidate Joomla ZIP must remain first-party-only (12 known child archives).
This is a source/composition gate, not a remote CVE or hosted-Joomla audit.
Prints counts and component types, never payloads or credentials.
"""
from __future__ import annotations

import argparse
import io
from pathlib import Path, PurePosixPath
import sys
import zipfile
import xml.etree.ElementTree as ET

PACKAGE_FILES = {
    "lib_jlms.zip", "com_jlms.zip", "plg_jlmsvideo_youtube.zip",
    "plg_jlmsvideo_vimeo.zip", "plg_jlmsvideo_cloudflarestream.zip",
    "plg_jlmsvideo_html5.zip", "plg_privacy_jdlearn.zip",
    "plg_task_jlms.zip", "plg_system_jlmsmenu.zip",
    "plg_finder_jlms.zip", "plg_user_jdlearnconsent.zip",
    "plg_actionlog_jdlearn.zip",
}
DENIED_DIRS = {
    "vendor", "node_modules", "bower_components", "third_party",
    "third-party", "site-packages", ".runtime", ".git",
}
DENIED_NAMES = {
    "composer.json", "composer.lock", "package.json", "package-lock.json",
    "npm-shrinkwrap.json", "yarn.lock", "pnpm-lock.yaml", "pipfile.lock",
    "requirements.txt", "poetry.lock", "cargo.lock", "gemfile.lock",
    "go.sum",
}
BINARY_CODE = {".so", ".dll", ".dylib", ".exe", ".phar", ".jar", ".wasm"}

def unsafe_members(child: bytes) -> tuple[int, int]:
    inspected = violations = 0
    with zipfile.ZipFile(io.BytesIO(child)) as archive:
        for member in archive.infolist():
            if member.is_dir():
                continue
            inspected += 1
            parts = PurePosixPath(member.filename.replace("\\", "/")).parts
            lowered = [p.casefold() for p in parts]
            if (any(part in DENIED_DIRS for part in lowered)
                    or lowered[-1] in DENIED_NAMES
                    or PurePosixPath(lowered[-1]).suffix in BINARY_CODE):
                violations += 1
    return inspected, violations

def inspect(package: bytes) -> tuple[int, int, int]:
    try:
        with zipfile.ZipFile(io.BytesIO(package)) as archive:
            names = [entry.filename for entry in archive.infolist() if not entry.is_dir()]
            actual = set(names)
            expected = {"pkg_jlms.xml"} | {"constituents/" + name for name in PACKAGE_FILES}
            if len(names) != len(expected) or actual != expected:
                raise ValueError("Unexpected Joomla package composition")
            root = ET.fromstring(archive.read("pkg_jlms.xml"))
            if root.get("type") != "package" or root.findtext("packagename") != "jlms":
                raise ValueError("Unexpected Joomla package manifest")
            count = violations = 0
            for name in sorted(PACKAGE_FILES):
                inspected, found = unsafe_members(archive.read("constituents/" + name))
                count += inspected
                violations += found
            return len(PACKAGE_FILES), count, violations
    except (zipfile.BadZipFile, ET.ParseError, KeyError) as exc:
        raise ValueError("Invalid Joomla candidate ZIP") from exc

def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("package", type=Path)
    args = parser.parse_args()
    try:
        children, files, findings = inspect(args.package.read_bytes())
    except (ValueError, OSError, RuntimeError):
        print("SEC-P2 dependency inventory incomplete; build not approved.", file=sys.stderr)
        return 2
    print(f"SEC-P2 dependency inventory: {children} first-party child archives; "
          f"{files} files reviewed; {findings} unknown bundled dependency markers")
    return 2 if findings else 0

if __name__ == "__main__":
    raise SystemExit(main())
