<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Model;

use Joomla\CMS\MVC\Model\ListModel;

final class TeachersModel extends ListModel
{
    protected function populateState($ordering = 'id', $direction = 'DESC')
    {
        parent::populateState($ordering, $direction);
        $this->setState('list.limit', max(1, min(100, (int) $this->state->get('list.limit', 20))));
    }

    protected function getListQuery()
    {
        $fresh = new \Joomla\CMS\User\User((int) $this->getCurrentUser()->id); $fresh->clearAccessRights(); $this->setCurrentUser($fresh);
        if (!$fresh->id || $fresh->block || !$fresh->authorise('core.manage', 'com_jlms')) { throw new \RuntimeException('JERROR_ALERTNOAUTHOR',403); }
        $db = $this->getDatabase();
        // All administration reads require core.manage via Joomla's component dispatcher.
        // Record visibility is narrowed below instead of listing another author's drafts.
        $query = $db->createQuery()->select('id, title, catid, state, revision, created_by, modified_at')
            ->from($db->quoteName('#__jlms_teachers'))->order($db->quoteName('id') . ' DESC');
        $user = $this->getCurrentUser();
        if (!$user->authorise('jlms.teachers.manage', 'com_jlms')) {
            $userId = (int) $user->id;
            $query->where('(created_by = :actor OR user_id = :linked)')->bind(':actor', $userId, \Joomla\Database\ParameterType::INTEGER)->bind(':linked', $userId, \Joomla\Database\ParameterType::INTEGER);
        }
        return $query;
    }
}
