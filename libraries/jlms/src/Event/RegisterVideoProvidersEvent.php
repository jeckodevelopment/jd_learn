<?php

declare(strict_types=1);


namespace Jlms\Contracts\Event;

use Joomla\Event\Event;
use Jlms\Contracts\Video\VideoProviderRegistryInterface;

final class RegisterVideoProvidersEvent extends Event
{
    public const NAME = 'onJlmsRegisterVideoProviders';

    public function __construct(VideoProviderRegistryInterface $registry)
    {
        parent::__construct(self::NAME, ['registry' => $registry]);
    }

    public function getRegistry(): VideoProviderRegistryInterface
    {
        return $this->getArgument('registry');
    }
}
