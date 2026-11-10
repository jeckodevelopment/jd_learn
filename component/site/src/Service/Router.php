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
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class Router extends RouterView
{
    /** @var array<string, string> */
    private static array $aliasCache = [];

    public function __construct(SiteApplication $app, AbstractMenu $menu)
    {
        $courses = new RouterViewConfiguration('courses');
        $this->registerView($courses);
        $this->registerView(new RouterViewConfiguration('dashboard'));
        $categories = new RouterViewConfiguration('categories');
        $this->registerView($categories);
        $category = new RouterViewConfiguration('category');
        $category->setKey('id')->setParent($categories);
        $this->registerView($category);
        $course = new RouterViewConfiguration('course');
        $course->setKey('id')->setParent($courses);
        $this->registerView($course);
        $classroom = new RouterViewConfiguration('classroom');
        $classroom->setKey('id');
        $this->registerView($classroom);
        $teacher = new RouterViewConfiguration('teacher');
        $teacher->setKey('id');
        $this->registerView($teacher);
        $teacheredit = new RouterViewConfiguration('teacheredit');
        $teacheredit->setKey('id');
        $this->registerView($teacheredit);
        $pathways = new RouterViewConfiguration('pathways');
        $this->registerView($pathways);
        $pathway = new RouterViewConfiguration('pathway');
        $pathway->setKey('id')->setParent($pathways);
        $this->registerView($pathway);
        $quiz = new RouterViewConfiguration('quiz');
        $quiz->setKey('id');
        $this->registerView($quiz);
        parent::__construct($app, $menu);
        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
    }

    public function getTeachereditSegment($id, $query): array { return [(int) $id => (string) (int) $id]; }
    public function getTeachereditId($segment, $query): int { return ctype_digit((string) $segment) ? (int) $segment : 0; }

    public function getTeacherSegment($id, $query): array { return [(int) $id => (string) (int) $id]; }
    public function getTeacherId($segment, $query): int { return ctype_digit((string) $segment) ? (int) $segment : 0; }

    public function getCategorySegment($id, $query): array { return [(int) $id => $this->itemSegment('#__categories', (int) $id, true)]; }
    public function getCategoryId($segment, $query): int { return $this->segmentId($segment); }

    public function getPathwaySegment($id, $query): array { return [(int) $id => $this->itemSegment('#__jlms_pathways', (int) $id)]; }
    public function getPathwayId($segment, $query): int { return $this->segmentId($segment); }

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
        return [(int) $id => $this->itemSegment('#__jlms_courses', (int) $id)];
    }

    public function getCourseId($segment, $query): int
    {
        return $this->segmentId($segment);
    }

    private function itemSegment(string $table, int $id, bool $courseCategory = false): string
    {
        if ($id < 1) { return (string) $id; }
        $cacheKey = $table . ':' . $id;
        if (!array_key_exists($cacheKey, self::$aliasCache)) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->createQuery()->select($db->quoteName('alias'))->from($db->quoteName($table))
                ->where($db->quoteName('id') . ' = :id')->bind(':id', $id, ParameterType::INTEGER);
            if ($courseCategory) {
                $extension = 'com_jlms.course';
                $query->where($db->quoteName('extension') . ' = :extension')->bind(':extension', $extension);
            }
            self::$aliasCache[$cacheKey] = (string) ($db->setQuery($query)->loadResult() ?: '');
        }

        $alias = self::$aliasCache[$cacheKey];
        return $alias === '' ? (string) $id : $id . ':' . $alias;
    }

    private function segmentId($segment): int
    {
        if (preg_match('/^(\d+)(?:(?::|-).*)?$/uD', (string) $segment, $matches) !== 1) { return 0; }
        return (int) $matches[1];
    }
}
