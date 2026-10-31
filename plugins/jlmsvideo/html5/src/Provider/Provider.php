<?php

declare(strict_types=1);

namespace Jlms\Plugin\Jlmsvideo\Html5\Provider;

use Jlms\Contracts\Video\AbstractFoundationProvider;

final class Provider extends AbstractFoundationProvider
{
    protected function key(): string
    {
        return 'html5';
    }
}
