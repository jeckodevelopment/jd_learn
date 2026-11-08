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
        $this->setState('list.limit', max(1, min(100, (int) $this->state->get('list.limit', 20))));
    }

    protected function getListQuery()
    {
        $actor = new User((int) $this->getCurrentUser()->id);
        $actor->clearAccessRights();
        if (!$actor->id || $actor->block || !$actor->authorise('core.manage', 'com_jlms')
            || !$actor->authorise('jlms.pathways.manage', 'com_jlms')) {
            throw new \RuntimeException('JERROR_ALERTNOAUTHOR', 403);
        }

        return $this->getDatabase()->createQuery()->select('p.id, p.title, p.catid, p.state, p.revision, p.modified_at')
            ->from('#__jlms_pathways AS p')->order('p.title, p.id');
    }
}
