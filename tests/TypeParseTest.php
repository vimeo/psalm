<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Codebase;
use Psalm\Exception\TypeParseTreeException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Type;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TTemplateKeyOf;
use Psalm\Type\Union;
use ReflectionFunction;

use function function_exists;
use function mb_substr;
use function print_r;
use function stripos;

final class TypeParseTest extends TestCase
{
    #[Override]
    public function setUp(): void
    {
        RuntimeCaches::clearAll();
        $this->file_provider = new FakeFileProvider();

        $config = new TestConfig();

        $providers = new Providers(
            $this->file_provider,
            new FakeParserCacheProvider(),
        );

        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            $providers,
        );
    }

    public function testThisToStatic(): void
    {
        $this->assertSame('static', (string) Type::parseString('$this'));
    }

    public function testThisToStaticUnion(): void
    {
        $this->assertSame('A|static', (string) Type::parseString('$this|A'));
    }

    public function testIntOrString(): void
    {
        $this->assertSame('int|string', (string) Type::parseString('int|string'));
    }

    public function testBracketedIntOrString(): void
    {
        $this->assertSame('int|string', (string) Type::parseString('(int|string)'));
    }

    public function testBoolOrIntOrString(): void
    {
        $this->assertSame('bool|int|string', (string) Type::parseString('bool|int|string'));
    }

    public function testNullable(): void
    {
        $this->assertSame('null|string', (string) Type::parseString('?string'));
    }

    public function testNullableUnion(): void
    {
        $this->assertSame('int|null|string', (string) Type::parseString('?(string|int)'));
    }

    public function testNullableFullyQualified(): void
    {
        $this->assertSame('null|stdClass', (string) Type::parseString('?\\stdClass'));
    }

    public function testNullableOrNullable(): void
    {
        $this->assertSame('int|null|string', (string) Type::parseString('?string|?int'));
    }

    public function testBadNullableCharacterInUnion(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('int|array|?');
    }

    public function testBadNullableCharacterInUnionWithFollowing(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('int|array|?|bool');
    }

    public function testArrayWithClosingBracket(): void
    {
        $this->assertSame('array<int, int>', (string) Type::parseString('array<int, int>'));
    }

    public function testArrayWithoutClosingBracket(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('array<int, int');
    }

    public function testArrayWithSingleArg(): void
    {
        $this->assertSame('array<array-key, int>', (string) Type::parseString('array<int>'));
    }

    public function testArrayWithNestedSingleArg(): void
    {
        $this->assertSame('array<array-key, array<array-key, int>>', (string) Type::parseString('array<array<int>>'));
    }

    public function testArrayWithUnion(): void
    {
        $this->assertSame('array<int|string, string>', (string) Type::parseString('array<int|string, string>'));
    }

    public function testNonEmptyArray(): void
    {
        $this->assertSame('non-empty-array<array-key, int>', (string) Type::parseString('non-empty-array<int>'));
    }

    public function testGeneric(): void
    {
        $this->assertSame('B<int>', (string) Type::parseString('B<int>'));
    }

    public function testIntersection(): void
    {
        $this->assertSame('I1&I2&I3', (string) Type::parseString('I1&I2&I3'));
    }

    public function testIntersectionOrNull(): void
    {
        $this->assertSame('I1&I2|null', (string) Type::parseString('I1&I2|null'));
    }

    public function testNullOrIntersection(): void
    {
        $this->assertSame('I1&I2|null', (string) Type::parseString('null|I1&I2'));
    }

    public function testIteratorAndTraversable(): void
    {
        $this->assertSame('Iterator<mixed, int>&Traversable', (string) Type::parseString('Iterator<int>&Traversable'));
    }

    public function testStaticAndStatic(): void
    {
        $this->assertSame('static', (string) Type::parseString('static&static'));
    }

    public function testTraversableAndIteratorOrNull(): void
    {
        $this->assertSame(
            'Traversable&Iterator<mixed, int>|null',
            (string) Type::parseString('Traversable&Iterator<int>|null'),
        );
    }

    public function testIteratorAndTraversableOrNull(): void
    {
        $this->assertSame(
            'Iterator<mixed, int>&Traversable|null',
            (string) Type::parseString('Iterator<mixed, int>&Traversable|null'),
        );
    }

    public function testUnsealedArray(): void
    {
        $this->assertSame('array{a: int, ...<string, string>}', Type::parseString('array{a: int, ...<string, string>}')->getId());
    }

    public function testUnsealedList(): void
    {
        $this->assertSame('list{int, ...<string>}', Type::parseString('list{int, ...<string>}')->getId());
    }

    public function testUnsealedListComplex(): void
    {
        $this->assertSame('list{array{a: 123}, ...<123>}', Type::parseString('list{0: array{a: 123}, ...<123>}')->getId());
    }

    public function testIntersectionAfterGeneric(): void
    {
        $this->assertSame('Countable&iterable<mixed, int>&I', (string) Type::parseString('Countable&iterable<int>&I'));
    }

    public function testIntersectionOfIterables(): void
    {
        $this->assertSame('iterable<mixed, A>&iterable<mixed, B>', (string) Type::parseString('iterable<A>&iterable<B>'));
    }

    public function testIntersectionOfTKeyedArray(): void
    {
        $this->assertSame('array{a: int, b: int}', (string) Type::parseString('array{a: int}&array{b: int}'));
    }

    public function testIntersectionOfTwoDifferentArrays(): void
    {
        $this->assertSame('array{a: int, ...<string, string>}', Type::parseString('array{a: int}&array<string, string>')->getId());
    }

    public function testIntersectionOfTwoDifferentArraysReversed(): void
    {
        $this->assertSame('array{a: int, ...<string, string>}', Type::parseString('array<string, string>&array{a: int}')->getId());
    }

    public function testIntersectionOfTKeyedArrayWithMergedProperties(): void
    {
        $this->assertSame('array{a: int}', (string) Type::parseString('array{a: int}&array{a: mixed}'));
    }

    public function testIntersectionOfTKeyedArrayWithPossiblyUndefinedMergedProperties(): void
    {
        $this->assertSame('array{a: int}', (string) Type::parseString('array{a: int}&array{a?: int}'));
    }


    public function testIntersectionOfIntranges(): void
    {
        $this->assertSame('array{a: int<3, 4>}', (string) Type::parseString('array{a: int<2, 4>}&array{a: int<3, 6>}'));
        $this->assertSame('array{a: 4}', Type::parseString('array{a: 4}&array{a: int<3, 6>}')->getId(true));
    }

    public function testIntersectionOfTKeyedArrayWithConflictingProperties(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('array{a: string}&array{a: int}');
    }

    public function testIntersectionOfTwoRegularArrays(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('string[]&array<string, string>');
    }

    public function testUnionOfIntersectionOfTKeyedArray(): void
    {
        $this->assertSame('array{a: int|string, b?: int}', (string) Type::parseString('array{a: int}|array{a: string}&array{b: int}'));
        $this->assertSame('array{a: int|string, b?: int}', (string) Type::parseString('array{b: int}&array{a: string}|array{a: int}'));
    }

    public function testIntersectionOfUnionOfTKeyedArray(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('array{a: int}&array{a: string}|array{b: int}');
    }

    public function testIntersectionOfTKeyedArrayAndObject(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('array{a: int}&T1');
    }

    public function testIterableContainingTKeyedArray(): void
    {
        $this->assertSame('iterable<string, list{int}>', Type::parseString('iterable<string, array{int}>')->getId());
    }

    public function testPhpDocSimpleArray(): void
    {
        $this->assertSame('array<array-key, A>', (string) Type::parseString('A[]'));
    }

    public function testPhpDocUnionArray(): void
    {
        $this->assertSame('array<array-key, A|B>', (string) Type::parseString('(A|B)[]'));
    }

    public function testPhpDocMultiDimensionalArray(): void
    {
        $this->assertSame('array<array-key, array<array-key, A>>', (string) Type::parseString('A[][]'));
    }

    public function testPhpDocMultidimensionalUnionArray(): void
    {
        $this->assertSame('array<array-key, array<array-key, A|B>>', (string) Type::parseString('(A|B)[][]'));
    }

    public function testPhpDocTKeyedArray(): void
    {
        $this->assertSame(
            'array<array-key, array{b: bool, d: string}>',
            (string) Type::parseString('array{b: bool, d: string}[]'),
        );
    }

    public function testPhpDocUnionOfArrays(): void
    {
        $this->assertSame('array<array-key, A|B>', (string) Type::parseString('A[]|B[]'));
    }

    public function testPhpDocUnionOfArraysOrObject(): void
    {
        $this->assertSame('C|array<array-key, A|B>', (string) Type::parseString('A[]|B[]|C'));
    }

    public function testPsalmOnlyAtomic(): void
    {
        $this->assertSame('class-string', (string) Type::parseString('class-string'));
    }

    public function testParameterizedClassString(): void
    {
        $this->assertSame('class-string<A>', (string) Type::parseString('class-string<A>'));
    }

    public function testParameterizedClassStringUnion(): void
    {
        $this->assertSame('class-string<A>|class-string<B>', (string) Type::parseString('class-string<A>|class-string<B>'));
    }

    public function testInvalidType(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('array(A)');
    }

    public function testBracketedUnionAndIntersection(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('(A|B)&C');
    }

    public function testBracketInUnion(): void
    {
        Type::parseString('null|(scalar|array|object)');
    }

    public function testTKeyedArrayWithSimpleArgs(): void
    {
        $this->assertSame('array{a: int, b: string}', (string) Type::parseString('array{a: int, b: string}'));
    }

    public function testTKeyedArrayWithSpace(): void
    {
        $this->assertSame('array{\'a \': int, \'b  \': string}', (string) Type::parseString('array{\'a \': int, \'b  \': string}'));
    }

    public function testTKeyedArrayWithQuotedKeys(): void
    {
        $this->assertSame('array{\'\\"\': int, \'\\\'\': string}', (string) Type::parseString('array{\'"\': int, \'\\\'\': string}'));
        $this->assertSame('array{\'\\"\': int, \'\\\'\': string}', (string) Type::parseString('array{"\\"": int, "\\\'": string}'));
    }

    public function testTKeyedArrayWithClassConstantValueType(): void
    {
        $this->assertSame('list{A::X|A::Y, B::X}', (string) Type::parseString('list{A::X|A::Y, B::X}'));
    }

    public function testTKeyedArrayWithClassConstantKey(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('array{self::FOO: string}');
    }

    public function testTKeyedArrayWithQuotedClassConstantKey(): void
    {
        $this->assertSame('array{\'self::FOO\': string}', (string) Type::parseString('array{"self::FOO": string}'));
    }

    public function testTKeyedArrayWithoutClosingBracket(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('array{a: int, b: string');
    }

    public function testTKeyedArrayInType(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('array{a:[]}');
    }

    public function testObjectWithSimpleArgs(): void
    {
        $this->assertSame('object{a:int, b:string}', (string) Type::parseString('object{a:int, b:string}'));
    }

    public function testObjectWithDollarArgs(): void
    {
        $this->assertSame('object{a:int, $b:string}', (string) Type::parseString('object{a:int, $b:string}'));
    }

    public function testTKeyedArrayWithUnionArgs(): void
    {
        $this->assertSame(
            'array{a: int|string, b: string}',
            (string) Type::parseString('array{a: int|string, b: string}'),
        );
    }

    public function testTKeyedArrayWithGenericArgs(): void
    {
        $this->assertSame(
            'array{a: array<int, int|string>, b: string}',
            (string) Type::parseString('array{a: array<int, string|int>, b: string}'),
        );
    }

    public function testTKeyedArrayWithIntKeysAndUnionArgs(): void
    {
        $this->assertSame(
            'list{null|stdClass}',
            (string)Type::parseString('list{stdClass|null}'),
        );
    }

    public function testTKeyedArrayWithIntKeysAndGenericArgs(): void
    {
        $this->assertSame(
            'list{array<array-key, mixed>}',
            (string)Type::parseString('array{array}'),
        );

        $this->assertSame(
            'list{array<int, string>}',
            (string)Type::parseString('array{array<int, string>}'),
        );
    }

    public function testTKeyedArrayOptional(): void
    {
        $this->assertSame(
            'array{a: int, b?: int}',
            (string)Type::parseString('array{a: int, b?: int}'),
        );
    }

    public function testTKeyedArrayNotSealed(): void
    {
        $this->assertSame(
            'array{a: int, ...<array-key, mixed>}',
            (string)Type::parseString('array{a: int, ...}'),
        );
    }

    public function testTKeyedList(): void
    {
        $this->assertSame(
            'list{int, int, string}',
            (string)Type::parseString('list{int, int, string}'),
        );
    }

    public function testTKeyedListOptional(): void
    {
        $this->assertSame(
            'list{0: int, 1?: int, 2?: string}',
            (string)Type::parseString('list{0: int, 1?: int, 2?: string}'),
        );
    }


    public function testTKeyedArrayList(): void
    {
        $this->assertSame(
            'list{int, int, string}',
            (string)Type::parseString('array{int, int, string}'),
        );
    }


    public function testTKeyedArrayNonList(): void
    {
        $this->assertSame(
            'array{0: int, 1: int, 2: string}',
            (string)Type::parseString('array{0: int, 1: int, 2: string}'),
        );
    }


    public function testTKeyedCallableArrayNonList(): void
    {
        $this->assertSame(
            'callable-array{0: class-string, 1: string}',
            (string)Type::parseString('callable-array{0: class-string, 1: string}'),
        );
    }


    public function testTKeyedListNonList(): void
    {
        $this->expectExceptionMessage('A list shape cannot describe a non-list');
        Type::parseString('list{a: 0, b: 1, c: 2}');
    }


    public function testTKeyedListNonListOptional(): void
    {
        $this->expectExceptionMessage('A list shape cannot describe a non-list');
        Type::parseString('list{a: 0, b?: 1, c?: 2}');
    }

    public function testTKeyedListNonListOptionalWrongOrder1(): void
    {
        $this->expectExceptionMessage('A list shape cannot describe a non-list');
        Type::parseString('list{0?: 0, 1: 1, 2: 2}');
    }

    public function testTKeyedListNonListOptionalWrongOrder2(): void
    {
        $this->expectExceptionMessage('A list shape cannot describe a non-list');
        Type::parseString('list{0: 0, 1?: 1, 2: 2}');
    }


    public function testTKeyedListWrongOrder(): void
    {
        $this->expectExceptionMessage('A list shape cannot describe a non-list');
        Type::parseString('list{1: 1, 0: 0}');
    }

    public function testTKeyedListNonListKeys(): void
    {
        $this->expectExceptionMessage('A list shape cannot describe a non-list');
        Type::parseString('list{1: 1, 2: 2}');
    }

    public function testTKeyedListNoExplicitAndImplicitKeys(): void
    {
        $this->expectExceptionMessage('Cannot mix explicit and implicit keys');
        Type::parseString('list{0: 0, 1}');
    }

    public function testTKeyedArrayNoExplicitAndImplicitKeys(): void
    {
        $this->expectExceptionMessage('Cannot mix explicit and implicit keys');
        Type::parseString('array{0, test: 1}');
    }

    public function testTKeyedArrayNoDuplicateKeys(): void
    {
        $this->expectExceptionMessage('Duplicate key a detected');
        Type::parseString('array{a: int, a: int}');
    }

    public function testSimpleCallable(): void
    {
        $this->assertSame(
            'callable(int, string):void',
            (string)Type::parseString('callable(int, string) : void'),
        );
    }

    public function testCallableWithoutClosingBracket(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('callable(int, string');
    }

    public function testCallableWithParamNames(): void
    {
        $this->assertSame(
            'callable(int, string):void',
            (string)Type::parseString('callable(int $foo, string $bar) : void'),
        );
    }

    public function testCallableReturningIntersection(): void
    {
        $this->assertSame(
            'callable(int, string):I1&I2',
            (string)Type::parseString('callable(int, string) : (I1&I2)'),
        );
    }

    public function testEmptyCallable(): void
    {
        $this->assertSame(
            'callable():void',
            (string)Type::parseString('callable() : void'),
        );
    }

    public function testCallableWithUnionLastType(): void
    {
        $this->assertSame(
            'callable(int, int|string):void',
            (string)Type::parseString('callable(int, int|string) : void'),
        );
    }

    public function testCallableWithVariadic(): void
    {
        $this->assertSame(
            'callable(int, string...):void',
            (string)Type::parseString('callable(int, string...) : void'),
        );
    }

    public function testCallableThatReturnsACallable(): void
    {
        $this->assertSame(
            'callable():callable():string',
            (string)Type::parseString('callable() : callable() : string'),
        );
    }

    public function testCallableThatReturnsACallableThatReturnsACallable(): void
    {
        $this->assertSame(
            'callable():callable():callable():string',
            (string)Type::parseString('callable() : callable() : callable() : string'),
        );
    }

    public function testCallableOrInt(): void
    {
        $this->assertSame(
            'callable(string):void|int',
            (string)Type::parseString('callable(string):void|int'),
        );
    }

    public function testCallableWithGoodVariadic(): void
    {
        Type::parseString('callable(int, string...) : void');
        Type::parseString('callable(int,string...) : void');
    }

    public function testCallableWithSpreadBefore(): void
    {
        $this->assertSame(
            'callable(int, string...):void',
            (string)Type::parseString('callable(int, ...string):void'),
        );
    }

    public function testConditionalTypeWithSpaces(): void
    {
        $this->assertSame(
            '(T is string ? string : int)',
            (string) Type::parseString('(T is string ? string : int)', null, ['T' => ['' => Type::getArray()]]),
        );
    }

    public function testConditionalTypeWithUnion(): void
    {
        $this->assertSame(
            '(T is string|true ? int|string : int)',
            Type::parseString('(T is "hello"|true ? string|int : int)', null, ['T' => ['' => Type::getArray()]])->getId(false),
        );
    }

    public function testConditionalTypeWithTKeyedArray(): void
    {
        $this->assertSame(
            '(T is array{a: string} ? string : int)',
            (string) Type::parseString('(T is array{a: string} ? string : int)', null, ['T' => ['' => Type::getArray()]]),
        );
    }

    public function testConditionalTypeWithGenericIs(): void
    {
        $this->assertSame(
            '(T is array<array-key, string> ? string : int)',
            (string) Type::parseString('(T is array<string> ? string : int)', null, ['T' => ['' => Type::getArray()]]),
        );
    }

    public function testConditionalTypeWithIntersection(): void
    {
        $this->assertSame(
            '(T is A&B ? string : int)',
            (string) Type::parseString('(T is A&B ? string : int)', null, ['T' => ['' => Type::getArray()]]),
        );
    }

    public function testConditionalTypeWithoutSpaces(): void
    {
        $this->assertSame(
            '(T is string ? string : int)',
            (string) Type::parseString('(T is string?string:int)', null, ['T' => ['' => Type::getArray()]]),
        );
    }

    public function testConditionalTypeWithCallableElseBool(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('(T is string ? callable() : bool)', null, ['T' => ['' => Type::getArray()]]);
    }

    public function testConditionalTypeWithCallableReturningBoolElseBool(): void
    {
        $this->assertSame(
            '(T is string ? callable():bool : bool)',
            (string) Type::parseString('(T is string ? (callable() : bool) : bool)', null, ['T' => ['' => Type::getArray()]]),
        );
    }

    public function testConditionalTypeWithGenerics(): void
    {
        $this->assertSame(
            '(T is string ? string : array<string, string>)',
            (string) Type::parseString(
                '(T is string ? string : array<string, string>)',
                null,
                ['T' => ['' => Type::getArray()]],
            ),
        );
    }

    public function testConditionalTypeWithCallableBracketed(): void
    {
        $this->assertSame(
            '(T is string ? callable(string, string):string : callable(mixed...):mixed)',
            (string) Type::parseString(
                '(T is string ? (callable(string, string):string) : (callable(mixed...):mixed))',
                null,
                ['T' => ['' => Type::getArray()]],
            ),
        );
    }

    public function testConditionalTypeWithCallableNotBracketed(): void
    {
        $this->assertSame(
            '(T is string ? callable(string, string):string : callable(mixed...):mixed)',
            (string) Type::parseString(
                '(T is string ? callable(string, string):string : callable(mixed...):mixed)',
                null,
                ['T' => ['' => Type::getArray()]],
            ),
        );
    }

    public function testCallableWithTrailingColon(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('callable(int):');
    }

    public function testCallableWithAnotherBadVariadic(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('callable(int, string..) : void');
    }

    public function testCallableWithMissingVariadicType(): void
    {
        $this->assertSame(
            'callable(mixed...):void',
            (string) Type::parseString('callable(...): void'),
        );
    }

    public function testCallableWithVariadicAndDefault(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('callable(int, string...=) : void');
    }

    public function testBadVariadic(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('string...');
    }

    public function testBadFullStop(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('string.');
    }

    public function testBadSemicolon(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('string;');
    }

    public function testBadGenericString(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('string<T>');
    }

    public function testBadAmpersand(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('&array');
    }

    public function testBadColon(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString(':array');
    }

    public function testBadBrackets(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('max(a)');
    }

    public function testMoreBadBrackets(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('max(a):void');
    }

    public function testGeneratorWithWBadBrackets(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('Generator{string, A}');
    }

    public function testBadEquals(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('=array');
    }

    public function testBadBar(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('|array');
    }

    public function testBadColonDash(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('array|string:-');
    }

    public function testDoubleBar(): void
    {
        $this->expectException(TypeParseTreeException::class);
        Type::parseString('PDO||Closure|numeric');
    }

    public function testCallableWithDefault(): void
    {
        $this->assertSame(
            'callable(int, string=):void',
            (string)Type::parseString('callable(int, string=) : void'),
        );
    }

    public function testNestedCallable(): void
    {
        $this->assertSame(
            'callable(callable(A):B):C',
            (string)Type::parseString('callable(callable(A):B):C'),
        );
    }

    public function testCallableWithoutReturn(): void
    {
        $this->assertSame(
            'callable(int, string)',
            (string)Type::parseString('callable(int, string)'),
        );
    }

    public function testCombineLiteralStringWithClassString(): void
    {
        $this->assertSame(
            "'array'|class-string",
            Type::parseString('"array"|class-string')->getId(),
        );
    }

    public function testCombineLiteralClassStringWithClassString(): void
    {
        $this->assertSame(
            'class-string',
            Type::parseString('A::class|class-string')->getId(),
        );
    }

    public function testKeyOfClassConstant(): void
    {
        $this->assertSame(
            'key-of<Foo\Baz::BAR>',
            (string)Type::parseString('key-of<Foo\Baz::BAR>'),
        );
    }

    public function testKeyOfTemplate(): void
    {
        $this->assertSame(
            'key-of<T>',
            Type::parseString('key-of<T>', null, ['T' => ['' => Type::getArray()]])->getId(false),
        );
    }

    public function testValueOfTemplate(): void
    {
        $this->assertSame(
            'value-of<T>',
            (string)Type::parseString('value-of<T>', null, ['T' => ['' => Type::getArray()]]),
        );
    }

    public function testIndexedAccess(): void
    {
        $this->assertSame(
            'T[K]',
            (string) Type::parseString(
                'T[K]',
                null,
                [
                    'T' => ['' => Type::getArray()],
                    'K' => ['' => new Union([
                        new TTemplateKeyOf('T', 'fn-foo', Type::getMixed()),
                    ])],
                ],
            ),
        );
    }

    public function testValueOfClassConstant(): void
    {
        $this->assertSame(
            'value-of<Foo\Baz::BAR>',
            (string)Type::parseString('value-of<Foo\Baz::BAR>'),
        );
    }

    public function testClassStringMap(): void
    {
        $this->assertSame(
            'class-string-map<T as Foo, T>',
            Type::parseString('class-string-map<T as Foo, T>')->getId(false),
        );
    }

    public function testClassStringMapOf(): void
    {
        $this->assertSame(
            'class-string-map<T as Foo, T>',
            Type::parseString('class-string-map<T of Foo, T>')->getId(false),
        );
    }

    public function testVeryLargeType(): void
    {
        $very_large_type = 'array{a: Closure():(array<array-key, mixed>|null), b?: Closure():array<array-key, mixed>, c?: Closure():array<array-key, mixed>, d?: Closure():array<array-key, mixed>, e?: Closure():(array{f: null|string, g: null|string, h: null|string, i: string, j: mixed, k: mixed, l: mixed, m: mixed, n: bool, o?: array{0: string}}|null), p?: Closure():(array{f: null|string, g: null|string, h: null|string, i: string, j: mixed, k: mixed, l: mixed, m: mixed, n: bool, o?: array{0: string}}|null), q: string, r?: Closure():(array<array-key, mixed>|null), s: array<array-key, mixed>}|null';

        $this->assertSame(
            $very_large_type,
            (string) Type::parseString($very_large_type),
        );
    }

    public function testEnum(): void
    {
        $docblock_type = Type::parseString('( \'foo\\\'with\' | "bar\"bar" | "baz" | "bat\\\\" | \'bang bang\' | 1 | 2 | 3 | 4.5)');

        $resolved_type = new Union([
            new TLiteralString('foo\'with'),
            new TLiteralString('bar"bar'),
            new TLiteralString('baz'),
            new TLiteralString('bat\\'),
            new TLiteralString('bang bang'),
            new TLiteralInt(1),
            new TLiteralInt(2),
            new TLiteralInt(3),
            new TLiteralFloat(4.5),
        ]);

        $this->assertSame($resolved_type->getId(), $docblock_type->getId());
    }

    public function testEmptyString(): void
    {
        $docblock_type = Type::parseString('""|"admin"|"fun"');

        $resolved_type = new Union([
            new TLiteralString(''),
            new TLiteralString('admin'),
            new TLiteralString('fun'),
        ]);

        $this->assertSame($resolved_type->getId(), $docblock_type->getId());

        $docblock_type = Type::parseString('"admin"|""|"fun"');

        $resolved_type = new Union([
            new TLiteralString('admin'),
            new TLiteralString(''),
            new TLiteralString('fun'),
        ]);

        $this->assertSame($resolved_type->getId(), $docblock_type->getId());

        $docblock_type = Type::parseString('"admin"|"fun"|""');

        $resolved_type = new Union([
            new TLiteralString('admin'),
            new TLiteralString('fun'),
            new TLiteralString(''),
        ]);

        $this->assertSame($resolved_type->getId(), $docblock_type->getId());
    }

    public function testEnumWithoutSpaces(): void
    {
        $docblock_type = Type::parseString('\'foo\\\'with\'|"bar\"bar"|"baz"|"bat\\\\"|\'bang bang\'|1|2|3|4.5');

        $resolved_type = new Union([
            new TLiteralString('foo\'with'),
            new TLiteralString('bar"bar'),
            new TLiteralString('baz'),
            new TLiteralString('bat\\'),
            new TLiteralString('bang bang'),
            new TLiteralInt(1),
            new TLiteralInt(2),
            new TLiteralInt(3),
            new TLiteralFloat(4.5),
        ]);

        $this->assertSame($resolved_type->getId(), $docblock_type->getId());
    }

    public function testLongUtf8LiteralString(): void
    {
        $string = "АаБбВвГгДдЕеЁёЖжЗзИиЙйКкЛлМмНнОоПпРрСсТтУуФфХхЦцЧчШшЩщЪъЫыЬьЭэЮюЯя";
        $string .= $string;
        $expected = mb_substr($string, 0, 80);
        $this->assertSame("'$expected...'", Type::parseString("'$string'")->getId());
        $this->assertSame("'$expected...'", Type::parseString("\"$string\"")->getId());
    }

    public function testSingleLiteralString(): void
    {
        $this->assertSame(
            "'var'",
            Type::parseString('"var"')->getId(),
        );
    }

    public function testEmptyArrayShape(): void
    {
        $this->assertSame(
            'array<never, never>',
            (string)Type::parseString('array{}'),
        );
    }

    public function testSingleLiteralInt(): void
    {
        $this->assertSame(
            '6',
            Type::parseString('6')->getId(),
        );
    }

    public function testSingleLiteralIntWithSeparators(): void
    {
        $this->assertSame('10', Type::parseString('1_0')->getId());
    }

    public function testIntRangeWithSeparators(): void
    {
        $this->assertSame('int<10, 20>', Type::parseString('int<1_0, 2_0>')->getId());
    }

    public function testLiteralIntUnionWithSeparators(): void
    {
        $this->assertSame('10|20', Type::parseString('1_0|2_0')->getId());
    }

    public function testIntMaskWithIntsWithSeparators(): void
    {
        $this->assertSame('int-mask-verifier<10,20>', Type::parseString('int-mask<1_0, 2_0>')->getId());
    }

    public function testSingleLiteralFloat(): void
    {
        $this->assertSame(
            'float(6.315)',
            Type::parseString('6.315')->getId(),
        );
    }

    public function testEnumWithClassConstants(): void
    {
        $docblock_type = Type::parseString('("baz" | One2::TWO_THREE | Foo::BAR_BAR | Bat\Bar::BAZ_BAM)');

        $resolved_type = new Union([
            new TLiteralString('baz'),
            new TClassConstant('One2', 'TWO_THREE'),
            new TClassConstant('Foo', 'BAR_BAR'),
            new TClassConstant('Bat\\Bar', 'BAZ_BAM'),
        ]);

        $this->assertSame($resolved_type->getId(), $docblock_type->getId());
    }

    public function testIntMaskWithInts(): void
    {
        $docblock_type = Type::parseString('int-mask<0, 1, 2, 4>');

        $this->assertSame('int-mask-verifier<0,1,2,4>', $docblock_type->getId());

        $docblock_type = Type::parseString('int-mask<1, 2, 4>');

        $this->assertSame('int-mask-verifier<1,2,4>', $docblock_type->getId());

        $docblock_type = Type::parseString('int-mask<1, 4>');

        $this->assertSame('int-mask-verifier<1,4>', $docblock_type->getId());

        $docblock_type = Type::parseString('int-mask<PREG_PATTERN_ORDER, PREG_OFFSET_CAPTURE, PREG_UNMATCHED_AS_NULL>');

        $this->assertSame('int-mask-verifier<1,256,512>', $docblock_type->getId());
    }

    public function testIntMaskWithClassConstant(): void
    {
        $docblock_type = Type::parseString('int-mask<0, A::FOO, A::BAR>');

        $this->assertSame('int-mask<0, A::FOO, A::BAR>', $docblock_type->getId());
    }

    public function testIntMaskWithInvalidClassConstant(): void
    {
        $this->expectException(TypeParseTreeException::class);

        Type::parseString('int-mask<A::*>');
    }

    public function testIntMaskOfWithValidClassConstant(): void
    {
        $docblock_type = Type::parseString('int-mask-of<A::*>');

        $this->assertSame('int-mask-of<class-constant(A::*)>', $docblock_type->getId());
    }

    public function testIntMaskOfWithInvalidClassConstant(): void
    {
        $this->expectException(TypeParseTreeException::class);

        Type::parseString('int-mask-of<A::FOO>');
    }

    public function testIntMaskOfWithValidValueOf(): void
    {
        $docblock_type = Type::parseString('int-mask-of<value-of<A::FOO>>');

        $this->assertSame('int-mask-of<value-of<A::FOO>>', $docblock_type->getId());
    }

    public function testUnionOfClassStringAndClassStringWithIntersection(): void
    {
        $this->assertSame(
            'class-string<IFoo>',
            (string) Type::parseString('class-string<IFoo>|class-string<IFoo&IBar>'),
        );
    }

    public function testReflectionTypeParse(): void
    {
        if (!function_exists('Psalm\Tests\someFunction')) {
            /** @psalm-suppress UnusedParam */
            function someFunction(string $param, array $param2, ?int $param3 = null): string
            {
                return 'hello';
            }
        }

        $reflectionFunc = new ReflectionFunction('Psalm\Tests\someFunction');
        $reflectionParams = $reflectionFunc->getParameters();

        $this->assertSame(
            'string',
            (string) Codebase::getPsalmTypeFromReflection($reflectionParams[0]->getType()),
        );

        $this->assertSame(
            'array<array-key, mixed>',
            (string) Codebase::getPsalmTypeFromReflection($reflectionParams[1]->getType()),
        );

        $this->assertSame(
            'int|null',
            (string) Codebase::getPsalmTypeFromReflection($reflectionParams[2]->getType()),
        );

        $this->assertSame(
            'string',
            (string) Codebase::getPsalmTypeFromReflection($reflectionFunc->getReturnType()),
        );
    }

    public function testValidCallMapType(): void
    {
        $callmap_types = InternalCallMapHandler::getCallMap();

        foreach ($callmap_types as $signature) {
            $return_type = $signature[0] ?? null;
            $param_type_1 = $signature[1] ?? null;
            $param_type_2 = $signature[2] ?? null;
            $param_type_3 = $signature[3] ?? null;
            $param_type_4 = $signature[4] ?? null;

            if ($return_type && $return_type !== 'void') {
                if (stripos($return_type, 'oci-') !== false) {
                    continue;
                }

                try {
                    Type::parseString($return_type);
                } catch (TypeParseTreeException $e) {
                    self::assertTrue(false, $e . ' | ' . print_r($signature, true));
                }
            }

            if ($param_type_1 && $param_type_1 !== 'mixed') {
                if (stripos($param_type_1, 'oci-') !== false) {
                    continue;
                }

                try {
                    Type::parseString($param_type_1);
                } catch (TypeParseTreeException $e) {
                    self::assertTrue(false, $e . ' | ' . print_r($signature, true));
                }
            }

            if ($param_type_2 && $param_type_2 !== 'mixed') {
                try {
                    Type::parseString($param_type_2);
                } catch (TypeParseTreeException $e) {
                    self::assertTrue(false, $e . ' | ' . print_r($signature, true));
                }
            }

            if ($param_type_3 && $param_type_3 !== 'mixed') {
                try {
                    Type::parseString($param_type_3);
                } catch (TypeParseTreeException $e) {
                    self::assertTrue(false, $e . ' | ' . print_r($signature, true));
                }
            }

            if ($param_type_4 && $param_type_4 !== 'mixed') {
                try {
                    Type::parseString($param_type_4);
                } catch (TypeParseTreeException $e) {
                    self::assertTrue(false, $e . ' | ' . print_r($signature, true));
                }
            }
        }
    }
}
