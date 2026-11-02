<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Public profile projection. Linked native accounts never confer rights or expose email. */
final class TeacherProfiles
{
    public static function available(DatabaseInterface $db, User $actor, string $language = '*', ?array $only = null): array
    {
        if ($only === []) { return []; }
        if ($actor->id) { $actor = new User((int) $actor->id); $actor->clearAccessRights(); if (!$actor->id || $actor->block) { return []; } }
        $levels = array_map('intval', $actor->getAuthorisedViewLevels());
        if (!$levels) { return []; }
        $q = $db->createQuery()->select('t.id,t.title,t.bio,t.image,t.params_json,t.access,t.language,t.catid')
            ->from('#__jlms_teachers AS t')->innerJoin('#__categories AS cat ON cat.id=t.catid')
            ->where('t.state=1')->where('cat.published=1')->where("cat.extension='com_jlms.teacher'")
            ->where('t.access IN (' . implode(',', $levels) . ')')
            ->where("NOT EXISTS (SELECT 1 FROM #__categories AS ancestor WHERE ancestor.lft<=cat.lft AND ancestor.rgt>=cat.rgt AND ancestor.level>0 AND (ancestor.published<>1 OR ancestor.access NOT IN (" . implode(',', $levels) . "))) ")
            ->order('t.title,t.id');
        if ($language !== '*') {
            $q->where("(t.language='*' OR t.language=:language)")->bind(':language', $language);
            $q->where("NOT EXISTS (SELECT 1 FROM #__categories AS ancestor WHERE ancestor.lft<=cat.lft AND ancestor.rgt>=cat.rgt AND ancestor.level>0 AND ancestor.language<>'*' AND ancestor.language<>:categoryLanguage)")->bind(':categoryLanguage', $language);
        }
        if ($only !== null) { $q->where('t.id IN (' . implode(',',array_map('intval',$only)) . ')'); }
        $rows = $db->setQuery($q, 0, 2001)->loadObjectList('id');
        if (count($rows) > 2000) { throw new \RuntimeException('COM_JLMS_TEACHER_SELECTION_LIMIT'); }
        return $rows;
    }

    public static function assignment(DatabaseInterface $db, User $actor, string $language, mixed $input): array
    {
        if (!is_array($input) || count($input) > 50) { throw new \InvalidArgumentException('COM_JLMS_TEACHER_INVALID'); }
        $available = self::available($db, $actor, $language, array_map(static fn ($id): int => is_scalar($id) ? (int) $id : 0, $input));
        $ids = [];
        foreach ($input as $value) {
            if ($value === '') { continue; } // Native multiple-select empty marker.
            if ((!is_string($value) && !is_int($value)) || !preg_match('/^[1-9][0-9]{0,17}$/D', (string) $value)) { throw new \InvalidArgumentException('COM_JLMS_TEACHER_INVALID'); }
            $id = (int) $value;
            if (!isset($available[$id]) || isset($ids[$id])) { throw new \InvalidArgumentException('COM_JLMS_TEACHER_INVALID'); }
            $ids[$id] = $id;
        }
        return array_values($ids);
    }

    public static function selected(DatabaseInterface $db, int $course): array
    {
        $q = $db->createQuery()->select('teacher_id')->from('#__jlms_course_teachers')->where('course_id=:course')->bind(':course', $course, ParameterType::INTEGER)->order('ordering,teacher_id');
        return array_map('intval', $db->setQuery($q, 0, 51)->loadColumn());
    }

    public static function freeze(DatabaseInterface $db, User $actor, object $course, string $presentation): string
    {
        $ids = self::selected($db, (int) $course->id);
        if (!$ids) { return $presentation; }
        $ids = self::assignment($db, $actor, (string) $course->language, $ids);
        $available = self::available($db, $actor, (string) $course->language, $ids);
        $profiles = [];
        foreach ($ids as $id) {
            $row = $available[$id];
            $params = json_decode($row->params_json, true, 16, JSON_THROW_ON_ERROR);
            CourseArtwork::validateLocal((string) $row->image);
            $q = $db->createQuery()->select('ancestor.access,ancestor.language')->from('#__categories AS ancestor')->innerJoin('#__categories AS cat ON cat.id=:category')
                ->where('ancestor.lft<=cat.lft AND ancestor.rgt>=cat.rgt AND ancestor.level>0')->bind(':category', $row->catid, ParameterType::INTEGER);
            $categories = $db->setQuery($q)->loadAssocList();
            $profiles[] = ['id' => $id, 'title' => (string) $row->title, 'bio' => (string) $row->bio, 'image' => (string) $row->image,
                'image_alt' => (string) ($params['image_alt'] ?? ''), 'image_decorative' => (bool) ($params['image_decorative'] ?? false),
                'website' => (string) ($params['website'] ?? ''), 'access' => (int) $row->access, 'language' => (string) $row->language,
                'category_access' => array_map('intval',array_column($categories,'access')), 'category_language' => array_column($categories,'language')];
        }
        $data = json_decode($presentation, true, 32, JSON_THROW_ON_ERROR);
        $data['teachers'] = $profiles;
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    public static function visible(DatabaseInterface $db, User $actor, string $language, string $presentation): array
    {
        $data = json_decode($presentation, true, 32, JSON_THROW_ON_ERROR);
        $snapshots = $data['teachers'] ?? [];
        if (!$snapshots) { return []; }
        if (!is_array($snapshots) || count($snapshots) > 50) { throw new \RuntimeException('COM_JLMS_TEACHER_INVALID'); }
        if ($actor->id) { $actor = new User((int) $actor->id); $actor->clearAccessRights(); if (!$actor->id || $actor->block) { return []; } }
        $current = self::available($db, $actor, $language,array_map(static fn (array $profile): int => (int) $profile['id'],$snapshots));
        $levels = array_map('intval', $actor->getAuthorisedViewLevels());
        $result = [];
        foreach ($snapshots as $profile) {
            if (!isset($current[$profile['id']]) || !in_array($profile['access'], $levels, true)
                || array_diff($profile['category_access'], $levels) || array_diff($profile['category_language'], ['*',$language])
                || !in_array($profile['language'], ['*', $language], true)) { continue; }
            // Live availability gates historical text; profile edits never rewrite the snapshot.
            $result[] = $profile;
        }
        return $result;
    }
}
