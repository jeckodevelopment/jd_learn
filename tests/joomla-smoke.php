<?php

declare(strict_types=1);


/** Run after Joomla's CLI installer and extension:install in an isolated CI site. */
$root = realpath($argv[1] ?? '');
if (!$root || getenv('JLMS_TEST_DISPOSABLE') !== '1') {
    throw new \RuntimeException('Disposable Joomla site required.');
}
// Set fixture plugin state before Joomla caches its enabled-plugin list.
// The retained-data mode must not alter state, so it can detect upgrade regressions.
if (($argv[2] ?? '') !== 'retained') {
    $fixtureDb = new \PDO(getenv('JLMS_TEST_DSN'), getenv('JLMS_TEST_USER'), getenv('JLMS_TEST_PASSWORD'), [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    $fixtureDb->exec("UPDATE joomla_extensions SET enabled=1 WHERE type='plugin' AND (folder='jlmsvideo' OR (folder='privacy' AND element='jdlearn') OR (folder='user' AND element='jdlearnconsent'))");
    unset($fixtureDb);
}

// Simulate the web request context required by AdministratorApplication in a CLI smoke test.
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/administrator/index.php?option=com_jlms';
$_SERVER['SCRIPT_NAME'] = '/administrator/index.php';
$_SERVER['PHP_SELF'] = '/administrator/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root . '/administrator/index.php';
\define('_JEXEC', 1);
\define('JPATH_BASE', $root . '/administrator');
require $root . '/administrator/includes/defines.php';
require $root . '/administrator/includes/framework.php';
\define('JPATH_COMPONENT', JPATH_ADMINISTRATOR . '/components/com_jlms');
\define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_COMPONENT);
\define('JPATH_COMPONENT_SITE', JPATH_SITE . '/components/com_jlms');

use Joomla\CMS\Factory;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Session\Session;
use Joomla\Session\SessionInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Event\Privacy\CollectCapabilitiesEvent;
use Joomla\CMS\Event\Privacy\CanRemoveDataEvent;
use Joomla\CMS\Event\Privacy\ExportRequestEvent;
use Joomla\CMS\Event\Privacy\RemoveDataEvent;
use Joomla\Component\Privacy\Administrator\Table\RequestTable;
use Joomla\CMS\Language\Text;
use Jlms\Contracts\Event\RegisterVideoProvidersEvent;
use Jlms\Contracts\Video\VideoProviderRegistry;

$container = Factory::getContainer();
$container->alias('session.web', 'session.web.administrator')
    ->alias('session', 'session.web.administrator')
    ->alias(Session::class, 'session.web.administrator')
    ->alias(SessionInterface::class, 'session.web.administrator');
$app = $container->get(AdministratorApplication::class);
Factory::$application = $app;
$app->loadLanguage($container->get(\Joomla\CMS\Language\LanguageFactoryInterface::class)->createLanguage('en-GB', false));
$app->createExtensionNamespaceMap();
$db = $container->get(DatabaseInterface::class);

// SEC-P1: a session with a stale email must not receive a new consent token.
// The registration origin still accepts Joomla users blocked pending activation.
$consentClass = \Jlms\Component\Jlms\Administrator\Application\CourseAnnouncementConsent::class;
$account = $db->setQuery($db->createQuery()->select(['id', 'name', 'email', 'block'])
    ->from('#__users')->where('email <> ' . $db->quote(''))->order('id'), 0, 1)->loadObject();
if (!$account || !filter_var((string) $account->email, FILTER_VALIDATE_EMAIL)) {
    throw new \RuntimeException('Consent test requires a valid Joomla account in the disposable fixture.');
}
$recipient = $consentClass::verifiedRecipient($db, (int) $account->id, (string) $account->email, 'registration');
if ($recipient['email'] !== (string) $account->email || $recipient['name'] !== (string) $account->name) {
    throw new \RuntimeException('Consent must use canonical name and email from Joomla.');
}
foreach ([(int) $account->id + 1000000, 0] as $unknownId) {
    try {
        $consentClass::verifiedRecipient($db, $unknownId, (string) $account->email, 'registration');
        throw new \RuntimeException('Nonexistent Joomla account accepted for consent.');
    } catch (\InvalidArgumentException) {}
}
try {
    $consentClass::verifiedRecipient($db, (int) $account->id, 'stale-recipient@example.invalid', 'registration');
    throw new \RuntimeException('Stale session email accepted for consent.');
} catch (\InvalidArgumentException) {}
try {
    $consentClass::verifiedRecipient($db, (int) $account->id, (string) $account->email, 'unsupported');
    throw new \RuntimeException('Unsupported consent origin accepted.');
} catch (\InvalidArgumentException) {}
$oldBlock = (int) $account->block;
try {
    $db->setQuery($db->createQuery()->update('#__users')->set('block = 1')
        ->where('id = ' . (int) $account->id))->execute();
    $pending = $consentClass::verifiedRecipient($db, (int) $account->id, (string) $account->email, 'registration');
    if ($pending['email'] !== (string) $account->email) {
        throw new \RuntimeException('Joomla account activation flow broken.');
    }
    foreach (['dashboard', 'enrollment'] as $origin) {
        try {
            $consentClass::verifiedRecipient($db, (int) $account->id, (string) $account->email, $origin);
            throw new \RuntimeException('Blocked account accepted for consent origin: ' . $origin);
        } catch (\InvalidArgumentException) {}
    }
} finally {
    $db->setQuery($db->createQuery()->update('#__users')->set('block = ' . $oldBlock)
        ->where('id = ' . (int) $account->id))->execute();
}

// SEC-P1: outbox recipients are checked against the CURRENT Joomla account
// before dispatch. No SMTP is invoked by these negative service tests.
$mailQueueClass = \Jlms\Component\Jlms\Administrator\Infrastructure\MailQueue::class;
$queued = (object) ['recipient_user_id' => (int) $account->id,
    'recipient_email' => (string) $account->email];
$originalBlock = (int) $account->block;
try {
    $db->setQuery($db->createQuery()->update('#__users')->set('block = 0')
        ->where('id = ' . (int) $account->id))->execute();
    if (!$mailQueueClass::currentRecipient($db, $queued)) {
        throw new \RuntimeException('Valid current Joomla outbox recipient must be deliverable.');
    }
    foreach ([
        (object) ['recipient_user_id' => 0, 'recipient_email' => (string) $account->email],
        (object) ['recipient_user_id' => (int) $account->id + 1000000,
            'recipient_email' => (string) $account->email],
        (object) ['recipient_user_id' => (int) $account->id,
            'recipient_email' => 'stale-outbox@example.invalid'],
        (object) ['recipient_user_id' => (int) $account->id,
            'recipient_email' => 'not-an-email'],
    ] as $badRecipient) {
        if ($mailQueueClass::currentRecipient($db, $badRecipient)) {
            throw new \RuntimeException('Stale, invalid or unbound outbox recipient passed.');
        }
    }
    $db->setQuery($db->createQuery()->update('#__users')->set('block = 1')
        ->where('id = ' . (int) $account->id))->execute();
    if ($mailQueueClass::currentRecipient($db, $queued)) {
        throw new \RuntimeException('Blocked Joomla account must not receive queued email.');
    }
} finally {
    $db->setQuery($db->createQuery()->update('#__users')->set('block = ' . $originalBlock)
        ->where('id = ' . (int) $account->id))->execute();
}

$mailEscaping = \Jlms\Component\Jlms\Administrator\Infrastructure\MailTemplateVariableCatalog::class;
$renderTemplate = new \Joomla\CMS\Mail\MailTemplate('com_jlms.enrollment.manager_request', 'en-GB');
$renderTemplate->addUnsafeTags($mailEscaping::tagsRequiringHtmlEscape('com_jlms.enrollment.manager_request'));
$replaceTokens = new \ReflectionMethod(\Joomla\CMS\Mail\MailTemplate::class, 'replaceTags');
$unsafeTitle = '<img src=x onerror=alert(1)>';
$htmlTitle = htmlspecialchars($unsafeTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$rendered = $replaceTokens->invoke($renderTemplate,
    '<p>{COURSE_TITLE}</p><p>{COURSE_TITLE_HTML}</p>',
    ['COURSE_TITLE' => $unsafeTitle, 'COURSE_TITLE_HTML' => $htmlTitle], true);
if ($rendered !== '<p>' . $htmlTitle . '</p><p>' . $htmlTitle . '</p>') {
    throw new \RuntimeException('Mail template must escape raw text and avoid double-escaping prepared HTML.');
}
$renderFragment = new \Joomla\CMS\Mail\MailTemplate('com_jlms.course.message', 'en-GB');
$renderFragment->addUnsafeTags($mailEscaping::tagsRequiringHtmlEscape('com_jlms.course.message'));
$rendered = $replaceTokens->invoke($renderFragment, '<p>{MESSAGE_BODY_HTML}</p><p>{MESSAGE_BODY_TEXT}</p>',
    ['MESSAGE_BODY_HTML' => '<strong>Allowed</strong>', 'MESSAGE_BODY_TEXT' => '<img src=x onerror=alert(1)>'], true);
if ($rendered !== '<p><strong>Allowed</strong></p><p>' . $htmlTitle . '</p>') {
    throw new \RuntimeException('Joomla must preserve sanitized mail HTML and escape raw body text.');
}

// Native extension installer must create the submenu once and preserve it on upgrade.
$menuParent = (int) $db->setQuery("SELECT id FROM #__menu WHERE client_id=1 AND menutype='main' AND link='index.php?option=com_jlms&view=dashboard' AND parent_id=1")->loadResult();
$menuLinks = $db->setQuery($db->getQuery(true)->select($db->quoteName('link'))->from($db->quoteName('#__menu'))->where($db->quoteName('client_id') . ' = 1')->where($db->quoteName('parent_id') . ' = ' . $menuParent)->order($db->quoteName('lft') . ' ASC'))->loadColumn();
$expectedLinks = ['index.php?option=com_jlms&view=dashboard', 'index.php?option=com_jlms&view=courses', 'index.php?option=com_jlms&view=pathways', 'index.php?option=com_jlms&view=teachers', 'index.php?option=com_jlms&view=questionbank'];
$menuTitles = $db->setQuery($db->getQuery(true)->select($db->quoteName('title'))->from($db->quoteName('#__menu'))->where($db->quoteName('client_id') . ' = 1')->where($db->quoteName('parent_id') . ' = ' . $menuParent)->order($db->quoteName('lft') . ' ASC'))->loadColumn();
$expectedMenuTitles = ['COM_JLMS_DASHBOARD_MENU', 'COM_JLMS_COURSES', 'COM_JLMS_PATHWAYS', 'COM_JLMS_TEACHERS', 'COM_JLMS_QUESTION_BANK'];
if (!$menuParent || $menuLinks !== $expectedLinks || $menuTitles !== $expectedMenuTitles) { throw new \RuntimeException('Native administrator menu must include Dashboard first with the localized label and without duplicates.'); }
$reassignSystemLanguage = parse_ini_file(JPATH_ADMINISTRATOR . '/language/en-GB/com_jlms.sys.ini', false, INI_SCANNER_RAW);
if (!is_array($reassignSystemLanguage) || ($reassignSystemLanguage['COM_JLMS_CATEGORY_REASSIGN_TITLE'] ?? null) !== 'Category dependency cleanup') { throw new \RuntimeException('Category reassignment title must be installed in the administrator system language catalog.'); }
// Reproduce the old component menu entry left behind by upgrades from releases that opened Courses.
$db->setQuery($db->getQuery(true)->update($db->quoteName('#__menu'))->set($db->quoteName('link') . ' = ' . $db->quote('index.php?option=com_jlms&view=courses'))->where($db->quoteName('id') . ' = ' . $menuParent))->execute();
require_once JPATH_ADMINISTRATOR . '/components/com_jlms/script.php';
(new \Com_JlmsInstallerScript())->postflight('update', null);
$defaultCertificateTemplate = $db->createQuery()->select('COUNT(*)')->from('#__jlms_certificate_templates')
    ->where('title = :title')->where('state = 1');
$defaultCertificateTitle = 'JD Learn — Attestato standard';
$defaultCertificateTemplate->bind(':title', $defaultCertificateTitle);
if ((int) $db->setQuery($defaultCertificateTemplate)->loadResult() !== 1) {
    throw new \RuntimeException('JD Learn must install exactly one safe default certificate template.');
}
$mailTemplateIds = [
    'com_jlms.enrollment.requested',
    'com_jlms.enrollment.approved',
    'com_jlms.enrollment.rejected',
    'com_jlms.enrollment.confirmed',
    'com_jlms.enrollment.suspended',
    'com_jlms.enrollment.reactivated',
    'com_jlms.course.completed',
    'com_jlms.certificate.available',
];
foreach ($mailTemplateIds as $templateId) {
    $mailTemplate = \Joomla\CMS\Mail\MailTemplate::getTemplate($templateId, 'it-IT');
    if ($mailTemplate === null || !($mailTemplate->params instanceof \Joomla\Registry\Registry)) {
        throw new \RuntimeException('JD Learn mail template is not registered in Joomla: ' . $templateId);
    }
    if (trim((string) $mailTemplate->subject) === '' || trim((string) $mailTemplate->body) === '' || trim((string) $mailTemplate->htmlbody) === '') {
        throw new \RuntimeException('JD Learn mail template must include subject, plain-text and HTML content: ' . $templateId);
    }
    $tags = (array) $mailTemplate->params->get('tags', []);
    $requiredTags = match ($templateId) {
        'com_jlms.enrollment.confirmed' => ['USER_FULLNAME', 'COURSE_TITLE', 'SITE_NAME', 'COURSE_URL'],
        'com_jlms.certificate.available' => ['USER_FULLNAME', 'COURSE_TITLE', 'SITE_NAME', 'CERTIFICATE_URL'],
        default => ['USER_FULLNAME', 'COURSE_TITLE', 'SITE_NAME'],
    };
    foreach ($requiredTags as $requiredTag) {
        if (!in_array($requiredTag, $tags, true)) {
            throw new \RuntimeException('JD Learn mail template does not document its variable ' . $requiredTag . ': ' . $templateId);
        }
    }
}
// Course-message composition must load the actual Joomla template and validate a
// per-send snapshot without changing the global template.
$templateSource = \Jlms\Component\Jlms\Administrator\Application\CourseMessageTemplate::load('*');
if (!str_contains($templateSource['subject'], '{MESSAGE_SUBJECT}')
    || !str_contains($templateSource['body'], '{MESSAGE_BODY_TEXT}')
    || !str_contains($templateSource['htmlbody'], '{MESSAGE_BODY_HTML}')) {
    throw new \RuntimeException('Course communication must preload the Joomla Mail Template.');
}
$sampleVars = [
    'MESSAGE_SUBJECT' => 'Avviso', 'MESSAGE_BODY_TEXT' => 'Test', 'MESSAGE_BODY_HTML' => 'Test',
    'USER_FULLNAME' => 'Test', 'USER_FULLNAME_HTML' => 'Test',
    'COURSE_TITLE' => 'Corso', 'COURSE_TITLE_HTML' => 'Corso',
    'SITE_NAME' => 'Sito', 'SITE_NAME_HTML' => 'Sito',
];
$composerSource = \Jlms\Component\Jlms\Administrator\Application\CourseMessageTemplate::forComposition('*');
if (str_contains(implode(' ', $composerSource), '{MESSAGE_SUBJECT}')
    || str_contains(implode(' ', $composerSource), '{MESSAGE_BODY_TEXT}')
    || str_contains(implode(' ', $composerSource), '{MESSAGE_BODY_HTML}')) {
    throw new \RuntimeException('Legacy free-message fields were not resolved for the single composer.');
}
$checked = \Jlms\Component\Jlms\Administrator\Application\CourseMessageTemplate::validate($composerSource, $sampleVars);
if ($checked['subject'] !== $composerSource['subject']) {
    throw new \RuntimeException('Per-message template snapshot was unexpectedly changed.');
}
try {
    \Jlms\Component\Jlms\Administrator\Application\CourseMessageTemplate::validate(
        ['subject' => '{UNSUPPORTED_TAG}', 'body' => 'Text', 'htmlbody' => '<p>Text</p>'], $sampleVars
    );
    throw new \RuntimeException('Unsupported mail variables must be rejected.');
} catch (\RuntimeException $expected) {
    if ($expected->getMessage() !== 'COM_JLMS_COMMUNICATION_TEMPLATE_VARIABLES') {
        throw $expected;
    }
}
// Preview and delivery share Joomla's token replacement, including HTML escaping.
$composerRenderer = new \Jlms\Component\Jlms\Administrator\Application\CourseMessageMailer();
$renderedSubject = $composerRenderer->render('{COURSE_TITLE}: {MESSAGE_SUBJECT}', $sampleVars);
if ($renderedSubject !== 'Corso: Avviso') {
    throw new \RuntimeException('Course email subject preview differs from Joomla template replacement.');
}
$untrusted = \Jlms\Component\Jlms\Administrator\Application\CourseMessageTemplate::validate(
    ['subject' => '{COURSE_TITLE}', 'body' => '{COURSE_TITLE}', 'htmlbody' => '<p>{USER_FULLNAME_HTML}</p><script>alert(1)</script>'],
    $sampleVars
);
if (str_contains($untrusted['htmlbody'], '<script')) {
    throw new \RuntimeException('Unsafe HTML in message snapshot was retained.');
}
if (!str_contains($untrusted['htmlbody'], '<p>')) {
    throw new \RuntimeException('Safe HTML formatting was removed from the message.');
}
// Issue: a single HTML composer must accept documented recipient/course tokens
// without requiring a second free-message field or self-referential slots.
$singleComposer = \Jlms\Component\Jlms\Administrator\Application\CourseMessageTemplate::forComposition('*');
$singleComposer['subject'] = 'Aggiornamento {COURSE_TITLE}';
$singleComposer['htmlbody'] = '<p>Ciao {USER_FULLNAME_HTML}, novità sul corso {COURSE_TITLE_HTML}.</p><p>{SITE_NAME_HTML}</p>';
$singleComposer['body'] = \Jlms\Component\Jlms\Administrator\Application\CourseMessageTemplate::plainTextFromHtml($singleComposer['htmlbody']);
$checkedSingle = \Jlms\Component\Jlms\Administrator\Application\CourseMessageTemplate::validate($singleComposer, $sampleVars);
if ($checkedSingle['subject'] !== 'Aggiornamento {COURSE_TITLE}'
    || !str_contains($checkedSingle['body'], '{USER_FULLNAME}')
    || $composerRenderer->render($checkedSingle['subject'], $sampleVars) !== 'Aggiornamento Corso') {
    throw new \RuntimeException('Single editor rejects standard recipient/course variables.');
}
// Upgrades synchronize the Joomla tag list but preserve the old content.
$legacyMessage = (object) [
    'template_id' => 'com_jlms.course.message',
    'language' => '',
    'subject' => '[Aggiornamento] {COURSE}: {MESSAGE_SUBJECT}',
    'body' => "{NAME} — {MESSAGE_BODY_TEXT} — {SITENAME}",
    'htmlbody' => '<p>Ciao {NAME_HTML}, {COURSE_HTML} — {MESSAGE_BODY_HTML}</p><p>{SITENAME_HTML}</p>',
];
$messageTemplate = \Jlms\Component\Jlms\Administrator\Application\CourseMessageTemplate::class;
try {
    $db->updateObject('#__mail_templates', $legacyMessage, ['template_id', 'language']);
    $legacyComposition = $messageTemplate::forComposition('it-IT');
    $legacyComposition['body'] = $messageTemplate::plainTextFromHtml($legacyComposition['htmlbody']);
    $snapshot = $messageTemplate::validate($legacyComposition, $sampleVars);
    $renderedSubject = $composerRenderer->render($snapshot['subject'], $sampleVars);
    $escapedSample = [
        'USER_FULLNAME' => 'Test & Figli',
        'USER_FULLNAME_HTML' => 'Test &amp; Figli',
    ] + $sampleVars;
    $renderedBody = $composerRenderer->render($snapshot['body'], $escapedSample);
    $renderedHtml = $composerRenderer->render($snapshot['htmlbody'], $escapedSample, true);
    if (!str_starts_with($renderedSubject, '[Aggiornamento] Corso: ')
        || !str_starts_with($renderedBody, 'Ciao Test & Figli, Corso')
        || !str_contains($renderedHtml, 'Ciao Test &amp; Figli, Corso')
        || !str_ends_with($renderedHtml, '<p>Sito</p>')
        || preg_match('/\{[A-Z][A-Z0-9_]*\}/', $renderedSubject . $renderedBody . $renderedHtml)
        || $messageTemplate::load('*')['htmlbody'] !== $legacyMessage->htmlbody) {
        throw new \RuntimeException('The upgraded Joomla model must render canonically without changing its saved content.');
    }
    $expectedTags = array_diff(array_keys($sampleVars), ['MESSAGE_SUBJECT', 'MESSAGE_BODY_TEXT', 'MESSAGE_BODY_HTML']);
    if (array_diff($expectedTags, $messageTemplate::tags()) || array_diff($messageTemplate::tags(), $expectedTags)) {
        throw new \RuntimeException('Composer placeholder help must match the recipient data.');
    }
    $legacyComposition['subject'] .= ' {COURSE_URL} {UNKNOWN_TAG}';
    try {
        $messageTemplate::validate($legacyComposition, $sampleVars + ['COURSE_URL' => 'https://example.invalid']);
        throw new \RuntimeException('Placeholders outside the communication catalog must fail closed.');
    } catch (\Jlms\Component\Jlms\Administrator\Application\CourseMessageVariableException $error) {
        if ($error->getCode() !== 422 || $error->names !== ['COURSE_URL', 'UNKNOWN_TAG']) {
            throw $error;
        }
    }
} finally {
    $defaultMessage = (object) (['template_id' => 'com_jlms.course.message', 'language' => ''] + $templateSource);
    $db->updateObject('#__mail_templates', $defaultMessage, ['template_id', 'language']);
}
$dangerousPlain = \Jlms\Component\Jlms\Administrator\Application\CourseMessageTemplate::plainTextFromHtml(
    '<p>Messaggio &amp; comunicazione</p><script>alert("bad")</script>'
);
if (str_contains($dangerousPlain, 'alert') || !str_contains($dangerousPlain, 'Messaggio & comunicazione')) {
    throw new \RuntimeException('HTML-derived plain text must be safe and readable.');
}
try {
    \Jlms\Component\Jlms\Administrator\Application\CourseMessageTemplate::validate(
        ['subject' => '{MESSAGE_SUBJECT}', 'body' => 'Testo', 'htmlbody' => '<p>Testo</p>'], $sampleVars
    );
    throw new \RuntimeException('Recursive legacy composer slot should not be accepted.');
} catch (\RuntimeException $legacy) {
    if ($legacy->getMessage() !== 'COM_JLMS_COMMUNICATION_TEMPLATE_LEGACY_SLOT') {
        throw $legacy;
    }
}
// Native editor changes must survive subsequent component upgrades.
$customSubject = 'Subject edited in Joomla Mail Templates';
$db->setQuery($db->getQuery(true)->update($db->quoteName('#__mail_templates'))
    ->set($db->quoteName('subject') . ' = ' . $db->quote($customSubject))
    ->where($db->quoteName('template_id') . ' = ' . $db->quote('com_jlms.enrollment.confirmed'))
    ->where($db->quoteName('language') . ' = ' . $db->quote('')))->execute();
require_once JPATH_ADMINISTRATOR . '/components/com_jlms/src/Infrastructure/MailTemplateInstaller.php';
\Jlms\Component\Jlms\Administrator\Infrastructure\MailTemplateInstaller::ensureRegistered();
$preservedTemplate = \Joomla\CMS\Mail\MailTemplate::getTemplate('com_jlms.enrollment.confirmed', 'it-IT');
if ($preservedTemplate->subject !== $customSubject) {
    throw new \RuntimeException('JD Learn upgrade overwrote an administrator-edited Joomla mail template.');
}
// Reproduce a real upgrade with existing localized mail templates requiring
// canonical tag synchronization. This was not exercised by fresh-install CI.
require_once JPATH_ADMINISTRATOR . '/components/com_jlms/src/Infrastructure/MailTemplateTagUpdater.php';
$mailId = 'com_jlms.enrollment.confirmed';
$mailLang = '';
$mailRowQuery = $db->getQuery(true)->select(['template_id', 'language', 'params', 'subject'])
    ->from('#__mail_templates')
    ->where('template_id = ' . $db->quote($mailId))
    ->where('language = ' . $db->quote($mailLang));
$mailBefore = $db->setQuery($mailRowQuery)->loadObject();
if (!$mailBefore) {
    throw new \RuntimeException('Upgrade regression fixture: mail template missing.');
}
$mailParams = json_decode((string) $mailBefore->params, true, 64, JSON_THROW_ON_ERROR);
if (!is_array($mailParams) || !is_array($mailParams['tags'] ?? null)) {
    throw new \RuntimeException('Upgrade regression fixture: invalid initial tags.');
}
$mailParams['tags'] = array_values(array_diff($mailParams['tags'], ['COURSE_TITLE']));
$mailParams['tags'][] = 'NAME';
$db->setQuery($db->getQuery(true)->update('#__mail_templates')
    ->set('params = ' . $db->quote(json_encode($mailParams, JSON_THROW_ON_ERROR)))
    ->where('template_id = ' . $db->quote($mailId))
    ->where('language = ' . $db->quote($mailLang)))->execute();
$changedTags = \Jlms\Component\Jlms\Administrator\Infrastructure\MailTemplateTagUpdater::synchronize($db);
if ($changedTags < 1) {
    throw new \RuntimeException('Upgrade regression: old mail tags were not synchronized.');
}
$mailAfter = $db->setQuery($mailRowQuery)->loadObject();
$finalTags = json_decode((string) $mailAfter->params, true, 64, JSON_THROW_ON_ERROR)['tags'];
if (!in_array('COURSE_TITLE', $finalTags, true) || in_array('NAME', $finalTags, true)
    || (string) $mailAfter->subject !== $customSubject
    || \Jlms\Component\Jlms\Administrator\Infrastructure\MailTemplateTagUpdater::synchronize($db) !== 0) {
    throw new \RuntimeException('Upgrade regression: tags, editor content or idempotency lost.');
}
$menuRootLink = (string) $db->setQuery($db->getQuery(true)->select($db->quoteName('link'))->from($db->quoteName('#__menu'))->where($db->quoteName('id') . ' = ' . $menuParent))->loadResult();
if ($menuRootLink !== 'index.php?option=com_jlms&view=dashboard') { throw new \RuntimeException('JD Learn sidebar root must open the dashboard after a component upgrade.'); }
$menuLinksAfterUpdate = $db->setQuery($db->getQuery(true)->select($db->quoteName('link'))->from($db->quoteName('#__menu'))->where($db->quoteName('client_id') . ' = 1')->where($db->quoteName('parent_id') . ' = ' . $menuParent)->order($db->quoteName('lft') . ' ASC'))->loadColumn();
if ($menuLinksAfterUpdate !== $expectedLinks) { throw new \RuntimeException('Dashboard submenu order or links changed during component upgrade.'); }
$fieldContexts = array_keys(Factory::getApplication()->bootComponent('com_jlms')->getContexts());
if ($fieldContexts !== ['com_jlms.teacher', 'com_jlms.course', 'com_jlms.lesson']) {
    throw new \RuntimeException('JD Learn custom-field contexts are unavailable to the native com_fields filter.');
}


$userId = (int) $db->setQuery("SELECT id FROM #__users WHERE username = 'cioperator'")->loadResult();
if (!$userId) {
    throw new \RuntimeException('Test Joomla operator missing.');
}
$app->loadIdentity($container->get(UserFactoryInterface::class)->loadUserById($userId));

// The package must install and activate JD Learn's native Joomla Privacy event subscriber.
PluginHelper::importPlugin('privacy');
$privacyCapabilities = new CollectCapabilitiesEvent('onPrivacyCollectAdminCapabilities');
$app->getDispatcher()->dispatch('onPrivacyCollectAdminCapabilities', $privacyCapabilities);
$privacyData = $privacyCapabilities->getArgument('result', []);
$privacyTitle = Text::_('PLG_PRIVACY_JDLEARN_CAPABILITY_TITLE');
$privacyDescriptionKeys = [
    'PLG_PRIVACY_JDLEARN_CAPABILITY_ENROLLMENTS',
    'PLG_PRIVACY_JDLEARN_CAPABILITY_PROGRESS',
    'PLG_PRIVACY_JDLEARN_CAPABILITY_ATTEMPTS',
    'PLG_PRIVACY_JDLEARN_CAPABILITY_CERTIFICATES',
];
$privacyDescriptions = array_map(static fn(string $key): string => Text::_($key), $privacyDescriptionKeys);
$privacyLanguageFailure = static function () use ($app, $privacyTitle): string {
    $language = Factory::getLanguage();
    $appLanguage = $app->getLanguage();
    $tag = $language->getTag();
    $adminFile = JPATH_ADMINISTRATOR . '/language/' . $tag . '/plg_privacy_jdlearn.ini';
    $pluginFile = JPATH_PLUGINS . '/privacy/jdlearn/language/' . $tag . '/plg_privacy_jdlearn.ini';

    return 'JD Learn privacy capability language strings were not loaded (tag=' . $tag
        . ', title=' . $privacyTitle . ', adminFile=' . (is_file($adminFile) ? 'yes' : 'no')
        . ', pluginFile=' . (is_file($pluginFile) ? 'yes' : 'no')
        . ', appHasKey=' . ($appLanguage->hasKey('PLG_PRIVACY_JDLEARN_CAPABILITY_TITLE') ? 'yes' : 'no')
        . ', loadedPaths=' . json_encode($language->getPaths('plg_privacy_jdlearn'), JSON_THROW_ON_ERROR) . ').';
};
if ($privacyTitle === 'PLG_PRIVACY_JDLEARN_CAPABILITY_TITLE') {
    throw new \RuntimeException($privacyLanguageFailure());
}
foreach ($privacyDescriptionKeys as $index => $key) {
    if ($privacyDescriptions[$index] === $key) {
        throw new \RuntimeException($privacyLanguageFailure());
    }
}
$hasPrivacyCapabilities = false;
foreach ($privacyData as $capabilitySet) {
    if (is_array($capabilitySet)
        && array_key_exists($privacyTitle, $capabilitySet)
        && is_array($capabilitySet[$privacyTitle])
        && array_diff($privacyDescriptions, $capabilitySet[$privacyTitle]) === []
    ) {
        $hasPrivacyCapabilities = true;
        break;
    }
}
if (!$hasPrivacyCapabilities) {
    throw new \RuntimeException('JD Learn privacy capabilities were not collected by Joomla.');
}
$privacyRequest = new RequestTable($db);
$privacyExport = new ExportRequestEvent('onPrivacyExportRequest', ['subject' => $privacyRequest, 'user' => $app->getIdentity()]);
$app->getDispatcher()->dispatch('onPrivacyExportRequest', $privacyExport);
$privacyExportData = $privacyExport->getArgument('result', []);
$privacyDomainNames = [];
foreach ($privacyExportData as $contribution) {
    if (!is_array($contribution)) {
        continue;
    }

    foreach ($contribution as $domain) {
        if ($domain instanceof \Joomla\Component\Privacy\Administrator\Export\Domain && str_starts_with($domain->name, 'jdlearn_')) {
            $privacyDomainNames[] = $domain->name;
        }
    }
}
sort($privacyDomainNames);
$expectedPrivacyDomainNames = ['jdlearn_answers', 'jdlearn_attempts', 'jdlearn_certificates', 'jdlearn_course_announcement_consent', 'jdlearn_enrollments', 'jdlearn_enrollment_requests', 'jdlearn_mail_deliveries', 'jdlearn_progress'];
sort($expectedPrivacyDomainNames);
if ($privacyDomainNames !== $expectedPrivacyDomainNames) {
    throw new \RuntimeException('JD Learn privacy export domains missing: ' . implode(', ', $privacyDomainNames));
}
$privacyPlugin = new \Jlms\Plugin\Privacy\Jdlearn\Extension\Jdlearn((array) PluginHelper::getPlugin('privacy', 'jdlearn'));
$privacyPlugin->setApplication($app);
$privacyPlugin->setDatabase($db);
$privacyUserId = (int) $db->setQuery("SELECT id FROM #__users WHERE username='ciprivacyclean'")->loadResult();
if (!$privacyUserId) {
    $privacyUser = new \Joomla\CMS\User\User();
    $privacyUserData = ['name' => 'CI Privacy Clean User', 'username' => 'ciprivacyclean', 'email' => 'ciprivacyclean@example.invalid', 'password' => 'Ci-only-Privacy-Password-2026', 'password2' => 'Ci-only-Privacy-Password-2026', 'groups' => [2], 'block' => 0, 'activation' => ''];
    if (!$privacyUser->bind($privacyUserData) || !$privacyUser->save()) {
        throw new \RuntimeException('Could not create isolated clean privacy user: ' . $privacyUser->getError());
    }
    $privacyUserId = (int) $privacyUser->id;
}
$privacyUser = new \Joomla\CMS\User\User($privacyUserId);
$db->setQuery("DELETE FROM #__jlms_jobs WHERE kind='privacy-fixture' AND owner_user_id=" . $privacyUserId)->execute();
$privacyCheck = new CanRemoveDataEvent('onPrivacyCanRemoveData', ['subject' => $privacyRequest, 'user' => $privacyUser]);
$privacyPlugin->onPrivacyCanRemoveData($privacyCheck);
$privacyCheckData = $privacyCheck->getArgument('result', []);
if (count($privacyCheckData) !== 1 || !$privacyCheckData[0]->canRemove) {
    throw new \RuntimeException('JD Learn privacy plugin blocked removal for a user without JD Learn data.');
}
$db->setQuery("INSERT INTO #__jlms_jobs (kind, owner_user_id, scope_json, payload_json, status, available_at, created_at) VALUES ('privacy-fixture', " . $privacyUserId . ", '{}', '{}', 'complete', UTC_TIMESTAMP(6), UTC_TIMESTAMP(6))")->execute();
$privacyBlocked = new CanRemoveDataEvent('onPrivacyCanRemoveData', ['subject' => $privacyRequest, 'user' => $privacyUser]);
$privacyPlugin->onPrivacyCanRemoveData($privacyBlocked);
$privacyBlockedData = $privacyBlocked->getArgument('result', []);
$privacyRejected = false;
try {
    // Direct event dispatch must never bypass the same retention gate.
    $privacyPlugin->onPrivacyRemoveData(new RemoveDataEvent('onPrivacyRemoveData', ['subject' => $privacyRequest, 'user' => $privacyUser]));
} catch (\RuntimeException $expected) {
    $privacyRejected = str_contains($expected->getMessage(), 'blocked by retained records');
}
if (!$privacyRejected) {
    throw new \RuntimeException('JD Learn privacy removal bypassed an existing retained job.');
}
$db->setQuery("DELETE FROM #__jlms_jobs WHERE kind='privacy-fixture' AND owner_user_id=" . $privacyUserId)->execute();
if (count($privacyBlockedData) !== 1 || $privacyBlockedData[0]->canRemove || !$privacyBlockedData[0]->reason) {
    throw new \RuntimeException('JD Learn privacy plugin did not explain the removal blocker for an owned job.');
}
// Immutable audit attribution must block removal before modifying any state.
$privacyCampaignFixtureId = 900000000000 + $privacyUserId;
$db->setQuery($db->createQuery()->insert('#__jlms_course_announcement_campaigns')
    ->columns(['course_id', 'requested_by', 'recipient_count', 'state', 'requested_at'])
    ->values(implode(',', [$privacyCampaignFixtureId, $privacyUserId, 0, $db->quote('queued'), 'UTC_TIMESTAMP(6)'])))->execute();
try {
    $immutableAuditBlock = new CanRemoveDataEvent('onPrivacyCanRemoveData', ['subject' => $privacyRequest, 'user' => $privacyUser]);
    $privacyPlugin->onPrivacyCanRemoveData($immutableAuditBlock);
    if (($immutableAuditBlock->getArgument('result', []))[0]->canRemove) {
        throw new \RuntimeException('Immutable audit reference did not block privacy removal.');
    }
    $immutableRemovalRejected = false;
    try {
        $privacyPlugin->onPrivacyRemoveData(new RemoveDataEvent('onPrivacyRemoveData', ['subject' => $privacyRequest, 'user' => $privacyUser]));
    } catch (\RuntimeException $error) {
        $immutableRemovalRejected = str_contains($error->getMessage(), 'blocked by retained records');
    }
    if (!$immutableRemovalRejected) {
        throw new \RuntimeException('Direct erasure bypassed immutable audit retention.');
    }
} finally {
    $db->setQuery($db->createQuery()->delete('#__jlms_course_announcement_campaigns')
        ->where('course_id = ' . $privacyCampaignFixtureId))->execute();
}
// Verify all MAIL-11 consent states on an isolated disposable Joomla account,
// not a mock of the Privacy event. Consent remains the only authoritative row.
foreach (['pending', 'confirmed', 'revoked'] as $consentFixtureState) {
    $db->setQuery($db->createQuery()->delete('#__jlms_mail_consents')
        ->where('user_id = ' . $privacyUserId))->execute();
    $db->setQuery($db->createQuery()->insert('#__jlms_mail_consents')
        ->columns(['user_id', 'state', 'origin', 'policy_version', 'requested_at'])
        ->values(implode(',', [
            $privacyUserId, $db->quote($consentFixtureState),
            $db->quote('dashboard'), $db->quote('ci-test'), 'UTC_TIMESTAMP(6)'
        ])))->execute();
    $consentExportEvent = new ExportRequestEvent('onPrivacyExportRequest', ['subject' => $privacyRequest, 'user' => $privacyUser]);
    $privacyPlugin->onPrivacyExportRequest($consentExportEvent);
    $foundConsentDomain = false;
    foreach ($consentExportEvent->getArgument('result', []) as $contribution) {
        foreach ((array) $contribution as $domain) {
            if ($domain instanceof \Joomla\Component\Privacy\Administrator\Export\Domain
                && $domain->name === 'jdlearn_course_announcement_consent') {
                $foundConsentDomain = count($domain->getItems()) === 1;
            }
        }
    }
    if (!$foundConsentDomain) {
        throw new \RuntimeException('Joomla Privacy did not export consent state ' . $consentFixtureState);
    }
}
// PRIV-C2: administrator sees only the current consent record and safe fields.
$consentAudit = new \Jlms\Component\Jlms\Administrator\Application\ConsentAuditService($db);
$consentAuditData = $consentAudit->page($app->getIdentity(), $privacyUserId, 0, 25);
if ($consentAuditData['total'] !== 1 || count($consentAuditData['rows']) !== 1
    || (int) $consentAuditData['rows'][0]['user_id'] !== $privacyUserId
    || $consentAuditData['rows'][0]['state'] !== 'revoked') {
    throw new \RuntimeException('Protected consent audit does not show current revocation state.');
}
if (array_diff(array_keys($consentAuditData['rows'][0]),
    ['user_id', 'state', 'origin', 'policy_version', 'requested_at', 'confirmed_at', 'revoked_at'])) {
    throw new \RuntimeException('Protected consent audit exposed unapproved fields.');
}
$consentAuditDenied = false;
try {
    $consentAudit->page(new \Joomla\CMS\User\User(), $privacyUserId);
} catch (\RuntimeException $error) {
    $consentAuditDenied = $error->getCode() === 403;
}
if (!$consentAuditDenied) {
    throw new \RuntimeException('Guest user accessed global consent audit.');
}
$privacyTokenHash = hash('sha256', 'privacy-revocation-token-fixture-' . $privacyUserId);
$db->setQuery($db->createQuery()->insert('#__jlms_mail_unsubscribe_tokens')
    ->columns(['user_id', 'consent_confirmed_at', 'token_hash', 'created_at', 'expires_at'])
    ->values(implode(',', [
        $privacyUserId, 'UTC_TIMESTAMP(6)', $db->quote($privacyTokenHash),
        'UTC_TIMESTAMP(6)', 'DATE_ADD(UTC_TIMESTAMP(6), INTERVAL 1 HOUR)'
    ])))->execute();
// SEC-P3: active mail queue rows must block erasure; terminal rows are redacted.
$privacyMailId = hash('sha256', 'privacy-mail-queue-ci-' . $privacyUserId);
$db->setQuery("DELETE FROM #__jlms_mail_queue WHERE event_key=" . $db->quote($privacyMailId))->execute();
$db->setQuery($db->createQuery()->insert('#__jlms_mail_queue')
    ->columns(['event_key', 'template_id', 'recipient_user_id', 'recipient_email', 'recipient_name', 'template_data', 'state', 'available_at', 'created_at'])
    ->values(implode(',', [
        $db->quote($privacyMailId), $db->quote('ci.privacy'), (int) $privacyUserId,
        $db->quote('privacy-fixture@example.invalid'), $db->quote('Privacy Fixture'),
        $db->quote('{"fixture":"secret"}'), $db->quote('pending'), 'UTC_TIMESTAMP(6)', 'UTC_TIMESTAMP(6)'
    ])))->execute();
// SEC-P3: two distinct accounts. An unrelated recipient cannot pollute the
// requested subject's Privacy export or be anonymized with the first account.
$otherPrivacyUserId = (int) $app->getIdentity()->id;
if ($otherPrivacyUserId <= 0 || $otherPrivacyUserId === $privacyUserId) {
    throw new \RuntimeException('Privacy two-account fixture lacks an independent Joomla user.');
}
$otherPrivacyMailKey = hash('sha256', 'privacy-mail-other-user-ci-' . $privacyUserId);
$db->setQuery("DELETE FROM #__jlms_mail_queue WHERE event_key=" . $db->quote($otherPrivacyMailKey))->execute();
$db->setQuery($db->createQuery()->insert('#__jlms_mail_queue')
    ->columns(['event_key', 'template_id', 'recipient_user_id', 'recipient_email', 'recipient_name', 'template_data', 'state', 'available_at', 'created_at', 'reconciliation_by'])
    ->values(implode(',', [
        $db->quote($otherPrivacyMailKey), $db->quote('ci.privacy.other'), $otherPrivacyUserId,
        $db->quote('other-privacy-fixture@example.invalid'), $db->quote('Other Privacy Fixture'),
        $db->quote('{"fixture":"other-account"}'), $db->quote('sent'), 'UTC_TIMESTAMP(6)', 'UTC_TIMESTAMP(6)', $privacyUserId
    ])))->execute();
$privacySubjectExport = new ExportRequestEvent('onPrivacyExportRequest', ['subject' => $privacyRequest, 'user' => $privacyUser]);
$privacyPlugin->onPrivacyExportRequest($privacySubjectExport);
$privacySubjectDomains = $privacySubjectExport->getArgument('result', []);
$privacyMailDomains = [];
foreach ($privacySubjectDomains as $privacyContribution) {
    foreach ((array) $privacyContribution as $privacyDomain) {
        if ($privacyDomain instanceof \Joomla\Component\Privacy\Administrator\Export\Domain
            && $privacyDomain->name === 'jdlearn_mail_deliveries') {
            $privacyMailDomains[] = $privacyDomain;
        }
    }
}
if (count($privacyMailDomains) !== 1 || count($privacyMailDomains[0]->getItems()) !== 1) {
    throw new \RuntimeException('Privacy mail export leaked or lost the requested user scope.');
}
$privacyQueueBlocked = new CanRemoveDataEvent('onPrivacyCanRemoveData', ['subject' => $privacyRequest, 'user' => $privacyUser]);
$privacyPlugin->onPrivacyCanRemoveData($privacyQueueBlocked);
if (($privacyQueueBlocked->getArgument('result', []))[0]->canRemove) {
    throw new \RuntimeException('Active privacy mail queue did not block erasure.');
}
$queueRemoveRejected = false;
try {
    $privacyPlugin->onPrivacyRemoveData(new RemoveDataEvent('onPrivacyRemoveData', ['subject' => $privacyRequest, 'user' => $privacyUser]));
} catch (\RuntimeException $expected) {
    $queueRemoveRejected = str_contains($expected->getMessage(), 'blocked by retained records');
}
if (!$queueRemoveRejected) {
    throw new \RuntimeException('Direct privacy erasure ignored pending mail queue.');
}
$db->setQuery("UPDATE #__jlms_mail_queue SET state='sent' WHERE event_key=" . $db->quote($privacyMailId))->execute();
$db->setQuery($db->createQuery()->update('#__jlms_mail_queue')
    ->set('last_error = ' . $db->quote('ci-privacy-diagnostic-placeholder'))
    ->where('event_key = ' . $db->quote($privacyMailId)))->execute();
$privacyRemoval = new RemoveDataEvent('onPrivacyRemoveData', ['subject' => $privacyRequest, 'user' => $privacyUser]);
$privacyPlugin->onPrivacyRemoveData($privacyRemoval);
$sanitizedMail = $db->setQuery("SELECT recipient_user_id,recipient_email,recipient_name,template_data,last_error FROM #__jlms_mail_queue WHERE event_key=" . $db->quote($privacyMailId))->loadObject();
if (!$sanitizedMail || $sanitizedMail->recipient_user_id !== null || $sanitizedMail->recipient_email !== '' || $sanitizedMail->recipient_name !== '' || $sanitizedMail->template_data !== '{}' || $sanitizedMail->last_error !== null) {
    throw new \RuntimeException('Privacy removal did not redact completed mail queue evidence.');
}
$retainedPrivacyConsent = (int) $db->setQuery($db->createQuery()->select('COUNT(*)')
    ->from('#__jlms_mail_consents')->where('user_id = ' . $privacyUserId))->loadResult();
$retainedPrivacyToken = (int) $db->setQuery($db->createQuery()->select('COUNT(*)')
    ->from('#__jlms_mail_unsubscribe_tokens')->where('user_id = ' . $privacyUserId))->loadResult();
if ($retainedPrivacyConsent !== 0 || $retainedPrivacyToken !== 0) {
    throw new \RuntimeException('Privacy erasure retained consent or revocation tokens.');
}
$db->setQuery("DELETE FROM #__jlms_mail_queue WHERE event_key=" . $db->quote($privacyMailId))->execute();
$otherPrivacyRemaining = $db->setQuery($db->createQuery()
    ->select(['recipient_user_id', 'recipient_email', 'template_data', 'reconciliation_by'])
    ->from('#__jlms_mail_queue')
    ->where('event_key = ' . $db->quote($otherPrivacyMailKey)))->loadObject();
if (!$otherPrivacyRemaining || (int) $otherPrivacyRemaining->recipient_user_id !== $otherPrivacyUserId
    || $otherPrivacyRemaining->recipient_email !== 'other-privacy-fixture@example.invalid'
    || $otherPrivacyRemaining->template_data !== '{"fixture":"other-account"}'
    || $otherPrivacyRemaining->reconciliation_by !== null) {
    throw new \RuntimeException('Privacy erasure affected an unrelated account.');
}
$db->setQuery("DELETE FROM #__jlms_mail_queue WHERE event_key=" . $db->quote($otherPrivacyMailKey))->execute();

$component = $app->bootComponent('com_jlms');
$mailConsent = $component->getCourseAnnouncementConsent();
$db->setQuery('DELETE FROM #__jlms_mail_consents WHERE user_id=' . (int) $userId)->execute();
$db->setQuery('INSERT INTO #__jlms_mail_consents (user_id,state,origin,policy_version,requested_at,confirmed_at) VALUES ('
    . (int) $userId . ",'confirmed','dashboard','ci-test',UTC_TIMESTAMP(),UTC_TIMESTAMP())")->execute();
$confirmedAt = (string) $db->setQuery('SELECT confirmed_at FROM #__jlms_mail_consents WHERE user_id=' . (int) $userId)->loadResult();
$linkOne = $mailConsent->createUnsubscribeUrl((int) $userId);
$linkTwo = $mailConsent->createUnsubscribeUrl((int) $userId);
parse_str((string) parse_url((string) $linkOne, PHP_URL_QUERY), $linkQueryOne);
parse_str((string) parse_url((string) $linkTwo, PHP_URL_QUERY), $linkQueryTwo);
$tokenOne = (string) ($linkQueryOne['token'] ?? '');
$tokenTwo = (string) ($linkQueryTwo['token'] ?? '');
if (!$linkOne || !$linkTwo || $tokenOne === '' || $tokenTwo === '' || array_key_exists('user_id', $linkQueryOne) || array_key_exists('email', $linkQueryOne)) {
    throw new \RuntimeException('Unsubscribe link did not contain an opaque token or exposed the user id.');
}
$db->setQuery($db->getQuery(true)->update('#__jlms_mail_unsubscribe_tokens')->set('expires_at = DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 SECOND)')
    ->where('token_hash = ' . $db->quote(hash('sha256', $tokenOne))))->execute();
if ($mailConsent->revokeByToken($tokenOne) || $mailConsent->state((int) $userId) !== 'confirmed') {
    throw new \RuntimeException('Expired unsubscribe token revoked consent.');
}
if (!$mailConsent->revokeByToken($tokenTwo) || $mailConsent->state((int) $userId) !== 'revoked'
    || $mailConsent->revokeByToken($tokenTwo)) {
    throw new \RuntimeException('Valid unsubscribe token was not one-use or did not revoke consent.');
}
$db->setQuery('DELETE FROM #__jlms_mail_consents WHERE user_id=' . (int) $userId)->execute();
$certificateService = $component->getCertificateService();
// SEC-P1: administrator certificate authorization must re-read the Joomla
// account on every operation. A previously privileged session cannot retain
// access once its account is blocked, even for read-only certificate metadata.
$staleCertificateActor = $app->getIdentity();
$certificateAdminId = (int) $staleCertificateActor->id;
$originalCertificateBlock = (int) $db->setQuery($db->createQuery()
    ->select('block')->from('#__users')->where('id = ' . $certificateAdminId))->loadResult();
try {
    $db->setQuery($db->createQuery()->update('#__users')->set('block = 1')
        ->where('id = ' . $certificateAdminId))->execute();
    $blockedCertificateOperations = [
        'list' => static fn() => $certificateService->adminList($staleCertificateActor, []),
        'diagnosis' => static fn() => $certificateService->adminUnissuedCompletions($staleCertificateActor, 1),
        'detail' => static fn() => $certificateService->adminDetail($staleCertificateActor, 1),
        'download' => static fn() => $certificateService->adminDownload($staleCertificateActor, 1),
        'regenerate' => static fn() => $certificateService->adminRegenerate($staleCertificateActor, 1, 1, 'Security denial check'),
        'recover missing' => static fn() => $certificateService->adminRecoverMissing($staleCertificateActor, 1, 'Security denial check'),
        'recover historical' => static fn() => $certificateService->adminRecoverHistorical($staleCertificateActor, 1, 'Security denial check'),
    ];
    foreach ($blockedCertificateOperations as $operation => $invoke) {
        try {
            $invoke();
            throw new \RuntimeException('Blocked account reached certificate admin operation: ' . $operation);
        } catch (\RuntimeException $error) {
            if ($error->getMessage() !== 'JERROR_ALERTNOAUTHOR' || $error->getCode() !== 403) {
                throw $error;
            }
        }
    }
} finally {
    $db->setQuery($db->createQuery()->update('#__users')
        ->set('block = ' . $originalCertificateBlock)
        ->where('id = ' . $certificateAdminId))->execute();
}

$certificateService->adminList($app->getIdentity(), ['q' => 'CERT-C-PROBE', 'status' => 'ready', 'offset' => 0]);
$unissued = $certificateService->adminUnissuedCompletions($app->getIdentity(), 25);
if (!is_array($unissued) || count($unissued) > 25) {
    throw new \RuntimeException('Certificate non-issuance diagnosis must be bounded.');
}
foreach ($unissued as $entry) {
    if (!in_array($entry['reason'] ?? '', [
        'version_disabled', 'preferences_disabled', 'template_missing',
        'eligible_missing', 'document_missing', 'invalid_configuration',
    ], true)) {
        throw new \RuntimeException('Certificate non-issuance diagnosis returned an invalid status.');
    }
}
try {
    $certificateService->adminUnissuedCompletions(new \Joomla\CMS\User\User());
    throw new \RuntimeException('Certificate non-issuance diagnosis must reject anonymous users.');
} catch (\RuntimeException $error) {
    if ($error->getMessage() !== 'JERROR_ALERTNOAUTHOR') { throw $error; }
}

$auditActorId = (int) $userId;
$auditAction = 'admin_list';
$auditCount = (int) $db->setQuery($db->createQuery()->select('COUNT(*)')->from('#__jlms_certificate_audit')
    ->where('actor_user_id = :actor')->where('action = :action')->bind(':actor', $auditActorId)->bind(':action', $auditAction))->loadResult();
if ($auditCount < 1) { throw new \RuntimeException('Certificate administrator list access was not audited.'); }
try {
    $certificateService->adminList(new \Joomla\CMS\User\User(), []);
    throw new \RuntimeException('Certificate administration must reject unauthorised users.');
} catch (\RuntimeException $error) {
    if ($error->getMessage() !== 'JERROR_ALERTNOAUTHOR') { throw $error; }
}
// Explicit historical issuance remains restricted to certificate administrators.
try {
    $certificateService->adminRecoverHistorical(new \Joomla\CMS\User\User(), 1, 'Approved historical repair');
    throw new \RuntimeException('Historical recovery accepted an anonymous caller.');
} catch (\RuntimeException $error) {
    if ($error->getMessage() !== 'JERROR_ALERTNOAUTHOR') { throw $error; }
}
try {
    $certificateService->adminRecoverHistorical($app->getIdentity(), 1, 'short');
    throw new \RuntimeException('Historical recovery accepted a missing justification.');
} catch (\RuntimeException $error) {
    if ($error->getMessage() !== 'COM_JLMS_INVALID_INPUT') { throw $error; }
}
try {
    $certificateService->adminRecoverHistorical($app->getIdentity(), 999999999, 'Approved historical repair');
    throw new \RuntimeException('Historical recovery accepted a nonexistent enrollment.');
} catch (\RuntimeException $error) {
    if ($error->getMessage() !== 'COM_JLMS_RESOURCE_NOT_FOUND') { throw $error; }
}

// Missing issuance recovery is a separate, explicit action: never a background backfill.
try {
    $certificateService->adminRecoverMissing(new \Joomla\CMS\User\User(), 1, 'Approved missing issuance recovery');
    throw new \RuntimeException('Missing issuance recovery accepted an anonymous caller.');
} catch (\RuntimeException $error) {
    if ($error->getMessage() !== 'JERROR_ALERTNOAUTHOR') { throw $error; }
}
try {
    $certificateService->adminRecoverMissing($app->getIdentity(), 1, 'short');
    throw new \RuntimeException('Missing issuance recovery accepted an invalid justification.');
} catch (\RuntimeException $error) {
    if ($error->getMessage() !== 'COM_JLMS_INVALID_INPUT') { throw $error; }
}
try {
    $certificateService->adminRecoverMissing($app->getIdentity(), 999999999, 'Approved missing issuance recovery');
    throw new \RuntimeException('Missing issuance recovery accepted an unknown enrollment.');
} catch (\RuntimeException $error) {
    if ($error->getMessage() !== 'COM_JLMS_RESOURCE_NOT_FOUND') { throw $error; }
}

// CERT-08: mutation boundary must reject an anonymous caller before even
// inspecting the certificate ID; a legitimate admin gets input/404 errors.
try {
    $certificateService->adminRegenerate(new \Joomla\CMS\User\User(), 1, 1, 'Technical PDF repair');
    throw new \RuntimeException('Certificate regeneration must reject unauthorized users.');
} catch (\RuntimeException $error) {
    if ($error->getMessage() !== 'JERROR_ALERTNOAUTHOR') { throw $error; }
}
try {
    $certificateService->adminRegenerate($app->getIdentity(), 0, 1, 'Technical PDF repair');
    throw new \RuntimeException('Certificate regeneration accepted invalid ID.');
} catch (\RuntimeException $error) {
    if ($error->getMessage() !== 'COM_JLMS_INVALID_INPUT') { throw $error; }
}
try {
    $certificateService->adminRegenerate($app->getIdentity(), 999999999, 1, 'Technical PDF repair');
    throw new \RuntimeException('Certificate regeneration accepted an absent certificate.');
} catch (\RuntimeException $error) {
    if ($error->getMessage() !== 'COM_JLMS_RESOURCE_NOT_FOUND') { throw $error; }
}

$factory = $component->getMVCFactory();
if (($argv[2] ?? '') === 'retained') {
    $record = $db->setQuery("SELECT title, revision FROM #__jlms_courses WHERE alias='test-course'")->loadObject();
    $enabled = (int) $db->setQuery("SELECT COUNT(*) FROM #__extensions WHERE type='plugin' AND folder='jlmsvideo' AND enabled=1")->loadResult();
    if (!$record || $record->title !== 'Updated course' || (int) $record->revision !== 2 || $enabled !== 4) {
        throw new \RuntimeException('Package update lost draft data or plugin state.');
    }
    echo json_encode(['admin_menu_install_checks' => 1, 'package_upgrade' => 'passed', 'draft_revision' => 2, 'enabled_providers' => $enabled], JSON_THROW_ON_ERROR) . PHP_EOL;
    exit(0);
}

$managerTemplate = \Joomla\CMS\Mail\MailTemplate::getTemplate('com_jlms.enrollment.manager_request', '*');
if (!$managerTemplate || !str_contains((string) $managerTemplate->subject, '{COURSE_TITLE}')) {
    throw new \RuntimeException('Manager notification Mail Template not installed.');
}
$courseUpdatedTemplate = \Joomla\CMS\Mail\MailTemplate::getTemplate('com_jlms.course.updated', '*');
if (!$courseUpdatedTemplate || !str_contains((string) $courseUpdatedTemplate->subject, '{COURSE_TITLE}')) {
    throw new \RuntimeException('Significant course update template not installed.');
}
$lessonTemplate = \Joomla\CMS\Mail\MailTemplate::getTemplate('com_jlms.lesson.available', '*');
if (!$lessonTemplate || !str_contains((string) $lessonTemplate->subject, '{LESSON_TITLE}')) {
    throw new \RuntimeException('Lesson release Mail Template not registered in Joomla.');
}
$categoryModel = $app->bootComponent('com_categories')->getMVCFactory()->createModel('Category', 'Administrator', ['ignore_request' => true]);
if (!$categoryModel->save(['id' => 0,'parent_id' => 1,'extension' => 'com_jlms.course','title' => 'CI Courses','alias' => 'ci-courses','published' => 1,'language' => '*','access' => 1])) {
    throw new \RuntimeException('Native Joomla category save failed: ' . $categoryModel->getError());
}
$catid = (int) $categoryModel->getState('category.id');
$model = $factory->createModel('Course', 'Administrator', ['ignore_request' => true]);
$form = $model->getForm([], false);
if (!$form) {
    throw new \RuntimeException('Joomla course form failed to load.');
}
$data = ['id' => 0,'title' => 'Test course','alias' => 'test-course','catid' => $catid,'language' => '*','catalog_access' => 1,'content_access' => 2,'enrollment_mode' => 'auto','summary' => 'Summary','revision' => 1];
if (!$model->save($data + ['state' => 1,'asset_id' => 999999,'current_version_id' => 999999])) {
    throw new \RuntimeException('Native draft save failed: ' . $model->getError());
}
$id = (int) $model->getState('course.id');
$managerNotice = new \Jlms\Component\Jlms\Administrator\Application\EnrollmentManagerNotice($db);
$pendingScan = $managerNotice->enqueueDue(3);
if (($pendingScan['processed'] ?? -1) !== 0 || $pendingScan['failed'] !== 0
    || \Jlms\Component\Jlms\Administrator\Application\EnrollmentManagerNotice::authorized($db, $id, 0)) {
    throw new \RuntimeException('Pending manager alerts must not send without a real pending request and authorized manager.');
}
$record = $model->getItem($id);
if ($id < 1 || (int) $record->state !== 0 || $record->current_version_id !== null || (int) $record->revision !== 1) {
    throw new \RuntimeException('Draft mass assignment or initial revision invariant failed.');
}
$parent = (int) $db->setQuery('SELECT asset_id FROM #__categories WHERE id=' . $catid)->loadResult();
$actual = (int) $db->setQuery('SELECT parent_id FROM #__assets WHERE id=' . (int)$record->asset_id)->loadResult();
if ($parent !== $actual) {
    throw new \RuntimeException('Course asset does not inherit native category ACL.');
}
$data['id'] = $id;$data['title'] = 'Updated course';
if (!$model->save($data)) {
    throw new \RuntimeException('Draft update failed: ' . $model->getError());
}
if ($model->save($data)) {
    throw new \RuntimeException('Obsolete revision overwrote a newer draft.');
}
// The native Joomla registration form receives the optional, unchecked consent field.
PluginHelper::importPlugin('user');
$registrationForm = Form::getInstance('com_users.registration', JPATH_SITE . '/components/com_users/forms/registration.xml', ['control' => 'jform']);
$app->getDispatcher()->dispatch('onContentPrepareForm', new PrepareFormEvent('onContentPrepareForm', [
    'subject' => $registrationForm,
    'data' => [],
]));
$consentField = $registrationForm->getField('course_announcements', 'jdlearn');
if (!$consentField || (string) $consentField->getAttribute('default') !== '0') {
    throw new \RuntimeException('Native registration form is missing the unchecked course announcement consent field.');
}
// Enable providers only inside the disposable fixture to exercise Joomla's real plugin boot/dispatcher.
PluginHelper::importPlugin('jlmsvideo');
$registry = new VideoProviderRegistry();
$app->getDispatcher()->dispatch(RegisterVideoProvidersEvent::NAME, new RegisterVideoProvidersEvent($registry));
$keys = array_map(static fn ($x): string => $x->key, $registry->descriptors());sort($keys);
if ($keys !== ['cloudflarestream','html5','vimeo','youtube']) {
    throw new \RuntimeException('Four video providers were not registered by Joomla.');
}
// Instantiate all concrete MVC classes so incompatible inheritance signatures fail in CI.
foreach (['Dashboard','Courses','Course','Mailconsentrevoke'] as $view) {
    $factory->createView($view, 'Administrator', 'Html');
}
$factory->createView('Courses', 'Site', 'Html');
$categoryService = $component->getCategory([], 'course');
if (!$categoryService) {
    throw new \RuntimeException('Native category service failed.');
}
echo json_encode(['admin_menu_install_checks' => 1,'joomla' => JVERSION,'status' => 'passed','native_course_id' => $id,'providers' => $keys,'draft_revision_conflict' => 'denied','mass_assignment' => 'denied'], JSON_THROW_ON_ERROR) . PHP_EOL;
