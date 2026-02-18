<?php

declare(strict_types=1);

namespace Psalm\Tests\TypeReconciliation;

use Countable;
use Override;
use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Internal\Type\AssertionReconciler;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Storage\Assertion;
use Psalm\Storage\Assertion\Any;
use Psalm\Storage\Assertion\Falsy;
use Psalm\Storage\Assertion\IsIdentical;
use Psalm\Storage\Assertion\IsLooselyEqual;
use Psalm\Storage\Assertion\IsNotIdentical;
use Psalm\Storage\Assertion\IsNotType;
use Psalm\Storage\Assertion\IsType;
use Psalm\Storage\Assertion\NonEmpty;
use Psalm\Storage\Assertion\Truthy;
use Psalm\Tests\TestCase;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Union;

final class ReconcilerTest extends TestCase
{
    protected FileAnalyzer $file_analyzer;

    protected StatementsAnalyzer $statements_analyzer;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->file_analyzer = new FileAnalyzer($this->project_analyzer, 'somefile.php', 'somefile.php');
        $this->file_analyzer->context = new Context();
        $this->statements_analyzer = new StatementsAnalyzer(
            $this->file_analyzer,
            new NodeDataProvider(),
            false,
        );

        $this->addFile('newfile.php', '
            <?php
            class SomeClass {}
            class SomeChildClass extends SomeClass {}
            class A {}
            class B {}
            interface SomeInterface {}
        ');
        $this->project_analyzer->getCodebase()->queueClassLikeForScanning(Countable::class);
        $this->project_analyzer->getCodebase()->scanFiles();
    }

    /**
     * @dataProvider providerTestReconciliation
     */
    public function testReconciliation(string $expected_type, Assertion $assertion, string $original_type): void
    {
        $reconciled = AssertionReconciler::reconcile(
            $assertion,
            Type::parseString($original_type),
            null,
            $this->statements_analyzer,
            false,
            [],
        );

        $this->assertSame(
            $expected_type,
            $reconciled->getId(),
        );

        $this->assertContainsOnlyInstancesOf('Psalm\Type\Atomic', $reconciled->getAtomicTypes());
    }

    /**
     * @dataProvider providerTestTypeIsContainedBy
     */
    public function testTypeIsContainedBy(string $input, string $container): void
    {
        $this->assertTrue(
            UnionTypeComparator::isContainedBy(
                $this->project_analyzer->getCodebase(),
                Type::parseString($input),
                Type::parseString($container),
            ),
        );
    }

    /**
     * @return array<string,array{string,Assertion,string}>
     */
    public function providerTestReconciliation(): array
    {
        return [
            'notNullWithObject' => ['SomeClass', new IsNotType(new TNull()), 'SomeClass'],
            'notNullWithObjectPipeNull' => ['SomeClass', new IsNotType(new TNull()), 'SomeClass|null'],
            'notNullWithSomeClassPipeFalse' => ['SomeClass|false', new IsNotType(new TNull()), 'SomeClass|false'],
            'notNullWithMixed' => ['mixed', new IsNotType(new TNull()), 'mixed'],

            'notEmptyWithSomeClass' => ['SomeClass', new Truthy(), 'SomeClass'],
            'notEmptyWithSomeClassPipeNull' => ['SomeClass', new Truthy(), 'SomeClass|null'],
            'notEmptyWithSomeClassPipeFalse' => ['SomeClass', new Truthy(), 'SomeClass|false'],
            'notEmptyWithMixed' => ['non-empty-mixed', new Truthy(), 'mixed'],
            // @todo in the future this should also work
            //'notEmptyWithSomeClassFalseTrue' => ['SomeClass|true', '!falsy', 'SomeClass|bool'],

            'nullWithSomeClassPipeNull' => ['null', new IsType(new TNull()), 'SomeClass|null'],
            'nullWithMixed' => ['null', new IsType(new TNull()), 'mixed'],

            'falsyWithSomeClass' => ['never', new Falsy(), 'SomeClass'],
            'falsyWithSomeClassPipeFalse' => ['false', new Falsy(), 'SomeClass|false'],
            'falsyWithSomeClassPipeBool' => ['false', new Falsy(), 'SomeClass|bool'],
            'falsyWithMixed' => ['empty-mixed', new Falsy(), 'mixed'],
            'falsyWithBool' => ['false', new Falsy(), 'bool'],
            'falsyWithStringOrNull' => ["''|'0'|null", new Falsy(), 'string|null'],
            'falsyWithScalarOrNull' => ['empty-scalar', new Falsy(), 'scalar'],
            'trueWithBool' => ['true', new IsType(new TTrue()), 'bool'],
            'falseWithBool' => ['false', new IsType(new TFalse()), 'bool'],
            'notTrueWithBool' => ['false', new IsNotIdentical(new TTrue()), 'bool'],
            'notFalseWithBool' => ['true', new IsNotIdentical(new TFalse()), 'bool'],

            'notSomeClassWithSomeClassPipeBool' => ['bool', new IsNotType(new TNamedObject('SomeClass')), 'SomeClass|bool'],
            'notSomeClassWithSomeClassPipeNull' => ['null', new IsNotType(new TNamedObject('SomeClass')), 'SomeClass|null'],
            'notSomeClassWithAPipeB' => ['B', new IsNotType(new TNamedObject('A')), 'A|B'],
            'notDateTimeWithDateTimeInterface' => ['DateTimeImmutable', new IsNotType(new TNamedObject('DateTime')), 'DateTimeInterface'],
            'notDateTimeImmutableWithDateTimeInterface' => ['DateTime', new IsNotType(new TNamedObject('DateTimeImmutable')), 'DateTimeInterface'],

            'myObjectWithSomeClassPipeBool' => ['SomeClass', new IsType(new TNamedObject('SomeClass')), 'SomeClass|bool'],
            'myObjectWithAPipeB' => ['A', new IsType(new TNamedObject('A')), 'A|B'],

            'array' => ['array<array-key, mixed>', new IsType(new TArray([Type::getArrayKey(), Type::getMixed()])), 'array|null'],

            '2dArray' => ['array<array-key, array<array-key, string>>', new IsType(new TArray([Type::getArrayKey(), Type::getMixed()])), 'array<array<string>>|null'],

            'numeric' => ['numeric-string', new IsType(new TNumeric()), 'string'],

            'nullableClassString' => ['null', new Falsy(), '?class-string'],
            'mixedOrNullNotFalsy' => ['non-empty-mixed', new Truthy(), 'mixed|null'],
            'mixedOrNullFalsy' => ['empty-mixed|null', new Falsy(), 'mixed|null'],
            'nullableClassStringFalsy' => ['null', new Falsy(), 'class-string<SomeClass>|null'],
            'nullableClassStringEqualsNull' => ['null', new IsIdentical(new TNull()), 'class-string<SomeClass>|null'],
            'nullableClassStringTruthy' => ['class-string<SomeClass>', new Truthy(), 'class-string<SomeClass>|null'],
            'iterableToArray' => ['array<int, int>', new IsType(new TArray([Type::getArrayKey(), Type::getMixed()])), 'iterable<int, int>'],
            'iterableToTraversable' => ['Traversable<int, int>', new IsType(new TNamedObject('Traversable')), 'iterable<int, int>'],
            'callableToCallableArray' => ['callable-array{0: class-string|object, 1: non-empty-string}', new IsType(new TArray([Type::getArrayKey(), Type::getMixed()])), 'callable'],
            'SmallKeyedArrayAndCallable' => ['array{test: string}', new IsType(TKeyedArray::make(['test' => Type::getString()])), 'callable'],
            'BigKeyedArrayAndCallable' => ['array{foo: string, test: string, thing: string}', new IsType(TKeyedArray::make(['foo' => Type::getString(), 'test' => Type::getString(), 'thing' => Type::getString()])), 'callable'],
            'callableOrArrayToCallableArray' => ['array<array-key, mixed>', new IsType(new TArray([Type::getArrayKey(), Type::getMixed()])), 'callable|array'],
            'traversableToIntersection' => ['Countable&Traversable', new IsType(new TNamedObject('Traversable')), 'Countable'],
            'iterableWithoutParamsToTraversableWithoutParams' => ['Traversable', new IsNotType(new TArray([Type::getArrayKey(), Type::getMixed()])), 'iterable'],
            'iterableWithParamsToTraversableWithParams' => ['Traversable<int, string>', new IsNotType(new TArray([Type::getArrayKey(), Type::getMixed()])), 'iterable<int, string>'],
            'iterableAndObject' => ['Traversable<int, string>', new IsType(new TObject()), 'iterable<int, string>'],
            'iterableAndNotObject' => ['array<int, string>', new IsNotType(new TObject()), 'iterable<int, string>'],
            'boolNotEmptyIsTrue' => ['true', new NonEmpty(), 'bool'],
            'interfaceAssertionOnClassInterfaceUnion' => ['SomeInterface|SomeInterface&SomeClass', new IsType(new TNamedObject('SomeInterface')), 'SomeClass|SomeInterface'],
            'classAssertionOnClassInterfaceUnion' => ['SomeClass|SomeClass&SomeInterface', new IsType(new TNamedObject('SomeClass')), 'SomeClass|SomeInterface'],
            'stringToNumericStringWithInt' => ['numeric-string', new IsLooselyEqual(new TInt()), 'string'],
            'stringToNumericStringWithFloat' => ['numeric-string', new IsLooselyEqual(new TFloat()), 'string'],
            'filterKeyedArrayWithIterable' => ['array{some: string}',new IsType(new TIterable([Type::getMixed(), Type::getString()])), 'array{some: mixed}'],
            'SimpleXMLElementNotAlwaysTruthy' => ['SimpleXMLElement', new Truthy(), 'SimpleXMLElement'],
            'SimpleXMLElementNotAlwaysTruthy2' => ['SimpleXMLElement', new Falsy(), 'SimpleXMLElement'],
            'SimpleXMLIteratorNotAlwaysTruthy' => ['SimpleXMLIterator', new Truthy(), 'SimpleXMLIterator'],
            'SimpleXMLIteratorNotAlwaysTruthy2' => ['SimpleXMLIterator', new Falsy(), 'SimpleXMLIterator'],
            'stringWithAny' => ['string', new Any(), 'string'],
            'IsNotAClassReconciliation' => ['int', new Assertion\IsNotAClass(new TNamedObject('IDObject'), true), 'int|IDObject'],
            'nonEmptyArray' => ['non-empty-array<array-key, mixed>', new IsType(Atomic::create('non-empty-array')), 'array'],
            'nonEmptyList' => ['non-empty-list<mixed>', new IsType(Atomic::create('non-empty-list')), 'array'],
            'ListOfInts' => ['list<int>', new IsType(new TIterable([Type::getMixed(), Type::getInt()])), 'list<mixed>'],
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
                'array<never, never>',
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
    public function testReconciliationOfClassConstantInAssertions(Assertion $assertion, string $expected_type): void
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
            ',
        );
        $this->project_analyzer->getCodebase()->scanFiles();

        $reconciled = AssertionReconciler::reconcile(
            $assertion,
            new Union([
                new TLiteralString(''),
            ]),
            null,
            $this->statements_analyzer,
            false,
            [],
        );

        $this->assertSame(
            $expected_type,
            $reconciled->getId(),
        );
    }

    /**
     * @return array<non-empty-string,array{Assertion,string}>
     */
    public function constantAssertions(): array
    {
        return [
            'constant-with-prefix' => [
                new IsType(new TClassConstant('ReconciliationTest\\Foo', 'PREFIX_*')),
                "'bar'|'baz'",
            ],
            'single-class-constant' => [
                new IsType(new TClassConstant('ReconciliationTest\\Foo', 'PREFIX_BAR')),
                "'bar'",
            ],
            'referencing-another-class-constant' => [
                new IsType(new TClassConstant('ReconciliationTest\\Foo', 'PREFIX_QOO')),
                "'bar'",
            ],
            'referencing-all-class-constants' => [
                new IsType(new TClassConstant('ReconciliationTest\\Foo', '*')),
                "'bar'|'baz'",
            ],
            'referencing-some-class-constants-with-wildcard' => [
                new IsType(new TClassConstant('ReconciliationTest\\Foo', 'PREFIX_B*')),
                "'bar'|'baz'",
            ],
        ];
    }
}
