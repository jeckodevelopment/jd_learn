<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\User\User;
use Joomla\Database\ParameterType;

final class PathwaysModel extends ListModel
{
    private const FILTERS = ['search', 'pathway_state', 'category_id', 'access', 'language'];
    private const ORDERS = [
        'p.id',
        'p.title',
        'p.state',
        'category_title',
        'access_title',
        'p.language',
        'p.revision',
        'p.modified_at',
    ];

    public function __construct($config = [], $factory = null)
    {
        $config['filter_fields'] = array_merge(self::FILTERS, self::ORDERS);
        parent::__construct($config, $factory);
    }

    protected function populateState($ordering = 'p.title', $direction = 'ASC')
    {
        $input = Factory::getApplication()->getInput();
        $filters = $input->get('filter', null, 'array');
        if ($filters !== null) {
            $filters = array_intersect_key($filters, array_flip(self::FILTERS));
            foreach ($filters as &$value) {
                if (!is_scalar($value)) {
                    $value = '_invalid_';
                }
            }
            unset($value);
            $input->set('filter', $filters);
        } elseif ($input->get('filter_pathway_state', null) !== null) {
            // Preserve links from the pre-Search-Tools trash/restore actions.
            $input->set('filter', [
                'pathway_state' => $input->get('filter_pathway_state', 'active', 'cmd'),
            ]);
        }

        if ($input->get('list', null, 'array') !== null) {
            $list = array_intersect_key($input->get('list', [], 'array'), array_flip([
                'fullordering',
                'ordering',
                'direction',
                'limit',
            ]));
            foreach ($list as $key => &$value) {
                if (!is_scalar($value)) {
                    $value = $key === 'limit' ? 20 : '';
                }
            }
            unset($value);
            $input->set('list', $list);
        }

        foreach (self::FILTERS as $name) {
            $default = $name === 'pathway_state' ? 'active' : '';
            $this->getUserStateFromRequest(
                $this->context . '.filter.' . $name,
                'filter_' . $name,
                $default,
                'string'
            );
        }

        parent::populateState($ordering, $direction);
        $state = (string) $this->state->get('filter.pathway_state', 'active');
        $this->setState('filter.pathway_state', $state === 'trashed' ? 'trashed' : 'active');

        $limit = (int) $this->state->get('list.limit', 20);
        $this->setState('list.limit', $limit === 0 ? 100 : max(1, min(100, $limit)));
        $this->setState('list.start', max(0, (int) $this->state->get('list.start', 0)));
    }

    protected function getListQuery()
    {
        $actor = new User((int) $this->getCurrentUser()->id);
        $actor->clearAccessRights();
        if (
            !$actor->id
            || $actor->block
            || !$actor->authorise('core.manage', 'com_jlms')
            || !$actor->authorise('jlms.pathways.manage', 'com_jlms')
        ) {
            throw new \RuntimeException('JERROR_ALERTNOAUTHOR', 403);
        }

        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select(
                'p.id, p.title, p.alias, p.catid, p.state, p.access, p.language, p.revision, p.modified_at, '
                . 'cat.title AS category_title, vl.title AS access_title'
            )
            ->from('#__jlms_pathways AS p')
            ->leftJoin("#__categories AS cat ON cat.id = p.catid AND cat.extension = 'com_jlms.pathway'")
            ->leftJoin('#__viewlevels AS vl ON vl.id = p.access');

        if ((string) $this->getState('filter.pathway_state', 'active') === 'trashed') {
            $query->where('p.state = -2');
        } else {
            $query->where('p.state <> -2');
        }

        $search = trim((string) $this->getState('filter.search', ''));
        if ($search !== '') {
            $pattern = '%' . $db->escape(substr($search, 0, 200), true) . '%';
            $query->where(
                '(p.title LIKE :search_title OR p.alias LIKE :search_alias OR CAST(p.id AS CHAR) = :search_id)'
            );
            $query->bind(':search_title', $pattern);
            $query->bind(':search_alias', $pattern);
            $query->bind(':search_id', trim($search));
        }

        $filters = [
            'category_id' => ['p.catid', ParameterType::INTEGER],
            'access' => ['p.access', ParameterType::INTEGER],
        ];
        foreach ($filters as $name => [$column, $type]) {
            $value = (string) $this->getState('filter.' . $name, '');
            if ($value !== '') {
                $id = preg_match('/^[1-9][0-9]{0,9}$/D', $value) ? (int) $value : -1;
                $query->where($column . ' = :' . $name);
                $query->bind(':' . $name, $id, $type);
            }
        }

        $language = (string) $this->getState('filter.language', '');
        if ($language !== '') {
            $query->where('p.language = :language');
            $query->bind(':language', $language);
        }

        $ordering = (string) $this->getState('list.ordering', 'p.title');
        if (!in_array($ordering, self::ORDERS, true)) {
            $ordering = 'p.title';
        }
        $direction = strtoupper((string) $this->getState('list.direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        return $query->order($ordering . ' ' . $direction)->order('p.id ASC');
    }
}
