<?php

declare(strict_types=1);

namespace Jlms\Plugin\Jlmsvideo\Exampleprovider\Provider;

use Jlms\Contracts\Video\AbstractFoundationProvider;

/** Registration-compatible but intentionally without playback, metadata or source adapter. */
final class Provider extends AbstractFoundationProvider
{
    protected function key(): string
    {
        return 'exampleprovider';
    }
}
