<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use DG\BypassFinals;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\FileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Tests\TestConfig;
use Psalm\Type;

function internalNormalizeCallMap(array|string $callMap): array|string {
    if (is_string($callMap)) {
        return Type::parseString($callMap === '' ? 'mixed' : $callMap)->getId(true);
    }

    $new = [];

    foreach ($callMap as $key => $value) {
        $new[is_string($key) && is_array($value) ? strtolower($key) : $key] = internalNormalizeCallMap($value);
    }

    return $new;
}

function normalizeCallMap(array $callMap): array {
    return internalNormalizeCallMap($callMap);
}

function typeToString(?ReflectionType $reflection_type = null): string
{
    if (!$reflection_type) {
        return 'string';
    }

    if ($reflection_type instanceof ReflectionNamedType) {
        $type = $reflection_type->getName();
    } elseif ($reflection_type instanceof ReflectionUnionType) {
        $type = implode(
            '|',
            array_map(
                static fn(ReflectionNamedType $reflection): string => $reflection->getName(),
                $reflection_type->getTypes(),
            ),
        );
    } else {
        throw new LogicException('Unexpected reflection class ' . $reflection_type::class . ' found.');
    }

    if ($reflection_type->allowsNull()) {
        $type .= '|null';
    }

    return $type;
}

/**
 * @return array<string, array{byRef: bool, refMode: 'rw'|'w'|'r', variadic: bool, optional: bool, type: string}>
 */
function paramsToEntries(ReflectionFunctionAbstract $reflectionFunction): array
{
    $res = [typeToString($reflectionFunction->getReturnType())];

    foreach ($reflectionFunction->getParameters() as $param) {
        $key = $param->getName();
        if ($param->isPassedByReference()) {
            $key = "&$key";
        }
        if ($param->isVariadic()) {
            $key = "...$key";
        }
        if ($param->isOptional()) {
            $key .= '=';
        }

        $res[$key] = typeToString($param->getType());
    }

    return $res;
}


/**
     * Returns the correct reflection type for function or method name.
     */
function getReflectionFunction(string $functionName): ?ReflectionFunctionAbstract
{
    try {
        if (strpos($functionName, '::') !== false) {
            if (PHP_VERSION_ID < 8_03_00) {
                return new ReflectionMethod($functionName);
            }
    
            return ReflectionMethod::createFromMethodName($functionName);
        }
    
        /** @var callable-string $functionName */
        return new ReflectionFunction($functionName);
    } catch (ReflectionException $e) {
        return null;
    }
}
    
    /**
     * @param array<string, string> $entryParameters
     */
function assertEntryParameters(ReflectionFunctionAbstract $function, array &$entryParameters): void
{
    assertEntryReturnType($function, $entryParameters[0]);

    /**
     * Parse the parameter names from the map.
     *
     * @var array<string, array{byRef: bool, refMode: 'rw'|'w'|'r', variadic: bool, optional: bool, type: string}>
     */
    $normalizedEntries = [];
    
    foreach ($entryParameters as $key => &$entry) {
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
            'type' => &$entry,
        ];
        if (strncmp($normalizedKey, '&', 1) === 0) {
            $normalizedEntry['byRef'] = true;
            $normalizedKey = substr($normalizedKey, 1);
        }
    
        if (strncmp($normalizedKey, '...', 3) === 0) {
            $normalizedEntry['variadic'] = true;
            $normalizedKey = substr($normalizedKey, 3);
        }
    
        // Read the reference mode
        if ($normalizedEntry['byRef']) {
            $parts = explode('_', $normalizedKey, 2);
            if (count($parts) === 2) {
                if (!($parts[0] === 'rw' || $parts[0] === 'w' || $parts[0] === 'r')) {
                    throw new InvalidArgumentException('Invalid refMode: '.$parts[0]);
                }
                $normalizedEntry['refMode'] = $parts[0];
                $normalizedKey = $parts[1];
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
    
    foreach ($function->getParameters() as $parameter) {
        if (isset($normalizedEntries[$parameter->getName()])) {
            assertParameter($normalizedEntries[$parameter->getName()], $parameter);
        }
    }
}
    
    /**
     * @param array{byRef: bool, name?: string, refMode: 'rw'|'w'|'r', variadic: bool, optional: bool, type: string} $normalizedEntry
     */
function assertParameter(array &$normalizedEntry, ReflectionParameter $param): void
{
    $name = $param->getName();
    
    $expectedType = $param->getType();
    
    if (isset($expectedType) && !empty($normalizedEntry['type'])) {
        $func = $param->getDeclaringFunction()->getName();
        assertTypeValidity($expectedType, $normalizedEntry['type'], "Param $func '{$name}'");
    }
}
    
function assertEntryReturnType(ReflectionFunctionAbstract $function, string &$entryReturnType): void
{
    if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
        $expectedType = $function->hasTentativeReturnType() ? $function->getTentativeReturnType() : $function->getReturnType();
    } else {
        $expectedType = $function->getReturnType();
    }
    
    if ($expectedType !== null) {
        assertTypeValidity($expectedType, $entryReturnType, 'Return');
    }
}
    
        /**
         * Since string equality is too strict, we do some extra checking here
         */
function assertTypeValidity(ReflectionType $reflected, string &$specified, string $msgPrefix): void
{
    $expectedType = Reflection::getPsalmTypeFromReflectionType($reflected);
    $callMapType = Type::parseString($specified === '' ? 'mixed' : $specified);
    
    $codebase = ProjectAnalyzer::getInstance()->getCodebase();
    try {
        if (!UnionTypeComparator::isContainedBy($codebase, $callMapType, $expectedType, false, false, null, false, false) && !str_contains($specified, 'static')) {
            $specified = $expectedType->getId(true);
            $callMapType = $expectedType;
        }
    } catch (Throwable) {
    }
    
    if ($expectedType->hasMixed()) {
        return;
    }
    $callMapType = $callMapType->getBuilder();
    if ($expectedType->isNullable() !== $callMapType->isNullable()) {
        if ($expectedType->isNullable()) {
            $callMapType->addType(new TNull());
        } else {
            $callMapType->removeType('null');
        }
    }
    $specified = $callMapType->getId(true);
    //    //$this->assertSame($expectedType->hasBool(), $callMapType->hasBool(), "{$msgPrefix} type '{$specified}' missing bool from reflected type '{$reflected}'");
    //    $this->assertSame($expectedType->hasArray(), $callMapType->hasArray(), "{$msgPrefix} type '{$specified}' missing array from reflected type '{$reflected}'");
    //    $this->assertSame($expectedType->hasInt(), $callMapType->hasInt(), "{$msgPrefix} type '{$specified}' missing int from reflected type '{$reflected}'");
    //    $this->assertSame($expectedType->hasFloat(), $callMapType->hasFloat(), "{$msgPrefix} type '{$specified}' missing float from reflected type '{$reflected}'");
}

function writeCallMap(string $file, array $callMap): void
{
    file_put_contents($file, '<?php // phpcs:ignoreFile

return '.var_export($callMap, true).';');
}


BypassFinals::enable();

new ProjectAnalyzer(new TestConfig, new Providers(new FileProvider));
$callMap = require "dictionaries/CallMap.php";
$orig = $callMap;

$codebase = ProjectAnalyzer::getInstance()->getCodebase();
