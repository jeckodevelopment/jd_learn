<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Controller;

final class LessonController extends CurriculumController
{
    public function execute($task)
    {
        if (!in_array(strtolower($task), ['save', 'delete', 'reorder'], true)) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        return parent::execute($task);
    }

    public function save(): void
    {
        $this->perform('save', 'lesson');
    }

    public function delete(): void
    {
        $this->perform('delete', 'lesson');
    }

    public function reorder(): void
    {
        $this->perform('reorder', 'lesson');
    }
}
