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
    public string $stateFilter = 'active';

    public function display($tpl = null)
    {
        $actor = new User((int) Factory::getApplication()->getIdentity()->id);
        $actor->clearAccessRights();
        if (!$actor->id || $actor->block || !$actor->authorise('core.manage', 'com_jlms')
            || !$actor->authorise('jlms.pathways.manage', 'com_jlms')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        $this->stateFilter = (string) ($this->getModel()->getState('filter.pathway_state') ?: 'active');
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        ToolbarHelper::title(Text::_('COM_JLMS_PATHWAYS'), 'map-signs');
        if ($actor->authorise('core.create', 'com_jlms')) {
            ToolbarHelper::addNew('pathway.add');
        }
        if ($actor->authorise('core.edit.state', 'com_jlms')) {
            if ($this->stateFilter === 'trashed') {
                ToolbarHelper::custom('pathway.restore', 'refresh', 'refresh', 'JTOOLBAR_RESTORE', true);
                if ($actor->authorise('core.delete', 'com_jlms')) {
                    ToolbarHelper::deleteList('COM_JLMS_PATHWAY_PURGE_CONFIRM', 'pathway.purge');
                }
            } else {
                ToolbarHelper::publish('pathway.publish', 'JTOOLBAR_PUBLISH', true);
                ToolbarHelper::unpublish('pathway.unpublish', 'JTOOLBAR_UNPUBLISH', true);
                ToolbarHelper::archiveList('pathway.archive');
                ToolbarHelper::trash('pathway.trash');
            }
        }
        ToolbarHelper::help('pathways', true);
        parent::display($tpl);
    }
}
