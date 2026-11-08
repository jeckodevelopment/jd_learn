<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\View\Pathway;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
    public array $item = [];
    public bool $isGuest = true;

    public function display($tpl = null)
    {
        $this->item = $this->get('Item');
        $actor = Factory::getApplication()->getIdentity();
        $this->isGuest = !(int) $actor->id;
        $pathway = $this->item['pathway'];
        $this->getDocument()->setTitle((string) $pathway->title);
        if (trim(strip_tags((string) $pathway->description)) !== '') {
            $this->getDocument()->setDescription(mb_substr(trim(strip_tags((string) $pathway->description)), 0, 300));
        }
        if (!$this->isGuest) {
            $this->getDocument()->setMetaData('robots', 'noindex, nofollow');
        }
        $this->getDocument()->getWebAssetManager()->useStyle('com_jlms.site');
        parent::display($tpl);
    }
}
