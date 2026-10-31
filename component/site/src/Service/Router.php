<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Service;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;

final class Router extends RouterView
{
    public function __construct(SiteApplication $app, AbstractMenu $menu)
    {
        $courses = new RouterViewConfiguration('courses');
        $this->registerView($courses);
        $course = new RouterViewConfiguration('course');
        $course->setKey('id')->setParent($courses);
        $this->registerView($course);
        $classroom = new RouterViewConfiguration('classroom');
        $classroom->setKey('id');
        $this->registerView($classroom);
        $quiz = new RouterViewConfiguration('quiz');
        $quiz->setKey('id');
        $this->registerView($quiz);
        parent::__construct($app, $menu);
        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
    }

    public function getQuizSegment($id, $query): array { return [(int)$id => (string)(int)$id]; }
    public function getQuizId($segment, $query): int { return ctype_digit((string)$segment) ? (int)$segment : 0; }

    public function getClassroomSegment($id, $query): array
    {
        return [(int) $id => (string) (int) $id];
    }

    public function getClassroomId($segment, $query): int
    {
        return ctype_digit((string) $segment) ? (int) $segment : 0;
    }

    public function getCourseSegment($id, $query): array
    {
        return [(int) $id => (string) (int) $id];
    }

    public function getCourseId($segment, $query): int
    {
        return ctype_digit((string) $segment) ? (int) $segment : 0;
    }
}
