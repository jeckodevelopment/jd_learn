<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Model;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\User\User;

final class PathwaysModel extends ListModel
{
    protected function populateState($ordering = 'p.title', $direction = 'ASC')
    {
        parent::populateState($ordering, $direction);
        $filter = (string) \Joomla\CMS\Factory::getApplication()->getInput()
            ->get('filter_pathway_state', 'active', 'cmd');
        $this->setState('filter.pathway_state', $filter === 'trashed' ? 'trashed' : 'active');
        $this->setState('list.limit', max(1, min(100, (int) $this->state->get('list.limit', 20))));
    }

    protected function getListQuery()
    {
        $actor = new User((int) $this->getCurrentUser()->id);
        $actor->clearAccessRights();
        if (
            !$actor->id || $actor->block || !$actor->authorise('core.manage', 'com_jlms')
            || !$actor->authorise('jlms.pathways.manage', 'com_jlms')
        ) {
            throw new \RuntimeException('JERROR_ALERTNOAUTHOR', 403);
        }

        $state = (string) $this->getState('filter.pathway_state', 'active');
        return $this->getDatabase()->createQuery()->select('p.id, p.title, p.catid, p.state, p.revision, p.modified_at')
            ->from('#__jlms_pathways AS p')
            ->where($state === 'trashed' ? 'p.state = -2' : 'p.state <> -2')
            ->order('p.title, p.id');
    }
}
