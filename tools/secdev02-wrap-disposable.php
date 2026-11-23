<?php
declare(strict_types=1);

/**
 * Disposable PHP lexical line-wrapper.
 * Attempts only newline insertions after comma, opening bracket or opening parenthesis.
 * A candidate is valid only with identical significant token streams.
 */
function signature(string $code): array
{
    $out = [];
    foreach (token_get_all($code) as $token) {
        if (is_array($token) && $token[0] === T_WHITESPACE) {
            continue;
        }
        $out[] = is_array($token) ? [$token[0], $token[1]] : $token;
    }
    return $out;
}
function propose(string $code, int $mode): string
{
    $base = signature($code);
    for ($pass = 0; $pass < 6; $pass++) {
        $candidates = [];
        $offset = 0;
        foreach (token_get_all($code) as $token) {
            $raw = is_array($token) ? $token[1] : $token;
            $id = is_array($token) ? $token[0] : 0;
            if ($id === 0 && in_array($raw, [',', '(', '['], true)) {
                $candidates[] = [$offset, $raw];
            }
            $offset += strlen($raw);
        }
        $changes = 0;
        foreach (array_reverse($candidates) as [$position, $punct]) {
            $startOfLine = strrpos(substr($code, 0, $position), "\n");
            $start = $startOfLine === false ? 0 : $startOfLine + 1;
            $next = strpos($code, "\n", $position);
            $end = $next === false ? strlen($code) : $next;
            $col = $position - $start;
            if ($end - $start <= 120 || $col < 43 || $col > 112) {
                continue;
            }
            if ($punct === ',' && $mode >= 3) {
                continue;
            }
            if ($punct !== ',' && $mode < 3) {
                continue;
            }
            $after = $position + 1;
            $right = substr($code, $after);
            $spaces = strspn($right, " \t");
            if (isset($right[$spaces]) && $right[$spaces] === "\n") {
                continue;
            }
            $line = substr($code, $start, $end - $start);
            preg_match('/^[ \t]*/', $line, $found);
            $indent = $found[0] ?? '';
            if (strlen($indent) > 24) {
                continue;
            }
            $extra = match ($mode) { 0 => 0, 1 => 4, 2 => 8, 3 => 4, 4 => 8, default => 0 };
            $updated = substr_replace($code, "\n" . $indent . str_repeat(' ', $extra), $after, $spaces);
            if (signature($updated) === $base) {
                $code = $updated;
                $changes++;
            }
        }
        if (!$changes) {
            break;
        }
    }
    return $code;
}
if ($argc !== 3 || !is_file($argv[1])) {
    exit(2);
}
$source = file_get_contents($argv[1]);
if ($source === false) {
    exit(2);
}
$result = propose($source, (int) $argv[2]);
if (signature($source) !== signature($result)) {
    exit(3);
}
if ($result !== $source && file_put_contents($argv[1], $result) === false) {
    exit(4);
}
