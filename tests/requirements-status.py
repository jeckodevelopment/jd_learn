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
 def test_les01_stu04_core_implementation_completed_without_acceptance(self):
  inventory=json.loads((ROOT/'docs/FUNCTIONAL_CRITERIA_1.0.0.json').read_text())['requirements']
  for requirement,expected in [('LES-01',4),('STU-04',3)]:
   with self.subTest(requirement=requirement):
    criteria=inventory[requirement]
    row=next(r for r in self.rows if r['Requisito']==requirement)
    self.assertEqual(len(criteria),expected)
    self.assertTrue(all(unit['status']=='implemented' and unit['evidence'] for unit in criteria))
    self.assertEqual(row['Stato implementazione'],'Completata')
    self.assertTrue(row['Residui implementativi 1.0.0'].startswith('Nessuno'))
    self.assertEqual(row['Accettazione Joomla'],'Non eseguita')
    self.assertEqual(row['Criteri completi superati'],'No')
  self.check()
 def test_cat02_core_implementation_is_closed_independently_of_joomla_acceptance(self):
  row=next(r for r in self.rows if r['Requisito']=='CAT-02')
  criteria=json.loads((ROOT/'docs/FUNCTIONAL_CRITERIA_1.0.0.json').read_text())['requirements']['CAT-02']
  self.assertEqual(len(criteria),5)
  self.assertTrue(all(unit['status']=='implemented' and unit['evidence'] for unit in criteria))
  self.assertEqual(row['Stato implementazione'],'Completata')
  self.assertTrue(row['Residui implementativi 1.0.0'].startswith('Nessuno'))
  self.assertEqual(row['Accettazione Joomla'],'Non eseguita')
  self.assertEqual(row['Criteri completi superati'],'No')
  self.check()
 def test_mail11_template_is_partial_not_fully_implemented(self):
  row=next(r for r in self.rows if r['Requisito']=='MAIL-11')
  self.assertEqual(row['Stato implementazione'],'Completata')
  self.assertIn('MailTemplateInstaller.php',row['Fonti implementazione'])
  self.assertNotEqual(row['Criteri completi superati'],'Sì')
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
