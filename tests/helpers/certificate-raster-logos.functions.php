<?php

declare(strict_types=1);

use Jlms\Component\Jlms\Administrator\Infrastructure\CertificatePdf;

function assertPdf(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}
