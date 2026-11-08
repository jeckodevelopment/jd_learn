<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\View\Pathways;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public $pagination;

    public function display($tpl = null)
    {
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        if ($this->get('Errors')) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 500);
        }
        $this->getDocument()->setTitle(Text::_('COM_JLMS_PATHWAYS'));
        $this->getDocument()->getWebAssetManager()->useStyle('com_jlms.site');
        parent::display($tpl);
    }
}
