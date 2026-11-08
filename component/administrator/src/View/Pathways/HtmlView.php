<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\View\Pathways;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\User\User;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public $pagination;

    public function display($tpl = null)
    {
        $actor = new User((int) Factory::getApplication()->getIdentity()->id);
        $actor->clearAccessRights();
        if (!$actor->id || $actor->block || !$actor->authorise('core.manage', 'com_jlms')
            || !$actor->authorise('jlms.pathways.manage', 'com_jlms')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        ToolbarHelper::title(Text::_('COM_JLMS_PATHWAYS'), 'map-signs');
        if ($actor->authorise('core.create', 'com_jlms')) {
            ToolbarHelper::addNew('pathway.add');
        }
        parent::display($tpl);
    }
}
