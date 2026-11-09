<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;

/** Session-authenticated, non-credit audio resume endpoint. */
final class AudioController extends BaseController
{
    public function execute($task)
    {
        if (strtolower($task) !== 'save') { throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404); }
        return parent::execute($task);
    }

    public function save(): void
    {
        $this->app->setHeader('Cache-Control', 'private, no-store', true);
        $this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        try {
            if (strtoupper($this->input->getMethod()) !== 'POST') { throw new \RuntimeException('COM_JLMS_METHOD_NOT_ALLOWED', 405); }
            if ((int) $this->input->server->get('CONTENT_LENGTH', 0, 'UINT') > 4096) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 413); }
            if (!$this->checkToken('post', false)) { throw new \RuntimeException('JINVALID_TOKEN', 403); }
            $enrollment = CurriculumService::id($this->input->post->get('enrollment_id', null, 'RAW'));
            $lesson = CurriculumService::id($this->input->post->get('lesson_id', null, 'RAW'));
            $position = CurriculumService::id($this->input->post->get('position_ms', null, 'RAW'), true);
            $saved = $this->app->bootComponent('com_jlms')->getLearningService()->saveAudioResume(
                $this->app->getIdentity(), $this->app->getLanguage()->getTag(), $enrollment, $lesson, $position);
            echo new JsonResponse(['resume_ms' => $saved], null, false, true);
        } catch (\Throwable $error) {
            $code = (int) $error->getCode(); $key = $error->getMessage();
            if ($error instanceof \InvalidArgumentException) { $key = 'COM_JLMS_INVALID_INPUT'; $code = 400; }
            if (!in_array($code, [400, 401, 403, 404, 405, 409, 413], true)
                || (!preg_match('/\ACOM_JLMS_[A-Z_]+\z/D', $key) && $key !== 'JINVALID_TOKEN')) {
                Log::add('Audio progress save failed; correlation=' . bin2hex(random_bytes(8)), Log::ERROR, 'com_jlms');
                $key = 'COM_JLMS_SAVE_FAILED'; $code = 500;
            }
            $this->app->setHeader('status', (string) $code, true);
            http_response_code($code);
            echo new JsonResponse(['code' => $key], Text::_($key), true, true);
        }
        $this->app->close();
    }
}
