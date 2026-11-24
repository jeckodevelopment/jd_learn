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

function impactExpect(bool $ok, string $message): void
{
    global $checks;
    if (!$ok) {
        throw new RuntimeException($message);
    } $checks++;
}

function impactDenied(callable $work, string $message): void
{
    try {
        $work();
    } catch (\DomainException $e) {
        impactExpect($e->getMessage() === $message, 'Expected ' . $message . ', got ' . $e->getMessage());
        return;
    } throw new RuntimeException('Expected rejection: ' . $message);
}

function impactRow(string $table, int $id): object
{
    global $db;
    return $db->setQuery('SELECT * FROM #__jlms_' . $table . ' WHERE id=' . $id)->loadObject();
}

function impactLearning(): array
{
    global $db;
    return [$db->setQuery('SELECT * FROM #__jlms_enrollments ORDER BY id')->loadAssocList(),$db
        ->setQuery('SELECT * FROM #__jlms_attempts ORDER BY id')->loadAssocList(),$db
            ->setQuery('SELECT * FROM #__jlms_lesson_progress ORDER BY enrollment_id,lesson_id')->loadAssocList()];
}
