<?php
namespace Psalm\Tests;

use function function_exists;
use function print_r;

use Psalm\Internal\RuntimeCaches;
use Psalm\Type;
use function stripos;

class TypeParseTest extends TestCase
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
     * @return void
     */
    public function testThisToStatic(): void
    {
        $this->assertSame('static', (string) Type::parseString('$this'));
    }

    /**
     * @return void
     */
    public function testThisToStaticUnion(): void
    {
        $this->assertSame('A|static', (string) Type::parseString('$this|A'));
    }

    /**
     * @return void
     */
    public function testIntOrString(): void
    {
        $this->assertSame('int|string', (string) Type::parseString('int|string'));
    }

    /**
     * @return void
     */
    public function testBracketedIntOrString(): void
    {
        $this->assertSame('int|string', (string) Type::parseString('(int|string)'));
    }

    /**
     * @return void
     */
    public function testBoolOrIntOrString(): void
    {
        $this->assertSame('bool|int|string', (string) Type::parseString('bool|int|string'));
    }

    /**
     * @return void
     */
    public function testNullable(): void
    {
        $this->assertSame('null|string', (string) Type::parseString('?string'));
    }

    /**
     * @return void
     */
    public function testNullableUnion(): void
    {
        $this->assertSame('int|null|string', (string) Type::parseString('?(string|int)'));
    }

    /**
     * @return void
     */
    public function testNullableFullyQualified(): void
    {
        $this->assertSame('null|stdClass', (string) Type::parseString('?\\stdClass'));
    }

    /**
     * @return void
     */
    public function testNullableOrNullable(): void
    {
        $this->assertSame('int|null|string', (string) Type::parseString('?string|?int'));
    }

    /**
     * @return void
     */
    public function testBadNullableCharacterInUnion(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('int|array|?');
    }

    /**
     * @return void
     */
    public function testBadNullableCharacterInUnionWithFollowing(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('int|array|?|bool');
    }

    /**
     * @return void
     */
    public function testArrayWithClosingBracket(): void
    {
        $this->assertSame('array<int, int>', (string) Type::parseString('array<int, int>'));
    }

    /**
     * @return void
     */
    public function testArrayWithoutClosingBracket(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('array<int, int');
    }

    /**
     * @return void
     */
    public function testArrayWithSingleArg(): void
    {
        $this->assertSame('array<array-key, int>', (string) Type::parseString('array<int>'));
    }

    /**
     * @return void
     */
    public function testArrayWithNestedSingleArg(): void
    {
        $this->assertSame('array<array-key, array<array-key, int>>', (string) Type::parseString('array<array<int>>'));
    }

    /**
     * @return void
     */
    public function testArrayWithUnion(): void
    {
        $this->assertSame('array<int|string, string>', (string) Type::parseString('array<int|string, string>'));
    }

    /**
     * @return void
     */
    public function testNonEmptyArrray(): void
    {
        $this->assertSame('non-empty-array<array-key, int>', (string) Type::parseString('non-empty-array<int>'));
    }

    /**
     * @return void
     */
    public function testGeneric(): void
    {
        $this->assertSame('B<int>', (string) Type::parseString('B<int>'));
    }

    /**
     * @return void
     */
    public function testIntersection(): void
    {
        $this->assertSame('I1&I2&I3', (string) Type::parseString('I1&I2&I3'));
    }

    /**
     * @return void
     */
    public function testIntersectionOrNull(): void
    {
        $this->assertSame('I1&I2|null', (string) Type::parseString('I1&I2|null'));
    }

    /**
     * @return void
     */
    public function testNullOrIntersection(): void
    {
        $this->assertSame('I1&I2|null', (string) Type::parseString('null|I1&I2'));
    }

    /**
     * @return void
     */
    public function testInteratorAndTraversable(): void
    {
        $this->assertSame('Iterator<mixed, int>&Traversable', (string) Type::parseString('Iterator<int>&Traversable'));
    }

    /**
     * @return void
     */
    public function testStaticAndStatic(): void
    {
        $this->assertSame('static', (string) Type::parseString('static&static'));
    }

    /**
     * @return void
     */
    public function testTraversableAndIteratorOrNull(): void
    {
        $this->assertSame(
            'Traversable&Iterator<mixed, int>|null',
            (string) Type::parseString('Traversable&Iterator<int>|null')
        );
    }

    /**
     * @return void
     */
    public function testIteratorAndTraversableOrNull(): void
    {
        $this->assertSame(
            'Iterator<mixed, int>&Traversable|null',
            (string) Type::parseString('Iterator<mixed, int>&Traversable|null')
        );
    }

    /**
     * @return void
     */
    public function testIntersectionAfterGeneric(): void
    {
        $this->assertSame('Countable&iterable<mixed, int>&I', (string) Type::parseString('Countable&iterable<int>&I'));
    }

    /**
     * @return void
     */
    public function testIntersectionOfIterables(): void
    {
        $this->assertSame('iterable<mixed, A>&iterable<mixed, B>', (string) Type::parseString('iterable<A>&iterable<B>'));
    }

    /**
     * @return void
     */
    public function testIntersectionOfTKeyedArray(): void
    {
        $this->assertSame('array{a: int, b: int}', (string) Type::parseString('array{a: int}&array{b: int}'));
    }

    /**
     * @return void
     */
    public function testIntersectionOfTKeyedArrayWithMergedProperties(): void
    {
        $this->assertSame('array{a: int}', (string) Type::parseString('array{a: int}&array{a: mixed}'));
    }

    /**
     * @return void
     */
    public function testIntersectionOfTKeyedArrayWithPossiblyUndefinedMergedProperties(): void
    {
        $this->assertSame('array{a: int}', (string) Type::parseString('array{a: int}&array{a?: int}'));
    }

    /**
     * @return void
     */
    public function testIntersectionOfTKeyedArrayWithConflictingProperties(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('array{a: string}&array{a: int}');
    }

    /**
     * @return void
     */
    public function testUnionOfIntersectionOfTKeyedArray(): void
    {
        $this->assertSame('array{a: int|string, b?: int}', (string) Type::parseString('array{a: int}|array{a: string}&array{b: int}'));
        $this->assertSame('array{a: int|string, b?: int}', (string) Type::parseString('array{b: int}&array{a: string}|array{a: int}'));
    }

    /**
     * @return void
     */
    public function testIntersectionOfUnionOfTKeyedArray(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('array{a: int}&array{a: string}|array{b: int}');
    }

    /**
     * @return void
     */
    public function testIntersectionOfTKeyedArrayAndObject(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('array{a: int}&T1');
    }

    public function testIterableContainingTKeyedArray() : void
    {
        $this->assertSame('iterable<string, array{int}>', Type::parseString('iterable<string, array{int}>')->getId());
    }

    /**
     * @return void
     */
    public function testPhpDocSimpleArray(): void
    {
        $this->assertSame('array<array-key, A>', (string) Type::parseString('A[]'));
    }

    /**
     * @return void
     */
    public function testPhpDocUnionArray(): void
    {
        $this->assertSame('array<array-key, A|B>', (string) Type::parseString('(A|B)[]'));
    }

    /**
     * @return void
     */
    public function testPhpDocMultiDimensionalArray(): void
    {
        $this->assertSame('array<array-key, array<array-key, A>>', (string) Type::parseString('A[][]'));
    }

    /**
     * @return void
     */
    public function testPhpDocMultidimensionalUnionArray(): void
    {
        $this->assertSame('array<array-key, array<array-key, A|B>>', (string) Type::parseString('(A|B)[][]'));
    }

    /**
     * @return void
     */
    public function testPhpDocTKeyedArray(): void
    {
        $this->assertSame(
            'array<array-key, array{b: bool, d: string}>',
            (string) Type::parseString('array{b: bool, d: string}[]')
        );
    }

    /**
     * @return void
     */
    public function testPhpDocUnionOfArrays(): void
    {
        $this->assertSame('array<array-key, A|B>', (string) Type::parseString('A[]|B[]'));
    }

    /**
     * @return void
     */
    public function testPhpDocUnionOfArraysOrObject(): void
    {
        $this->assertSame('C|array<array-key, A|B>', (string) Type::parseString('A[]|B[]|C'));
    }

    /**
     * @return void
     */
    public function testPsalmOnlyAtomic(): void
    {
        $this->assertSame('class-string', (string) Type::parseString('class-string'));
    }

    /**
     * @return void
     */
    public function testParameterizedClassString(): void
    {
        $this->assertSame('class-string<A>', (string) Type::parseString('class-string<A>'));
    }

    /**
     * @return void
     */
    public function testParameterizedClassStringUnion(): void
    {
        $this->assertSame('class-string<A>|class-string<B>', (string) Type::parseString('class-string<A>|class-string<B>'));
    }

    /**
     * @return void
     */
    public function testInvalidType(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('array(A)');
    }

    /**
     * @return void
     */
    public function testBracketedUnionAndIntersection(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('(A|B)&C');
    }

    /**
     * @return void
     */
    public function testBracketInUnion(): void
    {
        Type::parseString('null|(scalar|array|object)');
    }

    /**
     * @return void
     */
    public function testTKeyedArrayWithSimpleArgs(): void
    {
        $this->assertSame('array{a: int, b: string}', (string) Type:: parseString('array{a: int, b: string}'));
    }

    /**
     * @return void
     */
    public function testTKeyedArrayWithSpace(): void
    {
        $this->assertSame('array{\'a \': int, \'b  \': string}', (string) Type:: parseString('array{\'a \': int, \'b  \': string}'));
    }

    /**
     * @return void
     */
    public function testTKeyedArrayWithQuotedKeys(): void
    {
        $this->assertSame('array{\'\\"\': int, \'\\\'\': string}', (string) Type:: parseString('array{\'"\': int, \'\\\'\': string}'));
        $this->assertSame('array{\'\\"\': int, \'\\\'\': string}', (string) Type:: parseString('array{"\\"": int, "\\\'": string}'));
    }

    /**
     * @return void
     */
    public function testTKeyedArrayWithClassConstantKey(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('array{self::FOO: string}');
    }

    /**
     * @return void
     */
    public function testTKeyedArrayWithQuotedClassConstantKey(): void
    {
        $this->assertSame('array{\'self::FOO\': string}', (string) Type:: parseString('array{"self::FOO": string}'));
    }

    /**
     * @return void
     */
    public function testTKeyedArrayWithoutClosingBracket(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('array{a: int, b: string');
    }

    /**
     * @return void
     */
    public function testTKeyedArrayInType(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('array{a:[]}');
    }

    /**
     * @return void
     */
    public function testObjectWithSimpleArgs(): void
    {
        $this->assertSame('object{a:int, b:string}', (string) Type::parseString('object{a:int, b:string}'));
    }

    /**
     * @return void
     */
    public function testObjectWithDollarArgs(): void
    {
        $this->assertSame('object{a:int, $b:string}', (string) Type::parseString('object{a:int, $b:string}'));
    }

    /**
     * @return void
     */
    public function testTKeyedArrayWithUnionArgs(): void
    {
        $this->assertSame(
            'array{a: int|string, b: string}',
            (string) Type::parseString('array{a: int|string, b: string}')
        );
    }

    /**
     * @return void
     */
    public function testTKeyedArrayWithGenericArgs(): void
    {
        $this->assertSame(
            'array{a: array<int, int|string>, b: string}',
            (string) Type::parseString('array{a: array<int, string|int>, b: string}')
        );
    }

    /**
     * @return void
     */
    public function testTKeyedArrayWithIntKeysAndUnionArgs(): void
    {
        $this->assertSame(
            'array{null|stdClass}',
            (string)Type::parseString('array{stdClass|null}')
        );
    }

    /**
     * @return void
     */
    public function testTKeyedArrayWithIntKeysAndGenericArgs(): void
    {
        $this->assertSame(
            'array{array<array-key, mixed>}',
            (string)Type::parseString('array{array}')
        );

        $this->assertSame(
            'array{array<int, string>}',
            (string)Type::parseString('array{array<int, string>}')
        );
    }

    /**
     * @return void
     */
    public function testTKeyedArrayOptional(): void
    {
        $this->assertSame(
            'array{a: int, b?: int}',
            (string)Type::parseString('array{a: int, b?: int}')
        );
    }

    /**
     * @return void
     */
    public function testSimpleCallable(): void
    {
        $this->assertSame(
            'callable(int, string):void',
            (string)Type::parseString('callable(int, string) : void')
        );
    }

    /**
     * @return void
     */
    public function testCallableWithoutClosingBracket(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('callable(int, string');
    }

    /**
     * @return void
     */
    public function testCallableWithParamNames(): void
    {
        $this->assertSame(
            'callable(int, string):void',
            (string)Type::parseString('callable(int $foo, string $bar) : void')
        );
    }

    /**
     * @return void
     */
    public function testCallableReturningIntersection(): void
    {
        $this->assertSame(
            'callable(int, string):I1&I2',
            (string)Type::parseString('callable(int, string) : (I1&I2)')
        );
    }

    /**
     * @return void
     */
    public function testEmptyCallable(): void
    {
        $this->assertSame(
            'callable():void',
            (string)Type::parseString('callable() : void')
        );
    }

    /**
     * @return void
     */
    public function testCallableWithUnionLastType(): void
    {
        $this->assertSame(
            'callable(int, int|string):void',
            (string)Type::parseString('callable(int, int|string) : void')
        );
    }

    /**
     * @return void
     */
    public function testCallableWithVariadic(): void
    {
        $this->assertSame(
            'callable(int, string...):void',
            (string)Type::parseString('callable(int, string...) : void')
        );
    }

    /**
     * @return void
     */
    public function testCallableThatReturnsACallable(): void
    {
        $this->assertSame(
            'callable():callable():string',
            (string)Type::parseString('callable() : callable() : string')
        );
    }

    /**
     * @return void
     */
    public function testCallableThatReturnsACallableThatReturnsACallable(): void
    {
        $this->assertSame(
            'callable():callable():callable():string',
            (string)Type::parseString('callable() : callable() : callable() : string')
        );
    }

    /**
     * @return void
     */
    public function testCallableOrInt(): void
    {
        $this->assertSame(
            'callable(string):void|int',
            (string)Type::parseString('callable(string):void|int')
        );
    }

    /**
     * @return void
     */
    public function testCallableWithGoodVariadic(): void
    {
        Type::parseString('callable(int, string...) : void');
        Type::parseString('callable(int,string...) : void');
    }

    /**
     * @return void
     */
    public function testCallableWithSpreadBefore(): void
    {
        $this->assertSame(
            'callable(int, string...):void',
            (string)Type::parseString('callable(int, ...string):void')
        );
    }

    /**
     * @return void
     */
    public function testConditionalTypeWithSpaces(): void
    {
        $this->assertSame(
            '(T is string ? string : int)',
            (string) Type::parseString('(T is string ? string : int)', null, ['T' => ['' => [Type::getArray()]]])
        );
    }

    /**
     * @return void
     */
    public function testConditionalTypeWithUnion(): void
    {
        $this->assertSame(
            '(T is string|true ? int|string : int)',
            (string) Type::parseString('(T is "hello"|true ? string|int : int)', null, ['T' => ['' => [Type::getArray()]]])
        );
    }

    /**
     * @return void
     */
    public function testConditionalTypeWithTKeyedArray(): void
    {
        $this->assertSame(
            '(T is array{a: string} ? string : int)',
            (string) Type::parseString('(T is array{a: string} ? string : int)', null, ['T' => ['' => [Type::getArray()]]])
        );
    }

    /**
     * @return void
     */
    public function testConditionalTypeWithGenericIs(): void
    {
        $this->assertSame(
            '(T is array<array-key, string> ? string : int)',
            (string) Type::parseString('(T is array<string> ? string : int)', null, ['T' => ['' => [Type::getArray()]]])
        );
    }

    /**
     * @return void
     */
    public function testConditionalTypeWithIntersection(): void
    {
        $this->assertSame(
            '(T is A&B ? string : int)',
            (string) Type::parseString('(T is A&B ? string : int)', null, ['T' => ['' => [Type::getArray()]]])
        );
    }

    /**
     * @return void
     */
    public function testConditionalTypeWithoutSpaces(): void
    {
        $this->assertSame(
            '(T is string ? string : int)',
            (string) Type::parseString('(T is string?string:int)', null, ['T' => ['' => [Type::getArray()]]])
        );
    }

    /**
     * @return void
     */
    public function testConditionalTypeWithCallableElseBool(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('(T is string ? callable() : bool)', null, ['T' => ['' => [Type::getArray()]]]);
    }

    /**
     * @return void
     */
    public function testConditionalTypeWithCallableReturningBoolElseBool(): void
    {
        $this->assertSame(
            '(T is string ? callable():bool : bool)',
            (string) Type::parseString('(T is string ? (callable() : bool) : bool)', null, ['T' => ['' => [Type::getArray()]]])
        );
    }

    public function testConditionalTypeWithGenerics() : void
    {
        $this->assertSame(
            '(T is string ? string : array<string, string>)',
            (string) Type::parseString(
                '(T is string ? string : array<string, string>)',
                null,
                ['T' => ['' => [Type::getArray()]]]
            )
        );
    }

    public function testConditionalTypeWithCallableBracketed() : void
    {
        $this->assertSame(
            '(T is string ? callable(string, string):string : callable(mixed...):mixed)',
            (string) Type::parseString(
                '(T is string ? (callable(string, string):string) : (callable(mixed...):mixed))',
                null,
                ['T' => ['' => [Type::getArray()]]]
            )
        );
    }

    public function testConditionalTypeWithCallableNotBracketed() : void
    {
        $this->assertSame(
            '(T is string ? callable(string, string):string : callable(mixed...):mixed)',
            (string) Type::parseString(
                '(T is string ? callable(string, string):string : callable(mixed...):mixed)',
                null,
                ['T' => ['' => [Type::getArray()]]]
            )
        );
    }

    /**
     * @return void
     */
    public function testCallableWithTrailingColon(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('callable(int):');
    }

    /**
     * @return void
     */
    public function testCallableWithAnotherBadVariadic(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('callable(int, string..) : void');
    }

    /**
     * @return void
     */
    public function testCallableWithVariadicAndDefault(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('callable(int, string...=) : void');
    }

    /**
     * @return void
     */
    public function testBadVariadic(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('string...');
    }

    /**
     * @return void
     */
    public function testBadFullStop(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('string.');
    }

    /**
     * @return void
     */
    public function testBadSemicolon(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('string;');
    }

    /**
     * @return void
     */
    public function testBadGenericString(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('string<T>');
    }

    /**
     * @return void
     */
    public function testBadAmpersand(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('&array');
    }

    /**
     * @return void
     */
    public function testBadColon(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString(':array');
    }

    /**
     * @return void
     */
    public function testBadBrackets(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('max(a)');
    }

    /**
     * @return void
     */
    public function testMoreBadBrackets(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('max(a):void');
    }

    /**
     * @return void
     */
    public function testGeneratorWithWBadBrackets(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('Generator{string, A}');
    }

    /**
     * @return void
     */
    public function testBadEquals(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('=array');
    }

    /**
     * @return void
     */
    public function testBadBar(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('|array');
    }

    /**
     * @return void
     */
    public function testBadColonDash(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('array|string:-');
    }

    /**
     * @return void
     */
    public function testDoubleBar(): void
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('PDO||Closure|numeric');
    }

    /**
     * @return void
     */
    public function testCallableWithDefault(): void
    {
        $this->assertSame(
            'callable(int, string=):void',
            (string)Type::parseString('callable(int, string=) : void')
        );
    }

    /**
     * @return void
     */
    public function testNestedCallable(): void
    {
        $this->assertSame(
            'callable(callable(A):B):C',
            (string)Type::parseString('callable(callable(A):B):C')
        );
    }

    /**
     * @return void
     */
    public function testCallableWithoutReturn(): void
    {
        $this->assertSame(
            'callable(int, string)',
            (string)Type::parseString('callable(int, string)')
        );
    }

    /**
     * @return void
     */
    public function testCombineLiteralStringWithClassString(): void
    {
        $this->assertSame(
            'class-string|string(array)',
            Type::parseString('"array"|class-string')->getId()
        );
    }

    /**
     * @return void
     */
    public function testCombineLiteralClassStringWithClassString(): void
    {
        $this->assertSame(
            'class-string',
            Type::parseString('A::class|class-string')->getId()
        );
    }

    /**
     * @return void
     */
    public function testKeyOfClassConstant(): void
    {
        $this->assertSame(
            'key-of<Foo\Baz::BAR>',
            (string)Type::parseString('key-of<Foo\Baz::BAR>')
        );
    }

    /**
     * @return void
     */
    public function testKeyOfTemplate(): void
    {
        $this->assertSame(
            'key-of<T>',
            (string)Type::parseString('key-of<T>', null, ['T' => ['' => [Type::getArray()]]])
        );
    }

    /**
     * @return void
     */
    public function testIndexedAccess(): void
    {
        $this->assertSame(
            'T[K]',
            (string) Type::parseString(
                'T[K]',
                null,
                [
                    'T' => ['' => [Type::getArray()]],
                    'K' => ['' => [new Type\Union([
                        new Type\Atomic\TTemplateKeyOf('T', 'fn-foo', Type::getMixed())
                    ])]],
                ]
            )
        );
    }

    /**
     * @return void
     */
    public function testValueOfClassConstant(): void
    {
        $this->assertSame(
            'value-of<Foo\Baz::BAR>',
            (string)Type::parseString('value-of<Foo\Baz::BAR>')
        );
    }

    public function testClassStringMap() : void
    {
        $this->assertSame(
            'class-string-map<T as Foo, T>',
            (string)Type::parseString('class-string-map<T as Foo, T>')
        );
    }

    /**
     * @return void
     */
    public function testVeryLargeType(): void
    {
        $very_large_type = 'array{a: Closure():(array<array-key, mixed>|null), b?: Closure():array<array-key, mixed>, c?: Closure():array<array-key, mixed>, d?: Closure():array<array-key, mixed>, e?: Closure():(array{f: null|string, g: null|string, h: null|string, i: string, j: mixed, k: mixed, l: mixed, m: mixed, n: bool, o?: array{0: string}}|null), p?: Closure():(array{f: null|string, g: null|string, h: null|string, i: string, j: mixed, k: mixed, l: mixed, m: mixed, n: bool, o?: array{0: string}}|null), q: string, r?: Closure():(array<array-key, mixed>|null), s: array<array-key, mixed>}|null';

        $this->assertSame(
            $very_large_type,
            (string) Type::parseString($very_large_type)
        );
    }

    /**
     * @return void
     */
    public function testEnum(): void
    {
        $docblock_type = Type::parseString('( \'foo\\\'with\' | "bar\"bar" | "baz" | "bat\\\\" | \'bang bang\' | 1 | 2 | 3 | 4.5)');

        $resolved_type = new Type\Union([
            new Type\Atomic\TLiteralString('foo\'with'),
            new Type\Atomic\TLiteralString('bar"bar'),
            new Type\Atomic\TLiteralString('baz'),
            new Type\Atomic\TLiteralString('bat\\'),
            new Type\Atomic\TLiteralString('bang bang'),
            new Type\Atomic\TLiteralInt(1),
            new Type\Atomic\TLiteralInt(2),
            new Type\Atomic\TLiteralInt(3),
            new Type\Atomic\TLiteralFloat(4.5),
        ]);

        $this->assertSame($resolved_type->getId(), $docblock_type->getId());
    }

    public function testEmptyString() : void
    {
        $docblock_type = Type::parseString('""|"admin"|"fun"');

        $resolved_type = new Type\Union([
            new Type\Atomic\TLiteralString(''),
            new Type\Atomic\TLiteralString('admin'),
            new Type\Atomic\TLiteralString('fun'),
        ]);

        $this->assertSame($resolved_type->getId(), $docblock_type->getId());

        $docblock_type = Type::parseString('"admin"|""|"fun"');

        $resolved_type = new Type\Union([
            new Type\Atomic\TLiteralString('admin'),
            new Type\Atomic\TLiteralString(''),
            new Type\Atomic\TLiteralString('fun'),
        ]);

        $this->assertSame($resolved_type->getId(), $docblock_type->getId());

        $docblock_type = Type::parseString('"admin"|"fun"|""');

        $resolved_type = new Type\Union([
            new Type\Atomic\TLiteralString('admin'),
            new Type\Atomic\TLiteralString('fun'),
            new Type\Atomic\TLiteralString(''),
        ]);

        $this->assertSame($resolved_type->getId(), $docblock_type->getId());
    }

    /**
     * @return void
     */
    public function testEnumWithoutSpaces(): void
    {
        $docblock_type = Type::parseString('\'foo\\\'with\'|"bar\"bar"|"baz"|"bat\\\\"|\'bang bang\'|1|2|3|4.5');

        $resolved_type = new Type\Union([
            new Type\Atomic\TLiteralString('foo\'with'),
            new Type\Atomic\TLiteralString('bar"bar'),
            new Type\Atomic\TLiteralString('baz'),
            new Type\Atomic\TLiteralString('bat\\'),
            new Type\Atomic\TLiteralString('bang bang'),
            new Type\Atomic\TLiteralInt(1),
            new Type\Atomic\TLiteralInt(2),
            new Type\Atomic\TLiteralInt(3),
            new Type\Atomic\TLiteralFloat(4.5),
        ]);

        $this->assertSame($resolved_type->getId(), $docblock_type->getId());
    }

    /**
     * @return void
     */
    public function testSingleLiteralString(): void
    {
        $this->assertSame(
            'string',
            (string)Type::parseString('"var"')
        );
    }

    /**
     * @return void
     */
    public function testSingleLiteralInt(): void
    {
        $this->assertSame(
            'int',
            (string)Type::parseString('6')
        );
    }

    /**
     * @return void
     */
    public function testSingleLiteralFloat(): void
    {
        $this->assertSame(
            'float',
            (string)Type::parseString('6.315')
        );
    }

    /**
     * @return void
     */
    public function testEnumWithClassConstants(): void
    {
        $docblock_type = Type::parseString('("baz" | One2::TWO_THREE | Foo::BAR_BAR | Bat\Bar::BAZ_BAM)');

        $resolved_type = new Type\Union([
            new Type\Atomic\TLiteralString('baz'),
            new Type\Atomic\TScalarClassConstant('One2', 'TWO_THREE'),
            new Type\Atomic\TScalarClassConstant('Foo', 'BAR_BAR'),
            new Type\Atomic\TScalarClassConstant('Bat\\Bar', 'BAZ_BAM'),
        ]);

        $this->assertSame($resolved_type->getId(), $docblock_type->getId());
    }

    /**
     * @return void
     */
    public function testReflectionTypeParse(): void
    {
        if (!function_exists('Psalm\Tests\someFunction')) {
            /** @psalm-suppress UnusedParam */
            function someFunction(string $param, array $param2, ?int $param3 = null) : string
            {
                return 'hello';
            }
        }

        $reflectionFunc = new \ReflectionFunction('Psalm\Tests\someFunction');
        $reflectionParams = $reflectionFunc->getParameters();

        $this->assertSame(
            'string',
            (string) \Psalm\Codebase::getPsalmTypeFromReflection($reflectionParams[0]->getType())
        );

        $this->assertSame(
            'array<array-key, mixed>',
            (string) \Psalm\Codebase::getPsalmTypeFromReflection($reflectionParams[1]->getType())
        );

        $this->assertSame(
            'int|null',
            (string) \Psalm\Codebase::getPsalmTypeFromReflection($reflectionParams[2]->getType())
        );

        $this->assertSame(
            'string',
            (string) \Psalm\Codebase::getPsalmTypeFromReflection($reflectionFunc->getReturnType())
        );
    }

    /**
     * @return void
     */
    public function testValidCallMapType(): void
    {
        $callmap_types = \Psalm\Internal\Codebase\InternalCallMapHandler::getCallMap();

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
                    \Psalm\Type::parseString($return_type);
                } catch (\Psalm\Exception\TypeParseTreeException $e) {
                    self::assertTrue(false, $e . ' | ' . print_r($signature, true));
                }
            }

            if ($param_type_1 && $param_type_1 !== 'mixed') {
                if (stripos($param_type_1, 'oci-') !== false) {
                    continue;
                }

                try {
                    \Psalm\Type::parseString($param_type_1);
                } catch (\Psalm\Exception\TypeParseTreeException $e) {
                    self::assertTrue(false, $e . ' | ' . print_r($signature, true));
                }
            }

            if ($param_type_2 && $param_type_2 !== 'mixed') {
                try {
                    \Psalm\Type::parseString($param_type_2);
                } catch (\Psalm\Exception\TypeParseTreeException $e) {
                    self::assertTrue(false, $e . ' | ' . print_r($signature, true));
                }
            }

            if ($param_type_3 && $param_type_3 !== 'mixed') {
                try {
                    \Psalm\Type::parseString($param_type_3);
                } catch (\Psalm\Exception\TypeParseTreeException $e) {
                    self::assertTrue(false, $e . ' | ' . print_r($signature, true));
                }
            }

            if ($param_type_4 && $param_type_4 !== 'mixed') {
                try {
                    \Psalm\Type::parseString($param_type_4);
                } catch (\Psalm\Exception\TypeParseTreeException $e) {
                    self::assertTrue(false, $e . ' | ' . print_r($signature, true));
                }
            }
        }
    }
}
