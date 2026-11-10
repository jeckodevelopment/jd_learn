<?php

declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\Database\DatabaseInterface;

return new class implements InstallerScriptInterface {
    public function install(InstallerAdapter $adapter): bool { return true; }
    public function update(InstallerAdapter $adapter): bool { return true; }
    public function uninstall(InstallerAdapter $adapter): bool { return true; }
    public function preflight(string $type, InstallerAdapter $adapter): bool { return true; }

    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        if ($type !== 'install') { return true; }
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery()->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('enabled') . ' = 1')
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('finder'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('jlms'));
        $db->setQuery($query)->execute();
        return true;
    }
};
