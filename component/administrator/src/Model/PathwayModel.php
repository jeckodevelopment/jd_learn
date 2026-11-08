<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Model;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\User\User;
use Joomla\Database\ParameterType;

final class PathwayModel extends AdminModel
{
    public $typeAlias = 'com_jlms.pathway';

    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_jlms.pathway', JPATH_ADMINISTRATOR . '/components/com_jlms/forms/pathway.xml',
            ['control' => 'jform', 'load_data' => $loadData]);
    }

    protected function loadFormData()
    {
        return Factory::getApplication()->getUserState('com_jlms.edit.pathway.data', []) ?: $this->getItem();
    }

    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);
        if ($item && !empty($item->id)) {
            $query = $this->getDatabase()->createQuery()->select('course_id, ordering')->from('#__jlms_pathway_courses')
                ->where('pathway_id = :pathway')->bind(':pathway', (int) $item->id, ParameterType::INTEGER)->order('ordering, course_id');
            $item->courses = $this->getDatabase()->setQuery($query, 0, 100)->loadAssocList();
        } else {
            $item->courses = [];
        }
        return $item;
    }

    public function save($input)
    {
        $db = $this->getDatabase();
        $db->transactionStart();
        try {
            $db->setQuery(\"SELECT id FROM #__assets WHERE name='com_jlms' FOR UPDATE\")->loadResult()
                ?: throw new \DomainException('JERROR_ALERTNOAUTHOR');
            $actor = new User((int) $this->getCurrentUser()->id);
            $actor->clearAccessRights();
            $this->setCurrentUser($actor);
            if (!$actor->id || $actor->block || !$actor->authorise('core.manage', 'com_jlms')
                || !$actor->authorise('jlms.pathways.manage', 'com_jlms')) {
                throw new \DomainException('JERROR_ALERTNOAUTHOR');
            }

            $allowed = ['id', 'revision', 'title', 'alias', 'description', 'catid', 'access', 'language', 'state', 'courses'];
            $data = array_intersect_key($input, array_flip($allowed));
            foreach (['id', 'revision', 'catid', 'access', 'state'] as $key) {
                if (isset($data[$key]) && ((!is_int($data[$key]) && !is_string($data[$key]))
                    || !preg_match('/^[0-9]{1,10}$/D', (string) $data[$key]))) {
                    throw new \InvalidArgumentException('COM_JLMS_PATHWAY_INVALID');
                }
            }

            $id = (int) ($data['id'] ?? 0);
            $record = null;
            if ($id) {
                $query = $db->createQuery()->select('*')->from('#__jlms_pathways')
                    ->where('id = :id')->bind(':id', $id, ParameterType::INTEGER);
                $query->setQuery((string) $query . ' FOR UPDATE');
                $record = $db->setQuery($query)->loadObject() ?: throw new \DomainException('JERROR_LAYOUT_PAGE_NOT_FOUND');
                if (!$actor->authorise('core.edit', 'com_jlms.pathway.' . $id)) {
                    throw new \DomainException('JERROR_ALERTNOAUTHOR');
                }
                if ((int) ($data['revision'] ?? 0) !== (int) $record->revision) {
                    throw new \DomainException('COM_JLMS_REVISION_CONFLICT');
                }
            } elseif (!$actor->authorise('core.create', 'com_jlms')) {
                throw new \DomainException('JERROR_ALERTNOAUTHOR');
            }

            $categoryId = (int) ($data['catid'] ?? $record->catid ?? 0);
            $categoryQuery = $db->createQuery()->select('id')->from('#__categories')
                ->where(\"extension='com_jlms.pathway' AND published <> -2\")
                ->where('id = :category')->bind(':category', $categoryId, ParameterType::INTEGER);
            if (!$categoryId || !$db->setQuery($categoryQuery)->loadResult()) {
                throw new \InvalidArgumentException('COM_JLMS_PATHWAY_INVALID');
            }
            if ((!$record || $categoryId !== (int) $record->catid)
                && !$actor->authorise('core.create', 'com_jlms.pathway.category.' . $categoryId)) {
                throw new \DomainException('JERROR_ALERTNOAUTHOR');
            }
            if (isset($data['state']) && (int) $data['state'] !== (int) ($record->state ?? 0)
                && !$actor->authorise('core.edit.state', $id ? 'com_jlms.pathway.' . $id : 'com_jlms')) {
                throw new \DomainException('JERROR_ALERTNOAUTHOR');
            }

            foreach (['title', 'alias', 'description', 'language'] as $key) {
                if (isset($data[$key]) && (!is_string($data[$key]) || preg_match('//u', $data[$key]) !== 1)) {
                    throw new \InvalidArgumentException('COM_JLMS_PATHWAY_INVALID');
                }
            }
            $data['description'] = ComponentHelper::filterText((string) ($data['description'] ?? $record->description ?? ''));
            $data['catid'] = $categoryId;
            $data['access'] = (int) ($data['access'] ?? $record->access ?? 1);
            $data['language'] = (string) ($data['language'] ?? $record->language ?? '*');
            $data['state'] = (int) ($data['state'] ?? $record->state ?? 0);
            $data['params_json'] = (string) ($record->params_json ?? '{}');
            $courses = $this->normalizeCourses($data['courses'] ?? []);
            unset($data['courses'], $data['revision']);

            $table = $this->getTable('Pathway');
            if ($record) {
                foreach ((array) $record as $key => $value) {
                    $table->$key = $value;
                }
            }
            if (!$table->bind($data) || !$table->check() || !$table->store()) {
                throw new \RuntimeException('COM_JLMS_SAVE_FAILED');
            }
            $pathwayId = (int) $table->id;

            $db->setQuery($db->createQuery()->delete('#__jlms_pathway_courses')
                ->where('pathway_id = :pathway')->bind(':pathway', $pathwayId, ParameterType::INTEGER))->execute();
            foreach ($courses as $ordering => $courseId) {
                $db->setQuery($db->createQuery()->insert('#__jlms_pathway_courses')
                    ->columns(['pathway_id', 'course_id', 'ordering'])
                    ->values(implode(', ', [$pathwayId, $courseId, $ordering])))->execute();
            }

            $db->transactionCommit();
            return true;
        } catch (\Throwable $error) {
            $db->transactionRollback();
            $known = ['JERROR_ALERTNOAUTHOR', 'JERROR_LAYOUT_PAGE_NOT_FOUND', 'COM_JLMS_REVISION_CONFLICT', 'COM_JLMS_PATHWAY_INVALID'];
            $this->setError(Text::_(in_array($error->getMessage(), $known, true) ? $error->getMessage() : 'COM_JLMS_SAVE_FAILED'));
            return false;
        }
    }

    private function normalizeCourses(mixed $rows): array
    {
        if (!is_array($rows) || count($rows) > 100) {
            throw new \InvalidArgumentException('COM_JLMS_PATHWAY_INVALID');
        }
        $normalized = [];
        foreach ($rows as $index => $row) {
            if (!is_array($row) || !preg_match('/^[1-9][0-9]{0,9}$/D', (string) ($row['course_id'] ?? ''))
                || !preg_match('/^[0-9]{1,4}$/D', (string) ($row['ordering'] ?? $index))) {
                throw new \InvalidArgumentException('COM_JLMS_PATHWAY_INVALID');
            }
            $courseId = (int) $row['course_id'];
            if (isset($normalized[$courseId])) {
                throw new \InvalidArgumentException('COM_JLMS_PATHWAY_INVALID');
            }
            $normalized[$courseId] = (int) ($row['ordering'] ?? $index);
        }
        asort($normalized, SORT_NUMERIC);
        $courseIds = array_keys($normalized);
        if ($courseIds !== []) {
            $query = $this->getDatabase()->createQuery()->select('id')->from('#__jlms_courses')
                ->whereIn('id', $courseIds);
            if (count($this->getDatabase()->setQuery($query)->loadColumn()) !== count($courseIds)) {
                throw new \InvalidArgumentException('COM_JLMS_PATHWAY_INVALID');
            }
        }
        return $courseIds;
    }

    public function delete(&$pks)
    {
        $this->setError(Text::_('COM_JLMS_DELETE_NOT_AVAILABLE'));
        return false;
    }

    public function publish(&$pks, $value = 1)
    {
        $this->setError(Text::_('COM_JLMS_DELETE_NOT_AVAILABLE'));
        return false;
    }
}
