<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class TeacherTable extends Table
{
    protected $_supportNullValue = true;
    public function __construct(DatabaseInterface $db) { parent::__construct('#__jlms_teachers', 'id', $db); $this->setColumnAlias('published', 'state'); }

    public function check()
    {
        $this->title = trim((string) $this->title);
        $this->alias = \Joomla\CMS\Application\ApplicationHelper::stringURLSafe((string) ($this->alias ?: $this->title));
        if ($this->title === '' || mb_strlen($this->title) > 255 || preg_match('//u', $this->title) !== 1 || $this->alias === '' || strlen($this->alias) > 180
            || strlen((string) $this->bio) > 65535 || preg_match('//u', (string) $this->bio) !== 1 || !in_array((int) $this->state, [0, 1], true)) { $this->setError('COM_JLMS_TEACHER_INVALID'); return false; }
        $db = $this->getDatabase();
        $catid = (int) $this->catid; $access = (int) $this->access; $language = (string) $this->language;
        $q = $db->createQuery()->select('id')->from('#__categories')->where("extension='com_jlms.teacher' AND published<>-2")->where('id=:id')->bind(':id', $catid, ParameterType::INTEGER);
        if (!$db->setQuery($q)->loadResult()) { $this->setError('COM_JLMS_TEACHER_INVALID'); return false; }
        $q = $db->createQuery()->select('id')->from('#__viewlevels')->where('id=:id')->bind(':id', $access, ParameterType::INTEGER);
        if (!$db->setQuery($q)->loadResult()) { $this->setError('COM_JLMS_INVALID_ACCESS'); return false; }
        if ($language !== '*') {
            $q = $db->createQuery()->select('lang_id')->from('#__languages')->where('lang_code=:language')->bind(':language', $language);
            if (!$db->setQuery($q)->loadResult()) { $this->setError('COM_JLMS_TEACHER_INVALID'); return false; }
        }
        return parent::check();
    }

    public function store($updateNulls = true)
    {
        $now = Factory::getDate()->toSql(); $actor = (int) Factory::getApplication()->getIdentity()->id;
        if (!$this->id) { $this->revision = 1; $this->created_at = $now; $this->created_by = $actor; }
        else { $this->revision = (int) $this->revision + 1; }
        $this->modified_at = $now; $this->modified_by = $actor;
        return parent::store($updateNulls);
    }
    protected function _getAssetName() { return 'com_jlms.teacher.' . (int) $this->id; }
    protected function _getAssetTitle() { return $this->title; }
    protected function _getAssetParentId(?Table $table = null, $id = null)
    {
        $catid = (int) $this->catid;
        $q = $this->getDatabase()->createQuery()->select('asset_id')->from('#__categories')->where('id=:id')->bind(':id', $catid, ParameterType::INTEGER);
        return (int) $this->getDatabase()->setQuery($q)->loadResult() ?: parent::_getAssetParentId($table, $id);
    }
}
