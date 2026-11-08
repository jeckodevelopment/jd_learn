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
        // The plugin installer creates new plugins disabled. Enable JD Learn Privacy
        // only on a fresh install and preserve the administrator's choice on updates.
        if ($type !== 'install') {
            return true;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery()
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('enabled') . ' = 1')
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('privacy'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('jdlearn'));
        $db->setQuery($query)->execute();

        $verify = $db->createQuery()
            ->select($db->quoteName('enabled'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('privacy'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('jdlearn'));

        return (int) $db->setQuery($verify)->loadResult() === 1;
    }
};
