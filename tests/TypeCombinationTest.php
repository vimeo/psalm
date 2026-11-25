<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TTemplateParamClass;

use function array_reverse;
use function ini_get;
use function ini_set;

final class TypeCombinationTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    /**
     * @param non-empty-list<string> $types
     */
    #[DataProvider('providerTestValidTypeCombination')]
    public function testValidTypeCombination(string $expected, array $types): void
    {
        $converted_types = [];

        foreach ($types as $type) {
            $converted_type = self::getAtomic($type);
            /** @psalm-suppress InaccessibleProperty */
            $converted_type->from_docblock = true;
            $converted_types[] = $converted_type;
        }

        $this->assertSame(
            $expected,
            TypeCombiner::combine($converted_types)->getId(),
        );

        $this->assertSame(
            $expected,
            TypeCombiner::combine(array_reverse($converted_types))->getId(),
        );
    }

    public function testTooManyLiteralStringsAreCombinedIntoTheStringTypeTheyAllAre(): void
    {
        $words = [];
        for ($i = 0; $i < 600; $i++) {
            $words[] = new TLiteralString('w' . $i);
        }

        $this->assertSame('non-falsy-string', TypeCombiner::combine($words)->getId());
        $this->assertSame('non-empty-string', TypeCombiner::combine([new TLiteralString('0'), ...$words])->getId());
        $this->assertSame('non-empty-string', TypeCombiner::combine([...$words, new TLiteralString('0')])->getId());
        $this->assertSame('string', TypeCombiner::combine([new TLiteralString(''), ...$words])->getId());
        $this->assertSame('string', TypeCombiner::combine([...$words, new TLiteralString('')])->getId());
    }

    public function testStaticIsKeptOnlyWhenEveryCombinedObjectIsStatic(): void
    {
        $static = new TNamedObject('Exception', true);
        $plain = new TNamedObject('Exception');

        $this->assertSame('Exception', TypeCombiner::combine([$static, $plain])->getId());
        $this->assertSame('Exception', TypeCombiner::combine([$plain, $static])->getId());
        $this->assertSame('Exception', TypeCombiner::combine([$static, $plain, $static])->getId());
        $this->assertSame('Exception&static', TypeCombiner::combine([$static, $static])->getId());
    }

    public function testFloatsThatPrintAlikeAreKeptApart(): void
    {
        $this->assertSame(
            'float(0.3)|float(0.30000000000000004)',
            TypeCombiner::combine([new TLiteralFloat(0.3), new TLiteralFloat(0.1 + 0.2)])->getId(),
        );
    }

    public function testFloatsAreKeptApartWithALowSerializePrecision(): void
    {
        $precision = ini_get('precision');
        $serialize_precision = ini_get('serialize_precision');
        ini_set('precision', '14');
        ini_set('serialize_precision', '3');

        try {
            $this->assertSame(
                'float(1.234567890123456)|float(1.234567890123556)',
                TypeCombiner::combine([
                    new TLiteralFloat(1.234_567_890_123_456),
                    new TLiteralFloat(1.234_567_890_123_556),
                ])->getId(),
            );
        } finally {
            ini_set('precision', $precision);
            ini_set('serialize_precision', (string) $serialize_precision);
        }
    }

    public function testConditionalsOnTheSameTemplateAreKeptApart(): void
    {
        $int_conditional = new TConditional(
            'T',
            'fn-f',
            Type::getMixed(),
            Type::getInt(),
            Type::getString(),
            Type::getBool(),
        );
        $string_conditional = new TConditional(
            'T',
            'fn-f',
            Type::getMixed(),
            Type::getString(),
            Type::getInt(),
            Type::getFloat(),
        );

        $this->assertSame(
            '(T is int ? string : bool)|(T is string ? int : float)',
            TypeCombiner::combine([$int_conditional, $string_conditional])->getId(),
        );
    }

    public function testClassStringsOfTemplatesOfDifferentScopesAreKeptApart(): void
    {
        $function_template = new TTemplateParamClass('T', 'Exception', new TNamedObject('Exception'), 'fn-f');
        $class_template = new TTemplateParamClass('T', 'Exception', new TNamedObject('Exception'), 'Foo');

        $this->assertSame(
            '(class-string<T:Foo as Exception>)|(class-string<T:fn-f as Exception>)',
            TypeCombiner::combine([$function_template, $class_template])->getId(),
        );
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'multipleValuedArray' => [
                'code' => '<?php
                    class A {}
                    class B {}
                    $var = [];
                    $var[] = new A();
                    $var[] = new B();',
            ],
            'preventLiteralAndClassString' => [
                'code' => '<?php
                    /**
                     * @param "array"|class-string $type_name
                     */
                    function foo(string $type_name) : bool {
                        return $type_name === "array";
                    }',
            ],
            'NeverTwice' => [
                'code' => '<?php
                    /** @return no-return */
                    function other() {
                        throw new Exception();
                    }

                    rand(0,1) ? die() : other();',
            ],
            'ArrayAndTraversableNotIterable' => [
                'code' => '<?php declare(strict_types=1);

                    /** @param mixed $identifier */
                    function isNullIdentifier($identifier): bool
                    {
                        if ($identifier instanceof \Traversable || is_array($identifier)) {
                            expectsTraversableOrArray($identifier);
                        }

                        return false;
                    }

                    /** @param Traversable|array<array-key, mixed> $_a */
                    function expectsTraversableOrArray($_a): void
                    {

                    }
                    ',
            ],
            'emptyStringNumericStringDontCombine' => [
                'code' => '<?php
                    /**
                     * @param numeric-string $arg
                     * @return void
                     */
                    function takesNumeric($arg) {}

                    $b = rand(0, 10);
                    $a = $b < 5 ? "" : (string) $b;
                    if ($a !== "") {
                        takesNumeric($a);
                    }

                    /** @var ""|numeric-string $c */
                    if (is_numeric($c)) {
                        takesNumeric($c);
                    }',
            ],
            'emptyStringNumericStringDontCombineNegation' => [
                'code' => '<?php
                    /**
                     * @param ""|"hello" $arg
                     * @return void
                     */
                    function takesLiteralString($arg) {}

                    /** @var ""|numeric-string $c */
                    if (!is_numeric($c)) {
                        takesLiteralString($c);
                    }',
            ],
            'tooLongLiteralShouldBeNonFalsyString' => [
                'code' => '<?php
                    $x = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";',
                'assertions' => [
                    '$x===' => 'non-falsy-string',
                ],
            ],
            'loopNonFalsyWithZeroShouldBeNonEmpty' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress InvalidReturnType
                     * @return string[]
                     */
                    function getStringArray() {}

                    $x = array();
                    foreach (getStringArray() as $id) {
                        $x[] = "0";
                        $x[] = "some_" . $id;
                    }',
                'assertions' => [
                    '$x===' => "list{0?: '0', ...<non-empty-string>}",
                ],
            ],
            'loopNonLowercaseLiteralWithNonEmptyLowercaseShouldBeNonEmptyAndNotLowercase' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress InvalidReturnType
                     * @return int[]
                     */
                    function getIntArray() {}

                    $x = array();
                    foreach (getIntArray() as $id) {
                        $x[] = "TEXT";
                        $x[] = "some_" . $id;
                    }',
                'assertions' => [
                    '$x===' => "list{0?: 'TEXT', ...<non-empty-string>}",
                ],
            ],
            'nonemptyliteralstring' => [
                'code' => '<?php
                    /** @var non-empty-literal-string */
                    $instance = "test";
                    $provider = "test";
                    $key = "";
                    if (random_int(0, 1)) {
                        $key = "{$instance}_{$provider}";
                        /** @psalm-check-type-exact $key=non-empty-literal-string */;
                    }
                    /** @psalm-check-type-exact $key=literal-string */;

                    $_ = $key !== "" ? "test{$key}]" : "test";
                ',
            ],
        ];
    }

    /**
     * @return array<string,array{string,non-empty-list<string>}>
     * @psalm-pure
     */
    public static function providerTestValidTypeCombination(): iterable
    {
        return [
            'unsealedArrayKeyDeclaredInOneAndMissingInTheOtherTakesTheFallback' => [
                'non-empty-array<array-key, mixed>',
                [
                    'array{age: int, ...<array-key, mixed>}',
                    'array{name: string, ...<array-key, mixed>}',
                ],
            ],
            'unsealedArrayKeyDeclaredInOneAndOptionalInTheOtherKeepsItsType' => [
                'array{age?: int, ...<array-key, mixed>}',
                [
                    'array{age: int, ...<array-key, mixed>}',
                    'array{age?: int, ...<array-key, mixed>}',
                ],
            ],
            'unsealedArrayKeyDeclaredOptionalInBothKeepsItsType' => [
                'array{age?: int, name: string, ...<array-key, mixed>}',
                [
                    'array{age?: int, name: string, ...<array-key, mixed>}',
                    'array{age?: int, name: \'x\', ...<array-key, mixed>}',
                ],
            ],
            'objectShapesWithAnExtraPropertyKeepOnlyTheCommonOnes' => [
                'object{a:string}',
                [
                    'object{a: string}',
                    'object{a: string, b: int}',
                ],
            ],
            'objectShapesKeepAnOptionalPropertyBothDeclare' => [
                'object{a:string, b?:int}',
                [
                    'object{a: string, b?: int}',
                    'object{a: string, b: int}',
                ],
            ],
            'objectShapesWithDisjointPropertiesShareNone' => [
                'object{}',
                [
                    'object{a: string}',
                    'object{b: int}',
                ],
            ],
            'objectShapesWithTheSameProperty' => [
                'object{a:int|string}',
                [
                    'object{a: string}',
                    'object{a: int}',
                ],
            ],
            'objectShapesKeepTheirCommonIntersection' => [
                'object{a:string}&Iterator',
                [
                    'object{a: string}&Iterator',
                    'object{a: string, b: int}&Iterator&Countable',
                ],
            ],
            'objectAbsorbsObjectShapes' => [
                'object',
                [
                    'object',
                    'object{a: string}',
                ],
            ],
            'objectWithPropertiesKeepsNamedObjects' => [
                'Error|object{foo:string}',
                [
                    'Error',
                    'object{foo: string}',
                ],
            ],
            'objectWithPropertiesAfterNamedObject' => [
                'Error|object{foo:string}',
                [
                    'object{foo: string}',
                    'Error',
                ],
            ],
            'objectShapesWithOptionalProperty' => [
                'object{bar?:string}',
                [
                    'object{bar: string}',
                    'object{bar?: string}',
                ],
            ],
            'objectShapeInObject' => [
                'object',
                [
                    'object{a: int}',
                    'object',
                ],
            ],
            'complexArrayFallback1' => [
                'array{other_references: list<Psalm\Internal\Analyzer\DataFlowNodeData>|null, taint_trace: list<array<array-key, mixed>>|null, ...<string, mixed>}',
                [
                    'array{other_references: list<Psalm\Internal\Analyzer\DataFlowNodeData>|null, taint_trace: null}&array<string, mixed>',
                    'array{other_references: list<Psalm\Internal\Analyzer\DataFlowNodeData>|null, taint_trace: list<array<array-key, mixed>>}&array<string, mixed>',
                ],
            ],
            'optionalKeyOfAnUnsealedShapeKeepsItsTypeWhenCombinedWithTheSameKeyRequired' => [
                'array{id: string, name?: string, ...<array-key, mixed>}',
                [
                    'array{id: string, name: string, ...<array-key, mixed>}',
                    'array{id: string, name?: string, ...<array-key, mixed>}',
                ],
            ],
            'keyOfAnUnsealedShapeIsTheFallbackWhenAnotherShapeLacksItAndItsFallbackHoldsItsType' => [
                'array{id: string, ...<string, int|string>}',
                [
                    'array{id: string, name: string, ...<string, string>}',
                    'array{id: string, ...<string, int>}',
                ],
            ],
            'keyOfAnUnsealedShapeIsOptionalWhenAnotherShapeLacksItAndItsFallbackHoldsAnotherType' => [
                'array{id: string, name?: int|string, ...<string, int>}',
                [
                    'array{id: string, name: string, ...<string, int>}',
                    'array{id: string, ...<string, int>}',
                ],
            ],
            'keyOfAnUnsealedShapeIsOptionalWhenAnotherShapeLacksItAndItsFallbackCannotHoldIt' => [
                'array{5?: int, id: string, ...<string, int>}',
                [
                    'array{id: string, 5: int, ...<string, int>}',
                    'array{id: string, ...<string, int>}',
                ],
            ],
            'keyOfAnUnsealedShapeIsTheFallbackWhenAnotherShapeLacksItAndAThirdDeclaresIt' => [
                'array{id: string, ...<string, int>}',
                [
                    'array{id: string, name: int, ...<string, int>}',
                    'array{id: string, ...<string, int>}',
                    'array{id: string, name: 5, ...<string, int>}',
                ],
            ],
            'arrayKeyOfAnUnsealedShapeIsOptionalWhenAnotherShapeLacksItAndItsFallbackHoldsAnotherArray' => [
                'array{id: string, name?: array{0?: string, x?: 1, ...<int<0, max>, string>}, ...<string, list<string>>}',
                [
                    'array{id: string, name: array{x: 1}, ...<string, list<string>>}',
                    'array{id: string, ...<string, list<string>>}',
                ],
            ],
            'keyOfAnUnsealedShapeIsOptionalWhenTheShapeLackingItHasANarrowerFallback' => [
                'array{id: string, name?: int, ...<string, int|string>}',
                [
                    'array{id: string, ...<string, int>}',
                    'array{id: string, name: int, ...<string, string>}',
                ],
            ],
            'keyOfAnUnsealedShapeIsOptionalWhenTheShapeLackingItComesLastWithANarrowerFallback' => [
                'array{id: string, name?: int, ...<string, int|string>}',
                [
                    'array{id: string, name: int, ...<string, string>}',
                    'array{id: string, ...<string, int>}',
                ],
            ],
            'keyOfAnUnsealedShapeIsOptionalWhenTheShapeLackingItHasANarrowerFallbackThanMixed' => [
                'array{id: string, name?: int, ...<string, int|mixed>}',
                [
                    'array{id: string, ...<string, int>}',
                    'array{id: string, name: int, ...<string, mixed>}',
                ],
            ],
            'arrayKeyOfAnUnsealedShapeIsOptionalWhenAnotherShapeLacksItAndItsFallbackHoldsOtherElements' => [
                'array{id: string, k?: array<string, int|string>, ...<string, array<string, int>>}',
                [
                    'array{id: string, k: array<string, string>, ...<string, array<string, int>>}',
                    'array{id: string, ...<string, array<string, int>>}',
                ],
            ],
            'emptyKeyOfAnUnsealedShapeIsOptionalWhenAnotherShapeLacksItAndItsFallbackKeyIsNonEmpty' => [
                'array{\'\'?: int, id: string, ...<non-empty-string, int>}',
                [
                    'array{\'\': int, id: string, ...<non-empty-string, int>}',
                    'array{id: string, ...<non-empty-string, int>}',
                ],
            ],
            'keyOfAnUnsealedShapeIsTheFallbackWhenAnotherShapeLacksItAndItsFallbackKeyIsNonEmpty' => [
                'array{id: string, ...<non-empty-string, int>}',
                [
                    'array{id: string, name: int, ...<non-empty-string, int>}',
                    'array{id: string, ...<non-empty-string, int>}',
                ],
            ],
            'keyOfAnUnsealedShapeIsOptionalWhenAnotherShapeLacksItAndItsFallbackKeyIsARefinedString' => [
                'array{id: string, name?: int, ...<numeric-string, int>}',
                [
                    'array{id: string, name: int, ...<numeric-string, int>}',
                    'array{id: string, ...<numeric-string, int>}',
                ],
            ],
            'keyOfAnUnsealedShapeIsOptionalWhenASealedShapeLacksIt' => [
                'array{id: string, name?: int, ...<string, int>}',
                [
                    'array{id: string, name: int, ...<string, int>}',
                    'array{id: string}',
                ],
            ],
            'keyOfAnUnsealedShapeIsOptionalWhenASealedShapeLacksItBeforeUnsealedOnes' => [
                'array{id: string, name?: int, ...<string, int>}',
                [
                    'array{id: string}',
                    'array{id: string, ...<string, int>}',
                    'array{id: string, name: int, ...<string, int>}',
                ],
            ],
            'keyOfAnUnsealedShapeIsOptionalWhenASealedShapeLacksItBetweenUnsealedOnes' => [
                'array{id: string, name?: int, ...<string, int>}',
                [
                    'array{id: string, ...<string, int>}',
                    'array{id: string}',
                    'array{id: string, name: int, ...<string, int>}',
                ],
            ],
            'keyOfAnUnsealedShapeIsOptionalWhenAnotherShapeLacksItAndItsFallbackHasOtherKeys' => [
                'array{id: string, name?: int, ...<int|string, int>}',
                [
                    'array{id: string, name: int, ...<string, int>}',
                    'array{id: string, ...<int, int>}',
                ],
            ],
            'keyOfAnUnsealedShapeIsOptionalWhenAnEmptyArrayLacksIt' => [
                'array{id?: string, name?: int, ...<string, int>}',
                [
                    'array{id: string, name: int, ...<string, int>}',
                    'array{id: string, ...<string, int>}',
                    'array<never, never>',
                ],
            ],
            'keyOfASealedShapeIsOptionalWhenAnotherShapeDeclaresItOptional' => [
                'array{a?: int|string, ...<string, mixed>}',
                [
                    'array{a: int, ...<string, mixed>}',
                    'array{a?: string}',
                ],
            ],
            'complexArrayFallback2' => [
                'list{0?: 0|a, 1?: 0|a, ...<a>}',
                [
                    'list<a>',
                    'list{0, 0}',
                ],
            ],
            'intOrString' => [
                'int|string',
                [
                    'int',
                    'string',
                ],
            ],
            'mixedOrNull' => [
                'mixed|null',
                [
                    'mixed',
                    'null',
                ],
            ],
            'mixedOrNever' => [
                'mixed',
                [
                    'never',
                    'mixed',
                ],
            ],
            'mixedOrObject' => [
                'mixed|object',
                [
                    'mixed',
                    'object',
                ],
            ],
            'mixedOrEmptyArray' => [
                'array<never, never>|mixed',
                [
                    'mixed',
                    'array<never, never>',
                ],
            ],
            'falseTrueToBool' => [
                'bool',
                [
                    'false',
                    'true',
                ],
            ],
            'trueFalseToBool' => [
                'bool',
                [
                    'true',
                    'false',
                ],
            ],
            'trueBoolToBool' => [
                'bool',
                [
                    'true',
                    'bool',
                ],
            ],
            'boolTrueToBool' => [
                'bool',
                [
                    'bool',
                    'true',
                ],
            ],
            'intOrTrueOrFalseToBool' => [
                'bool|int',
                [
                    'int',
                    'false',
                    'true',
                ],
            ],
            'intOrBoolOrTrueToBool' => [
                'bool|int',
                [
                    'int',
                    'bool',
                    'true',
                ],
            ],
            'intOrTrueOrBoolToBool' => [
                'bool|int',
                [
                    'int',
                    'true',
                    'bool',
                ],
            ],
            'arrayOfIntOrString' => [
                'array<array-key, int|string>',
                [
                    'array<int>',
                    'array<string>',
                ],
            ],
            'arrayOfIntOrAlsoString' => [
                'array<array-key, int>|string',
                [
                    'array<int>',
                    'string',
                ],
            ],
            'emptyArrays' => [
                'array<never, never>',
                [
                    'array<never, never>',
                    'array<never, never>',
                ],
            ],
            'arrayStringOrEmptyArray' => [
                'array<array-key, string>',
                [
                    'array<never>',
                    'array<string>',
                ],
            ],
            'arrayMixedOrString' => [
                'array<array-key, mixed|string>',
                [
                    'array<mixed>',
                    'array<string>',
                ],
            ],
            'arrayMixedOrStringKeys' => [
                'array<array-key, string>',
                [
                    'array<int|string,string>',
                    'array<mixed,string>',
                ],
            ],
            'arrayMixedOrEmpty' => [
                'array<array-key, mixed>',
                [
                    'array<never>',
                    'array<mixed>',
                ],
            ],
            'arrayBigCombination' => [
                'array<array-key, float|int|string>',
                [
                    'array<int|float>',
                    'array<string>',
                ],
            ],
            'nonEmptyArrayTraversableStaysApart' => [
                'Traversable<int, int>|non-empty-array<int, int>',
                [
                    'non-empty-array<int, int>',
                    'Traversable<int, int>',
                ],
            ],
            'arrayTraversableToIterable' => [
                'iterable<array-key|mixed, mixed>',
                [
                    'array',
                    'Traversable',
                ],
            ],
            'arrayIterableToIterable' => [
                'iterable<mixed, mixed>',
                [
                    'array',
                    'iterable',
                ],
            ],
            'iterableArrayToIterable' => [
                'iterable<mixed, mixed>',
                [
                    'iterable',
                    'array',
                ],
            ],
            'traversableIterableToIterable' => [
                'iterable<mixed, mixed>',
                [
                    'Traversable',
                    'iterable',
                ],
            ],
            'iterableTraversableToIterable' => [
                'iterable<mixed, mixed>',
                [
                    'iterable',
                    'Traversable',
                ],
            ],
            'arrayTraversableToIterableWithParams' => [
                'iterable<int, bool|string>',
                [
                    'array<int, string>',
                    'Traversable<int, bool>',
                ],
            ],
            'arrayIterableToIterableWithParams' => [
                'iterable<int, bool|string>',
                [
                    'array<int, string>',
                    'iterable<int, bool>',
                ],
            ],
            'iterableArrayToIterableWithParams' => [
                'iterable<int, bool|string>',
                [
                    'iterable<int, string>',
                    'array<int, bool>',
                ],
            ],
            'traversableIterableToIterableWithParams' => [
                'iterable<int, bool|string>',
                [
                    'Traversable<int, string>',
                    'iterable<int, bool>',
                ],
            ],
            'iterableTraversableToIterableWithParams' => [
                'iterable<int, bool|string>',
                [
                    'iterable<int, string>',
                    'Traversable<int, bool>',
                ],
            ],
            'arrayObjectAndParamsWithEmptyArray' => [
                'ArrayObject<int, string>|array<never, never>',
                [
                    'ArrayObject<int, string>',
                    'array<never, never>',
                ],
            ],
            'emptyArrayWithArrayObjectAndParams' => [
                'ArrayObject<int, string>|array<never, never>',
                [
                    'array<never, never>',
                    'ArrayObject<int, string>',
                ],
            ],
            'emptyArrayAndFalse' => [
                'array<never, never>|false',
                [
                    'array<never, never>',
                    'false',
                ],
            ],
            'emptyArrayAndTrue' => [
                'array<never, never>|true',
                [
                    'array<never, never>',
                    'true',
                ],
            ],
            'emptyArrayWithTrueAndFalse' => [
                'array<never, never>|bool',
                [
                    'array<never, never>',
                    'true',
                    'false',
                ],
            ],
            'falseDestruction' => [
                'bool',
                [
                    'false',
                    'bool',
                ],
            ],
            'onlyFalse' => [
                'false',
                [
                    'false',
                ],
            ],
            'onlyTrue' => [
                'true',
                [
                    'true',
                ],
            ],
            'falseFalseDestruction' => [
                'false',
                [
                    'false',
                    'false',
                ],
            ],
            'aAndAOfB' => [
                'A|A<B>',
                [
                    'A',
                    'A<B>',
                ],
            ],
            'combineObjectType1' => [
                'array{a?: int, b?: string}',
                [
                    'array{a: int}',
                    'array{b: string}',
                ],
            ],
            'combineObjectType2' => [
                'array{a: int|string, b?: string}',
                [
                    'array{a: int}',
                    'array{a: string,b: string}',
                ],
            ],
            'combineObjectTypeWithIntKeyedArray' => [
                "array<'a'|int, int|string>",
                [
                    'array{a: int}',
                    'array<int, string>',
                ],
            ],
            'combineNestedObjectTypeWithTKeyedArrayIntKeyedArray' => [
                "array{a: array<'a'|int, int|string>}",
                [
                    'array{a: array{a: int}}',
                    'array{a: array<int, string>}',
                ],
            ],
            'combineIntKeyedObjectTypeWithNestedIntKeyedArray' => [
                "array<int, array<'a'|int, int|string>>",
                [
                    'array<int, array{a:int}>',
                    'array<int, array<int, string>>',
                ],
            ],
            'combineNestedObjectTypeWithNestedIntKeyedArray' => [
                "array<'a'|int, array<'a'|int, int|string>>",
                [
                    'array{a: array{a: int}}',
                    'array<int, array<int, string>>',
                ],
            ],
            'combinePossiblyUndefinedKeys' => [
                'array{a: bool, b?: mixed, d?: mixed}',
                [
                    'array{a: false, b: mixed}',
                    'array{a: true, d: mixed}',
                    'array{a: true, d: mixed}',
                ],
            ],
            'combinePossiblyUndefinedKeysAndString' => [
                'array{a: string, b?: int}|string',
                [
                    'array{a: string, b?: int}',
                    'string',
                ],
            ],
            'combineMixedArrayWithTKeyedArray' => [
                'array<array-key, mixed>',
                [
                    'array{a: int}',
                    'array',
                ],
            ],
            'traversableAorB' => [
                'Traversable<mixed, A|B>',
                [
                    'Traversable<A>',
                    'Traversable<B>',
                ],
            ],
            'iterableAorB' => [
                'iterable<mixed, A|B>',
                [
                    'iterable<A>',
                    'iterable<B>',
                ],
            ],
            'FooAorB' => [
                'Foo<A>|Foo<B>',
                [
                    'Foo<A>',
                    'Foo<B>',
                ],
            ],
            'traversableOfMixed' => [
                'Traversable<mixed, mixed>',
                [
                    'Traversable',
                    'Traversable<mixed, mixed>',
                ],
            ],
            'traversableAndIterator' => [
                'Traversable&Iterator',
                [
                    'Traversable&Iterator',
                    'Traversable&Iterator',
                ],
            ],
            'traversableOfMixedAndIterator' => [
                'Traversable<mixed, mixed>&Iterator',
                [
                    'Traversable<mixed, mixed>&Iterator',
                    'Traversable<mixed, mixed>&Iterator',
                ],
            ],
            'objectLikePlusArrayEqualsArray' => [
                "array<'a'|'b'|'c', 1|2|3>",
                [
                    'array<"a"|"b"|"c", 1|2|3>',
                    'array{a: 1|2, b: 2|3, c: 1|3}',
                ],
            ],
            'combineClosures' => [
                'Closure[impure](A):void|Closure[impure](B):void',
                [
                    'Closure(A):void',
                    'Closure(B):void',
                ],
            ],
            'combineClassStringWithString' => [
                'string',
                [
                    'class-string',
                    'string',
                ],
            ],
            'combineClassStringWithFalse' => [
                'class-string|false',
                [
                    'class-string',
                    'false',
                ],
            ],
            'combineRefinedClassStringWithString' => [
                'string',
                [
                    'class-string<Exception>',
                    'string',
                ],
            ],
            'combineRefinedClassStrings' => [
                'class-string<Exception>|class-string<Iterator>',
                [
                    'class-string<Exception>',
                    'class-string<Iterator>',
                ],
            ],
            'combineClassStringsWithLiteral' => [
                'class-string',
                [
                    'class-string',
                    'Exception::class',
                ],
            ],
            'combineNonFalsyLowercaseStringWithFalsyLiteral' => [
                'non-empty-lowercase-string',
                [
                    'lowercase-string&non-falsy-string',
                    "'0'",
                ],
            ],
            'combineNonFalsyLowercaseStringWithUppercaseLiteral' => [
                'non-falsy-string',
                [
                    'lowercase-string&non-falsy-string',
                    "'Abc'",
                ],
            ],
            'combineNonFalsyLowercaseStringWithEmptyLiteral' => [
                'lowercase-string',
                [
                    'lowercase-string&non-falsy-string',
                    "''",
                ],
            ],
            'combineNonFalsyLowercaseStringWithNonEmptyLowercaseString' => [
                'non-empty-lowercase-string',
                [
                    'lowercase-string&non-falsy-string',
                    'non-empty-lowercase-string',
                ],
            ],
            'combineNonFalsyLowercaseStringWithNonFalsyString' => [
                'non-falsy-string',
                [
                    'non-falsy-string',
                    'lowercase-string&non-falsy-string',
                ],
            ],
            'combineNonFalsyStringWithNumericString' => [
                'non-empty-string',
                [
                    'non-falsy-string',
                    'numeric-string',
                ],
            ],
            'combineNumericStringWithNonFalsyString' => [
                'non-empty-string',
                [
                    'numeric-string',
                    'non-falsy-string',
                ],
            ],
            'combineNonFalsyNumericStringWithZero' => [
                'numeric-string',
                [
                    'non-falsy-string&numeric-string',
                    "'0'",
                ],
            ],
            'combineNonFalsyNumericStringWithNonNumericLiteral' => [
                'non-falsy-string',
                [
                    'non-falsy-string&numeric-string',
                    "'abc'",
                ],
            ],
            'combineEmptyLiteralWithRepeatedNumericString' => [
                "''|numeric-string",
                [
                    "''",
                    'numeric-string',
                    'numeric-string',
                ],
            ],
            'combineNumericStringWithEmptyLiteral' => [
                "''|numeric-string",
                [
                    'numeric-string',
                    "''",
                ],
            ],
            'combineLowercaseNumericStringWithUppercaseNumericLiteral' => [
                'numeric-string',
                [
                    'lowercase-string&numeric-string',
                    "'1E3'",
                ],
            ],
            'combineLowercaseNumericStringWithLowercaseLiteral' => [
                'non-empty-lowercase-string',
                [
                    "'abc'",
                    'lowercase-string&numeric-string',
                ],
            ],
            'combineLowercaseNumericStringWithNonFalsyLowercaseString' => [
                'non-empty-lowercase-string',
                [
                    'lowercase-string&numeric-string',
                    'lowercase-string&non-falsy-string',
                ],
            ],
            'combineNonFalsyLowercaseNumericStringWithNonFalsyNumericString' => [
                'non-falsy-string&numeric-string',
                [
                    'lowercase-string&non-falsy-string&numeric-string',
                    'non-falsy-string&numeric-string',
                ],
            ],
            'combineNonFalsyNumericStringWithLowercaseNumericString' => [
                'numeric-string',
                [
                    'non-falsy-string&numeric-string',
                    'lowercase-string&numeric-string',
                ],
            ],
            'combineNonFalsyNumericStringWithNumericString' => [
                'numeric-string',
                [
                    'numeric-string',
                    'non-falsy-string&numeric-string',
                ],
            ],
            'combineNonFalsyNumericStringWithString' => [
                'string',
                [
                    'non-falsy-string&numeric-string',
                    'string',
                ],
            ],
            'combineNumericStringWithNonNumericLiteral' => [
                'non-empty-string',
                [
                    'numeric-string',
                    "'a'",
                ],
            ],
            'combineDecimalIntStringWithNonFalsyLowercaseString' => [
                'non-empty-lowercase-string',
                [
                    'decimal-int-string',
                    'lowercase-string&non-falsy-string',
                ],
            ],
            'combineLowercaseNumericStringWithDecimalIntString' => [
                'lowercase-string&numeric-string',
                [
                    'lowercase-string&numeric-string',
                    'decimal-int-string',
                ],
            ],
            'combineDecimalIntStringWithNonFalsyNumericString' => [
                'numeric-string',
                [
                    'decimal-int-string',
                    'non-falsy-string&numeric-string',
                ],
            ],
            'combineNonDecimalIntStringWithNonFalsyLowercaseString' => [
                'string',
                [
                    'non-decimal-int-string',
                    'lowercase-string&non-falsy-string',
                ],
            ],
            'combineDecimalIntStringWithDecimalIntLiteral' => [
                'decimal-int-string',
                [
                    'decimal-int-string',
                    "'123'",
                ],
            ],
            'combineDecimalIntStringWithNumericLiteral' => [
                "'1.5'|decimal-int-string",
                [
                    'decimal-int-string',
                    "'1.5'",
                ],
            ],
            'combineDecimalIntStringWithLiterals' => [
                "'a'|decimal-int-string",
                [
                    "'5'",
                    'decimal-int-string',
                    "'a'",
                ],
            ],
            'combineDecimalIntStringAndLiteralWithNonEmptyString' => [
                'non-empty-string',
                [
                    'decimal-int-string',
                    "'a'",
                    'non-empty-string',
                ],
            ],
            'combineDecimalIntStringAndEmptyLiteralWithNonEmptyString' => [
                'string',
                [
                    'decimal-int-string',
                    "''",
                    'non-empty-string',
                ],
            ],
            'combineDecimalIntStringAndLiteralWithNumericString' => [
                'non-empty-string',
                [
                    'decimal-int-string',
                    "'a'",
                    'numeric-string',
                ],
            ],
            'combineDecimalIntStringWithNumericString' => [
                'numeric-string',
                [
                    'decimal-int-string',
                    'numeric-string',
                ],
            ],
            'combineDecimalIntStringWithNonDecimalIntString' => [
                'string',
                [
                    'decimal-int-string',
                    'non-decimal-int-string',
                ],
            ],
            'combineNonDecimalIntStringWithNonDecimalIntLiteral' => [
                'non-decimal-int-string',
                [
                    'non-decimal-int-string',
                    "'abc'",
                ],
            ],
            'combineNonDecimalIntStringWithDecimalIntLiteral' => [
                "'123'|non-decimal-int-string",
                [
                    'non-decimal-int-string',
                    "'123'",
                ],
            ],
            'combineClassStringWithNumericString' => [
                'class-string|numeric-string',
                [
                    'class-string',
                    'numeric-string',
                ],
            ],
            'combineRefinedClassStringWithNumericString' => [
                'class-string<Exception>|numeric-string',
                [
                    'class-string<Exception>',
                    'numeric-string',
                ],
            ],
            'combineClassStringWithTraitString' => [
                'class-string|trait-string',
                [
                    'class-string',
                    'trait-string',
                ],
            ],
            'combineRefinedClassStringWithTraitString' => [
                'class-string<Exception>|trait-string',
                [
                    'class-string<Exception>',
                    'trait-string',
                ],
            ],
            'combineCallableAndCallableString' => [
                'callable[impure]',
                [
                    'callable',
                    'callable-string',
                ],
            ],
            'combineCallableStringAndCallable' => [
                'callable[impure]',
                [
                    'callable-string',
                    'callable',
                ],
            ],
            'combineCallableAndCallableObject' => [
                'callable[impure]',
                [
                    'callable',
                    'callable-object',
                ],
            ],
            'combineCallableObjectAndCallable' => [
                'callable[impure]',
                [
                    'callable-object',
                    'callable',
                ],
            ],
            'combineCallableAndCallableArray' => [
                'callable[impure]',
                [
                    'callable',
                    'callable-array',
                ],
            ],
            'combineCallableArrayAndCallable' => [
                'callable[impure]',
                [
                    'callable-array',
                    'callable',
                ],
            ],
            'combineCallableAndCallableList' => [
                'callable[impure]',
                [
                    'callable',
                    'callable-list',
                ],
            ],
            'combineCallableListAndCallable' => [
                'callable[impure]',
                [
                    'callable-list',
                    'callable',
                ],
            ],
            'combineCallableArrayAndArray' => [
                'array<array-key, mixed>',
                [
                    'callable-array{class-string, string}',
                    'array',
                ],
            ],
            'combineGenericArrayAndMixedArray' => [
                'array<array-key, int|mixed>',
                [
                    'array<string, int>',
                    'array<array-key, mixed>',
                ],
            ],
            'combineTKeyedArrayAndArray' => [
                'array<array-key, mixed>',
                [
                    'array{hello: int}',
                    'array<array-key, mixed>',
                ],
            ],
            'combineTKeyedArrayAndNestedArray' => [
                'array<array-key, mixed>',
                [
                    'array{hello: array{goodbye: int}}',
                    'array<array-key, mixed>',
                ],
            ],
            'combineNumericStringWithLiteralString' => [
                'numeric-string',
                [
                    'numeric-string',
                    '"1"',
                ],
            ],
            'combineLiteralStringWithNumericString' => [
                'numeric-string',
                [
                    '"1"',
                    'numeric-string',
                ],
            ],
            'combineNonEmptyListWithTKeyedArrayList' => [
                'list{null|string, ...<string>}',
                [
                    'non-empty-list<string>',
                    'array{null}',
                ],
            ],
            'combineZeroAndPositiveInt' => [
                'int<0, max>',
                [
                    '0',
                    'positive-int',
                ],
            ],
            'combinePositiveIntAndZero' => [
                'int<0, max>',
                [
                    'positive-int',
                    '0',
                ],
            ],
            'combinePositiveIntAndMinusOne' => [
                'int<-1, max>',
                [
                    'positive-int',
                    '-1',
                ],
            ],
            'combinePositiveIntZeroAndMinusOne' => [
                'int<-1, max>',
                [
                    '0',
                    'positive-int',
                    '-1',
                ],
            ],
            'combineMinusOneAndPositiveInt' => [
                'int<-1, max>',
                [
                    '-1',
                    'positive-int',
                ],
            ],
            'combineZeroMinusOneAndPositiveInt' => [
                'int<-1, max>',
                [
                    '0',
                    '-1',
                    'positive-int',
                ],
            ],
            'combineZeroOneAndPositiveInt' => [
                'int<0, max>',
                [
                    '0',
                    '1',
                    'positive-int',
                ],
            ],
            'combinePositiveIntOneAndZero' => [
                'int<0, max>',
                [
                    'positive-int',
                    '1',
                    '0',
                ],
            ],
            'combinePositiveInts' => [
                'int<1, max>',
                [
                    'positive-int',
                    'positive-int',
                ],
            ],
            'combineNonEmptyArrayAndKeyedArray' => [
                'array<int, int>',
                [
                    'non-empty-array<int, int>',
                    'array{0?:int}',
                ],
            ],
            'combineNonEmptyStringAndLiteral' => [
                'non-empty-string',
                [
                    'non-empty-string',
                    '"foo"',
                ],
            ],
            'combineLiteralAndNonEmptyString' => [
                'non-empty-string',
                [
                    '"foo"',
                    'non-empty-string',
                ],
            ],
            'combineTruthyStringAndNonEmptyString' => [
                'non-empty-string',
                [
                    'truthy-string',
                    'non-empty-string',
                ],
            ],
            'combineNonFalsyNonEmptyString' => [
                'non-empty-string',
                [
                    'non-falsy-string',
                    'non-empty-string',
                ],
            ],
            'combineNonEmptyNonFalsyString' => [
                'non-empty-string',
                [
                    'non-empty-string',
                    'non-falsy-string',
                ],
            ],
            'combineNonEmptyStringAndNumericString' => [
                'non-empty-string',
                [
                    'non-empty-string',
                    'numeric-string',
                ],
            ],
            'combineNumericStringAndNonEmptyString' => [
                'non-empty-string',
                [
                    'numeric-string',
                    'non-empty-string',
                ],
            ],
            'combineNonEmptyLowercaseAndNonFalsyString' => [
                'non-empty-string',
                [
                    'non-falsy-string',
                    'non-empty-lowercase-string',
                ],
            ],
            'combineNonEmptyAndEmptyScalar' => [
                'scalar',
                [
                    'non-empty-scalar',
                    'empty-scalar',
                ],
            ],
            'combineLiteralStringAndNonspecificLiteral' => [
                'literal-string',
                [
                    'literal-string',
                    '"foo"',
                ],
            ],
            'combineNonspecificLiteralAndLiteralString' => [
                'literal-string',
                [
                    '"foo"',
                    'literal-string',
                ],
            ],
            'combineLiteralIntAndNonspecificLiteral' => [
                'literal-int',
                [
                    'literal-int',
                    '5',
                ],
            ],
            'combineNonspecificLiteralAndLiteralInt' => [
                'literal-int',
                [
                    '5',
                    'literal-int',
                ],
            ],
            'combineNonspecificLiteralAndPositiveInt' => [
                'int',
                [
                    'positive-int',
                    'literal-int',
                ],
            ],
            'combinePositiveAndLiteralInt' => [
                'int',
                [
                    'literal-int',
                    'positive-int',
                ],
            ],
            'combineNonEmptyStringAndNonEmptyNonSpecificLiteralString' => [
                'non-empty-string',
                [
                    'non-empty-literal-string',
                    'non-empty-string',
                ],
            ],
            'combineNonEmptyNonSpecificLiteralStringAndNonEmptyString' => [
                'non-empty-string',
                [
                    'non-empty-string',
                    'non-empty-literal-string',
                ],
            ],
            'nonFalsyStringAndFalsyLiteral' => [
                'non-empty-string',
                [
                    'non-falsy-string',
                    '"0"',
                ],
            ],
            'unionOfClassStringAndClassStringWithIntersection' => [
                'class-string<IFoo>',
                [
                    'class-string<IFoo>',
                    'class-string<IFoo & IBar>',
                ],
            ],
            'unionNonEmptyLiteralStringAndLiteralString' => [
                'literal-string',
                [
                    'non-empty-literal-string',
                    'literal-string',
                ],
            ],
            'unionLiteralStringAndNonEmptyLiteralString' => [
                'literal-string',
                [
                    'literal-string',
                    'non-empty-literal-string',
                ],
            ],
            'classStringMapAndArrayReplaceTheMapTemplateByItsBound' => [
                'array<class-string<Foo>|int, Foo|int>',
                [
                    'class-string-map<T as Foo, T>',
                    'array<int, int>',
                ],
            ],
            'classStringMapAndArrayKeepTheIntersectionOfTheMapTemplate' => [
                'array<class-string<Foo>|int, Foo&Bar|int>',
                [
                    'class-string-map<T as Foo, T&Bar>',
                    'array<int, int>',
                ],
            ],
            'classStringMapWithoutBoundAndArrayKeepTheIntersectionOfTheMapTemplate' => [
                'array<class-string|int, Bar|int>',
                [
                    'class-string-map<T, T&Bar>',
                    'array<int, int>',
                ],
            ],
            'classStringMapsWithDifferentBoundsReplaceEachTemplateByItsBound' => [
                'array<class-string<Bar>|class-string<Foo>, Bar|Foo>',
                [
                    'class-string-map<T as Foo, T>',
                    'class-string-map<T as Bar, T>',
                ],
            ],
            'classStringMapsWithDifferentTemplateNamesReplaceEachTemplateByItsBound' => [
                'array<class-string<Foo>, Foo>',
                [
                    'class-string-map<T as Foo, T>',
                    'class-string-map<U as Foo, U>',
                ],
            ],
            'classStringMapWithoutBoundAndArrayReplaceTheMapTemplateByObject' => [
                'array<class-string|int, int|list<object>>',
                [
                    'class-string-map<T, list<T>>',
                    'array<int, int>',
                ],
            ],
            'classStringMapAndKeyedArrayReplaceTheMapTemplateByItsBound' => [
                'array<\'a\'|class-string<Foo>, Foo|int>',
                [
                    'class-string-map<T as Foo, T>',
                    'array{a: int}',
                ],
            ],
            'classStringMapAndListReplaceTheMapTemplateByItsBound' => [
                'array<class-string<Foo>|int<0, max>, Foo|int>',
                [
                    'class-string-map<T as Foo, T>',
                    'list<int>',
                ],
            ],
            'classStringMapsWithTheSameBoundStayAClassStringMap' => [
                'class-string-map<T as Foo, (T:class-string-map as Foo)|(list<T:class-string-map as Foo>)>',
                [
                    'class-string-map<T as Foo, T>',
                    'class-string-map<T as Foo, list<T>>',
                ],
            ],
            'classStringMapAndEmptyArrayStayAClassStringMap' => [
                'class-string-map<T as Foo, T:class-string-map as Foo>',
                [
                    'class-string-map<T as Foo, T>',
                    'array<never, never>',
                ],
            ],
            'classStringMapAndCallableArrayAreNotACallableArray' => [
                'array<0|1|class-string<Foo>, class-string|non-empty-string|object>',
                [
                    'class-string-map<T as Foo, T>',
                    'callable-array',
                ],
            ],
        ];
    }

    private static function getAtomic(string $string): Atomic
    {
        return Type::parseString($string)->getSingleAtomic();
    }
}
