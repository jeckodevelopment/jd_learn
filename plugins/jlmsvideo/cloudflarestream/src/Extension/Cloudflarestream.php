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
        $config = $this->params->toArray();
        $secrets = new \Jlms\Contracts\Security\EnvironmentSecretProvider();
        $resolve = static fn (string $reference): string => $secrets->resolve($reference);
        $timeout = max(1, min(10, (int) ($config['remote_timeout'] ?? 5)));
        $transport = static function (string $method, string $url, array $headers, ?string $body) use ($timeout): array {
            $http = (new \Joomla\Http\HttpFactory())->getHttp(['follow_location' => false, 'timeout' => $timeout]);
            $response = $method === 'GET' ? $http->get($url, $headers, $timeout) : $http->post($url, $body, $headers, $timeout);
            return ['status' => (int) $response->getStatusCode(), 'body' => (string) $response->getBody()];
        };
        $provider = new Provider($config, $resolve, $transport);
        $event->getRegistry()->register($provider->getDescriptor(), static fn (): Provider => $provider);
    }
}
