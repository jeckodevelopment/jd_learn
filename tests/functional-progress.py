#!/usr/bin/env python3
"""Guard functional progress from premature or fabricated percentages."""
import copy
import importlib.util
import unittest
from pathlib import Path

root = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location("functional_progress", root / "tools/functional_progress.py")
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)

class FunctionalProgressTests(unittest.TestCase):
    def setUp(self):
        import csv, json
        with (root / "docs/REQUIREMENTS_STATUS.csv").open(encoding="utf-8-sig", newline="") as handle:
            self.rows = list(csv.DictReader(handle))
        self.criteria = json.loads((root / "docs/FUNCTIONAL_CRITERIA_1.0.0.json").read_text())

    def test_no_global_percentage_before_full_reconciliation(self):
        output = module.calculate(self.rows, self.criteria)
        self.assertIsNone(output["functional_implementation_percent"])
        self.assertEqual(output["progress_status"], "not_yet_measurable")
        self.assertEqual(output["audited_requirements"], 3)

    def test_no_evidence_cannot_be_marked_implemented(self):
        changed = copy.deepcopy(self.criteria)
        changed["requirements"]["LCM-05"][0]["evidence"] = []
        with self.assertRaisesRegex(ValueError, "lacks evidence"):
            module.calculate(self.rows, changed)

    def test_missing_criterion_is_not_credited(self):
        output = module.calculate(self.rows, self.criteria)
        lcm = output["criteria_by_requirement"]["LCM-05"]
        self.assertLess(lcm["provisional_fraction"], 1)
        self.assertGreater(lcm["unassessed_criteria"], 0)

    def test_out_of_scope_is_rejected(self):
        changed = copy.deepcopy(self.criteria)
        changed["requirements"]["NOT-A-REQUIREMENT"] = changed["requirements"]["LCM-03"]
        with self.assertRaisesRegex(ValueError, "Unknown or excluded"):
            module.calculate(self.rows, changed)

if __name__ == "__main__":
    unittest.main()
