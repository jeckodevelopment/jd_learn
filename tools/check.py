#!/usr/bin/env python3
"""Structural source/package checks, separate from runtime PHP and database tests."""
from pathlib import Path
import re,json,xml.etree.ElementTree as E,zipfile,io
ROOT=Path(__file__).resolve().parents[1]
count=0
for base in ['component','libraries','plugins','packages']:
 for p in (ROOT/base).rglob('*.xml'):
  doc=E.parse(p);count+=1
  for tag in ['filename','folder','language']:
   for e in doc.findall('.//'+tag):
    # The manifest folder context is verified inside the ZIP below; parse all XML now.
    assert e.text and e.text.strip(),(p,tag)
for p in ROOT.rglob('*.json'):
 if 'dist' not in p.parts:json.loads(p.read_text())
sql=(ROOT/'component/administrator/sql/install.mysql.utf8mb4.sql').read_text()
assert len(re.findall(r'^CREATE TABLE',sql,re.M))==43
assert len(re.findall(r'ADD CONSTRAINT',sql))==63
mail_queue=(ROOT/'component/administrator/sql/install.mysql.utf8mb4.sql').read_text()
assert re.search(r'CREATE TABLE IF NOT EXISTS `#__jlms_mail_queue`',mail_queue)
assert re.search(r'UNIQUE KEY `uq_event_key` \(`event_key`\)',mail_queue)
assert not re.search(r'\b(?:DROP TABLE|TRUNCATE TABLE)\b',sql,re.I)
# Catch undefined component/plugin translation keys, including INI keys assembled in ACL metadata.
keys=set()
for p in ROOT.rglob('*.ini'):
 if 'docs' not in p.parts:keys.update(re.findall(r'^([A-Z][A-Z0-9_]+)=',p.read_text(),re.M))
used=set()
for folder in ['component','plugins']:
 for p in (ROOT/folder).rglob('*'):
  if p.suffix in ['.php','.xml']:used.update(re.findall(r'\b(?:COM_JLMS|PLG_JLMSVIDEO|PLG_PRIVACY_JDLEARN)_[A-Z0-9_]+\b',p.read_text()))
# Installer error codes are caught explicitly and listed in the implementation status; still translate them.
assert used<=keys,sorted(used-keys)
# The dashboard introduction used to have a stale duplicate value later in each INI file.
for tag,expected in {
 'it-IT':'Gestisci corsi, programma e risorse didattiche dal pannello amministrativo.',
 'en-GB':'Manage courses, the learning programme and teaching resources from the administrator panel.',
}.items():
 ini=(ROOT/'component'/'administrator'/'language'/tag/'com_jlms.ini').read_text()
 matches=re.findall(r'^COM_JLMS_DASHBOARD_INTRO="([^"]*)"$',ini,re.M)
 assert matches==[expected],(tag,matches)
 for key in ['COM_JLMS_COURSES','COM_JLMS_TEACHERS','COM_JLMS_LESSONS']:
  assert re.search(rf'^{key}="[^"]+"$',ini,re.M),(tag,key)
 lesson_label='Campi aggiuntivi' if tag=='it-IT' else 'Lesson fields'
 assert re.findall(r'^COM_JLMS_LESSON_FIELDS_MANAGE="([^"]*)"$',ini,re.M)==[lesson_label],(tag,'com_jlms.ini lesson fields label')
for tag in ['it-IT','en-GB']:
 sys_ini=(ROOT/'component'/'administrator'/'language'/tag/'com_jlms.sys.ini').read_text()
 assert re.findall(r'^COM_JLMS_DASHBOARD_MENU="([^"]*)"$',sys_ini,re.M)==['Dashboard'],tag
 pathway_title='Percorsi formativi' if tag=='it-IT' else 'Learning pathways'
 assert re.findall(r'^COM_JLMS_PATHWAYS="([^"]*)"$',sys_ini,re.M)==[pathway_title],tag
 lesson_label='Campi aggiuntivi' if tag=='it-IT' else 'Lesson fields'
 assert re.findall(r'^COM_JLMS_LESSON_FIELDS_MANAGE="([^"]*)"$',sys_ini,re.M)==[lesson_label],(tag,'com_jlms.sys.ini lesson fields label')
# The course editor exposes the curriculum action once, through its native toolbar.
course_editor=(ROOT/'component/administrator/tmpl/course/edit.php').read_text()
assert 'jlms-course-programme-link' not in course_editor
assert 'COM_JLMS_COURSE_SAVE_PROGRAMME' in course_editor
course_view=(ROOT/'component/administrator/src/View/Course/HtmlView.php').read_text()
assert "ToolbarHelper::custom('course.programme'" in course_view
dashboard_template=(ROOT/'component/administrator/tmpl/dashboard/default.php').read_text()
pathways_template=(ROOT/'component/administrator/tmpl/pathways/default.php').read_text()
assert not re.search(r'<h1\b',dashboard_template) and not re.search(r'<h1\b',pathways_template)
version=E.parse(ROOT/'packages/pkg_jlms.xml').findtext('version')
component_version=E.parse(ROOT/'component/jlms.xml').findtext('version')
semver_core=r'(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)'
assert re.fullmatch(semver_core,version),('non-canonical package version',version)
assert re.fullmatch(semver_core,component_version),('non-canonical component version',component_version)
assert version==component_version,('package/component version mismatch',version,component_version)
package=ROOT/'dist'/f'pkg_jlms-{version}-dev.zip'
if package.exists():
 with zipfile.ZipFile(package) as z:
  assert z.testzip() is None
  m=E.fromstring(z.read('pkg_jlms.xml'))
  for e in m.findall('./files/file'):
   with zipfile.ZipFile(io.BytesIO(z.read('constituents/'+e.text))) as child:
    assert child.testzip() is None
    names=set(child.namelist())
    manifest='jlms.xml' if e.attrib['type'] in ['component','library'] else e.attrib['id']+'.xml'
    x=E.fromstring(child.read(manifest))
    for files in x.findall('files')+x.findall('administration/files'):
     prefix=files.attrib.get('folder','')
     for f in list(files):
      source='/'.join(x for x in [prefix,f.text] if x)
      assert source in names or any(n.startswith(source+'/') for n in names),(manifest,source)
 print(f'ZIP manifests and all {len(m.findall("./files/file"))} child archives verified')
# Keep implementation claims separate from planned acceptance and verify their source references.
import runpy
runpy.run_path(str(ROOT/'tools/check_status.py'),run_name='__main__')
print(json.dumps({'xml_files':count,'defined_language_keys':len(keys),'schema_tables':43,'foreign_keys':63,'structural_checks':'passed'}))
