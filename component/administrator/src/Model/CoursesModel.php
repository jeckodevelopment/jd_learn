<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Model;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess;

final class CoursesModel extends ListModel
{
    private const ORDERS = ['a.id','a.title','a.state','category_title','access_title','author_name','a.language','a.modified_at'];
    private const FILTERS = ['search','published','category_id','access','author_id','language','checked_out'];

    public function __construct($config = [], $factory = null)
    {
        $config['filter_fields'] = array_merge(self::ORDERS, self::FILTERS);
        parent::__construct($config, $factory);
    }

    protected function populateState($ordering = 'a.id', $direction = 'DESC')
    {
        $input = Factory::getApplication()->getInput();
        if ($input->get('filter', null, 'array') !== null) {
            $filters = array_intersect_key($input->get('filter', [], 'array'), array_flip(self::FILTERS));
            foreach ($filters as &$value) { if (!is_scalar($value)) { $value = '_invalid_'; } }
            unset($value); $input->set('filter', $filters);
        }
        if ($input->get('list', null, 'array') !== null) {
            $list = array_intersect_key($input->get('list', [], 'array'), array_flip(['fullordering','ordering','direction','limit']));
            foreach ($list as $key=>&$value) {
                if (!is_scalar($value)) { $value = $key === 'limit' ? 20 : ($key === 'fullordering' ? 'a.id DESC' : ''); }
            }
            unset($value); $input->set('list', $list);
        }
        // Native Search Tools and session state, including reset to page one on filter changes.
        foreach (self::FILTERS as $name) {
            $this->getUserStateFromRequest($this->context . '.filter.' . $name, 'filter_' . $name, '', 'string');
        }
        parent::populateState($ordering, $direction);
        $limit = (int) $this->state->get('list.limit', 20);
        $this->setState('list.limit', $limit === 0 ? 100 : max(1, min(100, $limit)));
        $this->setState('list.start', max(0, (int) $this->state->get('list.start', 0)));
    }

    private function scope(): array
    {
        $actor = CourseEditorAccess::fresh($this->getCurrentUser());
        $this->setCurrentUser($actor);
        return CourseEditorAccess::editableIds($this->getDatabase(), $actor);
    }

    protected function getStoreId($id = '')
    {
        $scope = $this->scope();
        $filters = array_map(fn(string $name): mixed => $this->getState('filter.' . $name), self::FILTERS);
        return parent::getStoreId($id . ':' . (int) $this->getCurrentUser()->id . ':'
            . hash('sha256', serialize([$scope, $filters])));
    }

    protected function getListQuery()
    {
        $db = $this->getDatabase(); $ids = $this->scope();
        $q = $db->createQuery()->select('a.id,a.title,a.alias,a.catid,a.state,a.revision,a.created_by,a.modified_at,a.language,a.content_access,a.checked_out,a.checked_out_time')
            ->select('cat.title AS category_title,cat.lft AS category_lft,access_level.title AS access_title,author.name AS author_name,editor.name AS editor,lang.title AS language_title')
            ->from('#__jlms_courses AS a')->leftJoin('#__categories AS cat ON cat.id=a.catid')
            ->leftJoin('#__viewlevels AS access_level ON access_level.id=a.content_access')
            ->leftJoin('#__users AS author ON author.id=a.created_by')->leftJoin('#__users AS editor ON editor.id=a.checked_out')
            ->leftJoin('#__languages AS lang ON lang.lang_code=a.language');
        if ($ids) { $q->whereIn('a.id', $ids); } else { $q->where('1=0'); }
        foreach (self::FILTERS as $name) {
            $value = $this->getState('filter.' . $name, '');
            if ($value === null || $value === '') { continue; }
            if (!is_scalar($value)) { $q->where('1=0'); continue; }
            $value = trim((string) $value);
            if ($value === '') { continue; }
            if ($name === 'search') {
                if (preg_match('//u', $value) !== 1 || mb_strlen($value) > 200) { $q->where('1=0'); continue; }
                if (preg_match('/^id:([1-9][0-9]{0,9})$/iD', $value, $match)) {
                    $id = (int) $match[1]; $q->where('a.id=:search_id')->bind(':search_id', $id, ParameterType::INTEGER);
                } else {
                    $search = '%' . $db->escape($value, true) . '%';
                    $q->where('(a.title LIKE :title_search OR a.alias LIKE :alias_search)')->bind(':title_search', $search)->bind(':alias_search', $search);
                }
            } elseif ($name === 'published') {
                if (!in_array($value, ['0','1'], true)) { $q->where('1=0'); continue; }
                $state = (int) $value; $q->where('a.state=:state')->bind(':state', $state, ParameterType::INTEGER);
            } elseif ($name === 'language') {
                if ($value !== '*' && !preg_match('/^[a-zA-Z]{2,3}(?:-[a-zA-Z0-9]{2,8}){1,2}$/D', $value)) { $q->where('1=0'); continue; }
                $language = $value; $q->where('a.language=:language')->bind(':language', $language);
            } elseif ($name === 'checked_out') {
                if ($value === '1') { $q->where('a.checked_out IS NOT NULL AND a.checked_out<>0'); }
                elseif ($value === '0') { $q->where('(a.checked_out IS NULL OR a.checked_out=0)'); }
                else { $q->where('1=0'); }
            } else {
                if (!preg_match('/^[1-9][0-9]{0,9}$/D', $value)) { $q->where('1=0'); continue; }
                if ($name === 'category_id') {
                    $category = (int) $value;
                    $q->innerJoin('#__categories AS selected_category ON selected_category.id=:category')
                        ->where('cat.lft>=selected_category.lft AND cat.rgt<=selected_category.rgt')->bind(':category', $category, ParameterType::INTEGER);
                } elseif ($name === 'author_id') {
                    $author = (int) $value; $q->where('a.created_by=:author')->bind(':author', $author, ParameterType::INTEGER);
                } else {
                    $access = (int) $value; $q->where('a.content_access=:access')->bind(':access', $access, ParameterType::INTEGER);
                }
            }
        }
        $order = $this->getState('list.ordering', 'a.id');
        $direction = strtoupper((string) $this->getState('list.direction', 'DESC'));
        if (!is_string($order) || !in_array($order, self::ORDERS, true)) { $order = 'a.id'; }
        if (!in_array($direction, ['ASC','DESC'], true)) { $direction = 'DESC'; }
        $q->order($db->quoteName($order) . ' ' . $direction);
        if ($order !== 'a.id') { $q->order('a.id DESC'); }
        return $q;
    }
}
