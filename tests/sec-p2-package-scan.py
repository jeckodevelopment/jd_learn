#!/usr/bin/env python3
"""SEC-P2 regression: nested ZIP safety and actual development package."""
from pathlib import Path
import io
import runpy
import stat
import zipfile

ROOT = Path(__file__).resolve().parents[1]
module = runpy.run_path(str(ROOT / "tools/sec_p2_package_scan.py"))
scan = module["scan"]


def zip_bytes(files):
    out = io.BytesIO()
    with zipfile.ZipFile(out, "w") as archive:
        for name, value in files:
            archive.writestr(name, value)
    return out.getvalue()


safe = zip_bytes([("com_jlms/install.php", '<?php echo "ok";')])
assert scan(safe) == (0, 1)
assert scan(zip_bytes([("child.zip", safe)])) == (0, 2)
assert scan(zip_bytes([(".env", "empty")]))[0] > 0
assert scan(zip_bytes([("secrets/private.pem", "empty")]))[0] > 0
assert scan(zip_bytes([("child.zip", zip_bytes([("secrets/id_rsa", "empty")]))]))[0] > 0
assert scan(zip_bytes([("config.php", "-----BEGIN PRIVATE KEY-----\nfixture\n")]))[0] > 0
assert scan(zip_bytes([("../traversal.php", "fixture")]))[0] > 0
assert scan(zip_bytes([("C:\\unsafe.php", "fixture")]))[0] > 0
assert scan(zip_bytes([("safe.php", "a"), ("SAFE.php", "b")]))[0] > 0
assert scan(zip_bytes([("same.php", "a"), ("same.php", "b")]))[0] > 0
assert scan(zip_bytes([("child.zip", zip_bytes([("x.php", "a"), ("X.php", "b")]))]))[0] > 0
assert scan(zip_bytes([("nested.zip", zip_bytes([("nested.zip", zip_bytes([("../x", "a")]))]))]))[0] > 0
link = zipfile.ZipInfo("link.php")
link.create_system = 3
link.external_attr = (stat.S_IFLNK | 0o777) << 16
assert scan(zip_bytes([(link, "../outside")]))[0] > 0
for bad in (zip_bytes([("first.zip", safe), ("second.zip", safe)]),):
    try:
        scan(bad, budget={"bytes": module["MAX_TOTAL"] - len(safe) - 1, "entries": 0})
    except ValueError:
        pass
    else:
        raise AssertionError("Shared nested archive byte budget not enforced")
try:
    scan(zip_bytes([("one.php", "1"), ("two.php", "2")]), budget={"bytes": 0, "entries": module["MAX_ENTRIES"] - 1})
except ValueError:
    pass
else:
    raise AssertionError("Shared archive entry budget not enforced")
builds = list((ROOT / "dist").glob("pkg_jlms-*-dev.zip"))
assert len(builds) == 1, "Expected exactly one current development package"
findings, members = scan(builds[0].read_bytes())
assert findings == 0 and members > 12, (findings, members)
print(f"SEC-P2: current package and negative fixtures passed; {members} entries checked")
