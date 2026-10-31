<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Model;

use Joomla\CMS\MVC\Model\ListModel;

final class CoursesModel extends ListModel
{
    protected function populateState($ordering = 'id', $direction = 'DESC')
    {
        parent::populateState($ordering, $direction);
        $this->setState('list.limit', max(1, min(100, (int) $this->state->get('list.limit', 20))));
    }

    protected function getListQuery()
    {
        $db = $this->getDatabase();
        // All administration reads require core.manage via Joomla's component dispatcher.
        // Record visibility is narrowed below instead of listing another author's drafts.
        $query = $db->createQuery()->select('id, title, catid, state, revision, created_by, modified_at')
            ->from($db->quoteName('#__jlms_courses'))->order($db->quoteName('id') . ' DESC');
        $user = $this->getCurrentUser();
        if (!$user->authorise('core.edit', 'com_jlms')) {
            $userId = (int) $user->id;
            $query->where($db->quoteName('created_by') . ' = :actor')->bind(':actor', $userId, \Joomla\Database\ParameterType::INTEGER);
        }
        return $query;
    }
}
