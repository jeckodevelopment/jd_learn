<?php

declare(strict_types=1);


\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Version;
use Joomla\CMS\Table\Asset;
use Joomla\Database\DatabaseInterface;
use Jlms\Component\Jlms\Administrator\Infrastructure\SchemaInstaller;

final class Com_JlmsInstallerScript
{
    public function preflight($type, $parent): bool
    {
        if ($type === 'uninstall') {
            return true;
        }
        if (PHP_VERSION_ID < 80300 || PHP_INT_SIZE < 8 || version_compare(JVERSION, '6.0.0', '<')
            || version_compare(JVERSION, '7.0.0', '>=')) {
            throw new \RuntimeException('JD Learn requires Joomla 6 and 64-bit PHP 8.3+.');
        }
        if (!is_file(JPATH_LIBRARIES . '/jlms/bootstrap.php')) {
            throw new \RuntimeException('Install the complete JD Learn package: contracts library is required.');
        }
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        if ($db->getServerType() !== 'mysql' || strlen($db->getPrefix()) > 25) {
            throw new \RuntimeException('JD Learn requires MySQL/MariaDB and a table prefix of at most 25 characters.');
        }
        $version = $db->getVersion();
        $maria = stripos($version, 'mariadb') !== false || $db->isMariaDb();
        preg_match($maria ? '/(?:5\.5\.5-)?(\d+\.\d+\.\d+)/' : '/(\d+\.\d+\.\d+)/', $version, $match);
        if (!isset($match[1]) || version_compare($match[1], $maria ? '10.6.0' : '8.0.13', '<')) {
            throw new \RuntimeException('Unsupported database version for JD Learn.');
        }
        return true;
    }

    public function install($parent): bool
    {
        return $this->applySchema();
    }

    public function update($parent): bool
    {
        return $this->applySchema();
    }

    private function applySchema(): bool
    {
        $base = JPATH_ADMINISTRATOR . '/components/com_jlms';
        require_once $base . '/src/Infrastructure/SchemaInstaller.php';
        $sql = file_get_contents($base . '/sql/install.mysql.utf8mb4.sql');
        if ($sql === false) {
            throw new \RuntimeException('JD Learn schema is unavailable.');
        }
        (new SchemaInstaller(Factory::getContainer()->get(DatabaseInterface::class)))->apply($sql);
        return true;
    }

    public function postflight($type, $parent): bool
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $component = new Asset($db);
        if (!$component->loadByName('com_jlms')) {
            throw new \RuntimeException('JD Learn component ACL root is unavailable.');
        }
        // Section roots make native categories inherit from the component instead of Joomla ROOT.
        foreach (['course', 'question', 'teacher', 'pathway'] as $section) {
            $asset = new Asset($db);
            if (!$asset->loadByName('com_jlms.' . $section)) {
                $asset->setLocation((int) $component->id, 'last-child');
                $asset->name = 'com_jlms.' . $section;
                $asset->title = 'JD Learn ' . $section;
                $asset->rules = '{}';
                if (!$asset->check() || !$asset->store()) {
                    throw new \RuntimeException('JD Learn category ACL setup failed.');
                }
            }
        }
        $query=$db->createQuery()->select('COUNT(*)')->from('#__action_logs_extensions')->where("extension = 'com_jlms'");
        if(!(int)$db->setQuery($query)->loadResult()){$entry=(object)['extension'=>'com_jlms'];$db->insertObject('#__action_logs_extensions',$entry);}
        require_once JPATH_ADMINISTRATOR . '/components/com_jlms/src/Infrastructure/WorkflowInstaller.php';
        \Jlms\Component\Jlms\Administrator\Infrastructure\WorkflowInstaller::install(Factory::getApplication(), $db);
        require_once JPATH_ADMINISTRATOR . '/components/com_jlms/src/Infrastructure/EditorialInstaller.php';
        \Jlms\Component\Jlms\Administrator\Infrastructure\EditorialInstaller::install($db);
        return true;
    }

    public function uninstall($parent): bool
    {
        // Deliberately retain learning data and blobs. No destructive uninstall SQL.
        return true;
    }
}
