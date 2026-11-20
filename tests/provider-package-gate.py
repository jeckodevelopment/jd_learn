#!/usr/bin/env python3
"""PBASE-03: missing or stale bundled provider guides must block Joomla ZIP creation."""
import shutil
import subprocess
import sys
import tempfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "tools"))
from provider_doc_gate import PROVIDERS, validate_provider_guides  # noqa: E402

validate_provider_guides(ROOT)
with tempfile.TemporaryDirectory(prefix="jlms-pbase03-") as workspace:
    root = Path(workspace)
    (root / "tools").mkdir()
    (root / "packages").mkdir()
    for filename in ("build.py", "provider_doc_gate.py"):
        shutil.copyfile(ROOT / "tools" / filename, root / "tools" / filename)
    shutil.copyfile(ROOT / "packages" / "pkg_jlms.xml", root / "packages" / "pkg_jlms.xml")
    for provider in PROVIDERS:
        source = ROOT / "plugins" / "jlmsvideo" / provider
        dest = root / "plugins" / "jlmsvideo" / provider
        (dest / "docs").mkdir(parents=True)
        shutil.copyfile(source / f"{provider}.xml", dest / f"{provider}.xml")
        shutil.copyfile(source / "docs" / "README.md", dest / "docs" / "README.md")

    def rejected(label):
        process = subprocess.run(
            [sys.executable, str(root / "tools" / "build.py"), "--release-date", "2026-11-20"],
            cwd=root, capture_output=True, text=True,
        )
        assert process.returncode != 0, f"{label}: builder unexpectedly succeeded"
        assert "PBASE-03" in process.stderr, (label, process.stderr)
        assert not list((root / "dist").glob("*.zip")), f"{label}: an unverified package escaped"

    guide = root / "plugins" / "jlmsvideo" / "youtube" / "docs" / "README.md"
    original = guide.read_text(encoding="utf-8")
    guide.unlink()
    rejected("missing guide")
    guide.write_text(original.replace("## Capacità e limiti", "## obsolete section"), encoding="utf-8")
    rejected("missing contract section")
    guide.write_text(original.replace("`metadata_cache_ttl`", "undocumented field"), encoding="utf-8")
    rejected("undocumented Joomla manifest field")
print("PBASE-03: missing/stale plugin documentation prevents Joomla package creation")
