<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Response\JsonResponse;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;

final class LessonController extends EnrollmentController
{
    public function complete(): void
    {
        $this->mutate(true);
    }

    /** Record the start of viewing a lesson without crediting completion. */
    public function begin(): void
    {
        $this->app->setHeader('Cache-Control', 'private, no-store', true);
        $this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        try {
            if (strtoupper($this->input->getMethod()) !== 'POST') { throw new \RuntimeException('COM_JLMS_METHOD_NOT_ALLOWED', 405); }
            if ((int) $this->input->server->get('CONTENT_LENGTH', 0, 'UINT') > 4096) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 413); }
            if (!$this->checkToken('post', false)) { throw new \RuntimeException('JINVALID_TOKEN', 403); }
            $enrollmentId = CurriculumService::id($this->input->post->get('enrollment_id', null, 'RAW'));
            $lessonId = CurriculumService::id($this->input->post->get('lesson_id', null, 'RAW'));
            $state = $this->app->bootComponent('com_jlms')->getLearningService()->beginLesson(
                $this->app->getIdentity(), $this->app->getLanguage()->getTag(), $enrollmentId, $lessonId);
            echo new JsonResponse(['state' => $state], null, false, true);
        } catch (\Throwable $error) {
            $code = (int) $error->getCode(); $key = $error->getMessage();
            if ($error instanceof \InvalidArgumentException) { $key = 'COM_JLMS_INVALID_INPUT'; $code = 400; }
            $known = ['COM_JLMS_LOGIN_REQUIRED', 'COM_JLMS_LEARNING_DENIED', 'COM_JLMS_RESOURCE_NOT_FOUND',
                'COM_JLMS_LESSON_NOT_YET_AVAILABLE', 'COM_JLMS_LESSON_CLOSED', 'COM_JLMS_PREREQUISITES_INCOMPLETE',
                'COM_JLMS_INVALID_INPUT', 'COM_JLMS_METHOD_NOT_ALLOWED', 'JINVALID_TOKEN'];
            if (!in_array($key, $known, true) || !in_array($code, [400, 401, 403, 404, 405, 409, 413], true)) {
                Log::add('Lesson start failed; correlation=' . bin2hex(random_bytes(8)), Log::ERROR, 'com_jlms');
                $key = 'COM_JLMS_SAVE_FAILED'; $code = 500;
            }
            $this->app->setHeader('status', (string) $code, true);
            http_response_code($code);
            echo new JsonResponse(['code' => $key], Text::_($key), true, true);
        }
        $this->app->close();
    }
}
