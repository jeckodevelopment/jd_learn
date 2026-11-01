#!/usr/bin/env python3
"""Check traceability/documentation consistency, not functional requirement acceptance."""
from pathlib import Path
import csv,json,re,xml.etree.ElementTree as E
ROOT=Path(__file__).resolve().parents[1]
def read_csv(path):
 with (ROOT/path).open(encoding='utf-8-sig',newline='')as stream:return list(csv.DictReader(stream))
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
  assert row['Stato implementazione']in['In corso','Da implementare','Completato'],key
  assert row['Verifica parziale']in['CI parziale','Solo riscontro documentale','Non eseguita'],key
  assert row['Criteri completi superati']in['Sì','No'],key
  assert row['Criteri residui']and row['Evidenza e limiti'],(key,'Missing residual/evidence')
  sources=[p.strip()for p in row['Fonti implementazione'].split(';')if p.strip()]
  tests=[p.strip()for p in row['Fonti test parziali'].split(';')if p.strip()]
  for path in sources+tests:
   assert not Path(path).is_absolute()and'..'not in Path(path).parts and(ROOT/path).is_file(),(key,'Missing source',path)
  if row['Stato implementazione']=='In corso':assert sources,(key,'No source for partial implementation')
  if row['Verifica parziale']=='CI parziale':assert tests,(key,'No partial test reference')
  else:assert not tests,(key,'Test references require an explicit partial verification label')
  if row['Criteri completi superati']=='Sì':
   assert key in accepted and(ROOT/accepted[key]['report']).is_file(),(key,'No complete acceptance report')
  if row['Stato implementazione']=='Completato':assert row['Criteri completi superati']=='Sì',key
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
