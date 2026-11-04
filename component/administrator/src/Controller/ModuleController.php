<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Controller;

final class ModuleController extends CurriculumController
{
    public function execute($task)
    {
        if (!in_array(strtolower($task), ['save', 'delete', 'reorder', 'duplicate'], true)) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        return parent::execute($task);
    }

    public function duplicate(): void { $this->perform('duplicate', 'module'); }

    public function save(): void
    {
        $this->perform('save', 'module');
    }

    public function delete(): void
    {
        $this->perform('delete', 'module');
    }

    public function reorder(): void
    {
        $this->perform('reorder', 'module');
    }
}
