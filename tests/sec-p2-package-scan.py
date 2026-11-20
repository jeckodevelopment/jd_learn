#!/usr/bin/env python3
"""SEC-P2 package scan regression against nested archives and current build."""
from pathlib import Path
import io
import runpy
import zipfile

ROOT = Path(__file__).resolve().parents[1]
scan = runpy.run_path(str(ROOT / 'tools/sec_p2_package_scan.py'))['scan']
def zip_bytes(files):
    buf = io.BytesIO()
    with zipfile.ZipFile(buf, 'w') as z:
        for name, contents in files.items():
            z.writestr(name, contents)
    return buf.getvalue()

safe = zip_bytes({'com_jlms/install.php': '<?php echo "ok";'})
assert scan(safe) == (0, 1)
assert scan(zip_bytes({'child.zip': safe})) == (0, 2)
assert scan(zip_bytes({'.env': 'empty'}))[0] > 0
assert scan(zip_bytes({'secrets/private.pem': 'empty'}))[0] > 0
assert scan(zip_bytes({'child.zip': zip_bytes({'secrets/id_rsa': 'empty'})}))[0] > 0
assert scan(zip_bytes({'config.php': '-----BEGIN PRIVATE KEY-----\nfixture\n'}))[0] > 0
assert scan(zip_bytes({'../traversal.php': 'fixture'}))[0] > 0
builds = list((ROOT / 'dist').glob('pkg_jlms-*-dev.zip'))
assert len(builds) == 1, 'Expected exactly one current development package'
findings, members = scan(builds[0].read_bytes())
assert findings == 0 and members > 12, (findings, members)
print(f'SEC-P2: current package and negative fixtures passed; {members} archive entries checked')
