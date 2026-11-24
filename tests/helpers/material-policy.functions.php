<?php

declare(strict_types=1);

use Jlms\Component\Jlms\Administrator\Infrastructure\MaterialFilePolicy;

function materialExpect(bool $ok, string $message): void
{
    global $checks;
    if (!$ok) {
        throw new \RuntimeException($message);
    } $checks++;
}

function materialReject(callable $call, string $message): void
{
    try {
        $call();
    } catch (\RuntimeException $error) {
        materialExpect(true, $message);
        return;
    }
    throw new \RuntimeException($message);
}

function materialZip(string $path, array $files): void
{
    $zip = new \ZipArchive();
    $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
    foreach ($files as $name => $content) {
        $zip->addFromString($name, $content);
    }$zip->close();
}
