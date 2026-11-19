<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\View\Certificates;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public $items;
    public $pagination;
    public $state;
    public $filterForm;
    public $activeFilters;
    public array $unissuedCompletions = [];

    public function display($tpl = null)
    {
        $app = Factory::getApplication();
        $app->setHeader('Cache-Control', 'private, no-store', true);
        try {
            $this->items = $this->get('Items');
            $this->pagination = $this->get('Pagination');
            $this->state = $this->get('State');
            $this->filterForm = $this->get('FilterForm');
            $this->activeFilters = $this->get('ActiveFilters');
            $this->unissuedCompletions = $app->bootComponent('com_jlms')->getCertificateService()
                ->adminUnissuedCompletions($app->getIdentity());
        } catch (\Throwable $error) {
            if (in_array($error->getMessage(), ['JERROR_ALERTNOAUTHOR', 'COM_JLMS_CERTIFICATE_AUDIT_FAILED'], true)) {
                throw new \RuntimeException(Text::_($error->getMessage()), (int) $error->getCode());
            }
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
        $this->getDocument()->setTitle(Text::_('COM_JLMS_CERTIFICATES'));
        ToolbarHelper::title(Text::_('COM_JLMS_CERTIFICATES'), 'certificate');
        ToolbarHelper::help('certificates', true);
        parent::display($tpl);
    }
}
