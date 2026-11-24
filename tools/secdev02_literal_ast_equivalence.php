<?php

declare(strict_types=1);

/**
 * Normalize PHP ASTs for safe splitting of a plain literal into a concatenation
 * of plain literal fragments. No other PHP AST changes are accepted.
 */
require $argv[1] . '/vendor/autoload.php';

$parser = (new \PhpParser\ParserFactory())->createForNewestSupportedVersion();

$fold = static function (mixed $value) use (&$fold): mixed {
    if ($value instanceof \PhpParser\Node\Expr\BinaryOp\Concat) {
        $left = $fold($value->left);
        $right = $fold($value->right);

        if ($left instanceof \PhpParser\Node\Scalar\String_ && $right instanceof \PhpParser\Node\Scalar\String_) {
            return new \PhpParser\Node\Scalar\String_($left->value . $right->value);
        }

        $value->left = $left;
        $value->right = $right;
    } elseif ($value instanceof \PhpParser\Node) {
        foreach ($value->getSubNodeNames() as $name) {
            $value->$name = $fold($value->$name);
        }
    } elseif (is_array($value)) {
        return array_map($fold, $value);
    }

    return $value;
};

$normalize = static function (mixed $value) use (&$normalize): mixed {
    if ($value instanceof \PhpParser\Node) {
        $attributes = [];
        foreach ($value->getSubNodeNames() as $name) {
            $attributes[$name] = $normalize($value->$name);
        }

        return [$value->getType(), $attributes];
    }

    if (is_array($value)) {
        return array_map($normalize, $value);
    }

    return $value;
};

try {
    $before = $normalize($fold($parser->parse(file_get_contents($argv[2]))));
    $after = $normalize($fold($parser->parse(file_get_contents($argv[3]))));
    if ($before !== $after) {
        fwrite(STDERR, 'PHP AST semantic difference after literal split' . PHP_EOL);
        exit(1);
    }

    echo 'PHP normalized AST identical' . PHP_EOL;
} catch (\Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(2);
}
