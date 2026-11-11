<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Controller;

use Joomla\CMS\MVC\Controller\BaseController;

final class DisplayController extends BaseController
{
    protected $default_view = 'dashboard';

    public function display($cachable = false, $urlparams = [])
    {
        $view = $this->input->getCmd('view', 'dashboard');
        if (!in_array($view, ['dashboard', 'courses', 'course', 'curriculum', 'publicationreview', 'coursepreview', 'materials', 'questionbank', 'question', 'quiz', 'quiztime', 'quizreview', 'quizextend', 'enrollments', 'enrollmentstate', 'enrollmentmigration', 'courseoperations', 'manualenrollment', 'teachers', 'teacher', 'pathways', 'pathway', 'categoryreassignment'], true)) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        return parent::display(false, $urlparams);
    }
}
