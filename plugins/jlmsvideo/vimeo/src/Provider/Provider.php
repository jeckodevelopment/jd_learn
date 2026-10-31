<?php

declare(strict_types=1);

namespace Jlms\Plugin\Jlmsvideo\Vimeo\Provider;

use Jlms\Contracts\Video\EmbedProvider;

final class Provider extends EmbedProvider
{
    protected function key(): string
    {
        return 'vimeo';
    }
}
