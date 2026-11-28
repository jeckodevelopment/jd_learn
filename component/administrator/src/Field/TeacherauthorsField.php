<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;

final class TeacherauthorsField extends \Joomla\CMS\Form\Field\ListField
{
    protected $type = 'Teacherauthors';

    protected function getOptions()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $actor = new User((int) Factory::getApplication()->getIdentity()->id);
        $actor->clearAccessRights();
        $options = parent::getOptions();
        if (!$actor->id || $actor->block || !$actor->authorise('core.manage', 'com_jlms')) {
            return $options;
        }

        $query = $db->createQuery()->select('DISTINCT u.id AS value, u.name AS text')
            ->from('#__users AS u')
            ->innerJoin('#__jlms_teachers AS t ON t.created_by = u.id')
            ->where('t.created_by > 0');
        if (!$actor->authorise('jlms.teachers.manage', 'com_jlms')) {
            $query->where('(t.created_by = :created_by OR t.user_id = :linked_user)')
                ->bind(':created_by', (int) $actor->id, \Joomla\Database\ParameterType::INTEGER)
                ->bind(':linked_user', (int) $actor->id, \Joomla\Database\ParameterType::INTEGER);
        }
        $query->order('u.name, u.id');

        return array_merge($options, $db->setQuery($query)->loadObjectList());
    }
}
