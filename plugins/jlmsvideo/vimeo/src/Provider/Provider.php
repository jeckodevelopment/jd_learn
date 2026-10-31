<?php

declare(strict_types=1);

namespace Jlms\Plugin\Jlmsvideo\Vimeo\Provider;

use Jlms\Contracts\Video\AbstractFoundationProvider;

final class Provider extends AbstractFoundationProvider
{
    protected function key(): string
    {
        return 'vimeo';
    }
}
