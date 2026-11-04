<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;

final class CoursesController extends \Joomla\CMS\MVC\Controller\BaseController
{
    public function execute($task)
    {
        if (!in_array(strtolower($task), ['programme'], true)) { throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404); }
        return parent::execute($task);
    }
    public function programme(): void
    {
        if (strtoupper($this->input->getMethod()) !== 'POST') { throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'), 405); }
        if (!$this->checkToken('post', false)) { throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403); }
        $ids = $this->input->post->get('cid', [], 'array');
        if (count($ids) !== 1) { throw new \RuntimeException(Text::_('COM_JLMS_COURSES_SELECT_ONE'), 422); }
        try { $id = CurriculumService::id($ids[0]); }
        catch (\InvalidArgumentException | \DomainException) { throw new \RuntimeException(Text::_('COM_JLMS_INVALID_INPUT'), 422); }
        $actor = CourseEditorAccess::fresh($this->app->getIdentity());
        $model = $this->app->bootComponent('com_jlms')->getMVCFactory()->createModel('Course', 'Administrator', ['ignore_request'=>true]);
        $item = $model->getItem($id);
        if (!$item || !CourseEditorAccess::canEdit($actor, $item)) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        $this->setRedirect(Route::link('administrator', 'index.php?option=com_jlms&view=curriculum&id=' . $id, false));
    }
}
