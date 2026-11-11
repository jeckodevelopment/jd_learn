<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\View\Categoryreassignment;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public array $categories = [];
    public ?array $inspection = null;

    public function display($tpl = null)
    {
        $actor = new User((int) Factory::getApplication()->getIdentity()->id);
        $actor->clearAccessRights();
        if (!$actor->id || $actor->block || !$actor->authorise('core.manage', 'com_jlms')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery()->select('id, title, extension, created_user_id, checked_out, lft, rgt')->from('#__categories')
            ->where('extension LIKE ' . $db->quote('com_jlms.%'))->where('published <> -2')->order('extension, lft');
        $this->categories = array_values(array_filter($db->setQuery($query)->loadObjectList(), static function (object $category) use ($actor): bool {
            $asset = $category->extension . '.category.' . (int) $category->id;
            return $actor->authorise('core.delete', $asset)
                || ((int) $category->created_user_id === (int) $actor->id && $actor->authorise('core.delete.own', $asset));
        }));
        $categoryId = Factory::getApplication()->input->getInt('category_id');
        if ($categoryId > 1) {
            $this->inspection = Factory::getApplication()->bootComponent('com_jlms')
                ->getCategoryReassignmentService()->inspect($actor, $categoryId);
        }
        ToolbarHelper::title(Text::_('COM_JLMS_CATEGORY_REASSIGN_TITLE'), 'folder-tree');
        parent::display($tpl);
    }
}
