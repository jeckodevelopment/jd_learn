<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Domain\QuestionDefinition;

final class QuestionBankService
{
    private array $optionCache = [];

    public function __construct(private readonly DatabaseInterface $db, private readonly LearningService $learning,
        private readonly \Closure $filter, private readonly \Closure $tableFactory)
    {
    }

    private function transaction(\Closure $work): mixed
    {
        $this->db->transactionStart();
        try { $result = $work(); $this->db->transactionCommit(); return $result; }
        catch (\Throwable $error) { $this->db->transactionRollback(); throw $error; }
    }

    private function row(int $id, bool $lock = false): object
    {
        $q = $this->db->createQuery()->select('*')->from('#__jlms_questions')->where('id = :id')->bind(':id', $id, ParameterType::INTEGER);
        if ($lock) { $q->setQuery((string) $q . ' FOR UPDATE'); }
        return $this->db->setQuery($q)->loadObject() ?: throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
    }

    private function access(User $actor, string $asset, bool $edit = false, ?object $row = null): void
    {
        $own = $row && (int) $row->created_by === (int) $actor->id && $actor->authorise('core.edit.own', $asset);
        if (!$actor->authorise('core.manage', 'com_jlms') || !$actor->authorise('jlms.quiz.manage', $asset)
            || ($edit && !$actor->authorise('core.edit', $asset) && !$own)) { throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403); }
    }

    private function category(User $actor, int $id): object
    {
        $q = $this->db->createQuery()->select('*')->from('#__categories')->where('id = :id')->bind(':id', $id, ParameterType::INTEGER);
        $row = $this->db->setQuery($q)->loadObject();
        if (!$row || $row->extension !== 'com_jlms.question') { throw new \RuntimeException('COM_JLMS_QUESTION_CATEGORY', 422); }
        $this->access($actor, 'com_jlms.question.category.' . $id);
        return $row;
    }

    public function save(User $identity, array $input): int
    {
        $actor = $this->learning->freshActor($identity);
        $id = CurriculumService::id($input['id'] ?? 0, true); $category = CurriculumService::id($input['catid'] ?? null);
        $expected = CurriculumService::id($input['revision'] ?? 1); $language = $input['language'] ?? '*';
        $state = QuestionDefinition::integer($input['state'] ?? 0, 0, 1);
        $this->category($actor, $category);
        if (!is_string($language) || ($language !== '*' && !preg_match('/\A[a-z]{2,3}-[A-Z]{2}\z/D', $language))) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
        if ($language !== '*') {
            $q = $this->db->createQuery()->select('COUNT(*)')->from('#__languages')->where('lang_code = :language')->bind(':language', $language);
            if (!(int) $this->db->setQuery($q)->loadResult()) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
        }
        if ($id) { $existing = $this->row($id); $this->access($actor, 'com_jlms.question.' . $id, true, $existing); }
        elseif (!$actor->authorise('core.create', 'com_jlms.question.category.' . $category)) { throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403); }
        $definition = QuestionDefinition::normalise($input, $this->filter);
        return $this->transaction(function () use ($identity, $id, $category, $expected, $language, $state, $definition): int {
            $actor = $this->learning->freshActor($identity); $this->category($actor, $category); $table = ($this->tableFactory)();
            $now = gmdate('Y-m-d H:i:s'); $version = 1;
            if ($id) {
                $record = $this->row($id, true); $this->access($actor, 'com_jlms.question.' . $id, true, $record);
                if ((int) $record->revision !== $expected || ($record->checked_out && (int) $record->checked_out !== (int) $actor->id)) { throw new \RuntimeException('COM_JLMS_REVISION_CONFLICT', 409); }
                if ((int) $record->catid !== $category && !$actor->authorise('core.create', 'com_jlms.question.category.' . $category)) { throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403); }
                if (!$table->load($id)) { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404); }
                $version = (int) $record->revision + 1;
            } else {
                if ($expected !== 1 || !$actor->authorise('core.create', 'com_jlms.question.category.' . $category)) { throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403); }
                $table->created_by = (int) $actor->id; $table->created_at = $now;
            }
            $asset = $id ? 'com_jlms.question.' . $id : 'com_jlms.question.category.' . $category;
            if ($state !== (int) ($record->state ?? 0) && !$actor->authorise('core.edit.state', $asset)) { throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403); }
            $table->catid = $category; $table->language = $language; $table->state = $state; $table->revision = $version;
            $table->modified_at = $now; $table->modified_by = (int) $actor->id;
            if (!$table->store(true)) { throw new \RuntimeException('COM_JLMS_SAVE_FAILED', 500); }
            $id = (int) $table->id;
            $revision = (object) ['question_id' => $id, 'version_no' => $version, 'kind' => $definition['kind'], 'prompt' => $definition['prompt'],
                'options_json' => QuestionDefinition::canonical($definition['options']), 'grading_json' => QuestionDefinition::canonical($definition['grading']),
                'feedback_json' => QuestionDefinition::canonical($definition['feedback']), 'content_hash' => hash('sha256', QuestionDefinition::canonical($definition)),
                'created_by' => (int) $actor->id, 'modified_by' => (int) $actor->id, 'created_at' => $now, 'modified_at' => $now];
            $this->db->insertObject('#__jlms_question_revisions', $revision, 'id');
            $update = (object) ['id' => $id, 'current_revision_id' => (int) $revision->id];
            $this->db->updateObject('#__jlms_questions', $update, 'id');
            if (!$table->checkin($id)) { throw new \RuntimeException('COM_JLMS_SAVE_FAILED', 500); }
            return $id;
        });
    }

    /** Answer keys are returned only after explicit native bank ACL. No frontend calls this DTO. */
    public function editor(User $identity, int $id): array
    {
        $actor = $this->learning->freshActor($identity); $question = $this->row($id); $this->access($actor, 'com_jlms.question.' . $id);
        $revision = (int) $question->current_revision_id;
        $q = $this->db->createQuery()->select('*')->from('#__jlms_question_revisions')->where('id = :id')->where('question_id = :question')
            ->bind(':id', $revision, ParameterType::INTEGER)->bind(':question', $id, ParameterType::INTEGER);
        $content = $this->db->setQuery($q)->loadObject() ?: throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        $options = json_decode($content->options_json, true, 16, JSON_THROW_ON_ERROR)['items'];
        $grading = json_decode($content->grading_json, true, 16, JSON_THROW_ON_ERROR);
        $rows = [];
        foreach ($options as $option) { $rows[] = ['option_id' => $option['id'], 'label' => $option['label'], 'correct' => in_array($option['id'], $grading['correct'], true) ? '1' : '0']; }
        return ['id' => $id, 'revision' => (int) $question->revision, 'catid' => (int) $question->catid, 'language' => $question->language,
            'state' => (int) $question->state, 'kind' => $content->kind, 'prompt' => $content->prompt, 'options' => $rows,
            'explanation' => json_decode($content->feedback_json, true, 16, JSON_THROW_ON_ERROR)['explanation'], 'default_points' => $grading['default_points'],
            'checked_out' => (int) $question->checked_out];
    }

    public function lock(User $identity, int $id, bool $release): void
    {
        $this->transaction(function () use ($identity, $id, $release): void {
            $actor = $this->learning->freshActor($identity); $row = $this->row($id, true); $this->access($actor, 'com_jlms.question.' . $id, !$release, $row);
            if ($row->checked_out && (int) $row->checked_out !== (int) $actor->id
                && (!$release || !$actor->authorise('core.manage', 'com_checkin'))) { throw new \RuntimeException('COM_JLMS_REVISION_CONFLICT', 409); }
            $table = ($this->tableFactory)(); $ok = $release ? $table->checkin($id) : $table->checkOut((int) $actor->id, $id);
            if (!$ok) { throw new \RuntimeException('COM_JLMS_SAVE_FAILED', 500); }
        });
    }

    public function listing(User $identity, int $before = 0, int $category = 0): array
    {
        $actor = $this->learning->freshActor($identity);
        if (!$actor->authorise('core.manage', 'com_jlms')) { throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403); }
        $q = $this->db->createQuery()->select('q.id, q.catid, q.language, q.state, q.revision, r.id AS revision_id, r.kind, r.prompt, c.title AS category_title')
            ->from('#__jlms_questions AS q')->innerJoin('#__jlms_question_revisions AS r ON r.id = q.current_revision_id')
            ->innerJoin('#__categories AS c ON c.id = q.catid')->order('q.id DESC');
        if ($before > 0) { $q->where('q.id < :before')->bind(':before', $before, ParameterType::INTEGER); }
        if ($category > 0) { $q->where('q.catid = :category')->bind(':category', $category, ParameterType::INTEGER); }
        $candidates = $this->db->setQuery($q, 0, 100)->loadObjectList(); $items = []; $cursor = 0;
        foreach ($candidates as $item) {
            $cursor = (int) $item->id;
            if ($actor->authorise('jlms.quiz.manage', 'com_jlms.question.' . $cursor)) { $items[] = $item; }
            if (count($items) === 20) { break; }
        }
        return ['items' => $items, 'next_cursor' => count($candidates) === 100 || count($items) === 20 ? $cursor : 0];
    }
    public function revisionOptions(User $identity, int $selected = 0): array
    {
        $actor = $this->learning->freshActor($identity);
        if (!$actor->authorise('core.manage', 'com_jlms')) { throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403); }
        $key = (int) $actor->id;
        if (!isset($this->optionCache[$key])) {
            $q = $this->db->createQuery()->select('r.id, r.question_id, r.version_no, r.prompt, c.title AS category_title')
                ->from('#__jlms_question_revisions AS r')->innerJoin('#__jlms_questions AS q ON q.current_revision_id = r.id')
                ->innerJoin('#__categories AS c ON c.id = q.catid')->where('q.state = 1 AND c.published = 1')->order('q.id DESC');
            $rows = $this->db->setQuery($q, 0, 200)->loadObjectList(); $options = [];
            foreach ($rows as $row) {
                if ($actor->authorise('jlms.quiz.manage', 'com_jlms.question.' . (int) $row->question_id)) { $options[(int) $row->id] = $row; }
            }
            $this->optionCache[$key] = $options;
        }
        $rows = $this->optionCache[$key];
        if ($selected > 0 && !isset($rows[$selected])) {
            $q = $this->db->createQuery()->select('r.id, r.question_id, r.version_no, r.prompt, c.title AS category_title')
                ->from('#__jlms_question_revisions AS r')->innerJoin('#__jlms_questions AS q ON q.id = r.question_id')
                ->innerJoin('#__categories AS c ON c.id = q.catid')->where('r.id = :selected')->bind(':selected', $selected, ParameterType::INTEGER);
            $row = $this->db->setQuery($q)->loadObject();
            if ($row && $actor->authorise('jlms.quiz.manage', 'com_jlms.question.' . (int) $row->question_id)) { $rows[$selected] = $row; }
        }
        return array_map(static fn ($row): array => ['id' => (int) $row->id,
            'label' => $row->category_title . ' — ' . \Joomla\String\StringHelper::substr(strip_tags($row->prompt),0,100) . ' (v' . (int) $row->version_no . ')'], array_values($rows));
    }

}
