<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Domain\QuestionDefinition;

/** Draft authoring only: no publication, attempts, grades or student credit. */
final class QuizAuthoringService
{
    public function __construct(private readonly DatabaseInterface $db, private readonly LearningService $learning) {}

    private function row(string $table, int $id, bool $lock = false): object
    {
        $q = $this->db->createQuery()->select('*')->from($table === 'categories' ? '#__categories' : '#__jlms_' . $table)->where('id = :id')->bind(':id', $id, ParameterType::INTEGER);
        if ($lock) { $q->setQuery((string) $q . ' FOR UPDATE'); }
        return $this->db->setQuery($q)->loadObject() ?: throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
    }

    private function scope(User $identity, int $course, int $version, bool $lock = false): array
    {
        $actor = $this->learning->freshActor($identity); $c = $this->row('courses', $course, $lock);
        $asset = 'com_jlms.course.' . $course;
        $own = (int) $c->created_by === (int) $actor->id && $actor->authorise('core.edit.own', $asset);
        if (!$actor->authorise('core.manage', 'com_jlms') || !$actor->authorise('jlms.quiz.manage', $asset)
            || (!$actor->authorise('core.edit', $asset) && !$own)) { throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403); }
        $v = $this->row('course_versions', $version, $lock);
        if ((int) $v->course_id !== $course) { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404); }
        if ($c->checked_out && (int) $c->checked_out !== (int) $actor->id) { throw new \RuntimeException('COM_JLMS_REVISION_CONFLICT', 409); }
        return [$actor, $c, $v];
    }

    public function editor(User $identity, int $course, int $version, int $lesson = 0): array
    {
        [, $c, $v] = $this->scope($identity, $course, $version);
        $q = $this->db->createQuery()->select('id, title')->from('#__jlms_modules')->where('course_version_id = :version')
            ->bind(':version', $version, ParameterType::INTEGER)->order('ordering, id');
        $modules = $this->db->setQuery($q, 0, 1000)->loadObjectList();
        $data = ['course_id' => $course, 'version_id' => $version, 'expected_version_revision' => (int) $v->revision, 'lesson_id' => 0,
            'revision' => 1, 'required' => '1', 'threshold' => '70', 'max_attempts' => 3, 'time_minutes' => 0, 'retry_minutes' => 0,
            'assessment' => 'graded', 'selection_mode' => 'fixed', 'feedback_policy' => 'pass_or_exhausted', 'questions' => []];
        if ($lesson) {
            $l = $this->row('lessons', $lesson);
            if ((int) $l->course_version_id !== $version || $l->kind !== 'quiz') { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404); }
            $q = $this->db->createQuery()->select('*')->from('#__jlms_quizzes')->where('lesson_id = :lesson')->bind(':lesson', $lesson, ParameterType::INTEGER);
            $quiz = $this->db->setQuery($q)->loadObject() ?: throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
            $selection = json_decode($quiz->selection_json, true, 16, JSON_THROW_ON_ERROR);
            $quizId = (int) $quiz->id;
            $q = $this->db->createQuery()->select('question_revision_id AS revision_id, points')->from('#__jlms_quiz_questions')->where('quiz_id = :quiz')
                ->bind(':quiz', $quizId, ParameterType::INTEGER)->order('ordering');
            $data = array_replace($data, ['lesson_id' => $lesson, 'revision' => (int) $quiz->revision, 'title' => $l->title,
                'module_id' => (int) $l->module_id, 'required' => (string) $l->required, 'threshold' => (string) ((int) $quiz->pass_basis_points / 100),
                'max_attempts' => (int) $quiz->max_attempts, 'time_minutes' => intdiv((int) $quiz->time_limit_seconds, 60),
                'retry_minutes' => intdiv($selection['retry_delay_seconds'], 60), 'assessment' => $selection['assessment'],
                'selection_mode' => $selection['mode'], 'draw_count' => $selection['draw_count'],
                'shuffle_questions' => (int) $selection['shuffle_questions'], 'shuffle_options' => (int) $selection['shuffle_options'],
                'feedback_policy' => $quiz->feedback_policy, 'questions' => $this->db->setQuery($q, 0, 200)->loadAssocList()]);
        }
        return ['course' => $c, 'version' => $v, 'modules' => $modules, 'data' => $data];
    }

    private static function flag(mixed $value): bool
    {
        if (!in_array($value, [0,1,'0','1'], true)) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
        return (string) $value === '1';
    }

    public function save(User $identity, array $input): int
    {
        $course = CurriculumService::id($input['course_id'] ?? null); $version = CurriculumService::id($input['version_id'] ?? null);
        $expected = CurriculumService::id($input['expected_version_revision'] ?? null); $lesson = CurriculumService::id($input['lesson_id'] ?? 0, true);
        $module = CurriculumService::id($input['module_id'] ?? null); $revision = CurriculumService::id($input['revision'] ?? 1);
        $this->scope($identity, $course, $version);
        $title = $input['title'] ?? null;
        if (!is_string($title) || trim($title) === '' || strlen($title) > 255 || preg_match('/[\x00-\x1f\x7f<>]/', $title)) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
        $required = self::flag($input['required'] ?? '1'); $assessment = $input['assessment'] ?? 'graded';
        $selectionMode = $input['selection_mode'] ?? 'fixed'; $feedback = $input['feedback_policy'] ?? 'pass_or_exhausted';
        $threshold = $input['threshold'] ?? '70';
        if (!is_string($threshold) || !preg_match('/\A([0-9]{1,3})(?:\.([0-9]{1,2}))?\z/D', $threshold, $match)) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
        $threshold = (int) $match[1] * 100 + (int) str_pad($match[2] ?? '', 2, '0');
        if ($threshold > 10000 || !in_array($assessment, ['graded', 'practice'], true) || ($required && ($assessment !== 'graded' || $threshold === 0))
            || !in_array($selectionMode, ['fixed', 'random'], true) || !in_array($feedback, ['pass_or_exhausted', 'never', 'after_submit'], true)
            || ($assessment === 'graded' && $feedback === 'after_submit')) { throw new \RuntimeException('COM_JLMS_QUIZ_INVALID', 422); }
        $attempts = QuestionDefinition::integer($input['max_attempts'] ?? 3, 0, 100);
        $minutes = QuestionDefinition::integer($input['time_minutes'] ?? 0, 0, 1440);
        $retry = QuestionDefinition::integer($input['retry_minutes'] ?? 0, 0, 10080);
        $rows = $input['questions'] ?? null;
        if (!is_array($rows) || count($rows) < 1 || count($rows) > 200) { throw new \RuntimeException('COM_JLMS_QUIZ_INVALID', 422); }
        $draw = $selectionMode === 'fixed' ? count($rows) : QuestionDefinition::integer($input['draw_count'] ?? 0, 1, count($rows));
        $selection = ['_schema' => 1, 'assessment' => $assessment, 'mode' => $selectionMode, 'draw_count' => $draw,
            'shuffle_questions' => self::flag($input['shuffle_questions'] ?? '0'), 'shuffle_options' => self::flag($input['shuffle_options'] ?? '0'), 'retry_delay_seconds' => $retry * 60];
        $this->db->transactionStart();
        try {
            [$actor, $c, $v] = $this->scope($identity, $course, $version, true);
            if ($v->status !== 'draft') { throw new \RuntimeException('COM_JLMS_VERSION_IMMUTABLE', 409); }
            if ((int) $v->revision !== $expected) { throw new \RuntimeException('COM_JLMS_REVISION_CONFLICT', 409); }
            if ((int) $this->row('modules', $module)->course_version_id !== $version) { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404); }
            $pool = []; $questions = [];
            foreach ($rows as $row) {
                if (!is_array($row)) { throw new \RuntimeException('COM_JLMS_QUIZ_INVALID', 422); }
                $id = CurriculumService::id($row['revision_id'] ?? null);
                $content = $this->row('question_revisions', $id); $question = $this->row('questions', (int) $content->question_id);
                if (isset($pool[$id]) || isset($questions[$question->id])) { throw new \RuntimeException('COM_JLMS_QUIZ_INVALID', 422); }
                if (!$actor->authorise('jlms.quiz.manage', 'com_jlms.question.' . (int) $question->id)) { throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403); }
                $category = $this->row('categories', (int) $question->catid);
                if ((int) $question->state !== 1 || (int) $category->published !== 1 || $category->extension !== 'com_jlms.question'
                    || !in_array($question->language, ['*', $c->language], true)) { throw new \RuntimeException('COM_JLMS_QUIZ_INVALID', 422); }
                $grading = json_decode($content->grading_json, true, 16, JSON_THROW_ON_ERROR);
                $pool[$id] = QuestionDefinition::integer($row['points'] ?? $grading['default_points'], 1, 10000); $questions[$question->id] = true;
            }
            $now = gmdate('Y-m-d H:i:s'); $quiz = null;
            if ($lesson) {
                $l = $this->row('lessons', $lesson, true);
                if ((int) $l->course_version_id !== $version || $l->kind !== 'quiz' || (int) $l->module_id !== $module) { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404); }
                $q = $this->db->createQuery()->select('*')->from('#__jlms_quizzes')->where('lesson_id = :lesson')->bind(':lesson', $lesson, ParameterType::INTEGER);
                $q->setQuery((string) $q . ' FOR UPDATE'); $quiz = $this->db->setQuery($q)->loadObject();
                if (!$quiz || (int) $quiz->revision !== $revision) { throw new \RuntimeException('COM_JLMS_REVISION_CONFLICT', 409); }
                $update = (object) ['id' => $lesson, 'title' => trim($title), 'required' => (int) $required, 'revision' => (int) $l->revision + 1,
                    'modified_by' => (int) $actor->id, 'modified_at' => $now]; $this->db->updateObject('#__jlms_lessons', $update, 'id');
            } else {
                $q = $this->db->createQuery()->select('COUNT(*)')->from('#__jlms_lessons')->where('course_version_id = :version')->bind(':version', $version, ParameterType::INTEGER);
                if ((int) $this->db->setQuery($q)->loadResult() >= 5000) { throw new \RuntimeException('COM_JLMS_OUTLINE_LIMIT', 409); }
                $q = $this->db->createQuery()->select('MAX(ordering)')->from('#__jlms_lessons')->where('module_id = :module')->bind(':module', $module, ParameterType::INTEGER);
                $order = (int) $this->db->setQuery($q)->loadResult() + 1;
                $l = (object) ['course_version_id' => $version, 'module_id' => $module, 'logical_key' => sprintf('%s-%s-4%s-a%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), substr(bin2hex(random_bytes(2)), 1), substr(bin2hex(random_bytes(2)), 1), bin2hex(random_bytes(6))), 'alias' => 'quiz-' . bin2hex(random_bytes(8)),
                    'title' => trim($title), 'kind' => 'quiz', 'required' => (int) $required, 'ordering' => $order, 'completion_rule' => 'quiz_pass',
                    'params_json' => '{}', 'fields_snapshot_json' => '{}', 'revision' => 1, 'created_by' => (int) $actor->id, 'modified_by' => (int) $actor->id, 'created_at' => $now, 'modified_at' => $now];
                $this->db->insertObject('#__jlms_lessons', $l, 'id'); $lesson = (int) $l->id;
            }
            $config = (object) ['lesson_id' => $lesson, 'course_version_id' => $version, 'pass_basis_points' => $threshold, 'max_attempts' => $attempts,
                'time_limit_seconds' => $minutes ? $minutes * 60 : null, 'feedback_policy' => $feedback, 'selection_json' => QuestionDefinition::canonical($selection),
                'revision' => $quiz ? (int) $quiz->revision + 1 : 1, 'modified_by' => (int) $actor->id, 'modified_at' => $now];
            if ($quiz) { $config->id = (int) $quiz->id; $this->db->updateObject('#__jlms_quizzes', $config, 'id', true); }
            else { $config->created_by = (int) $actor->id; $config->created_at = $now; $this->db->insertObject('#__jlms_quizzes', $config, 'id'); }
            $quizId = (int) $config->id;
            $q = $this->db->createQuery()->delete('#__jlms_quiz_questions')->where('quiz_id = :quiz')->bind(':quiz', $quizId, ParameterType::INTEGER); $this->db->setQuery($q)->execute();
            $order = 0;
            foreach ($pool as $id => $points) { $link = (object) ['quiz_id' => $quizId, 'question_revision_id' => $id, 'ordering' => $order++, 'points' => $points]; $this->db->insertObject('#__jlms_quiz_questions', $link); }
            $touch = (object) ['id' => $version, 'revision' => (int) $v->revision + 1, 'modified_by' => (int) $actor->id, 'modified_at' => $now];
            $this->db->updateObject('#__jlms_course_versions', $touch, 'id'); $this->db->transactionCommit(); return $lesson;
        } catch (\Throwable $error) { $this->db->transactionRollback(); throw $error; }
    }
    public function delete(User $identity, int $course, int $version, int $lesson, int $expected): void
    {
        $this->db->transactionStart();
        try {
            [$actor, , $v] = $this->scope($identity, $course, $version, true);
            if (!$actor->authorise('core.delete', 'com_jlms.course.' . $course)) { throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403); }
            if ($v->status !== 'draft') { throw new \RuntimeException('COM_JLMS_VERSION_IMMUTABLE', 409); }
            if ((int) $v->revision !== $expected) { throw new \RuntimeException('COM_JLMS_REVISION_CONFLICT', 409); }
            $l = $this->row('lessons', $lesson, true);
            if ($l->kind !== 'quiz' || (int) $l->course_version_id !== $version) { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404); }
            $q = $this->db->createQuery()->select('id')->from('#__jlms_quizzes')->where('lesson_id = :lesson')->bind(':lesson', $lesson, ParameterType::INTEGER);
            $quiz = (int) $this->db->setQuery($q)->loadResult();
            foreach ([['attempts','quiz_id',$quiz],['attachments','lesson_id',$lesson],['prerequisites','lesson_id',$lesson],['prerequisites','required_lesson_id',$lesson]] as [$table,$column,$id]) {
                $q = $this->db->createQuery()->select('COUNT(*)')->from('#__jlms_' . $table)->where($column . ' = :id')->bind(':id', $id, ParameterType::INTEGER);
                if ((int) $this->db->setQuery($q)->loadResult()) { throw new \RuntimeException('COM_JLMS_ITEM_HAS_DEPENDENCIES', 409); }
            }
            foreach ([['quiz_questions','quiz_id',$quiz],['quizzes','id',$quiz],['lessons','id',$lesson]] as [$table,$column,$id]) {
                $q = $this->db->createQuery()->delete('#__jlms_' . $table)->where($column . ' = :id')->bind(':id', $id, ParameterType::INTEGER); $this->db->setQuery($q)->execute();
            }
            $touch = (object) ['id' => $version, 'revision' => (int) $v->revision + 1, 'modified_by' => (int) $actor->id, 'modified_at' => gmdate('Y-m-d H:i:s')];
            $this->db->updateObject('#__jlms_course_versions', $touch, 'id'); $this->db->transactionCommit();
        } catch (\Throwable $error) { $this->db->transactionRollback(); throw $error; }
    }

}
