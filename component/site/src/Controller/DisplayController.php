<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Controller;

use Joomla\CMS\MVC\Controller\BaseController;

final class DisplayController extends BaseController
{
    protected $default_view = 'courses';

    public function display($cachable = false, $urlparams = [])
    {
        // Protect even rejected requests: invalid views throw before rendering.
        $this->app->setHeader('Cache-Control', 'private, no-store', true);
        $view = $this->input->getCmd('view', 'courses');
        $allowedViews = [
            'courses', 'course', 'lessonpreview', 'teachers', 'teacher', 'teacheredit',
            'categories', 'category', 'dashboard', 'mycourses', 'mycertificates',
            'preferences', 'classroom', 'quiz', 'pathways', 'pathway',
            'mailconsentrevoke',
        ];
        if (!in_array($view, $allowedViews, true)) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        // Private teaching and editing routes must never enter public search indexes.
        // Public records (course/category/pathway) decide separately in their view
        // using the actual guest-visible access rules.
        $privateViews = [
            'dashboard', 'mycourses', 'mycertificates', 'preferences',
            'lessonpreview', 'teacheredit', 'classroom', 'quiz',
            'mailconsentrevoke',
        ];
        if (in_array($this->input->getCmd('view'), $privateViews, true)) {
            $this->app->setHeader('X-Robots-Tag', 'noindex, nofollow', true);
        }
        return parent::display(false, $urlparams);
    }
}