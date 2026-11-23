<?php

declare(strict_types=1);

namespace Psalm\Internal\Stubs;

use AssertionError;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Type;
use Psalm\Type\Atomic\TNull;
use Throwable;

use function array_intersect_key;
use function array_key_first;
use function array_slice;
use function array_values;
use function count;
use function explode;
use function file_put_contents;
use function is_array;
use function is_int;
use function json_encode;
use function ksort;
use function str_contains;
use function strncmp;
use function strtolower;
use function substr;
use function uksort;
use function var_export;

/**
 * @param array<int|string, string> $entry
 * @return array<int|string, string>
 */
function normalizeCallMapEntry(array $entry): array
{
    foreach ($entry as $key => $type) {
        // -2 says whether the reflected return type is tentative or absent: it isn't a type
        if ($key !== -2) {
            $entry[$key] = Type::parseString($type === '' ? 'mixed' : $type)->getId(true);
        }
    }

    return $entry;
}

/**
 * @param array<string, array<int|string, string>> $callMap
 * @return array<string, array<int|string, string>>
 */
function normalizeCallMap(array $callMap): array
{
    $normalized = [];
    foreach ($callMap as $function => $entry) {
        $normalized[strtolower($function)] = normalizeCallMapEntry($entry);
    }
    ksort($normalized);

    return $normalized;
}

/**
 * @param array{
 *     added: array<string, array<int|string, string>>,
 *     changed: array<string, array{old: array<int|string, string>, new: array<int|string, string>}>,
 *     removed: array<string, array<int|string, string>>
 * } $delta
 * @return array{
 *     added: array<string, array<int|string, string>>,
 *     changed: array<string, array{old: array<int|string, string>, new: array<int|string, string>}>,
 *     removed: array<string, array<int|string, string>>
 * }
 */
function normalizeCallMapDelta(array $delta): array
{
    $changed = [];
    foreach ($delta['changed'] as $function => $change) {
        $changed[strtolower($function)] = [
            'old' => normalizeCallMapEntry($change['old']),
            'new' => normalizeCallMapEntry($change['new']),
        ];
    }
    ksort($changed);

    return [
        'added' => normalizeCallMap($delta['added']),
        'changed' => $changed,
        'removed' => normalizeCallMap($delta['removed']),
    ];
}

/**
 * @psalm-pure
 * @param array<int|string, string> $parameters
 * @return array<string, array{
 *     byRef: bool,
 *     refMode?: 'rw'|'w'|'r',
 *     variadic: bool,
 *     optional: bool,
 *     type: string,
 *     name: string
 * }>
 */
function normalizeParameters(string $func, array $parameters): array
{
    $normalizedEntries = [];

    foreach ($parameters as $key => $type) {
        if (is_int($key)) {
            continue;
        }
        $normalizedKey = $key;
        $variadic = false;
        $byRef = false;

        do {
            if (strncmp($normalizedKey, '...', 3) === 0) {
                $variadic = true;
                $normalizedKey = substr($normalizedKey, 3);
                continue;
            }

            if (strncmp($normalizedKey, '&', 1) === 0) {
                $byRef = true;
                $normalizedKey = substr($normalizedKey, 1);
                continue;
            }
            break;
        } while (true);

        $normalizedEntry = [
            'variadic' => $variadic,
            'byRef' => $byRef,
            'optional' => false,
            'type' => $type,
        ];

        // Read the reference mode
        if ($byRef) {
            $parts = explode(' ', $normalizedKey, 2);
            if (count($parts) === 2) {
                if (!($parts[0] === 'rw' || $parts[0] === 'w' || $parts[0] === 'r')) {
                    $normalizedEntry['refMode'] = 'rw';
                } else {
                    $normalizedEntry['refMode'] = $parts[0];
                    $normalizedKey = $parts[1];
                }
            } else {
                $normalizedEntry['refMode'] = 'rw';
            }
        }

        // Strip prefixes.
        if (substr($normalizedKey, -1, 1) === "=") {
            $normalizedEntry['optional'] = true;
            $normalizedKey = substr($normalizedKey, 0, -1);
        }

        $normalizedEntry['name'] = $normalizedKey;
        $normalizedEntries[$normalizedKey] = $normalizedEntry;
    }

    return $normalizedEntries;
}

/**
 * @param array<int|string, string> $baseParameters
 * @param array<int|string, string> $customParameters
 * @return array<int|string, string>
 */
function assertEntryParameters(string $func, array $baseParameters, array $customParameters): array
{
    if ($func === 'max' || $func === 'min') {
        return $customParameters;
    }
    if (!isset($baseParameters[0], $customParameters[0])) {
        throw new AssertionError("$func has no return type");
    }
    $denormalized = [assertTypeValidity($baseParameters[0], $customParameters[0])];

    $base = normalizeParameters($func, $baseParameters);
    $custom = normalizeParameters($func, $customParameters);

    $customByPosition = array_values($custom);

    $final = [];
    $idx = 0;
    foreach ($base as $name => $parameter) {
        if ($parameter['variadic'] && count($custom) > $idx + 1) {
            // C variadics can require a first argument that reflection does not distinguish from the rest.
            foreach (array_slice($custom, $idx) as $customName => $customParameter) {
                $variadicBase = $parameter;
                $variadicBase['variadic'] = $customParameter['variadic'];
                $variadicBase['optional'] = $customParameter['optional'];
                $final[$customName] = assertParameter($func, $customParameter, $variadicBase);
            }
            break;
        }
        $positional = $customByPosition[$idx] ?? null;
        // a name reflection lacks renames the parameter at this position, so a later one may share this name
        if ($positional !== null && !isset($base[$positional['name']])) {
            $final[$name] = assertParameter($func, $positional, $parameter);
        } elseif (isset($custom[$name])) {
            $final[$name] = assertParameter($func, $custom[$name], $parameter);
        } elseif ($positional !== null) {
            $final[$name] = assertParameter($func, $positional, $parameter);
        } else {
            $final[$name] = $parameter;
        }
        $idx++;
    }

    foreach ($final as $key => $param) {
        if (isset($param['refMode']) && $param['refMode'] !== 'rw') {
            $key = "{$param['refMode']} $key";
        }
        if ($param['variadic']) {
            $key = "...$key";
        }
        if ($param['byRef']) {
            $key = "&$key";
        }
        if ($param['optional']) {
            $key = "$key=";
        }
        $denormalized[$key] = $param['type'];
    }

    return $denormalized;
}

/**
 * @param array{byRef: bool, refMode?: 'rw'|'w'|'r', variadic: bool, optional: bool, type: string, name: string} $custom
 * @param array{byRef: bool, refMode?: 'rw'|'w'|'r', variadic: bool, optional: bool, type: string, name: string} $base
 * @return array{byRef: bool, refMode?: 'rw'|'w'|'r', variadic: bool, optional: bool, type: string, name: string}
 */
function assertParameter(string $func, array $custom, array $base): array
{
    if ($func !== 'version_compare') {
        $custom['optional'] = $base['optional'];
    }
    $custom['variadic'] = $base['variadic'];
    $custom['byRef'] = $base['byRef'];

    $custom['type'] = assertTypeValidity($base['type'], $custom['type']);

    return $custom;
}

function assertTypeValidity(string $base, string $custom): string
{
    $expectedType = Type::parseString($base);
    $callMapType = Type::parseString($custom === '' ? $base : $custom);

    $codebase = ProjectAnalyzer::getInstance()->getCodebase();
    try {
        if (!UnionTypeComparator::isContainedBy(
            $codebase,
            $callMapType,
            $expectedType,
            false,
            false,
            null,
            false,
            false,
        ) && !str_contains($custom, 'static')) {
            $custom = $expectedType->getId(true);
            $callMapType = $expectedType;
        }
    } catch (Throwable) {
    }

    if ($expectedType->hasMixed()) {
        return $custom;
    }
    $callMapType = $callMapType->getBuilder();
    if ($expectedType->isNullable() !== $callMapType->isNullable()) {
        if ($expectedType->isNullable()) {
            $callMapType->addType(new TNull());
        } else {
            $callMapType->removeType('null');
        }
    }
    return $callMapType->getId(true);
}

/**
 * @param array<array-key, array<array-key, mixed>> $callMap
 */
function writeCallMap(string $file, array $callMap): void
{
    foreach ($callMap as &$entry) {
        if (isset($entry[-1]) && ($entry[0] ?? null) === $entry[-1]) {
            unset($entry[0]);
        }
    }
    unset($entry);

    file_put_contents($file, '<?php // phpcs:ignoreFile

return '.var_export($callMap, true).';');
}

/**
 * Writes the callmap version (e.g. 83) that introduced each key missing from the oldest callmap.
 *
 * @param array<int, array<string, mixed>> $callMaps the callmaps by version
 */
function writeCallMapIntroductions(string $file, array $callMaps): void
{
    ksort($callMaps);
    $oldest = array_key_first($callMaps);
    $introductions = [];
    if ($oldest !== null) {
        foreach ($callMaps as $version => $callMap) {
            if ($version === $oldest) {
                continue;
            }
            foreach ($callMap as $key => $_) {
                if (!isset($callMaps[$oldest][$key])) {
                    $introductions[$key] ??= $version;
                }
            }
        }
    }
    ksort($introductions);

    file_put_contents($file, '<?php // phpcs:ignoreFile

return '.var_export($introductions, true).';');
}

/**
 * @psalm-pure
 * @template K as array-key
 * @template V
 * @param array<K, V> $a
 * @param array<K, V> $b
 * @return array<K, V>
 */
function get_changed_functions(array $a, array $b): array
{
    $changed_functions = [];

    foreach (array_intersect_key($a, $b) as $function_name => $a_data) {
        if (json_encode($b[$function_name]) !== json_encode($a_data)) {
            $changed_functions[$function_name] = $b[$function_name];
        }
    }

    return $changed_functions;
}

/**
 * @psalm-capabilities read-props
 * @param array<Stmt> $statements
 * @return array<lowercase-string, true>
 */
function extractClassesFromStatements(array $statements): array
{
    $classes = [];
    foreach ($statements as $statement) {
        if ($statement instanceof Class_ && $statement->namespacedName !== null) {
            $classes[strtolower($statement->namespacedName->toString())] = true;
        }
        if ($statement instanceof Namespace_) {
            $classes += extractClassesFromStatements($statement->stmts);
        }
    }

    return $classes;
}

/**
 * @psalm-pure
 * @param array<array-key, array<array-key, string>|string> $array
 */
function serializeArray(array $array, string $prefix): string
{
    uksort(
        $array,
        static fn(int|string $first, int|string $second): int
            => strtolower((string) $first) <=> strtolower((string) $second),
    );
    $result = "[\n";
    $localPrefix = $prefix . '    ';
    foreach ($array as $key => $value) {
        $result .= $localPrefix . var_export((string) $key, true) . ' => ' .
            (is_array($value)
            ? serializeArray($value, $localPrefix)
            : var_export($value, true)) . ",\n";
    }
    $result .= $prefix . ']';

    return $result;
}
