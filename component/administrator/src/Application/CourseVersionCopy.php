<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Copies authoring records only, under the caller's course/version locks and transaction. */
final class CourseVersionCopy
{
    public function __construct(private readonly DatabaseInterface $db) {}

    private function rows(string $table, string $column, int $id, int $limit): array
    {
        $query = $this->db->createQuery()->select('*')->from('#__jlms_' . $table)
            ->where($column . ' = :owner')->bind(':owner', $id, ParameterType::INTEGER)->order('ordering,id');
        if (!in_array($table, ['modules', 'lessons', 'attachments'], true)) { $query->clear('order')->order($table === 'quiz_questions' ? 'ordering,question_revision_id' : 'id'); }
        $rows = $this->db->setQuery($query, 0, $limit + 1)->loadAssocList();
        if (count($rows) > $limit) { throw new \DomainException('COM_JLMS_OUTLINE_LIMIT'); }
        return $rows;
    }

    private static function key(): string
    {
        $hex = bin2hex(random_bytes(16));
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-4' . substr($hex, 13, 3)
            . '-a' . substr($hex, 17, 3) . '-' . substr($hex, 20);
    }

    private function insert(string $table, array $row, User $actor): int
    {
        unset($row['id']);
        $row['revision'] = 1;
        $row['created_by'] = $row['modified_by'] = (int) $actor->id;
        $row['created_at'] = $row['modified_at'] = gmdate('Y-m-d H:i:s');
        if (array_key_exists('checked_out', $row)) { $row['checked_out'] = null; $row['checked_out_time'] = null; }
        $record = (object) $row;
        $this->db->insertObject('#__jlms_' . $table, $record, 'id');
        return (int) $record->id;
    }

    private function capacity(string $table, int $version, int $extra, int $limit): void
    {
        $q = $this->db->createQuery()->select('COUNT(*)')->from('#__jlms_' . $table)
            ->where('course_version_id=:version')->bind(':version', $version, ParameterType::INTEGER);
        if ((int) $this->db->setQuery($q)->loadResult() + $extra > $limit) { throw new \DomainException('COM_JLMS_OUTLINE_LIMIT'); }
    }

    private function nextOrder(string $table, string $field, int $owner): int
    {
        $q = $this->db->createQuery()->select('COALESCE(MAX(ordering), -1) + 1')->from('#__jlms_' . $table)
            ->where($field . '=:owner')->bind(':owner', $owner, ParameterType::INTEGER);
        return (int) $this->db->setQuery($q)->loadResult();
    }

    /** Copy one module subtree or one lesson within the authorized draft. */
    public function item(User $actor, string $kind, object $source, int $version, string $title, int $destinationModule = 0): int
    {
        $modules = $kind === 'module' ? [(array) $source] : [];
        $lessons = $kind === 'module' ? $this->rows('lessons', 'module_id', (int) $source->id, 5000) : [(array) $source];
        return $this->nodes($actor, $version, $version, $modules, $lessons, true, false, $title, $destinationModule);
    }

    public function copy(User $actor, object $source, int $number, bool $newIdentity = false, ?int $sourceVersion = null): int
    {
        $original = $sourceVersion ?? (int) $source->id;
        $version = (array) $source;
        $version['version_no'] = $number;
        $version['status'] = 'draft';
        $version['structure_hash'] = $version['published_at'] = null;
        $target = $this->insert('course_versions', $version, $actor);
        $this->nodes($actor, $original, $target, $this->rows('modules', 'course_version_id', $original, 1000),
            $this->rows('lessons', 'course_version_id', $original, 5000), $newIdentity, true);
        return $target;
    }

    private function nodes(User $actor, int $sourceVersion, int $target, array $moduleRows, array $lessonRows,
        bool $freshKeys, bool $whole, ?string $title = null, int $destinationModule = 0): int
    {
        $this->capacity('modules', $target, count($moduleRows), 1000);
        $this->capacity('lessons', $target, count($lessonRows), 5000);
        $sourceCourse = $this->course($sourceVersion); $targetCourse = $this->course($target);
        $materialsAllowed = $actor->authorise('jlms.materials.manage', 'com_jlms.course.' . $sourceCourse) && $actor->authorise('jlms.materials.manage', 'com_jlms.course.' . $targetCourse);
        $quizAllowed = $actor->authorise('jlms.quiz.manage', 'com_jlms.course.' . $sourceCourse) && $actor->authorise('jlms.quiz.manage', 'com_jlms.course.' . $targetCourse);
        $modules = []; $lessons = []; $first = 0;
        foreach ($moduleRows as $module) {
            $old = (int) $module['id']; $module['course_version_id'] = $target;
            if ($freshKeys) { $module['logical_key'] = self::key(); }
            if (!$whole) { $module['title'] = $title; $module['ordering'] = $this->nextOrder('modules', 'course_version_id', $target); }
            $modules[$old] = $this->insert('modules', $module, $actor);
            $first = $first ?: $modules[$old];
        }
        foreach ($lessonRows as $lesson) {
            $old = (int) $lesson['id'];
            $module = $modules[$lesson['module_id']] ?? (!$whole && !$moduleRows ? $destinationModule : 0);
            if (!$module || (int) $lesson['course_version_id'] !== $sourceVersion) { throw new \DomainException('COM_JLMS_INVALID_INPUT'); }
            $lesson['course_version_id'] = $target; $lesson['module_id'] = $module;
            if ($freshKeys) { $lesson['logical_key'] = self::key(); }
            if (!$whole && !$moduleRows) { $lesson['title'] = $title; $lesson['ordering'] = $this->nextOrder('lessons', 'module_id', $module); }
            $lessons[$old] = $this->insert('lessons', $lesson, $actor);
            $first = $first ?: $lessons[$old];
        }
        $attachments = array_filter($this->rows('attachments', 'course_version_id', $sourceVersion, 1000),
            static fn(array $attachment): bool => $attachment['lesson_id'] === null ? $whole : isset($lessons[$attachment['lesson_id']]));
        $this->capacity('attachments', $target, count($attachments), 1000);
        foreach ($attachments as $attachment) {
            if (!$materialsAllowed) { throw new \DomainException('JERROR_ALERTNOAUTHOR'); }
            $attachment['course_version_id'] = $target;
            $attachment['lesson_id'] = $attachment['lesson_id'] === null ? null : $lessons[$attachment['lesson_id']];
            $this->insert('attachments', $attachment, $actor);
        }
        foreach ($lessonRows as $lesson) {
            if ($lesson['media_asset_id'] && !$materialsAllowed) { throw new \DomainException('JERROR_ALERTNOAUTHOR'); }
        }
        foreach ($this->rows('quizzes', 'course_version_id', $sourceVersion, 5000) as $quiz) {
            if (!isset($lessons[$quiz['lesson_id']])) { continue; }
            if (!$quizAllowed) { throw new \DomainException('JERROR_ALERTNOAUTHOR'); }
            $old = (int) $quiz['id']; $quiz['course_version_id'] = $target; $quiz['lesson_id'] = $lessons[$quiz['lesson_id']];
            $selection = json_decode($quiz['selection_json'], true, 32, JSON_THROW_ON_ERROR);
            unset($selection['published_pool_hash']);
            $quiz['selection_json'] = json_encode($selection, JSON_THROW_ON_ERROR);
            $new = $this->insert('quizzes', $quiz, $actor);
            $questions = $this->rows('quiz_questions', 'quiz_id', $old, 200);
            foreach ($questions as $question) {
                $question['quiz_id'] = $new;
                $record = (object) $question;
                $this->db->insertObject('#__jlms_quiz_questions', $record);
            }
        }
        foreach ($this->rows('prerequisites', 'course_version_id', $sourceVersion, 10000) as $edge) {
            if (!isset($lessons[$edge['lesson_id']])) { continue; }
            $required = $lessons[$edge['required_lesson_id']] ?? ($target === $sourceVersion ? (int) $edge['required_lesson_id'] : 0);
            if (!$required) { throw new \DomainException('COM_JLMS_INVALID_INPUT'); }
            $record = (object) ['course_version_id' => $target,
                'lesson_id' => $lessons[$edge['lesson_id']], 'required_lesson_id' => $required];
            $this->db->insertObject('#__jlms_prerequisites', $record);
        }
        return $first;
    }

    private function course(int $version): int
    {
        $q = $this->db->createQuery()->select('course_id')->from('#__jlms_course_versions')
            ->where('id=:version')->bind(':version', $version, ParameterType::INTEGER);
        return (int) $this->db->setQuery($q)->loadResult();
    }
}
