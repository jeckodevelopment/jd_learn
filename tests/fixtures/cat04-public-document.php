<?php

declare(strict_types=1);

namespace Jlms\Tests\Fixtures\Cat04PublicMetadata;

final class PublicDocument
{
    public string $title = '';
    public string $description = '';
    public array $head = [];
    public array $meta = [];

    public function setTitle(string $value): void
    {
        $this->title = $value;
    }

    public function setDescription(string $value): void
    {
        $this->description = $value;
    }

    public function addHeadLink(string $url, string $type): void
    {
        $this->head[$type] = $url;
    }

    public function setMetaData(string $key, string $value, string $type = 'name'): void
    {
        $this->meta[$type . ':' . $key] = $value;
    }
}
