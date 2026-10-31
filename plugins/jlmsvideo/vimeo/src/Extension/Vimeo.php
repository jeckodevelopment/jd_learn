<?php

declare(strict_types=1);

namespace Jlms\Plugin\Jlmsvideo\Vimeo\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Jlms\Contracts\Event\RegisterVideoProvidersEvent;
use Jlms\Contracts\Video\ProviderDescriptor;
use Jlms\Plugin\Jlmsvideo\Vimeo\Provider\Provider;

final class Vimeo extends CMSPlugin implements SubscriberInterface
{
    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return [RegisterVideoProvidersEvent::NAME => 'registerProviders'];
    }

    public function registerProviders(RegisterVideoProvidersEvent $event): void
    {
        $enabled = (bool) $this->params->get('playback_enabled', false);
        $descriptor = new ProviderDescriptor('vimeo', 'PLG_JLMSVIDEO_VIMEO', 1, $enabled);
        $event->getRegistry()->register($descriptor, static fn (): Provider => new Provider($enabled));
    }
}
