<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\View\Course;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
    public array $item;
    public bool $hasContentAccess = false;

    public function display($tpl = null)
    {
        $this->item = $this->get('Item');
        $actor = Factory::getApplication()->getIdentity();
        $this->hasContentAccess = !$actor->block && in_array((int) $this->item['course']->content_access, array_map('intval', $actor->getAuthorisedViewLevels()), true);
        $this->getDocument()->setTitle($this->item['course']->title);
        $this->getDocument()->getWebAssetManager()->useStyle('com_jlms.site');
        parent::display($tpl);
    }
}
