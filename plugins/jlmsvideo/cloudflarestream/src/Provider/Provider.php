<?php

declare(strict_types=1);

namespace Jlms\Plugin\Jlmsvideo\Cloudflarestream\Provider;

use Jlms\Contracts\Video\AbstractFoundationProvider;

final class Provider extends AbstractFoundationProvider
{
    protected function key(): string
    {
        return 'cloudflarestream';
    }
}
