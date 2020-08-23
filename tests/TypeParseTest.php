<?php
namespace Psalm\Tests;

use function function_exists;
use function print_r;

use Psalm\Internal\RuntimeCaches;
use Psalm\Type;
use function stripos;

class TypeParseTest extends TestCase
{
    /**
     * @return void
     */
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
    public function testThisToStatic()
    {
        $this->assertSame('static', (string) Type::parseString('$this'));
    }

    /**
     * @return void
     */
    public function testThisToStaticUnion()
    {
        $this->assertSame('A|static', (string) Type::parseString('$this|A'));
    }

    /**
     * @return void
     */
    public function testIntOrString()
    {
        $this->assertSame('int|string', (string) Type::parseString('int|string'));
    }

    /**
     * @return void
     */
    public function testBracketedIntOrString()
    {
        $this->assertSame('int|string', (string) Type::parseString('(int|string)'));
    }

    /**
     * @return void
     */
    public function testBoolOrIntOrString()
    {
        $this->assertSame('bool|int|string', (string) Type::parseString('bool|int|string'));
    }

    /**
     * @return void
     */
    public function testNullable()
    {
        $this->assertSame('null|string', (string) Type::parseString('?string'));
    }

    /**
     * @return void
     */
    public function testNullableUnion()
    {
        $this->assertSame('int|null|string', (string) Type::parseString('?(string|int)'));
    }

    /**
     * @return void
     */
    public function testNullableFullyQualified()
    {
        $this->assertSame('null|stdClass', (string) Type::parseString('?\\stdClass'));
    }

    /**
     * @return void
     */
    public function testNullableOrNullable()
    {
        $this->assertSame('int|null|string', (string) Type::parseString('?string|?int'));
    }

    /**
     * @return void
     */
    public function testBadNullableCharacterInUnion()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('int|array|?');
    }

    /**
     * @return void
     */
    public function testBadNullableCharacterInUnionWithFollowing()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('int|array|?|bool');
    }

    /**
     * @return void
     */
    public function testArrayWithClosingBracket()
    {
        $this->assertSame('array<int, int>', (string) Type::parseString('array<int, int>'));
    }

    /**
     * @return void
     */
    public function testArrayWithoutClosingBracket()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('array<int, int');
    }

    /**
     * @return void
     */
    public function testArrayWithSingleArg()
    {
        $this->assertSame('array<array-key, int>', (string) Type::parseString('array<int>'));
    }

    /**
     * @return void
     */
    public function testArrayWithNestedSingleArg()
    {
        $this->assertSame('array<array-key, array<array-key, int>>', (string) Type::parseString('array<array<int>>'));
    }

    /**
     * @return void
     */
    public function testArrayWithUnion()
    {
        $this->assertSame('array<int|string, string>', (string) Type::parseString('array<int|string, string>'));
    }

    /**
     * @return void
     */
    public function testNonEmptyArrray()
    {
        $this->assertSame('non-empty-array<array-key, int>', (string) Type::parseString('non-empty-array<int>'));
    }

    /**
     * @return void
     */
    public function testGeneric()
    {
        $this->assertSame('B<int>', (string) Type::parseString('B<int>'));
    }

    /**
     * @return void
     */
    public function testIntersection()
    {
        $this->assertSame('I1&I2&I3', (string) Type::parseString('I1&I2&I3'));
    }

    /**
     * @return void
     */
    public function testIntersectionOrNull()
    {
        $this->assertSame('I1&I2|null', (string) Type::parseString('I1&I2|null'));
    }

    /**
     * @return void
     */
    public function testNullOrIntersection()
    {
        $this->assertSame('I1&I2|null', (string) Type::parseString('null|I1&I2'));
    }

    /**
     * @return void
     */
    public function testInteratorAndTraversable()
    {
        $this->assertSame('Iterator<mixed, int>&Traversable', (string) Type::parseString('Iterator<int>&Traversable'));
    }

    /**
     * @return void
     */
    public function testStaticAndStatic()
    {
        $this->assertSame('static', (string) Type::parseString('static&static'));
    }

    /**
     * @return void
     */
    public function testTraversableAndIteratorOrNull()
    {
        $this->assertSame(
            'Traversable&Iterator<mixed, int>|null',
            (string) Type::parseString('Traversable&Iterator<int>|null')
        );
    }

    /**
     * @return void
     */
    public function testIteratorAndTraversableOrNull()
    {
        $this->assertSame(
            'Iterator<mixed, int>&Traversable|null',
            (string) Type::parseString('Iterator<mixed, int>&Traversable|null')
        );
    }

    /**
     * @return void
     */
    public function testIntersectionAfterGeneric()
    {
        $this->assertSame('Countable&iterable<mixed, int>&I', (string) Type::parseString('Countable&iterable<int>&I'));
    }

    /**
     * @return void
     */
    public function testIntersectionOfIterables()
    {
        $this->assertSame('iterable<mixed, A>&iterable<mixed, B>', (string) Type::parseString('iterable<A>&iterable<B>'));
    }

    /**
     * @return void
     */
    public function testIntersectionOfObjectLike()
    {
        $this->assertSame('array{a: int, b: int}', (string) Type::parseString('array{a: int}&array{b: int}'));
    }

    /**
     * @return void
     */
    public function testIntersectionOfObjectLikeWithMergedProperties()
    {
        $this->assertSame('array{a: int}', (string) Type::parseString('array{a: int}&array{a: mixed}'));
    }

    /**
     * @return void
     */
    public function testIntersectionOfObjectLikeWithPossiblyUndefinedMergedProperties()
    {
        $this->assertSame('array{a: int}', (string) Type::parseString('array{a: int}&array{a?: int}'));
    }

    /**
     * @return void
     */
    public function testIntersectionOfObjectLikeWithConflictingProperties()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('array{a: string}&array{a: int}');
    }

    /**
     * @return void
     */
    public function testUnionOfIntersectionOfObjectLike()
    {
        $this->assertSame('array{a: int|string, b?: int}', (string) Type::parseString('array{a: int}|array{a: string}&array{b: int}'));
        $this->assertSame('array{a: int|string, b?: int}', (string) Type::parseString('array{b: int}&array{a: string}|array{a: int}'));
    }

    /**
     * @return void
     */
    public function testIntersectionOfUnionOfObjectLike()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('array{a: int}&array{a: string}|array{b: int}');
    }

    /**
     * @return void
     */
    public function testIntersectionOfObjectLikeAndObject()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('array{a: int}&T1');
    }

    public function testIterableContainingObjectLike() : void
    {
        $this->assertSame('iterable<string, array{int}>', Type::parseString('iterable<string, array{int}>')->getId());
    }

    /**
     * @return void
     */
    public function testPhpDocSimpleArray()
    {
        $this->assertSame('array<array-key, A>', (string) Type::parseString('A[]'));
    }

    /**
     * @return void
     */
    public function testPhpDocUnionArray()
    {
        $this->assertSame('array<array-key, A|B>', (string) Type::parseString('(A|B)[]'));
    }

    /**
     * @return void
     */
    public function testPhpDocMultiDimensionalArray()
    {
        $this->assertSame('array<array-key, array<array-key, A>>', (string) Type::parseString('A[][]'));
    }

    /**
     * @return void
     */
    public function testPhpDocMultidimensionalUnionArray()
    {
        $this->assertSame('array<array-key, array<array-key, A|B>>', (string) Type::parseString('(A|B)[][]'));
    }

    /**
     * @return void
     */
    public function testPhpDocObjectLikeArray()
    {
        $this->assertSame(
            'array<array-key, array{b: bool, d: string}>',
            (string) Type::parseString('array{b: bool, d: string}[]')
        );
    }

    /**
     * @return void
     */
    public function testPhpDocUnionOfArrays()
    {
        $this->assertSame('array<array-key, A|B>', (string) Type::parseString('A[]|B[]'));
    }

    /**
     * @return void
     */
    public function testPhpDocUnionOfArraysOrObject()
    {
        $this->assertSame('C|array<array-key, A|B>', (string) Type::parseString('A[]|B[]|C'));
    }

    /**
     * @return void
     */
    public function testPsalmOnlyAtomic()
    {
        $this->assertSame('class-string', (string) Type::parseString('class-string'));
    }

    /**
     * @return void
     */
    public function testParameterizedClassString()
    {
        $this->assertSame('class-string<A>', (string) Type::parseString('class-string<A>'));
    }

    /**
     * @return void
     */
    public function testParameterizedClassStringUnion()
    {
        $this->assertSame('class-string<A>|class-string<B>', (string) Type::parseString('class-string<A>|class-string<B>'));
    }

    /**
     * @return void
     */
    public function testInvalidType()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('array(A)');
    }

    /**
     * @return void
     */
    public function testBracketedUnionAndIntersection()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('(A|B)&C');
    }

    /**
     * @return void
     */
    public function testBracketInUnion()
    {
        Type::parseString('null|(scalar|array|object)');
    }

    /**
     * @return void
     */
    public function testObjectLikeWithSimpleArgs()
    {
        $this->assertSame('array{a: int, b: string}', (string) Type:: parseString('array{a: int, b: string}'));
    }

    /**
     * @return void
     */
    public function testObjectLikeWithSpace()
    {
        $this->assertSame('array{\'a \': int, \'b  \': string}', (string) Type:: parseString('array{\'a \': int, \'b  \': string}'));
    }

    /**
     * @return void
     */
    public function testObjectLikeWithQuotedKeys()
    {
        $this->assertSame('array{\'\\"\': int, \'\\\'\': string}', (string) Type:: parseString('array{\'"\': int, \'\\\'\': string}'));
        $this->assertSame('array{\'\\"\': int, \'\\\'\': string}', (string) Type:: parseString('array{"\\"": int, "\\\'": string}'));
    }

    /**
     * @return void
     */
    public function testObjectLikeWithClassConstantKey()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('array{self::FOO: string}');
    }

    /**
     * @return void
     */
    public function testObjectLikeWithQuotedClassConstantKey()
    {
        $this->assertSame('array{\'self::FOO\': string}', (string) Type:: parseString('array{"self::FOO": string}'));
    }

    /**
     * @return void
     */
    public function testObjectLikeWithoutClosingBracket()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('array{a: int, b: string');
    }

    /**
     * @return void
     */
    public function testObjectLikeArrayInType()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('array{a:[]}');
    }

    /**
     * @return void
     */
    public function testObjectWithSimpleArgs()
    {
        $this->assertSame('object{a:int, b:string}', (string) Type::parseString('object{a:int, b:string}'));
    }

    /**
     * @return void
     */
    public function testObjectWithDollarArgs()
    {
        $this->assertSame('object{a:int, $b:string}', (string) Type::parseString('object{a:int, $b:string}'));
    }

    /**
     * @return void
     */
    public function testObjectLikeWithUnionArgs()
    {
        $this->assertSame(
            'array{a: int|string, b: string}',
            (string) Type::parseString('array{a: int|string, b: string}')
        );
    }

    /**
     * @return void
     */
    public function testObjectLikeWithGenericArgs()
    {
        $this->assertSame(
            'array{a: array<int, int|string>, b: string}',
            (string) Type::parseString('array{a: array<int, string|int>, b: string}')
        );
    }

    /**
     * @return void
     */
    public function testObjectLikeWithIntKeysAndUnionArgs()
    {
        $this->assertSame(
            'array{null|stdClass}',
            (string)Type::parseString('array{stdClass|null}')
        );
    }

    /**
     * @return void
     */
    public function testObjectLikeWithIntKeysAndGenericArgs()
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
    public function testObjectLikeOptional()
    {
        $this->assertSame(
            'array{a: int, b?: int}',
            (string)Type::parseString('array{a: int, b?: int}')
        );
    }

    /**
     * @return void
     */
    public function testSimpleCallable()
    {
        $this->assertSame(
            'callable(int, string):void',
            (string)Type::parseString('callable(int, string) : void')
        );
    }

    /**
     * @return void
     */
    public function testCallableWithoutClosingBracket()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('callable(int, string');
    }

    /**
     * @return void
     */
    public function testCallableWithParamNames()
    {
        $this->assertSame(
            'callable(int, string):void',
            (string)Type::parseString('callable(int $foo, string $bar) : void')
        );
    }

    /**
     * @return void
     */
    public function testCallableReturningIntersection()
    {
        $this->assertSame(
            'callable(int, string):I1&I2',
            (string)Type::parseString('callable(int, string) : (I1&I2)')
        );
    }

    /**
     * @return void
     */
    public function testEmptyCallable()
    {
        $this->assertSame(
            'callable():void',
            (string)Type::parseString('callable() : void')
        );
    }

    /**
     * @return void
     */
    public function testCallableWithUnionLastType()
    {
        $this->assertSame(
            'callable(int, int|string):void',
            (string)Type::parseString('callable(int, int|string) : void')
        );
    }

    /**
     * @return void
     */
    public function testCallableWithVariadic()
    {
        $this->assertSame(
            'callable(int, string...):void',
            (string)Type::parseString('callable(int, string...) : void')
        );
    }

    /**
     * @return void
     */
    public function testCallableThatReturnsACallable()
    {
        $this->assertSame(
            'callable():callable():string',
            (string)Type::parseString('callable() : callable() : string')
        );
    }

    /**
     * @return void
     */
    public function testCallableThatReturnsACallableThatReturnsACallable()
    {
        $this->assertSame(
            'callable():callable():callable():string',
            (string)Type::parseString('callable() : callable() : callable() : string')
        );
    }

    /**
     * @return void
     */
    public function testCallableOrInt()
    {
        $this->assertSame(
            'callable(string):void|int',
            (string)Type::parseString('callable(string):void|int')
        );
    }

    /**
     * @return void
     */
    public function testCallableWithGoodVariadic()
    {
        Type::parseString('callable(int, string...) : void');
        Type::parseString('callable(int,string...) : void');
    }

    /**
     * @return void
     */
    public function testCallableWithSpreadBefore()
    {
        $this->assertSame(
            'callable(int, string...):void',
            (string)Type::parseString('callable(int, ...string):void')
        );
    }

    /**
     * @return void
     */
    public function testConditionalTypeWithSpaces()
    {
        $this->assertSame(
            '(T is string ? string : int)',
            (string) Type::parseString('(T is string ? string : int)', null, ['T' => ['' => [Type::getArray()]]])
        );
    }

    /**
     * @return void
     */
    public function testConditionalTypeWithUnion()
    {
        $this->assertSame(
            '(T is string|true ? int|string : int)',
            (string) Type::parseString('(T is "hello"|true ? string|int : int)', null, ['T' => ['' => [Type::getArray()]]])
        );
    }

    /**
     * @return void
     */
    public function testConditionalTypeWithObjectLikeArray()
    {
        $this->assertSame(
            '(T is array{a: string} ? string : int)',
            (string) Type::parseString('(T is array{a: string} ? string : int)', null, ['T' => ['' => [Type::getArray()]]])
        );
    }

    /**
     * @return void
     */
    public function testConditionalTypeWithGenericIs()
    {
        $this->assertSame(
            '(T is array<array-key, string> ? string : int)',
            (string) Type::parseString('(T is array<string> ? string : int)', null, ['T' => ['' => [Type::getArray()]]])
        );
    }

    /**
     * @return void
     */
    public function testConditionalTypeWithIntersection()
    {
        $this->assertSame(
            '(T is A&B ? string : int)',
            (string) Type::parseString('(T is A&B ? string : int)', null, ['T' => ['' => [Type::getArray()]]])
        );
    }

    /**
     * @return void
     */
    public function testConditionalTypeWithoutSpaces()
    {
        $this->assertSame(
            '(T is string ? string : int)',
            (string) Type::parseString('(T is string?string:int)', null, ['T' => ['' => [Type::getArray()]]])
        );
    }

    /**
     * @return void
     */
    public function testConditionalTypeWithCallableElseBool()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('(T is string ? callable() : bool)', null, ['T' => ['' => [Type::getArray()]]]);
    }

    /**
     * @return void
     */
    public function testConditionalTypeWithCallableReturningBoolElseBool()
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
    public function testCallableWithTrailingColon()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('callable(int):');
    }

    /**
     * @return void
     */
    public function testCallableWithAnotherBadVariadic()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('callable(int, string..) : void');
    }

    /**
     * @return void
     */
    public function testCallableWithVariadicAndDefault()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('callable(int, string...=) : void');
    }

    /**
     * @return void
     */
    public function testBadVariadic()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('string...');
    }

    /**
     * @return void
     */
    public function testBadFullStop()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('string.');
    }

    /**
     * @return void
     */
    public function testBadSemicolon()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('string;');
    }

    /**
     * @return void
     */
    public function testBadGenericString()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('string<T>');
    }

    /**
     * @return void
     */
    public function testBadAmpersand()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('&array');
    }

    /**
     * @return void
     */
    public function testBadColon()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString(':array');
    }

    /**
     * @return void
     */
    public function testBadBrackets()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('max(a)');
    }

    /**
     * @return void
     */
    public function testMoreBadBrackets()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('max(a):void');
    }

    /**
     * @return void
     */
    public function testGeneratorWithWBadBrackets()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('Generator{string, A}');
    }

    /**
     * @return void
     */
    public function testBadEquals()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('=array');
    }

    /**
     * @return void
     */
    public function testBadBar()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('|array');
    }

    /**
     * @return void
     */
    public function testBadColonDash()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('array|string:-');
    }

    /**
     * @return void
     */
    public function testDoubleBar()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('PDO||Closure|numeric');
    }

    /**
     * @return void
     */
    public function testCallableWithDefault()
    {
        $this->assertSame(
            'callable(int, string=):void',
            (string)Type::parseString('callable(int, string=) : void')
        );
    }

    /**
     * @return void
     */
    public function testNestedCallable()
    {
        $this->assertSame(
            'callable(callable(A):B):C',
            (string)Type::parseString('callable(callable(A):B):C')
        );
    }

    /**
     * @return void
     */
    public function testCallableWithoutReturn()
    {
        $this->assertSame(
            'callable(int, string)',
            (string)Type::parseString('callable(int, string)')
        );
    }

    /**
     * @return void
     */
    public function testCombineLiteralStringWithClassString()
    {
        $this->assertSame(
            'class-string|string(array)',
            Type::parseString('"array"|class-string')->getId()
        );
    }

    /**
     * @return void
     */
    public function testCombineLiteralClassStringWithClassString()
    {
        $this->assertSame(
            'class-string',
            Type::parseString('A::class|class-string')->getId()
        );
    }

    /**
     * @return void
     */
    public function testKeyOfClassConstant()
    {
        $this->assertSame(
            'key-of<Foo\Baz::BAR>',
            (string)Type::parseString('key-of<Foo\Baz::BAR>')
        );
    }

    /**
     * @return void
     */
    public function testKeyOfTemplate()
    {
        $this->assertSame(
            'key-of<T>',
            (string)Type::parseString('key-of<T>', null, ['T' => ['' => [Type::getArray()]]])
        );
    }

    /**
     * @return void
     */
    public function testIndexedAccess()
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
    public function testValueOfClassConstant()
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
    public function testVeryLargeType()
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
    public function testEnum()
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
    public function testEnumWithoutSpaces()
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
    public function testSingleLiteralString()
    {
        $this->assertSame(
            'string',
            (string)Type::parseString('"var"')
        );
    }

    /**
     * @return void
     */
    public function testSingleLiteralInt()
    {
        $this->assertSame(
            'int',
            (string)Type::parseString('6')
        );
    }

    /**
     * @return void
     */
    public function testSingleLiteralFloat()
    {
        $this->assertSame(
            'float',
            (string)Type::parseString('6.315')
        );
    }

    /**
     * @return void
     */
    public function testEnumWithClassConstants()
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
    public function testReflectionTypeParse()
    {
        if (!function_exists('Psalm\Tests\someFunction')) {
            /** @psalm-suppress UnusedParam */
            function someFunction(string $param, array $param2, int $param3 = null) : string
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
    public function testValidCallMapType()
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
