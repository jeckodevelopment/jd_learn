<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Internal read model. PublicationService authorises and serialises its use. */
final class PublicationImpact
{
    public function __construct(private readonly DatabaseInterface $db) {}

    private function rows(string $sql, int $version, int $limit): array
    {
        $query = $this->db->createQuery()->setQuery($sql)->bind(':version', $version, ParameterType::INTEGER);
        $rows = $this->db->setQuery($query, 0, $limit + 1)->loadAssocList();
        if (count($rows) > $limit) { throw new \DomainException('COM_JLMS_OUTLINE_LIMIT'); }
        return $rows;
    }

    private static function hash(mixed $value): string
    {
        return hash('sha256', json_encode($value, JSON_THROW_ON_ERROR));
    }

    private static function canonical(mixed $value): mixed
    {
        if (is_array($value)) {
            if (!array_is_list($value)) { ksort($value); }
            foreach ($value as &$child) { $child = self::canonical($child); } unset($child);
        }
        return $value;
    }

    private function structure(int $version): array
    {
        $modules = $this->rows('SELECT id,logical_key,title,ordering,SHA2(params_json,256) AS settings FROM #__jlms_modules WHERE course_version_id=:version ORDER BY ordering,id', $version, 1000);
        $moduleKeys = []; $result = ['modules'=>[], 'lessons'=>[], 'quizzes'=>[], 'materials'=>[]];
        foreach ($modules as $row) {
            $moduleKeys[$row['id']] = $row['logical_key']; unset($row['id']);
            $result['modules'][$row['logical_key']] = $row;
        }
        // Hash large bodies in SQL: do not buffer thousands of 1 MiB lesson bodies in PHP.
        $lessons = $this->rows("SELECT id,logical_key,module_id,title,ordering,kind,required,preview,completion_rule,duration_ms,provider_key,media_asset_id,publish_up,publish_down,release_after_days,SHA2(COALESCE(body,''),256) AS body_hash,SHA2(COALESCE(source_json,''),256) AS source_hash,SHA2(params_json,256) AS settings,SHA2(fields_snapshot_json,256) AS fields_hash FROM #__jlms_lessons WHERE course_version_id=:version ORDER BY ordering,id", $version, 5000);
        $lessonKeys = [];
        foreach ($lessons as $row) {
            $lessonKeys[$row['id']] = $row['logical_key']; $row['module_id'] = $moduleKeys[$row['module_id']] ?? throw new \DomainException('COM_JLMS_INVALID_INPUT');
            unset($row['id']); $result['lessons'][$row['logical_key']] = $row;
        }
        $quizzes = $this->rows('SELECT id,lesson_id,pass_basis_points,max_attempts,time_limit_seconds,feedback_policy,selection_json FROM #__jlms_quizzes WHERE course_version_id=:version ORDER BY id', $version, 5000);
        $questionHashes = [];
        foreach (array_chunk(array_column($quizzes, 'id'), 32) as $batch) {
            $query = $this->db->createQuery()->select('quiz_id,question_revision_id,ordering,points')->from('#__jlms_quiz_questions')->order('quiz_id,ordering,question_revision_id');
            $values = array_map('intval', $batch); $names = [];
            foreach (array_keys($values) as $index) { $name = ':quiz' . $index; $names[] = $name; $query->bind($name, $values[$index], ParameterType::INTEGER); }
            $query->where('quiz_id IN (' . implode(',', $names) . ')');
            $questionRows = [];
            $rows = $this->db->setQuery($query, 0, 6401)->loadAssocList();
            if (count($rows) > 6400) { throw new \DomainException('COM_JLMS_OUTLINE_LIMIT'); }
            foreach ($rows as $question) { $id = $question['quiz_id']; unset($question['quiz_id']); $questionRows[$id][] = $question; }
            foreach ($questionRows as $id=>$questions) {
                if (count($questions) > 200) { throw new \DomainException('COM_JLMS_OUTLINE_LIMIT'); }
                $questionHashes[$id] = self::hash($questions);
            }
        }
        foreach ($quizzes as $row) {
            $key = $lessonKeys[$row['lesson_id']] ?? throw new \DomainException('COM_JLMS_INVALID_INPUT');
            $selection = json_decode($row['selection_json'], true, 32, JSON_THROW_ON_ERROR);
            unset($selection['published_pool_hash']);
            $row['selection_json'] = self::canonical($selection);
            $row['questions_hash'] = $questionHashes[$row['id']] ?? self::hash([]);
            unset($row['id'], $row['lesson_id']);
            $result['quizzes'][$key] = ['title'=>$result['lessons'][$key]['title']] + $row;
        }
        $attachments = $this->rows('SELECT a.lesson_id,a.asset_id,a.title,SHA2(COALESCE(a.description,\'\'),256) AS description_hash,a.ordering,a.state,a.preview,a.publish_up,a.publish_down,f.sha256,f.status FROM #__jlms_attachments AS a INNER JOIN #__jlms_assets AS f ON f.id=a.asset_id WHERE a.course_version_id=:version ORDER BY a.ordering,a.id', $version, 1000);
        $occurrences = [];
        foreach ($attachments as $row) {
            $scope = $row['lesson_id'] === null ? 'course' : ($lessonKeys[$row['lesson_id']] ?? throw new \DomainException('COM_JLMS_INVALID_INPUT'));
            $base = $scope . ':' . $row['asset_id']; $occurrences[$base] = ($occurrences[$base] ?? 0) + 1;
            unset($row['lesson_id']); $result['materials'][$base . ':' . $occurrences[$base]] = $row;
        }
        foreach ($result as &$items) { ksort($items); } unset($items);
        return $result;
    }

    private static function changes(array $before, array $after): array
    {
        $diff = ['added'=>0, 'changed'=>0, 'removed'=>0, 'details'=>[]];
        foreach ($after as $key=>$item) {
            $kind = !isset($before[$key]) ? 'added' : (self::hash($item) !== self::hash($before[$key]) ? 'changed' : '');
            if ($kind !== '') { $diff[$kind]++; if (count($diff['details']) < 100) { $diff['details'][] = ['kind'=>$kind, 'title'=>$item['title']]; } }
        }
        foreach ($before as $key=>$item) {
            if (!isset($after[$key])) { $diff['removed']++; if (count($diff['details']) < 100) { $diff['details'][] = ['kind'=>'removed', 'title'=>$item['title']]; } }
        }
        return $diff;
    }

    public function build(object $course, object $source, object $draft, int $transition, int $actor): array
    {
        $before = $this->structure((int) $source->id); $after = $this->structure((int) $draft->id);
        $diff = []; foreach ($before as $kind=>$items) { $diff[$kind] = self::changes($items, $after[$kind]); }
        $rulesBefore = self::canonical(json_decode($source->rules_json, true, 32, JSON_THROW_ON_ERROR));
        $rulesAfter = self::canonical(json_decode($draft->rules_json, true, 32, JSON_THROW_ON_ERROR));
        $query = $this->db->createQuery()->select('v.version_no,COUNT(e.id) AS total')->from('#__jlms_enrollments AS e')
            ->innerJoin('#__jlms_course_versions AS v ON v.id=e.course_version_id AND v.course_id=e.course_id')
            ->where('e.course_id=:course')->group('v.id,v.version_no')->order('v.version_no');
        $id = (int) $course->id; $query->bind(':course', $id, ParameterType::INTEGER);
        $enrollments = $this->db->setQuery($query, 0, 101)->loadAssocList();
        if (count($enrollments) > 100) { throw new \DomainException('COM_JLMS_OUTLINE_LIMIT'); }
        // Enrollment counts are informational at preview time; admissions do not invalidate a programme review.
        $fingerprint = self::hash([$id,(int)$course->revision,(int)$source->id,(int)$source->revision,(int)$draft->id,(int)$draft->revision,$transition,$actor,$before,$after,$rulesBefore,$rulesAfter,$draft->fields_snapshot_json]);
        return ['course'=>$course, 'version'=>$draft, 'source'=>$source, 'transition_id'=>$transition,
            'changes'=>$diff, 'rules_changed'=>$rulesBefore !== $rulesAfter, 'enrollments'=>$enrollments, 'fingerprint'=>$fingerprint];
    }
}
