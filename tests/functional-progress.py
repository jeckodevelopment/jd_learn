#!/usr/bin/env python3
"""Guard functional progress from premature or fabricated percentages."""
import csv
import importlib.util
import json
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location("functional_progress", ROOT / "tools/functional_progress.py")
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)


class FunctionalProgressTests(unittest.TestCase):
    def setUp(self):
        self.rows = [
            {"Requisito": "A", "Perimetro 1.0.0": "1.0.0", "Accettazione Joomla": "Parziale"},
            {"Requisito": "B", "Perimetro 1.0.0": "Misto", "Accettazione Joomla": "Non eseguita"},
            {"Requisito": "C", "Perimetro 1.0.0": "Fase 2", "Accettazione Joomla": "Superata"},
        ]
        self.criteria = {"requirements": {
            "A": [
                {"id": "a", "status": "implemented", "evidence": ["tools/functional_progress.py"]},
                {"id": "b", "status": "missing", "evidence": []},
            ],
            "B": [{"id": "a", "status": "missing", "evidence": []}],
        }}

    def test_ledger_covers_current_scope(self):
        with module.REGISTER.open(encoding="utf-8-sig", newline="") as stream:
            rows = list(csv.DictReader(stream))
        criteria = json.loads(module.CRITERIA.read_text(encoding="utf-8"))
        output = module.calculate(rows, criteria)
        self.assertEqual(output["audited_requirements"], output["in_scope_requirements"])
        self.assertEqual(output["not_yet_audited_requirements"], 0)
        for requirement in ("ISC-04", "CERT-03", "MAIL-09", "JOO-03", "SIC-01",
                            "PRI-02", "CORE-14", "ACC-01", "PERF-01"):
            self.assertIn(requirement, output["criteria_by_requirement"])
        for requirement in ("PRO-07", "ATT-01"):
            self.assertNotIn(requirement, output["criteria_by_requirement"])

    def test_unassessed_criterion_blocks_score(self):
        self.criteria["requirements"]["A"][1]["status"] = "unassessed"
        output = module.calculate(self.rows, self.criteria)
        self.assertIsNone(output["functional_implementation_percent"])
        self.assertFalse(output["criteria_by_requirement"]["A"]["complete_audit"])

    def test_unverifiable_criterion_blocks_score_after_review(self):
        self.criteria["requirements"]["A"][1]["status"] = "unverifiable"
        output = module.calculate(self.rows, self.criteria)
        self.assertIsNone(output["functional_implementation_percent"])
        self.assertTrue(output["criteria_by_requirement"]["A"]["complete_audit"])
        self.assertEqual(output["criteria_by_requirement"]["A"]["unverifiable_criteria"], 1)

    def test_incomplete_inventory_blocks_score(self):
        del self.criteria["requirements"]["B"]
        output = module.calculate(self.rows, self.criteria)
        self.assertIsNone(output["functional_implementation_percent"])
        self.assertEqual(output["not_yet_audited_requirements"], 1)

    def test_equal_requirement_weights_and_missing_criterion(self):
        output = module.calculate(self.rows, self.criteria)
        self.assertEqual(output["functional_implementation_percent"], 25.0)\n        self.assertEqual(output["development_evidence_floor_percent"], 25.0)\n        self.assertEqual(output["manual_acceptance"]["coverage_percent"], 50.0)\n        self.assertEqual(output["manual_acceptance"]["requirements_partial"], 1)\n        self.assertEqual(output["manual_acceptance"]["requirements_passed"], 0)
        self.assertEqual(output["criteria_by_requirement"]["A"]["missing_criteria"], 1)
        self.assertNotIn("C", output["criteria_by_requirement"])

    def test_manual_acceptance_is_validated(self):\n        self.rows[0]["Accettazione Joomla"] = "Auto superata"\n        with self.assertRaisesRegex(ValueError, "Invalid Joomla acceptance state"):\n            module.calculate(self.rows, self.criteria)\n\n    def test_no_evidence_cannot_be_marked_implemented(self):
        self.criteria["requirements"]["A"][0]["evidence"] = []
        with self.assertRaisesRegex(ValueError, "lacks evidence"):
            module.calculate(self.rows, self.criteria)

    def test_out_of_scope_is_rejected(self):
        self.criteria["requirements"]["C"] = self.criteria["requirements"]["A"]
        with self.assertRaisesRegex(ValueError, "Unknown or excluded"):
            module.calculate(self.rows, self.criteria)


if __name__ == "__main__":
    unittest.main()
