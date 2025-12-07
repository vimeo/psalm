<?php

declare(strict_types=1);

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Type;
use Psalm\Type\Atomic\TNull;

function internalNormalizeCallMap(array|string $callMap, string|int $key = 0): array|string
{
    if (is_string($callMap)) {
        return Type::parseString($callMap === '' ? 'mixed' : $callMap)->getId(true);
    }

    $new = [];

    $value = null;
    foreach ($callMap as $key => $value) {
        $new[is_string($key) && is_array($value) ? strtolower($key) : $key] = internalNormalizeCallMap($value, $key);
    }
    if (is_array($value) && $key !== 'old' && $key !== 'new') {
        ksort($new);
    }

    return $new;
}

function normalizeCallMap(array $callMap): array
{
    return internalNormalizeCallMap($callMap);
}

/**
 * @return array<string, array{byRef: bool, refMode: 'rw'|'w'|'r', variadic: bool, optional: bool, type: string}>
 */
function normalizeParameters(string $func, array $parameters): array
{

    /**
     * Parse the parameter names from the map.
     *
     * @var array<string, array{byRef: bool, refMode: 'rw'|'w'|'r', variadic: bool, optional: bool, type: string}>
     */
    $normalizedEntries = [];
    
    foreach ($parameters as $key => $entry) {
        if ($key === 0) {
            continue;
        }
        $normalizedKey = $key;
        /**
         * @var array{byRef: bool, refMode: 'rw'|'w'|'r', variadic: bool, optional: bool, type: string} $normalizedEntry
         */
        $normalizedEntry = [
            'variadic' => false,
            'byRef' => false,
            'optional' => false,
            'type' => $entry,
        ];

        do {
            if (strncmp($normalizedKey, '...', 3) === 0) {
                $normalizedEntry['variadic'] = true;
                $normalizedKey = substr($normalizedKey, 3);
                continue;
            }

            if (strncmp($normalizedKey, '&', 1) === 0) {
                $normalizedEntry['byRef'] = true;
                $normalizedKey = substr($normalizedKey, 1);
                continue;
            }
            break;
        } while (true);

        // Read the reference mode
        if ($normalizedEntry['byRef']) {
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
 * @param array<string|int, string> $baseParameters
 * @param array<string|int, string> $customParameters
 * @return array<string|int, string>
 */
function assertEntryParameters(string $func, array $baseParameters, array $customParameters): array
{
    if ($func === 'max' || $func === 'min') {
        return $customParameters;
    }
    $denormalized = [assertTypeValidity($baseParameters[0], $customParameters[0], "Return $func")];

    $baseParameters = normalizeParameters($func, $baseParameters);
    $customParameters = normalizeParameters($func, $customParameters);

    $customParametersByVal = array_values($customParameters);

    $final = [];
    $idx = 0;
    foreach ($baseParameters as $name => $parameter) {
        if (isset($customParameters[$name])) {
            $final[$name] = assertParameter($func, $name, $customParameters[$name], $parameter);
        } elseif (isset($customParametersByVal[$idx])) {
            $final[$name] = assertParameter($func, $name, $customParametersByVal[$idx], $parameter);
        } else {
            $final[$name] = $parameter;
        }
        $idx++;
    }

    foreach ($final as $key => $param) {
        if ($key === 0) {
            continue;
        }
        if (($param['refMode'] ?? 'rw') !== 'rw') {
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
 * @param array{
 *      byRef: bool,
 *      name?: string,
 *      refMode: 'rw'|'w'|'r',
 *      variadic: bool,
 *      optional: bool,
 *      type: string
 * } $custom
 * @param array{
 *      byRef: bool,
 *      name?: string,
 *      refMode: 'rw'|'w'|'r',
 *      variadic: bool,
 *      optional: bool,
 *      type: string
 * } $base
 */
function assertParameter(string $func, string $paramName, array $custom, array $base): array
{
    if ($func !== 'version_compare') {
        $custom['optional'] = $base['optional'];
    }
    $custom['variadic'] = $base['variadic'];
    $custom['byRef'] = $base['byRef'];
    
    $custom['type'] = assertTypeValidity($base['type'], $custom['type'], "Param $func '{$paramName}'");

    return $custom;
}

function assertTypeValidity(string $base, string $custom, string $msgPrefix): string
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

function writeCallMap(string $file, array $callMap): void
{
    file_put_contents($file, '<?php // phpcs:ignoreFile

return '.var_export($callMap, true).';');
}

/**
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

function extractClassesFromStatements(array $statements): array
{
    $classes = [];
    foreach ($statements as $statement) {
        if ($statement instanceof Class_) {
            $classes[strtolower($statement->namespacedName->toString())] = true;
        }
        if ($statement instanceof Namespace_) {
            $classes += extractClassesFromStatements($statement->stmts);
        }
    }

    return $classes;
}

function serializeArray(array $array, string $prefix): string
{
    uksort($array, fn(string $first, string $second): int => strtolower($first) <=> strtolower($second));
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
