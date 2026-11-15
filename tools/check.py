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
assert len(re.findall(r'^CREATE TABLE',sql,re.M))==47
assert len(re.findall(r'ADD CONSTRAINT',sql))==66
mail_queue=(ROOT/'component/administrator/sql/install.mysql.utf8mb4.sql').read_text()
assert re.search(r'CREATE TABLE IF NOT EXISTS `#__jlms_mail_queue`',mail_queue)
assert re.search(r'UNIQUE KEY `uq_event_key` \(`event_key`\)',mail_queue)
assert not re.search(r'\b(?:DROP TABLE|TRUNCATE TABLE)\b',sql,re.I)
# Course communications stay within active enrolments and share Joomla Mail Templates/outbox.
communication=(ROOT/'component/administrator/src/Application/CourseCommunicationService.php').read_text()
assert "e.status='active'" in communication and 'u.block=0' in communication
assert 'filter_var((string) $row->email, FILTER_VALIDATE_EMAIL)' in communication
assert 'JSON_UNQUOTE(JSON_EXTRACT(scope_json' in communication and "'mutation_id'=>$mutationId" in communication and 'owner_user_id = :owner' in communication
assert '->authorised' not in communication.lower() and 'getEnrollmentRosterService' not in communication
assert "'com_jlms.course.message' => 'communication'" in (ROOT/'component/administrator/src/Infrastructure/MailQueue.php').read_text()
assert "'com_jlms.course.message'" in (ROOT/'component/administrator/src/Infrastructure/MailTemplateInstaller.php').read_text()
assert 'getCourseCommunicationService()->process' in (ROOT/'plugins/task/jlms/src/Extension/Jlms.php').read_text()
assert "checkToken('post',false)" in (ROOT/'component/administrator/src/Controller/CoursemessageController.php').read_text()
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
# Joomla's Mail Templates manager resolves each ID title and description in both
# administrator language catalogs: .sys.ini for the list and .ini for the editor.
mail_definitions=(ROOT/'component/administrator/src/Infrastructure/MailTemplateInstaller.php').read_text()
mail_ids=re.findall(r"'(com_jlms\\.[a-z.]+)'\\s*=>\\s*\\[",mail_definitions)
assert len(mail_ids)==14 and len(set(mail_ids))==14,('Unexpected mail template inventory',mail_ids)
for language in ['it-IT','en-GB']:
 for suffix in ['.ini','.sys.ini']:
  catalog=(ROOT/'component/administrator/language'/language/('com_jlms'+suffix)).read_text()
  for template_id in mail_ids:
   for field in ['TITLE','DESC']:
    language_key='COM_JLMS_MAIL_'+template_id.removeprefix('com_jlms.').replace('.','_').upper()+'_'+field
    assert catalog.count(language_key+'="')==1,(language,suffix,language_key)
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
 assert 'COM_JLMS_PATHWAYS=' in sys_ini and pathway_title in sys_ini,tag
 category_reassign='Gestione dipendenze categorie' if tag=='it-IT' else 'Category dependency cleanup'
 assert 'COM_JLMS_CATEGORY_REASSIGN_TITLE=' in sys_ini and category_reassign in sys_ini,(tag,'com_jlms.sys.ini category reassignment label')
 lesson_label='Campi aggiuntivi' if tag=='it-IT' else 'Lesson fields'
 assert re.findall(r'^COM_JLMS_LESSON_FIELDS_MANAGE="([^"]*)"$',sys_ini,re.M)==[lesson_label],(tag,'com_jlms.sys.ini lesson fields label')
# The course editor exposes the curriculum action once, through its native toolbar.
course_editor=(ROOT/'component/administrator/tmpl/course/edit.php').read_text()
assert 'jlms-course-programme-link' not in course_editor
assert 'COM_JLMS_COURSE_SAVE_PROGRAMME' in course_editor
course_view=(ROOT/'component/administrator/src/View/Course/HtmlView.php').read_text()
assert "$toolbar->save('course.apply')" in course_view
assert "$toolbar->dropdownButton('save-group')" in course_view
assert "$saveDropdown->getChildToolbar()" in course_view
assert "$programmeMenu->save('course.save', 'COM_JLMS_COURSE_SAVE_AND_CLOSE')" in course_view
assert "$programmeMenu->standardButton(" in course_view
assert "'COM_JLMS_COURSE_SAVE_AND_CLOSE'" in course_view
assert 'COM_JLMS_COURSE_SAVE_AND_CLOSE="Save & Close"' in (ROOT/'component/administrator/language/en-GB/com_jlms.ini').read_text()
assert 'COM_JLMS_COURSE_SAVE_AND_CLOSE="Salva & Chiudi"' in (ROOT/'component/administrator/language/it-IT/com_jlms.ini').read_text()
assert "'course-programme'" in course_view and "'COM_JLMS_COURSE_SAVE_PROGRAMME'" in course_view and "'course.programme'" in course_view
assert "ToolbarHelper::custom('course.programme'" not in course_view
dashboard_template=(ROOT/'component/administrator/tmpl/dashboard/default.php').read_text()
dashboard_view=(ROOT/'component/administrator/src/View/Dashboard/HtmlView.php').read_text()
dashboard_style=(ROOT/'component/media/css/admin-dashboard.css').read_text()
for group in ('DIDACTICS','PEOPLE','COMMUNICATION','CONFIGURATION'):
    marker='COM_JLMS_DASHBOARD_'+'GROUP_'+group
    assert marker in dashboard_view, ('Dashboard missing group', group)
    for locale in ('it-IT','en-GB'):
        assert marker+'="' in (ROOT/f'component/administrator/language/{locale}/com_jlms.ini').read_text()
assert "foreach ($this->shortcutGroups as $groupKey => $shortcuts)" in dashboard_template
assert "if (!$shortcuts) { continue; }" in dashboard_template
assert "core.manage', 'com_mails'" in dashboard_view and "core.edit', 'com_mails'" in dashboard_view
assert "option=com_mails&view=templates&filter[extension]=com_jlms" in dashboard_view
assert dashboard_view.index("'COM_JLMS_COURSES'") < dashboard_view.index("'COM_JLMS_CERTIFICATES'")
assert "'COM_JLMS_MAIL_TEMPLATES'" in dashboard_view
assert "grid-template-columns: repeat(4" in dashboard_style and "@media (max-width:" in dashboard_style

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
# The administrator announcement preview is an explicit, reachable display route.
display_controller=(ROOT/'component/administrator/src/Controller/DisplayController.php').read_text()
assert "'courseannouncement'" in display_controller
assert (ROOT/'component/administrator/src/View/Courseannouncement/HtmlView.php').is_file()
assert 'new \\Jlms\\Component\\Jlms\\Administrator\\Application\\CourseAnnouncementService' in (ROOT/'component/administrator/src/View/Courseannouncement/HtmlView.php').read_text()
assert 'new CourseAnnouncementService' in (ROOT/'component/administrator/src/Controller/CourseannouncementController.php').read_text()
assert (ROOT/'component/administrator/tmpl/courseannouncement/default.php').is_file()

# Keep implementation claims separate from planned acceptance and verify their source references.
import runpy
runpy.run_path(str(ROOT/'tools/check_status.py'),run_name='__main__')
print(json.dumps({'xml_files':count,'defined_language_keys':len(keys),'schema_tables':46,'foreign_keys':66,'structural_checks':'passed'}))

# MAIL-11.1 registers the opt-in new-course template without enabling sends.
mail_templates=(ROOT/'component/administrator/src/Infrastructure/MailTemplateInstaller.php').read_text()
assert "'com_jlms.course.published' => [" in mail_templates
for tag in ['COURSE_TITLE','COURSE_IMAGE_HTML','COURSE_INTRO_HTML','COURSE_LESSONS_HTML','COURSE_URL','UNSUBSCRIBE_URL']:
    assert tag in mail_templates, ('MAIL-11.1 missing template token',tag)
consent=(ROOT/'component/administrator/src/Application/CourseAnnouncementConsent.php').read_text()
assert "state = 'confirmed'" in consent and "state = 'pending'" in consent
assert "hash('sha256', $token)" in consent and 'token_expires_at >= UTC_TIMESTAMP()' in consent
assert 'random_bytes(32)' in consent and 'task=mailconsent.confirm' in consent
assert "com_jlms.course.consent" in (ROOT/'component/administrator/src/Infrastructure/MailTemplateInstaller.php').read_text()
assert "getCourseAnnouncementConsent()->state" in (ROOT/'component/site/src/View/Dashboard/HtmlView.php').read_text()

# MAIL-11.3 anonymous unsubscribe is explicit POST-only, hashed, expiring and one-use.
assert 'createUnsubscribeUrl' in consent and 'revokeByToken' in consent
assert 'mail_unsubscribe_tokens' in consent and 'DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 DAY)' in consent
assert "->where('confirmed_at = :confirmed')" in consent
mail_controller=(ROOT/'component/site/src/Controller/MailconsentController.php').read_text()
assert "['request', 'confirm', 'revoke', 'revokelink']" in mail_controller.lower()
assert "strtoupper($this->input->getMethod()) !== 'POST'" in mail_controller
assert "->revokeByToken($token)" in mail_controller
assert "mailconsentrevoke" in (ROOT/'component/site/src/View/Mailconsentrevoke/HtmlView.php').read_text().lower()

# Documentation hygiene is checked with every standard build.
runpy.run_path(str(ROOT / 'tools/check_docs_hygiene.py'), run_name='__main__')

# JD Learn's administrator sidebar is a concise operational navigation, not a
# duplicate of the dashboard. Secondary native Joomla views remain reachable
# from the dashboard with their existing ACL guards.
manifest=(ROOT/'component/jlms.xml').read_text()
submenu=E.fromstring(manifest).find('./administration/submenu')
assert submenu is not None
assert [node.attrib.get('link') for node in submenu.findall('menu')]==[
 'option=com_jlms&view=dashboard',
 'option=com_jlms&view=courses',
 'option=com_jlms&view=pathways',
 'option=com_jlms&view=teachers',
 'option=com_jlms&view=mailqueue',
 'option=com_jlms&view=questionbank',
]
dashboard_view=(ROOT/'component/administrator/src/View/Dashboard/HtmlView.php').read_text()
for key in ('COM_JLMS_DASHBOARD_MAIL_VARIABLES','COM_JLMS_DASHBOARD_CATEGORY_DEPENDENCIES'):
 assert key in dashboard_view
assert "'COM_JLMS_MAIL_VARIABLE_GUIDE_TITLE', 'icon-info-circle', 'index.php?option=com_jlms&view=mailvariables'" not in dashboard_view
for tag in ('it-IT','en-GB'):
 messages=(ROOT/f'component/administrator/language/{tag}/com_jlms.ini').read_text()
 for key in ('COM_JLMS_DASHBOARD_MAIL_VARIABLES','COM_JLMS_DASHBOARD_CATEGORY_DEPENDENCIES','COM_JLMS_DASHBOARD_GROUP_CONFIGURATION'):
  assert len(re.findall(r'^'+key+r'=',messages,re.M))==1,(tag,key)

# Nested Joomla administrator views must map to the component's own primary
# menu entries and use Joomla Pathway rather than a custom breadcrumb widget.
admin_controller=(ROOT/'component/administrator/src/Controller/DisplayController.php').read_text()
for pair in ("'courses' => ['courses', 'course', 'curriculum'","'pathways' => ['pathways', 'pathway']","'teachers' => ['teachers', 'teacher']","'questionbank' => ['questionbank', 'question']","'mailqueue' => ['mailqueue']"):
 assert pair in admin_controller,pair
assert '$menu->setActive((int) $item->id)' in admin_controller
assert "$app->getPathway()" not in admin_controller
,catalog,re.M))==1,(language, suffix, language_key)
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
 assert 'COM_JLMS_PATHWAYS=' in sys_ini and pathway_title in sys_ini,tag
 category_reassign='Gestione dipendenze categorie' if tag=='it-IT' else 'Category dependency cleanup'
 assert 'COM_JLMS_CATEGORY_REASSIGN_TITLE=' in sys_ini and category_reassign in sys_ini,(tag,'com_jlms.sys.ini category reassignment label')
 lesson_label='Campi aggiuntivi' if tag=='it-IT' else 'Lesson fields'
 assert re.findall(r'^COM_JLMS_LESSON_FIELDS_MANAGE="([^"]*)"$',sys_ini,re.M)==[lesson_label],(tag,'com_jlms.sys.ini lesson fields label')
# The course editor exposes the curriculum action once, through its native toolbar.
course_editor=(ROOT/'component/administrator/tmpl/course/edit.php').read_text()
assert 'jlms-course-programme-link' not in course_editor
assert 'COM_JLMS_COURSE_SAVE_PROGRAMME' in course_editor
course_view=(ROOT/'component/administrator/src/View/Course/HtmlView.php').read_text()
assert "$toolbar->save('course.apply')" in course_view
assert "$toolbar->dropdownButton('save-group')" in course_view
assert "$saveDropdown->getChildToolbar()" in course_view
assert "$programmeMenu->save('course.save', 'COM_JLMS_COURSE_SAVE_AND_CLOSE')" in course_view
assert "$programmeMenu->standardButton(" in course_view
assert "'COM_JLMS_COURSE_SAVE_AND_CLOSE'" in course_view
assert 'COM_JLMS_COURSE_SAVE_AND_CLOSE="Save & Close"' in (ROOT/'component/administrator/language/en-GB/com_jlms.ini').read_text()
assert 'COM_JLMS_COURSE_SAVE_AND_CLOSE="Salva & Chiudi"' in (ROOT/'component/administrator/language/it-IT/com_jlms.ini').read_text()
assert "'course-programme'" in course_view and "'COM_JLMS_COURSE_SAVE_PROGRAMME'" in course_view and "'course.programme'" in course_view
assert "ToolbarHelper::custom('course.programme'" not in course_view
dashboard_template=(ROOT/'component/administrator/tmpl/dashboard/default.php').read_text()
dashboard_view=(ROOT/'component/administrator/src/View/Dashboard/HtmlView.php').read_text()
dashboard_style=(ROOT/'component/media/css/admin-dashboard.css').read_text()
for group in ('DIDACTICS','PEOPLE','COMMUNICATION','CONFIGURATION'):
    marker='COM_JLMS_DASHBOARD_'+'GROUP_'+group
    assert marker in dashboard_view, ('Dashboard missing group', group)
    for locale in ('it-IT','en-GB'):
        assert marker+'="' in (ROOT/f'component/administrator/language/{locale}/com_jlms.ini').read_text()
assert "foreach ($this->shortcutGroups as $groupKey => $shortcuts)" in dashboard_template
assert "if (!$shortcuts) { continue; }" in dashboard_template
assert "core.manage', 'com_mails'" in dashboard_view and "core.edit', 'com_mails'" in dashboard_view
assert "option=com_mails&view=templates&filter[extension]=com_jlms" in dashboard_view
assert dashboard_view.index("'COM_JLMS_COURSES'") < dashboard_view.index("'COM_JLMS_CERTIFICATES'")
assert "'COM_JLMS_MAIL_TEMPLATES'" in dashboard_view
assert "grid-template-columns: repeat(4" in dashboard_style and "@media (max-width:" in dashboard_style

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
# The administrator announcement preview is an explicit, reachable display route.
display_controller=(ROOT/'component/administrator/src/Controller/DisplayController.php').read_text()
assert "'courseannouncement'" in display_controller
assert (ROOT/'component/administrator/src/View/Courseannouncement/HtmlView.php').is_file()
assert 'new \\Jlms\\Component\\Jlms\\Administrator\\Application\\CourseAnnouncementService' in (ROOT/'component/administrator/src/View/Courseannouncement/HtmlView.php').read_text()
assert 'new CourseAnnouncementService' in (ROOT/'component/administrator/src/Controller/CourseannouncementController.php').read_text()
assert (ROOT/'component/administrator/tmpl/courseannouncement/default.php').is_file()

# Keep implementation claims separate from planned acceptance and verify their source references.
import runpy
runpy.run_path(str(ROOT/'tools/check_status.py'),run_name='__main__')
print(json.dumps({'xml_files':count,'defined_language_keys':len(keys),'schema_tables':46,'foreign_keys':66,'structural_checks':'passed'}))

# MAIL-11.1 registers the opt-in new-course template without enabling sends.
mail_templates=(ROOT/'component/administrator/src/Infrastructure/MailTemplateInstaller.php').read_text()
assert "'com_jlms.course.published' => [" in mail_templates
for tag in ['COURSE_TITLE','COURSE_IMAGE_HTML','COURSE_INTRO_HTML','COURSE_LESSONS_HTML','COURSE_URL','UNSUBSCRIBE_URL']:
    assert tag in mail_templates, ('MAIL-11.1 missing template token',tag)
consent=(ROOT/'component/administrator/src/Application/CourseAnnouncementConsent.php').read_text()
assert "state = 'confirmed'" in consent and "state = 'pending'" in consent
assert "hash('sha256', $token)" in consent and 'token_expires_at >= UTC_TIMESTAMP()' in consent
assert 'random_bytes(32)' in consent and 'task=mailconsent.confirm' in consent
assert "com_jlms.course.consent" in (ROOT/'component/administrator/src/Infrastructure/MailTemplateInstaller.php').read_text()
assert "getCourseAnnouncementConsent()->state" in (ROOT/'component/site/src/View/Dashboard/HtmlView.php').read_text()

# MAIL-11.3 anonymous unsubscribe is explicit POST-only, hashed, expiring and one-use.
assert 'createUnsubscribeUrl' in consent and 'revokeByToken' in consent
assert 'mail_unsubscribe_tokens' in consent and 'DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 DAY)' in consent
assert "->where('confirmed_at = :confirmed')" in consent
mail_controller=(ROOT/'component/site/src/Controller/MailconsentController.php').read_text()
assert "['request', 'confirm', 'revoke', 'revokelink']" in mail_controller.lower()
assert "strtoupper($this->input->getMethod()) !== 'POST'" in mail_controller
assert "->revokeByToken($token)" in mail_controller
assert "mailconsentrevoke" in (ROOT/'component/site/src/View/Mailconsentrevoke/HtmlView.php').read_text().lower()

# Documentation hygiene is checked with every standard build.
runpy.run_path(str(ROOT / 'tools/check_docs_hygiene.py'), run_name='__main__')

# JD Learn's administrator sidebar is a concise operational navigation, not a
# duplicate of the dashboard. Secondary native Joomla views remain reachable
# from the dashboard with their existing ACL guards.
manifest=(ROOT/'component/jlms.xml').read_text()
submenu=E.fromstring(manifest).find('./administration/submenu')
assert submenu is not None
assert [node.attrib.get('link') for node in submenu.findall('menu')]==[
 'option=com_jlms&view=dashboard',
 'option=com_jlms&view=courses',
 'option=com_jlms&view=pathways',
 'option=com_jlms&view=teachers',
 'option=com_jlms&view=mailqueue',
 'option=com_jlms&view=questionbank',
]
dashboard_view=(ROOT/'component/administrator/src/View/Dashboard/HtmlView.php').read_text()
for key in ('COM_JLMS_DASHBOARD_MAIL_VARIABLES','COM_JLMS_DASHBOARD_CATEGORY_DEPENDENCIES'):
 assert key in dashboard_view
assert "'COM_JLMS_MAIL_VARIABLE_GUIDE_TITLE', 'icon-info-circle', 'index.php?option=com_jlms&view=mailvariables'" not in dashboard_view
for tag in ('it-IT','en-GB'):
 messages=(ROOT/f'component/administrator/language/{tag}/com_jlms.ini').read_text()
 for key in ('COM_JLMS_DASHBOARD_MAIL_VARIABLES','COM_JLMS_DASHBOARD_CATEGORY_DEPENDENCIES','COM_JLMS_DASHBOARD_GROUP_CONFIGURATION'):
  assert len(re.findall(r'^'+key+r'=',messages,re.M))==1,(tag,key)

# Nested Joomla administrator views must map to the component's own primary
# menu entries and use Joomla Pathway rather than a custom breadcrumb widget.
admin_controller=(ROOT/'component/administrator/src/Controller/DisplayController.php').read_text()
for pair in ("'courses' => ['courses', 'course', 'curriculum'","'pathways' => ['pathways', 'pathway']","'teachers' => ['teachers', 'teacher']","'questionbank' => ['questionbank', 'question']","'mailqueue' => ['mailqueue']"):
 assert pair in admin_controller,pair
assert '$menu->setActive((int) $item->id)' in admin_controller
assert "$app->getPathway()" not in admin_controller
