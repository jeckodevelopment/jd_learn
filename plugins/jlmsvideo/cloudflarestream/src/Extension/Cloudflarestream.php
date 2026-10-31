<?php

declare(strict_types=1);

namespace Jlms\Plugin\Jlmsvideo\Cloudflarestream\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Jlms\Contracts\Event\RegisterVideoProvidersEvent;
use Jlms\Contracts\Video\ProviderDescriptor;
use Jlms\Plugin\Jlmsvideo\Cloudflarestream\Provider\Provider;

final class Cloudflarestream extends CMSPlugin implements SubscriberInterface
{
    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return [RegisterVideoProvidersEvent::NAME => 'registerProviders'];
    }

    public function registerProviders(RegisterVideoProvidersEvent $event): void
    {
        $descriptor = new ProviderDescriptor('cloudflarestream', 'PLG_JLMSVIDEO_CLOUDFLARESTREAM', 1, false);
        $event->getRegistry()->register($descriptor, static fn (): Provider => new Provider());
    }
}
