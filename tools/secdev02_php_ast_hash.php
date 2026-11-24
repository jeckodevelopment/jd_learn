<?php

declare(strict_types=1);

require $argv[1] . '/vendor/autoload.php';

$factory = new \PhpParser\ParserFactory();
$parser = $factory->createForNewestSupportedVersion();

$normalize = static function (mixed $value) use (&$normalize): mixed {
    if ($value instanceof \PhpParser\Node) {
        $attributes = [];
        foreach ($value->getSubNodeNames() as $property) {
            $attributes[$property] = $normalize($value->$property);
        }
        return [$value->getType(), $attributes];
    }

    if (is_array($value)) {
        return array_map($normalize, $value);
    }

    return $value;
};

$source = file_get_contents($argv[2]);
$ast = $normalize($parser->parse($source));
echo hash('sha256', json_encode($ast, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE));
