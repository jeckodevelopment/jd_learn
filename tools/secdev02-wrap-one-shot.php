<?php
declare(strict_types=1);

/** One-shot PHP-token-preserving long-line fixer. Never shipped in a release. */
function structuralTokens(string $source): array
{
    $out = [];
    foreach (token_get_all($source) as $token) {
        if (is_array($token) && $token[0] === T_WHITESPACE) {
            continue;
        }
        $out[] = is_array($token) ? [$token[0], $token[1]] : $token;
    }
    return $out;
}

function wrapLongLines(string $source): string
{
    for ($round = 0; $round < 6; $round++) {
        $offset = 0;
        $positions = [];
        foreach (token_get_all($source) as $token) {
            $text = is_array($token) ? $token[1] : $token;
            if ($text === ',') {
                $positions[] = $offset + 1;
            }
            $offset += strlen($text);
        }
        $changes = 0;
        foreach (array_reverse($positions) as $position) {
            $previousNewline = strrpos(substr($source, 0, $position), "\n");
            $start = $previousNewline === false ? 0 : $previousNewline + 1;
            $nextNewline = strpos($source, "\n", $position);
            $end = $nextNewline === false ? strlen($source) : $nextNewline;
            if ($end - $start <= 120) {
                continue;
            }
            $column = $position - $start;
            if ($column < 55 || $column > 105) {
                continue;
            }
            $suffix = substr($source, $position);
            $trailing = strspn($suffix, " \t");
            if (isset($suffix[$trailing]) && $suffix[$trailing] === "\n") {
                continue;
            }
            $line = substr($source, $start, $end - $start);
            preg_match('/^[ \t]*/', $line, $match);
            $indent = $match[0] ?? '';
            if (strlen($indent) > 32) {
                continue;
            }
            $insert = "\n" . $indent . '    ';
            $source = substr_replace($source, $insert, $position, $trailing);
            $changes++;
        }
        if ($changes === 0) {
            break;
        }
    }
    return $source;
}

if ($argc !== 2 || !is_file($argv[1])) {
    fwrite(STDERR, "File parameter required\n");
    exit(2);
}
$original = file_get_contents($argv[1]);
if ($original === false) {
    exit(2);
}
$updated = wrapLongLines($original);
if (structuralTokens($original) !== structuralTokens($updated)) {
    fwrite(STDERR, "Token mismatch (no write): " . $argv[1] . "\n");
    exit(3);
}
if ($updated !== $original && file_put_contents($argv[1], $updated) === false) {
    exit(4);
}
