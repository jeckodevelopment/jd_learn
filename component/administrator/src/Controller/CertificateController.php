<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;
use Jlms\Component\Jlms\Administrator\Infrastructure\MaterialDownload;

final class CertificateController extends BaseController
{
    public function execute($task)
    {
        if (!in_array(strtolower($task), ['download', 'regenerate', 'recoverhistorical', 'recovermissing'], true)) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        return parent::execute($task);
    }

    /** Explicit historical recovery, protected by POST token, ACL and typed inputs. */
    public function recoverHistorical(): void
    {
        $this->app->setHeader('Cache-Control', 'private, no-store', true);
        if (strtoupper($this->input->getMethod()) !== 'POST') {
            throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'), 405);
        }
        if (!$this->checkToken('post', false)) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN_NOTICE'), 403);
        }
        try {
            if ((int) $this->input->server->get('CONTENT_LENGTH', 0, 'UINT') > 4096) {
                throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 413);
            }
            $enrollmentId = CurriculumService::id($this->input->post->get('enrollment_id', null, 'RAW'));
            if ($this->input->post->getString('confirmation', '') !== '1') {
                throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 422);
            }
            $reason = $this->input->post->getString('reason', '');
            $this->app->bootComponent('com_jlms')->getCertificateService()->adminRecoverHistorical(
                $this->app->getIdentity(), $enrollmentId, $reason
            );
            $this->setMessage(Text::_('COM_JLMS_CERTIFICATE_HISTORICAL_RECOVERED'));
            $this->setRedirect(\Joomla\CMS\Router\Route::link(
                'administrator', 'index.php?option=com_jlms&view=certificates', false
            ));
        } catch (\Throwable $error) {
            $known = ['JERROR_ALERTNOAUTHOR', 'COM_JLMS_INVALID_INPUT', 'COM_JLMS_RESOURCE_NOT_FOUND',
                'COM_JLMS_CERTIFICATE_UNAVAILABLE', 'COM_JLMS_CERTIFICATE_AUDIT_FAILED'];
            if (in_array($error->getMessage(), $known, true)) {
                throw new \RuntimeException(Text::_($error->getMessage()), (int) $error->getCode());
            }
            Log::add('Certificate historical recovery failed; correlation=' . bin2hex(random_bytes(8)), Log::ERROR, 'com_jlms');
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
    }

    /** Explicit recovery for eligible completion without issuance, protected by POST token, ACL and typed inputs. */
    public function recoverMissing(): void
    {
        $this->app->setHeader('Cache-Control', 'private, no-store', true);
        if (strtoupper($this->input->getMethod()) !== 'POST') {
            throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'), 405);
        }
        if (!$this->checkToken('post', false)) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN_NOTICE'), 403);
        }
        try {
            if ((int) $this->input->server->get('CONTENT_LENGTH', 0, 'UINT') > 4096) {
                throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 413);
            }
            $enrollmentId = CurriculumService::id($this->input->post->get('enrollment_id', null, 'RAW'));
            if ($this->input->post->getString('confirmation', '') !== '1') {
                throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 422);
            }
            $reason = $this->input->post->getString('reason', '');
            $this->app->bootComponent('com_jlms')->getCertificateService()->adminRecoverMissing(
                $this->app->getIdentity(), $enrollmentId, $reason
            );
            $this->setMessage(Text::_('COM_JLMS_CERTIFICATE_MISSING_RECOVERED'));
            $this->setRedirect(\Joomla\CMS\Router\Route::link(
                'administrator', 'index.php?option=com_jlms&view=certificates', false
            ));
        } catch (\Throwable $error) {
            $known = ['JERROR_ALERTNOAUTHOR', 'COM_JLMS_INVALID_INPUT', 'COM_JLMS_RESOURCE_NOT_FOUND',
                'COM_JLMS_CERTIFICATE_UNAVAILABLE', 'COM_JLMS_CERTIFICATE_AUDIT_FAILED'];
            if (in_array($error->getMessage(), $known, true)) {
                throw new \RuntimeException(Text::_($error->getMessage()), (int) $error->getCode());
            }
            Log::add('Certificate missing issuance recovery failed; correlation=' . bin2hex(random_bytes(8)), Log::ERROR, 'com_jlms');
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
    }

    /** Explicit administrative mutation; never regenerate by GET or download. */
    public function regenerate(): void
    {
        $this->app->setHeader('Cache-Control', 'private, no-store', true);
        if (strtoupper($this->input->getMethod()) !== 'POST') {
            throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'), 405);
        }
        if (!$this->checkToken('post', false)) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN_NOTICE'), 403);
        }
        try {
            if ((int) $this->input->server->get('CONTENT_LENGTH', 0, 'UINT') > 4096) {
                throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 413);
            }
            $id = CurriculumService::id($this->input->post->get('id', null, 'RAW'));
            $revision = CurriculumService::id($this->input->post->get('revision', null, 'RAW'));
            if ($this->input->post->getString('confirmation', '') !== '1') {
                throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 422);
            }
            $reason = $this->input->post->getString('reason', '');
            $this->app->bootComponent('com_jlms')->getCertificateService()->adminRegenerate(
                $this->app->getIdentity(), $id, $revision, $reason
            );
            $this->setMessage(Text::_('COM_JLMS_CERTIFICATE_REGENERATED'));
            $this->setRedirect(\Joomla\CMS\Router\Route::link(
                'administrator', 'index.php?option=com_jlms&view=certificate&id=' . $id, false
            ));
        } catch (\Throwable $error) {
            $known = ['JERROR_ALERTNOAUTHOR', 'COM_JLMS_INVALID_INPUT', 'COM_JLMS_RESOURCE_NOT_FOUND',
                'COM_JLMS_CERTIFICATE_UNAVAILABLE', 'COM_JLMS_CERTIFICATE_AUDIT_FAILED',
                'COM_JLMS_CERTIFICATE_RENDER_FAILED', 'COM_JLMS_STORAGE_UNAVAILABLE',
                'COM_JLMS_CERTIFICATE_REVISION_CONFLICT'];
            if (in_array($error->getMessage(), $known, true)) {
                throw new \RuntimeException(Text::_($error->getMessage()), (int) $error->getCode());
            }
            Log::add('Certificate administrator regeneration failed; correlation=' . bin2hex(random_bytes(8)), Log::ERROR, 'com_jlms');
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
            try {
                $id = CurriculumService::id($this->input->get('id', null, 'RAW'));
            } catch (\InvalidArgumentException $error) {
                throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
            }
            $file = $this->app->bootComponent('com_jlms')->getCertificateService()->adminDownload($this->app->getIdentity(), $id);
            MaterialDownload::send($this->app, $file);
        } catch (\Throwable $error) {
            $known = ['JERROR_ALERTNOAUTHOR', 'COM_JLMS_INVALID_INPUT', 'COM_JLMS_RESOURCE_NOT_FOUND',
                'COM_JLMS_CERTIFICATE_UNAVAILABLE', 'COM_JLMS_CERTIFICATE_AUDIT_FAILED', 'COM_JLMS_STORAGE_UNAVAILABLE',
                'COM_JLMS_CERTIFICATE_RENDER_FAILED', 'COM_JLMS_METHOD_NOT_ALLOWED', 'COM_JLMS_RANGE_INVALID'];
            if (in_array($error->getMessage(), $known, true)) {
                throw new \RuntimeException(Text::_($error->getMessage()), (int) $error->getCode());
            }
            Log::add('Certificate administrator download failed; correlation=' . bin2hex(random_bytes(8)), Log::ERROR, 'com_jlms');
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
    }
}
