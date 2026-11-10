<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Controller;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Jlms\Component\Jlms\Administrator\Infrastructure\MaterialDownload;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;

final class AttachmentController extends BaseController
{
    public function execute($task)
    {
        if (!in_array(strtolower($task), ['download', 'audio', 'preview', 'inline'], true)) { throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404); }
        return parent::execute($task);
    }

    public function preview(): void
    {
        $this->app->setHeader('Cache-Control', 'private, no-store', true);
        if (!in_array(strtoupper($this->input->getMethod()), ['GET', 'HEAD'], true)) { throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'), 405); }
        try {
            $ids = [];
            foreach (['course_id', 'version_id', 'lesson_id', 'id'] as $key) {
                try { $ids[] = CurriculumService::id($this->input->get($key, null, 'RAW')); }
                catch (\InvalidArgumentException $error) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
            }
            $file = $this->app->bootComponent('com_jlms')->getMaterialService()->previewDownload($this->app->getIdentity(), ...$ids);
            MaterialDownload::send($this->app, $file);
        } catch (\Throwable $error) {
            $known = ['COM_JLMS_LOGIN_REQUIRED', 'COM_JLMS_LEARNING_DENIED', 'COM_JLMS_RESOURCE_NOT_FOUND',
                'COM_JLMS_STORAGE_UNAVAILABLE', 'COM_JLMS_MATERIAL_NOT_READY', 'COM_JLMS_DELIVERY_DISABLED',
                'COM_JLMS_RANGE_INVALID', 'COM_JLMS_INVALID_INPUT'];
            if (in_array($error->getMessage(), $known, true)) { throw new \RuntimeException(Text::_($error->getMessage()), $error->getCode()); }
            Log::add('Editorial preview material delivery failed; correlation=' . bin2hex(random_bytes(8)), Log::ERROR, 'com_jlms');
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
    }

    public function audio(): void
    {
        $this->app->setHeader('Cache-Control', 'private, no-store', true);
        if (!in_array(strtoupper($this->input->getMethod()), ['GET', 'HEAD'], true)) { throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'), 405); }
        try {
            $file = $this->app->bootComponent('com_jlms')->getMaterialService()->audioDownload($this->app->getIdentity(),
                $this->app->getLanguage()->getTag(), $this->input->getInt('enrollment_id', 0), $this->input->getInt('lesson_id', 0), $this->input->getInt('id', 0));
            MaterialDownload::send($this->app, $file);
        } catch (\Throwable $error) {
            $known = ['COM_JLMS_LOGIN_REQUIRED', 'COM_JLMS_LEARNING_DENIED', 'COM_JLMS_LEARNING_WINDOW', 'COM_JLMS_RESOURCE_NOT_FOUND',
                'COM_JLMS_STORAGE_UNAVAILABLE', 'COM_JLMS_MATERIAL_NOT_READY', 'COM_JLMS_DELIVERY_DISABLED', 'COM_JLMS_RANGE_INVALID'];
            if (in_array($error->getMessage(), $known, true)) { throw new \RuntimeException(Text::_($error->getMessage()), $error->getCode()); }
            Log::add('Audio delivery failed; correlation=' . bin2hex(random_bytes(8)), Log::ERROR, 'com_jlms');
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
    }

    public function download(): void
    {
        $this->app->setHeader('Cache-Control', 'private, no-store', true);
        if (!in_array(strtoupper($this->input->getMethod()), ['GET', 'HEAD'], true)) {
            throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'), 405);
        }
        try {
            $file = $this->app->bootComponent('com_jlms')->getMaterialService()->download($this->app->getIdentity(),
                $this->app->getLanguage()->getTag(), $this->input->getInt('enrollment_id', 0), $this->input->getInt('id', 0));
            MaterialDownload::send($this->app, $file);
        } catch (\Throwable $error) {
            $known = ['COM_JLMS_LOGIN_REQUIRED', 'COM_JLMS_LEARNING_DENIED', 'COM_JLMS_LEARNING_WINDOW',
                'COM_JLMS_RESOURCE_NOT_FOUND', 'JERROR_LAYOUT_PAGE_NOT_FOUND', 'COM_JLMS_STORAGE_UNAVAILABLE',
                'COM_JLMS_MATERIAL_NOT_READY', 'COM_JLMS_DELIVERY_DISABLED', 'COM_JLMS_RANGE_INVALID'];
            if (in_array($error->getMessage(), $known, true)) {
                throw new \RuntimeException(Text::_($error->getMessage()), $error->getCode());
            }
            Log::add('Material download failed; correlation=' . bin2hex(random_bytes(8)), Log::ERROR, 'com_jlms');
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
    }

    public function inline(): void
    {
        $this->app->setHeader('Cache-Control', 'private, no-store', true);
        if (!in_array(strtoupper($this->input->getMethod()), ['GET', 'HEAD'], true)) {
            throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'), 405);
        }
        try {
            $file = $this->app->bootComponent('com_jlms')->getMaterialService()->inlinePreview($this->app->getIdentity(),
                $this->app->getLanguage()->getTag(), $this->input->getInt('enrollment_id', 0), $this->input->getInt('id', 0));
            MaterialDownload::send($this->app, $file);
        } catch (\Throwable $error) {
            $known = ['COM_JLMS_LOGIN_REQUIRED', 'COM_JLMS_LEARNING_DENIED', 'COM_JLMS_LEARNING_WINDOW',
                'COM_JLMS_RESOURCE_NOT_FOUND', 'COM_JLMS_STORAGE_UNAVAILABLE', 'COM_JLMS_MATERIAL_NOT_READY',
                'COM_JLMS_DELIVERY_DISABLED', 'COM_JLMS_RANGE_INVALID'];
            if (in_array($error->getMessage(), $known, true)) {
                throw new \RuntimeException(Text::_($error->getMessage()), $error->getCode());
            }
            Log::add('Inline material delivery failed; correlation=' . bin2hex(random_bytes(8)), Log::ERROR, 'com_jlms');
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
    }
}
