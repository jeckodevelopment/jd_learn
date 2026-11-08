<?php

declare(strict_types=1);
\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Jlms\Plugin\Privacy\Jdlearn\Extension\Jdlearn;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(PluginInterface::class, $container->lazy(Jdlearn::class, static function (Container $container): Jdlearn {
            $plugin = new Jdlearn((array) PluginHelper::getPlugin('privacy', 'jdlearn'));
            $plugin->setApplication(Factory::getApplication());
            $plugin->setDatabase($container->get(DatabaseInterface::class));

            return $plugin;
        }));
    }
};
