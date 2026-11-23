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
        if (!in_array($view, ['dashboard', 'courses', 'course', 'curriculum', 'publicationreview', 'coursepreview', 'courseannouncement', 'materials', 'questionbank', 'question', 'quiz', 'quiztime', 'quizreview', 'quizextend', 'enrollments', 'enrollmentstate', 'enrollmentmigration', 'courseoperations', 'manualenrollment', 'coursemessage', 'mailqueue', 'mailvariables', 'teachers', 'teacher', 'certificates', 'certificate', 'pathways', 'pathway', 'categoryreassignment'], true)) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        $this->setNavigationContext($view);
        return parent::display(false, $urlparams);
    }
    /**
     * Associate deeper views with their operational submenu item. Atum reads
     * the selected item from Joomla's administrator menu; no DOM manipulation.
     * Secondary dashboard-only tools deliberately have no operational parent.
     */
    private function setNavigationContext(string $view): void
    {
        $groups = [
            'courses' => ['courses', 'course', 'curriculum', 'publicationreview', 'coursepreview', 'courseannouncement', 'materials', 'enrollments', 'enrollmentstate', 'enrollmentmigration', 'courseoperations', 'manualenrollment', 'coursemessage', 'quiz', 'quiztime', 'quizreview', 'quizextend'],
            'pathways' => ['pathways', 'pathway'],
            'teachers' => ['teachers', 'teacher'],
            'questionbank' => ['questionbank', 'question'],
        ];

        $parent = $view === 'dashboard' ? 'dashboard' : null;
        foreach ($groups as $primary => $views) {
            if (in_array($view, $views, true)) {
                $parent = $primary;
                break;
            }
        }

        // A dashboard-only view selects the JD Learn component root, not a
        // fictitious operational submenu item. Atum can keep JD Learn expanded
        // while no submenu link is highlighted (the agreed UX choice A).
        $app = $this->app;
        $menu = $app->getMenu();
        foreach ($menu->getMenu() as $item) {
            $link = html_entity_decode((string) ($item->link ?? ''), ENT_QUOTES);
            $query = parse_url($link, PHP_URL_QUERY);
            if ($query === null || $query === false) {
                $query = $link;
            }
            parse_str($query, $parameters);
            if (($parameters['option'] ?? '') !== 'com_jlms') {
                continue;
            }
            if ($parent === null) {
                if ((int) ($item->parent_id ?? 0) !== 1 || ($item->type ?? '') !== 'component') {
                    continue;
                }
            } elseif (($parameters['view'] ?? '') !== $parent || (int) ($item->parent_id ?? 0) === 1) {
                continue;
            }
            $menu->setActive((int) $item->id);
            break;
        }
    }
}
