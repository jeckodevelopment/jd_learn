<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Controller;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Log\Log;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;
use Jlms\Component\Jlms\Administrator\Infrastructure\MaterialDownload;

final class MaterialController extends BaseController
{
    public function execute($task)
    {
        if (!in_array(strtolower($task), ['upload', 'reuse', 'save', 'delete', 'scan', 'download'], true)) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        return parent::execute($task);
    }

    private function perform(string $operation): void
    {
        if (strtoupper($this->input->getMethod()) !== 'POST') { throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'), 405); }
        $postLimit = (string) ini_get('post_max_size');
        $factor = match (strtolower(substr($postLimit, -1))) { 'g' => 1073741824, 'm' => 1048576, 'k' => 1024, default => 1 };
        $postBytes = (int) ((float) $postLimit * $factor);
        if ($postBytes > 0 && $this->input->server->getInt('CONTENT_LENGTH', 0) > $postBytes) {
            throw new \RuntimeException(Text::_('COM_JLMS_FILE_SIZE'), 413);
        }
        if (!$this->checkToken('post', false)) { throw new \RuntimeException(Text::_('JINVALID_TOKEN_NOTICE'), 403); }
        try {
            $data = $this->input->post->get('jform', [], 'array');
            $courseId = CurriculumService::id($data['course_id'] ?? null);
            $versionId = CurriculumService::id($data['version_id'] ?? null);
            $revision = CurriculumService::id($data['expected_version_revision'] ?? null);
            $service = $this->app->bootComponent('com_jlms')->getMaterialService();
            if ($operation === 'reuse') {
                $service->reuse($this->app->getIdentity(), $courseId, $versionId, $revision,
                    CurriculumService::id($data['id'] ?? null), CurriculumService::id($data['target_lesson_id'] ?? 0, true));
            } elseif ($operation === 'upload') {
                if (!is_string($data['title'] ?? null) || !is_string($data['description'] ?? '')) {
                    throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
                }
                $files = $this->input->files->get('jform', [], 'array');
                $service->upload($this->app->getIdentity(), $courseId, $versionId,
                    CurriculumService::id($data['lesson_id'] ?? 0, true), $revision, $data['title'], $data['description'] ?? '',
                    $files['upload'] ?? [], CurriculumService::id($data['replace_id'] ?? 0, true),
                    $data['publish_up'] ?? null, $data['publish_down'] ?? null);
            } else {
                $service->mutate($this->app->getIdentity(), $courseId, $versionId,
                    CurriculumService::id($data['id'] ?? null), $revision, $operation, $data);
            }
            $this->setRedirect(Route::link('administrator', 'index.php?option=com_jlms&view=materials&id=' . $courseId . '&version_id=' . $versionId, false));
        } catch (\Throwable $error) { $this->failure($error); }
    }

    private function failure(\Throwable $error): never
    {
        $known = ['COM_JLMS_FILE_INVALID', 'COM_JLMS_FILE_SIZE', 'COM_JLMS_STORAGE_UNAVAILABLE', 'COM_JLMS_DELIVERY_DISABLED',
            'COM_JLMS_RESOURCE_NOT_FOUND', 'COM_JLMS_LEARNING_DENIED', 'COM_JLMS_LOGIN_REQUIRED', 'COM_JLMS_INVALID_INPUT',
            'COM_JLMS_MATERIAL_NOT_READY', 'COM_JLMS_MATERIAL_ALREADY_LINKED', 'COM_JLMS_VERSION_IMMUTABLE', 'COM_JLMS_REVISION_CONFLICT', 'COM_JLMS_OUTLINE_LIMIT',
            'COM_JLMS_RANGE_INVALID', 'COM_JLMS_CALENDAR_INVALID', 'COM_JLMS_CALENDAR_RANGE', 'COM_JLMS_CALENDAR_DST'];
        if (in_array($error->getMessage(), $known, true)) {
            $code = in_array($error->getCode(), [400, 401, 403, 404, 409, 413, 416, 422, 503], true) ? $error->getCode() : 400;
            throw new \RuntimeException(Text::_($error->getMessage()), $code);
        }
        Log::add('Material mutation failed; correlation=' . bin2hex(random_bytes(8)), Log::ERROR, 'com_jlms');
        throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
    }

    public function upload(): void { $this->perform('upload'); }
    public function reuse(): void { $this->perform('reuse'); }
    public function save(): void { $this->perform('save'); }
    public function delete(): void { $this->perform('delete'); }
    public function scan(): void { $this->perform('scan'); }

    public function download(): void
    {
        if (!in_array(strtoupper($this->input->getMethod()), ['GET', 'HEAD'], true)) {
            throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'), 405);
        }
        try {
            $file = $this->app->bootComponent('com_jlms')->getMaterialService()->adminDownload($this->app->getIdentity(),
                $this->input->getInt('course_id', 0), $this->input->getInt('version_id', 0), $this->input->getInt('id', 0));
            MaterialDownload::send($this->app, $file);
        } catch (\Throwable $error) { $this->failure($error); }
    }
}
