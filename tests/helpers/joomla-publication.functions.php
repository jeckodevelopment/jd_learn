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

function pubExpect(bool $ok, string $message): void
{
    global $checks;
    if (!$ok) {
        throw new \RuntimeException($message);
    }
    $checks++;
}

function pubDenied(callable $call, string $message): void
{
    try {
        $call();
    } catch (\DomainException | \InvalidArgumentException $expected) {
        pubExpect(true, $message);
        return;
    }
    throw new \RuntimeException($message);
}

function pubRevision(string $table, int $id): int
{
    global $db;
    return (int) $db->setQuery('SELECT revision FROM #__jlms_' . $table . ' WHERE id=' . $id)->loadResult();
}

function pubScope(): array
{
    global $course,$version;
    return ['course_id' => $course,'version_id' => $version,'expected_version_revision'
        => pubRevision('course_versions', $version)];
}

function pubTransition(?int $expectedCourse = null): void
{
    global $publication,$actor,$course,$version;
    $choices = $publication->transitions($actor, $course);
    if (!$choices) {
        throw new \RuntimeException('Native workflow transition missing');
    }
    $publication->transition($actor, $course, $version, $expectedCourse
        ?? pubRevision('courses', $course), pubRevision('course_versions', $version), (int)$choices[0]->id);
}
