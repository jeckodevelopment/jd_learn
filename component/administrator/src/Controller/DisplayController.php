<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Controller;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;

final class DisplayController extends BaseController
{
    protected $default_view = 'dashboard';

    public function display($cachable = false, $urlparams = [])
    {
        $view = $this->input->getCmd('view', 'dashboard');
        if (!in_array($view, ['dashboard', 'courses', 'course', 'curriculum', 'publicationreview', 'coursepreview', 'courseannouncement', 'materials', 'questionbank', 'question', 'quiz', 'quiztime', 'quizreview', 'quizextend', 'enrollments', 'enrollmentstate', 'enrollmentmigration', 'courseoperations', 'manualenrollment', 'mailqueue', 'mailvariables', 'teachers', 'teacher', 'certificates', 'certificate', 'pathways', 'pathway', 'categoryreassignment'], true)) {
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
            'courses' => ['courses', 'course', 'curriculum', 'publicationreview', 'coursepreview', 'courseannouncement', 'materials', 'enrollments', 'enrollmentstate', 'enrollmentmigration', 'courseoperations', 'manualenrollment', 'quiz', 'quiztime', 'quizreview', 'quizextend'],
            'pathways' => ['pathways', 'pathway'],
            'teachers' => ['teachers', 'teacher'],
            'mailqueue' => ['mailqueue'],
            'questionbank' => ['questionbank', 'question'],
        ];

        $parent = $view === 'dashboard' ? 'dashboard' : null;
        foreach ($groups as $primary => $views) {
            if (in_array($view, $views, true)) {
                $parent = $primary;
                break;
            }
        }

        if ($parent === null) {
            return;
        }

        $app = $this->app;
        $menu = $app->getMenu();
        foreach ($menu->getMenu() as $item) {
            $link = html_entity_decode((string) ($item->link ?? ''), ENT_QUOTES);
            $query = parse_url($link, PHP_URL_QUERY);
            if ($query === null || $query === false) {
                $query = $link;
            }
            parse_str($query, $parameters);
            if (($parameters['option'] ?? '') !== 'com_jlms' || ($parameters['view'] ?? '') !== $parent) {
                continue;
            }
            $menu->setActive((int) $item->id);
            break;
        }

        if ($view === $parent) {
            return;
        }

        // Breadcrumbs use Joomla's own Pathway API, not parallel markup.
        $labels = [
            'courses' => 'COM_JLMS_COURSES',
            'pathways' => 'COM_JLMS_PATHWAYS',
            'teachers' => 'COM_JLMS_TEACHERS',
            'mailqueue' => 'COM_JLMS_MAIL_QUEUE',
            'questionbank' => 'COM_JLMS_QUESTION_BANK',
        ];
        $app->getPathway()->addItem(Text::_($labels[$parent]), 'index.php?option=com_jlms&view=' . $parent);
    }
}
