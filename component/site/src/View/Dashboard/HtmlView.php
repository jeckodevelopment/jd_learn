<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\View\Dashboard;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public $pagination;
    public $progressForm;

    public function display($tpl = null)
    {
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->progressForm = $this->getModel()->getProgressForm();
        if ($this->get('Errors')) { throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500); }
        $this->getDocument()->setTitle(Text::_('COM_JLMS_MY_COURSES'));
        $this->getDocument()->setMetaData('robots', 'noindex, nofollow');
        $this->getDocument()->getWebAssetManager()->useStyle('com_jlms.site');
        parent::display($tpl);
    }
}
