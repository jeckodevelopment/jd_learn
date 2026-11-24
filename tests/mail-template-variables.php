<?php

declare(strict_types=1);

require_once __DIR__ . '/../component/administrator/src/Infrastructure/MailTemplateInstaller.php';
require_once __DIR__ . '/../component/administrator/src/Infrastructure/MailTemplateVariableCatalog.php';
require_once __DIR__ . '/../component/administrator/src/Infrastructure/MailTemplatePlaceholderValidator.php';
require_once __DIR__ . '/../component/administrator/src/Infrastructure/MailTemplateTagUpdater.php';
require_once __DIR__ . '/../component/administrator/src/Infrastructure/MailTemplateContext.php';

// MAIL-10: optional course start dates are absolute, validated and UTC-normalized.
$courseDateParser = new ReflectionMethod(
    \Jlms\Component\Jlms\Administrator\Infrastructure\MailTemplateContext::class,
    'dateValue'
);
foreach ([null, '', 'tomorrow', '2026-02-30', '2026-13-01', '2026-11-13 junk'] as $invalid) {
    if ($courseDateParser->invoke(null, $invalid) !== '') {
        throw new RuntimeException('Unsafe course start date accepted in email context.');
    }
}
if (
    $courseDateParser->invoke(null, '2026-11-13 12:00:00') !== '2026-11-13 12:00:00 UTC'
    || $courseDateParser->invoke(null, '2026-11-13T12:00:00Z') !== '2026-11-13 12:00:00 UTC'
    || $courseDateParser->invoke(null, '2026-11-13T14:00:00+02:00') !== '2026-11-13 12:00:00 UTC'
) {
    throw new RuntimeException('Course start date was not normalized safely to UTC.');
}


use Jlms\Component\Jlms\Administrator\Infrastructure\MailTemplateInstaller;
use Jlms\Component\Jlms\Administrator\Infrastructure\MailTemplateVariableCatalog;
use Jlms\Component\Jlms\Administrator\Infrastructure\MailTemplatePlaceholderValidator;
use Jlms\Component\Jlms\Administrator\Infrastructure\MailTemplateTagUpdater;

$method = new ReflectionMethod(MailTemplateInstaller::class, 'definitions');
$definitions = $method->invoke(null);
if (count($definitions) < 10) {
    throw new RuntimeException('Unexpected mail template catalog size.');
}
$deprecated = ['NAME', 'COURSE', 'SITENAME', 'NAME_HTML', 'COURSE_HTML', 'SITENAME_HTML'];
foreach ($definitions as $id => [$subject, $body, $tags, $html]) {
    if (count($tags) !== count(array_unique($tags))) {
        throw new RuntimeException('Duplicate tags: ' . $id);
    }
    foreach (['it-IT', 'en-GB'] as $locale) {
        $catalog = MailTemplateVariableCatalog::forTemplate($id, $locale);
        if (array_keys($catalog) !== $tags) {
            throw new RuntimeException('Catalog and declared tags disagree: ' . $id);
        }
        foreach ($catalog as $entry) {
            if ($entry['description'] === '' || $entry['type'] === '') {
                throw new RuntimeException('Missing catalog metadata: ' . $id);
            }
        }
    }
    foreach ($deprecated as $tag) {
        if (in_array($tag, $tags, true)) {
            throw new RuntimeException('Deprecated tag in catalog: ' . $id . ':' . $tag);
        }
    }
    preg_match_all('/\\{([A-Z][A-Z0-9_]*)\\}/', $subject . "\n" . $body . "\n" . $html, $matches);
    foreach (array_unique($matches[1]) as $tag) {
        if (!in_array($tag, $tags, true)) {
            throw new RuntimeException('Undeclared mail tag: ' . $id . ':' . $tag);
        }
    }
}
// MAIL-G: teacher notices use only canonical, documented, recipient-scoped tokens.
$teacherTemplate = $definitions['com_jlms.teacher.assigned'] ?? null;
if (!$teacherTemplate || !in_array('TEACHER_NAME', $teacherTemplate[2], true)) {
    throw new RuntimeException('Native teacher assignment mail template missing.');
}
foreach (
    [
    'component/administrator/config.xml' => 'mail_teacher_assignment_enabled',
    'component/administrator/forms/course.xml' => 'mail_teacher_assignment',
    'component/administrator/src/Application/MailPreferences.php' => "'teacher_assignment'",
    'component/administrator/src/Infrastructure/MailQueue.php' => "'com_jlms.teacher.assigned' => 'teacher_assignment'",
    'component/administrator/src/Model/CourseModel.php' => 'queueAdded($id, $previousTeacherIds, $teachers)',
    'component/administrator/src/Application/TeacherAssignmentNotice.php' => 'public function queueAdded(',
    ] as $file => $expected
) {
    $source = file_get_contents(__DIR__ . '/../' . $file);
    if ($source === false || !str_contains($source, $expected)) {
        throw new RuntimeException('MAIL-G assignment notification wiring incomplete: ' . $file);
    }
}

// Approved course and lesson context is declared, rendered and produced by the
// queue boundary. USER_FIRSTNAME is intentionally not a supported tag.
$contextTags = [
    'COURSE_DESCRIPTION',
    'COURSE_DURATION',
    'COURSE_START_DATE',
    'LESSON_DESCRIPTION',
    'LESSON_URL',
    'TEACHER_NAME',
    'CONTACT_EMAIL'
];
$contextSource = file_get_contents(__DIR__ . '/../component/administrator/src/Infrastructure/MailTemplateContext.php');
$queueSource = file_get_contents(__DIR__ . '/../component/administrator/src/Infrastructure/MailQueue.php');
if (
    $contextSource === false || $queueSource === false
    || !str_contains($queueSource, 'MailTemplateContext::enrich($this->db, $courseId, $data)')
) {
    throw new RuntimeException('Mail template context enrichment is not connected to both queue paths.');
}
foreach (['com_jlms.lesson.available', 'com_jlms.teacher.assigned', 'com_jlms.course.published'] as $templateId) {
    $scopedTags = array_intersect($contextTags, [
        'COURSE_DESCRIPTION',
        'COURSE_DURATION',
        'COURSE_START_DATE',
        'CONTACT_EMAIL'
    ]);
    if ($templateId === 'com_jlms.lesson.available') {
        $scopedTags = array_merge($scopedTags, ['LESSON_DESCRIPTION', 'LESSON_URL']);
    }
    if ($templateId === 'com_jlms.teacher.assigned') {
        $scopedTags[] = 'TEACHER_NAME';
    }
    foreach ($scopedTags as $tag) {
        if (!in_array($tag, $definitions[$templateId][2], true)) {
            throw new RuntimeException('Approved context tag missing from ' . $templateId . ': ' . $tag);
        }
    }
}
$registeredTags = [];
foreach ($definitions as $definition) {
    $registeredTags = array_merge($registeredTags, $definition[2]);
}
if (in_array('USER_FIRSTNAME', $registeredTags, true)) {
    throw new RuntimeException('USER_FIRSTNAME must not be registered without a reliable name source.');
}
foreach (
    ['LESSON_URL' => 'view=classroom', 'LESSON_DESCRIPTION' => "'LESSON_DESCRIPTION' => ''",
          'COURSE_DURATION' => 'estimated_minutes', 'COURSE_START_DATE' => 'starts_at',
          'CONTACT_EMAIL' => 'FILTER_VALIDATE_EMAIL'] as $tag => $sourceNeedle
) {
    if (!str_contains($contextSource, $sourceNeedle)) {
        throw new RuntimeException('Mail context producer missing safe source for ' . $tag);
    }
}

$cancelled = $definitions['com_jlms.enrollment.cancelled'] ?? null;
if (
    !$cancelled || !str_contains($cancelled[1], '{COURSE_TITLE}')
    || !str_contains($cancelled[3], '{USER_FULLNAME}')
    || $cancelled[2] !== ['USER_FULLNAME', 'COURSE_TITLE', 'SITE_NAME']
) {
    throw new RuntimeException('Cancellation mail must be declared in native HTML/text templates.');
}
foreach (
    [
    'component/administrator/config.xml' => 'mail_cancellation_enabled',
    'component/administrator/forms/course.xml' => 'mail_cancellation',
    'component/administrator/src/Infrastructure/MailQueue.php' => "'com_jlms.enrollment.cancelled' => 'cancellation'",
    'component/administrator/src/Application/MailPreferences.php' => "'cancellation'",
    'component/administrator/src/Application/EnrollmentStateService.php' =>
         "'cancelled' => 'com_jlms.enrollment.cancelled'",
    ] as $file => $expected
) {
    $contents = file_get_contents(__DIR__ . '/../' . $file);
    if ($contents === false || !str_contains($contents, $expected)) {
        throw new RuntimeException('Missing cancellation event wiring: ' . $file);
    }
}

// MAIL-F: the public request action must use the native queue and course-level preferences.
foreach (
    [
    'component/administrator/config.xml' => 'mail_request_enabled',
    'component/administrator/forms/course.xml' => 'mail_request',
    'component/administrator/src/Infrastructure/MailQueue.php' => "'com_jlms.enrollment.requested' => 'request'",
    'component/administrator/src/Application/MailPreferences.php' => "'request'",
    'component/administrator/src/Application/LearningService.php' => 'public function requestApproval(',
    'component/site/src/Controller/EnrollmentController.php' => 'public function request(): void',
    'component/site/tmpl/course/default.php' => 'jlms-enrollment-request-form',
    ] as $file => $expected
) {
    $source = file_get_contents(__DIR__ . '/../' . $file);
    if ($source === false || !str_contains($source, $expected)) {
        throw new RuntimeException('Incomplete approval request wiring: ' . $file);
    }
}
foreach (['it-IT', 'en-GB'] as $locale) {
    $admin = file_get_contents(__DIR__ . '/../component/administrator/language/' . $locale . '/com_jlms.ini');
    $site = file_get_contents(__DIR__ . '/../component/site/language/' . $locale . '/com_jlms.ini');
    if (
        $admin === false || !str_contains($admin, 'COM_JLMS_MAIL_REQUEST_ENABLED=')
        || $site === false || !str_contains($site, 'COM_JLMS_ENROLLMENT_REQUEST_SUBMIT=')
    ) {
        throw new RuntimeException('Missing MAIL-F translation: ' . $locale);
    }
}

// MAIL-F: outcome notifications and the administrative authorization boundary are explicit.
foreach (
    ['approved' => ['USER_FULLNAME','COURSE_TITLE','DASHBOARD_URL','SITE_NAME'],
          'rejected' => ['USER_FULLNAME','COURSE_TITLE','SITE_NAME']] as $event => $expectedTags
) {
    $id = 'com_jlms.enrollment.' . $event;
    if (!isset($definitions[$id]) || $definitions[$id][2] !== $expectedTags) {
        throw new RuntimeException('MAIL-F outcome template missing or inconsistent: ' . $id);
    }
}
foreach (
    [
    'component/administrator/config.xml' => 'mail_approval_enabled',
    'component/administrator/forms/course.xml' => 'mail_rejection',
    'component/administrator/src/Infrastructure/MailQueue.php' => "'com_jlms.enrollment.approved' => 'approval'",
    'component/administrator/src/Application/MailPreferences.php' => "'approval', 'rejection'",
    'component/administrator/src/Application/EnrollmentApprovalService.php' => "FOR UPDATE",
    'component/administrator/src/Infrastructure/AssessmentAudit.php' =>
         "'enrollmentapproval'=>'COM_JLMS_LOG_ENROLLMENT_APPROVAL'",
    'component/administrator/src/Controller/EnrollmentapprovalController.php' => "checkToken('post', false)",
    'component/administrator/tmpl/enrollments/default.php' => 'enrollmentapproval.decide',
    ] as $file => $needle
) {
    $source = file_get_contents(__DIR__ . '/../' . $file);
    if ($source === false || !str_contains($source, $needle)) {
        throw new RuntimeException('MAIL-F outcome wiring missing: ' . $file);
    }
}

$confirmed = $definitions['com_jlms.enrollment.confirmed'];
$completed = $definitions['com_jlms.course.completed'];
if (
    !in_array('ENROLLMENT_DATE', $confirmed[2], true)
    || !str_contains($confirmed[1], '{ENROLLMENT_DATE}')
    || !str_contains($confirmed[3], '{ENROLLMENT_DATE}')
) {
    throw new RuntimeException('Enrollment date must be registered in both text and HTML.');
}
if (
    !in_array('COMPLETION_DATE', $completed[2], true)
    || !str_contains($completed[1], '{COMPLETION_DATE}')
    || !str_contains($completed[3], '{COMPLETION_DATE}')
) {
    throw new RuntimeException('Completion date must be registered in both text and HTML.');
}
foreach (
    [
    'ManualEnrollmentService.php' => ['\'ENROLLMENT_DATE\' => $now'],
    'LearningService.php' => ['\'ENROLLMENT_DATE\' => $now', '\'COMPLETION_DATE\' => $completedAt',
        ('$this->queueCourseCompletion($actor, $enrollmentId, (int) '
            . '$version->course_id, $language, (string) $update->completed_at)')],
    ] as $producerFile => $needles
) {
    $producer = file_get_contents(__DIR__ . '/../component/administrator/src/Application/' . $producerFile);
    foreach ($needles as $needle) {
        if ($producer === false || !str_contains($producer, $needle)) {
            throw new RuntimeException('Missing persisted timestamp producer contract: ' . $producerFile);
        }
    }
}
foreach (['it-IT', 'en-GB'] as $locale) {
    if (
        MailTemplateVariableCatalog::forTemplate('com_jlms.enrollment.confirmed', $locale)['ENROLLMENT_DATE']['type'] !== 'date'
        || MailTemplateVariableCatalog::forTemplate('com_jlms.course.completed', $locale)['COMPLETION_DATE']['type'] !== 'date'
    ) {
        throw new RuntimeException('Missing typed localized date variables: ' . $locale);
    }
}



// Upgrades migrate only legacy placeholders in installed Joomla templates.
$legacy = ('Hello {NAME}, {COURSE} on {SITENAME}; {NAME_HTML} / {COURSE_HTML} '
    . '/ {SITENAME_HTML}. Keep {CUSTOM} and {COURSE_TITLE}.');
$canonical = ('Hello {USER_FULLNAME}, {COURSE_TITLE} on {SITE_NAME}; '
    . '{USER_FULLNAME_HTML} / {COURSE_TITLE_HTML} / {SITE_NAME_HTML}. '
    . 'Keep {CUSTOM} and {COURSE_TITLE}.');
$migrated = MailTemplateTagUpdater::migratePlaceholders($legacy);
if ($migrated !== $canonical || MailTemplateTagUpdater::migratePlaceholders($migrated) !== $canonical) {
    throw new RuntimeException('Installed mail placeholder migration must preserve content and be idempotent.');
}
if (MailTemplateTagUpdater::migratePlaceholders('{COURSE_TITLE} {NAME_SUFFIX} {NAME}') !== '{COURSE_TITLE} {NAME_SUFFIX} {USER_FULLNAME}') {
    throw new RuntimeException('Mail placeholder migration must match exact legacy tokens only.');
}

// Joomla upgrades must expose new canonical variables without changing editorial
// content or non-tag mailer settings, including on localized template rows.
$originalParams = [
    'tags' => ['USER_FULLNAME', 'COURSE_TITLE', 'NAME', 'EXTRA_CUSTOM', 'COURSE_TITLE'],
    'mailfrom' => 'operator@example.invalid',
    'replyto' => 'support@example.invalid',
    'custom' => ['format' => 'html', 'enabled' => false],
];
$mergedParams = MailTemplateTagUpdater::mergeParams(
    $originalParams,
    MailTemplateInstaller::tagsFor('com_jlms.enrollment.confirmed')
);
if (
    !in_array('ENROLLMENT_DATE', $mergedParams['tags'], true)
    || !in_array('COURSE_URL', $mergedParams['tags'], true)
    || !in_array('EXTRA_CUSTOM', $mergedParams['tags'], true)
    || in_array('NAME', $mergedParams['tags'], true)
    || count($mergedParams['tags']) !== count(array_unique($mergedParams['tags']))
    || $mergedParams['mailfrom'] !== $originalParams['mailfrom']
    || $mergedParams['replyto'] !== $originalParams['replyto']
    || $mergedParams['custom'] !== $originalParams['custom']
    || $originalParams['tags'][2] !== 'NAME'
) {
    throw new RuntimeException('Upgrade tag synchronization must preserve preferences and add canonical tags.');
}
if (
    MailTemplateTagUpdater::mergeParams(
        $mergedParams,
        MailTemplateInstaller::tagsFor('com_jlms.enrollment.confirmed')
    ) !== $mergedParams
) {
    throw new RuntimeException('Upgrading already synchronized template tags must be idempotent.');
}
if (
    !in_array('COMPLETION_DATE', MailTemplateTagUpdater::mergeParams(
        ['tags' => []],
        MailTemplateInstaller::tagsFor('com_jlms.course.completed')
    )['tags'], true)
) {
    throw new RuntimeException('Completion date must be available on upgraded templates.');
}
try {
    MailTemplateTagUpdater::mergeParams(['tags' => 'malformed'], ['COURSE_TITLE']);
    throw new RuntimeException('Malformed template tag metadata must be rejected.');
} catch (UnexpectedValueException $expected) {
    // Do not discard or replace corrupt configuration silently.
}
$script = file_get_contents(__DIR__ . '/../component/script.php');
if ($script === false || !str_contains($script, 'MailTemplateTagUpdater::synchronize($db)')) {
    throw new RuntimeException('Joomla postflight must synchronize native template tags.');
}


foreach (['com_jlms.course.completed', 'com_jlms.enrollment.reactivated'] as $templateId) {
    $definition = $definitions[$templateId];
    if (
        !in_array('DASHBOARD_URL', $definition[2], true)
        || !str_contains($definition[1], '{DASHBOARD_URL}')
        || !str_contains($definition[3], 'href="{DASHBOARD_URL}"')
    ) {
        throw new RuntimeException('Authenticated dashboard URL must be available in text and HTML: ' . $templateId);
    }
    foreach (['it-IT', 'en-GB'] as $locale) {
        if (MailTemplateVariableCatalog::forTemplate($templateId, $locale)['DASHBOARD_URL']['type'] !== 'url') {
            throw new RuntimeException('Missing localized dashboard URL metadata: ' . $templateId);
        }
    }
}
foreach (
    [
    'LearningService.php' => ["'DASHBOARD_URL' => \\Joomla\\CMS\\Uri\\Uri::root()"],
    'EnrollmentStateService.php' => ["\$target === 'active' ? ['DASHBOARD_URL' => \\Joomla\\CMS\\Uri\\Uri::root()"],
    ] as $filename => $needles
) {
    $source = file_get_contents(__DIR__ . '/../component/administrator/src/Application/' . $filename);
    foreach ($needles as $needle) {
        if ($source === false || !str_contains($source, $needle)) {
            throw new RuntimeException('Missing authorized dashboard URL event payload: ' . $filename);
        }
    }
}

if (MailTemplateInstaller::templateIds() !== array_keys($definitions)) {
    throw new RuntimeException('Mail template list is not sourced from definitions.');
}
$guide = file_get_contents(__DIR__ . '/../component/administrator/tmpl/mailvariables/default.php');
$queue = file_get_contents(__DIR__ . '/../component/administrator/tmpl/mailqueue/default.php');
$dashboard = file_get_contents(__DIR__ . '/../component/administrator/src/View/Dashboard/HtmlView.php');
$guideView = file_get_contents(__DIR__ . '/../component/administrator/src/View/Mailvariables/HtmlView.php');
if (
    $guide === false || !str_contains($guide, 'MailTemplateInstaller::templateIds()')
    || !str_contains($guide, 'MailTemplateVariableCatalog::class')
    || !str_contains($guide, 'forTemplate($mailId, $mailLanguage)')
    || !str_contains($guide, 'jlms-mail-variable-guide')
) {
    throw new RuntimeException('Standalone variable guide must use the registered catalog.');
}
// Issue #404: the guide shows the Joomla-localized name above its technical template ID.
if (
    $guide === false || !str_contains($guide, "'JLMS_MAIL_'")
    || !str_contains($guide, "strtoupper(str_replace(")
    || !str_contains($guide, '$displayTitle')
    || !str_contains($guide, '$this->escape($mailId)')
) {
    throw new RuntimeException('Mail variable guide must expose translated model titles and canonical IDs.');
}
foreach (['it-IT', 'en-GB'] as $locale) {
    $language = file_get_contents(__DIR__ . '/../component/administrator/language/' . $locale . '/com_jlms.ini');
    foreach (MailTemplateInstaller::templateIds() as $templateId) {
        $key = 'COM_JLMS_MAIL_' . strtoupper(str_replace(['com_jlms.', '.'], ['', '_'], $templateId)) . '_TITLE';
        if ($language === false || !str_contains($language, $key . '=')) {
            throw new RuntimeException('Missing localized mail model title: ' . $locale . '/' . $key);
        }
    }
}

if (
    $queue === false || str_contains($queue, 'jlms-mail-variable-guide')
    || !str_contains($queue, 'MailTemplateInstaller::templateIds()')
) {
    throw new RuntimeException('Mail queue must remain operational and keep the shared filter list.');
}
$displayController = file_get_contents(__DIR__ . '/../component/administrator/src/Controller/DisplayController.php');
if ($displayController === false || !str_contains($displayController, "'mailqueue', 'mailvariables'")) {
    throw new RuntimeException('Mail variable guide is not permitted by the administrator display controller.');
}
$queueController = file_get_contents(__DIR__ . '/../component/administrator/src/Controller/MailqueueController.php');
if ($queueController === false || !str_contains($queueController, "['reconcile', 'display']")) {
    throw new RuntimeException('Operational outbox read-only display must stay permitted.');
}
if (
    $dashboard === false || !str_contains($dashboard, "view=mailvariables")
    || $guideView === false || !str_contains($guideView, 'CourseEditorAccess::fresh($app->getIdentity())')
    || !str_contains($guideView, "authorise('core.manage', 'com_mails')")
    || !str_contains($guideView, "authorise('core.edit', 'com_mails')")
) {
    throw new RuntimeException('Guide navigation and native editor ACL must be enforced by the toolbar view.');
}
foreach (['it-IT', 'en-GB'] as $locale) {
    $language = file_get_contents(__DIR__ . '/../component/administrator/language/' . $locale . '/com_jlms.ini');
    foreach (
        ['COM_JLMS_MAIL_VARIABLE_GUIDE_TITLE', 'COM_JLMS_MAIL_VARIABLE_GUIDE_INTRO',
        'COM_JLMS_MAIL_VARIABLE_GUIDE_WARNING', 'COM_JLMS_MAIL_VARIABLE_GUIDE_TAG',
        'COM_JLMS_MAIL_VARIABLE_GUIDE_TYPE', 'COM_JLMS_MAIL_VARIABLE_GUIDE_DESCRIPTION'] as $key
    ) {
        if ($language === false || !str_contains($language, $key . '=')) {
            throw new RuntimeException('Missing translated guide key: ' . $locale . '/' . $key);
        }
    }
}

// The native Mail Templates shortcut belongs in the Joomla toolbar and retains its ACL boundary.
if (
    $guideView === false
    || !str_contains($guideView, "ToolbarHelper::link(")
    || !str_contains($guideView, "authorise('core.edit', 'com_mails')")
    || !str_contains($guideView, "filter[extension]=com_jlms")
    || str_contains($guide, 'COM_JLMS_MAIL_TEMPLATES')
) {
    throw new RuntimeException('Mail template shortcut must be toolbar-only and ACL protected.');
}

if (MailTemplateVariableCatalog::forTemplate('unknown.template') !== []) {
    throw new RuntimeException('Unknown template has unexpected tags.');
}

$data = ['USER_FULLNAME' => 'Ada', 'COURSE_TITLE' => 'Intro', 'SITE_NAME' => 'Academy'];
$validator = MailTemplatePlaceholderValidator::class;
if ($validator::unresolved('com_jlms.course.completed', $data, 'Ciao {USER_FULLNAME} - {COURSE_TITLE} - {SITE_NAME}') !== []) {
    throw new RuntimeException('Registered, provided tags should resolve.');
}
if ($validator::unresolved('com_jlms.course.completed', $data, '{UNKNOWN} {COURSE_URL} {USER_FULLNAME} {UNKNOWN}') !== ['COURSE_URL', 'UNKNOWN']) {
    throw new RuntimeException('Unsupported and unprovided tags must be detected.');
}
if ($validator::unresolved('com_jlms.course.completed', ['USER_FULLNAME' => ['invalid']], '{USER_FULLNAME}') !== ['USER_FULLNAME']) {
    throw new RuntimeException('Non scalar tag values must not pass validation.');
}
if ($validator::unresolved('com_jlms.course.completed', $data, 'A literal {lowercase} and JSON {"x":1}') !== []) {
    throw new RuntimeException('Non-JD-Learn placeholders should not be blocked.');
}

echo "Mail template canonical variable checks passed\\n";

// The video-only course completion path must populate all placeholders in the
// installed com_jlms.course.completed Joomla template, just like text lessons.
$videoCompletionSource = file_get_contents(__DIR__ . '/../component/administrator/src/Application/VideoProgressService.php');
if ($videoCompletionSource === false) {
    throw new RuntimeException('Missing video course completion source.');
}
$completionQueue = substr($videoCompletionSource, strpos($videoCompletionSource, "hash('sha256', 'course.completed:'"));
$completionQueue = substr($completionQueue, 0, strpos($completionQueue, ');'));
foreach (['USER_FULLNAME','COURSE_TITLE','COMPLETION_DATE','DASHBOARD_URL','SITE_NAME'] as $requiredTag) {
    if (!str_contains($completionQueue, "'" . $requiredTag . "' =>")) {
        throw new RuntimeException('Video completion notification omits ' . $requiredTag);
    }
}

// MAIL-10: administrators can identify invalid token NAMES without exposing
// recipient addresses, template values or transport exceptions in the outbox.
$auditSource = file_get_contents(__DIR__ . '/../component/administrator/src/Application/MailQueueAuditService.php');
$auditView = file_get_contents(__DIR__ . '/../component/administrator/tmpl/mailqueue/default.php');
if (
    !str_contains($auditSource, 'MailTemplatePlaceholderValidator::unresolved(')
    || !str_contains($auditSource, 'unset($row->template_data, $row->language);')
    || !str_contains($auditView, 'diagnostic_tokens')
    || !str_contains(preg_replace('/\\s+/', '', $auditView), '$this->escape(implode(')
) {
    throw new RuntimeException('Safe mail queue placeholder diagnostics not integrated.');
}

// A failed template validation is pre-SMTP and may be manually retried only
// after repair; uncertain transport failures must never be auto-requeued.
$mailAuditSource = file_get_contents(__DIR__ . '/../component/administrator/src/Application/MailQueueAuditService.php');
if (
    $mailAuditSource === false || !str_contains($mailAuditSource, 'private function retryFailedTemplate(')
    || !str_contains($mailAuditSource, "state = 'failed'")
    || !str_contains($mailAuditSource, 'MailTemplatePlaceholderValidator::unresolved(')
    || !str_contains($mailAuditSource, "template_id IN ('com_jlms.enrollment.confirmed', 'com_jlms.course.completed')")
    || !str_contains($mailAuditSource, 'getAffectedRows() !== 1')
) {
    throw new RuntimeException('Failed pre-SMTP template retry guard is missing.');
}
