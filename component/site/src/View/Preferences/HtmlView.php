<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\View\Preferences;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
    public string $announcementConsent = 'none';

    public function display($tpl = null)
    {
        $this->announcementConsent = $this->get('AnnouncementConsent');
        if ($this->get('Errors')) {
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
        $this->getDocument()->setTitle(Text::_('COM_JLMS_PREFERENCES'));
        $this->getDocument()->setMetaData('robots', 'noindex, nofollow');
        $this->getDocument()->getWebAssetManager()->useStyle('com_jlms.site');
        parent::display($tpl);
    }
}
