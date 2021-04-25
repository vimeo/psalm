<?php
namespace Psalm\Tests;

use function function_exists;
use function print_r;
use function mb_substr;
use function stripos;

use Psalm\Internal\RuntimeCaches;
use Psalm\Type;

class TypeComparatorTest extends TestCase
{
    public function setUp() : void
    {
        RuntimeCaches::clearAll();
        $this->file_provider = new \Psalm\Tests\Internal\Provider\FakeFileProvider();

        $config = new TestConfig();

        $providers = new \Psalm\Internal\Provider\Providers(
            $this->file_provider,
            new \Psalm\Tests\Internal\Provider\FakeParserCacheProvider()
        );

        $this->project_analyzer = new \Psalm\Internal\Analyzer\ProjectAnalyzer(
            $config,
            $providers
        );
    }

    /**
     * @dataProvider getAllBasicTypes
     */
    public function testTypeAcceptsItself(string $type_string): void
    {
        $type_1 = \Psalm\Type::parseString($type_string);
        $type_2 = \Psalm\Type::parseString($type_string);

        $this->assertTrue(
            \Psalm\Internal\Type\Comparator\UnionTypeComparator::isContainedBy(
                $this->project_analyzer->getCodebase(),
                $type_1,
                $type_2
            )
        );
    }

    /**
     * @return array<array{string}>
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

        $basic_types = \array_diff_key(
            \Psalm\Internal\Type\TypeTokenizer::PSALM_RESERVED_WORDS,
            $basic_generic_types,
            [
                'open-resource' => true, // unverifiable
                'mysql-escaped-string' => true, // deprecated
                'non-empty-countable' => true, // bit weird, maybe a bug?
            ]
        );
        return \array_map(
            function ($type) {
                return [$type];
            },
            \array_keys($basic_types)
        );
    }
}
