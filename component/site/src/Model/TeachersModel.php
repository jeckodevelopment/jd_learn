<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseInterface;
use Jlms\Component\Jlms\Administrator\Application\TeacherProfiles;

/** Public directory: never select linked Joomla account details. */
final class TeachersModel extends ListModel
{
    protected function populateState($ordering = 't.title', $direction = 'ASC')
    {
        parent::populateState($ordering, $direction);
        $this->setState('list.limit', max(1, min(50, (int) $this->state->get('list.limit', 20))));
    }

    protected function getStoreId($id = '')
    {
        $app = Factory::getApplication();
        return parent::getStoreId($id . ':' . (int) $app->getIdentity()->id . ':' . $app->getLanguage()->getTag());
    }

    protected function getListQuery()
    {
        $app = Factory::getApplication();
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        // Reuse the live Joomla category, language and ACL projection used by profile pages.
        $allowed = TeacherProfiles::available($db, $app->getIdentity(), $app->getLanguage()->getTag());
        $query = $db->createQuery()->select('t.id,t.title,t.bio,t.image,t.params_json')
            ->from('#__jlms_teachers AS t')
            ->innerJoin('#__categories AS cat ON cat.id=t.catid')
            ->where('t.state=1')->where('cat.published=1')
            ->where("cat.extension='com_jlms.teacher'")
            ->order('t.title ASC,t.id ASC');
        if (!$allowed) {
            return $query->where('1=0');
        }
        return $query->where('t.id IN (' . implode(',', array_map('intval', array_keys($allowed))) . ')');
    }
}
