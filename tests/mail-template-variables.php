<?php

declare(strict_types=1);

require_once __DIR__ . '/../component/administrator/src/Infrastructure/MailTemplateInstaller.php';
require_once __DIR__ . '/../component/administrator/src/Infrastructure/MailTemplateVariableCatalog.php';
require_once __DIR__ . '/../component/administrator/src/Infrastructure/MailTemplatePlaceholderValidator.php';

use Jlms\Component\Jlms\Administrator\Infrastructure\MailTemplateInstaller;
use Jlms\Component\Jlms\Administrator\Infrastructure\MailTemplateVariableCatalog;
use Jlms\Component\Jlms\Administrator\Infrastructure\MailTemplatePlaceholderValidator;

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
if (MailTemplateInstaller::templateIds() !== array_keys($definitions)) {
    throw new RuntimeException('Mail template list is not sourced from definitions.');
}
$guide = file_get_contents(__DIR__ . '/../component/administrator/tmpl/mailvariables/default.php');
$queue = file_get_contents(__DIR__ . '/../component/administrator/tmpl/mailqueue/default.php');
$dashboard = file_get_contents(__DIR__ . '/../component/administrator/src/View/Dashboard/HtmlView.php');
$guideView = file_get_contents(__DIR__ . '/../component/administrator/src/View/Mailvariables/HtmlView.php');
if ($guide === false || !str_contains($guide, 'MailTemplateInstaller::templateIds()')
    || !str_contains($guide, 'MailTemplateVariableCatalog::class')
    || !str_contains($guide, 'forTemplate($mailId, $mailLanguage)')
    || !str_contains($guide, 'jlms-mail-variable-guide')) {
    throw new RuntimeException('Standalone variable guide must use the registered catalog.');
}
if ($queue === false || str_contains($queue, 'jlms-mail-variable-guide')
    || !str_contains($queue, 'MailTemplateInstaller::templateIds()')) {
    throw new RuntimeException('Mail queue must remain operational and keep the shared filter list.');
}
$displayController = file_get_contents(__DIR__ . '/../component/administrator/src/Controller/DisplayController.php');
if ($displayController === false || !str_contains($displayController, "'mailvariables'")) {
    throw new RuntimeException('Mail variable guide is not permitted by the administrator display controller.');
}
if ($dashboard === false || !str_contains($dashboard, "view=mailvariables")
    || $guideView === false || !str_contains($guideView, "authorise('core.manage', 'com_jlms')")
    || !str_contains($guide, "authorise('core.edit', 'com_mails')")) {
    throw new RuntimeException('Guide navigation and native editor ACL must be enforced.');
}
foreach (['it-IT', 'en-GB'] as $locale) {
    $language = file_get_contents(__DIR__ . '/../component/administrator/language/' . $locale . '/com_jlms.ini');
    foreach (['COM_JLMS_MAIL_VARIABLE_GUIDE_TITLE', 'COM_JLMS_MAIL_VARIABLE_GUIDE_INTRO',
        'COM_JLMS_MAIL_VARIABLE_GUIDE_WARNING', 'COM_JLMS_MAIL_VARIABLE_GUIDE_TAG',
        'COM_JLMS_MAIL_VARIABLE_GUIDE_TYPE', 'COM_JLMS_MAIL_VARIABLE_GUIDE_DESCRIPTION'] as $key) {
        if ($language === false || !str_contains($language, $key . '=')) {
            throw new RuntimeException('Missing translated guide key: ' . $locale . '/' . $key);
        }
    }
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
