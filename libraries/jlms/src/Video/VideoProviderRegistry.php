<?php

declare(strict_types=1);


namespace Jlms\Contracts\Video;

final class VideoProviderRegistry implements VideoProviderRegistryInterface
{
    private array $descriptors = [];
    private array $factories = [];
    private array $instances = [];

    public function register(ProviderDescriptor $descriptor, \Closure $factory): void
    {
        if (!preg_match('/^[a-z][a-z0-9_]{1,63}$/D', $descriptor->key) || $descriptor->contractMajor !== 1) {
            throw new \DomainException('PROVIDER_CONTRACT_MISMATCH');
        }
        if (isset($this->descriptors[$descriptor->key])) {
            throw new \DomainException('DUPLICATE_PROVIDER_KEY');
        }
        $this->descriptors[$descriptor->key] = $descriptor;
        $this->factories[$descriptor->key] = $factory;
    }

    public function get(string $providerKey): VideoProviderInterface
    {
        if (!isset($this->factories[$providerKey])) {
            throw new \DomainException('PROVIDER_UNAVAILABLE');
        }
        if (!isset($this->instances[$providerKey])) {
            $adapter = ($this->factories[$providerKey])();
            if (!$adapter instanceof VideoProviderInterface || $adapter->getDescriptor()->key !== $providerKey) {
                throw new \DomainException('PROVIDER_CONTRACT_MISMATCH');
            }
            $this->instances[$providerKey] = $adapter;
        }
        return $this->instances[$providerKey];
    }

    public function descriptors(): array
    {
        return array_values($this->descriptors);
    }
}
