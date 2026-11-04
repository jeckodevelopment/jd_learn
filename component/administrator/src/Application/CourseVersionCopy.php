<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Copies authoring records only. Learning identities and results stay on their assigned version. */
final class CourseVersionCopy
{
    public function __construct(private readonly DatabaseInterface $db) {}

    private function rows(string $table, string $column, int $id, int $limit): array
    {
        $query = $this->db->createQuery()->select('*')->from('#__jlms_' . $table)
            ->where($column . ' = :owner')->bind(':owner', $id, ParameterType::INTEGER)->order('id');
        $rows = $this->db->setQuery($query, 0, $limit + 1)->loadAssocList();
        if (count($rows) > $limit) { throw new \DomainException('COM_JLMS_OUTLINE_LIMIT'); }
        return $rows;
    }

    private function insert(string $table, array $row, User $actor): int
    {
        unset($row['id']);
        $row['revision'] = 1;
        $row['created_by'] = (int) $actor->id;
        $row['modified_by'] = (int) $actor->id;
        $row['created_at'] = $row['modified_at'] = gmdate('Y-m-d H:i:s');
        if (array_key_exists('checked_out', $row)) { $row['checked_out'] = null; $row['checked_out_time'] = null; }
        $record = (object) $row;
        $this->db->insertObject('#__jlms_' . $table, $record, 'id');
        return (int) $record->id;
    }

    /** Caller holds the course row lock and owns the transaction. */
    public function copy(User $actor, object $source, int $number): int
    {
        $version = (array) $source;
        $version['version_no'] = $number;
        $version['status'] = 'draft';
        $version['structure_hash'] = null;
        $version['published_at'] = null;
        $target = $this->insert('course_versions', $version, $actor);
        $modules = [];
        foreach ($this->rows('modules', 'course_version_id', (int) $source->id, 1000) as $module) {
            $old = (int) $module['id']; $module['course_version_id'] = $target;
            $modules[$old] = $this->insert('modules', $module, $actor);
        }
        $lessons = [];
        foreach ($this->rows('lessons', 'course_version_id', (int) $source->id, 5000) as $lesson) {
            $old = (int) $lesson['id'];
            if (!isset($modules[$lesson['module_id']])) { throw new \DomainException('COM_JLMS_INVALID_INPUT'); }
            $lesson['course_version_id'] = $target;
            $lesson['module_id'] = $modules[$lesson['module_id']];
            $lessons[$old] = $this->insert('lessons', $lesson, $actor);
        }
        foreach ($this->rows('attachments', 'course_version_id', (int) $source->id, 1000) as $attachment) {
            $attachment['course_version_id'] = $target;
            if ($attachment['lesson_id'] !== null) {
                if (!isset($lessons[$attachment['lesson_id']])) { throw new \DomainException('COM_JLMS_INVALID_INPUT'); }
                $attachment['lesson_id'] = $lessons[$attachment['lesson_id']];
            }
            $this->insert('attachments', $attachment, $actor);
        }
        foreach ($this->rows('quizzes', 'course_version_id', (int) $source->id, 5000) as $quiz) {
            if (!isset($lessons[$quiz['lesson_id']])) { throw new \DomainException('COM_JLMS_INVALID_INPUT'); }
            $old = (int) $quiz['id']; $quiz['course_version_id'] = $target;
            $quiz['lesson_id'] = $lessons[$quiz['lesson_id']];
            $selection = json_decode($quiz['selection_json'], true, 32, JSON_THROW_ON_ERROR);
            unset($selection['published_pool_hash']);
            $quiz['selection_json'] = json_encode($selection, JSON_THROW_ON_ERROR);
            $new = $this->insert('quizzes', $quiz, $actor);
            $query = $this->db->createQuery()->select('*')->from('#__jlms_quiz_questions')
                ->where('quiz_id = :quiz')->bind(':quiz', $old, ParameterType::INTEGER);
            foreach ($this->db->setQuery($query, 0, 201)->loadAssocList() as $question) {
                $question['quiz_id'] = $new;
                $record = (object) $question;
                $this->db->insertObject('#__jlms_quiz_questions', $record);
            }
        }
        foreach ($this->rows('prerequisites', 'course_version_id', (int) $source->id, 10000) as $edge) {
            $record = (object) ['course_version_id' => $target,
                'lesson_id' => $lessons[$edge['lesson_id']], 'required_lesson_id' => $lessons[$edge['required_lesson_id']]];
            $this->db->insertObject('#__jlms_prerequisites', $record);
        }
        return $target;
    }
}
