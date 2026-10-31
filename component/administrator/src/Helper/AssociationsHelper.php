<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Helper;

use Joomla\CMS\Association\AssociationExtensionHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Jlms\Component\Jlms\Site\Helper\AssociationHelper;

/** Native association contract; course sheets and section-scoped native categories. */
final class AssociationsHelper extends AssociationExtensionHelper
{
    protected $extension = 'com_jlms';
    protected $itemTypes = ['course', 'course.category'];
    protected $associationsSupport = true;

    public function getAssociationsForItem($id = 0, $view = null)
    {
        return AssociationHelper::getAssociations((int) $id, $view);
    }

    public function getAssociations($typeName, $id)
    {
        if ($typeName === 'course.category') {
            return Associations::getAssociations('com_jlms.course', '#__categories', 'com_categories.item', (int) $id, 'id', null, null);
        }
        // Courses use sectioned categories, not the component name as category extension.
        return $typeName === 'course'
            ? Associations::getAssociations('com_jlms', '#__jlms_courses', 'com_jlms.item', (int) $id, 'id', null, null)
            : [];
    }

    public function getItem($typeName, $id)
    {
        if (!in_array($typeName, $this->itemTypes, true) || (int) $id < 1) {
            return null;
        }
        if ($typeName === 'course.category') {
            $table = new \Joomla\CMS\Table\Category(Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class));
            return $table->load((int) $id) && $table->extension === 'com_jlms.course' ? $table : null;
        }
        $table = Factory::getApplication()->bootComponent('com_jlms')->getMVCFactory()->createTable('Course', 'Administrator');
        return $table->load((int) $id) ? $table : null;
    }

    public function allowEdit($typeName, $id)
    {
        $item = $this->getItem($typeName, $id);
        $actor = new \Joomla\CMS\User\User((int) Factory::getApplication()->getIdentity()->id);
        $actor->clearAccessRights();
        if ($typeName === 'course.category') {
            $asset = 'com_jlms.course.category.' . (int) $id;
            return $item && !$actor->guest && !$actor->block && (!$item->checked_out || (int) $item->checked_out === (int) $actor->id)
                && ($actor->authorise('core.edit', $asset) || ((int) $item->created_user_id === (int) $actor->id && $actor->authorise('core.edit.own', $asset)));
        }
        $asset = 'com_jlms.course.' . (int) $id;
        return $item && !$actor->guest && !$actor->block && (int) $item->state === 0 && $item->current_version_id === null
            && (!$item->checked_out || (int) $item->checked_out === (int) $actor->id)
            && ($actor->authorise('core.edit', $asset) || ((int) $item->created_by === (int) $actor->id && $actor->authorise('core.edit.own', $asset)));
    }

    public function getType($typeName = '')
    {
        $fields = $this->getFieldsTemplate();
        $support = $this->getSupportTemplate();
        $tables = [];
        if ($typeName === 'course') {
            $fields['access'] = 'a.catalog_access';
            $fields['ordering'] = 'a.id';
            $fields['catid'] = '';
            $support['state'] = $support['acl'] = $support['checkout'] = true;
            $support['category'] = $support['save2copy'] = false;
            $tables = ['a' => '#__jlms_courses'];
        }
        if ($typeName === 'course.category') {
            $fields['created_user_id'] = 'a.created_user_id';
            $fields['ordering'] = 'a.lft';
            $fields['level'] = 'a.level';
            $fields['catid'] = '';
            $fields['state'] = 'a.published';
            $support['state'] = $support['acl'] = $support['checkout'] = $support['level'] = true;
            $tables = ['a' => '#__categories'];
        }
        return ['fields' => $fields, 'support' => $support, 'tables' => $tables, 'joins' => [], 'title' => $typeName === 'course' ? 'course' : ($typeName === 'course.category' ? 'category' : '')];
    }
}
