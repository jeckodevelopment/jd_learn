<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class QuestionTable extends Table
{
    use QuestionTableAssetMethods {
        assetName as protected _getAssetName;
        assetTitle as protected _getAssetTitle;
        assetParentId as protected _getAssetParentId;
    }

    public function __construct(DatabaseInterface $db)
    {
        parent::__construct('#__jlms_questions', 'id', $db);
        // Retain Joomla Table's inherited nullable-column flag without redeclaring it.
        $this->_supportNullValue = true;
        $this->setColumnAlias('published', 'state');
    }
}
