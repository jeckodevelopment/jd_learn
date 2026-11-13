#!/usr/bin/env python3
"""Regression checks for claims that must never be inferred from green CI."""
import copy,importlib.util,json,unittest,contextlib,io
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
spec=importlib.util.spec_from_file_location('status',ROOT/'tools/check_status.py')
status=importlib.util.module_from_spec(spec);spec.loader.exec_module(status)
class Reconciliation(unittest.TestCase):
 def setUp(self):
  self.rows=status.read_csv('docs/REQUIREMENTS_STATUS.csv')
  self.evidence=json.loads((ROOT/'docs/IMPLEMENTATION_EVIDENCE.json').read_text())
 def check(self):status.check_reconciliation(self.rows,self.evidence)
 def test_implemented_does_not_require_human_acceptance(self):self.check()
 def test_unimplemented_documented_requirement_has_no_runtime_commit(self):
  row=next(r for r in self.rows if r['Requisito']=='MAIL-11')
  self.assertEqual(row['Stato implementazione'],'Non iniziata')
  self.assertEqual(row['Commit riscontrato'],'')
  self.check()
 def test_green_ci_does_not_grant_acceptance(self):
  self.rows[0]['Accettazione Joomla']='Superata'
  with self.assertRaisesRegex(AssertionError,'No full human'):self.check()
 def test_completion_cannot_hide_residual_code(self):
  row=next(r for r in self.rows if r['Stato implementazione']=='Completata')
  row['Residui implementativi 1.0.0']='Implementare ancora un comando.'
  with self.assertRaisesRegex(AssertionError,'residual code'):self.check()
 def test_phase_two_is_not_a_core_gate(self):
  row=next(r for r in self.rows if r['Perimetro 1.0.0']=='Fase 2')
  row['Criteri 1.0.0']='Tutto'
  with self.assertRaisesRegex(AssertionError,'not a core gate'):self.check()
 def test_passing_tests_need_execution_reference(self):
  row=next(r for r in self.rows if r['Verifica automatica']=='Superata')
  row['Evidenza automatica']=''
  with self.assertRaisesRegex(AssertionError,'Unregistered CI'):self.check()
 def test_summary_must_follow_rows(self):
  self.rows[0]['Accettazione Joomla']='Fallita'
  with self.assertRaisesRegex(AssertionError,'summary drift'):self.check()
if __name__=='__main__':unittest.main()
