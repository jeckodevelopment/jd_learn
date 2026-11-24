<?php

declare(strict_types=1);

use Jlms\Component\Jlms\Administrator\Application\CurriculumService as JdCurriculumService;
use Jlms\Component\Jlms\Administrator\Application\VideoProgressService as JdVideoProgressService;
use Jlms\Plugin\Jlmsvideo\Youtube\Provider\Provider as JdProvider;
use Joomla\CMS\Component\ComponentHelper as NativeComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Session\Session;
use Joomla\Session\SessionInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Jlms\Contracts\Event\RegisterVideoProvidersEvent;
use Jlms\Contracts\Video\VideoProviderRegistry;

function durationExpect(bool $ok, string $message): void
{
    global $checks;
    if (!$ok) {
        throw new RuntimeException($message);
    }
    $checks++;
}

function durationDenied(callable $work, string $key): void
{
    try {
        $work();
    } catch (\Throwable $e) {
        durationExpect($e->getMessage() === $key, 'Expected ' . $key . ', got ' . $e->getMessage());
        return;
    }throw new RuntimeException('Expected ' . $key);
}

function durationRow(string $table, int $id): object
{
    global $db;
    return $db->setQuery('SELECT * FROM #__jlms_' . $table . ' WHERE id=' . $id)->loadObject();
}

function durationState(int $course): array
{
    global $db;
    $versions = $db->setQuery('SELECT * FROM #__jlms_course_versions WHERE course_id=' . $course
        . ' ORDER BY id')->loadAssocList();
    $ids = implode(',', array_column($versions, 'id'));
    return ['course' => (array)durationRow('courses', $course),'versions' => $versions,'lessons' => $db
        ->setQuery('SELECT * FROM #__jlms_lessons WHERE course_version_id IN (' . $ids . ') ORDER BY id')
            ->loadAssocList(),'enrollments' => $db->
                setQuery('SELECT * FROM #__jlms_enrollments WHERE course_id=' . $course . ' ORDER BY id')->
                loadAssocList(),'progress' => $db->
                setQuery('SELECT p.* FROM #__jlms_lesson_progress p JOIN ' .
                    '#__jlms_enrollments e ON e.id=p.enrollment_id WHERE ' .
                    'e.course_id=' . $course . ' ORDER BY p.enrollment_id,p.lesson_id')->loadAssocList()];
}
