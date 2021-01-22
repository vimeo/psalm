<?php
namespace Psalm\Tests\TypeReconciliation;

use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Type;

class ReconcilerTest extends \Psalm\Tests\TestCase
{
    /** @var FileAnalyzer */
    protected $file_analyzer;

    /** @var StatementsAnalyzer */
    protected $statements_analyzer;

    public function setUp() : void
    {
        parent::setUp();

        $this->file_analyzer = new FileAnalyzer($this->project_analyzer, 'somefile.php', 'somefile.php');
        $this->file_analyzer->context = new Context();
        $this->statements_analyzer = new StatementsAnalyzer(
            $this->file_analyzer,
            new \Psalm\Internal\Provider\NodeDataProvider()
        );

        $this->addFile('newfile.php', '
            <?php
            class A {}
            class B extends A {}
        ');
        $this->project_analyzer->getCodebase()->scanFiles();
    }

    /**
     * @dataProvider providerTestReconcilation
     *
     */
    public function testReconcilation(string $expected_type, string $assertion, string $original_type): void
    {
        $reconciled = \Psalm\Internal\Type\AssertionReconciler::reconcile(
            $assertion,
            Type::parseString($original_type),
            null,
            $this->statements_analyzer,
            false,
            []
        );

        $this->assertSame(
            $expected_type,
            $reconciled->getId()
        );

        $this->assertContainsOnlyInstancesOf('Psalm\Type\Atomic', $reconciled->getAtomicTypes());
    }

    /**
     * @dataProvider providerTestTypeIsContainedBy
     *
     * @param string $input
     * @param string $container
     *
     */
    public function testTypeIsContainedBy($input, $container): void
    {
        $this->assertTrue(
            UnionTypeComparator::isContainedBy(
                $this->project_analyzer->getCodebase(),
                Type::parseString($input),
                Type::parseString($container)
            )
        );
    }

    /**
     * @return array<string,array{string,string,string}>
     */
    public function providerTestReconcilation(): array
    {
        return [
            'notNullWithObject' => ['MyObject', '!null', 'MyObject'],
            'notNullWithObjectPipeNull' => ['MyObject', '!null', 'MyObject|null'],
            'notNullWithMyObjectPipeFalse' => ['MyObject|false', '!null', 'MyObject|false'],
            'notNullWithMixed' => ['mixed', '!null', 'mixed'],

            'notEmptyWithMyObject' => ['MyObject', '!falsy', 'MyObject'],
            'notEmptyWithMyObjectPipeNull' => ['MyObject', '!falsy', 'MyObject|null'],
            'notEmptyWithMyObjectPipeFalse' => ['MyObject', '!falsy', 'MyObject|false'],
            'notEmptyWithMixed' => ['non-empty-mixed', '!falsy', 'mixed'],
            // @todo in the future this should also work
            //'notEmptyWithMyObjectFalseTrue' => ['MyObject|true', '!falsy', 'MyObject|bool'],

            'nullWithMyObjectPipeNull' => ['null', 'null', 'MyObject|null'],
            'nullWithMixed' => ['null', 'null', 'mixed'],

            'falsyWithMyObject' => ['mixed', 'falsy', 'MyObject'],
            'falsyWithMyObjectPipeFalse' => ['false', 'falsy', 'MyObject|false'],
            'falsyWithMyObjectPipeBool' => ['false', 'falsy', 'MyObject|bool'],
            'falsyWithMixed' => ['empty-mixed', 'falsy', 'mixed'],
            'falsyWithBool' => ['false', 'falsy', 'bool'],
            'falsyWithStringOrNull' => ['null|string()|string(0)', 'falsy', 'string|null'],
            'falsyWithScalarOrNull' => ['empty-scalar', 'falsy', 'scalar'],

            'notMyObjectWithMyObjectPipeBool' => ['bool', '!MyObject', 'MyObject|bool'],
            'notMyObjectWithMyObjectPipeNull' => ['null', '!MyObject', 'MyObject|null'],
            'notMyObjectWithMyObjectAPipeMyObjectB' => ['MyObjectB', '!MyObjectA', 'MyObjectA|MyObjectB'],

            'myObjectWithMyObjectPipeBool' => ['MyObject', 'MyObject', 'MyObject|bool'],
            'myObjectWithMyObjectAPipeMyObjectB' => ['MyObjectA', 'MyObjectA', 'MyObjectA|MyObjectB'],

            'array' => ['array<array-key, mixed>', 'array', 'array|null'],

            '2dArray' => ['array<array-key, array<array-key, string>>', 'array', 'array<array<string>>|null'],

            'numeric' => ['numeric-string', 'numeric', 'string'],

            'nullableClassString' => ['null', 'falsy', '?class-string'],
            'mixedOrNullNotFalsy' => ['non-empty-mixed', '!falsy', 'mixed|null'],
            'mixedOrNullFalsy' => ['empty-mixed|null', 'falsy', 'mixed|null'],
            'nullableClassStringFalsy' => ['null', 'falsy', 'class-string<A>|null'],
            'nullableClassStringEqualsNull' => ['null', '=null', 'class-string<A>|null'],
            'nullableClassStringTruthy' => ['class-string<A>', '!falsy', 'class-string<A>|null'],
            'iterableToArray' => ['array<int, int>', 'array', 'iterable<int, int>'],
            'iterableToTraversable' => ['Traversable<int, int>', 'Traversable', 'iterable<int, int>'],
            'callableToCallableArray' => ['callable-array{0: class-string|object, 1: string}', 'array', 'callable'],
            'SmallKeyedArrayAndCallable' => ['array{test: string}', 'array{test: string}', 'callable'],
            'BigKeyedArrayAndCallable' => ['array{foo: string, test: string, thing: string}', 'array{foo: string, test: string, thing: string}', 'callable'],
            'callableOrArrayToCallableArray' => ['array<array-key, mixed>', 'array', 'callable|array'],
            'traversableToIntersection' => ['Countable&Traversable', 'Traversable', 'Countable'],
            'iterableWithoutParamsToTraversableWithoutParams' => ['Traversable', '!array', 'iterable'],
            'iterableWithParamsToTraversableWithParams' => ['Traversable<int, string>', '!array', 'iterable<int, string>'],
            'iterableAndObject' => ['Traversable<int, string>', 'object', 'iterable<int, string>'],
            'iterableAndNotObject' => ['array<int, string>', '!object', 'iterable<int, string>'],
            'boolNotEmptyIsTrue' => ['true', '!empty', 'bool'],
        ];
    }

    /**
     * @return array<string,array{string,string}>
     */
    public function providerTestTypeIsContainedBy(): array
    {
        return [
            'arrayContainsWithArrayOfStrings' => ['array<string>', 'array'],
            'arrayContainsWithArrayOfExceptions' => ['array<Exception>', 'array'],
            'arrayOfIterable' => ['array', 'iterable'],
            'arrayOfIterableWithType' => ['array<A>', 'iterable<A>'],
            'arrayOfIterableWithSubclass' => ['array<B>', 'iterable<A>'],
            'arrayOfSubclassOfParent' => ['array<B>', 'array<A>'],
            'subclassOfParent' => ['B', 'A'],
            'unionContainsWithstring' => ['string', 'string|false'],
            'unionContainsWithFalse' => ['false', 'string|false'],
            'objectLikeTypeWithPossiblyUndefinedToGeneric' => [
                'array{0: array{a: string}, 1: array{c: string, e: string}}',
                'array<int, array<string, string>>',
            ],
            'objectLikeTypeWithPossiblyUndefinedToEmpty' => [
                'array<empty, empty>',
                'array{a?: string, b?: string}',
            ],
            'literalNumericStringInt' => [
                '"0"',
                'numeric',
            ],
            'literalNumericString' => [
                '"10.03"',
                'numeric',
            ],
        ];
    }
}
