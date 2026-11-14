#!/usr/bin/env python3
"""Tests for the PR -> requirements register gate."""
import importlib.util
import unittest
from pathlib import Path
path = Path(__file__).resolve().parents[1] / "scripts" / "check_pr_requirements_coverage.py"
spec = importlib.util.spec_from_file_location("gate", path)
gate = importlib.util.module_from_spec(spec)
spec.loader.exec_module(gate)

def example(pr=""):
    return "Requisito,PR implementazione\nCORE-18,"+pr+"\n"

class GateTests(unittest.TestCase):
    def test_documentation_only_pr_is_exempt(self):
        self.assertTrue(gate.check(900, {"README.md"}, example(),example())[0])
    def test_runtime_requires_csv_change(self):
        self.assertFalse(gate.check(900, {"component/script.php"},example(),example())[0])
    def test_runtime_requires_its_own_pr_number(self):
        self.assertFalse(gate.check(900, {"component/script.php", gate.REGISTRY},example("800"),example("800;899"))[0])
    def test_runtime_with_correct_new_link_passes(self):
        self.assertTrue(gate.check(900, {"component/script.php", gate.REGISTRY},example("800"),example("800;900"))[0])
    def test_source_commit_with_a_preexisting_link_is_insufficient(self):
        self.assertFalse(gate.check(900, {"plugins/user/jdlearnconsent/a.php", gate.REGISTRY},example("900"),example("900"))[0])
    def test_tooling_build_change_is_tracked(self):
        self.assertFalse(gate.check(900, {"tools/build.py"},example(),example())[0])

if __name__ == "__main__":
    unittest.main()
