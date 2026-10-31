<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Helper;

use Joomla\CMS\Association\AssociationExtensionHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Jlms\Component\Jlms\Site\Helper\AssociationHelper;

/** Native association contract; only course sheets are supported in this increment. */
final class AssociationsHelper extends AssociationExtensionHelper
{
    protected $extension = 'com_jlms';
    protected $itemTypes = ['course'];
    protected $associationsSupport = true;

    public function getAssociationsForItem($id = 0, $view = null)
    {
        return AssociationHelper::getAssociations((int) $id, $view);
    }

    public function getAssociations($typeName, $id)
    {
        // Courses use sectioned categories, not the component name as category extension.
        return $typeName === 'course'
            ? Associations::getAssociations('com_jlms', '#__jlms_courses', 'com_jlms.item', (int) $id, 'id', null, null)
            : [];
    }

    public function getItem($typeName, $id)
    {
        if ($typeName !== 'course' || (int) $id < 1) {
            return null;
        }
        $table = Factory::getApplication()->bootComponent('com_jlms')->getMVCFactory()->createTable('Course', 'Administrator');
        return $table->load((int) $id) ? $table : null;
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
        return ['fields' => $fields, 'support' => $support, 'tables' => $tables, 'joins' => [], 'title' => $typeName === 'course' ? 'course' : ''];
    }
}
