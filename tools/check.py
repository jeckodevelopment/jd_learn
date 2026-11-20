#!/usr/bin/env python3
"""Structural source/package checks, separate from runtime PHP and database tests."""
from pathlib import Path
import re,json,hashlib,xml.etree.ElementTree as E,zipfile,io
ROOT=Path(__file__).resolve().parents[1]
# QUI-02: the row-locked service, not merely the UI, must reject hard deletes outside trash.
question_bank=(ROOT/'component/administrator/src/Application/QuestionBankService.php').read_text()
hard_delete=question_bank.split('public function delete(User $identity, int $id): void',1)[1].split('public function revisionOptions(',1)[0]
assert "if ((int) $question->state !== -2)" in hard_delete
assert hard_delete.index("$question->state !== -2") < hard_delete.index("->delete('#__jlms_question_revisions')")

# STU-01: resume discovery must not silently stop at the first 50 inaccessible enrollments.
dashboard=(ROOT/'component/administrator/src/Application/DashboardService.php').read_text()
summary=dashboard.split('public function summary(',1)[1].split('public function catalogStates(',1)[0]
assert 'setQuery($recentQuery, $offset, 50)' in summary
assert '$offset += count($recent)' in summary
assert 'while ($latest === null && count($recent) === 50)' in summary
assert "if ($card['access'] === 'available')" in summary

# CAT-02: the bounded catalogue batch must select one latest enrollment per
# requested course *before* applying its limit, not truncate owner history.
dashboard=(ROOT/'component/administrator/src/Application/DashboardService.php').read_text()
catalog=dashboard.split('public function catalogStates(',1)[1].split('public function cards(',1)[0]
assert "SELECT MAX(latest.id) FROM #__jlms_enrollments AS latest" in catalog
assert 'latest.user_id = e.user_id AND latest.course_id = e.course_id' in catalog
assert catalog.index("->where('e.id = (SELECT MAX(") < catalog.index("setQuery($query, 0, 50)")
assert (ROOT/'tests/dashboard-catalog-latest-regression.php').is_file()

# DOC-07: every scheduler state gets actionable IT/EN advice, and links
# to Joomla scheduler require explicit permission on com_scheduler.
scheduler=(ROOT/'component/administrator/src/Infrastructure/SchedulerHealth.php').read_text()
admin_dashboard=(ROOT/'component/administrator/tmpl/dashboard/default.php').read_text()
admin_dashboard_view=(ROOT/'component/administrator/src/View/Dashboard/HtmlView.php').read_text()
assert "public static function guidanceKey(string $state, bool $duplicates = false)" in scheduler
assert 'SchedulerHealth::guidanceKey(' in admin_dashboard
assert "$this->canManageScheduler" in admin_dashboard
assert "authorise('core.manage', 'com_scheduler')" in admin_dashboard_view
for locale in ('it-IT', 'en-GB'):
    lang=(ROOT/f'component/administrator/language/{locale}/com_jlms.ini').read_text()
    for suffix in ('MISSING','DISABLED','DUPLICATES','FAILED','OVERDUE','UNKNOWN','WAITING','OPERATIONAL'):
        assert lang.count(f'COM_JLMS_SCHEDULER_GUIDANCE_{suffix}=') == 1, (locale,suffix)
    assert lang.count('COM_JLMS_SCHEDULER_HEALTH_GUIDANCE=') == 1, locale
assert (ROOT/'tests/scheduler-guidance.php').is_file()

# CAT-01: input search terms are literal, not unescaped SQL LIKE patterns.
catalog_search=(ROOT/'component/site/src/Helper/CatalogSearch.php').read_text()
catalog_model=(ROOT/'component/site/src/Model/CoursesModel.php').read_text()
assert "str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $text)" in catalog_search
assert 'CatalogSearch::pattern($search)' in catalog_model
assert "ESCAPE '!'" in catalog_model
assert (ROOT/'tests/catalog-literal-search.php').is_file()

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
# Regression #411: these administrative pages expose one page title via the
# native Joomla toolbar; keep their guidance, table captions and section headings.
for name in ('mailqueue', 'mailvariables', 'categoryreassignment'):
 view=(ROOT/f'component/administrator/src/View/{name.capitalize()}/HtmlView.php').read_text()
 template=(ROOT/f'component/administrator/tmpl/{name}/default.php').read_text()
 assert 'ToolbarHelper::title(' in view, ('Missing native toolbar title', name)
 assert not re.search(r'<h1\\b', template, re.I), ('Duplicate H1 beneath toolbar', name)
assert 'COM_JLMS_MAIL_QUEUE_HELP' in (ROOT/'component/administrator/tmpl/mailqueue/default.php').read_text()
assert 'COM_JLMS_MAIL_VARIABLE_GUIDE_INTRO' in (ROOT/'component/administrator/tmpl/mailvariables/default.php').read_text()
message_view=(ROOT/'component/administrator/src/View/Coursemessage/HtmlView.php').read_text()
message_page=(ROOT/'component/administrator/tmpl/coursemessage/default.php').read_text()
operations_view=(ROOT/'component/administrator/src/View/Courseoperations/HtmlView.php').read_text()
operations_page=(ROOT/'component/administrator/tmpl/courseoperations/default.php').read_text()
assert "'COM_JLMS_ENROLLMENTS_TOOLBAR_SHORT', 'arrow-left'" in message_view
assert "Route::_('index.php?option=com_jlms&view=enrollments" not in message_page
assert "'COM_JLMS_ENROLLMENTS_TOOLBAR_SHORT', 'arrow-left'" in operations_view
for operation in ('admissions_close', 'admissions_open', 'delivery_suspend', 'delivery_resume'):
 assert operation in operations_view
assert '<nav aria-label=' not in operations_page
assert "Route::_('index.php?option=com_jlms&view=enrollments" not in operations_page
for locale in ('it-IT', 'en-GB'):
 strings=(ROOT/f'component/administrator/language/{locale}/com_jlms.ini').read_text()
 for key in ('COM_JLMS_ENROLLMENTS_TOOLBAR_SHORT','COM_JLMS_COURSE_ADMISSIONS_CLOSE_SHORT','COM_JLMS_COURSE_ADMISSIONS_OPEN_SHORT','COM_JLMS_COURSE_DELIVERY_SUSPEND_SHORT','COM_JLMS_COURSE_DELIVERY_RESUME_SHORT'):
  assert key+'=' in strings, (locale,key)
roster_view=(ROOT/'component/administrator/src/View/Enrollments/HtmlView.php').read_text()
roster_template=(ROOT/'component/administrator/tmpl/enrollments/default.php').read_text()
for action in ('manualenrollment', 'coursemessage', 'courseoperations'):
 assert f'view={action}' in roster_view, ('Missing roster toolbar action', action)
 assert f'view={action}' not in roster_template, ('Roster action duplicated in page body', action)
# Issue #455: keep navigation in the first toolbar slot on both enrollment
# views, retain the course context, and avoid duplicate body links.
assert roster_view.index('view=courses') < roster_view.index('view=manualenrollment')
assert "'COM_JLMS_COURSES', 'arrow-left'" in roster_view
assert "'COM_JLMS_MANUAL_TITLE', 'plus'" in roster_view
manual_view=(ROOT/'component/administrator/src/View/Manualenrollment/HtmlView.php').read_text()
manual_template=(ROOT/'component/administrator/tmpl/manualenrollment/default.php').read_text()
assert "view=enrollments&id=' . (int) $this->item['course']->id" in manual_view
assert "'COM_JLMS_ENROLLMENTS_TOOLBAR_SHORT', 'arrow-left'" in manual_view
assert 'jlms-manual-roster' not in manual_template
assert 'view=enrollments' not in manual_template
assert '<h2>' in (ROOT/'component/administrator/tmpl/categoryreassignment/default.php').read_text()
# Issue #416: translated model titles and IDs require a visible separator.
mail_variable_guide=(ROOT/'component/administrator/tmpl/mailvariables/default.php').read_text()
assert '<span class="text-muted small"> — <code>' in mail_variable_guide
assert 'd-block text-muted small' not in mail_variable_guide
# Regression #383: GET /administrator/index.php?option=com_jlms&view=coursemessage&id=N
# must enter the read-only display whitelist and retain Courses' native Atum context.
admin_display=(ROOT/'component/administrator/src/Controller/DisplayController.php').read_text()
assert "'manualenrollment', 'coursemessage', 'mailqueue'" in admin_display
assert "'manualenrollment', 'coursemessage', 'quiz'" in admin_display
assert "throw new \\RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404)" in admin_display
assert (ROOT/'component/administrator/src/View/Coursemessage/HtmlView.php').is_file()
assert (ROOT/'component/administrator/src/Model/CoursemessageModel.php').is_file()
assert (ROOT/'component/administrator/tmpl/coursemessage/default.php').is_file()
# Failed mail diagnostic display is restricted to known categories and ACL-protected audit.
audit_mail=(ROOT/'component/administrator/src/Application/MailQueueAuditService.php').read_text()
mail_queue_page=(ROOT/'component/administrator/tmpl/mailqueue/default.php').read_text()
assert "'q.created_at', 'q.last_error'" in audit_mail
assert "match ((string) ($row->last_error ?? ''))" in audit_mail
assert "unset($row->recipient_email, $row->last_error);" in audit_mail
assert "COM_JLMS_MAIL_QUEUE_DIAGNOSTIC_TEMPLATE" in audit_mail
assert "COM_JLMS_MAIL_QUEUE_DIAGNOSTIC_TRANSPORT" in audit_mail
assert "$this->escape(Text::_($row->diagnostic_key))" in mail_queue_page
for locale in ('it-IT', 'en-GB'):
    definitions=(ROOT/f'component/administrator/language/{locale}/com_jlms.ini').read_text()
    for key in ('COM_JLMS_MAIL_QUEUE_DIAGNOSTIC_TEMPLATE', 'COM_JLMS_MAIL_QUEUE_DIAGNOSTIC_TRANSPORT'):
        assert key+'=' in definitions, (locale,key)
# Message editor, preview and dispatch must use the same immutable snapshot.
compose=(ROOT/'component/administrator/src/Application/CourseCommunicationService.php').read_text()
dispatch=(ROOT/'component/administrator/src/Infrastructure/MailQueue.php').read_text()
composer_view=(ROOT/'component/administrator/tmpl/coursemessage/default.php').read_text()
sender=(ROOT/'component/administrator/src/Application/CourseMessageMailer.php').read_text()
# The same immutable template snapshot is canonicalized before job lookup and insertion.
assert "'template' => $plan['template']" in compose
assert "hash_equals((string) $previous->payload_json, $expectedPayload)" in compose
assert "'payload_json'=>$expectedPayload" in compose
assert "'__course_message_snapshot'" in compose and "'__course_message_snapshot'" in dispatch
assert "CourseMessageTemplate::validate($snapshot, $data)" in dispatch
assert "CourseMessageMailer::deliver(" in dispatch
assert "Factory::getMailer()" in sender and "->replaceTags(" in sender
assert "CourseMessageTemplate::validate(" in compose
assert "['rendered']['htmlbody']" in composer_view and 'sandbox=""' in composer_view
assert "Editor::getInstance(" in composer_view
assert "jform[template][htmlbody]" in composer_view
assert 'class="btn btn-danger"' not in composer_view
# Regression: course messages have exactly one editor and do not accept
# recursive legacy wrapper fields or diverging plaintext fields from POST.
controller_source=(ROOT/'component/administrator/src/Controller/CoursemessageController.php').read_text()
model_source=(ROOT/'component/administrator/src/Model/CoursemessageModel.php').read_text()
template_source=(ROOT/'component/administrator/src/Application/CourseMessageTemplate.php').read_text()
view_source=(ROOT/'component/administrator/tmpl/coursemessage/default.php').read_text()
assert 'jform[subject]' not in view_source
assert 'jform[body]' not in view_source
assert 'jform[template][body]' not in view_source
assert view_source.count("Editor::getInstance(")==1
assert "CourseMessageTemplate::forComposition(" in model_source
assert "CourseMessageTemplate::plainTextFromHtml(" in controller_source
assert "$raw['subject'] = $raw['template']['subject'];" in controller_source
assert "COM_JLMS_COMMUNICATION_TEMPLATE_LEGACY_SLOT" in template_source
assert "MESSAGE_BODY_HTML" in template_source
# Template-backed course composer: the native Joomla template is the source,
# and a per-message snapshot is checked before any future queue persistence.
composer=(ROOT/'component/administrator/src/Application/CourseMessageTemplate.php').read_text()
assert "MailTemplate::getTemplate(self::TEMPLATE_ID, $language)" in composer
assert "'com_jlms.course.message'" in composer
assert "MailTemplatePlaceholderValidator::unresolved(" in composer
assert "InputFilter::getInstance([], [], InputFilter::ONLY_BLOCK_DEFINED_TAGS, InputFilter::ONLY_BLOCK_DEFINED_ATTRIBUTES)" in composer
assert "throw new CourseMessageVariableException($missing)" in composer
assert "MailTemplate::updateTemplate" not in composer
# Regression #419: Joomla DatabaseQuery::bind() accepts values by reference.
# Passing a cast expression makes 'Accoda invio' fail with HTTP 500 before INSERT.
communication_source=(ROOT/'component/administrator/src/Application/CourseCommunicationService.php').read_text()
assert '$scopeCourseId = (string) $courseId;' in communication_source
assert '$ownerUserId = (int) $actor->id;' in communication_source
assert "->bind(':scopeCourse', $scopeCourseId)" in communication_source
assert "->bind(':owner', $ownerUserId, ParameterType::INTEGER)" in communication_source
assert "->bind(':scopeCourse',(string)$courseId)" not in communication_source
assert "->bind(':owner',(int)$actor->id" not in communication_source
communication_template=(ROOT/'component/administrator/tmpl/coursemessage/default.php').read_text()
assert 'class="btn btn-danger"' not in communication_template
assert 'COM_JLMS_COMMUNICATION_QUEUE' in communication_template and 'btn btn-primary' in communication_template
assert 'class="form-check-label" for="jlms-communication-confirm"' in communication_template
assert 'class="card mt-4 mb-3"' in communication_template
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
lesson_notice=(ROOT/'component/administrator/src/Application/LessonReleaseNotice.php').read_text()
assert "LessonAvailability::effectiveStart" in lesson_notice
assert "'lesson_release'" in (ROOT/'component/administrator/src/Application/MailPreferences.php').read_text()
assert "'mail_lesson_release'" in (ROOT/'component/administrator/src/Model/CourseModel.php').read_text()
assert 'mail_lesson_release_enabled' in (ROOT/'component/administrator/config.xml').read_text()
assert 'mail_lesson_release' in (ROOT/'component/administrator/forms/course.xml').read_text()
assert "'com_jlms.lesson.available' => 'lesson_release'" in (ROOT/'component/administrator/src/Infrastructure/MailQueue.php').read_text()
assert "lessonReleaseEligible($row)" in (ROOT/'component/administrator/src/Infrastructure/MailQueue.php').read_text()
assert "new \\Jlms\\Component\\Jlms\\Administrator\\Application\\LessonReleaseNotice($db)" in (ROOT/'plugins/task/jlms/src/Extension/Jlms.php').read_text()
course_update=(ROOT/'component/administrator/src/Application/CourseVersionChangeNotice.php').read_text()
assert "significantVersionChange" in course_update and "course.version.changed:" in course_update
assert "'com_jlms.course.updated' => 'course_update'" in (ROOT/'component/administrator/src/Infrastructure/MailQueue.php').read_text()
assert 'courseUpdateEligible($row)' in (ROOT/'component/administrator/src/Infrastructure/MailQueue.php').read_text()
assert 'mail_course_update_enabled' in (ROOT/'component/administrator/config.xml').read_text()
assert "'mail_course_update'" in (ROOT/'component/administrator/src/Model/CourseModel.php').read_text()
assert 'mail_course_update' in (ROOT/'component/administrator/forms/course.xml').read_text()
assert 'CourseVersionChangeNotice($db)' in (ROOT/'plugins/task/jlms/src/Extension/Jlms.php').read_text()
manager_notice=(ROOT/'component/administrator/src/Application/EnrollmentManagerNotice.php').read_text()
assert 'EnrollmentRosterService' in manager_notice and 'Access::checkGroup' in manager_notice
assert "'com_jlms.enrollment.manager_request' => 'manager_request'" in (ROOT/'component/administrator/src/Infrastructure/MailQueue.php').read_text()
assert 'managerRequestEligible($row)' in (ROOT/'component/administrator/src/Infrastructure/MailQueue.php').read_text()
mail_queue_source=(ROOT/'component/administrator/src/Infrastructure/MailQueue.php').read_text()
mail_catalog_source=(ROOT/'component/administrator/src/Infrastructure/MailTemplateVariableCatalog.php').read_text()
assert '$mail->addUnsafeTags(MailTemplateVariableCatalog::tagsRequiringHtmlEscape($templateId));' in mail_queue_source
assert '$mail->addUnsafeTags(array_keys($data));' not in mail_queue_source
assert 'tagsRequiringHtmlEscape' in mail_catalog_source
assert "['text', 'date', 'url']" in mail_catalog_source
assert 'mail_manager_request_enabled' in (ROOT/'component/administrator/config.xml').read_text()
assert "'mail_manager_request'" in (ROOT/'component/administrator/src/Model/CourseModel.php').read_text()
assert 'mail_manager_request' in (ROOT/'component/administrator/forms/course.xml').read_text()
assert 'EnrollmentManagerNotice($db)' in (ROOT/'plugins/task/jlms/src/Extension/Jlms.php').read_text()
# Joomla's Mail Templates manager resolves each ID title and description in both
# administrator language catalogs: .sys.ini for the list and .ini for the editor.
mail_definitions=(ROOT/'component/administrator/src/Infrastructure/MailTemplateInstaller.php').read_text()
mail_ids=[line.strip().split("'")[1] for line in mail_definitions.splitlines() if line.strip().startswith("'com_jlms.") and "' => [" in line]
assert len(mail_ids)==17 and len(set(mail_ids))==17,('Unexpected mail template inventory',mail_ids)
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
# Issues #406/#408: back-to-course is the first native Joomla toolbar action.
for view_name, template_name, label in [
 ('Curriculum', 'curriculum', 'COM_JLMS_PROGRAMME_BACK_COURSE'),
 ('Courseannouncement', 'courseannouncement', 'COM_JLMS_COURSE_ANNOUNCEMENT_PREVIEW_BACK'),
]:
 view_source=(ROOT/f'component/administrator/src/View/{view_name}/HtmlView.php').read_text()
 template_source=(ROOT/f'component/administrator/tmpl/{template_name}/default.php').read_text()
 assert "->linkButton('back-to-course', '"+label+"')" in view_source, view_name
 assert "->icon('icon-arrow-left')" in view_source, view_name
 assert 'task=course.edit&id=' in view_source or 'view=course&layout=edit&id=' in view_source, view_name
 assert label not in template_source, ('Back action must not remain in page body', view_name)
 assert view_source.index("->linkButton('back-to-course'") < view_source.index('parent::display($tpl)'), view_name
# The course editor exposes the curriculum action once, through its native toolbar.
course_editor=(ROOT/'component/administrator/tmpl/course/edit.php').read_text()
assert 'jlms-course-programme-link' not in course_editor
assert 'COM_JLMS_COURSE_SAVE_PROGRAMME' in course_editor
course_view=(ROOT/'component/administrator/src/View/Course/HtmlView.php').read_text()
assert "$toolbar->save('course.apply')" in course_view
assert "$toolbar->dropdownButton('save-group')" in course_view
assert "->linkButton('announcement-preview', 'COM_JLMS_COURSE_ANNOUNCEMENT_PREVIEW')" in course_view
assert "'index.php?option=com_jlms&view=courseannouncement&id=' . $id" in course_view
assert 'view=courseannouncement' not in course_editor
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
# P0 #532: installation must never ship a stale WAM query version.
source_assets=json.loads((ROOT/'component/media/joomla.asset.json').read_text())
assert source_assets['version']==version, ('stale source WAM version',source_assets['version'],version)
asset_sources=[(p.relative_to(ROOT/'component/media').as_posix(),p.read_bytes())
               for p in (ROOT/'component/media').rglob('*') if p.is_file() and p.name!='joomla.asset.json']
for key in ('youtube','vimeo','cloudflarestream','html5'):
 asset_sources.extend((key+'/'+p.relative_to(ROOT/'plugins/jlmsvideo'/key/'media').as_posix(),p.read_bytes())
                      for p in (ROOT/'plugins/jlmsvideo'/key/'media').rglob('*') if p.is_file())
fingerprint=hashlib.sha256()
for name,blob in sorted(asset_sources):
 fingerprint.update(name.encode('utf-8')+b'\0'+blob+b'\0')
expected_wam_version=version+'-'+fingerprint.hexdigest()[:12]
package=ROOT/'dist'/f'pkg_jlms-{version}-dev.zip'
if package.exists():
 with zipfile.ZipFile(package) as z:
  assert z.testzip() is None
  m=E.fromstring(z.read('pkg_jlms.xml'))
  for e in m.findall('./files/file'):
   with zipfile.ZipFile(io.BytesIO(z.read('constituents/'+e.text))) as child:
    assert child.testzip() is None
    names=set(child.namelist())
    if e.attrib['type']=='component':
     bundled=json.loads(child.read('media/joomla.asset.json'))
     assert bundled['version']==expected_wam_version, ('stale installed component asset fingerprint',bundled['version'],expected_wam_version)

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
assert "getCourseAnnouncementConsent()->state" in (ROOT/'component/site/src/Model/PreferencesModel.php').read_text()

# MAIL-11.3 anonymous unsubscribe is explicit POST-only, hashed, expiring and one-use.
assert 'createUnsubscribeUrl' in consent and 'revokeByToken' in consent
assert 'mail_unsubscribe_tokens' in consent and 'DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 DAY)' in consent
assert "->where('confirmed_at = :confirmed')" in consent
mail_controller=(ROOT/'component/site/src/Controller/MailconsentController.php').read_text()
assert "['request', 'confirm', 'revoke', 'revokelink']" in mail_controller.lower()
assert "strtoupper($this->input->getMethod()) !== 'POST'" in mail_controller
assert "->revokeByToken($token)" in mail_controller
assert "mailconsentrevoke" in (ROOT/'component/site/src/View/Mailconsentrevoke/HtmlView.php').read_text().lower()

# SEC-P1: new Joomla controllers require an explicit security classification.
# This is structural drift detection, not evidence of full endpoint ACL coverage.
runpy.run_path(str(ROOT / 'tests/sec-p1-controller-gates.py'), run_name='__main__')
runpy.run_path(str(ROOT / 'tests/sec-p1-admin-identity.py'), run_name='__main__')
runpy.run_path(str(ROOT / 'tests/sec-p1-mail-recipient-identity.py'), run_name='__main__')
# Additional packaged plugin, Scheduler and protected media surface anti-drift checks.
runpy.run_path(str(ROOT / 'tests/sec-p1-cross-surface-gate.py'), run_name='__main__')
# SEC-P1 cross-package high-risk execution, network, storage source/sink gate.
runpy.run_path(str(ROOT / 'tests/sec-p1-systematic-source-gate.py'), run_name='__main__')

# Documentation hygiene is checked with every standard build.
runpy.run_path(str(ROOT / 'tools/check_docs_hygiene.py'), run_name='__main__')

# JD Learn's administrator sidebar is a concise operational navigation, not a
# duplicate of the dashboard. Secondary native Joomla views remain reachable
# from the dashboard with their existing ACL guards.
manifest=(ROOT/'component/jlms.xml').read_text()
submenu=E.fromstring(manifest).find('./administration/submenu')
assert submenu is not None
actual_submenu_links=[node.attrib.get('link') for node in submenu.findall('menu')]
expected_submenu_links=[
 'option=com_jlms&view=dashboard',
 'option=com_jlms&view=courses',
 'option=com_jlms&view=pathways',
 'option=com_jlms&view=teachers',
 'option=com_jlms&view=questionbank',
]
assert actual_submenu_links==expected_submenu_links, {'actual': actual_submenu_links, 'expected': expected_submenu_links}
dashboard_view=(ROOT/'component/administrator/src/View/Dashboard/HtmlView.php').read_text()
for key in ('COM_JLMS_DASHBOARD_MAIL_VARIABLES','COM_JLMS_DASHBOARD_CATEGORY_DEPENDENCIES'):
 assert key in dashboard_view
assert "'COM_JLMS_MAIL_VARIABLE_GUIDE_TITLE', 'icon-info-circle', 'index.php?option=com_jlms&view=mailvariables'" not in dashboard_view
for tag in ('it-IT','en-GB'):
 messages=(ROOT/f'component/administrator/language/{tag}/com_jlms.ini').read_text()
 for key in ('COM_JLMS_DASHBOARD_MAIL_VARIABLES','COM_JLMS_DASHBOARD_CATEGORY_DEPENDENCIES','COM_JLMS_DASHBOARD_GROUP_CONFIGURATION'):
  assert len(re.findall(r'^'+key+r'=',messages,re.M))==1,(tag,key)

# Nested Joomla administrator views select native submenu entries; secondary
# views select the component root with no operational submenu selected.
admin_controller=(ROOT/'component/administrator/src/Controller/DisplayController.php').read_text()
for pair in ("'courses' => ['courses', 'course', 'curriculum'","'pathways' => ['pathways', 'pathway']","'teachers' => ['teachers', 'teacher']","'questionbank' => ['questionbank', 'question']"):
 assert pair in admin_controller,pair
assert '$menu->setActive((int) $item->id)' in admin_controller
assert "$app->getPathway()" not in admin_controller
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
assert "getCourseAnnouncementConsent()->state" in (ROOT/'component/site/src/Model/PreferencesModel.php').read_text()

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
# menu entries. Secondary views select the native component root.
admin_controller=(ROOT/'component/administrator/src/Controller/DisplayController.php').read_text()
for pair in ("'courses' => ['courses', 'course', 'curriculum'","'pathways' => ['pathways', 'pathway']","'teachers' => ['teachers', 'teacher']","'questionbank' => ['questionbank', 'question']"):
 assert pair in admin_controller,pair
assert '$menu->setActive((int) $item->id)' in admin_controller
assert "$app->getPathway()" not in admin_controller

# The dashboard-only email queue stays accessible but has no sidebar entry.
assert "'COM_JLMS_MAIL_QUEUE', 'icon-envelope'" in dashboard_view
assert "'COM_JLMS_DASHBOARD_CATEGORY_DEPENDENCIES', 'icon-folder'," in dashboard_view
assert "'COM_JLMS_DASHBOARD_CATEGORY_DEPENDENCIES', 'icon-folder-tree'," not in dashboard_view
assert "'mailqueue' => ['mailqueue']" not in admin_controller
assert "(int) ($item->parent_id ?? 0) !== 1" in admin_controller
assert "(int) ($item->parent_id ?? 0) === 1" in admin_controller


# Regression: Joomla DatabaseQuery::bind() expects stable variables (by reference).
# The stale mail reconciliation must never bind a ternary, cast or expression.
audit=(ROOT/'component/administrator/src/Application/MailQueueAuditService.php').read_text()
start=audit.index('public function reconcile(')
end=audit.index('private function filters(', start)
reconciliation=audit[start:end]
for expected in (
    "$newState = $action === 'sent' ? 'sent' : 'retry';",
    "$actorId = (int) $actor->id;",
    "->bind(':state', $newState)",
    "->bind(':actor', $actorId, ParameterType::INTEGER)",
    "->bind(':lastError', $lastError)",
    "->where(\"state = 'sending'\")",
    "self::STALE_MINUTES",
):
    assert expected in reconciliation, ('Unsafe stale mail reconciliation',expected)
for bad in (
    "->bind(':state', $action ===",
    "->bind(':actor', (int)",
    "->bind(':lastError', 'Manual reconciliation:",
):
    assert bad not in reconciliation, ('By-reference SQL bind expression',bad)
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
lesson_notice=(ROOT/'component/administrator/src/Application/LessonReleaseNotice.php').read_text()
assert "LessonAvailability::effectiveStart" in lesson_notice
assert "'lesson_release'" in (ROOT/'component/administrator/src/Application/MailPreferences.php').read_text()
assert "'mail_lesson_release'" in (ROOT/'component/administrator/src/Model/CourseModel.php').read_text()
assert 'mail_lesson_release_enabled' in (ROOT/'component/administrator/config.xml').read_text()
assert 'mail_lesson_release' in (ROOT/'component/administrator/forms/course.xml').read_text()
assert "'com_jlms.lesson.available' => 'lesson_release'" in (ROOT/'component/administrator/src/Infrastructure/MailQueue.php').read_text()
assert "lessonReleaseEligible($row)" in (ROOT/'component/administrator/src/Infrastructure/MailQueue.php').read_text()
assert "new \\Jlms\\Component\\Jlms\\Administrator\\Application\\LessonReleaseNotice($db)" in (ROOT/'plugins/task/jlms/src/Extension/Jlms.php').read_text()
course_update=(ROOT/'component/administrator/src/Application/CourseVersionChangeNotice.php').read_text()
assert "significantVersionChange" in course_update and "course.version.changed:" in course_update
assert "'com_jlms.course.updated' => 'course_update'" in (ROOT/'component/administrator/src/Infrastructure/MailQueue.php').read_text()
assert 'courseUpdateEligible($row)' in (ROOT/'component/administrator/src/Infrastructure/MailQueue.php').read_text()
assert 'mail_course_update_enabled' in (ROOT/'component/administrator/config.xml').read_text()
assert "'mail_course_update'" in (ROOT/'component/administrator/src/Model/CourseModel.php').read_text()
assert 'mail_course_update' in (ROOT/'component/administrator/forms/course.xml').read_text()
assert 'CourseVersionChangeNotice($db)' in (ROOT/'plugins/task/jlms/src/Extension/Jlms.php').read_text()
manager_notice=(ROOT/'component/administrator/src/Application/EnrollmentManagerNotice.php').read_text()
assert 'EnrollmentRosterService' in manager_notice and 'Access::checkGroup' in manager_notice
assert "'com_jlms.enrollment.manager_request' => 'manager_request'" in (ROOT/'component/administrator/src/Infrastructure/MailQueue.php').read_text()
assert 'managerRequestEligible($row)' in (ROOT/'component/administrator/src/Infrastructure/MailQueue.php').read_text()
mail_queue_source=(ROOT/'component/administrator/src/Infrastructure/MailQueue.php').read_text()
mail_catalog_source=(ROOT/'component/administrator/src/Infrastructure/MailTemplateVariableCatalog.php').read_text()
assert '$mail->addUnsafeTags(MailTemplateVariableCatalog::tagsRequiringHtmlEscape($templateId));' in mail_queue_source
assert '$mail->addUnsafeTags(array_keys($data));' not in mail_queue_source
assert 'tagsRequiringHtmlEscape' in mail_catalog_source
assert "['text', 'date', 'url']" in mail_catalog_source
assert 'mail_manager_request_enabled' in (ROOT/'component/administrator/config.xml').read_text()
assert "'mail_manager_request'" in (ROOT/'component/administrator/src/Model/CourseModel.php').read_text()
assert 'mail_manager_request' in (ROOT/'component/administrator/forms/course.xml').read_text()
assert 'EnrollmentManagerNotice($db)' in (ROOT/'plugins/task/jlms/src/Extension/Jlms.php').read_text()
# Joomla's Mail Templates manager resolves each ID title and description in both
# administrator language catalogs: .sys.ini for the list and .ini for the editor.
mail_definitions=(ROOT/'component/administrator/src/Infrastructure/MailTemplateInstaller.php').read_text()
mail_ids=[line.strip().split("'")[1] for line in mail_definitions.splitlines() if line.strip().startswith("'com_jlms.") and "' => [" in line]
assert len(mail_ids)==17 and len(set(mail_ids))==17,('Unexpected mail template inventory',mail_ids)
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
# Issues #406/#408: back-to-course is the first native Joomla toolbar action.
for view_name, template_name, label in [
 ('Curriculum', 'curriculum', 'COM_JLMS_PROGRAMME_BACK_COURSE'),
 ('Courseannouncement', 'courseannouncement', 'COM_JLMS_COURSE_ANNOUNCEMENT_PREVIEW_BACK'),
]:
 view_source=(ROOT/f'component/administrator/src/View/{view_name}/HtmlView.php').read_text()
 template_source=(ROOT/f'component/administrator/tmpl/{template_name}/default.php').read_text()
 assert "->linkButton('back-to-course', '"+label+"')" in view_source, view_name
 assert "->icon('icon-arrow-left')" in view_source, view_name
 assert 'task=course.edit&id=' in view_source or 'view=course&layout=edit&id=' in view_source, view_name
 assert label not in template_source, ('Back action must not remain in page body', view_name)
 assert view_source.index("->linkButton('back-to-course'") < view_source.index('parent::display($tpl)'), view_name
# The course editor exposes the curriculum action once, through its native toolbar.
course_editor=(ROOT/'component/administrator/tmpl/course/edit.php').read_text()
assert 'jlms-course-programme-link' not in course_editor
assert 'COM_JLMS_COURSE_SAVE_PROGRAMME' in course_editor
course_view=(ROOT/'component/administrator/src/View/Course/HtmlView.php').read_text()
assert "$toolbar->save('course.apply')" in course_view
assert "$toolbar->dropdownButton('save-group')" in course_view
assert "->linkButton('announcement-preview', 'COM_JLMS_COURSE_ANNOUNCEMENT_PREVIEW')" in course_view
assert "'index.php?option=com_jlms&view=courseannouncement&id=' . $id" in course_view
assert 'view=courseannouncement' not in course_editor
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
assert "getCourseAnnouncementConsent()->state" in (ROOT/'component/site/src/Model/PreferencesModel.php').read_text()

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
actual_submenu_links=[node.attrib.get('link') for node in submenu.findall('menu')]
expected_submenu_links=[
 'option=com_jlms&view=dashboard',
 'option=com_jlms&view=courses',
 'option=com_jlms&view=pathways',
 'option=com_jlms&view=teachers',
 'option=com_jlms&view=questionbank',
]
assert actual_submenu_links==expected_submenu_links, {'actual': actual_submenu_links, 'expected': expected_submenu_links}
dashboard_view=(ROOT/'component/administrator/src/View/Dashboard/HtmlView.php').read_text()
for key in ('COM_JLMS_DASHBOARD_MAIL_VARIABLES','COM_JLMS_DASHBOARD_CATEGORY_DEPENDENCIES'):
 assert key in dashboard_view
assert "'COM_JLMS_MAIL_VARIABLE_GUIDE_TITLE', 'icon-info-circle', 'index.php?option=com_jlms&view=mailvariables'" not in dashboard_view
for tag in ('it-IT','en-GB'):
 messages=(ROOT/f'component/administrator/language/{tag}/com_jlms.ini').read_text()
 for key in ('COM_JLMS_DASHBOARD_MAIL_VARIABLES','COM_JLMS_DASHBOARD_CATEGORY_DEPENDENCIES','COM_JLMS_DASHBOARD_GROUP_CONFIGURATION'):
  assert len(re.findall(r'^'+key+r'=',messages,re.M))==1,(tag,key)

# Nested Joomla administrator views select native submenu entries; secondary
# views select the component root with no operational submenu selected.
admin_controller=(ROOT/'component/administrator/src/Controller/DisplayController.php').read_text()
for pair in ("'courses' => ['courses', 'course', 'curriculum'","'pathways' => ['pathways', 'pathway']","'teachers' => ['teachers', 'teacher']","'questionbank' => ['questionbank', 'question']"):
 assert pair in admin_controller,pair
assert '$menu->setActive((int) $item->id)' in admin_controller
assert "$app->getPathway()" not in admin_controller
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
assert "getCourseAnnouncementConsent()->state" in (ROOT/'component/site/src/Model/PreferencesModel.php').read_text()

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
# menu entries. Secondary views select the native component root.
admin_controller=(ROOT/'component/administrator/src/Controller/DisplayController.php').read_text()
for pair in ("'courses' => ['courses', 'course', 'curriculum'","'pathways' => ['pathways', 'pathway']","'teachers' => ['teachers', 'teacher']","'questionbank' => ['questionbank', 'question']"):
 assert pair in admin_controller,pair
assert '$menu->setActive((int) $item->id)' in admin_controller
assert "$app->getPathway()" not in admin_controller

# The dashboard-only email queue stays accessible but has no sidebar entry.
assert "'COM_JLMS_MAIL_QUEUE', 'icon-envelope'" in dashboard_view
assert "'COM_JLMS_DASHBOARD_CATEGORY_DEPENDENCIES', 'icon-folder'," in dashboard_view
assert "'COM_JLMS_DASHBOARD_CATEGORY_DEPENDENCIES', 'icon-folder-tree'," not in dashboard_view
assert "'mailqueue' => ['mailqueue']" not in admin_controller
assert "(int) ($item->parent_id ?? 0) !== 1" in admin_controller
assert "(int) ($item->parent_id ?? 0) === 1" in admin_controller


# Regression: Joomla DatabaseQuery::bind() expects stable variables (by reference).
# The stale mail reconciliation must never bind a ternary, cast or expression.
audit=(ROOT/'component/administrator/src/Application/MailQueueAuditService.php').read_text()
start=audit.index('public function reconcile(')
end=audit.index('private function filters(', start)
reconciliation=audit[start:end]
for expected in (
    "$newState = $action === 'sent' ? 'sent' : 'retry';",
    "$actorId = (int) $actor->id;",
    "->bind(':state', $newState)",
    "->bind(':actor', $actorId, ParameterType::INTEGER)",
    "->bind(':lastError', $lastError)",
    "->where(\"state = 'sending'\")",
    "self::STALE_MINUTES",
):
    assert expected in reconciliation, ('Unsafe stale mail reconciliation',expected)
for bad in (
    "->bind(':state', $action ===",
    "->bind(':actor', (int)",
    "->bind(':lastError', 'Manual reconciliation:",
):
    assert bad not in reconciliation, ('By-reference SQL bind expression',bad)


# Issue #308: native Joomla certificate list and visible per-course policy.
certificate_model = (ROOT / 'component/administrator/src/Model/CertificatesModel.php').read_text()
certificate_view = (ROOT / 'component/administrator/src/View/Certificates/HtmlView.php').read_text()
certificate_list = (ROOT / 'component/administrator/tmpl/certificates/default.php').read_text()
certificate_filters = (ROOT / 'component/administrator/forms/filter_certificates.xml').read_text()
certificate_service = (ROOT / 'component/administrator/src/Application/CertificateService.php').read_text()
course_editor = (ROOT / 'component/administrator/tmpl/course/edit.php').read_text()
course_view = (ROOT / 'component/administrator/src/View/Course/HtmlView.php').read_text()
assert 'extends ListModel' in certificate_model
assert "'filter_fields'" in certificate_model and "'filter.status'" in certificate_model
assert "'FilterForm'" in certificate_view and "'Pagination'" in certificate_view
assert "joomla.searchtools.default" in certificate_list and "getListFooter()" in certificate_list
assert "searchtools.sort" in certificate_list and "view=certificate&id=" in certificate_list
assert 'name="fullordering"' in certificate_filters and 'name="limit"' in certificate_filters
assert 'name="status"' in certificate_filters and 'name="search"' in certificate_filters
assert "$allowedOrders[$order]" in certificate_service and "'certificate_state'" in certificate_service
assert "renderField('certificate_enabled')" in course_editor and "jlms-course-certificates" in course_editor
assert "setFieldAttribute('certificate_enabled', 'disabled', 'true')" in course_view


# CERT-08: regeneration is an authenticated, CSRF-checked explicit POST and never
# changes the immutable certificate identity/snapshot or sends a second notice.
regen_controller = (ROOT / 'component/administrator/src/Controller/CertificateController.php').read_text()
regen_service = (ROOT / 'component/administrator/src/Application/CertificateService.php').read_text()
regen_detail = (ROOT / 'component/administrator/tmpl/certificate/default.php').read_text()
assert "public function regenerate(): void" in regen_controller
assert "getMethod()) !== 'POST'" in regen_controller and "checkToken('post', false)" in regen_controller
assert "CurriculumService::id($this->input->post->get('revision'" in regen_controller
assert "public function adminRegenerate(" in regen_service
assert "assertCertificateAdministrator($identity)" in regen_service
assert "FOR UPDATE" in regen_service and "COM_JLMS_CERTIFICATE_REVISION_CONFLICT" in regen_service
assert "identity_snapshot_json !== (string) $record->identity_snapshot_json" in regen_service
assert "template_snapshot_json !== (string) $record->template_snapshot_json" in regen_service
assert "transactionStart()" in regen_service and "recordCertificateAudit($certificateId" in regen_service
assert "admin_regenerate" in regen_service and "'pdf_asset_id' => $newAssetId" in regen_service
assert "storage->remove((object) $staged)" in regen_service
assert "'revision' => (int) $record->revision" in regen_service
assert 'task" value="certificate.regenerate"' in regen_detail
assert 'name="confirmation"' in regen_detail and 'name="reason"' in regen_detail
assert "form.token" in regen_detail and "['status'] === 'ready'" in regen_detail
for locale in ('it-IT', 'en-GB'):
    t = (ROOT / 'component/administrator/language' / locale / 'com_jlms.ini').read_text()
    assert 'COM_JLMS_CERTIFICATE_REGENERATE=' in t
    assert 'COM_JLMS_CERTIFICATE_REVISION_CONFLICT=' in t


# PR #475 regression: never call getState() from inside Joomla populateState().
cert_model = (ROOT / 'component/administrator/src/Model/CertificatesModel.php').read_text()
cert_populate = cert_model.split('protected function populateState(', 1)[1].split('private function listing(', 1)[0]
assert '$this->getState(' not in cert_populate, 'Certificate populateState recursion reintroduced'
assert "$this->state->get('list.limit'" in cert_populate
assert "$this->state->get('list.start'" in cert_populate

# Issue #472: expose the existing public pathway catalogue to the Joomla menu manager.
pathways_menu = ROOT / 'component/site/tmpl/pathways/default.xml'
pathways_metadata = E.parse(pathways_menu).getroot()
assert pathways_metadata.find('./layout').attrib['title'] == 'COM_JLMS_MENU_PATHWAYS_TITLE'
assert pathways_metadata.find('./layout/message').text == 'COM_JLMS_MENU_PATHWAYS_DESC'
assert (ROOT / 'component/site/tmpl/pathways/default.php').exists()
assert (ROOT / 'component/site/src/View/Pathways/HtmlView.php').exists()
for locale in ('it-IT', 'en-GB'):
 for client in ('site', 'administrator'):
  translations = (ROOT / 'component' / client / 'language' / locale / 'com_jlms.sys.ini').read_text()
  assert 'COM_JLMS_MENU_PATHWAYS_TITLE=' in translations
  assert 'COM_JLMS_MENU_PATHWAYS_DESC=' in translations

# LCM-01: lifecycle gating must cover version migrations and asynchronous lesson
# notifications at both producer and SMTP-dispatch revalidation boundaries.
migration_code = (ROOT/'component/administrator/src/Application/EnrollmentMigrationService.php').read_text()
lesson_notice = (ROOT/'component/administrator/src/Application/LessonReleaseNotice.php').read_text()
version_notice = (ROOT/'component/administrator/src/Application/CourseVersionChangeNotice.php').read_text()
mail_queue = (ROOT/'component/administrator/src/Infrastructure/MailQueue.php').read_text()
assert "select('current_version_id, delivery_enabled, state')" in migration_code
assert "'blocked'=>(int)$courseRecord->state!==1?'COM_JLMS_LEARNING_DENIED'" in migration_code
assert migration_code.count("if ($plan['blocked']!==null)") >= 2
assert "->where('c.state = 1')->where('c.delivery_enabled = 1')" in lesson_notice
assert "->where('c.state = 1')->where('c.delivery_enabled = 1')" in version_notice
assert mail_queue.count("->where('c.state = 1')->where('c.delivery_enabled = 1')") >= 2

# FE-05 private preferences contract
import runpy
runpy.run_path(str(ROOT/'tests/frontend-preferences.py'), run_name='__main__')

# FE-05: summarize owner activity without repeating autonomous personal views.
runpy.run_path(str(ROOT/'tests/frontend-learner-summary.py'), run_name='__main__')

# FE-06: optional personal navigation is governed by each Joomla menu item.
runpy.run_path(str(ROOT/'tests/frontend-personal-navigation.py'), run_name='__main__')

# FE-07: public pathway cards, accessible ordered sequence and safe artwork.
runpy.run_path(str(ROOT/'tests/frontend-pathway-layout.py'), run_name='__main__')

# FE-08 native category menu options preserve section-specific ACL projections.
runpy.run_path(str(ROOT/'tests/frontend-category-sections.py'), run_name='__main__')

# DOC-07: video provider remediation messages must remain complete in both locales.
for locale in ('it-IT', 'en-GB'):
 provider_lang = (ROOT / 'component/administrator/language' / locale / 'com_jlms.ini').read_text()
 for suffix in ('NOT_INSTALLED', 'DISABLED', 'UNAVAILABLE', 'NOT_CONFIGURED', 'READY', 'UNKNOWN'):
  assert provider_lang.count('COM_JLMS_PROVIDER_GUIDANCE_' + suffix + '=') == 1, (locale, suffix)
provider_model = (ROOT / 'component/administrator/src/Application/DashboardProviderStatus.php').read_text()
provider_template = (ROOT / 'component/administrator/tmpl/dashboard/default.php').read_text()
assert 'public static function guidanceKey(' in provider_model
assert 'DashboardProviderStatus::guidanceKey(' in provider_template

# Functional criterion reconciliation: no unsupported completion percentage.
import subprocess as _progress_subprocess
import sys as _progress_sys
_progress_subprocess.run([_progress_sys.executable, str(ROOT/'tools/functional_audit.py'), '--check'], check=True)
_progress_subprocess.run([_progress_sys.executable, str(ROOT/'tools/functional_progress.py')], check=True)
_progress_subprocess.run([_progress_sys.executable, str(ROOT/'tests/functional-progress.py')], check=True)

# PBASE-02: the four shipped provider guides track actual plugin manifest fields.
runpy.run_path(str(ROOT/'tests/provider-doc-contract.py'), run_name='__main__')

# PBASE-03: prove the standalone Joomla builder rejects missing and stale provider guides.
runpy.run_path(str(ROOT/'tests/provider-package-gate.py'), run_name='__main__')

# PBASE-03: verify the example against the actual v1 registry and Joomla plugin skeleton.
_progress_subprocess.run(['php', str(ROOT/'tests/provider-contract-example.php')], check=True)

# UI-07: reject unpublished DOM selector changes and missing page contracts.
runpy.run_path(str(ROOT/'tests/frontend-dom-contracts.py'), run_name='__main__')

# DOC-07: read-only high-risk operational guidance and IT/EN ACL gate.
runpy.run_path(str(ROOT/'tests/doc07-safe-operations.py'), run_name='__main__')

# DOC-08: every 1.0.0 core flow must have a guide; manual release acceptance is distinct.
runpy.run_path(str(ROOT/'tests/doc08-gate.py'), run_name='__main__')
runpy.run_path(str(ROOT/'tools/doc08_gate.py'), run_name='__main__')

# DOC-02: offline administrator manual chapters and anchors in both languages.
runpy.run_path(str(ROOT/'tests/doc02-admin-manual.py'), run_name='__main__')

# SEC-P2/SEC-P3: fail-closed privacy removal and read-only legacy-storage inspection.
runpy.run_path(str(ROOT/'tests/sec-p2-p3-guard.py'), run_name='__main__')
