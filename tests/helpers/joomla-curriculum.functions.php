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

function curriculumExpect(bool $ok, string $message): void
{
    global $checks;
    if (!$ok) {
        throw new \RuntimeException($message);
    }
    $checks++;
}

function curriculumDenied(callable $call, string $message): void
{
    try {
        $call();
    } catch (\DomainException | \InvalidArgumentException $expected) {
        curriculumExpect(true, $message);
        return;
    }
    throw new \RuntimeException($message);
}

function versionRevision(): int
{
    global $db, $version;
    return (int) $db->setQuery('SELECT revision FROM #__jlms_course_versions WHERE id=' . $version)->loadResult();
}

function scopeInput(): array
{
    global $course, $version;
    return ['course_id' => $course,'version_id' => $version,'expected_version_revision' => versionRevision()];
}

function recordRevision(string $table, int $id): int
{
    global $db;
    return (int) $db->setQuery('SELECT revision FROM #__jlms_' . $table . ' WHERE id=' . $id)->loadResult();
}
