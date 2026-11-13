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
    $fixtureDb->exec("UPDATE joomla_extensions SET enabled=1 WHERE type='plugin' AND folder='jlmsvideo'");
    if (($argv[3] ?? '') === 'disabled-upgrade') {
        $fixtureDb->exec("UPDATE joomla_extensions SET enabled=0 WHERE type='plugin' AND folder='user' AND element='jdlearnconsent'");
    }
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
use Jlms\Contracts\Event\RegisterVideoProvidersEvent;
use Jlms\Contracts\Video\VideoProviderRegistry;

$container = Factory::getContainer();
$container->alias('session.web', 'session.web.administrator')
    ->alias('session', 'session.web.administrator')
    ->alias(Session::class, 'session.web.administrator')
    ->alias(SessionInterface::class, 'session.web.administrator');
$app = $container->get(AdministratorApplication::class);
Factory::$application = $app;
$app->createExtensionNamespaceMap();
$db = $container->get(DatabaseInterface::class);
$userId = (int) $db->setQuery("SELECT id FROM #__users WHERE username = 'cioperator'")->loadResult();
if (!$userId) {
    throw new \RuntimeException('Test Joomla operator missing.');
}
$app->loadIdentity($container->get(UserFactoryInterface::class)->loadUserById($userId));

// Same native Installer API used by Joomla's CLI command, with its diagnostic queue preserved.
$packagePath = realpath($argv[2] ?? '');
$expectDisabledPlugin = ($argv[3] ?? '') === 'disabled-upgrade';
if (!$packagePath || !str_ends_with($packagePath,'.zip')) { throw new \RuntimeException('Package ZIP required.'); }
if ($expectDisabledPlugin) {
    $db->setQuery("UPDATE #__extensions SET enabled=0 WHERE type='plugin' AND folder='system' AND element='jlmsmenu'")->execute();
}
$package = \Joomla\CMS\Installer\InstallerHelper::unpack($packagePath, true);
$installer = new \Joomla\CMS\Installer\Installer();
$installer->setDatabase($db);
try {
    $ok = $installer->install($package['extractdir']);
    if (!$ok) {
        throw new \RuntimeException('Native package installation failed: ' . json_encode($app->getMessageQueue(),JSON_THROW_ON_ERROR));
    }
    $privacyPlugin = $db->setQuery("SELECT enabled FROM #__extensions WHERE type='plugin' AND folder='privacy' AND element='jdlearn'")->loadResult();
    if ($privacyPlugin === null || (int) $privacyPlugin !== 1) {
        throw new \RuntimeException('The package installer did not enable the JD Learn privacy plugin.');
    }
    $consentPlugin = $db->setQuery("SELECT enabled FROM #__extensions WHERE type='plugin' AND folder='user' AND element='jdlearnconsent'")->loadResult();
    $expectedConsentState = $expectDisabledPlugin ? 0 : 1;
    if ($consentPlugin === null || (int) $consentPlugin !== $expectedConsentState) {
        throw new \RuntimeException($expectDisabledPlugin
            ? 'A JD Learn package upgrade must preserve the registration consent plugin state.'
            : 'The package installer did not enable the registration consent plugin.');
    }
    $menuPlugin = $db->setQuery("SELECT enabled FROM #__extensions WHERE type='plugin' AND folder='system' AND element='jlmsmenu'")->loadResult();
    $expectedPluginState = $expectDisabledPlugin ? 0 : 1;
    if ($menuPlugin === null || (int) $menuPlugin !== $expectedPluginState) {
        throw new \RuntimeException($expectDisabledPlugin
            ? 'A JD Learn package upgrade must preserve the administrator plugin state.'
            : 'The package installer did not enable the JD Learn menu ACL plugin.');
    }

    echo json_encode([
        'package_install' => 'passed',
        'menu_acl_plugin_state' => $expectDisabledPlugin ? 'preserved disabled across upgrade' : 'enabled on install',
        'registration_consent_plugin_state' => $expectDisabledPlugin ? 'preserved disabled across upgrade' : 'enabled on install',
        'messages' => $app->getMessageQueue(),
    ], JSON_THROW_ON_ERROR) . PHP_EOL;
} finally {
    \Joomla\CMS\Installer\InstallerHelper::cleanupInstall('', $package['extractdir']);
}
