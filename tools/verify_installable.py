#!/usr/bin/env python3
"""Check a directly-installable Joomla package or unwrap a GitHub Actions artifact.

GitHub's downloadable Actions artifact is always a wrapper ZIP. That wrapper is
not suitable for Joomla's "Upload Package File" installer; the inner ZIP is.
"""
from __future__ import annotations

import argparse
from hashlib import sha256
from io import BytesIO
from pathlib import Path
import sys
import xml.etree.ElementTree as ET
import zipfile


def check_package(data: bytes) -> tuple[str, str]:
    try:
        with zipfile.ZipFile(BytesIO(data)) as archive:
            if archive.testzip() is not None:
                raise ValueError("Corrupt Joomla package ZIP")
            if "pkg_jlms.xml" not in archive.namelist():
                raise ValueError("Missing pkg_jlms.xml at ZIP root; this is not an installable JD Learn package")
            root = ET.fromstring(archive.read("pkg_jlms.xml"))
            if root.tag != "extension" or root.get("type") != "package" or root.findtext("packagename") != "jlms":
                raise ValueError("Invalid JD Learn Joomla package manifest")
            children = root.findall("./files/file")
            if len(children) != 11:
                raise ValueError("JD Learn package must include all 11 extension archives")
            for child in children:
                path = "constituents/" + (child.text or "")
                if path not in archive.namelist():
                    raise ValueError("Missing extension archive " + path)
                with zipfile.ZipFile(BytesIO(archive.read(path))) as sub:
                    if sub.testzip() is not None:
                        raise ValueError("Corrupt extension archive " + path)
            return root.findtext("version") or "", sha256(data).hexdigest()
    except zipfile.BadZipFile as exc:
        raise ValueError("Invalid ZIP archive") from exc


def extract_wrapper(wrapper: Path, output: Path) -> bytes:
    with zipfile.ZipFile(wrapper) as outer:
        names = [n for n in outer.namelist() if not n.endswith("/")]
        candidates = [n for n in names if n.startswith("pkg_jlms-") and n.endswith(".zip") and "/" not in n]
        if len(candidates) != 1 or any(n not in candidates + ["SHA256SUMS.txt"] for n in names):
            raise ValueError("Unexpected GitHub Actions wrapper content")
        filename = candidates[0]
        data = outer.read(filename)
        if "SHA256SUMS.txt" not in names:
            raise ValueError("Missing checksum record")
        lines = outer.read("SHA256SUMS.txt").decode("utf-8").splitlines()
        if len(lines) != 1 or lines[0].split() != [sha256(data).hexdigest(), filename]:
            raise ValueError("GitHub wrapper package SHA-256 mismatch")
        check_package(data)
        output.mkdir(parents=True, exist_ok=True)
        (output / filename).write_bytes(data)
        return data


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("zip_file", type=Path)
    parser.add_argument("--extract-wrapper", action="store_true",
                        help="Extract the verified inner Joomla ZIP from a GitHub Actions artifact")
    parser.add_argument("--output", type=Path, default=Path("."),
                        help="Destination for --extract-wrapper (default current directory)")
    args = parser.parse_args()
    try:
        if args.extract_wrapper:
            data = extract_wrapper(args.zip_file, args.output)
        else:
            data = args.zip_file.read_bytes()
        version, digest = check_package(data)
    except (OSError, ValueError, zipfile.BadZipFile, ET.ParseError) as exc:
        print("NOT INSTALLABLE: " + str(exc), file=sys.stderr)
        return 1
    print(f"INSTALLABLE: JD Learn {version}, SHA-256 {digest}, bytes={len(data)}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
