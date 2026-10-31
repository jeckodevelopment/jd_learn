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
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Jlms\Component\Jlms\Administrator\Extension\JlmsComponent;

require_once JPATH_LIBRARIES . '/jlms/bootstrap.php';

return new class () implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->registerServiceProvider(new MVCFactory('\\Jlms\\Component\\Jlms'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Jlms\\Component\\Jlms'));
        $container->registerServiceProvider(new CategoryFactory('\\Jlms\\Component\\Jlms'));
        $container->set(ComponentInterface::class, static function (Container $container): JlmsComponent {
            $component = new JlmsComponent($container->get(ComponentDispatcherFactoryInterface::class));
            $component->setMVCFactory($container->get(MVCFactoryInterface::class));
            $component->setCategoryFactory($container->get(CategoryFactoryInterface::class));
            return $component;
        });
    }
};
