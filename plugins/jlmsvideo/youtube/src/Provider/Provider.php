<?php

declare(strict_types=1);

namespace Jlms\Plugin\Jlmsvideo\Youtube\Provider;

use Jlms\Contracts\Video\AbstractFoundationProvider;

final class Provider extends AbstractFoundationProvider
{
    protected function key(): string
    {
        return 'youtube';
    }
}
