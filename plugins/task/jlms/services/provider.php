<?php

declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Jlms\Plugin\Task\Jlms\Extension\Jlms;
return new class implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->set(PluginInterface::class,static function():Jlms {
            $plugin=new Jlms((array)PluginHelper::getPlugin('task','jlms'));
            $plugin->setApplication(Factory::getApplication());return $plugin;
        });
    }
};
