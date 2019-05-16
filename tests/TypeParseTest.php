<?php
namespace Psalm\Tests;

use Psalm\Type;

class TypeParseTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp() : void
    {
        $this->file_provider = new \Psalm\Tests\Internal\Provider\FakeFileProvider();

        $config = new TestConfig();

        $providers = new \Psalm\Internal\Provider\Providers(
            $this->file_provider,
            new \Psalm\Tests\Internal\Provider\FakeParserCacheProvider()
        );

        $this->project_analyzer = new \Psalm\Internal\Analyzer\ProjectAnalyzer(
            $config,
            $providers,
            false,
            true,
            \Psalm\Internal\Analyzer\ProjectAnalyzer::TYPE_CONSOLE,
            1,
            false
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
        $this->assertSame('static|A', (string) Type::parseString('$this|A'));
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
        $this->assertSame('string|int|null', (string) Type::parseString('?(string|int)'));
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
        $this->assertSame('string|int|null', (string) Type::parseString('?string|?int'));
    }

    /**
     * @return void
     */
    public function testArray()
    {
        $this->assertSame('array<int, int>', (string) Type::parseString('array<int, int>'));
        $this->assertSame('array<int, string>', (string) Type::parseString('array<int, string>'));
        $this->assertSame('array<int, static>', (string) Type::parseString('array<int, static>'));
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
        $this->assertSame('null|I1&I2', (string) Type::parseString('I1&I2|null'));
    }

    /**
     * @return void
     */
    public function testNullOrIntersection()
    {
        $this->assertSame('null|I1&I2', (string) Type::parseString('null|I1&I2'));
    }

    /**
     * @return void
     */
    public function testInteratorAndTraversable()
    {
        $this->assertSame('Iterator<int>&Traversable', (string) Type::parseString('Iterator<int>&Traversable'));
    }

    /**
     * @return void
     */
    public function testTraversableAndIteratorOrNull()
    {
        $this->assertSame(
            'null|Traversable&Iterator<int>',
            (string) Type::parseString('Traversable&Iterator<int>|null')
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
            'array<array-key, array{b:bool, d:string}>',
            (string) Type::parseString('array{b:bool,d:string}[]')
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
        $this->assertSame('array<array-key, A|B>|C', (string) Type::parseString('A[]|B[]|C'));
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
    public function testParamaterizedClassString()
    {
        $this->assertSame('class-string<A>', (string) Type::parseString('class-string<A>'));
    }

    /**
     *
     * @return void
     */
    public function testInvalidType()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('array(A)');
    }

    /**
     *
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
        $this->assertSame('array{a:int, b:string}', (string) Type::parseString('array{a:int, b:string}'));
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
    public function testObjectLikeWithUnionArgs()
    {
        $this->assertSame(
            'array{a:int|string, b:string}',
            (string) Type::parseString('array{a:int|string, b:string}')
        );
    }

    /**
     * @return void
     */
    public function testObjectLikeWithGenericArgs()
    {
        $this->assertSame(
            'array{a:array<int, string|int>, b:string}',
            (string) Type::parseString('array{a:array<int, string|int>, b:string}')
        );
    }

    /**
     * @return void
     */
    public function testObjectLikeWithIntKeysAndUnionArgs()
    {
        $this->assertSame(
            'array{0:null|stdClass}',
            (string)Type::parseString('array{stdClass|null}')
        );
    }

    /**
     * @return void
     */
    public function testObjectLikeWithIntKeysAndGenericArgs()
    {
        $this->assertSame(
            'array{0:array<array-key, mixed>}',
            (string)Type::parseString('array{array}')
        );

        $this->assertSame(
            'array{0:array<int, string>}',
            (string)Type::parseString('array{array<int, string>}')
        );
    }

    /**
     * @return void
     */
    public function testObjectLikeOptional()
    {
        $this->assertSame(
            'array{a:int, b?:int}',
            (string)Type::parseString('array{a:int, b?:int}')
        );
    }

    /**
     * @return void
     */
    public function testCallable()
    {
        $this->assertSame(
            'callable(int, string):void',
            (string)Type::parseString('callable(int, string) : void')
        );
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
     *
     * @return void
     */
    public function testCallableWithBadVariadic()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('callable(int, ...string) : void');
    }

    /**
     *
     * @return void
     */
    public function testCallableWithTrailingColon()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('callable(int):');
    }

    /**
     *
     * @return void
     */
    public function testCallableWithAnotherBadVariadic()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('callable(int, string..) : void');
    }

    /**
     *
     * @return void
     */
    public function testCallableWithVariadicAndDefault()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('callable(int, string...=) : void');
    }

    /**
     *
     * @return void
     */
    public function testBadVariadic()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('string...');
    }

    /**
     *
     * @return void
     */
    public function testBadFullStop()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('string.');
    }

    /**
     *
     * @return void
     */
    public function testBadSemicolon()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('string;');
    }

    /**
     *
     * @return void
     */
    public function testBadGenericString()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('string<T>');
    }

    /**
     *
     * @return void
     */
    public function testBadAmpersand()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('&array');
    }

    /**
     *
     * @return void
     */
    public function testBadColon()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString(':array');
    }

    /**
     *
     * @return void
     */
    public function testBadBrackets()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('max(a)');
    }

    /**
     *
     * @return void
     */
    public function testMoreBadBrackets()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('max(a):void');
    }

    /**
     *
     * @return void
     */
    public function testGeneratorWithWBadBrackets()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('Generator{string, A}');
    }

    /**
     *
     * @return void
     */
    public function testBadEquals()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('=array');
    }

    /**
     *
     * @return void
     */
    public function testBadBar()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('|array');
    }

    /**
     *
     * @return void
     */
    public function testBadColonDash()
    {
        $this->expectException(\Psalm\Exception\TypeParseTreeException::class);
        Type::parseString('array|string:-');
    }

    /**
     *
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
            'string',
            (string)Type::parseString('"array"|class-string')
        );
    }

    /**
     * @return void
     */
    public function testCombineLiteralClassStringWithClassString()
    {
        $this->assertSame(
            'class-string',
            (string)Type::parseString('A::class|class-string')
        );
    }

    /**
     * @return void
     */
    public function testCombineCallables()
    {
        $this->assertSame(
            'callable(int, string=):bool',
            (string) Type::parseString('(callable(int):bool)|(callable(int,string):bool)')
        );

        $this->assertSame(
            'callable(int, string=):bool',
            (string) Type::parseString('(callable(int,string):bool)|(callable(int):bool)')
        );

        $this->assertSame(
            'callable(int, string=):bool',
            (string) Type::parseString('(callable(int,string=):bool)|(callable(int):bool)')
        );

        $this->assertSame(
            'callable(int, string):bool',
            (string) Type::parseString('(callable(int,string=):bool)|(callable(int,string):bool)')
        );
    }

    /**
     * @return void
     */
    public function testCombineClosures()
    {
        $this->assertSame(
            'Closure(int, string=):bool',
            (string) Type::parseString('(Closure(int):bool)|(Closure(int,string):bool)')
        );

        $this->assertSame(
            'Closure(int, string=):bool',
            (string) Type::parseString('(Closure(int,string):bool)|(Closure(int):bool)')
        );

        $this->assertSame(
            'Closure(int, string=):bool',
            (string) Type::parseString('(Closure(int,string=):bool)|(Closure(int):bool)')
        );

        $this->assertSame(
            'Closure(int, string):bool',
            (string) Type::parseString('(Closure(int,string=):bool)|(Closure(int,string):bool)')
        );
    }

    /**
     * @return void
     */
    public function testVeryLargeType()
    {
        $very_large_type = 'array{a:Closure():(array<mixed, mixed>|null), b?:Closure():array<mixed, mixed>, c?:Closure():array<mixed, mixed>, d?:Closure():array<mixed, mixed>, e?:Closure():(array{f:null|string, g:null|string, h:null|string, i:string, j:mixed, k:mixed, l:mixed, m:mixed, n:bool, o?:array{0:string}}|null), p?:Closure():(array{f:null|string, g:null|string, h:null|string, q:string, i:string, j:mixed, k:mixed, l:mixed, m:mixed, n:bool, o?:array{0:string}}|null), r?:Closure():(array<mixed, mixed>|null), s:array<mixed, mixed>}|null';

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
        $callmap_types = \Psalm\Internal\Codebase\CallMap::getCallMap();

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
