<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Table;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class PathwayTable extends Table
{
    private int $preparedAsset = 0;

    public function __construct(DatabaseInterface $db)
    {
        parent::__construct('#__jlms_pathways', 'id', $db);
        $this->setColumnAlias('published', 'state');
    }

    public function usePreparedAsset(int $assetId): void
    {
        $this->preparedAsset = $assetId;
    }

    public function check(): bool
    {
        $this->title = trim((string) $this->title);
        $this->alias = \Joomla\CMS\Application\ApplicationHelper::stringURLSafe((string) ($this->alias ?: $this->title));
        $this->description = (string) ($this->description ?? '');
        $this->language = (string) ($this->language ?? '*');
        $this->params_json = (string) ($this->params_json ?: '{}');

        if ($this->title === '' || mb_strlen($this->title) > 255 || preg_match('//u', $this->title) !== 1
            || $this->alias === '' || strlen($this->alias) > 180 || strlen($this->description) > 65535
            || preg_match('//u', $this->description) !== 1 || !in_array((int) $this->state, [0, 1], true)) {
            $this->setError('COM_JLMS_PATHWAY_INVALID');
            return false;
        }

        $db = $this->getDatabase();
        $category = $db->createQuery()->select('id')->from('#__categories')
            ->where("extension='com_jlms.pathway' AND published <> -2")
            ->where('id = :category')->bind(':category', (int) $this->catid, ParameterType::INTEGER);
        if (!$db->setQuery($category)->loadResult()) {
            $this->setError('COM_JLMS_PATHWAY_INVALID');
            return false;
        }

        $access = $db->createQuery()->select('id')->from('#__viewlevels')
            ->where('id = :access')->bind(':access', (int) $this->access, ParameterType::INTEGER);
        if (!$db->setQuery($access)->loadResult()) {
            $this->setError('COM_JLMS_INVALID_ACCESS');
            return false;
        }

        if ($this->language !== '*') {
            $language = $db->createQuery()->select('lang_id')->from('#__languages')
                ->where('lang_code = :language')->bind(':language', $this->language);
            if (!$db->setQuery($language)->loadResult()) {
                $this->setError('COM_JLMS_PATHWAY_INVALID');
                return false;
            }
        }

        return parent::check();
    }

    public function store($updateNulls = true)
    {
        $asset = new \Joomla\CMS\Table\Asset($this->getDatabase());
        $stable = $this->id && $asset->loadByName($this->_getAssetName())
            && (int) $asset->id === (int) $this->asset_id
            && (int) $asset->parent_id === (int) $this->_getAssetParentId()
            && $this->_rules === null;
        $prepared = !$this->id && $this->preparedAsset > 0 && $asset->load($this->preparedAsset)
            && str_starts_with((string) $asset->name, 'com_jlms.pathway.pending.')
            && (int) $asset->parent_id === (int) $this->_getAssetParentId();
        if ($this->preparedAsset && !$prepared) {
            throw new \DomainException('COM_JLMS_SAVE_FAILED');
        }
        if ($prepared) {
            $this->asset_id = $this->preparedAsset;
        }

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

        $tracking = $this->_trackAssets;
        if ($stable || $prepared) {
            $this->_trackAssets = false;
            $this->getDatabase()->updateObject('#__assets', (object) [
                'id' => (int) $asset->id,
                'title' => \Joomla\String\StringHelper::substr((string) $this->title, 0, 100),
            ], 'id');
        }
        try {
            $success = parent::store($updateNulls);
            if ($success && $prepared) {
                $this->getDatabase()->updateObject('#__assets', (object) [
                    'id' => $this->preparedAsset,
                    'name' => $this->_getAssetName(),
                ], 'id');
            }
            return $success;
        } finally {
            $this->_trackAssets = $tracking;
        }
    }

    protected function _getAssetName()
    {
        return 'com_jlms.pathway.' . (int) $this->id;
    }

    protected function _getAssetTitle()
    {
        return (string) $this->title;
    }

    protected function _getAssetParentId(?Table $table = null, $id = null)
    {
        $query = $this->getDatabase()->createQuery()->select('asset_id')->from('#__categories')
            ->where('id = :category')->bind(':category', (int) $this->catid, ParameterType::INTEGER);
        return (int) $this->getDatabase()->setQuery($query)->loadResult() ?: parent::_getAssetParentId($table, $id);
    }
}
