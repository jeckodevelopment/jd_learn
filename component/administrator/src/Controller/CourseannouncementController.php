<?php

declare(strict_types=1);

namespace Jlms\\Component\\Jlms\\Administrator\\Controller;

use Joomla\\CMS\\Factory;
use Joomla\\CMS\\Language\\Text;
use Joomla\\CMS\\MVC\\Controller\\BaseController;
use Joomla\\CMS\\Router\\Route;
use Jlms\\Component\\Jlms\\Administrator\\Application\\CourseAnnouncementService;

final class CourseannouncementController extends BaseController
{
    public function execute($task)
    {
        if (strtolower((string) $task) !== 'notify') {
            throw new \\RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        return parent::execute($task);
    }

    public function notify(): void
    {
        if (strtoupper($this->input->getMethod()) !== 'POST') {
            throw new \\RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'), 405);
        }
        if (!$this->checkToken('post', false)) {
            throw new \\RuntimeException(Text::_('JINVALID_TOKEN_NOTICE'), 403);
        }

        $courseId = $this->input->post->getInt('id', 0);
        $confirmed = $this->input->post->getBool('confirm', false);
        $route = Route::link('administrator', 'index.php?option=com_jlms&view=courseannouncement&id=' . $courseId, false);
        if (!$confirmed) {
            $this->app->enqueueMessage(Text::_('COM_JLMS_COURSE_ANNOUNCEMENT_CONFIRM_REQUIRED'), 'warning');
            $this->setRedirect($route);
            return;
        }

        try {
            $service = Factory::getContainer()->get(CourseAnnouncementService::class);
            $queued = $service->notify($this->app->getIdentity(), $courseId);
            $this->app->enqueueMessage(Text::sprintf('COM_JLMS_COURSE_ANNOUNCEMENT_QUEUED', $queued), 'message');
        } catch (\\Throwable $error) {
            $message = str_starts_with($error->getMessage(), 'COM_JLMS_') || str_starts_with($error->getMessage(), 'JERROR_')
                ? Text::_($error->getMessage()) : Text::_('COM_JLMS_COURSE_ANNOUNCEMENT_QUEUE_FAILED');
            $this->app->enqueueMessage($message, 'warning');
        }
        $this->setRedirect($route);
    }
}
