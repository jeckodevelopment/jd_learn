<?php

declare(strict_types=1);

/**
 * Minimal event registration demonstration. A real Joomla plugin wraps this
 * in a CMSPlugin implementing SubscriberInterface and its service provider.
 * See plugins/jlmsvideo/youtube/src/Extension/Youtube.php.
 */
use Jlms\Contracts\Event\RegisterVideoProvidersEvent;
use Jlms\Contracts\Video\ProviderDescriptor;
use Jlms\Examples\Video\ExampleProvider;

return static function (RegisterVideoProvidersEvent $event): void {
    $event->getRegistry()->register(
        new ProviderDescriptor('exampleprovider', 'PLG_JLMSVIDEO_EXAMPLE', 1, false),
        static fn (): ExampleProvider => new ExampleProvider(),
    );
};
