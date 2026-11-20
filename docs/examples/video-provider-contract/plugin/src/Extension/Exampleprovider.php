<?php

declare(strict_types=1);

namespace Jlms\Plugin\Jlmsvideo\Exampleprovider\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Jlms\Contracts\Event\RegisterVideoProvidersEvent;
use Jlms\Contracts\Video\ProviderDescriptor;
use Jlms\Plugin\Jlmsvideo\Exampleprovider\Provider\Provider;

/** Documentation-only Joomla plugin skeleton; its adapter is deliberately fail-closed. */
final class Exampleprovider extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [RegisterVideoProvidersEvent::NAME => 'registerProviders'];
    }

    public function registerProviders(RegisterVideoProvidersEvent $event): void
    {
        $event->getRegistry()->register(
            new ProviderDescriptor('exampleprovider', 'PLG_JLMSVIDEO_EXAMPLEPROVIDER', 1, false),
            static fn (): Provider => new Provider(),
        );
    }
}
