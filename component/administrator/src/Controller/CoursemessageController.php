<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;
use Jlms\Component\Jlms\Administrator\Application\CourseMessageVariableException;

final class CoursemessageController extends BaseController
{
    public function execute($task)
    {
        if (!in_array(strtolower((string)$task), ['preview','test','queue'], true)) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        return parent::execute($task);
    }
    private function read(): array
    {
        if (strtoupper($this->input->getMethod()) !== 'POST') {
            throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'), 405);
        }
        if (!$this->checkToken('post', false)) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN_NOTICE'), 403);
        }
        if ((int)$this->input->server->get('CONTENT_LENGTH', 0, 'UINT') > 65536) {
            throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 413);
        }
        $raw = $this->input->post->get('jform', [], 'array');
        // Only the HTML template copy is read as raw input. The composition
        // service sanitizes it with Joomla SafeHTML before hashing/queueing.
        $unfiltered = $this->input->post->get('jform', [], 'raw');
        if (is_array($unfiltered) && isset($unfiltered['template'])) {
            $raw['template'] = $unfiltered['template'];
        }
        if (!is_array($raw['template'] ?? null)) {
            throw new \RuntimeException('COM_JLMS_COMMUNICATION_TEMPLATE_INVALID', 422);
        }
        if (
            !is_string($raw['course_id'] ?? null)
            || !is_string($raw['template']['subject'] ?? null)
            || !is_string($raw['template']['htmlbody'] ?? null)
        ) {
            throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
        }
        // HTML is the only body editor; generate the plain-text alternative
        // from sanitized HTML for both preview and final message.
        $raw['template'] = [
            'subject' => $raw['template']['subject'],
            'htmlbody' => $raw['template']['htmlbody'],
            'body' => \Jlms\Component\Jlms\Administrator\Application\CourseMessageTemplate::plainTextFromHtml($raw['template']['htmlbody']),
        ];
        $raw['subject'] = $raw['template']['subject'];
        $raw['body'] = $raw['template']['body'];
        $ids = $raw['enrollment_ids'] ?? [];
        if (!is_array($ids)) {
            throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
        }
        return [$raw,CurriculumService::id($raw['course_id']),$ids];
    }
    private function redirectTo(int $course): void
    {
        $this->setRedirect(Route::link('administrator', 'index.php?option=com_jlms&view=coursemessage&id='
            . $course, false));
    }
    public function preview(): void
    {
        try {
            [$raw,$course,$ids] = $this->read();
            $service = $this->app->bootComponent('com_jlms')->getCourseCommunicationService();
            $plan = $service->preview(
                $this->app->getIdentity(),
                $course,
                $raw['subject'],
                $raw['body'],
                $ids,
                $raw['template'] ?? []
            );
            $key = 'com_jlms.coursemessage.' . (int)$this->app->getIdentity()->id . '.' . $course;
            $this->app->getSession()->set($key, ['subject' => $plan['subject'],'body'
                => $plan['body'],'template' => $plan['template'],'enrollment_ids' => $plan['enrollment_ids'],
                // An explicit new preview begins a new idempotency window. Reloads do not.
                'mutation_id' => \Jlms\Component\Jlms\Administrator\Application\CourseMessageMutation::create()]);
            $this->setMessage(Text::sprintf('COM_JLMS_COMMUNICATION_PREVIEW_COUNT', $plan['count']));
            $this->redirectTo($course);
        } catch (\Throwable $error) {
            $this->fail($error);
        }
    }
    public function test(): void
    {
        try {
            [$raw,$course] = $this->read();
            if (!is_string($raw['mutation_id'] ?? null)) {
                throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
            }
            $this->app->bootComponent('com_jlms')->getCourseCommunicationService()->test($this->app
                ->getIdentity(), $course, $raw['subject'], $raw['body'], $raw['mutation_id'], $raw['template'] ?? []);
            $this->setMessage(Text::_('COM_JLMS_COMMUNICATION_TEST_QUEUED'));
            $this->redirectTo($course);
        } catch (\Throwable $error) {
            $this->fail($error);
        }
    }
    public function queue(): void
    {
        try {
            [$raw,$course,$ids] = $this->read();
            foreach (['impact_hash','mutation_id','confirmation'] as $key) {
                if (!is_string($raw[$key] ?? null)) {
                    throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
                }
            }
            if ($raw['confirmation'] !== '1') {
                throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
            }
            $job = $this->app->bootComponent('com_jlms')->getCourseCommunicationService()->queue(
                $this->app
                ->getIdentity(),
                $course,
                $raw['subject'],
                $raw['body'],
                $ids,
                $raw['impact_hash'],
                $raw['mutation_id'],
                $raw['template'] ?? []
            );
            $key = 'com_jlms.coursemessage.' . (int)$this->app->getIdentity()->id . '.' . $course;
            $this->app->getSession()->clear($key);
            $this->setMessage(Text::sprintf('COM_JLMS_COMMUNICATION_JOB_QUEUED', $job));
            $this->redirectTo($course);
        } catch (\Throwable $error) {
            $this->fail($error);
        }
    }
    private function fail(\Throwable $error): never
    {
        if ($error instanceof CourseMessageVariableException) {
            throw new \RuntimeException($error->translatedMessage(), 422);
        }
        $key = $error->getMessage();
        $code = (int)$error->getCode();
        if ($error instanceof \InvalidArgumentException) {
            $key = 'COM_JLMS_INVALID_INPUT';
            $code = 400;
        }
        if (in_array($code, [400,401,403,404,409,413,422], true) && str_starts_with($key, 'COM_JLMS_')) {
            throw new \RuntimeException(Text::_($key), $code);
        }
        \Joomla\CMS\Log\Log::add('Course communication failed; correlation='
            . bin2hex(random_bytes(8)), \Joomla\CMS\Log\Log::ERROR, 'com_jlms');
        throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
    }
}
