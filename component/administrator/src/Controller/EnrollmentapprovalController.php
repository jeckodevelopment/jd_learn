<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;

final class EnrollmentapprovalController extends BaseController
{
    public function execute($task)
    {
        if (strtolower($task) !== 'decide') {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        return parent::execute($task);
    }

    public function decide(): void
    {
        $this->app->setHeader('Cache-Control', 'private, no-store', true);
        if (strtoupper($this->input->getMethod()) !== 'POST') {
            throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'), 405);
        }
        if (!$this->checkToken('post', false)) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN_NOTICE'), 403);
        }
        try {
            if ((int) $this->input->server->get('CONTENT_LENGTH', 0, 'UINT') > 8192) {
                throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 413);
            }
            $raw = $this->input->post->get('jform', [], 'array');
            foreach (['course_id','request_id','revision','decision','reason','confirmation'] as $field) {
                if (!is_string($raw[$field] ?? null)) {
                    throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
                }
            }
            if ($raw['confirmation'] !== '1') {
                throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
            }
            $courseId = CurriculumService::id($raw['course_id']);
            $requestId = CurriculumService::id($raw['request_id']);
            $revision = CurriculumService::id($raw['revision']);
            $this->app->bootComponent('com_jlms')->getEnrollmentApprovalService()->decide(
                $this->app->getIdentity(), $courseId, $requestId, $revision,
                $raw['decision'], $raw['reason'],
            );
            $this->setMessage(Text::_('COM_JLMS_ENROLLMENT_APPROVAL_SAVED'));
            $this->setRedirect(Route::link('administrator', 'index.php?option=com_jlms&view=enrollments&id=' . $courseId, false));
        } catch (\Throwable $error) {
            $key = $error->getMessage();
            $code = (int) $error->getCode();
            if ($error instanceof \InvalidArgumentException) {
                $key = 'COM_JLMS_INVALID_INPUT';
                $code = 400;
            }
            $known = ['COM_JLMS_INVALID_INPUT', 'COM_JLMS_LOGIN_REQUIRED', 'COM_JLMS_LEARNING_DENIED',
                'COM_JLMS_RESOURCE_NOT_FOUND', 'COM_JLMS_ENROLLMENT_APPROVAL_REASON',
                'COM_JLMS_ENROLLMENT_APPROVAL_CONFLICT', 'COM_JLMS_ENROLLMENT_APPROVAL_AUDIT_FAILED', 'COM_JLMS_LEARNING_WINDOW', 'COM_JLMS_EDITION_FULL'];
            if (in_array($code, [400,401,403,404,409,413,422,503], true) && in_array($key, $known, true)) {
                throw new \RuntimeException(Text::_($key), $code);
            }
            Log::add('Enrollment decision failed; correlation=' . bin2hex(random_bytes(8)), Log::ERROR, 'com_jlms');
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
    }
}
