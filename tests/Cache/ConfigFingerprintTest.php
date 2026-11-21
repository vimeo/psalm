<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use PHPUnit\Framework\TestCase;
use Psalm\Config;
use Psalm\Internal\Cache\ConfigFingerprint;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

use function array_keys;
use function count;
use function dirname;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

final class ConfigFingerprintTest extends TestCase
{
    private const CONFIG = __DIR__ . '/../fixtures/ModularConfig/psalm.xml';

    public function testTwoLoadsOfAConfigHaveOneFingerprint(): void
    {
        $this->assertSame(ConfigFingerprint::of(self::load()), ConfigFingerprint::of(self::load()));
    }

    /**
     * A property that is neither listed as run plumbing nor a part of the fingerprint would let a cache made under one
     * value of it be read under another
     */
    public function testEachSettingChangesTheFingerprint(): void
    {
        $config = self::load();
        $fingerprint = ConfigFingerprint::of($config);

        $changed = [];
        $unchanged = [];
        foreach (self::properties() as $property) {
            if (isset(ConfigFingerprint::NOT_ANALYSIS[$property->name])) {
                continue;
            }

            $value = self::otherValue($property, $config);
            if ($value === null) {
                continue;
            }

            $copy = clone $config;
            $property->setValue($copy, $value[0]);
            if (ConfigFingerprint::of($copy) === $fingerprint) {
                $unchanged[] = $property->name;
            } else {
                $changed[] = $property->name;
            }
        }

        $this->assertSame([], $unchanged);
        $this->assertGreaterThan(80, count($changed));
    }

    public function testThePlumbingListNamesPropertiesOfTheConfig(): void
    {
        $names = [];
        foreach (self::properties() as $property) {
            $names[$property->name] = true;
        }

        foreach (array_keys(ConfigFingerprint::NOT_ANALYSIS) as $name) {
            $this->assertArrayHasKey($name, $names);
        }
    }

    private static function load(): Config
    {
        return Config::loadFromXMLFile(self::CONFIG, dirname(self::CONFIG));
    }

    /**
     * @return list<ReflectionProperty>
     * @psalm-pure
     */
    private static function properties(): array
    {
        $properties = [];
        foreach ((new ReflectionClass(Config::class))->getProperties() as $property) {
            if (!$property->isStatic() && !$property->isReadOnly()) {
                $properties[] = $property;
            }
        }

        return $properties;
    }

    /**
     * Another value of a scalar or array property, null for an object
     *
     * @return array{mixed}|null
     */
    private static function otherValue(ReflectionProperty $property, Config $config): ?array
    {
        $value = $property->isInitialized($config) ? $property->getValue($config) : null;
        if (is_bool($value)) {
            return [!$value];
        }
        if (is_int($value)) {
            return [$value + 1];
        }
        if (is_float($value)) {
            return [$value + 1.5];
        }
        if (is_string($value)) {
            return [$value . 'x'];
        }
        if (is_array($value)) {
            return [$value + ['probe' => 'probe']];
        }
        if ($value !== null) {
            return null;
        }

        $type = $property->getType();
        if (!$type instanceof ReflectionNamedType) {
            return null;
        }

        return match ($type->getName()) {
            'string' => ['x'],
            'int' => [1],
            'float' => [1.5],
            'bool' => [true],
            'array' => [['probe' => 'probe']],
            default => null,
        };
    }
}
