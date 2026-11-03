<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Model;

use Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseModel;

final class CoursepreviewModel extends BaseModel
{
    public function getPreview(): array
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
        $actor = CourseEditorAccess::fresh($app->getIdentity());
        $service = $app->bootComponent('com_jlms')->getCurriculumService();
        $courseId = $input->getInt('id');
        $versionId = $input->getInt('version_id');
        $lessonId = $input->getInt('lesson_id');
        if ($courseId < 1 || $versionId < 1 || $lessonId < 0) {
            throw new \RuntimeException(Text::_('COM_JLMS_RESOURCE_NOT_FOUND'), 404);
        }

        try {
            $outline = $service->outline($actor, $courseId, $versionId);
            $lesson = null;
            if ($lessonId) {
                $record = $service->lessonForEdit($actor, $courseId, $versionId, $lessonId);
                // The administrator may have authored HTML with permissive filters.
                // Keep text formatting without executing markup in the administrator origin.
                $filter = new InputFilter(
                    ['p', 'br', 'h2', 'h3', 'h4', 'strong', 'em', 'b', 'i', 'ul', 'ol', 'li', 'blockquote', 'pre', 'code', 'table', 'thead', 'tbody', 'tr', 'th', 'td'],
                    [],
                );
                $lesson = (object) [
                    'id' => (int) $record->id,
                    'title' => $record->title,
                    'kind' => $record->kind,
                    'body' => $filter->clean((string) $record->body, 'html'),
                ];
            }
        } catch (\DomainException $error) {
            $denied = $error->getMessage() === 'JERROR_ALERTNOAUTHOR';
            throw new \RuntimeException(Text::_($denied ? 'JERROR_ALERTNOAUTHOR' : 'COM_JLMS_RESOURCE_NOT_FOUND'), $denied ? 403 : 404);
        }

        $outline['selected'] = $lesson;

        return $outline;
    }
}
