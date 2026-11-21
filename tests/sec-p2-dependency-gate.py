#!/usr/bin/env python3
"""SEC-P2 inventory negative fixtures for nested Joomla extension packages."""
from pathlib import Path
import io
import runpy
import zipfile

ROOT = Path(__file__).resolve().parents[1]
module = runpy.run_path(str(ROOT / "tools/sec_p2_dependency_gate.py"))
inspect = module["inspect"]
names = module["PACKAGE_FILES"]

def zip_bytes(items):
    stream = io.BytesIO()
    with zipfile.ZipFile(stream, "w") as archive:
        for name, data in items:
            archive.writestr(name, data)
    return stream.getvalue()

def candidate(child_entries=None, extra=()):
    children = []
    for name in sorted(names):
        files = child_entries if name == "com_jlms.zip" and child_entries is not None else [
            ("src/Controller/Example.php", "<?php /* safe source */")
        ]
        children.append(("constituents/" + name, zip_bytes(files)))
    return zip_bytes([
        ("pkg_jlms.xml", '<extension type="package"><packagename>jlms</packagename></extension>'),
        *children, *extra,
    ])

assert inspect(candidate()) == (12, 12, 0)
for bad in (
    "vendor/lib.php", "node_modules/example/index.js",
    "src/composer.lock", "src/package-lock.json",
    "src/third_party/library.php", "src/embedded.wasm",
):
    assert inspect(candidate([("src/Controller/Example.php", "safe"), (bad, "unsafe")]))[2] == 1, bad

try:
    inspect(candidate(extra=[("constituents/rogue.zip", b"not a valid archive")]))
except ValueError:
    pass
else:
    raise AssertionError("Undeclared third-party extension accepted")
try:
    inspect(zip_bytes([("pkg_jlms.xml", "<extension />")]))
except ValueError:
    pass
else:
    raise AssertionError("Incomplete package accepted")

builds = list((ROOT / "dist").glob("pkg_jlms-*-dev.zip"))
assert len(builds) == 1, "Expected one current candidate ZIP"
children, members, findings = inspect(builds[0].read_bytes())
assert children == 12 and members > 50 and findings == 0, (children, members, findings)
print(f"SEC-P2 dependency inventory: {children} children, {members} files, no unknown bundled dependencies")
