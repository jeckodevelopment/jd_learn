<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\View\Classroom;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
    public array $item;
    public string $mutationKey;

    public function display($tpl = null)
    {
        $this->item = $this->get('Item');
        $this->mutationKey = bin2hex(random_bytes(16));
        $this->getDocument()->setTitle($this->item['title']);
        $this->getDocument()->setMetaData('robots', 'noindex, nofollow');
        $this->getDocument()->getWebAssetManager()->useStyle('com_jlms.site');
        parent::display($tpl);
    }
}
