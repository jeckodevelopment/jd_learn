<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ItemModel;

final class CourseModel extends ItemModel
{
    /** Read-only pending approval state of the current learner, never the course roster. */
    public function hasPendingApproval(int $courseId, int $userId): bool
    {
        if ($courseId < 1 || $userId < 1) {
return false;
        }
        $query = $this->getDatabase()->createQuery()
            ->select('1')->from('#__jlms_enrollment_requests AS request')
            ->innerJoin('#__jlms_editions AS edition ON edition.id = request.edition_id')
            ->innerJoin('#__jlms_courses AS course ON course.id = edition.course_id')
            ->where('edition.course_id = :pendingCourse')
            ->where('request.user_id = :pendingOwner')
            ->where('edition.course_version_id = course.current_version_id')
            ->where("request.status = 'pending'")
            ->bind(':pendingCourse', $courseId, \Joomla\Database\ParameterType::INTEGER)
            ->bind(':pendingOwner', $userId, \Joomla\Database\ParameterType::INTEGER);
        return (bool) $this->getDatabase()->setQuery($query, 0, 1)->loadResult();
    }

    public function getItem($pk = null)
    {
        $app = Factory::getApplication();
        // Native com_fields reads the application identity, so refresh its access
        // together with the catalog check rather than using session-cached grants.
        $actor = new \Joomla\CMS\User\User((int) $app->getIdentity()->id);
        $actor->clearAccessRights();
        $app->loadIdentity($actor);
        $id = (int) ($pk ?? $app->getInput()->getInt('id', 0));
        $item = $app->bootComponent('com_jlms')->getCatalogService()->outline($actor, $app->getLanguage()->getTag(), $id);
        $item['fields'] = \Jlms\Component\Jlms\Site\Helper\CourseFields::prepare($item['course'], $app->getLanguage()->getTag());
        $item['public_materials'] = $app->bootComponent('com_jlms')->getMaterialService()->publicPreviewList(
            $id, (int) $item['course']->current_version_id, $app->getInput()->getInt('course_material_page', 0));
        return $item;
    }
}
