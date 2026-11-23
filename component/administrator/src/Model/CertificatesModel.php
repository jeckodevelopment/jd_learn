<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Pagination\Pagination;

/**
 * Native Joomla Search Tools state and pagination backed by the existing
 * ACL-protected/audited certificate service. Never query certificates outside
 * the service's authorization boundary.
 */
final class CertificatesModel extends ListModel
{
    private const ORDERS = ['d.issued_at', 'd.serial', 'certificate_state', 'student_name', 'course_title', 'c.id'];
    private const FILTERS = ['search', 'status'];
    private ?array $result = null;

    public function __construct($config = [], $factory = null)
    {
        $config['filter_fields'] = array_merge(self::ORDERS, self::FILTERS);
        parent::__construct($config, $factory);
    }

    protected function populateState($ordering = 'd.issued_at', $direction = 'DESC')
    {
        $input = Factory::getApplication()->getInput();
        if ($input->get('filter', null, 'array') !== null) {
            $filters = array_intersect_key($input->get('filter', [], 'array'), array_flip(self::FILTERS));
            foreach ($filters as &$value) {
                if (!is_scalar($value)) {
                    $value = '';
                }
            }
            unset($value);
            $input->set('filter', $filters);
        }
        if ($input->get('list', null, 'array') !== null) {
            $list = array_intersect_key($input->get('list', [], 'array'), array_flip(['fullordering',
                'ordering', 'direction', 'limit']));
            foreach ($list as $key => &$value) {
                if (!is_scalar($value)) {
                    $value = $key === 'limit' ? 20 : '';
                }
            }
            unset($value);
            $input->set('list', $list);
        }
        foreach (self::FILTERS as $name) {
            $this->getUserStateFromRequest($this->context . '.filter.' . $name, 'filter_' . $name, '', 'string');
        }
        parent::populateState($ordering, $direction);
        // populateState() is itself called by getState() until Joomla marks
        // initialization complete. Calling getState() here causes recursion
        // and a fatal error when opening the Attestati administrator view.
        $limit = (int) $this->state->get('list.limit', 20);
        $this->setState('list.limit', $limit === 0 ? 100 : max(1, min(100, $limit)));
        $this->setState('list.start', max(0, (int) $this->state->get('list.start', 0)));
    }

    private function listing(): array
    {
        if ($this->result !== null) {
            return $this->result;
        }
        $app = Factory::getApplication();
        return $this->result = $app->bootComponent('com_jlms')->getCertificateService()->adminList(
            $app->getIdentity(),
            [
                'q' => (string) $this->getState('filter.search', ''),
                'status' => (string) $this->getState('filter.status', ''),
                'offset' => (int) $this->getState('list.start', 0),
                'limit' => (int) $this->getState('list.limit', 20),
                'ordering' => (string) $this->getState('list.ordering', 'd.issued_at'),
                'direction' => (string) $this->getState('list.direction', 'DESC'),
            ]
        );
    }

    public function getItems()
    {
        return $this->listing()['items'];
    }

    public function getPagination()
    {
        $data = $this->listing();
        return new Pagination($data['total'], $data['offset'], $data['limit']);
    }
}
