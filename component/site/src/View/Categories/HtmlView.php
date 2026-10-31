<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\View\Categories;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Jlms\Component\Jlms\Site\Helper\CategoryNavigationHelper;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public $pagination;
    public ?object $parent = null;

    public function display($tpl = null)
    {
        $this->parent = $this->getModel()->getParent();
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        if ($this->get('Errors')) { throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 500); }
        if ($this->parent) { CategoryNavigationHelper::addPath((int) $this->parent->id); }
        $this->getDocument()->setTitle($this->parent ? $this->parent->title : Text::_('COM_JLMS_CATEGORIES'));
        $this->getDocument()->getWebAssetManager()->useStyle('com_jlms.site');
        parent::display($tpl);
    }
}
