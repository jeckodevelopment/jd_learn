#!/usr/bin/env python3
"""SEC-P2 regression: historic and deleted secrets must not be overlooked."""
from pathlib import Path
import runpy
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parents[1]
scan = runpy.run_path(str(ROOT / "tools/sec_p2_history_scan.py"))["scan"]


def git(folder, *args):
    return subprocess.check_output(
        ["git", "-C", str(folder), *args],
        stderr=subprocess.DEVNULL,
    )


with tempfile.TemporaryDirectory() as directory:
    root = Path(directory)
    git(root, "init", "-q")
    git(root, "config", "user.email", "ci@example.invalid")
    git(root, "config", "user.name", "SEC-P2 fixture")
    runtime = root / "component" / "administrator"
    runtime.mkdir(parents=True)
    source = runtime / "example.php"
    source.write_text("<?php /* benign */\n", encoding="utf-8")
    git(root, "add", ".")
    git(root, "commit", "-qm", "safe baseline")
    checked, findings, oversized = scan(root)
    assert checked == 1 and findings == oversized == 0
    # Deleted or later overwritten files must remain visible in Git history.
    source.write_text("<?php /* ghp_" + "A" * 36 + " */\n", encoding="utf-8")
    git(root, "add", ".")
    git(root, "commit", "-qm", "historical fixture")
    source.write_text("<?php /* benign again */\n", encoding="utf-8")
    git(root, "add", ".")
    git(root, "commit", "-qm", "remove historical fixture")
    checked, findings, oversized = scan(root)
    assert checked == 3 and findings == 1 and oversized == 0, (
        checked, findings, oversized,
    )
    source.unlink()
    git(root, "add", "-A")
    git(root, "commit", "-qm", "delete original")
    assert scan(root)[1] == 1, "Deleted historical source was not scanned"
    (root / "README.md").write_text("ghp_" + "Z" * 36, encoding="utf-8")
    git(root, "add", ".")
    git(root, "commit", "-qm", "unshipped fixture")
    assert scan(root)[1] == 1, "Non-runtime content changed the scope"
    workflow = root / ".github" / "workflows"
    workflow.mkdir(parents=True)
    (workflow / "example.yml").write_text(
        "env: {SECRET: 'github_pat_" + "Q" * 40 + "'}\n", encoding="utf-8"
    )
    git(root, "add", ".")
    git(root, "commit", "-qm", "workflow fixture")
    assert scan(root)[1] == 2, "Workflow history was not inspected"
print("SEC-P2 history scan: deleted blobs, source scope and workflows verified")
