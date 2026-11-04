<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\View\Teachers;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Factory;

final class HtmlView extends BaseHtmlView
{
    public $items;
    public $pagination;

    public function display($tpl = null)
    {
        $actor = new \Joomla\CMS\User\User((int) Factory::getApplication()->getIdentity()->id); $actor->clearAccessRights();
        if (!$actor->id || $actor->block || !$actor->authorise('core.manage', 'com_jlms')) { throw new \RuntimeException('JERROR_ALERTNOAUTHOR',403); }
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        ToolbarHelper::title('JD Learn', 'graduation-cap');
        if ($actor->authorise('core.create', 'com_jlms') && $actor->authorise('jlms.teachers.manage', 'com_jlms')) {
            ToolbarHelper::addNew('teacher.add');
        }
        parent::display($tpl);
    }
}
