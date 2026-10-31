<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Site\View\Courses;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Language\Text;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public $pagination;
    public int $categoryId = 0;

    public function display($tpl = null)
    {
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->categoryId = (int) $this->getModel()->getState('filter.category_id');
        if ($this->get('Errors')) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 500);
        }
        $this->getDocument()->setTitle(Text::_('COM_JLMS_COURSES'));
        $this->getDocument()->getWebAssetManager()->useStyle('com_jlms.site');
        parent::display($tpl);
    }
}
