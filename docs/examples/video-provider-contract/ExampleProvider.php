<?php

declare(strict_types=1);

namespace Jlms\Examples\Video;

use Jlms\Contracts\Video\AbstractFoundationProvider;

/**
 * Contract-only sample. NO supported media source or playback implementation.
 * Do not install it on a production Joomla site.
 */
final class ExampleProvider extends AbstractFoundationProvider
{
    protected function key(): string
    {
        return 'exampleprovider';
    }
}
