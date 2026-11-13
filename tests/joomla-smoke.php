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
// Native extension installer must create the submenu once and preserve it on upgrade.
$menuParent = (int) $db->setQuery("SELECT id FROM #__menu WHERE client_id=1 AND menutype='main' AND link='index.php?option=com_jlms&view=dashboard' AND parent_id=1")->loadResult();
$menuLinks = $db->setQuery($db->getQuery(true)->select($db->quoteName('link'))->from($db->quoteName('#__menu'))->where($db->quoteName('client_id') . ' = 1')->where($db->quoteName('parent_id') . ' = ' . $menuParent)->order($db->quoteName('lft') . ' ASC'))->loadColumn();
$expectedLinks = ['index.php?option=com_jlms&view=dashboard', 'index.php?option=com_jlms&view=courses', 'index.php?option=com_jlms&view=mailqueue', 'index.php?option=com_jlms&view=pathways', 'index.php?option=com_categories&view=categories&extension=com_jlms.course', 'index.php?option=com_jlms&view=categoryreassignment', 'index.php?option=com_jlms&view=questionbank', 'index.php?option=com_jlms&view=teachers', 'index.php?option=com_fields&view=fields&context=com_jlms.course', 'index.php?option=com_plugins&view=plugins&filter[folder]=jlmsvideo&filter[search]=&filter[enabled]=&filter[access]=&filter[element]=&limitstart=0'];
$menuTitles = $db->setQuery($db->getQuery(true)->select($db->quoteName('title'))->from($db->quoteName('#__menu'))->where($db->quoteName('client_id') . ' = 1')->where($db->quoteName('parent_id') . ' = ' . $menuParent)->order($db->quoteName('lft') . ' ASC'))->loadColumn();
$expectedMenuTitles = ['COM_JLMS_DASHBOARD_MENU', 'COM_JLMS_COURSES', 'COM_JLMS_MAIL_QUEUE', 'COM_JLMS_PATHWAYS', 'COM_JLMS_COURSE_CATEGORIES', 'COM_JLMS_CATEGORY_REASSIGN_TITLE', 'COM_JLMS_QUESTION_BANK', 'COM_JLMS_TEACHERS', 'COM_JLMS_CUSTOM_FIELDS', 'COM_JLMS_VIDEO_PLUGINS'];
if (!$menuParent || $menuLinks !== $expectedLinks || $menuTitles !== $expectedMenuTitles) { throw new \RuntimeException('Native administrator menu must include Dashboard first with the localized label and without duplicates.'); }
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
        'com_jlms.enrollment.confirmed' => ['NAME', 'COURSE', 'SITENAME', 'COURSE_URL'],
        'com_jlms.certificate.available' => ['NAME', 'COURSE', 'SITENAME', 'CERTIFICATE_URL'],
        default => ['NAME', 'COURSE', 'SITENAME'],
    };
    foreach ($requiredTags as $requiredTag) {
        if (!in_array($requiredTag, $tags, true)) {
            throw new \RuntimeException('JD Learn mail template does not document its variable ' . $requiredTag . ': ' . $templateId);
        }
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
$expectedPrivacyDomainNames = ['jdlearn_answers', 'jdlearn_attempts', 'jdlearn_certificates', 'jdlearn_course_announcement_consent', 'jdlearn_enrollments', 'jdlearn_enrollment_requests', 'jdlearn_progress'];
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
$db->setQuery("DELETE FROM #__jlms_jobs WHERE kind='privacy-fixture' AND owner_user_id=" . $privacyUserId)->execute();
if (count($privacyBlockedData) !== 1 || $privacyBlockedData[0]->canRemove || !$privacyBlockedData[0]->reason) {
    throw new \RuntimeException('JD Learn privacy plugin did not explain the removal blocker for an owned job.');
}
$privacyRemoval = new RemoveDataEvent('onPrivacyRemoveData', ['subject' => $privacyRequest, 'user' => $privacyUser]);
$privacyPlugin->onPrivacyRemoveData($privacyRemoval);
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
$certificateService->adminList($app->getIdentity(), ['q' => 'CERT-C-PROBE', 'status' => 'ready', 'offset' => 0]);
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

$categoryModel = $app->bootComponent('com_categories')->getMVCFactory()->createModel('Category', 'Administrator', ['ignore_request'=>true]);
if (!$categoryModel->save(['id'=>0,'parent_id'=>1,'extension'=>'com_jlms.course','title'=>'CI Courses','alias'=>'ci-courses','published'=>1,'language'=>'*','access'=>1])) {
    throw new \RuntimeException('Native Joomla category save failed: ' . $categoryModel->getError());
}
$catid = (int) $categoryModel->getState('category.id');
$model = $factory->createModel('Course','Administrator',['ignore_request'=>true]);
$form = $model->getForm([], false);
if (!$form) {
    throw new \RuntimeException('Joomla course form failed to load.');
}
$data=['id'=>0,'title'=>'Test course','alias'=>'test-course','catid'=>$catid,'language'=>'*','catalog_access'=>1,'content_access'=>2,'enrollment_mode'=>'auto','summary'=>'Summary','revision'=>1];
if (!$model->save($data + ['state'=>1,'asset_id'=>999999,'current_version_id'=>999999])) {
    throw new \RuntimeException('Native draft save failed: ' . $model->getError());
}
$id = (int) $model->getState('course.id');
$record = $model->getItem($id);
if ($id < 1 || (int) $record->state !== 0 || $record->current_version_id !== null || (int) $record->revision !== 1) {
    throw new \RuntimeException('Draft mass assignment or initial revision invariant failed.');
}
$parent = (int) $db->setQuery('SELECT asset_id FROM #__categories WHERE id=' . $catid)->loadResult();
$actual = (int) $db->setQuery('SELECT parent_id FROM #__assets WHERE id=' . (int)$record->asset_id)->loadResult();
if ($parent !== $actual) {
    throw new \RuntimeException('Course asset does not inherit native category ACL.');
}
$data['id']=$id;$data['title']='Updated course';
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
$app->getDispatcher()->dispatch(RegisterVideoProvidersEvent::NAME,new RegisterVideoProvidersEvent($registry));
$keys=array_map(static fn ($x): string => $x->key,$registry->descriptors());sort($keys);
if ($keys!==['cloudflarestream','html5','vimeo','youtube']) {
    throw new \RuntimeException('Four video providers were not registered by Joomla.');
}
// Instantiate all concrete MVC classes so incompatible inheritance signatures fail in CI.
foreach (['Dashboard','Courses','Course','Mailconsentrevoke'] as $view) {
    $factory->createView($view,'Administrator','Html');
}
$factory->createView('Courses','Site','Html');
$categoryService=$component->getCategory([], 'course');
if (!$categoryService) {
    throw new \RuntimeException('Native category service failed.');
}
echo json_encode(['admin_menu_install_checks'=>1,'joomla'=>JVERSION,'status'=>'passed','native_course_id'=>$id,'providers'=>$keys,'draft_revision_conflict'=>'denied','mass_assignment'=>'denied'],JSON_THROW_ON_ERROR) . PHP_EOL;
