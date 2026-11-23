<?php
declare(strict_types=1);

/**
 * One-shot long-line candidate generator. Preserve PHP lexical tokens exactly.
 * This helper and its workflow are removed before the PR is proposed.
 */
function significantTokens(string $code): array
{
    $tokens = [];
    foreach (token_get_all($code) as $token) {
        if (is_array($token) && $token[0] === T_WHITESPACE) {
            continue;
        }
        $tokens[] = is_array($token) ? [$token[0], $token[1]] : $token;
    }
    return $tokens;
}
function propose(string $code): string
{
    for ($round = 0; $round < 8; $round++) {
        $positions = [];
        $offset = 0;
        foreach (token_get_all($code) as $token) {
            $raw = is_array($token) ? $token[1] : $token;
            $id = is_array($token) ? $token[0] : 0;
            if (($id === T_OBJECT_OPERATOR || $id === T_NULLSAFE_OBJECT_OPERATOR
                    || $id === T_BOOLEAN_AND || $id === T_BOOLEAN_OR
                    || $id === T_LOGICAL_AND || $id === T_LOGICAL_OR
                    || $id === T_COALESCE || $id === T_DOUBLE_ARROW
                    || $raw === '.') && !str_contains($raw, "\n")) {
                $positions[] = $offset;
            }
            $offset += strlen($raw);
        }
        $changed = 0;
        foreach (array_reverse($positions) as $pos) {
            $before = substr($code, 0, $pos);
            $lastNewline = strrpos($before, "\n");
            $start = $lastNewline === false ? 0 : $lastNewline + 1;
            $nextNewline = strpos($code, "\n", $pos);
            $end = $nextNewline === false ? strlen($code) : $nextNewline;
            $length = $end - $start;
            $column = $pos - $start;
            if ($length <= 120 || $column < 62 || $column > 112) {
                continue;
            }
            $line = substr($code, $start, $end - $start);
            if (!preg_match('/^([ \t]*)/', $line, $match)) {
                continue;
            }
            $indent = $match[1];
            if (strlen($indent) > 32) {
                continue;
            }
            $suffixWhitespace = strlen($before) - strlen(rtrim($before, " \t"));
            if ($suffixWhitespace > 16) {
                continue;
            }
            // Avoid edits to comments or strings: tokens only designate executable operators.
            $at = $pos - $suffixWhitespace;
            $code = substr_replace($code, "\n" . $indent . '    ', $at, $suffixWhitespace);
            $changed++;
        }
        if ($changed === 0) {
            break;
        }
    }
    return $code;
}
if ($argc !== 2 || !is_file($argv[1])) {
    fwrite(STDERR, "Need path\n");
    exit(2);
}
$source = file_get_contents($argv[1]);
if ($source === false) {
    exit(2);
}
$candidate = propose($source);
if (significantTokens($source) !== significantTokens($candidate)) {
    fwrite(STDERR, "Unsupported token transformation: " . $argv[1] . "\n");
    exit(3);
}
if ($source !== $candidate && file_put_contents($argv[1], $candidate) === false) {
    exit(4);
}
