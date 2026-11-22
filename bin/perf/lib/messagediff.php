<?php

declare(strict_types=1);

/** A namespaced class, function or constant name; group 1 is its last segment. */
const QUALIFIED_NAME_PATTERN = '/\\\\?(?:[A-Za-z_][A-Za-z0-9_]*\\\\)+([A-Za-z_][A-Za-z0-9_]*)/';

/**
 * The messages of an in-place change cut into segments: each segment holds one string per message (old messages
 * first), and is shared when all of them are equal. One issue on each side diffs word by word; several issues on a
 * side share the text around one differing middle.
 *
 * @param list<string> $old
 * @param list<string> $new
 * @return list<list<string>>
 */
function changeSegments(array $old, array $new): array
{
    return count($old) === 1 && count($new) === 1
        ? tokenDiff($old[0], $new[0])
        : commonAffixes([...$old, ...$new]);
}

/** @param list<string> $segment */
function isShared(array $segment): bool
{
    return count(array_unique($segment)) === 1;
}

/**
 * Namespaced names cut to their last segment, unless two names in the messages share it.
 *
 * @param list<string> $messages
 * @return list<string>
 */
function shortClassNames(array $messages): array
{
    $owners = [];
    foreach ($messages as $message) {
        preg_match_all(QUALIFIED_NAME_PATTERN, $message, $m, PREG_SET_ORDER);
        foreach ($m as [$name, $short]) {
            $owners[$short][ltrim($name, '\\')] = true;
        }
    }

    return array_map(static fn(string $message): string => preg_replace_callback(
        QUALIFIED_NAME_PATTERN,
        static fn(array $m): string => count($owners[$m[1]]) === 1 ? $m[1] : $m[0],
        $message,
    ), $messages);
}

/**
 * Words, `{self}`, whitespace runs and single other characters, so that `HasOne` and `HasOneOrMany` differ as
 * whole names.
 *
 * @return list<string>
 */
function messageTokens(string $text): array
{
    preg_match_all('/\{self\}|[A-Za-z0-9_]+|\s+|./su', $text, $m);

    return $m[0];
}

/**
 * Word diff of two messages as [old, new] segments.
 *
 * @return list<array{string, string}>
 */
function tokenDiff(string $old, string $new): array
{
    [$a, $b] = [messageTokens($old), messageTokens($new)];
    [$na, $nb] = [count($a), count($b)];
    $prefix = 0;
    while ($prefix < $na && $prefix < $nb && $a[$prefix] === $b[$prefix]) {
        $prefix++;
    }

    $suffix = 0;
    while ($suffix < min($na, $nb) - $prefix && $a[$na - 1 - $suffix] === $b[$nb - 1 - $suffix]) {
        $suffix++;
    }

    $x = array_slice($a, $prefix, $na - $prefix - $suffix);
    $y = array_slice($b, $prefix, $nb - $prefix - $suffix);
    [$n, $m] = [count($x), count($y)];
    $lcs = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
    for ($i = $n - 1; $i >= 0; $i--) {
        for ($j = $m - 1; $j >= 0; $j--) {
            $lcs[$i][$j] = $x[$i] === $y[$j] ? $lcs[$i + 1][$j + 1] + 1 : max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
        }
    }

    $segments = [];
    $push = static function (string $old, string $new) use (&$segments): void {
        $last = array_key_last($segments);
        if ($last !== null && ($segments[$last][0] === $segments[$last][1]) === ($old === $new)) {
            $segments[$last] = [$segments[$last][0] . $old, $segments[$last][1] . $new];
        } elseif ($old !== '' || $new !== '') {
            $segments[] = [$old, $new];
        }
    };
    $head = implode('', array_slice($a, 0, $prefix));
    $push($head, $head);
    [$i, $j] = [0, 0];
    while ($i < $n || $j < $m) {
        if ($i < $n && $j < $m && $x[$i] === $y[$j]) {
            $push($x[$i++], $y[$j++]);
        } elseif ($j >= $m || ($i < $n && $lcs[$i + 1][$j] >= $lcs[$i][$j + 1])) {
            $push($x[$i++], '');
        } else {
            $push('', $y[$j++]);
        }
    }

    $tail = implode('', array_slice($a, $na - $suffix));
    $push($tail, $tail);

    return $segments;
}

/**
 * The text all messages start and end with as shared segments, around one segment of differing middles.
 *
 * @param list<string> $messages
 * @return list<list<string>>
 */
function commonAffixes(array $messages): array
{
    $tokens = array_map(messageTokens(...), $messages);
    $shortest = min(array_map(count(...), $tokens));
    $shared = static function (callable $at) use ($tokens, $shortest): int {
        for ($k = 0; $k < $shortest; $k++) {
            foreach ($tokens as $t) {
                if ($at($t, $k) !== $at($tokens[0], $k)) {
                    return $k;
                }
            }
        }

        return $shortest;
    };
    $prefix = $shared(static fn(array $t, int $k): string => $t[$k]);
    $suffix = min($shortest - $prefix, $shared(static fn(array $t, int $k): string => $t[count($t) - 1 - $k]));
    $count = count($messages);
    $head = implode('', array_slice($tokens[0], 0, $prefix));
    $tail = implode('', array_slice($tokens[0], count($tokens[0]) - $suffix));
    $middles = array_map(
        static fn(array $t): string => implode('', array_slice($t, $prefix, count($t) - $prefix - $suffix)),
        $tokens,
    );

    return array_values(array_filter(
        [array_fill(0, $count, $head), $middles, array_fill(0, $count, $tail)],
        static fn(array $segment): bool => implode('', $segment) !== '',
    ));
}
