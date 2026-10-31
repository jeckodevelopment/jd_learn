<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Jlms\Plugin\Jlmsvideo\Cloudflarestream\Extension\Cloudflarestream;

require_once JPATH_LIBRARIES . '/jlms/bootstrap.php';

return new class () implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->set(PluginInterface::class, static function (Container $container): Cloudflarestream {
            return new Cloudflarestream($container->get(DispatcherInterface::class), (array) PluginHelper::getPlugin('jlmsvideo', 'cloudflarestream'));
        });
    }
};
