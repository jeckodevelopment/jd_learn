#!/usr/bin/env python3
"""Read-only deployment audit for legacy plaintext videos under Joomla webroot.

No content is opened, no file name is printed and no deletion/migration occurs.
Exit 2 means potential exposed video objects require operator intervention.
"""
import argparse
from pathlib import Path
import sys

def audit(webroot: Path) -> tuple[int, int]:
    root = webroot.resolve(strict=True)
    if not root.is_dir():
        raise ValueError("webroot must be a directory")
    managed = root / "media" / "com_jlms" / "private"
    if managed.is_symlink():
        return (0, 1)
    if not managed.exists():
        return (0, 0)
    if not managed.is_dir():
        return (0, 1)
    total = 0
    unsafe_links = 0
    # Do not follow symlinked directories or files. Treat links as a finding.
    import os
    for base, dirs, files in os.walk(managed, followlinks=False):
        for directory in list(dirs):
            if (Path(base) / directory).is_symlink():
                unsafe_links += 1
                dirs.remove(directory)
        for name in files:
            path = Path(base) / name
            if path.is_symlink():
                unsafe_links += 1
            elif path.is_file() and path.suffix.lower() == ".blob":
                total += 1
    return total, unsafe_links

def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("webroot", type=Path, help="Absolute path to Joomla public webroot")
    args = parser.parse_args()
    try:
        blobs, links = audit(args.webroot)
    except (OSError, ValueError) as exc:
        print(f"Audit unavailable: {type(exc).__name__}", file=sys.stderr)
        return 3
    print(f"Potential legacy plaintext .blob files under public tree: {blobs}")
    print(f"Symlink findings under managed private tree: {links}")
    if blobs or links:
        print("REVIEW REQUIRED: restrict web access and plan verified relocation; no files changed.")
        return 2
    print("No matching legacy blobs detected in the inspected managed directory.")
    return 0

if __name__ == "__main__":
    sys.exit(main())
