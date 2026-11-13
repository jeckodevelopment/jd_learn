<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;
use Jlms\Component\Jlms\Administrator\Infrastructure\MaterialDownload;

/** Serves a student's own issued certificate after an ownership check. */
final class CertificateController extends BaseController
{
    public function execute($task)
    {
        if (!in_array(strtolower($task), ['download'], true)) { throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404); }
        return parent::execute($task);
    }

    public function download(): void
    {
        $this->app->setHeader('Cache-Control', 'private, no-store', true);
        if (!in_array(strtoupper($this->input->getMethod()), ['GET', 'HEAD'], true)) {
            throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'), 405);
        }
        try {
            try { $enrollmentId = CurriculumService::id($this->input->get('enrollment_id', null, 'RAW')); }
            catch (\InvalidArgumentException $error) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
            $file = $this->app->bootComponent('com_jlms')->getCertificateService()->download($this->app->getIdentity(), $enrollmentId);
            MaterialDownload::send($this->app, $file);
        } catch (\Throwable $error) {
            $known = ['COM_JLMS_LOGIN_REQUIRED', 'COM_JLMS_INVALID_INPUT', 'COM_JLMS_RESOURCE_NOT_FOUND',
                'COM_JLMS_STORAGE_UNAVAILABLE', 'COM_JLMS_CERTIFICATE_UNAVAILABLE', 'COM_JLMS_CERTIFICATE_RENDER_FAILED',
                'COM_JLMS_FILE_INVALID', 'COM_JLMS_FILE_SIZE', 'COM_JLMS_RANGE_INVALID', 'COM_JLMS_METHOD_NOT_ALLOWED'];
            if (in_array($error->getMessage(), $known, true)) {
                throw new \RuntimeException(Text::_($error->getMessage()), $error->getCode());
            }
            Log::add('Certificate delivery failed; correlation=' . bin2hex(random_bytes(8)), Log::ERROR, 'com_jlms');
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
    }
}
