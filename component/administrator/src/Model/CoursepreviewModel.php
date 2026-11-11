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
    public function getPlaybackDescriptor(): array
    {
        $app = Factory::getApplication();
        $actor = CourseEditorAccess::fresh($app->getIdentity());
        $input = $app->getInput()->post;
        $courseId = $input->getInt('course_id');
        $versionId = $input->getInt('version_id');
        $lessonId = $input->getInt('lesson_id');
        $service = $app->bootComponent('com_jlms')->getCurriculumService();
        $lesson = $service->lessonForEdit($actor, $courseId, $versionId, $lessonId);
        if ($lesson->kind !== 'video' || !$lesson->source_json) {
            throw new \RuntimeException(Text::_('COM_JLMS_RESOURCE_NOT_FOUND'), 404);
        }
        $policy = \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')->get('video_external_policy', 'blocked');
        if ($lesson->provider_key !== 'html5' && !in_array($policy, ['embed', 'site_consent'], true)) {
            throw new \RuntimeException(Text::_('COM_JLMS_VIDEO_CONSENT_REQUIRED'), 403);
        }
        $source = json_decode($lesson->source_json, true, 16, JSON_THROW_ON_ERROR);
        $provider = $service->videoProvider((string) $lesson->provider_key);
        if (!$provider->getDescriptor()->configured) {
            throw new \RuntimeException(Text::_('COM_JLMS_VIDEO_UNAVAILABLE'), 503);
        }
        $descriptor = $provider->createPlaybackDescriptor(new \Jlms\Contracts\Video\VideoSource(
            (string) $lesson->provider_key, (string) $source['reference'], (int) $lesson->source_revision, $source['options'] ?? [],
        ), new \Jlms\Contracts\Video\PlaybackContext(
            (int) $actor->id, 0, (int) $lesson->id, $app->getLanguage()->getTag(), 0, '',
            new \DateTimeImmutable('now', new \DateTimeZone('UTC')), null, true, $courseId, $versionId,
        ));
        return ['provider' => $descriptor->provider, 'options' => $descriptor->playerOptions,
            'source_revision' => $descriptor->sourceRevision];
    }

    public function getPreview(): array
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
        $actor = CourseEditorAccess::fresh($app->getIdentity());
        $service = $app->bootComponent('com_jlms')->getCurriculumService();
        $courseId = $input->getInt('id');
        $versionId = $input->getInt('version_id');
        $lessonId = $input->getInt('lesson_id');
        $requestedLevels = array_map('intval', (array) $input->get('access_levels', [], 'array'));
        if ($courseId < 1 || $versionId < 1 || $lessonId < 0) {
            throw new \RuntimeException(Text::_('COM_JLMS_RESOURCE_NOT_FOUND'), 404);
        }

        try {
            $outline = $service->outline($actor, $courseId, $versionId);
            $accessPreview = $app->bootComponent('com_jlms')->getCatalogService()->accessPreview(
                $actor, $courseId, $app->getLanguage()->getTag(), $requestedLevels,
            );
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
                    'provider_key' => $record->provider_key,
                    'body' => $filter->clean((string) $record->body, 'html'),
                ];
                $lesson->preview_materials = $app->bootComponent('com_jlms')->getMaterialService()
                    ->previewLists($actor, $courseId, $versionId, $lessonId);
            }
        } catch (\DomainException $error) {
            $denied = $error->getMessage() === 'JERROR_ALERTNOAUTHOR';
            throw new \RuntimeException(Text::_($denied ? 'JERROR_ALERTNOAUTHOR' : 'COM_JLMS_RESOURCE_NOT_FOUND'), $denied ? 403 : 404);
        }

        $outline['selected'] = $lesson;
        $outline['access_preview'] = $accessPreview;

        return $outline;
    }
}
