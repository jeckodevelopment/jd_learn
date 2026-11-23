<?php

declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\Database\DatabaseInterface;

return new class implements InstallerScriptInterface {
    public function install(InstallerAdapter $adapter): bool
    {
        return true;
    }
    public function update(InstallerAdapter $adapter): bool
    {
        return true;
    }
    public function uninstall(InstallerAdapter $adapter): bool
    {
        return true;
    }
    public function preflight(string $type, InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        // New installations enable native logging. Never override an admin's
        // choice to disable the plugin during an update.
        if ($type !== 'install') {
            return true;
        }
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $q = $db->createQuery()->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('enabled') . ' = 1')
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('actionlog'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('jdlearn'));
        $db->setQuery($q)->execute();
        $check = $db->createQuery()->select('enabled')->from('#__extensions')
            ->where('type = ' . $db->quote('plugin'))
            ->where('folder = ' . $db->quote('actionlog'))
            ->where('element = ' . $db->quote('jdlearn'));
        return (int) $db->setQuery($check)->loadResult() === 1;
    }
};
