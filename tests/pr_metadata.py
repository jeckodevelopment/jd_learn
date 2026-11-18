#!/usr/bin/env python3
"""Unit tests for the pull request metadata gate."""
import importlib.util
import unittest
from pathlib import Path

path = Path(__file__).resolve().parents[1] / "scripts" / "check_pr_metadata.py"
spec = importlib.util.spec_from_file_location("pr_metadata", path)
gate = importlib.util.module_from_spec(spec)
spec.loader.exec_module(gate)

BODY = """## Cosa è stato fatto
Descrive la modifica.
## Risolve un bug?
- [x] No — nuova funzionalità
## Requisiti implementati
CAT-01 / language_available
## Documentazione allineata
- [x] Report e riepiloghi generati.
## Collaudo manuale Joomla
Stato: In attesa
Casi da provare:
- Accesso consentito e negato secondo i permessi previsti; riferire l’esito in seguito.
## Comportamenti da testare
- La CI convalida il registro e non accredita l’accettazione manuale.
## Controlli automatici
La CI della PR.
"""

class PullRequestMetadataTests(unittest.TestCase):
    def test_pending_manual_joomla_test_is_allowed(self):
        self.assertEqual([], gate.validate(BODY, "1.0.0", ["P1"], {"component/site/src/Controller/X.php"}))

    def test_manual_test_marked_executed_requires_observed_result(self):
        body = BODY.replace("Stato: In attesa", "Stato: Eseguito")
        errors = gate.validate(body, "1.0.0", ["P1"], {"component/site/X.php"})
        self.assertTrue(any("observed result" in error for error in errors))
        body = body.replace("Stato: Eseguito", "Stato: Eseguito\\nRisultato: confermato")
        self.assertEqual([], gate.validate(body, "1.0.0", ["P1"], {"component/site/X.php"}))

    def test_missing_priority_is_rejected(self):
        errors = gate.validate(BODY, "1.0.0", [], {"README.md"})
        self.assertTrue(any("priority label" in error for error in errors))

    def test_documentation_only_requires_label(self):
        errors = gate.validate(BODY, "1.0.0", ["P1"], {"docs/guide.md"})
        self.assertTrue(any("documentation label" in error for error in errors))

    def test_bug_choice_must_be_selected_once(self):
        body = BODY.replace("- [x] No", "- [ ] No")
        errors = gate.validate(body, "1.0.0", ["P1"], {"component/site/X.php"})
        self.assertTrue(any("exactly one answer" in error for error in errors))

    def test_wrong_milestone_is_rejected(self):
        errors = gate.validate(BODY, "0.9", ["P1"], {"component/site/X.php"})
        self.assertTrue(any("milestone 1.0.0" in error for error in errors))

    def test_requirement_can_be_not_applicable_for_nonfunctional_change(self):
        body = BODY.replace("CAT-01 / language_available", "N/A — sola documentazione")
        errors = gate.validate(body, "1.0.0", ["P1", "documentation"], {"docs/guide.md"})
        self.assertEqual([], errors)


if __name__ == "__main__":
    unittest.main()
