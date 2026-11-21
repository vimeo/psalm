<?php

declare(strict_types=1);

/** A namespaced class, function or constant name; group 1 is its last segment. */
const QUALIFIED_NAME_PATTERN = '/\\\\?(?:[A-Za-z_][A-Za-z0-9_]*\\\\)+([A-Za-z_][A-Za-z0-9_]*)/';

/**
 * Diff of the issues an in-place change replaced: chunks [old, new], where unchanged text has old === new.
 * Several issues on a side diff as sets; for one issue whose type changed, only the quoted types are diffed.
 *
 * @param list<array{type: string, message: string}> $old
 * @param list<array{type: string, message: string}> $new
 * @return list<array{string, string}>
 */
function changeDiff(array $old, array $new): array
{
    [$a, $b] = [array_column($old, 'message'), array_column($new, 'message')];

    return match (true) {
        count($a) > 1 || count($b) > 1 => setDiff($a, $b),
        $old[0]['type'] !== $new[0]['type'] => quotedDiff($a[0], $b[0]),
        default => tokenDiff($a[0], $b[0]),
    };
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
 * Words, whitespace runs and single other characters, so that `HasOne` and `HasOneOrMany` differ as whole names.
 *
 * @return list<string>
 */
function messageTokens(string $text): array
{
    preg_match_all('/[A-Za-z0-9_]+|\s+|./su', $text, $m);

    return $m[0];
}

/**
 * Word diff as chunks [old, new]; unchanged text has old === new. Whitespace between two changes joins them, so
 * that `is more general than` → `does not match` reads as one change.
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

    $chunks = [];
    $push = static function (string $old, string $new) use (&$chunks): void {
        $last = array_key_last($chunks);
        if ($last !== null && ($chunks[$last][0] === $chunks[$last][1]) === ($old === $new)) {
            $chunks[$last] = [$chunks[$last][0] . $old, $chunks[$last][1] . $new];
        } else {
            $chunks[] = [$old, $new];
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

    $tail = implode('', array_slice($a, count($a) - $suffix));
    $push($tail, $tail);

    $joined = [];
    foreach ($chunks as $k => $c) {
        $last = array_key_last($joined);
        $glue = $c[0] === $c[1] && trim($c[0]) === '' && isset($chunks[$k + 1]);
        if ($last !== null && $joined[$last][0] !== $joined[$last][1] && ($c[0] !== $c[1] || $glue)) {
            $joined[$last] = [$joined[$last][0] . $c[0], $joined[$last][1] . $c[1]];
        } elseif ($c !== ['', '']) {
            $joined[] = $c;
        }
    }

    return $joined;
}

/**
 * For an issue whose type changed, the prose differs anyway: the new message with only its quoted types diffed.
 * Falls back to a word diff when the two messages quote a different number of types.
 *
 * @return list<array{string, string}>
 */
function quotedDiff(string $old, string $new): array
{
    $split = static fn(string $s): array => preg_split("/('[^']*')/", $s, -1, PREG_SPLIT_DELIM_CAPTURE);
    [$a, $b] = [$split($old), $split($new)];
    if (count($a) !== count($b) || count($b) === 1) {
        return tokenDiff($old, $new);
    }

    $merged = [];
    foreach ($b as $k => $part) {
        foreach ($k % 2 === 1 ? tokenDiff($a[$k], $part) : [[$part, $part]] as $c) {
            $last = array_key_last($merged);
            if ($last !== null && $c[0] === $c[1] && $merged[$last][0] === $merged[$last][1]) {
                $merged[$last] = [$merged[$last][0] . $c[0], $merged[$last][1] . $c[1]];
            } elseif ($c !== ['', '']) {
                $merged[] = $c;
            }
        }
    }

    return $merged;
}

/**
 * Several issues at one location: the text all messages share around one change, the differing middles as sets,
 * e.g. `Builder<TRelatedModel:` ~~`{HasOne, MorphOne}`~~ **`HasOneOrMany`** ` as Model>`.
 *
 * @param list<string> $old
 * @param list<string> $new
 * @return list<array{string, string}>
 */
function setDiff(array $old, array $new): array
{
    $tokens = array_map(messageTokens(...), [...$old, ...$new]);
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
    $middles = array_map(
        static fn(array $t): string => implode('', array_slice($t, $prefix, count($t) - $prefix - $suffix)),
        $tokens,
    );
    $set = static function (array $items): string {
        if (count($items) === 1) {
            return $items[0];
        }

        $counts = array_count_values($items);

        return '{' . implode(', ', array_map(
            static fn(string $item, int $n): string => ($item === '' ? '∅' : $item) . ($n > 1 ? " ×{$n}" : ''),
            array_keys($counts),
            $counts,
        )) . '}';
    };
    $head = implode('', array_slice($tokens[0], 0, $prefix));
    $tail = implode('', array_slice($tokens[0], count($tokens[0]) - $suffix));
    $change = [$set(array_slice($middles, 0, count($old))), $set(array_slice($middles, count($old)))];

    return array_values(array_filter(
        [[$head, $head], $change, [$tail, $tail]],
        static fn(array $c): bool => $c !== ['', ''],
    ));
}
