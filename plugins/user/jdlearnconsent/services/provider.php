<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Jlms\Plugin\User\Jdlearnconsent\Extension\Jdlearnconsent;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(PluginInterface::class, static function (): Jdlearnconsent {
            $plugin = new Jdlearnconsent((array) PluginHelper::getPlugin('user', 'jdlearnconsent'));
            $plugin->setApplication(Factory::getApplication());

            return $plugin;
        });
    }
};
