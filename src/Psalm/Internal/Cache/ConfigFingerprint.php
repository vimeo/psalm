<?php

declare(strict_types=1);

namespace Psalm\Internal\Cache;

use Closure;
use HashContext;
use Psalm\Config;
use ReflectionClass;
use ReflectionFunction;
use ReflectionObject;
use SimpleXMLElement;
use UnitEnum;

use function count;
use function get_resource_type;
use function hash_final;
use function hash_init;
use function hash_update;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_object;
use function is_resource;
use function is_string;
use function pack;
use function spl_object_id;
use function strlen;

/**
 * A digest of the state of a Config: every instance property, walked reflectively (scalars, arrays in order, objects
 * by class and properties, SimpleXMLElement as XML), except the run plumbing that NOT_ANALYSIS lists. A property
 * that is not listed changes the digest, so a new setting keeps a cache from being read under another value of it.
 *
 * @internal
 */
final class ConfigFingerprint
{
    /**
     * Properties of Config that do not change what an analysis finds, with the reason
     */
    public const NOT_ANALYSIS = [
        'cache_directory' => 'where the caches are',
        'global_cache_directory' => 'where the caches are',
        'cache_directory_initialized' => 'where the caches are',
        'array_cache' => 'whether the caches keep their items in memory',
        'use_igbinary' => 'the serializer: the engine component',
        'compressor' => 'the serializer: the engine component',
        'threads' => 'parallelism',
        'scan_threads' => 'parallelism',
        'eventDispatcher' => 'the handlers of the plugins: their files are the plugins component',
        'plugins' => 'the instances of the plugins: their files are the plugins component',
        'autoloaders' => 'the class loaders of the process',
        'include_collector' => 'the files the process included',
        'predefined_constants' => 'collected from the running PHP (the runtime component) and the autoload files of '
            . 'the project when these are visited, after the caches open',
        'predefined_functions' => 'collected from the running PHP (the runtime component) and the autoload files of '
            . 'the project when these are visited, after the caches open',
        'internal_stubs' => 'derived from the analysis PHP version (the php component) when the stubs are scanned',
        'config_warnings' => 'messages printed at the start',
        'debug_emitted_issues' => 'debug output',
        'force_jit' => 'how the process runs',
        'long_scan_warning' => 'when the progress output warns about a slow scan',
        'shepherd_endpoint' => 'where the Shepherd report goes',
        'min_type_coverage' => 'a threshold checked after the analysis',
        'analysis_identity' => 'the identity itself, once taken',
    ];

    public static function of(Config $config): string
    {
        $context = hash_init('xxh128');
        /** @var array<int, int> $seen */
        $seen = [spl_object_id($config) => 0];

        self::updateProperties($context, $config, new ReflectionObject($config), $seen, self::NOT_ANALYSIS);

        return hash_final($context);
    }

    /**
     * @param array<int, int> $seen the objects already described, by their position
     * @param array<string, string> $skipped
     */
    private static function updateProperties(
        HashContext $context,
        object $object,
        ReflectionClass $class,
        array &$seen,
        array $skipped = [],
    ): void {
        // the private properties of a parent class are not properties of the child's reflection
        do {
            foreach ($class->getProperties() as $property) {
                if ($property->isStatic()
                    || $property->class !== $class->name
                    || isset($skipped[$property->name])
                ) {
                    continue;
                }

                hash_update($context, $property->class . '::' . $property->name . "\0");

                if ($property->isInitialized($object)) {
                    self::update($context, $property->getValue($object), $seen);
                } else {
                    hash_update($context, 'u');
                }
            }
        } while ($class = $class->getParentClass());
    }

    /** @param array<int, int> $seen */
    private static function update(HashContext $context, mixed $value, array &$seen): void
    {
        if ($value === null) {
            hash_update($context, 'n');
        } elseif (is_bool($value)) {
            hash_update($context, $value ? 't' : 'f');
        } elseif (is_int($value)) {
            hash_update($context, 'i' . $value . ';');
        } elseif (is_float($value)) {
            hash_update($context, 'd' . pack('E', $value));
        } elseif (is_string($value)) {
            hash_update($context, 's' . strlen($value) . ':' . $value);
        } elseif (is_array($value)) {
            hash_update($context, 'a' . count($value) . '{');
            /** @var mixed $item */
            foreach ($value as $key => $item) {
                self::update($context, $key, $seen);
                self::update($context, $item, $seen);
            }
            hash_update($context, '}');
        } elseif (is_object($value)) {
            $id = spl_object_id($value);
            if (isset($seen[$id])) {
                hash_update($context, 'r' . $seen[$id] . ';');
                return;
            }
            $seen[$id] = count($seen);

            if ($value instanceof SimpleXMLElement) {
                $xml = (string) $value->asXML();
                hash_update($context, 'x' . strlen($xml) . ':' . $xml);
            } elseif ($value instanceof UnitEnum) {
                hash_update($context, 'e' . $value::class . '::' . $value->name . ';');
            } elseif ($value instanceof Closure) {
                $function = new ReflectionFunction($value);
                hash_update(
                    $context,
                    'c' . $function->getFileName() . ':' . $function->getStartLine() . '-' . $function->getEndLine()
                        . ';',
                );
            } else {
                hash_update($context, 'o' . $value::class . '{');
                self::updateProperties($context, $value, new ReflectionObject($value), $seen);
                hash_update($context, '}');
            }
        } elseif (is_resource($value)) {
            hash_update($context, 'R' . get_resource_type($value) . ';');
        }
    }
}
