<?php

declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

final class pkg_jlmsInstallerScript
{
    public function postflight($type, $parent): bool
    {
        // Enable the privacy integration for a fresh package installation. On updates,
        // preserve the administrator's current choice, including an intentional disable.
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
        $enabled = $db->setQuery($verify)->loadResult();

        if ((int) $enabled !== 1) {
            throw new RuntimeException('JD Learn Privacy plugin was not installed or could not be enabled.');
        }

        return true;
    }
}
