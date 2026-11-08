<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\View\Pathway;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\User\User;

final class HtmlView extends BaseHtmlView
{
    public $item;
    public $form;

    public function display($tpl = null)
    {
        $actor = new User((int) Factory::getApplication()->getIdentity()->id);
        $actor->clearAccessRights();
        if (!$actor->id || $actor->block || !$actor->authorise('core.manage', 'com_jlms')
            || !$actor->authorise('jlms.pathways.manage', 'com_jlms')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        $this->item = $this->get('Item');
        $id = (int) ($this->item->id ?? 0);
        $scope = $id ? 'com_jlms.pathway.' . $id : 'com_jlms';
        if ($id ? !$actor->authorise('core.edit', $scope) : !$actor->authorise('core.create', $scope)) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        $this->form = $this->get('Form');
        if (!$this->form) {
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
        if ($id) {
            $this->form->setFieldAttribute('catid', 'disabled', 'true');
            $this->form->setFieldAttribute('catid', 'description', 'COM_JLMS_PATHWAY_CATEGORY_LOCKED');
        }
        if (!$actor->authorise('core.edit.state', $scope)) {
            $this->form->setFieldAttribute('state', 'disabled', 'true');
        }
        ToolbarHelper::title(Text::_('COM_JLMS_PATHWAY'), 'map-signs');
        ToolbarHelper::apply('pathway.apply');
        ToolbarHelper::save('pathway.save');
        ToolbarHelper::cancel('pathway.cancel');
        parent::display($tpl);
    }
}
