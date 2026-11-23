<?php
declare(strict_types=1);

/**
 * Disposable conservative PHP constant-string literal line wrapper.
 * Rewrites only single-line T_CONSTANT_ENCAPSED_STRING tokens, splitting
 * at literal ASCII spaces and checking exact byte reconstruction.
 */
function literalWrap(string $source, int $mode): string
{
    $offset = 0;
    $edits = [];
    $baseChunks = [66, 76, 86, 94];
    $maxChunk = $baseChunks[$mode % count($baseChunks)];
    $extra = [4, 8, 0, 12][$mode % 4];
    foreach (token_get_all($source) as $token) {
        $raw = is_array($token) ? $token[1] : $token;
        $id = is_array($token) ? $token[0] : 0;
        if ($id !== T_CONSTANT_ENCAPSED_STRING || strlen($raw) < 78 || strpbrk($raw, "\r\n") !== false) {
            $offset += strlen($raw);
            continue;
        }
        $quote = $raw[0];
        if (($quote !== "'" && $quote !== '"') || substr($raw, -1) !== $quote) {
            $offset += strlen($raw);
            continue;
        }
        $beforeLine = strrpos(substr($source, 0, $offset), "\n");
        $start = $beforeLine === false ? 0 : $beforeLine + 1;
        $nextLine = strpos($source, "\n", $offset);
        $end = $nextLine === false ? strlen($source) : $nextLine;
        if ($end - $start <= 120) {
            $offset += strlen($raw);
            continue;
        }
        $column = $offset - $start;
        if ($column > 100) {
            $offset += strlen($raw);
            continue;
        }
        $line = substr($source, $start, $end - $start);
        preg_match('/^[ \t]*/', $line, $match);
        $indent = $match[0] ?? '';
        if (strlen($indent) > 24) {
            $offset += strlen($raw);
            continue;
        }
        $inner = substr($raw, 1, -1);
        if (strlen($inner) < 75 || strpos($inner, ' ') === false) {
            $offset += strlen($raw);
            continue;
        }
        $parts = [];
        $remaining = $inner;
        $firstLimit = min($maxChunk, max(18, 112 - $column));
        while (strlen($remaining) > $maxChunk + 12) {
            $limit = count($parts) === 0 ? $firstLimit : $maxChunk;
            $split = strrpos(substr($remaining, 0, $limit + 1), ' ');
            if ($split === false || $split < 14 || ($split > 0 && $remaining[$split - 1] === '\\')) {
                break;
            }
            $parts[] = substr($remaining, 0, $split + 1);
            $remaining = substr($remaining, $split + 1);
        }
        if (!$parts) {
            $offset += strlen($raw);
            continue;
        }
        $parts[] = $remaining;
        if (implode('', $parts) !== $inner) {
            throw new RuntimeException('Literal bytes mutated');
        }
        $continuation = "\n" . $indent . str_repeat(' ', $extra) . '. ';
        $wrapped = '(' . $quote . implode($quote . $continuation . $quote, $parts) . $quote . ')';
        $edits[] = [$offset, strlen($raw), $wrapped];
        $offset += strlen($raw);
    }
    foreach (array_reverse($edits) as [$pos, $len, $next]) {
        $source = substr_replace($source, $next, $pos, $len);
    }
    return $source;
}
if ($argc !== 3 || !is_file($argv[1])) { exit(2); }
$original = file_get_contents($argv[1]);
if ($original === false) { exit(2); }
$changed = literalWrap($original, (int)$argv[2]);
if ($changed !== $original) {
    file_put_contents($argv[1], $changed);
}
