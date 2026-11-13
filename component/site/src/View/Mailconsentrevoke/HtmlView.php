<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\View\Mailconsentrevoke;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/** Confirmation page for bearer unsubscribe links; GET never changes consent. */
final class HtmlView extends BaseHtmlView
{
    public string $token = '';
    public bool $canSubmit = false;
    public bool $completed = false;

    public function display($tpl = null)
    {
        $app = Factory::getApplication();
        $app->setHeader('Cache-Control', 'private, no-store', true);
        $app->setHeader('Referrer-Policy', 'no-referrer', true);
        $app->setHeader('X-Robots-Tag', 'noindex, nofollow', true);

        $this->completed = $app->input->getBool('done', false);
        $this->token = $this->completed ? '' : $app->input->get->getString('token', '');
        $this->canSubmit = !$this->completed && preg_match('/^[A-Za-z0-9_-]{40,50}$/', $this->token) === 1;
        $this->getDocument()->setTitle(Text::_('COM_JLMS_MAIL_CONSENT_LINK_TITLE'));
        $this->getDocument()->setMetaData('robots', 'noindex, nofollow');
        parent::display($tpl);
    }
}
