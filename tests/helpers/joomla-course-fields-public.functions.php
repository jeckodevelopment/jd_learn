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

function publicFieldsExpect(bool $ok, string $message): void
{
    global $checks;
    if (!$ok) {
        throw new RuntimeException($message);
    } $checks++;
}

function publicFieldsState(array $f): array
{
    global $db;
    $c = (int)$f['course'];
    $v = (int)$f['version'];
    $e = (int)$f['enrollment'];
    return ['teaching' => [
    'version' => $db->setQuery('SELECT * FROM #__jlms_course_versions WHERE id=' . $v)->loadAssoc(),
    'modules' => $db->setQuery('SELECT * FROM #__jlms_modules WHERE course_version_id=' . $v . ' ORDER BY id')
        ->loadAssocList(),
    'lessons' => $db->setQuery('SELECT * FROM #__jlms_lessons WHERE course_version_id=' . $v . ' ORDER BY id')
        ->loadAssocList(),
    'enrollment' => $db->setQuery('SELECT * FROM #__jlms_enrollments WHERE id=' . $e)->loadAssoc(),
    'progress' => $db->setQuery('SELECT * FROM #__jlms_lesson_progress WHERE enrollment_id=' . $e
        . ' ORDER BY lesson_id')->loadAssocList()],
    'course' => $db->setQuery('SELECT * FROM #__jlms_courses WHERE id=' . $c)->loadAssoc(),
    'values' => $db->setQuery('SELECT field_id,value FROM #__fields_values WHERE item_id=' . $db
        ->quote((string)$c) . ' AND field_id IN (' . implode(
            ',',
            array_map('intval', array_values($f['fields']))
        ) . ') ORDER BY field_id,value')->loadAssocList(),
    'groups' => $db->setQuery('SELECT * FROM #__fields_groups WHERE id=' . (int)$f['group'])->loadAssocList(),
    'fields' => $db->setQuery('SELECT * FROM #__fields WHERE id IN ('
        . implode(',', array_map('intval', array_values($f['fields']))) . ') ORDER BY id')->loadAssocList()];
}
