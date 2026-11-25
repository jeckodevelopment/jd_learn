<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;

/**
 * Table-specific ACL computations; Joomla's protected _getAsset* hooks are
 * retained via trait aliases on TeacherTable, without renaming native contracts.
 */
trait TeacherTableAssetMethods
{
    protected function assetName()
    {
        return 'com_jlms.teacher.' . (int) $this->id;
    }
    protected function assetTitle()
    {
        return $this->title;
    }
    protected function assetParentId(?Table $table = null, $id = null)
    {
        $catid = (int) $this->catid;
        $q = $this->getDatabase()->createQuery()->select('asset_id')->from('#__categories')->where('id=:id')
            ->bind(':id', $catid, ParameterType::INTEGER);
        return (int) $this->getDatabase()->setQuery($q)->loadResult() ?: parent::_getAssetParentId($table, $id);
    }
}
