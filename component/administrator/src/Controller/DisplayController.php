<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Controller;

use Joomla\CMS\MVC\Controller\BaseController;

final class DisplayController extends BaseController
{
    protected $default_view = 'courses';

    public function display($cachable = false, $urlparams = [])
    {
        $view = $this->input->getCmd('view', 'courses');
        if (!in_array($view, ['dashboard', 'courses', 'course', 'curriculum', 'publicationreview', 'coursepreview', 'materials', 'questionbank', 'quiz', 'quiztime', 'quizreview', 'quizextend', 'enrollments', 'enrollmentstate', 'enrollmentmigration', 'courseoperations', 'manualenrollment', 'teachers', 'teacher'], true)) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        return parent::display(false, $urlparams);
    }
}
