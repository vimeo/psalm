<?php
namespace Psalm\Tests;

use Generator;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Type;

class CodebaseTest extends TestCase
{
    /** @var Codebase */
    private $codebase;

    /** @return void */
    public function setUp()
    {
        parent::setUp();
        $this->codebase = $this->project_analyzer->getCodebase();
    }

    /**
     * @test
     * @dataProvider typeContainments
     * @return void
     */
    public function isTypeContainedByType(string $input, string $container, bool $expected)
    {
        $input = Type::parseString($input);
        $container = Type::parseString($container);

        $this->assertEquals(
            $expected,
            $this->codebase->isTypeContainedByType($input, $container),
            'Expected ' . $input->getId() . ($expected ? ' ' : ' not ')
            . 'to be contained in ' . $container->getId()
        );
    }


    /** @return iterable<int,array{string,string,bool} */
    public function typeContainments()
    {
        yield ['int', 'int|string', true];
        yield ['int|string', 'int', false];

        // This fails with 'could not get class storage' :(

        // yield ['RuntimeException', 'Exception', true];
        // yield ['Exception', 'RuntimeException', false];
    }

    /**
     * @test
     * @dataProvider typeIntersections
     * @return void
     */
    public function canTypeBeContainedByType(string $input, string $container, bool $expected)
    {
        $input = Type::parseString($input);
        $container = Type::parseString($container);

        $this->assertEquals(
            $expected,
            $this->codebase->canTypeBeContainedByType($input, $container),
            'Expected ' . $input->getId() . ($expected ? ' ' : ' not ')
            . 'to be contained in ' . $container->getId()
        );
    }

    /** @return iterable<int,array{string,string,bool} */
    public function typeIntersections()
    {
        yield ['int', 'int|string', true];
        yield ['int|string', 'int', true];
        yield ['int|string', 'string|float', true];
        yield ['int', 'string', false];
        yield ['int|string', 'array|float', false];
    }

    /**
     * @test
     * @dataProvider iterableParams
     * @param array{string,string} $expected
     * @return void
     */
    public function getKeyValueParamsForTraversableObject(string $input, array $expected)
    {
        list($input) = array_values(Type::parseString($input)->getTypes());

        $expected_key_type = Type::parseString($expected[0]);
        $expected_value_type = Type::parseString($expected[1]);

        $actual = $this->codebase->getKeyValueParamsForTraversableObject($input);

        $this->assertTrue(
            $expected_key_type->equals($actual[0]),
            'Expected ' . $input->getId() . ' to have ' . $expected_key_type
            . ' but got ' . $actual[0]->getId()
        );

        $this->assertTrue(
            $expected_value_type->equals($actual[1]),
            'Expected ' . $input->getId() . ' to have ' . $expected_value_type
            . ' but got ' . $actual[1]->getId()
        );
    }

    /** @return iterable<int,array{string,array{string,string}} */
    public function iterableParams()
    {
        yield ['iterable<int,string>', ['int', 'string']];
        yield ['iterable<int|string,bool|float', ['int|string', 'bool|float']];
    }
}
