<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Extension;

use Joomla\CMS\Extension\MVCComponent;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;
use Joomla\CMS\Categories\CategoryServiceInterface;
use Joomla\CMS\Categories\CategoryServiceTrait;

final class JlmsComponent extends MVCComponent implements \Joomla\CMS\Fields\FieldsServiceInterface, \Joomla\CMS\Association\AssociationServiceInterface, CategoryServiceInterface, \Joomla\CMS\Component\Router\RouterServiceInterface, \Joomla\CMS\Workflow\WorkflowServiceInterface
{
    public function validateSection($section, $item = null)
    {
        return in_array($section, ['teacher', 'course', 'lesson', 'pathway'], true) ? $section : null;
    }

    public function getContexts(): array
    {
        return ['com_jlms.teacher' => \Joomla\CMS\Language\Text::_('COM_JLMS_TEACHERS'),
            'com_jlms.course' => \Joomla\CMS\Language\Text::_('COM_JLMS_COURSES'),
            'com_jlms.lesson' => \Joomla\CMS\Language\Text::_('COM_JLMS_LESSONS')];
    }

    private \Jlms\Component\Jlms\Administrator\Application\CategoryReassignmentService $categoryReassignmentService;
    public function setCategoryReassignmentService(\Jlms\Component\Jlms\Administrator\Application\CategoryReassignmentService $service): void { $this->categoryReassignmentService = $service; }
    public function getCategoryReassignmentService(): \Jlms\Component\Jlms\Administrator\Application\CategoryReassignmentService { return $this->categoryReassignmentService; }

    private \Jlms\Component\Jlms\Administrator\Application\DashboardService $dashboardService;
    public function setDashboardService(\Jlms\Component\Jlms\Administrator\Application\DashboardService $service): void { $this->dashboardService = $service; }
    public function getDashboardService(): \Jlms\Component\Jlms\Administrator\Application\DashboardService { return $this->dashboardService; }

    private \Jlms\Component\Jlms\Administrator\Application\PathwayService $pathwayService;
    public function setPathwayService(\Jlms\Component\Jlms\Administrator\Application\PathwayService $service): void { $this->pathwayService = $service; }
    public function getPathwayService(): \Jlms\Component\Jlms\Administrator\Application\PathwayService { return $this->pathwayService; }

    private \Jlms\Component\Jlms\Administrator\Application\EnrollmentRosterService $enrollmentRosterService;
    public function setEnrollmentRosterService(\Jlms\Component\Jlms\Administrator\Application\EnrollmentRosterService $service): void { $this->enrollmentRosterService = $service; }
    public function getEnrollmentRosterService(): \Jlms\Component\Jlms\Administrator\Application\EnrollmentRosterService { return $this->enrollmentRosterService; }

    private \Jlms\Component\Jlms\Administrator\Application\CourseCommunicationService $courseCommunicationService;
    public function setCourseCommunicationService(\Jlms\Component\Jlms\Administrator\Application\CourseCommunicationService $service): void { $this->courseCommunicationService = $service; }
    public function getCourseCommunicationService(): \Jlms\Component\Jlms\Administrator\Application\CourseCommunicationService { return $this->courseCommunicationService; }

    private \Jlms\Component\Jlms\Administrator\Application\EnrollmentStateService $enrollmentStateService;
    public function setEnrollmentStateService(\Jlms\Component\Jlms\Administrator\Application\EnrollmentStateService $service): void { $this->enrollmentStateService = $service; }
    public function getEnrollmentStateService(): \Jlms\Component\Jlms\Administrator\Application\EnrollmentStateService { return $this->enrollmentStateService; }

    private \Jlms\Component\Jlms\Administrator\Application\EnrollmentMigrationService $enrollmentMigrationService;
    public function setEnrollmentMigrationService(\Jlms\Component\Jlms\Administrator\Application\EnrollmentMigrationService $service): void { $this->enrollmentMigrationService = $service; }
    public function getEnrollmentMigrationService(): \Jlms\Component\Jlms\Administrator\Application\EnrollmentMigrationService { return $this->enrollmentMigrationService; }

    private \Jlms\Component\Jlms\Administrator\Application\CourseOperationsService $courseOperationsService;
    public function setCourseOperationsService(\Jlms\Component\Jlms\Administrator\Application\CourseOperationsService $service): void { $this->courseOperationsService = $service; }
    public function getCourseOperationsService(): \Jlms\Component\Jlms\Administrator\Application\CourseOperationsService { return $this->courseOperationsService; }

    private \Jlms\Component\Jlms\Administrator\Application\ManualEnrollmentService $manualEnrollmentService;
    public function setManualEnrollmentService(\Jlms\Component\Jlms\Administrator\Application\ManualEnrollmentService $service): void { $this->manualEnrollmentService = $service; }
    public function getManualEnrollmentService(): \Jlms\Component\Jlms\Administrator\Application\ManualEnrollmentService { return $this->manualEnrollmentService; }

    use CategoryServiceTrait;
    use \Joomla\CMS\Association\AssociationServiceTrait;

    private \Jlms\Component\Jlms\Administrator\Application\QuizDeadlineService $quizDeadlineService;
    public function setQuizDeadlineService(\Jlms\Component\Jlms\Administrator\Application\QuizDeadlineService $service): void { $this->quizDeadlineService = $service; }
    public function getQuizDeadlineService(): \Jlms\Component\Jlms\Administrator\Application\QuizDeadlineService { return $this->quizDeadlineService; }
    private \Jlms\Component\Jlms\Administrator\Application\QuizReviewService $quizReviewService;
    public function setQuizReviewService(\Jlms\Component\Jlms\Administrator\Application\QuizReviewService $service): void { $this->quizReviewService = $service; }
    public function getQuizReviewService(): \Jlms\Component\Jlms\Administrator\Application\QuizReviewService { return $this->quizReviewService; }
    private \Jlms\Component\Jlms\Administrator\Application\QuizTimeService $quizTimeService;
    public function setQuizTimeService(\Jlms\Component\Jlms\Administrator\Application\QuizTimeService $service): void { $this->quizTimeService = $service; }
    public function getQuizTimeService(): \Jlms\Component\Jlms\Administrator\Application\QuizTimeService { return $this->quizTimeService; }
    private \Jlms\Component\Jlms\Administrator\Application\QuizSnapshotService $quizSnapshotService;
    public function setQuizSnapshotService(\Jlms\Component\Jlms\Administrator\Application\QuizSnapshotService $service): void { $this->quizSnapshotService = $service; }
    public function getQuizSnapshotService(): \Jlms\Component\Jlms\Administrator\Application\QuizSnapshotService { return $this->quizSnapshotService; }
    private \Jlms\Component\Jlms\Administrator\Application\QuizAttemptService $quizAttemptService;
    public function setQuizAttemptService(\Jlms\Component\Jlms\Administrator\Application\QuizAttemptService $service): void { $this->quizAttemptService = $service; }
    public function getQuizAttemptService(): \Jlms\Component\Jlms\Administrator\Application\QuizAttemptService { return $this->quizAttemptService; }
    private \Jlms\Component\Jlms\Administrator\Application\QuestionBankService $questionBankService;
    public function setQuestionBankService(\Jlms\Component\Jlms\Administrator\Application\QuestionBankService $service): void { $this->questionBankService = $service; }
    public function getQuestionBankService(): \Jlms\Component\Jlms\Administrator\Application\QuestionBankService { return $this->questionBankService; }
    private \Jlms\Component\Jlms\Administrator\Application\QuizAuthoringService $quizAuthoringService;
    public function setQuizAuthoringService(\Jlms\Component\Jlms\Administrator\Application\QuizAuthoringService $service): void { $this->quizAuthoringService = $service; }
    public function getQuizAuthoringService(): \Jlms\Component\Jlms\Administrator\Application\QuizAuthoringService { return $this->quizAuthoringService; }

    private \Jlms\Component\Jlms\Administrator\Application\LocalVideoService $localVideoService;
    public function setLocalVideoService(\Jlms\Component\Jlms\Administrator\Application\LocalVideoService $service): void { $this->localVideoService = $service; }
    public function getLocalVideoService(): \Jlms\Component\Jlms\Administrator\Application\LocalVideoService { return $this->localVideoService; }

    private \Jlms\Component\Jlms\Administrator\Application\VideoProgressService $videoProgressService;

    public function setVideoProgressService(\Jlms\Component\Jlms\Administrator\Application\VideoProgressService $service): void
    {
        $this->videoProgressService = $service;
    }

    public function getVideoProgressService(): \Jlms\Component\Jlms\Administrator\Application\VideoProgressService
    {
        return $this->videoProgressService;
    }

    private \Jlms\Component\Jlms\Administrator\Application\MaterialService $materialService;

    public function setMaterialService(\Jlms\Component\Jlms\Administrator\Application\MaterialService $service): void
    {
        $this->materialService = $service;
    }

    public function getMaterialService(): \Jlms\Component\Jlms\Administrator\Application\MaterialService
    {
        return $this->materialService;
    }

    private \Jlms\Component\Jlms\Administrator\Application\CertificateService $certificateService;

    public function setCertificateService(\Jlms\Component\Jlms\Administrator\Application\CertificateService $service): void
    {
        $this->certificateService = $service;
    }

    public function getCertificateService(): \Jlms\Component\Jlms\Administrator\Application\CertificateService
    {
        return $this->certificateService;
    }

    private \Jlms\Component\Jlms\Administrator\Application\CourseAnnouncementConsent $courseAnnouncementConsent;
    public function setCourseAnnouncementConsent(\Jlms\Component\Jlms\Administrator\Application\CourseAnnouncementConsent $service): void { $this->courseAnnouncementConsent = $service; }
    public function getCourseAnnouncementConsent(): \Jlms\Component\Jlms\Administrator\Application\CourseAnnouncementConsent { return $this->courseAnnouncementConsent; }

    private \Jlms\Component\Jlms\Administrator\Application\CourseAnnouncementService $courseAnnouncementService;
    public function setCourseAnnouncementService(\Jlms\Component\Jlms\Administrator\Application\CourseAnnouncementService $service): void { $this->courseAnnouncementService = $service; }
    public function getCourseAnnouncementService(): \Jlms\Component\Jlms\Administrator\Application\CourseAnnouncementService { return $this->courseAnnouncementService; }


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
