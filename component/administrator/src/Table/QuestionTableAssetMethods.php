<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;

/**
 * Table-specific ACL computations; Joomla's protected _getAsset* hooks are
 * retained via trait aliases on QuestionTable, without renaming native contracts.
 */
trait QuestionTableAssetMethods
{
    protected function assetName()
    {
        return 'com_jlms.question.' . (int) $this->id;
    }
    protected function assetTitle()
    {
        return 'JD Learn question #' . (int) $this->id;
    }
    protected function assetParentId(?Table $table = null, $id = null)
    {
        $db = $this->getDatabase();
        $category = (int) $this->catid;
        $q = $db->createQuery()->select('asset_id')->from('#__categories')->where('id = :category')
            ->bind(':category', $category, ParameterType::INTEGER);
        return (int) $db->setQuery($q)->loadResult() ?: parent::_getAssetParentId($table, $id);
    }
}
