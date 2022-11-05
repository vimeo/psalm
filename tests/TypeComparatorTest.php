<?php

namespace Psalm\Tests;

use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TypeTokenizer;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Type;
use Psalm\Type\Atomic\TPropertiesOf;

use function array_diff_key;
use function array_keys;
use function array_map;

class TypeComparatorTest extends TestCase
{
    public function setUp(): void
    {
        RuntimeCaches::clearAll();
        $this->file_provider = new FakeFileProvider();

        $config = new TestConfig();

        $providers = new Providers(
            $this->file_provider,
            new FakeParserCacheProvider()
        );

        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            $providers
        );
    }

    /**
     * @dataProvider getAllBasicTypes
     */
    public function testTypeAcceptsItself(string $type_string): void
    {
        $type_1 = Type::parseString($type_string);
        $type_2 = Type::parseString($type_string);

        $this->assertTrue(
            UnionTypeComparator::isContainedBy(
                $this->project_analyzer->getCodebase(),
                $type_1,
                $type_2
            )
        );
    }

    /**
     * @return array<strict-array{string}>
     */
    public function getAllBasicTypes(): array
    {
        // these types are not valid without generics attached
        $basic_generic_types = [
            'key-of' => true,
            'arraylike-object' => true,
            'value-of' => true,
            'class-string-map' => true,
            'int-mask-of' => true,
            'int-mask' => true,
            'pure-Closure' => true,
        ];
        foreach (TPropertiesOf::tokenNames() as $token_name) {
            $basic_generic_types[$token_name] = true;
        }

        $basic_types = array_diff_key(
            TypeTokenizer::PSALM_RESERVED_WORDS,
            $basic_generic_types,
            [
                'open-resource' => true, // unverifiable
                'non-empty-countable' => true, // bit weird, maybe a bug?
            ],
            [
                'strict-array' => true, // Requires a shape
                'strict-list' => true, // Requires a shape
            ]
        );
        $basic_types['strict-array{test: 123}'] = true;
        $basic_types['strict-list{123}'] = true;

        return array_map(
            fn($type) => [$type],
            array_keys($basic_types)
        );
    }

    /**
     * @dataProvider getAllowedChildTypes
     */
    public function testTypeAcceptsType(string $parent_type_string, string $child_type_string): void
    {
        $parent_type = Type::parseString($parent_type_string);
        $child_type = Type::parseString($child_type_string);

        $this->assertTrue(
            UnionTypeComparator::isContainedBy(
                $this->project_analyzer->getCodebase(),
                $child_type,
                $parent_type
            )
        );
    }

    /**
     * @return array<strict-array{string, string}>
     */
    public function getAllowedChildTypes(): array
    {
        return [
            'iterableAcceptsArray' => [
                'iterable',
                'array',
            ],
            'listAcceptsEmptyArray' => [
                'list',
                'array<never, never>',
            ],
            'arrayAcceptsEmptyArray' => [
                'array',
                'array<never, never>',
            ],
            'arrayOptionalKeyed1AcceptsEmptyArray' => [
                'strict-array{foo?: string}',
                'array<never, never>',
            ],
            'arrayOptionalKeyed2AcceptsEmptyArray' => [
                'strict-array{foo?: string}&array<string, mixed>',
                'array<never, never>',
            ],
            'Lowercase-stringAndCallable-string' => [
                'lowercase-string',
                'callable-string',
            ],
        ];
    }
}
