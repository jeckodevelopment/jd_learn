<?php

declare(strict_types=1);

\defined("_JEXEC") or die();

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\CategoryFactory;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Jlms\Contracts\Video\VideoProviderRegistry;
use Jlms\Contracts\Event\RegisterVideoProvidersEvent;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;
use Jlms\Component\Jlms\Administrator\Application\PublicationService;
use Jlms\Component\Jlms\Administrator\Application\MailQueueAuditService;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Jlms\Component\Jlms\Administrator\Extension\JlmsComponent;
use Jlms\Component\Jlms\Administrator\Application\CatalogService;
use Jlms\Component\Jlms\Administrator\Application\CategoryReassignmentService;
use Jlms\Component\Jlms\Administrator\Application\CertificateService;
use Jlms\Component\Jlms\Administrator\Application\CourseAnnouncementConsent;
use Jlms\Component\Jlms\Administrator\Application\CourseAnnouncementService;
use Jlms\Component\Jlms\Administrator\Application\CourseCommunicationService;
use Jlms\Component\Jlms\Administrator\Application\CourseOperationsService;
use Jlms\Component\Jlms\Administrator\Application\DashboardService;
use Jlms\Component\Jlms\Administrator\Application\EnrollmentApprovalService;
use Jlms\Component\Jlms\Administrator\Application\EnrollmentMigrationService;
use Jlms\Component\Jlms\Administrator\Application\EnrollmentRosterService;
use Jlms\Component\Jlms\Administrator\Application\EnrollmentStateService;
use Jlms\Component\Jlms\Administrator\Application\LearningService;
use Jlms\Component\Jlms\Administrator\Application\LocalVideoService;
use Jlms\Component\Jlms\Administrator\Application\ManualEnrollmentService;
use Jlms\Component\Jlms\Administrator\Application\MaterialService;
use Jlms\Component\Jlms\Administrator\Application\PathwayService;
use Jlms\Component\Jlms\Administrator\Application\QuestionBankService;
use Jlms\Component\Jlms\Administrator\Application\QuizAttemptService;
use Jlms\Component\Jlms\Administrator\Application\QuizAuthoringService;
use Jlms\Component\Jlms\Administrator\Application\QuizDeadlineService;
use Jlms\Component\Jlms\Administrator\Application\QuizReviewService;
use Jlms\Component\Jlms\Administrator\Application\QuizSnapshotService;
use Jlms\Component\Jlms\Administrator\Application\QuizTimeService;
use Jlms\Component\Jlms\Administrator\Application\VideoProgressService;
use Jlms\Component\Jlms\Administrator\Helper\AssociationsHelper;
use Jlms\Component\Jlms\Administrator\Infrastructure\AssessmentAudit;
use Jlms\Component\Jlms\Administrator\Infrastructure\ProtectedMaterialStorage;
use Jlms\Component\Jlms\Administrator\Infrastructure\ProtectedVideoStorage;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\User\User;
use Joomla\Registry\Registry;

require_once JPATH_LIBRARIES . "/jlms/bootstrap.php";

return new class implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->registerServiceProvider(new MVCFactory("\\Jlms\\Component\\Jlms"));
        $container->registerServiceProvider(new ComponentDispatcherFactory("\\Jlms\\Component\\Jlms"));
        $container->registerServiceProvider(new RouterFactory("\\Jlms\\Component\\Jlms"));
        $container->registerServiceProvider(new CategoryFactory("\\Jlms\\Component\\Jlms"));
        $container->set(
            MailQueueAuditService::class,
            static fn(Container $c): MailQueueAuditService => new MailQueueAuditService(
                $c->get(DatabaseInterface::class),
            ),
        );
        $container->set(CurriculumService::class, static function (Container $container): CurriculumService {
            $registry = null;
            return new CurriculumService(
                $container->get(DatabaseInterface::class),
                static fn(string $body): string => ComponentHelper::filterText($body),
                static function () use (&$registry): VideoProviderRegistry {
                    if ($registry === null) {
                        $registry = new VideoProviderRegistry();
                        $dispatcher = Factory::getApplication()->getDispatcher();
                        PluginHelper::importPlugin("jlmsvideo", null, true, $dispatcher);
                        $dispatcher->dispatch(
                            RegisterVideoProvidersEvent::NAME,
                            new RegisterVideoProvidersEvent($registry),
                        );
                    }
                    return $registry;
                },
            );
        });
        $container->set(
            CategoryReassignmentService::class,
            static fn(Container $c) => new CategoryReassignmentService($c->get(DatabaseInterface::class)),
        );
        $container->set(
            CatalogService::class,
            static fn(Container $c) => new CatalogService($c->get(DatabaseInterface::class)),
        );
        $container->set(
            LearningService::class,
            static fn(Container $c) => new LearningService(
                $c->get(DatabaseInterface::class),
                $c->get(CatalogService::class),
            ),
        );
        $container->set(
            ProtectedMaterialStorage::class,
            static fn() => new ProtectedMaterialStorage(static fn() => ComponentHelper::getParams("com_jlms")),
        );
        $container->set(
            MaterialService::class,
            static fn(Container $c) => new MaterialService(
                $c->get(DatabaseInterface::class),
                $c->get(LearningService::class),
                $c->get(ProtectedMaterialStorage::class),
            ),
        );
        $container->set(
            CertificateService::class,
            static fn(Container $c) => new CertificateService(
                $c->get(DatabaseInterface::class),
                $c->get(ProtectedMaterialStorage::class),
            ),
        );
        $container->set(
            CourseAnnouncementConsent::class,
            static fn(Container $c) => new CourseAnnouncementConsent($c->get(DatabaseInterface::class)),
        );
        $container->set(
            CourseAnnouncementService::class,
            static fn(Container $c) => new CourseAnnouncementService($c->get(DatabaseInterface::class)),
        );
        $container->set(
            VideoProgressService::class,
            static fn(Container $c) => new VideoProgressService(
                $c->get(DatabaseInterface::class),
                $c->get(LearningService::class),
                $c->get(CurriculumService::class),
                static fn(): int => (int) floor(microtime(true) * 1000),
                $c->get(LocalVideoService::class),
            ),
        );
        $container->set(
            ProtectedVideoStorage::class,
            static fn() => new ProtectedVideoStorage(static fn() => ComponentHelper::getParams("com_jlms")),
        );
        $container->set(
            LocalVideoService::class,
            static fn(Container $c) => new LocalVideoService(
                $c->get(DatabaseInterface::class),
                $c->get(LearningService::class),
                $c->get(ProtectedVideoStorage::class),
                static function (): bool {
                    return PluginHelper::isEnabled("jlmsvideo", "html5") &&
                        (int) (new Registry(PluginHelper::getPlugin("jlmsvideo", "html5")->params ?? "{}"))->get(
                            "playback_enabled",
                            0,
                        ) === 1;
                },
            ),
        );
        $container->set(
            QuestionBankService::class,
            static fn(Container $c) => new QuestionBankService(
                $c->get(DatabaseInterface::class),
                $c->get(LearningService::class),
                static fn(string $html): string => ComponentHelper::filterText($html),
                static fn() => Factory::getApplication()
                    ->bootComponent("com_jlms")
                    ->getMVCFactory()
                    ->createTable("Question", "Administrator"),
            ),
        );
        $container->set(
            QuizAuthoringService::class,
            static fn(Container $c) => new QuizAuthoringService(
                $c->get(DatabaseInterface::class),
                $c->get(LearningService::class),
            ),
        );
        $container->set(
            QuizSnapshotService::class,
            static fn(Container $c) => new QuizSnapshotService($c->get(DatabaseInterface::class)),
        );
        $container->set(
            QuizTimeService::class,
            static fn(Container $c) => new QuizTimeService(
                $c->get(DatabaseInterface::class),
                $c->get(LearningService::class),
                static function (User $actor, array $message) use ($c): void {
                    (new AssessmentAudit($c->get(DatabaseInterface::class)))->record($actor, $message);
                },
            ),
        );
        $container->set(
            QuizDeadlineService::class,
            static fn(Container $c) => new QuizDeadlineService(
                $c->get(DatabaseInterface::class),
                $c->get(QuizTimeService::class),
                static function (User $actor, array $message) use ($c): void {
                    (new AssessmentAudit($c->get(DatabaseInterface::class)))->record($actor, $message, "quizextend");
                },
                static fn(): int => time(),
            ),
        );
        $container->set(
            QuizReviewService::class,
            static fn(Container $c) => new QuizReviewService(
                $c->get(DatabaseInterface::class),
                $c->get(QuizTimeService::class),
                static function (User $actor, array $message) use ($c): void {
                    (new AssessmentAudit($c->get(DatabaseInterface::class)))->record($actor, $message, "quizcancel");
                },
            ),
        );
        $container->set(
            QuizAttemptService::class,
            static fn(Container $c) => new QuizAttemptService(
                $c->get(DatabaseInterface::class),
                $c->get(LearningService::class),
                $c->get(QuizSnapshotService::class),
                static fn(): int => time(),
                $c->get(QuizTimeService::class),
            ),
        );
        $container->set(
            DashboardService::class,
            static fn(Container $c) => new DashboardService(
                $c->get(DatabaseInterface::class),
                $c->get(LearningService::class),
                $c->get(CatalogService::class),
            ),
        );
        $container->set(
            PathwayService::class,
            static fn(Container $c) => new PathwayService(
                $c->get(DatabaseInterface::class),
                $c->get(LearningService::class),
                $c->get(CatalogService::class),
            ),
        );
        $container->set(
            PublicationService::class,
            static fn(Container $c): PublicationService => new PublicationService(
                $c->get(DatabaseInterface::class),
                Factory::getApplication(),
            ),
        );
        $container->set(
            EnrollmentApprovalService::class,
            static fn(Container $c) => new EnrollmentApprovalService(
                $c->get(DatabaseInterface::class),
                $c->get(LearningService::class),
                $c->get(EnrollmentRosterService::class),
                $c->get(CatalogService::class),
                static function (User $actor, array $message) use ($c): void {
                    (new AssessmentAudit($c->get(DatabaseInterface::class)))->record(
                        $actor,
                        $message,
                        "enrollmentapproval",
                    );
                },
            ),
        );
        $container->set(
            EnrollmentRosterService::class,
            static fn(Container $c) => new EnrollmentRosterService(
                $c->get(DatabaseInterface::class),
                $c->get(LearningService::class),
            ),
        );
        $container->set(
            CourseCommunicationService::class,
            static fn(Container $c) => new CourseCommunicationService(
                $c->get(DatabaseInterface::class),
                $c->get(LearningService::class),
                $c->get(EnrollmentRosterService::class),
            ),
        );
        $container->set(
            EnrollmentStateService::class,
            static fn(Container $c) => new EnrollmentStateService(
                $c->get(DatabaseInterface::class),
                $c->get(LearningService::class),
                $c->get(EnrollmentRosterService::class),
                static function (User $actor, array $message) use ($c): void {
                    (new AssessmentAudit($c->get(DatabaseInterface::class)))->record(
                        $actor,
                        $message,
                        "enrollmentstate",
                    );
                },
            ),
        );
        $container->set(
            EnrollmentMigrationService::class,
            static fn(Container $c) => new EnrollmentMigrationService(
                $c->get(DatabaseInterface::class),
                $c->get(LearningService::class),
                $c->get(EnrollmentRosterService::class),
                static function (User $actor, array $message) use ($c): void {
                    (new AssessmentAudit($c->get(DatabaseInterface::class)))->record(
                        $actor,
                        $message,
                        "enrollmentmigration",
                    );
                },
            ),
        );
        $container->set(
            CourseOperationsService::class,
            static fn(Container $c) => new CourseOperationsService(
                $c->get(DatabaseInterface::class),
                $c->get(EnrollmentRosterService::class),
                static function (User $actor, array $message) use ($c): void {
                    (new AssessmentAudit($c->get(DatabaseInterface::class)))->record(
                        $actor,
                        $message,
                        "courseoperations",
                    );
                },
            ),
        );
        $container->set(
            ManualEnrollmentService::class,
            static fn(Container $c) => new ManualEnrollmentService(
                $c->get(DatabaseInterface::class),
                $c->get(LearningService::class),
                $c->get(CatalogService::class),
                $c->get(EnrollmentRosterService::class),
                static function (User $actor, array $message) use ($c): void {
                    (new AssessmentAudit($c->get(DatabaseInterface::class)))->record(
                        $actor,
                        $message,
                        "manualenrollment",
                    );
                },
            ),
        );
        $container->set(ComponentInterface::class, static function (Container $container): JlmsComponent {
            $component = new JlmsComponent($container->get(ComponentDispatcherFactoryInterface::class));
            $component->setAssociationExtension(new AssociationsHelper());
            $component->setMVCFactory($container->get(MVCFactoryInterface::class));
            $component->setCurriculumService($container->get(CurriculumService::class));
            $component->setCategoryReassignmentService($container->get(CategoryReassignmentService::class));
            $component->setPublicationService($container->get(PublicationService::class));
            $component->setCatalogService($container->get(CatalogService::class));
            $component->setDashboardService($container->get(DashboardService::class));
            $component->setPathwayService($container->get(PathwayService::class));
            $component->setManualEnrollmentService($container->get(ManualEnrollmentService::class));
            $component->setEnrollmentStateService($container->get(EnrollmentStateService::class));
            $component->setEnrollmentMigrationService($container->get(EnrollmentMigrationService::class));
            $component->setCourseOperationsService($container->get(CourseOperationsService::class));
            $component->setEnrollmentRosterService($container->get(EnrollmentRosterService::class));
            $component->setEnrollmentApprovalService($container->get(EnrollmentApprovalService::class));
            $component->setCourseCommunicationService($container->get(CourseCommunicationService::class));
            $component->setLearningService($container->get(LearningService::class));
            $component->setMaterialService($container->get(MaterialService::class));
            $component->setCertificateService($container->get(CertificateService::class));
            $component->setCourseAnnouncementConsent($container->get(CourseAnnouncementConsent::class));
            $component->setCourseAnnouncementService($container->get(CourseAnnouncementService::class));
            $component->setVideoProgressService($container->get(VideoProgressService::class));
            $component->setLocalVideoService($container->get(LocalVideoService::class));
            $component->setQuestionBankService($container->get(QuestionBankService::class));
            $component->setQuizAuthoringService($container->get(QuizAuthoringService::class));
            $component->setQuizSnapshotService($container->get(QuizSnapshotService::class));
            $component->setQuizDeadlineService($container->get(QuizDeadlineService::class));
            $component->setQuizReviewService($container->get(QuizReviewService::class));
            $component->setQuizTimeService($container->get(QuizTimeService::class));
            $component->setQuizAttemptService($container->get(QuizAttemptService::class));
            $component->setRouterFactory($container->get(RouterFactoryInterface::class));
            $component->setCategoryFactory($container->get(CategoryFactoryInterface::class));
            return $component;
        });
    }
};
