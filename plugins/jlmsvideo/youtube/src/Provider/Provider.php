<?php

declare(strict_types=1);

namespace Jlms\Plugin\Jlmsvideo\Youtube\Provider;

use Jlms\Contracts\Video\EmbedProvider;

final class Provider extends EmbedProvider
{
    protected function key(): string
    {
        return 'youtube';
    }
}
