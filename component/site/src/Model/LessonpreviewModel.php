<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\User\User;

final class LessonpreviewModel extends ItemModel
{
    public function getItem($pk = null): array
    {
        $app = Factory::getApplication();
        $actor = new User((int) $app->getIdentity()->id);
        $actor->clearAccessRights();
        $app->loadIdentity($actor);
        $courseId = $app->getInput()->getInt('course_id', 0);
        $lessonId = $app->getInput()->getInt('lesson_id', 0);
        if ($courseId < 1 || $lessonId < 1) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        $service = $app->bootComponent('com_jlms')->getCatalogService();
        $course = $service->course($actor, $app->getLanguage()->getTag(), $courseId);
        $lesson = $service->publicLessonPreview($actor, $app->getLanguage()->getTag(), $courseId, $lessonId);
        return ['course' => $course, 'lesson' => $lesson];
    }
}
