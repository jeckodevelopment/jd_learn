<?php

declare(strict_types=1);

namespace Joomla\CMS\Component\Router {
    // Only the constructor-less ID decoders are exercised by this fixture.
    class RouterView
    {
    }
}

namespace {
    require_once dirname(__DIR__) . '/component/site/src/Service/Router.php';

    use Jlms\Component\Jlms\Site\Service\Router;

    $router = (new \ReflectionClass(Router::class))->newInstanceWithoutConstructor();
    $decoders = [
        'getCourseId', 'getCategoryId', 'getPathwayId',
        'getTeacherId', 'getTeachereditId', 'getClassroomId', 'getQuizId',
    ];
    $cases = [
        '42' => 42,
        '00042' => 42,
        '42:lezione-di-prova' => 42,
        '42:opera-à-italiana' => 42,
        '42-old-alias' => 42,
        (string) PHP_INT_MAX => PHP_INT_MAX,
        '0' => 0,
        '000' => 0,
        '-1' => 0,
        '+42' => 0,
        '42:' => 0,
        '42-' => 0,
        '42:alias/altro' => 0,
        '42:alias?foo=bar' => 0,
        '42:alias#fragment' => 0,
        '42:alias' . "\n" => 0,
        '42:alias' . "\0" => 0,
        '12.3' => 0,
        '12e3' => 0,
        (string) PHP_INT_MAX . '9' => 0,
        str_repeat('9', 100) => 0,
        '123abc' => 0,
    ];
    foreach ($decoders as $method) {
        foreach ($cases as $segment => $expected) {
            $actual = $router->{$method}($segment, []);
            if ($actual !== $expected) {
                throw new \RuntimeException($method . ': invalid segment '
                    . json_encode($segment) . ': expected ' . $expected . ', got ' . $actual);
            }
        }
        foreach ([null, [], new \stdClass()] as $segment) {
            if ($router->{$method}($segment, []) !== 0) {
                throw new \RuntimeException($method . ': non-scalar route segment accepted');
            }
        }
    }
    // Build the three simple numeric child routes: names and IDs round-trip.
    foreach (
        [
            'getTeacherSegment', 'getTeachereditSegment',
            'getClassroomSegment', 'getQuizSegment',
        ] as $method
    ) {
        $segment = $router->{$method}(42, []);
        if ($segment !== [42 => '42']) {
            throw new \RuntimeException($method . ': native numeric segment changed');
        }
    }
    echo 'SECDEV-02 CORE-13: ' . count($decoders)
        . ' route decoders, positive/negative fixtures and numeric round-trips: OK' . PHP_EOL;
}
