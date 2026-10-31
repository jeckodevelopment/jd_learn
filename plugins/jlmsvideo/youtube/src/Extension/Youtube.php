<?php

declare(strict_types=1);

namespace Jlms\Plugin\Jlmsvideo\Youtube\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Jlms\Contracts\Event\RegisterVideoProvidersEvent;
use Jlms\Contracts\Video\ProviderDescriptor;
use Jlms\Plugin\Jlmsvideo\Youtube\Provider\Provider;

final class Youtube extends CMSPlugin implements SubscriberInterface
{
    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return [RegisterVideoProvidersEvent::NAME => 'registerProviders'];
    }

    public function registerProviders(RegisterVideoProvidersEvent $event): void
    {
        $descriptor = new ProviderDescriptor('youtube', 'PLG_JLMSVIDEO_YOUTUBE', 1, false);
        $event->getRegistry()->register($descriptor, static fn (): Provider => new Provider());
    }
}
