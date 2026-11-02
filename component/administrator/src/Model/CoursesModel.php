<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Model;
use Joomla\CMS\MVC\Model\ListModel;
use Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess;

final class CoursesModel extends ListModel
{
    protected function populateState($ordering='id',$direction='DESC')
    {
        parent::populateState($ordering,$direction);
        $this->setState('list.limit',max(1,min(100,(int)$this->state->get('list.limit',20))));
    }
    private function scope(): array
    {
        $actor=CourseEditorAccess::fresh($this->getCurrentUser()); $this->setCurrentUser($actor);
        return CourseEditorAccess::editableIds($this->getDatabase(),$actor);
    }
    protected function getStoreId($id='')
    {
        // Native list caches must not survive a same-session change in visible record scope.
        $scope=$this->scope();
        return parent::getStoreId($id.':'.(int)$this->getCurrentUser()->id.':'.hash('sha256',implode(',',$scope)));
    }
    protected function getListQuery()
    {
        $db=$this->getDatabase(); $ids=$this->scope();
        $q=$db->createQuery()->select('id,title,catid,state,revision,created_by,modified_at')->from('#__jlms_courses')->order('id DESC');
        if ($ids) { $q->whereIn('id',$ids); } else { $q->where('1=0'); }
        return $q;
    }
}
