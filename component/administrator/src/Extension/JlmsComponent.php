<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Extension;

use Joomla\CMS\Extension\MVCComponent;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;
use Joomla\CMS\Categories\CategoryServiceInterface;
use Joomla\CMS\Categories\CategoryServiceTrait;

final class JlmsComponent extends MVCComponent implements CategoryServiceInterface
{
    use CategoryServiceTrait;

    private CurriculumService $curriculumService;

    public function setCurriculumService(CurriculumService $service): void
    {
        $this->curriculumService = $service;
    }

    public function getCurriculumService(): CurriculumService
    {
        return $this->curriculumService;
    }


    protected function getTableNameForSection(?string $section = null)
    {
        return match ($section) {
            'question' => 'jlms_questions',
            'teacher' => 'jlms_teachers',
            'pathway' => 'jlms_pathways',
            'category' => 'categories',
            default => 'jlms_courses',
        };
    }

    protected function getStateColumnForSection(?string $section = null)
    {
        return $section === 'category' ? 'published' : 'state';
    }
}
