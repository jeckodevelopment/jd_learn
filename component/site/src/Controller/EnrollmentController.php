<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Controller;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Log\Log;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;

class EnrollmentController extends BaseController
{
    public function execute($task)
    {
        $allowed = static::class === self::class ? ['start', 'resume'] : ['complete', 'begin'];
        if (!in_array(strtolower($task), $allowed, true)) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        return parent::execute($task);
    }

    protected function mutate(bool $completion): void
    {
        $this->app->setHeader('Cache-Control', 'private, no-store', true);
        if (strtoupper($this->input->getMethod()) !== 'POST') {
            throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'), 405);
        }
        if (!$this->checkToken('post', false)) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN_NOTICE'), 403);
        }
        $data = $this->input->post->get('jform', [], 'array');
        try {
            $service = $this->app->bootComponent('com_jlms')->getLearningService();
            $key = $data['idempotency_key'] ?? null;
            if (!is_string($key)) {
                throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
            }
            $language = $this->app->getLanguage()->getTag();
            $lessonId = 0;
            if ($completion) {
                $id = CurriculumService::id($data['enrollment_id'] ?? null);
                $lessonId = CurriculumService::id($data['lesson_id'] ?? null);
                $service->complete($this->app->getIdentity(), $language, $id, $lessonId, $key);
            } else {
                $id = $service->start($this->app->getIdentity(), $language, CurriculumService::id($data['course_id'] ?? null), $key);
            }
            $this->setRedirect(Route::_('index.php?option=com_jlms&view=classroom&id=' . $id . '&lesson_id=' . $lessonId, false));
        } catch (\Throwable $error) {
            $known = ['COM_JLMS_LOGIN_REQUIRED', 'COM_JLMS_LEARNING_DENIED', 'COM_JLMS_LEARNING_WINDOW',
                'COM_JLMS_RESOURCE_NOT_FOUND', 'COM_JLMS_INVALID_INPUT', 'COM_JLMS_IDEMPOTENCY_CONFLICT',
                'COM_JLMS_ENROLLMENT_MODE_PENDING', 'COM_JLMS_EDITION_FULL', 'JERROR_LAYOUT_PAGE_NOT_FOUND'];
            if (in_array($error->getMessage(), $known, true)) {
                $code = $error->getCode();
                throw new \RuntimeException(Text::_($error->getMessage()), in_array($code, [400, 401, 403, 404, 409], true) ? $code : 400);
            }
            Log::add('Learning mutation failed; correlation=' . bin2hex(random_bytes(8)), Log::ERROR, 'com_jlms');
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
    }

    public function start(): void
    {
        $this->mutate(false);
    }

    public function resume(): void
    {
        $this->app->setHeader('Cache-Control', 'private, no-store', true);
        $this->app->setHeader('X-Robots-Tag', 'noindex, nofollow', true);
        if (strtoupper($this->input->getMethod()) !== 'GET') { throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'), 405); }
        try {
            $id = CurriculumService::id($this->input->get('id', 0, 'raw'));
            $target = $this->app->bootComponent('com_jlms')->getLearningService()->resumeTarget($this->app->getIdentity(), $this->app->getLanguage()->getTag(), $id);
            $this->setRedirect(Route::_('index.php?option=com_jlms&view=classroom&id=' . $target['enrollment_id'] . '&lesson_id=' . $target['lesson_id'] . '&lang=' . rawurlencode($target['language']), false));
        } catch (\Throwable $error) {
            $known = ['COM_JLMS_LOGIN_REQUIRED', 'COM_JLMS_LEARNING_DENIED', 'COM_JLMS_LEARNING_WINDOW', 'COM_JLMS_RESOURCE_NOT_FOUND', 'COM_JLMS_INVALID_INPUT', 'JERROR_LAYOUT_PAGE_NOT_FOUND'];
            if (in_array($error->getMessage(), $known, true)) {
                throw new \RuntimeException(Text::_($error->getMessage()), in_array($error->getCode(), [400, 401, 403, 404], true) ? $error->getCode() : 400);
            }
            Log::add('Resume target failed; correlation=' . bin2hex(random_bytes(8)), Log::ERROR, 'com_jlms');
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
    }
}
