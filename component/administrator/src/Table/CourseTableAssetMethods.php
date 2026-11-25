<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;

/**
 * Table-specific ACL computations; Joomla's protected _getAsset* hooks are
 * retained via trait aliases on CourseTable, without renaming native contracts.
 */
trait CourseTableAssetMethods
{
    protected function assetName()
    {
        return 'com_jlms.course.' . (int) $this->id;
    }

    protected function assetTitle()
    {
        return $this->title;
    }

    protected function assetParentId(?Table $table = null, $id = null)
    {
        $db = $this->getDatabase();
        $catid = (int) $this->catid;
        $q = $db->createQuery()->select($db->quoteName('asset_id'))->from($db->quoteName('#__categories'))
            ->where($db->quoteName('id') . ' = :id')->bind(':id', $catid, ParameterType::INTEGER);
        $assetId = (int) $db->setQuery($q)->loadResult();
        return $assetId ?: parent::_getAssetParentId($table, $id);
    }
}
