#!/usr/bin/env python3
"""Bind a JD Learn release candidate to its actual ZIP, feed and source notes.

This is an integrity record, never a release approval or manual acceptance.
"""
import argparse
import hashlib
import json
from pathlib import Path
import re
import xml.etree.ElementTree as ET
import zipfile


def sha256(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def verify(args):
    package = Path(args.package)
    feed = Path(args.feed)
    sums = Path(args.checksums)
    notes = Path(args.notes)
    assert re.fullmatch(r"(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)", args.version)
    assert re.fullmatch(r"[a-f0-9]{40}", args.commit), "Full commit SHA required"
    assert package.is_file() and package.name == f"pkg_jlms-{args.version}.zip"
    assert feed.is_file() and sums.is_file() and notes.is_file()
    digest = sha256(package)
    entries = [line.split() for line in sums.read_text().splitlines() if line.strip()]
    assert len(entries) == 1 and len(entries[0]) == 2, "Exactly one checksum required"
    assert entries[0][0] == digest, "Checksum file differs from package bytes"
    assert Path(entries[0][1].lstrip("*")).name == package.name, "Checksum refers to a different package"
    with zipfile.ZipFile(package) as z:
        assert z.testzip() is None, "Corrupt Joomla ZIP"
        manifest = ET.fromstring(z.read("pkg_jlms.xml"))
        assert manifest.findtext("version") == args.version, "Manifest version differs"
        assert manifest.get("type") == "package" and manifest.findtext("packagename") == "jlms"
    update = ET.parse(feed).getroot().find("./update")
    assert update is not None and update.findtext("version") == args.version
    assert update.findtext("sha256") == digest, "Joomla feed checksum mismatch"
    expected_url = (
        f"https://github.com/jeckodevelopment/jd_learn/releases/download/"
        f"v{args.version}/{package.name}"
    )
    assert update.findtext("./downloads/downloadurl") == expected_url
    assert args.release_notes.strip(), "Release notes must not be empty"
    source = notes.read_text(encoding="utf-8")
    assert "## Incompatibilità e limitazioni note" in source
    assert "## Gate prima di pubblicare le note definitive" in source
    assert "non" in source.lower() and "pubblicata" in source.lower()
    return {
        "schema_version": 1,
        "status": "candidate_not_approved",
        "manual_joomla_acceptance": "not_asserted",
        "version": args.version,
        "source_commit": args.commit,
        "package_filename": package.name,
        "package_sha256": digest,
        "feed_sha256": sha256(feed),
        "draft_release_notes_sha256": sha256(notes),
        "actual_release_notes_sha256": hashlib.sha256(args.release_notes.encode("utf-8")).hexdigest(),
        "release_asset_url": expected_url,
    }


def main():
    parser = argparse.ArgumentParser()
    for flag in ("version", "commit", "package", "feed", "checksums", "notes", "output"):
        parser.add_argument("--" + flag, required=True)
    parser.add_argument("--release-notes", required=True)
    parser.add_argument("--verify", action="store_true")
    args = parser.parse_args()
    evidence = verify(args)
    output = Path(args.output)
    if args.verify:
        assert output.is_file(), "Evidence record missing"
        assert json.loads(output.read_text(encoding="utf-8")) == evidence, (
            "Candidate evidence does not match source or package"
        )
    else:
        output.parent.mkdir(parents=True, exist_ok=True)
        output.write_text(json.dumps(evidence, indent=2, sort_keys=True) + "\n", encoding="utf-8")
    print(f"DOC-10 release candidate {'verified' if args.verify else 'recorded'}: {args.version}")


if __name__ == "__main__":
    main()
