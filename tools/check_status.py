#!/usr/bin/env python3
"""Check traceability/documentation consistency, not functional requirement acceptance."""
from pathlib import Path
import csv,json,re,collections,xml.etree.ElementTree as E
ROOT=Path(__file__).resolve().parents[1]
def read_csv(path):
 with (ROOT/path).open(encoding='utf-8-sig',newline='')as stream:return list(csv.DictReader(stream))
def check_reconciliation(rows,evidence):
 """Implementation, automated execution and human acceptance are independent axes."""
 for row in rows:
  key=row['Requisito'];state=row['Stato implementazione'];scope=row['Perimetro 1.0.0']
  assert state in ['Non iniziata','Parziale','Completata'],(key,'Invalid implementation state')
  assert scope in ['1.0.0','Misto','Fase 2'],(key,'Invalid release scope')
  for field in ['Criteri 1.0.0','Criteri fase 2','Commit riscontrato','Residui implementativi 1.0.0','Verifiche residue 1.0.0','Residui fase 2','Evidenza accettazione Joomla']:
   assert row[field].strip(),(key,'Missing reconciliation field',field)
  assert row['Verifica automatica']in['Non eseguita','Superata','Fallita'],key
  assert row['Copertura automatica']in['Nessuna','Parziale','Completa'],key
  assert row['Accettazione Joomla']in['Non eseguita','Parziale','Superata','Fallita'],key
  if scope=='Fase 2':assert row['Criteri 1.0.0']=='Non incluso.'and row['Residui implementativi 1.0.0'].startswith('Non applicabile'),(key,'Phase two is not a core gate')
  if state=='Completata':
   assert row['Fonti implementazione']and re.fullmatch(r'[1-9][0-9]*(?:;[1-9][0-9]*)*', row['PR implementazione']),(key,'Completion needs implementation evidence')
   assert row['Residui implementativi 1.0.0'].startswith('Nessuno'),(key,'Completed implementation has residual code work')
  if row['Verifica automatica']=='Superata':
   assert row['Fonti test parziali']and row['Copertura automatica']!='Nessuna',key
   valid_ci_urls={evidence['ci']['url']}|{prior.get('ci',{}).get('url') for prior in evidence.get('prior_evidence',[]) if prior.get('ci',{}).get('url')}
   assert row['Evidenza automatica'] in valid_ci_urls,(key,'Unregistered CI evidence')
  if row['Verifica automatica']=='Non eseguita':assert row['Copertura automatica']=='Nessuna',key
  if row['Accettazione Joomla']=='Superata':
   assert key in evidence['full_requirement_acceptance'],(key,'No full human acceptance report')
  if row['Criteri completi superati']=='Sì':assert row['Accettazione Joomla']=='Superata'and state=='Completata',key
 summary=json.loads((ROOT/'docs/REQUIREMENTS_RECONCILIATION.json').read_text())
 for field,counts in summary['counts'].items():
  assert dict(collections.Counter(r[field]for r in rows))==counts,('Reconciliation summary drift',field)
 progress=summary['implementation_progress']
 assert round(sum(progress['areas'])/len(progress['areas']))==progress['percent'], 'Progress arithmetic drift'
def check_status():
 baseline=read_csv('docs/specifications/Matrice_requisiti_test_LMS_1.0.csv')
 rows=read_csv('docs/REQUIREMENTS_STATUS.csv')
 expected={r['Requisito']:r for r in baseline}
 assert len(expected)==len(baseline),'Duplicate baseline requirement'
 assert [r['Requisito']for r in rows]==[r['Requisito']for r in baseline],'Requirements missing, duplicated or reordered'
 evidence=json.loads((ROOT/'docs/IMPLEMENTATION_EVIDENCE.json').read_text())
 accepted=evidence['full_requirement_acceptance']
 for row in rows:
  key=row['Requisito'];original=expected[key]
  assert row['Perimetro baseline']==original['Perimetro'],(key,'Scope drift')
  assert row['Descrizione baseline']==original['Descrizione'],(key,'Description drift')
  assert row['Scenari previsti']==original['Test previsti'],(key,'Acceptance-plan drift')
  assert row['Verifica parziale']in['CI parziale','Solo riscontro documentale','Non eseguita'],key
  assert row['Criteri completi superati']in['Sì','No'],key
  assert row['Criteri residui']and row['Evidenza e limiti'],(key,'Missing residual/evidence')
  sources=[p.strip()for p in row['Fonti implementazione'].split(';')if p.strip()]
  tests=[p.strip()for p in row['Fonti test parziali'].split(';')if p.strip()]
  for path in sources+tests:
   assert not Path(path).is_absolute()and'..'not in Path(path).parts and(ROOT/path).is_file(),(key,'Missing source',path)
  if row['Stato implementazione']in['Parziale','Completata']:assert sources,(key,'No implementation source')
  if row['Verifica parziale']=='CI parziale':assert tests,(key,'No partial test reference')
  else:assert not tests,(key,'Test references require an explicit partial verification label')
  if row['Criteri completi superati']=='Sì':
   assert key in accepted and(ROOT/accepted[key]['report']).is_file(),(key,'No complete acceptance report')
 check_reconciliation(rows,evidence)
 plan=json.loads((ROOT/'docs/RELEASE_PLAN.json').read_text())
 phases=plan['milestones']
 assert len({p['id']for p in phases})==len(phases),'Duplicate release phase'
 planned=[key for phase in phases for key in phase['requirements']]
 assert len(planned)==len(set(planned))and set(planned)==set(expected),'Release plan must assign every baseline ID exactly once'
 for phase in phases:
  assert phase['title']and phase['acceptance']=='Non accettata','A roadmap is not an acceptance report'
  for key in phase['requirements']:
   assert (phase['id']=='M13')==(expected[key]['Perimetro']=='Differito'),(key,'Deferred scope drift')
 schema=(ROOT/'component/administrator/sql/install.mysql.utf8mb4.sql').read_text()
 counts=(len(re.findall(r'^CREATE TABLE',schema,re.M)),len(re.findall(r'ADD CONSTRAINT',schema)))
 for name,label in [('README.md','Schema InnoDB'),('docs/IMPLEMENTATION_STATUS.md','Schema attuale')]:
  text=(ROOT/name).read_text();match=re.search(label+r': \*\*(\d+) tabelle / (\d+) FK\*\*',text)
  assert match and tuple(map(int,match.groups()))==counts,(name,'Schema summary drift')
  version=E.parse(ROOT/'packages/pkg_jlms.xml').findtext('version')+'-dev'
  assert version in text[:500],(name,'Version summary drift')
 assert evidence['ci']['jobs']and all(j['status']=='completed'and j['conclusion']=='success'for j in evidence['ci']['jobs']),'Evidence must identify actual completed successful jobs'
 print(json.dumps({'requirements':len(rows),'traceability':'passed','full_acceptance_records':len(accepted),'note':'Documentation check only; not functional acceptance'}))
if __name__=='__main__':check_status()
