#!/usr/bin/env python3
"""SECDEV-02: fail closed on the *shipped* Joomla extension surface.

The source-tree gate alone cannot prove what is actually inside the nested
installable ZIPs. Inspect all Joomla package members and constituent manifests.
This is not a substitute for Joomla API, upgrade or site acceptance tests.
"""
from __future__ import annotations

import argparse
from io import BytesIO
import json
from pathlib import Path
import stat
import sys
import xml.etree.ElementTree as ET
from zipfile import BadZipFile, ZipFile, ZIP_DEFLATED

ROOT = Path(__file__).resolve().parents[1]
PARTS = {
    "lib_jlms.zip": ("library", "jlms", None),
    "com_jlms.zip": ("component", "com_jlms", None),
    "plg_jlmsvideo_youtube.zip": ("plugin", "youtube", "jlmsvideo"),
    "plg_jlmsvideo_vimeo.zip": ("plugin", "vimeo", "jlmsvideo"),
    "plg_jlmsvideo_cloudflarestream.zip": ("plugin", "cloudflarestream", "jlmsvideo"),
    "plg_jlmsvideo_html5.zip": ("plugin", "html5", "jlmsvideo"),
    "plg_task_jlms.zip": ("plugin", "jlms", "task"),
    "plg_privacy_jdlearn.zip": ("plugin", "jdlearn", "privacy"),
    "plg_system_jlmsmenu.zip": ("plugin", "jlmsmenu", "system"),
    "plg_finder_jlms.zip": ("plugin", "jlms", "finder"),
    "plg_user_jdlearnconsent.zip": ("plugin", "jdlearnconsent", "user"),
    "plg_actionlog_jdlearn.zip": ("plugin", "jdlearn", "actionlog"),
}
COMPONENT_ROOTS = {"administrator", "site", "media", "jlms.xml", "script.php"}
LIBRARY_ROOTS = {"src", "jlms.xml", "bootstrap.php"}
PLUGIN_ROOTS = {
    "src", "services", "language", "media", "docs", "forms",
    "installer.php", "script.php",
}


def members(z: ZipFile, context: str) -> tuple[list[str], list[str]]:
    errors = []
    names = []
    seen = set()
    for info in z.infolist():
        name = info.filename
        normalized = name.casefold()
        if (
            not name or name.startswith("/") or "\\" in name or "\x00" in name
            or ":" in name or any(part in {"", ".", ".."} for part in name.split("/"))
            or info.is_dir() or stat.S_IFMT(info.external_attr >> 16) == stat.S_IFLNK
            or info.flag_bits & 1
        ):
            errors.append(f"{context}: unsafe ZIP member {name!r}")
        if normalized in seen:
            errors.append(f"{context}: duplicate/case-colliding ZIP member {name!r}")
        seen.add(normalized)
        names.append(name)
    return names, errors


def scan(payload: bytes) -> tuple[list[str], dict]:
    errors = []
    report = {"constituents": 0, "files": 0, "manifest_version": ""}
    try:
        with ZipFile(BytesIO(payload)) as package:
            top, findings = members(package, "package")
            errors.extend(findings)
            expected = {"pkg_jlms.xml"} | {"constituents/" + key for key in PARTS}
            if set(top) != expected:
                errors.append(
                    "package: unapproved or missing constituents "
                    + repr(sorted(set(top) ^ expected))
                )
            manifest = ET.fromstring(package.read("pkg_jlms.xml"))
            if manifest.tag != "extension" or manifest.get("type") != "package":
                errors.append("package: invalid Joomla package manifest")
            version = manifest.findtext("version", "")
            report["manifest_version"] = version
            declared = {}
            files = manifest.find("files")
            if files is None or files.get("folder") != "constituents":
                errors.append("package: manifest must install only constituents/")
            else:
                for node in files.findall("file"):
                    filename = (node.text or "").strip()
                    if filename in declared:
                        errors.append(f"package: duplicate manifest constituent {filename}")
                    declared[filename] = (
                        node.get("type"), node.get("id"), node.get("group")
                    )
            if declared != PARTS:
                errors.append("package: manifest constituent types/IDs/groups drifted")
            for filename, (kind, element, group) in PARTS.items():
                member = "constituents/" + filename
                if member not in top:
                    continue
                with ZipFile(BytesIO(package.read(member))) as inner:
                    names, findings = members(inner, filename)
                    errors.extend(findings)
                    report["constituents"] += 1
                    report["files"] += len(names)
                    manifest_name = (
                        "jlms.xml" if kind in {"component", "library"}
                        else element + ".xml"
                    )
                    if manifest_name not in names:
                        errors.append(f"{filename}: extension manifest missing")
                        continue
                    ext = ET.fromstring(inner.read(manifest_name))
                    if ext.tag != "extension" or ext.get("type") != kind:
                        errors.append(f"{filename}: manifest extension type mismatch")
                    if ext.findtext("version") != version:
                        errors.append(f"{filename}: constituent version differs from package")
                    if kind == "plugin" and ext.get("group") != group:
                        errors.append(f"{filename}: plugin group differs from manifest")
                    if kind == "library" and ext.findtext("libraryname") != "jlms":
                        errors.append(f"{filename}: libraryname must be jlms")
                    if kind == "component":
                        allow = COMPONENT_ROOTS
                    elif kind == "library":
                        allow = LIBRARY_ROOTS
                    else:
                        allow = PLUGIN_ROOTS | {manifest_name}
                    for path in names:
                        root = path.split("/", 1)[0]
                        if root not in allow:
                            errors.append(
                                f"{filename}: unapproved installation root {path}"
                            )
    except (BadZipFile, ValueError, KeyError, OSError, ET.ParseError) as exc:
        errors.append(f"package: unreadable ZIP or Joomla manifest: {exc}")
    return errors, report


def fixture_package(extra_member: str | None = None,
                    rogue_inner: str | None = None) -> bytes:
    def write_zip(items: dict[str, bytes]) -> bytes:
        output = BytesIO()
        with ZipFile(output, "w", compression=ZIP_DEFLATED) as z:
            for name, data in items.items():
                z.writestr(name, data)
        return output.getvalue()

    version = "0.0.1"
    manifest_rows = []
    top = {}
    for filename, (kind, element, group) in PARTS.items():
        attrs = 'type="' + kind + '" id="' + element + '"'
        if group:
            attrs += ' group="' + group + '"'
        manifest_rows.append("<file " + attrs + ">" + filename + "</file>")
        inner_name = "jlms.xml" if kind in {"component", "library"} else element + ".xml"
        metadata = '<extension type="' + kind + '"'
        if group:
            metadata += ' group="' + group + '"'
        metadata += '><version>' + version + '</version>'
        if kind == "library":
            metadata += "<libraryname>jlms</libraryname>"
        metadata += "</extension>"
        items = {inner_name: metadata.encode()}
        if kind == "component" and rogue_inner:
            items[rogue_inner] = b"unsafe"
        top["constituents/" + filename] = write_zip(items)
    pkg = (
        '<extension type="package"><version>' + version +
        '</version><files folder="constituents">' +
        "".join(manifest_rows) + "</files></extension>"
    )
    top["pkg_jlms.xml"] = pkg.encode()
    if extra_member:
        top[extra_member] = b"unsafe"
    return write_zip(top)


def self_test() -> None:
    errors, report = scan(fixture_package())
    assert not errors and report["constituents"] == 12, errors
    for unsafe in ("../administrator/index.php", "/tmp/core.php", "unlisted.php"):
        errors, _ = scan(fixture_package(extra_member=unsafe))
        assert errors, unsafe
    errors, _ = scan(fixture_package(rogue_inner="administrator/components/com_users/user.php"))
    assert any("unapproved installation root" in item for item in errors), errors
    errors, _ = scan(fixture_package(rogue_inner="../outside.php"))
    assert any("unsafe ZIP member" in item for item in errors), errors


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--self-test", action="store_true")
    parser.add_argument("--package", type=Path)
    args = parser.parse_args()
    if args.self_test:
        self_test()
        print("SECDEV-02 shipped package negative fixtures: OK")
        return 0
    if args.package is not None:
        package = args.package
    else:
        import xml.etree.ElementTree as xml
        version = xml.parse(ROOT / "packages/pkg_jlms.xml").findtext("version")
        package = ROOT / "dist" / f"pkg_jlms-{version}-dev.zip"
    try:
        errors, report = scan(package.read_bytes())
    except OSError as exc:
        errors, report = [f"package missing: {exc}"], {}
    for finding in errors:
        print("SECDEV-02: " + finding, file=sys.stderr)
    print(json.dumps(report, sort_keys=True))
    return 1 if errors else 0


if __name__ == "__main__":
    raise SystemExit(main())
