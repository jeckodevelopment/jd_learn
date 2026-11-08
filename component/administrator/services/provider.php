<?php

declare(strict_types=1);


\defined('_JEXEC') or die;

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
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Jlms\Component\Jlms\Administrator\Extension\JlmsComponent;

require_once JPATH_LIBRARIES . '/jlms/bootstrap.php';

return new class () implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->registerServiceProvider(new MVCFactory('\\Jlms\\Component\\Jlms'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Jlms\\Component\\Jlms'));
        $container->registerServiceProvider(new \Joomla\CMS\Extension\Service\Provider\RouterFactory('\\Jlms\\Component\\Jlms'));
        $container->registerServiceProvider(new CategoryFactory('\\Jlms\\Component\\Jlms'));
        $container->set(CurriculumService::class, static function (Container $container): CurriculumService {
            $registry = null;
            return new CurriculumService(
                $container->get(DatabaseInterface::class),
                static fn (string $body): string => ComponentHelper::filterText($body),
                static function () use (&$registry): VideoProviderRegistry {
                    if ($registry === null) {
                        $registry = new VideoProviderRegistry();
                        $dispatcher = Factory::getApplication()->getDispatcher();
                        PluginHelper::importPlugin('jlmsvideo', null, true, $dispatcher);
                        $dispatcher->dispatch(RegisterVideoProvidersEvent::NAME, new RegisterVideoProvidersEvent($registry));
                    }
                    return $registry;
                },
            );
        });
        $container->set(\Jlms\Component\Jlms\Administrator\Application\CatalogService::class, static fn (Container $c) => new \Jlms\Component\Jlms\Administrator\Application\CatalogService($c->get(DatabaseInterface::class)));
        $container->set(\Jlms\Component\Jlms\Administrator\Application\LearningService::class, static fn (Container $c) => new \Jlms\Component\Jlms\Administrator\Application\LearningService($c->get(DatabaseInterface::class), $c->get(\Jlms\Component\Jlms\Administrator\Application\CatalogService::class)));
        $container->set(\Jlms\Component\Jlms\Administrator\Infrastructure\ProtectedMaterialStorage::class, static fn () => new \Jlms\Component\Jlms\Administrator\Infrastructure\ProtectedMaterialStorage(static fn () => ComponentHelper::getParams('com_jlms')));
        $container->set(\Jlms\Component\Jlms\Administrator\Application\MaterialService::class, static fn (Container $c) => new \Jlms\Component\Jlms\Administrator\Application\MaterialService($c->get(DatabaseInterface::class), $c->get(\Jlms\Component\Jlms\Administrator\Application\LearningService::class), $c->get(\Jlms\Component\Jlms\Administrator\Infrastructure\ProtectedMaterialStorage::class)));
        $container->set(\Jlms\Component\Jlms\Administrator\Application\VideoProgressService::class, static fn (Container $c) => new \Jlms\Component\Jlms\Administrator\Application\VideoProgressService($c->get(DatabaseInterface::class), $c->get(\Jlms\Component\Jlms\Administrator\Application\LearningService::class), $c->get(CurriculumService::class), static fn (): int => (int) floor(microtime(true) * 1000), $c->get(\Jlms\Component\Jlms\Administrator\Application\LocalVideoService::class)));
        $container->set(\Jlms\Component\Jlms\Administrator\Infrastructure\ProtectedVideoStorage::class, static fn () => new \Jlms\Component\Jlms\Administrator\Infrastructure\ProtectedVideoStorage(static fn () => ComponentHelper::getParams('com_jlms')));
        $container->set(\Jlms\Component\Jlms\Administrator\Application\LocalVideoService::class, static fn (Container $c) => new \Jlms\Component\Jlms\Administrator\Application\LocalVideoService($c->get(DatabaseInterface::class), $c->get(\Jlms\Component\Jlms\Administrator\Application\LearningService::class), $c->get(\Jlms\Component\Jlms\Administrator\Infrastructure\ProtectedVideoStorage::class), static function (): bool {
            return PluginHelper::isEnabled('jlmsvideo', 'html5') && (int) (new \Joomla\Registry\Registry(PluginHelper::getPlugin('jlmsvideo', 'html5')->params ?? '{}'))->get('playback_enabled', 0) === 1;
        }));
        $container->set(\Jlms\Component\Jlms\Administrator\Application\QuestionBankService::class, static fn (Container $c) => new \Jlms\Component\Jlms\Administrator\Application\QuestionBankService($c->get(DatabaseInterface::class), $c->get(\Jlms\Component\Jlms\Administrator\Application\LearningService::class), static fn (string $html): string => ComponentHelper::filterText($html), static fn () => Factory::getApplication()->bootComponent('com_jlms')->getMVCFactory()->createTable('Question', 'Administrator')));
        $container->set(\Jlms\Component\Jlms\Administrator\Application\QuizAuthoringService::class, static fn (Container $c) => new \Jlms\Component\Jlms\Administrator\Application\QuizAuthoringService($c->get(DatabaseInterface::class), $c->get(\Jlms\Component\Jlms\Administrator\Application\LearningService::class)));
        $container->set(\Jlms\Component\Jlms\Administrator\Application\QuizSnapshotService::class, static fn (Container $c) => new \Jlms\Component\Jlms\Administrator\Application\QuizSnapshotService($c->get(DatabaseInterface::class)));
        $container->set(\Jlms\Component\Jlms\Administrator\Application\QuizTimeService::class, static fn (Container $c) => new \Jlms\Component\Jlms\Administrator\Application\QuizTimeService($c->get(DatabaseInterface::class), $c->get(\Jlms\Component\Jlms\Administrator\Application\LearningService::class), static function (\Joomla\CMS\User\User $actor, array $message) use ($c): void { (new \Jlms\Component\Jlms\Administrator\Infrastructure\AssessmentAudit($c->get(DatabaseInterface::class)))->record($actor, $message); }));
        $container->set(\Jlms\Component\Jlms\Administrator\Application\QuizDeadlineService::class, static fn (Container $c) => new \Jlms\Component\Jlms\Administrator\Application\QuizDeadlineService($c->get(DatabaseInterface::class), $c->get(\Jlms\Component\Jlms\Administrator\Application\QuizTimeService::class), static function (\Joomla\CMS\User\User $actor, array $message) use ($c): void { (new \Jlms\Component\Jlms\Administrator\Infrastructure\AssessmentAudit($c->get(DatabaseInterface::class)))->record($actor, $message, 'quizextend'); }, static fn (): int => time()));
        $container->set(\Jlms\Component\Jlms\Administrator\Application\QuizReviewService::class, static fn (Container $c) => new \Jlms\Component\Jlms\Administrator\Application\QuizReviewService($c->get(DatabaseInterface::class), $c->get(\Jlms\Component\Jlms\Administrator\Application\QuizTimeService::class), static function (\Joomla\CMS\User\User $actor, array $message) use ($c): void { (new \Jlms\Component\Jlms\Administrator\Infrastructure\AssessmentAudit($c->get(DatabaseInterface::class)))->record($actor, $message, 'quizcancel'); }));
        $container->set(\Jlms\Component\Jlms\Administrator\Application\QuizAttemptService::class, static fn (Container $c) => new \Jlms\Component\Jlms\Administrator\Application\QuizAttemptService($c->get(DatabaseInterface::class), $c->get(\Jlms\Component\Jlms\Administrator\Application\LearningService::class), $c->get(\Jlms\Component\Jlms\Administrator\Application\QuizSnapshotService::class), static fn (): int => time(), $c->get(\Jlms\Component\Jlms\Administrator\Application\QuizTimeService::class)));
        $container->set(\Jlms\Component\Jlms\Administrator\Application\DashboardService::class, static fn (Container $c) => new \Jlms\Component\Jlms\Administrator\Application\DashboardService($c->get(DatabaseInterface::class), $c->get(\Jlms\Component\Jlms\Administrator\Application\LearningService::class), $c->get(\Jlms\Component\Jlms\Administrator\Application\CatalogService::class)));
        $container->set(PublicationService::class, static fn (Container $c): PublicationService => new PublicationService($c->get(DatabaseInterface::class), Factory::getApplication()));
        $container->set(\Jlms\Component\Jlms\Administrator\Application\EnrollmentRosterService::class, static fn (Container $c) => new \Jlms\Component\Jlms\Administrator\Application\EnrollmentRosterService($c->get(DatabaseInterface::class), $c->get(\Jlms\Component\Jlms\Administrator\Application\LearningService::class)));
        $container->set(\Jlms\Component\Jlms\Administrator\Application\EnrollmentStateService::class, static fn (Container $c) => new \Jlms\Component\Jlms\Administrator\Application\EnrollmentStateService($c->get(DatabaseInterface::class), $c->get(\Jlms\Component\Jlms\Administrator\Application\LearningService::class), $c->get(\Jlms\Component\Jlms\Administrator\Application\EnrollmentRosterService::class), static function (\Joomla\CMS\User\User $actor, array $message) use ($c): void { (new \Jlms\Component\Jlms\Administrator\Infrastructure\AssessmentAudit($c->get(DatabaseInterface::class)))->record($actor, $message, 'enrollmentstate'); }));
        $container->set(\Jlms\Component\Jlms\Administrator\Application\EnrollmentMigrationService::class, static fn (Container $c) => new \Jlms\Component\Jlms\Administrator\Application\EnrollmentMigrationService($c->get(DatabaseInterface::class), $c->get(\Jlms\Component\Jlms\Administrator\Application\LearningService::class), $c->get(\Jlms\Component\Jlms\Administrator\Application\EnrollmentRosterService::class), static function (\Joomla\CMS\User\User $actor, array $message) use ($c): void { (new \Jlms\Component\Jlms\Administrator\Infrastructure\AssessmentAudit($c->get(DatabaseInterface::class)))->record($actor, $message, 'enrollmentmigration'); }));
        $container->set(\Jlms\Component\Jlms\Administrator\Application\CourseOperationsService::class, static fn (Container $c) => new \Jlms\Component\Jlms\Administrator\Application\CourseOperationsService($c->get(DatabaseInterface::class), $c->get(\Jlms\Component\Jlms\Administrator\Application\EnrollmentRosterService::class), static function (\Joomla\CMS\User\User $actor, array $message) use ($c): void { (new \Jlms\Component\Jlms\Administrator\Infrastructure\AssessmentAudit($c->get(DatabaseInterface::class)))->record($actor, $message, 'courseoperations'); }));
        $container->set(\Jlms\Component\Jlms\Administrator\Application\ManualEnrollmentService::class, static fn (Container $c) => new \Jlms\Component\Jlms\Administrator\Application\ManualEnrollmentService($c->get(DatabaseInterface::class), $c->get(\Jlms\Component\Jlms\Administrator\Application\LearningService::class), $c->get(\Jlms\Component\Jlms\Administrator\Application\CatalogService::class), $c->get(\Jlms\Component\Jlms\Administrator\Application\EnrollmentRosterService::class), static function (\Joomla\CMS\User\User $actor, array $message) use ($c): void { (new \Jlms\Component\Jlms\Administrator\Infrastructure\AssessmentAudit($c->get(DatabaseInterface::class)))->record($actor, $message, 'manualenrollment'); }));
        $container->set(ComponentInterface::class, static function (Container $container): JlmsComponent {
            $component = new JlmsComponent($container->get(ComponentDispatcherFactoryInterface::class));
            $component->setAssociationExtension(new \Jlms\Component\Jlms\Administrator\Helper\AssociationsHelper());
            $component->setMVCFactory($container->get(MVCFactoryInterface::class));
            $component->setCurriculumService($container->get(CurriculumService::class));
            $component->setPublicationService($container->get(PublicationService::class));
            $component->setCatalogService($container->get(\Jlms\Component\Jlms\Administrator\Application\CatalogService::class));
            $component->setDashboardService($container->get(\Jlms\Component\Jlms\Administrator\Application\DashboardService::class));
            $component->setManualEnrollmentService($container->get(\Jlms\Component\Jlms\Administrator\Application\ManualEnrollmentService::class));
            $component->setEnrollmentStateService($container->get(\Jlms\Component\Jlms\Administrator\Application\EnrollmentStateService::class));
            $component->setEnrollmentMigrationService($container->get(\Jlms\Component\Jlms\Administrator\Application\EnrollmentMigrationService::class));
            $component->setCourseOperationsService($container->get(\Jlms\Component\Jlms\Administrator\Application\CourseOperationsService::class));
            $component->setEnrollmentRosterService($container->get(\Jlms\Component\Jlms\Administrator\Application\EnrollmentRosterService::class));
            $component->setLearningService($container->get(\Jlms\Component\Jlms\Administrator\Application\LearningService::class));
            $component->setMaterialService($container->get(\Jlms\Component\Jlms\Administrator\Application\MaterialService::class));
            $component->setVideoProgressService($container->get(\Jlms\Component\Jlms\Administrator\Application\VideoProgressService::class));
            $component->setLocalVideoService($container->get(\Jlms\Component\Jlms\Administrator\Application\LocalVideoService::class));
            $component->setQuestionBankService($container->get(\Jlms\Component\Jlms\Administrator\Application\QuestionBankService::class));
            $component->setQuizAuthoringService($container->get(\Jlms\Component\Jlms\Administrator\Application\QuizAuthoringService::class));
            $component->setQuizSnapshotService($container->get(\Jlms\Component\Jlms\Administrator\Application\QuizSnapshotService::class));
            $component->setQuizDeadlineService($container->get(\Jlms\Component\Jlms\Administrator\Application\QuizDeadlineService::class));
            $component->setQuizReviewService($container->get(\Jlms\Component\Jlms\Administrator\Application\QuizReviewService::class));
            $component->setQuizTimeService($container->get(\Jlms\Component\Jlms\Administrator\Application\QuizTimeService::class));
            $component->setQuizAttemptService($container->get(\Jlms\Component\Jlms\Administrator\Application\QuizAttemptService::class));
            $component->setRouterFactory($container->get(\Joomla\CMS\Component\Router\RouterFactoryInterface::class));
            $component->setCategoryFactory($container->get(CategoryFactoryInterface::class));
            return $component;
        });
    }
};
