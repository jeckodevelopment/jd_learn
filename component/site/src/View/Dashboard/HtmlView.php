<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\View\Dashboard;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
    public array $summary = [];
    public int $certificateCount = 0;

    public function display($tpl = null)
    {
        $app = Factory::getApplication();
        $component = $app->bootComponent('com_jlms');

        // Existing ACL-aware history projection and ownership checks.
        $this->summary = $component->getDashboardService()
            ->summary($app->getIdentity(), $app->getLanguage()->getTag());
        $this->certificateCount = count(
            $component->getCertificateService()->listForStudent($app->getIdentity())
        );

        $this->getDocument()->setTitle(Text::_('COM_JLMS_MY_TRAINING'));
        $this->getDocument()->setMetaData('robots', 'noindex, nofollow');
        $this->getDocument()->getWebAssetManager()->useStyle('com_jlms.site');
        parent::display($tpl);
    }
}
