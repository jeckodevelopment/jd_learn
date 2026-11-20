#!/usr/bin/env python3
"""SEC-P2 gate negative fixtures and source inventory."""
from pathlib import Path
import runpy
import tempfile

root = Path(__file__).resolve().parents[1]
inspect = runpy.run_path(str(root / 'tools/sec_p2_source_gate.py'))['inspect']
checked, actions, findings = inspect(root)
assert checked >= 300, (checked, actions, findings)
assert actions > 0, (checked, actions, findings)
assert findings == 0, (checked, actions, findings)
with tempfile.TemporaryDirectory() as folder:
    tmp = Path(folder)
    runtime = tmp / 'component'
    runtime.mkdir()
    (runtime / 'test.php').write_text('<?php /* normal code */')
    workflow = tmp / '.github/workflows'
    workflow.mkdir(parents=True)
    (workflow / 'test.yml').write_text('steps:\n  - uses: actions/checkout@v4\n')
    assert inspect(tmp)[2] == 1, 'Unpinned GitHub action accepted'
    (workflow / 'test.yml').write_text('steps:\n  - uses: actions/checkout@' + 'a' * 40 + '\n')
    assert inspect(tmp)[2] == 0, 'Pinned action rejected'
    (runtime / 'test.php').write_text('-----BEGIN RSA PRIVATE KEY-----\nfixture')
    assert inspect(tmp)[2] == 1, 'Private key fixture accepted'
print('SEC-P2 source and workflow supply-chain policy passed')
