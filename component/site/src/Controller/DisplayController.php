<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Site\Controller;
use Joomla\CMS\MVC\Controller\BaseController;

final class DisplayController extends BaseController
{
    protected $default_view = 'courses';

    public function display($cachable = false, $urlparams = [])
    {
        if (!in_array($this->input->getCmd('view', 'courses'), ['courses', 'course', 'categories', 'category', 'dashboard', 'classroom', 'quiz'], true)) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        if ($this->input->getCmd('view') === 'dashboard') { $this->app->setHeader('X-Robots-Tag', 'noindex, nofollow', true); }
        $this->app->setHeader('Cache-Control', 'private, no-store', true);
        return parent::display(false, $urlparams);
    }
}
