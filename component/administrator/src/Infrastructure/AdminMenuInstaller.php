<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Infrastructure;

use Joomla\Database\DatabaseInterface;

final class AdminMenuInstaller
{
    public static function ensureDashboardRootLink(DatabaseInterface $db): void
    {
        $componentId = (int) $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName('extension_id'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_jlms')),
        )->loadResult();

        if (!$componentId) {
            throw new \RuntimeException('JD Learn component menu cannot be synchronized.');
        }

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__menu'))
            ->set($db->quoteName('link') . ' = ' . $db->quote('index.php?option=com_jlms&view=dashboard'))
            ->where($db->quoteName('client_id') . ' = 1')
            ->where($db->quoteName('menutype') . ' = ' . $db->quote('main'))
            ->where($db->quoteName('parent_id') . ' = 1')
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
            ->where($db->quoteName('component_id') . ' = ' . $componentId)
            ->where($db->quoteName('link') . ' IN ('
                . $db->quote('index.php?option=com_jlms') . ', '
                . $db->quote('index.php?option=com_jlms&view=courses') . ')');

        $db->setQuery($query)->execute();
    }
}
