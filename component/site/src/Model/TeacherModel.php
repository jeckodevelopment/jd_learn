<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Application\TeacherProfiles;

final class TeacherModel extends ListModel
{
    public function getProfile(): object
    {
        $app = Factory::getApplication();
        $id = $app->getInput()->getInt('id');
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $rows = TeacherProfiles::available($db, $app->getIdentity(), $app->getLanguage()->getTag(), [$id], true);
        if (!isset($rows[$id])) { throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404); }
        $profile = $rows[$id];
        $profile->fields = FieldsHelper::getFields('com_jlms.teacher', $profile, true);
        return $profile;
    }

    protected function getListQuery()
    {
        $profile = $this->getProfile();
        $app = Factory::getApplication();
        $query = $app->bootComponent('com_jlms')->getCatalogService()->query($app->getIdentity(), $app->getLanguage()->getTag());
        $id = (int) $profile->id;
        return $query->innerJoin('#__jlms_course_teachers AS teaching ON teaching.course_id=c.id')
            ->where('teaching.teacher_id=:teacher')->bind(':teacher', $id, ParameterType::INTEGER)->order('c.title,c.id');
    }
}
