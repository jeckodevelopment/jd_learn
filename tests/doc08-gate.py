#!/usr/bin/env python3
"""Exercise DOC-08 tampering and the pre-release fail-closed behavior."""
import copy
import importlib.util
import json
from pathlib import Path
root = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location("doc08_gate", root / "tools/doc08_gate.py")
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)
data = json.loads((root / "docs/CORE_FLOW_DOCUMENTATION_1.0.0.json").read_text())
module.check(data)
for change in ("missing", "bad_path", "fake_acceptance", "duplicate"):
    variant = copy.deepcopy(data)
    if change == "missing":
        variant["flows"].pop()
    elif change == "bad_path":
        variant["flows"][0]["guide"] = "../private.md"
    elif change == "fake_acceptance":
        variant["flows"][0]["manual_acceptance"] = "passed"
    elif change == "duplicate":
        variant["flows"][0]["id"] = variant["flows"][1]["id"]
    try:
        module.check(variant)
    except ValueError:
        pass
    else:
        raise AssertionError("DOC-08: tampering accepted: " + change)
try:
    module.check(data, release=True)
except ValueError:
    pass
else:
    raise AssertionError("DOC-08: release permitted without manual acceptance")
print("DOC-08: documented inventory, mutation checks and release fail-closed passed")
