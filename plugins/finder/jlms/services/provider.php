<?php

declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Jlms\Plugin\Finder\Jlms\Extension\Jlms;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(PluginInterface::class, static function (Container $container): Jlms {
            $plugin = new Jlms((array) PluginHelper::getPlugin('finder', 'jlms'));
            $plugin->setApplication(Factory::getApplication());
            $plugin->setDatabase($container->get(DatabaseInterface::class));
            return $plugin;
        });
    }
};
