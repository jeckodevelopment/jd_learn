<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Controller;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Router\Route;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;

class CurriculumController extends BaseController
{
    protected function perform(string $operation, string $kind = ''): void
    {
        if (strtoupper($this->input->getMethod()) !== 'POST') {
            throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'), 405);
        }
        if (!$this->checkToken('post', false)) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }
        $input = $this->input->post->get('jform', [], 'array');
        $courseId = 0;
        $versionId = 0;
        $resultId = 0; $failed = false;
        try {
            $courseId = CurriculumService::id($input['course_id'] ?? null);
            $versionId = CurriculumService::id($input['version_id'] ?? 0, true);
            $service = $this->app->bootComponent('com_jlms')->getCurriculumService();
            $actor = $this->app->getIdentity();
            switch ($operation) {
                case 'transition':
                    $publication = $this->app->bootComponent('com_jlms')->getPublicationService();
                    $transition = CurriculumService::id($input['transition_id'] ?? null);
                    $impact = $publication->updateImpact($actor, $courseId, $versionId, $transition);
                    $fingerprint = null;
                    if ($impact !== null) {
                        $key = 'com_jlms.publication.review.' . $courseId . '.' . $versionId;
                        $ticket = $input['review_ticket'] ?? '';
                        if ($ticket === '' && !isset($input['publication_confirmation'])) {
                            if ((int)$impact['course']->revision !== CurriculumService::id($input['revision'] ?? null)
                                || (int)$impact['version']->revision !== CurriculumService::id($input['expected_version_revision'] ?? null)) { throw new \DomainException('COM_JLMS_REVISION_CONFLICT'); }
                            $this->app->setUserState($key, ['actor'=>(int)$actor->id, 'transition'=>$transition, 'expires'=>time()+900,
                                'ticket'=>bin2hex(random_bytes(32)), 'fingerprint'=>$impact['fingerprint']]);
                            $this->setRedirect(Route::link('administrator', 'index.php?option=com_jlms&view=publicationreview&id=' . $courseId . '&version_id=' . $versionId, false));
                            return;
                        }
                        $plan = $this->app->getUserState($key);
                        if (!is_array($plan) || (int)$plan['actor'] !== (int)$actor->id || (int)$plan['transition'] !== $transition
                            || (int)$plan['expires'] < time() || !is_string($ticket) || !hash_equals($plan['ticket'], $ticket)
                            || ($input['publication_confirmation'] ?? '') !== '1') { throw new \DomainException('COM_JLMS_PUBLICATION_REVIEW_REQUIRED'); }
                        $fingerprint = $plan['fingerprint'];
                        $this->app->setUserState($key, null);
                    }
                    $publication->transition($actor, $courseId, $versionId, CurriculumService::id($input['revision'] ?? null),
                        CurriculumService::id($input['expected_version_revision'] ?? null), $transition, $fingerprint);
                    break;
                case 'restore':
                    $versionId = $service->restoreVersion($actor, $input);
                    break;
                case 'version':
                    $versionId = $service->createDraft($actor, $courseId, CurriculumService::id($input['revision'] ?? null));
                    break;
                case 'duplicatecourse':
                    $model = $this->app->bootComponent('com_jlms')->getMVCFactory()->createModel('Course', 'Administrator', ['ignore_request' => true]);
                    $courseId = $model->duplicate($input);
                    $versionId = 0;
                    break;
                case 'duplicate':
                    $resultId = $service->duplicateItem($actor, $kind, $input);
                    break;
                case 'move':
                    $resultId = $service->moveLesson($actor, $input);
                    break;
                case 'save':
                    $resultId = $kind === 'module' ? $service->saveModule($actor, $input) : $service->saveLesson($actor, $input);
                    break;
                case 'delete':
                    $service->deleteItem($actor, $kind, $input);
                    break;
                case 'reorder':
                    $service->reorder($actor, $kind, $input);
                    break;
                default:
                    throw new \InvalidArgumentException('COM_JLMS_INVALID_INPUT');
            }
            $this->setMessage(Text::_($operation === 'restore' ? 'COM_JLMS_PROGRAMME_RESTORE_SAVED' : 'COM_JLMS_CURRICULUM_SAVED'));
        } catch (\DomainException | \InvalidArgumentException $error) {
            $failed = true;
            $key = match ($error->getMessage()) {
                'SOURCE_INVALID' => 'COM_JLMS_SOURCE_INVALID',
                'PROVIDER_UNAVAILABLE' => 'COM_JLMS_VIDEO_EDITOR_PLUGIN_MISSING',
                default => $error->getMessage(),
            };
            $known = ['COM_JLMS_PROGRAMME_RESTORE_CONFIRM_REQUIRED', 'COM_JLMS_PROGRAMME_RESTORE_SOURCE_INVALID', 'COM_JLMS_PUBLICATION_REVIEW_REQUIRED', 'COM_JLMS_PUBLICATION_REVIEW_STALE', 'JERROR_ALERTNOAUTHOR', 'COM_JLMS_RESOURCE_NOT_FOUND', 'COM_JLMS_VERSION_IMMUTABLE',
                'COM_JLMS_REVISION_CONFLICT', 'COM_JLMS_INVALID_INPUT', 'COM_JLMS_INVALID_COURSE',
                'COM_JLMS_VIDEO_EDITOR_PLUGIN_MISSING', 'COM_JLMS_VIDEO_EDITOR_POLICY_BLOCKED',
                'COM_JLMS_VIDEO_EDITOR_CONFIG_REQUIRED', 'COM_JLMS_VIDEO_EDITOR_DURATION_REQUIRED', 'COM_JLMS_OUTLINE_LIMIT', 'COM_JLMS_VERSION_COPY_NOT_AVAILABLE', 'COM_JLMS_SOURCE_INVALID',
                'COM_JLMS_LESSON_KIND_NOT_AVAILABLE', 'COM_JLMS_MOVE_MODULE_NOT_AVAILABLE', 'COM_JLMS_ITEM_HAS_DEPENDENCIES', 'COM_JLMS_COURSE_DESCRIPTION_REQUIRED', 'COM_JLMS_PUBLICATION_EMPTY', 'COM_JLMS_PUBLICATION_UNSUPPORTED', 'COM_JLMS_PUBLICATION_PLUGIN_REQUIRED', 'COM_JLMS_WORKFLOW_REJECTED', 'COM_JLMS_MATERIAL_NOT_READY', 'COM_JLMS_TEACHER_INVALID', 'COM_JLMS_COURSE_IMAGE_INVALID', 'COM_JLMS_COURSE_IMAGE_ALT_REQUIRED', 'COM_JLMS_CALENDAR_INVALID', 'COM_JLMS_CALENDAR_RANGE', 'COM_JLMS_CALENDAR_DST'];
            $this->setMessage(Text::_(in_array($key, $known, true) ? $key : 'COM_JLMS_INVALID_INPUT'), 'error');
        } catch (\Throwable $error) {
            $failed = true;
            $videoErrors = [
                'COM_JLMS_VIDEO_CONFIGURATION', 'COM_JLMS_VIDEO_PROVIDER_UNAVAILABLE',
                'COM_JLMS_VIDEO_CREDENTIAL_REJECTED', 'COM_JLMS_VIDEO_REMOTE_NOT_FOUND',
                'COM_JLMS_VIDEO_PROVIDER_BUSY', 'COM_JLMS_VIDEO_REMOTE_UNPROTECTED',
                'COM_JLMS_VIDEO_REMOTE_NOT_READY', 'COM_JLMS_VIDEO_ORIGIN_REJECTED',
            ];
            if ($operation === 'transition' && $error instanceof \RuntimeException && in_array($error->getMessage(), $videoErrors, true)) {
                $this->setMessage(Text::_($error->getMessage()), 'error');
            } else {
                Log::add('Curriculum mutation failed; correlation=' . bin2hex(random_bytes(8)), Log::ERROR, 'com_jlms');
                $this->setMessage(Text::_('COM_JLMS_SAVE_FAILED'), 'error');
            }
        }
        $target = $courseId ? 'index.php?option=com_jlms&view=curriculum&id=' . $courseId . '&version_id=' . $versionId
            : 'index.php?option=com_jlms&view=courses';
        if ($failed && $operation === 'save' && $courseId && $versionId) {
            $this->app->setUserState('com_jlms.curriculum.' . $courseId . '.' . $versionId . '.' . $kind, $input);
            $target .= $kind === 'module' ? '&edit_module=' . (int) ($input['id'] ?? 0) . '&new_module=1' : '&edit_lesson=' . (int) ($input['id'] ?? 0) . '&new_lesson=1&module_id=' . (int) ($input['module_id'] ?? 0);
        } elseif (!$failed && $operation === 'save') {
            $this->app->setUserState('com_jlms.curriculum.' . $courseId . '.' . $versionId . '.' . $kind, null);
            $target .= '&module_id=' . ($kind === 'module' ? $resultId : (int) ($input['module_id'] ?? 0)) . '#jlms-' . $kind . '-' . $resultId;
        }
        if (!$failed && in_array($operation, ['move', 'duplicate'], true)) {
            $target .= '&module_id=' . ($kind === 'module' ? $resultId : (int) ($input['module_id'] ?? 0)) . '#jlms-' . ($kind ?: 'lesson') . '-' . $resultId;
        }
        $this->setRedirect(Route::link('administrator', $target, false));
    }

    public function execute($task)
    {
        if (static::class === self::class && !in_array(strtolower($task), ['createversion', 'restoreversion', 'transition', 'duplicatecourse', 'saveorder'], true)) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        return parent::execute($task);
    }

    public function saveOrder(): void
    {
        $code = 200; $error = false; $data = null; $message = '';
        try {
            if (strtoupper($this->input->getMethod()) !== 'POST') { throw new \RuntimeException('COM_JLMS_METHOD_NOT_ALLOWED', 405); }
            if (!$this->checkToken('post', false)) { throw new \RuntimeException('JINVALID_TOKEN', 403); }
            $input = $this->input->post->get('jform', [], 'array');
            $data = ['revision' => $this->app->bootComponent('com_jlms')->getCurriculumService()->saveOrder($this->app->getIdentity(), $input)];
        } catch (\Throwable $exception) {
            $error = true;
            $key = $exception->getMessage();
            $code = match ($key) {
                'JERROR_ALERTNOAUTHOR', 'JINVALID_TOKEN' => 403,
                'COM_JLMS_RESOURCE_NOT_FOUND' => 404,
                'COM_JLMS_REVISION_CONFLICT', 'COM_JLMS_VERSION_IMMUTABLE' => 409,
                'COM_JLMS_INVALID_INPUT', 'COM_JLMS_OUTLINE_LIMIT' => 422,
                'COM_JLMS_METHOD_NOT_ALLOWED' => 405,
                default => 500,
            };
            $message = Text::_($code === 500 ? 'COM_JLMS_SAVE_FAILED' : $key);
        }
        $this->app->setHeader('status', (string) $code, true);
        http_response_code($code);
        $this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $this->app->setHeader('Cache-Control', 'private, no-store', true);
        echo new \Joomla\CMS\Response\JsonResponse($data, $message, $error, true);
        $this->app->close();
    }

    public function restoreVersion(): void { $this->perform('restore'); }

    public function duplicateCourse(): void { $this->perform('duplicatecourse'); }

    public function transition(): void
    {
        $this->perform('transition');
    }

    public function createVersion(): void
    {
        $this->perform('version');
    }
}
