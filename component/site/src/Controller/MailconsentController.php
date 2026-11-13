<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

/** Handles optional new-course email consent separately from operational notices. */
final class MailconsentController extends BaseController
{
    public function execute($task)
    {
        if (!in_array(strtolower($task), ['request', 'confirm', 'revoke', 'revokeLink'], true)) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        return parent::execute($task);
    }

    public function request(): void
    {
        $this->app->setHeader('Cache-Control', 'private, no-store', true);
        if (strtoupper($this->input->getMethod()) !== 'POST' || !$this->checkToken('post', false)) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN_NOTICE'), 403);
        }
        $user = $this->app->getIdentity();
        if (!$user || $user->guest) { throw new \RuntimeException(Text::_('COM_JLMS_LOGIN_REQUIRED'), 401); }
        if ($this->input->post->getInt('consent') !== 1) {
            $this->app->enqueueMessage(Text::_('COM_JLMS_MAIL_CONSENT_CHECKBOX_REQUIRED'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_jlms&view=dashboard', false));
            return;
        }
        try {
            $this->app->bootComponent('com_jlms')->getCourseAnnouncementConsent()->request((int) $user->id, (string) $user->email, (string) $user->name, 'dashboard');
            $this->app->enqueueMessage(Text::_('COM_JLMS_MAIL_CONSENT_CONFIRMATION_SENT'), 'message');
        } catch (\Throwable $error) {
            Log::add('Course consent request failed; correlation=' . bin2hex(random_bytes(8)), Log::ERROR, 'com_jlms');
            $this->app->enqueueMessage(Text::_('COM_JLMS_MAIL_CONSENT_REQUEST_FAILED'), 'error');
        }
        $this->setRedirect(Route::_('index.php?option=com_jlms&view=dashboard', false));
    }

    public function confirm(): void
    {
        $this->app->setHeader('Cache-Control', 'no-store', true);
        $token = $this->input->getString('token', '');
        $ok = $this->app->bootComponent('com_jlms')->getCourseAnnouncementConsent()->confirm($token);
        $this->app->enqueueMessage(Text::_($ok ? 'COM_JLMS_MAIL_CONSENT_CONFIRMED' : 'COM_JLMS_MAIL_CONSENT_LINK_INVALID'), $ok ? 'message' : 'warning');
        $this->setRedirect(Route::_('index.php?option=com_jlms&view=dashboard', false));
    }

    public function revokeLink(): void
    {
        $this->app->setHeader('Cache-Control', 'private, no-store', true);
        $this->app->setHeader('Referrer-Policy', 'no-referrer', true);
        $this->app->setHeader('X-Robots-Tag', 'noindex, nofollow', true);
        if (strtoupper($this->input->getMethod()) !== 'POST' || !$this->checkToken('post', false)) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN_NOTICE'), 403);
        }

        $token = $this->input->post->getString('token', '');
        $ok = $this->app->bootComponent('com_jlms')->getCourseAnnouncementConsent()->revokeByToken($token);
        $this->app->enqueueMessage(
            Text::_($ok ? 'COM_JLMS_MAIL_CONSENT_LINK_REVOKED' : 'COM_JLMS_MAIL_CONSENT_LINK_INVALID'),
            $ok ? 'message' : 'warning'
        );
        $this->setRedirect(Route::_('index.php?option=com_jlms&view=mailconsentrevoke&done=1', false));
    }

    public function revoke(): void
    {
        $this->app->setHeader('Cache-Control', 'private, no-store', true);
        if (strtoupper($this->input->getMethod()) !== 'POST' || !$this->checkToken('post', false)) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN_NOTICE'), 403);
        }
        $user = $this->app->getIdentity();
        if (!$user || $user->guest) { throw new \RuntimeException(Text::_('COM_JLMS_LOGIN_REQUIRED'), 401); }
        $this->app->bootComponent('com_jlms')->getCourseAnnouncementConsent()->revoke((int) $user->id);
        $this->app->enqueueMessage(Text::_('COM_JLMS_MAIL_CONSENT_REVOKED'), 'message');
        $this->setRedirect(Route::_('index.php?option=com_jlms&view=dashboard', false));
    }
}
