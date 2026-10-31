<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Extension;

use Joomla\CMS\Extension\MVCComponent;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;
use Joomla\CMS\Categories\CategoryServiceInterface;
use Joomla\CMS\Categories\CategoryServiceTrait;

final class JlmsComponent extends MVCComponent implements CategoryServiceInterface, \Joomla\CMS\Component\Router\RouterServiceInterface, \Joomla\CMS\Workflow\WorkflowServiceInterface
{
    use CategoryServiceTrait;

    private \Jlms\Component\Jlms\Administrator\Application\LearningService $learningService;

    public function setLearningService(\Jlms\Component\Jlms\Administrator\Application\LearningService $service): void
    {
        $this->learningService = $service;
    }

    public function getLearningService(): \Jlms\Component\Jlms\Administrator\Application\LearningService
    {
        return $this->learningService;
    }

    use \Joomla\CMS\Component\Router\RouterServiceTrait;

    private \Jlms\Component\Jlms\Administrator\Application\CatalogService $catalogService;

    public function setCatalogService(\Jlms\Component\Jlms\Administrator\Application\CatalogService $service): void
    {
        $this->catalogService = $service;
    }

    public function getCatalogService(): \Jlms\Component\Jlms\Administrator\Application\CatalogService
    {
        return $this->catalogService;
    }

    use \Joomla\CMS\Workflow\WorkflowServiceTrait;

    protected $supportedFunctionality = ['core.state' => ['com_jlms.course']];
    private \Jlms\Component\Jlms\Administrator\Application\PublicationService $publicationService;

    public function setPublicationService(\Jlms\Component\Jlms\Administrator\Application\PublicationService $service): void
    {
        $this->publicationService = $service;
    }

    public function getPublicationService(): \Jlms\Component\Jlms\Administrator\Application\PublicationService
    {
        return $this->publicationService;
    }

    public function isWorkflowActive($context): bool
    {
        return $context === 'com_jlms.course';
    }

    public function getWorkflowContexts(): array
    {
        return ['com_jlms.course' => 'JD Learn'];
    }

    public function getCategoryWorkflowContext(?string $section = null): string
    {
        return $section === 'course' ? 'com_jlms.course' : '';
    }

    public function getWorkflowTableBySection(?string $section = null): string
    {
        return '#__jlms_courses';
    }

    public function filterTransitions(array $transitions, int $pk): array
    {
        $allowed = array_column($this->publicationService->transitions(\Joomla\CMS\Factory::getApplication()->getIdentity(), $pk), 'id');
        return array_values(array_filter($transitions, static fn ($t): bool => in_array($t->id, $allowed)));
    }


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
