<?php

declare(strict_types=1);

namespace Jlms\Plugin\Jlmsvideo\Html5\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Jlms\Contracts\Event\RegisterVideoProvidersEvent;
use Jlms\Contracts\Video\ProviderDescriptor;
use Jlms\Plugin\Jlmsvideo\Html5\Provider\Provider;

final class Html5 extends CMSPlugin implements SubscriberInterface
{
    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return [RegisterVideoProvidersEvent::NAME => 'registerProviders'];
    }

    public function registerProviders(RegisterVideoProvidersEvent $event): void
    {
        $settings = \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms');
        $mode = $settings->get('video_delivery_mode', 'disabled');
        $enabled = (int) $this->params->get('playback_enabled', 0) === 1
            && (string) $settings->get('video_storage_root', '') !== ''
            && in_array($mode, ['php_bounded', 'x_accel'], true);
        $descriptor = new ProviderDescriptor('html5', 'PLG_JLMSVIDEO_HTML5', 1, $enabled);
        $event->getRegistry()->register($descriptor, static fn (): Provider => new Provider($enabled));
    }
}
