<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Model;

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\ParameterType;

final class CourseModel extends AdminModel
{
    public $typeAlias = 'com_jlms.course';

    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_jlms.course', 'course', ['control' => 'jform', 'load_data' => $loadData]);
    }

    protected function loadFormData()
    {
        return Factory::getApplication()->getUserState('com_jlms.edit.course.data', []) ?: $this->getItem();
    }

    public function save($data)
    {
        $db = $this->getDatabase();
        $user = $this->getCurrentUser();
        $allowed = ['id', 'title', 'alias', 'catid', 'language', 'catalog_access', 'content_access', 'enrollment_mode', 'summary', 'revision'];
        $data = array_intersect_key($data, array_flip($allowed));
        $id = (int) ($data['id'] ?? 0);
        $expected = (int) ($data['revision'] ?? 0);
        unset($data['revision']);
        $db->transactionStart();
        try {
            if ($id > 0) {
                $q = $db->createQuery()->select('*')->from($db->quoteName('#__jlms_courses'))
                    ->where($db->quoteName('id') . ' = :id')->bind(':id', $id, ParameterType::INTEGER)->forUpdate();
                $record = $db->setQuery($q)->loadObject();
                if (!$record) {
                    throw new \DomainException('JERROR_LAYOUT_PAGE_NOT_FOUND');
                }
                $asset = 'com_jlms.course.' . $id;
                $canEdit = $user->authorise('core.edit', $asset)
                    || ((int) $record->created_by === (int) $user->id && $user->authorise('core.edit.own', $asset));
                if (!$canEdit) {
                    throw new \DomainException('JERROR_ALERTNOAUTHOR');
                }
                if ((int) $record->revision !== $expected) {
                    throw new \DomainException('COM_JLMS_REVISION_CONFLICT');
                }
                // Foundation edits drafts only; never mutate a course already published/versioned by later increments.
                if ((int) $record->state !== 0 || $record->current_version_id !== null) {
                    throw new \DomainException('COM_JLMS_VERSION_IMMUTABLE');
                }
                if ((int) $record->catid !== (int) ($data['catid'] ?? 0)
                    && !$user->authorise('core.create', 'com_jlms.course.category.' . (int) $data['catid'])) {
                    throw new \DomainException('JERROR_ALERTNOAUTHOR');
                }
            } elseif (!$user->authorise('core.create', 'com_jlms.course.category.' . (int) ($data['catid'] ?? 0))) {
                throw new \DomainException('JERROR_ALERTNOAUTHOR');
            }
            if (!parent::save($data)) {
                $db->transactionRollback();
                $this->setError(Text::_('COM_JLMS_SAVE_FAILED'));
                return false;
            }
            $db->transactionCommit();
            return true;
        } catch (\Throwable $error) {
            $db->transactionRollback();
            $known = ['JERROR_LAYOUT_PAGE_NOT_FOUND', 'JERROR_ALERTNOAUTHOR', 'COM_JLMS_REVISION_CONFLICT', 'COM_JLMS_VERSION_IMMUTABLE'];
            $this->setError(Text::_(in_array($error->getMessage(), $known, true) ? $error->getMessage() : 'COM_JLMS_SAVE_FAILED'));
            return false;
        }
    }

    public function delete(&$pks)
    {
        $this->setError(Text::_('COM_JLMS_DELETE_NOT_AVAILABLE'));
        return false;
    }

    public function publish(&$pks, $value = 1)
    {
        $this->setError(Text::_('COM_JLMS_PUBLICATION_NOT_AVAILABLE'));
        return false;
    }
}
