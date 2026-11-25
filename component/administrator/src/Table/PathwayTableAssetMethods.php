<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;

/**
 * Table-specific ACL computations; Joomla's protected _getAsset* hooks are
 * retained via trait aliases on PathwayTable, without renaming native contracts.
 */
trait PathwayTableAssetMethods
{
    protected function assetName()
    {
        return 'com_jlms.pathway.' . (int) $this->id;
    }

    protected function assetTitle()
    {
        return (string) $this->title;
    }

    protected function assetParentId(?Table $table = null, $id = null)
    {
        $categoryId = (int) $this->catid;
        $query = $this->getDatabase()->createQuery()->select('asset_id')->from('#__categories')
            ->where('id = :category')->bind(':category', $categoryId, ParameterType::INTEGER);
        return (int) $this->getDatabase()->setQuery($query)->loadResult() ?: parent::_getAssetParentId($table, $id);
    }
}
