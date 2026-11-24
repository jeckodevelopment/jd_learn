<?php

declare(strict_types=1);

use Joomla\CMS\Factory;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Session\Session;
use Joomla\Session\SessionInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Jlms\Contracts\Event\RegisterVideoProvidersEvent;
use Jlms\Contracts\Video\VideoProviderRegistry;

function builderExpect(bool $ok, string $message): void
{
    global $checks;
    if (!$ok) {
        throw new RuntimeException($message);
    } $checks++;
}

function builderDenied(callable $work, string $key): void
{
    try {
        $work();
    } catch (\Throwable $e) {
        builderExpect($e->getMessage() === $key, 'Expected ' . $key . ', got ' . $e->getMessage());
        return;
    }
    throw new RuntimeException('Expected rejection: ' . $key);
}

function builderRow(string $table, int $id): object
{
    global $db;
    return $db->setQuery('SELECT * FROM #__jlms_' . $table . ' WHERE id=' . $id)->loadObject();
}

function builderCheckout(string $table, int $id, ?int $user): void
{
    global $db;
    $row = (object)['id' => $id,'checked_out' => $user];
    $db->updateObject('#__jlms_' . $table, $row, 'id', true);
}

function builderSnapshot(int $course, int $version): array
{
    global $db;
    return [$db->setQuery('SELECT * FROM #__jlms_courses WHERE id=' . $course)->loadAssoc(),
        $db->setQuery('SELECT * FROM #__jlms_course_versions WHERE id=' . $version)->loadAssoc(),
        $db->setQuery('SELECT * FROM #__jlms_modules WHERE course_version_id=' . $version . ' ORDER BY id')
            ->loadAssocList(),
        $db->setQuery('SELECT * FROM #__jlms_lessons WHERE course_version_id=' . $version . ' ORDER BY id')
            ->loadAssocList()];
}
