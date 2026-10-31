<?php

declare(strict_types=1);

namespace Jlms\Contracts\Video;

interface VideoProviderRegistryInterface
{
    /** @param \Closure(): VideoProviderInterface $factory No remote I/O during registration. */
    public function register(ProviderDescriptor $descriptor, \Closure $factory): void;
    public function get(string $providerKey): VideoProviderInterface;
    /** @return list<ProviderDescriptor> */
    public function descriptors(): array;
}
