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
        $container->set(PublicationService::class, static fn (Container $c): PublicationService => new PublicationService($c->get(DatabaseInterface::class), Factory::getApplication()));
        $container->set(ComponentInterface::class, static function (Container $container): JlmsComponent {
            $component = new JlmsComponent($container->get(ComponentDispatcherFactoryInterface::class));
            $component->setMVCFactory($container->get(MVCFactoryInterface::class));
            $component->setCurriculumService($container->get(CurriculumService::class));
            $component->setPublicationService($container->get(PublicationService::class));
            $component->setCatalogService($container->get(\Jlms\Component\Jlms\Administrator\Application\CatalogService::class));
            $component->setLearningService($container->get(\Jlms\Component\Jlms\Administrator\Application\LearningService::class));
            $component->setMaterialService($container->get(\Jlms\Component\Jlms\Administrator\Application\MaterialService::class));
            $component->setVideoProgressService($container->get(\Jlms\Component\Jlms\Administrator\Application\VideoProgressService::class));
            $component->setLocalVideoService($container->get(\Jlms\Component\Jlms\Administrator\Application\LocalVideoService::class));
            $component->setRouterFactory($container->get(\Joomla\CMS\Component\Router\RouterFactoryInterface::class));
            $component->setCategoryFactory($container->get(CategoryFactoryInterface::class));
            return $component;
        });
    }
};
