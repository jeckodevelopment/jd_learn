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
        self.assertEqual(output["audited_requirements"], 73)

    def test_completed_requirement_is_explicitly_audited(self):
        output = module.calculate(self.rows, self.criteria)
        self.assertTrue(output["criteria_by_requirement"]["CAT-03"]["complete_audit"])
        self.assertTrue(output["criteria_by_requirement"]["AUT-02"]["complete_audit"])

    def test_legacy_partial_remains_not_fully_audited(self):
        output = module.calculate(self.rows, self.criteria)
        self.assertFalse(output["criteria_by_requirement"]["CAT-04"]["complete_audit"])
        self.assertIsNone(output["functional_implementation_percent"])

    def test_in_scope_learning_criteria_and_phase_two_exclusion(self):
        output = module.calculate(self.rows, self.criteria)
        self.assertTrue(output["criteria_by_requirement"]["PRO-02"]["complete_audit"])
        self.assertNotIn("PRO-07", output["criteria_by_requirement"])
        self.assertEqual(output["criteria_by_requirement"]["ISC-04"]["unassessed_criteria"], 2)

    def test_materials_video_scope_still_incomplete(self):
        output = module.calculate(self.rows, self.criteria)
        self.assertGreater(output["criteria_by_requirement"]["VID-03"]["unassessed_criteria"], 0)
        self.assertGreater(output["criteria_by_requirement"]["MAT-06"]["unassessed_criteria"], 0)
        self.assertIsNone(output["functional_implementation_percent"])

    def test_certificates_and_mail_are_distinct_from_phase_two(self):
        output = module.calculate(self.rows, self.criteria)
        self.assertIn("CERT-03", output["criteria_by_requirement"])
        self.assertIn("MAIL-09", output["criteria_by_requirement"])
        self.assertIn("JOO-03", output["criteria_by_requirement"])
        self.assertNotIn("ATT-01", output["criteria_by_requirement"])
        self.assertGreater(output["criteria_by_requirement"]["CERT-08"]["unassessed_criteria"], 0)
        self.assertIsNone(output["functional_implementation_percent"])

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
