<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Tag\TaggableTableTrait;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class TeacherTable extends Table implements TaggableTableInterface
{
    use TeacherTableAssetMethods {
        assetName as protected _getAssetName;
        assetTitle as protected _getAssetTitle;
        assetParentId as protected _getAssetParentId;
    }
    use TaggableTableTrait;

    public $typeAlias = 'com_jlms.teacher';

    public function getTypeAlias(): string
    {
        return $this->typeAlias;
    }

    public function __construct(DatabaseInterface $db)
    {
        parent::__construct('#__jlms_teachers', 'id', $db);
        // Retain Joomla Table's inherited nullable-column flag without redeclaring it.
        $this->_supportNullValue = true;
        $this->setColumnAlias('published', 'state');
    }

    public function check()
    {
        $this->title = trim((string) $this->title);
        $this->alias = \Joomla\CMS\Application\ApplicationHelper::stringURLSafe((string) ($this->alias ?: $this
            ->title));
        if (
            $this->title === '' || mb_strlen($this->title) > 255 || preg_match('//u', $this->title) !== 1
                || $this->alias === '' || strlen($this->alias) > 180
            || strlen((string) $this->bio) > 65535 || preg_match('//u', (string) $this->bio) !== 1
                || !in_array((int) $this->state, [-2, 0, 1], true)
        ) {
            $this->setError('COM_JLMS_TEACHER_INVALID');
            return false;
        }
        $db = $this->getDatabase();
        $catid = (int) $this->catid;
        $access = (int) $this->access;
        $language = (string) $this->language;
        $q = $db->createQuery()->select('cat.id')->from('#__categories AS cat')
            ->where("cat.extension='com_jlms.teacher' AND cat.published<>-2")->where('cat.id=:id')
            ->where('NOT EXISTS (SELECT 1 FROM #__categories AS ancestor'
                . ' WHERE ancestor.lft <= cat.lft AND ancestor.rgt >= cat.rgt AND ancestor.published = -2)')
            ->bind(':id', $catid, ParameterType::INTEGER);
        if (!$db->setQuery($q)->loadResult()) {
            $this->setError('COM_JLMS_TEACHER_INVALID');
            return false;
        }
        $q = $db->createQuery()->select('id')->from('#__viewlevels')->where('id=:id')
            ->bind(':id', $access, ParameterType::INTEGER);
        if (!$db->setQuery($q)->loadResult()) {
            $this->setError('COM_JLMS_INVALID_ACCESS');
            return false;
        }
        if ($language !== '*') {
            $q = $db->createQuery()->select('lang_id')->from('#__languages')->where('lang_code=:language')
                ->bind(':language', $language);
            if (!$db->setQuery($q)->loadResult()) {
                $this->setError('COM_JLMS_TEACHER_INVALID');
                return false;
            }
        }
        return parent::check();
    }

    public function store($updateNulls = true)
    {
        $now = Factory::getDate()->toSql();
        $actor = (int) Factory::getApplication()->getIdentity()->id;
        if (!$this->id) {
            $this->revision = 1;
            $this->created_at = $now;
            $this->created_by = $actor;
        } else {
            $this->revision = (int) $this->revision + 1;
        }
        $this->modified_at = $now;
        $this->modified_by = $actor;
        $asset = new \Joomla\CMS\Table\Asset($this->getDatabase());
        $stable = $this->id && $asset->loadByName($this->_getAssetName())
            && (int) $asset->id === (int) $this->asset_id && (int) $asset->parent_id === (int) $this
                ->_getAssetParentId() && $this->_rules === null;
        $tracking = $this->_trackAssets;
        if ($stable) {
            $this->_trackAssets = false;
            $title = (object) ['id' => (int) $asset->id, 'title'
                => \Joomla\String\StringHelper::substr((string) $this->title, 0, 100)];
            $this->getDatabase()->updateObject('#__assets', $title, 'id');
        }
        try {
            return parent::store($updateNulls);
        } finally {
            $this->_trackAssets = $tracking;
        }
    }
}
