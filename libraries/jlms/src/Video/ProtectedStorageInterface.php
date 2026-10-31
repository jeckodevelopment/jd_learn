<?php

declare(strict_types=1);

namespace Jlms\Contracts\Video;

interface ProtectedStorageInterface
{
    /** Open an already authorised immutable object, never accept a browser-supplied path. @return resource */
    public function open(string $storageKey);
    public function size(string $storageKey): int;
    public function deleteUnreferenced(string $storageKey, string $gcLease): void;
}
