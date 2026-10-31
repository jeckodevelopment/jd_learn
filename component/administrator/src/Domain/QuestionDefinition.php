<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Domain;

/** Public and grading documents stay separate from the first persisted revision. */
final class QuestionDefinition
{
    public static function integer(mixed $value, int $minimum, int $maximum): int
    {
        if ((!is_int($value) && !is_string($value)) || !preg_match('/\A(?:0|[1-9][0-9]{0,8})\z/D', (string) $value)
            || (int) $value < $minimum || (int) $value > $maximum) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
        return (int) $value;
    }

    public static function canonical(array $value): string
    {
        $normalise = static function (mixed $item) use (&$normalise): mixed {
            if (!is_array($item)) { return $item; }
            if (!array_is_list($item)) { ksort($item, SORT_STRING); }
            foreach ($item as &$child) { $child = $normalise($child); } unset($child);
            return $item;
        };
        return json_encode($normalise($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public static function normalise(array $input, \Closure $filter): array
    {
        $kind = $input['kind'] ?? '';
        if (!in_array($kind, ['single', 'true_false', 'multiple'], true) || !is_string($input['prompt'] ?? null)
            || !is_string($input['explanation'] ?? '') || strlen($input['prompt']) > 65536 || strlen($input['explanation'] ?? '') > 65536) {
            throw new \RuntimeException('COM_JLMS_QUESTION_INVALID', 422);
        }
        $prompt = $filter($input['prompt']); $feedback = $filter($input['explanation'] ?? '');
        if (trim(html_entity_decode(strip_tags($prompt), ENT_QUOTES | ENT_HTML5, 'UTF-8')) === '') { throw new \RuntimeException('COM_JLMS_QUESTION_INVALID', 422); }
        $rows = $input['options'] ?? null;
        if (!is_array($rows) || count($rows) < 2 || count($rows) > 20 || ($kind === 'true_false' && count($rows) !== 2)) {
            throw new \RuntimeException('COM_JLMS_QUESTION_INVALID', 422);
        }
        $options = []; $correct = []; $ids = []; $labels = [];
        foreach ($rows as $row) {
            if (!is_array($row) || !is_string($row['label'] ?? null) || !is_string($row['option_id'] ?? '')
                || !in_array($row['correct'] ?? '0', [0,1,'0','1'], true)) { throw new \RuntimeException('COM_JLMS_QUESTION_INVALID', 422); }
            $label = trim($row['label']); $id = $row['option_id'] ?? '';
            if ($id === '') { $id = bin2hex(random_bytes(12)); }
            if (!preg_match('/\A[A-Za-z0-9_-]{1,64}\z/D', $id) || isset($ids[$id]) || $label === '' || strlen($label) > 4096
                || preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', $label) || isset($labels[$label])) {
                throw new \RuntimeException('COM_JLMS_QUESTION_INVALID', 422);
            }
            $ids[$id] = true; $labels[$label] = true; $options[] = ['id' => $id, 'label' => $label];
            if ((string) ($row['correct'] ?? '0') === '1') { $correct[] = $id; }
        }
        if (!$correct || ($kind !== 'multiple' && count($correct) !== 1)) { throw new \RuntimeException('COM_JLMS_QUESTION_INVALID', 422); }
        $points = self::integer($input['default_points'] ?? 1, 1, 10000);
        $definition = ['kind' => $kind, 'prompt' => $prompt, 'options' => ['_schema' => 1, 'items' => $options],
            'grading' => ['_schema' => 1, 'correct' => $correct, 'default_points' => $points], 'feedback' => ['_schema' => 1, 'explanation' => $feedback]];
        if (strlen(self::canonical($definition)) > 262144) { throw new \RuntimeException('COM_JLMS_QUESTION_INVALID', 422); }
        return $definition;
    }
}
