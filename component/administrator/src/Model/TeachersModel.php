<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess;

final class TeachersModel extends ListModel
{
    private const FILTERS = ['search', 'published', 'language'];
    private const ORDERS = ['a.id', 'a.title', 'a.state', 'a.created_by', 'a.language', 'a.modified_at'];

    public function __construct($config = [], $factory = null)
    {
        $config['filter_fields'] = array_merge(self::FILTERS, self::ORDERS);
        parent::__construct($config, $factory);
    }

    protected function populateState($ordering = 'a.title', $direction = 'ASC')
    {
        $input = Factory::getApplication()->getInput();
        if ($input->get('filter', null, 'array') !== null) {
            $filters = array_intersect_key($input->get('filter', [], 'array'), array_flip(self::FILTERS));
            foreach ($filters as &$value) { if (!is_scalar($value)) { $value = '_invalid_'; } }
            unset($value); $input->set('filter', $filters);
        }
        if ($input->get('list', null, 'array') !== null) {
            $list = array_intersect_key($input->get('list', [], 'array'), array_flip(['fullordering', 'ordering', 'direction', 'limit']));
            foreach ($list as $key => &$value) { if (!is_scalar($value)) { $value = $key === 'limit' ? 20 : ''; } }
            unset($value); $input->set('list', $list);
        }
        foreach (self::FILTERS as $name) {
            $this->getUserStateFromRequest($this->context . '.filter.' . $name, 'filter_' . $name, '', 'string');
        }
        parent::populateState($ordering, $direction);
        $limit = (int) $this->state->get('list.limit', 20);
        $this->setState('list.limit', $limit === 0 ? 100 : max(1, min(100, $limit)));
        $this->setState('list.start', max(0, (int) $this->state->get('list.start', 0)));
    }

    protected function getListQuery()
    {
        $fresh = new \Joomla\CMS\User\User((int) $this->getCurrentUser()->id); $fresh->clearAccessRights(); $this->setCurrentUser($fresh);
        if (!$fresh->id || $fresh->block || !$fresh->authorise('core.manage', 'com_jlms')) { throw new \RuntimeException('JERROR_ALERTNOAUTHOR', 403); }
        $db = $this->getDatabase();
        $courseIds = array_map('intval', CourseEditorAccess::editableIds($db, $fresh));
        $courseCounts = $db->createQuery()->select('teacher_id, COUNT(DISTINCT course_id) AS course_count')
            ->from('#__jlms_course_teachers');
        if ($courseIds) { $courseCounts->where('course_id IN (' . implode(',', $courseIds) . ')'); }
        else { $courseCounts->where('1=0'); }
        $courseCounts->group('teacher_id');
        $query = $db->createQuery()->select('a.id, a.title, a.state, a.revision, a.created_by, a.modified_at, a.language, author.name AS author_name, lang.title AS language_title, COALESCE(teacher_courses.course_count, 0) AS course_count')
            ->from($db->quoteName('#__jlms_teachers', 'a'))
            ->leftJoin($db->quoteName('#__users', 'author') . ' ON author.id = a.created_by')
            ->leftJoin($db->quoteName('#__languages', 'lang') . ' ON lang.lang_code = a.language')
            ->leftJoin('(' . (string) $courseCounts . ') AS teacher_courses ON teacher_courses.teacher_id = a.id');
        if (!$fresh->authorise('jlms.teachers.manage', 'com_jlms')) {
            $id = (int) $fresh->id;
            $query->where('(a.created_by = :actor OR a.user_id = :linked)')->bind(':actor', $id, ParameterType::INTEGER)->bind(':linked', $id, ParameterType::INTEGER);
        }
        foreach (self::FILTERS as $name) {
            $value = trim((string) $this->getState('filter.' . $name, ''));
            if ($value === '') { continue; }
            if ($name === 'search') {
                if (preg_match('//u', $value) !== 1 || mb_strlen($value) > 200) { $query->where('1=0'); continue; }
                if (preg_match('/^id:([1-9][0-9]{0,9})$/iD', $value, $match)) {
                    $id = (int) $match[1]; $query->where('a.id = :search_id')->bind(':search_id', $id, ParameterType::INTEGER);
                } else {
                    $search = '%' . $db->escape($value, true) . '%';
                    $query->where('(a.title LIKE :title OR a.alias LIKE :alias)')->bind(':title', $search)->bind(':alias', $search);
                }
            } elseif ($name === 'published') {
                if (!in_array($value, ['0', '1'], true)) { $query->where('1=0'); continue; }
                $state = (int) $value; $query->where('a.state = :state')->bind(':state', $state, ParameterType::INTEGER);
            } else {
                if ($value !== '*' && !preg_match('/^[a-zA-Z]{2,3}(?:-[a-zA-Z0-9]{2,8}){1,2}$/D', $value)) { $query->where('1=0'); continue; }
                $query->where('a.language = :language')->bind(':language', $value);
            }
        }
        $order = $this->getState('list.ordering', 'a.title');
        $direction = strtoupper((string) $this->getState('list.direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        if (!in_array($order, self::ORDERS, true)) { $order = 'a.title'; }
        return $query->order($db->quoteName($order) . ' ' . $direction . ', a.id ASC');
    }
}
