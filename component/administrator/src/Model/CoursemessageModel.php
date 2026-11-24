<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;
use Jlms\Component\Jlms\Administrator\Application\CourseMessageVariableException;

final class CoursemessageModel extends BaseDatabaseModel
{
    public function getItem(): array
    {
        try {
            $app = Factory::getApplication();
            $courseId = CurriculumService::id($app->getInput()->get('id', 0, 'raw'));
            $data = $app->bootComponent('com_jlms')->getCourseCommunicationService()->editor($app->
                getIdentity(), $courseId);
            $key = 'com_jlms.coursemessage.' . (int)$app->getIdentity()->id . '.' . $courseId;
            $preview = $app->getSession()->get($key, null);
            $data['template'] = \Jlms\Component\Jlms\Administrator\Application\CourseMessageTemplate::forComposition((string) $app->getLanguage()->getTag());
            if (is_array($preview)) {
                // Preserve the same mutation across GET refreshes; migrate older
                // sessions once, without creating a second test event on reload.
                if (!\Jlms\Component\Jlms\Administrator\Application\CourseMessageMutation::valid($preview['mutation_id'] ?? null)) {
                    $preview['mutation_id'] =
                        \Jlms\Component\Jlms\Administrator\Application\CourseMessageMutation::create();
                    $app->getSession()->set($key, $preview);
                }
                $data['preview'] = $app->bootComponent('com_jlms')->getCourseCommunicationService()->preview($app->getIdentity(), $courseId, (string)$preview['subject'], (string)$preview['body'], $preview['enrollment_ids'], $preview['template'] ?? []);
                $data['preview']['mutation_id'] = $preview['mutation_id'];
                $data['template'] = $data['preview']['template'];
            }
            $data['preview_key'] = $key;
            return $data;
        } catch (\Throwable $error) {
            if ($error instanceof CourseMessageVariableException) {
                throw new \RuntimeException($error->translatedMessage(), 422);
            }
            if ($error instanceof \InvalidArgumentException) {
                throw new \RuntimeException(Text::_('COM_JLMS_INVALID_INPUT'), 400);
            }
            if (in_array($error->getCode(), [400,401,403,404,409,422], true) && str_starts_with($error->getMessage(), 'COM_JLMS_')) {
                throw new \RuntimeException(Text::_($error->getMessage()), $error->getCode());
            }
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
    }
}
