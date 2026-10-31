<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Table;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\CMS\Factory;

final class CourseTable extends Table
{
    protected $_supportNullValue = true;
    protected bool $stableAssetRequired = false;

    public function requireStableAsset(bool $required): void
    {
        $this->stableAssetRequired = $required;
    }

    public function __construct(DatabaseInterface $db)
    {
        parent::__construct('#__jlms_courses', 'id', $db);
        $this->setColumnAlias('published', 'state');
    }

    public function check()
    {
        $this->title = trim((string) $this->title);
        $this->alias = ApplicationHelper::stringURLSafe((string) ($this->alias ?: $this->title));
        if ($this->title === '' || \Joomla\String\StringHelper::strlen($this->title) > 255 || $this->alias === '' || strlen($this->alias) > 180) {
            $this->setError('COM_JLMS_INVALID_COURSE');
            return false;
        }
        if (!in_array($this->enrollment_mode, ['auto', 'explicit', 'approval'], true)) {
            $this->setError('COM_JLMS_INVALID_COURSE');
            return false;
        }
        $db = $this->getDatabase();
        $catid = (int) $this->catid;
        if (strlen((string) $this->summary) > 65535) {
            $this->setError('COM_JLMS_INVALID_COURSE');
            return false;
        }
        if ($this->language !== '*') {
            $language = (string) $this->language;
            $languageQuery = $db->createQuery()->select('COUNT(*)')->from($db->quoteName('#__languages'))
                ->where($db->quoteName('lang_code') . ' = :language')->bind(':language', $language);
            if (!(int) $db->setQuery($languageQuery)->loadResult()) {
                $this->setError('COM_JLMS_INVALID_COURSE');
                return false;
            }
        }
        $extension = 'com_jlms.course';
        $q = $db->createQuery()->select($db->quoteName('id'))->from($db->quoteName('#__categories'))
            ->where($db->quoteName('id') . ' = :catid')->where($db->quoteName('extension') . ' = :extension')
            ->bind(':catid', $catid, ParameterType::INTEGER)->bind(':extension', $extension);
        if (!$db->setQuery($q)->loadResult()) {
            $this->setError('JLIB_DATABASE_ERROR_CATEGORY_REQUIRED');
            return false;
        }
        foreach (['catalog_access', 'content_access'] as $field) {
            $level = (int) $this->$field;
            $q = $db->createQuery()->select($db->quoteName('id'))->from($db->quoteName('#__viewlevels'))
                ->where($db->quoteName('id') . ' = :level')->bind(':level', $level, ParameterType::INTEGER);
            if (!$db->setQuery($q)->loadResult()) {
                $this->setError('COM_JLMS_INVALID_ACCESS');
                return false;
            }
        }
        return parent::check();
    }

    public function store($updateNulls = true)
    {
        // Native Asset/Nested::store uses LOCK TABLES, which implicitly commits in MySQL.
        // When the existing asset needs no change, retain it and avoid that operation.
        // Association writes require this path so AdminModel's graph writes stay atomic.
        $asset = new \Joomla\CMS\Table\Asset($this->getDatabase());
        $stable = $this->id && $asset->loadByName($this->_getAssetName())
            && (int) $asset->id === (int) $this->asset_id
            && (int) $asset->parent_id === (int) $this->_getAssetParentId()
            && $asset->title === \Joomla\String\StringHelper::substr((string) $this->title, 0, 100)
            && $this->_rules === null;
        if ($this->stableAssetRequired && !$stable) {
            throw new \DomainException('COM_JLMS_ASSOCIATION_SAVE_SEPARATELY');
        }
        $now = Factory::getDate()->toSql();
        $userId = (int) Factory::getApplication()->getIdentity()->id;
        if (!$this->id) {
            $this->created_at = $now;
            $this->created_by = $userId;
            $this->revision = 1;
            $this->params_json = '{}';
            $this->metadata_json = '{}';
            $this->state = 0;
        } else {
            $this->revision = (int) $this->revision + 1;
        }
        $this->modified_at = $now;
        $this->modified_by = $userId;
        $tracking = $this->_trackAssets;
        if ($stable) { $this->_trackAssets = false; }
        try {
            return parent::store($updateNulls);
        } finally {
            $this->_trackAssets = $tracking;
        }
    }

    protected function _getAssetName()
    {
        return 'com_jlms.course.' . (int) $this->id;
    }

    protected function _getAssetTitle()
    {
        return $this->title;
    }

    protected function _getAssetParentId(?Table $table = null, $id = null)
    {
        $db = $this->getDatabase();
        $catid = (int) $this->catid;
        $q = $db->createQuery()->select($db->quoteName('asset_id'))->from($db->quoteName('#__categories'))
            ->where($db->quoteName('id') . ' = :id')->bind(':id', $catid, ParameterType::INTEGER);
        $assetId = (int) $db->setQuery($q)->loadResult();
        return $assetId ?: parent::_getAssetParentId($table, $id);
    }
}
