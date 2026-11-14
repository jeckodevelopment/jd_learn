#!/usr/bin/env python3
"""Safety and installability checks for the development-only direct download."""
from pathlib import Path
import re

root = Path(__file__).resolve().parents[1]
workflow = (root / ".github/workflows/dev-package.yml").read_text()
package_workflow = (root / ".github/workflows/ci.yml").read_text()
assert "branches: [main]" in workflow
assert "github.repository == 'jeckodevelopment/jd_learn'" in workflow
assert "github.ref == 'refs/heads/main'" in workflow
assert "gh release create" in workflow
assert "--prerelease" in workflow
assert "jdlearn-dev-latest" in workflow
assert "python3 tools/verify_installable.py" in workflow
assert "sha256sum --check" in workflow
assert "curl --fail --location" in workflow
assert "releases/download/jdlearn-dev-latest/$ZIP_FILE" in workflow
assert "jdlearn.xml" not in workflow
assert "publish_update_feed_ftps.py" not in workflow
assert "JDLEARN_FTPS_" not in workflow
assert "v1.0.0" not in workflow
assert "EXTRACT-FIRST-JD-Learn-" in package_workflow
assert re.search(r"permissions:\s*\n\s*contents: write", workflow)
print("Direct development ZIP download and stable-release isolation checks passed")
