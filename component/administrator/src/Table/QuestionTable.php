<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class QuestionTable extends Table
{
    protected $_supportNullValue = true;
    public function __construct(DatabaseInterface $db) { parent::__construct('#__jlms_questions', 'id', $db); $this->setColumnAlias('published', 'state'); }
    protected function _getAssetName() { return 'com_jlms.question.' . (int) $this->id; }
    protected function _getAssetTitle() { return 'JD Learn question #' . (int) $this->id; }
    protected function _getAssetParentId(?Table $table = null, $id = null)
    {
        $db = $this->getDatabase(); $category = (int) $this->catid;
        $q = $db->createQuery()->select('asset_id')->from('#__categories')->where('id = :category')->bind(':category', $category, ParameterType::INTEGER);
        return (int) $db->setQuery($q)->loadResult() ?: parent::_getAssetParentId($table, $id);
    }
}
