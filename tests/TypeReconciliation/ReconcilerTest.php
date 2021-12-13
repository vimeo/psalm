<?php
namespace Psalm\Tests\TypeReconciliation;

use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Internal\Type\AssertionReconciler;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Tests\TestCase;
use Psalm\Type;
use Psalm\Type\Atomic\TLiteralString;

class ReconcilerTest extends TestCase
{
    /** @var FileAnalyzer */
    protected $file_analyzer;

    /** @var StatementsAnalyzer */
    protected $statements_analyzer;

    public function setUp(): void
    {
        parent::setUp();

        $this->file_analyzer = new FileAnalyzer($this->project_analyzer, 'somefile.php', 'somefile.php');
        $this->file_analyzer->context = new Context();
        $this->statements_analyzer = new StatementsAnalyzer(
            $this->file_analyzer,
            new NodeDataProvider()
        );

        $this->addFile('newfile.php', '
            <?php
            class SomeClass {}
            class SomeChildClass extends SomeClass {}
            class A {}
            class B {}
            interface SomeInterface {}
        ');
        $this->project_analyzer->getCodebase()->scanFiles();
    }

    /**
     * @dataProvider providerTestReconcilation
     */
    public function testReconcilation(string $expected_type, string $assertion, string $original_type): void
    {
        $reconciled = AssertionReconciler::reconcile(
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
            'notNullWithObject' => ['SomeClass', '!null', 'SomeClass'],
            'notNullWithObjectPipeNull' => ['SomeClass', '!null', 'SomeClass|null'],
            'notNullWithSomeClassPipeFalse' => ['SomeClass|false', '!null', 'SomeClass|false'],
            'notNullWithMixed' => ['mixed', '!null', 'mixed'],

            'notEmptyWithSomeClass' => ['SomeClass', '!falsy', 'SomeClass'],
            'notEmptyWithSomeClassPipeNull' => ['SomeClass', '!falsy', 'SomeClass|null'],
            'notEmptyWithSomeClassPipeFalse' => ['SomeClass', '!falsy', 'SomeClass|false'],
            'notEmptyWithMixed' => ['non-empty-mixed', '!falsy', 'mixed'],
            // @todo in the future this should also work
            //'notEmptyWithSomeClassFalseTrue' => ['SomeClass|true', '!falsy', 'SomeClass|bool'],

            'nullWithSomeClassPipeNull' => ['null', 'null', 'SomeClass|null'],
            'nullWithMixed' => ['null', 'null', 'mixed'],

            'falsyWithSomeClass' => ['empty', 'falsy', 'SomeClass'],
            'falsyWithSomeClassPipeFalse' => ['false', 'falsy', 'SomeClass|false'],
            'falsyWithSomeClassPipeBool' => ['false', 'falsy', 'SomeClass|bool'],
            'falsyWithMixed' => ['empty-mixed', 'falsy', 'mixed'],
            'falsyWithBool' => ['false', 'falsy', 'bool'],
            'falsyWithStringOrNull' => ['""|"0"|null', 'falsy', 'string|null'],
            'falsyWithScalarOrNull' => ['empty-scalar', 'falsy', 'scalar'],

            'notSomeClassWithSomeClassPipeBool' => ['bool', '!SomeClass', 'SomeClass|bool'],
            'notSomeClassWithSomeClassPipeNull' => ['null', '!SomeClass', 'SomeClass|null'],
            'notSomeClassWithAPipeB' => ['B', '!A', 'A|B'],
            'notDateTimeWithDateTimeInterface' => ['DateTimeImmutable', '!DateTime', 'DateTimeInterface'],
            'notDateTimeImmutableWithDateTimeInterface' => ['DateTime', '!DateTimeImmutable', 'DateTimeInterface'],

            'myObjectWithSomeClassPipeBool' => ['SomeClass', 'SomeClass', 'SomeClass|bool'],
            'myObjectWithAPipeB' => ['A', 'A', 'A|B'],

            'array' => ['array<array-key, mixed>', 'array', 'array|null'],

            '2dArray' => ['array<array-key, array<array-key, string>>', 'array', 'array<array<string>>|null'],

            'numeric' => ['numeric-string', 'numeric', 'string'],

            'nullableClassString' => ['null', 'falsy', '?class-string'],
            'mixedOrNullNotFalsy' => ['non-empty-mixed', '!falsy', 'mixed|null'],
            'mixedOrNullFalsy' => ['empty-mixed|null', 'falsy', 'mixed|null'],
            'nullableClassStringFalsy' => ['null', 'falsy', 'class-string<SomeClass>|null'],
            'nullableClassStringEqualsNull' => ['null', '=null', 'class-string<SomeClass>|null'],
            'nullableClassStringTruthy' => ['class-string<SomeClass>', '!falsy', 'class-string<SomeClass>|null'],
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
            'interfaceAssertionOnClassInterfaceUnion' => ['SomeInterface|SomeInterface&SomeClass', 'SomeInterface', 'SomeClass|SomeInterface'],
            'stringToNumericStringWithInt' => ['numeric-string', '~int', 'string'],
            'stringToNumericStringWithFloat' => ['numeric-string', '~float', 'string'],
            'filterKeyedArrayWithIterable' => ['array{some: string}', 'iterable<string>', 'array{some: mixed}'],
            'SimpleXMLElementNotAlwaysTruthy' => ['SimpleXMLElement', '!falsy', 'SimpleXMLElement'],
            'SimpleXMLElementNotAlwaysTruthy2' => ['SimpleXMLElement', 'falsy', 'SimpleXMLElement'],
            'SimpleXMLIteratorNotAlwaysTruthy' => ['SimpleXMLIterator', '!falsy', 'SimpleXMLIterator'],
            'SimpleXMLIteratorNotAlwaysTruthy2' => ['SimpleXMLIterator', 'falsy', 'SimpleXMLIterator'],
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
            'arrayOfIterableWithType' => ['array<SomeClass>', 'iterable<SomeClass>'],
            'arrayOfIterableWithSubclass' => ['array<SomeChildClass>', 'iterable<SomeClass>'],
            'arrayOfSubclassOfParent' => ['array<SomeChildClass>', 'array<SomeClass>'],
            'subclassOfParent' => ['SomeChildClass', 'SomeClass'],
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

    /**
     * @dataProvider constantAssertions
     */
    public function testReconciliationOfClassConstantInAssertions(string $assertion, string $expected_type): void
    {
        $this->addFile(
            'psalm-assert.php',
            '
            <?php
            namespace ReconciliationTest;
            class Foo
            {
                const PREFIX_BAR = \'bar\';
                const PREFIX_BAZ = \'baz\';
                const PREFIX_QOO = Foo::PREFIX_BAR;
            }
            '
        );
        $this->project_analyzer->getCodebase()->scanFiles();

        $reconciled = AssertionReconciler::reconcile(
            $assertion,
            new Type\Union([
                new TLiteralString(''),
            ]),
            null,
            $this->statements_analyzer,
            false,
            []
        );

        $this->assertSame(
            $expected_type,
            $reconciled->getId()
        );
    }

    /**
     * @return array<non-empty-string,array{non-empty-string,string}>
     */
    public function constantAssertions(): array
    {
        return [
            'constant-with-prefix' => [
                'class-constant(ReconciliationTest\\Foo::PREFIX_*)',
                '"bar"|"baz"',
            ],
            'single-class-constant' => [
                'class-constant(ReconciliationTest\\Foo::PREFIX_BAR)',
                '"bar"',
            ],
            'referencing-another-class-constant' => [
                'class-constant(ReconciliationTest\\Foo::PREFIX_QOO)',
                '"bar"',
            ],
            'referencing-all-class-constants' => [
                'class-constant(ReconciliationTest\\Foo::*)',
                '"bar"|"baz"',
            ],
            'referencing-some-class-constants-with-wildcard' => [
                'class-constant(ReconciliationTest\\Foo::PREFIX_B*)',
                '"bar"|"baz"',
            ],
        ];
    }
}
