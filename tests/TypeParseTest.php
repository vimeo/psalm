<?php
namespace Psalm\Tests;

use Psalm\Type;

class TypeParseTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp()
    {
        //pae::setUp();
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
        $this->assertSame('array<mixed, int>', (string) Type::parseString('array<int>'));
    }

    /**
     * @return void
     */
    public function testArrayWithNestedSingleArg()
    {
        $this->assertSame('array<mixed, array<mixed, int>>', (string) Type::parseString('array<array<int>>'));
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
    public function testGeneric()
    {
        $this->assertSame('B<int>', (string) Type::parseString('B<int>'));
    }

    /**
     * @return void
     */
    public function testIntersection()
    {
        $this->assertSame('I1&I2', (string) Type::parseString('I1&I2'));
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
            'Traversable&Iterator<int>|null',
            (string) Type::parseString('Traversable&Iterator<int>|null')
        );
    }

    /**
     * @return void
     */
    public function testPhpDocSimpleArray()
    {
        $this->assertSame('array<mixed, A>', (string) Type::parseString('A[]'));
    }

    /**
     * @return void
     */
    public function testPhpDocUnionArray()
    {
        $this->assertSame('array<mixed, A|B>', (string) Type::parseString('(A|B)[]'));
    }

    /**
     * @return void
     */
    public function testPhpDocMultiDimensionalArray()
    {
        $this->assertSame('array<mixed, array<mixed, A>>', (string) Type::parseString('A[][]'));
    }

    /**
     * @return void
     */
    public function testPhpDocMultidimensionalUnionArray()
    {
        $this->assertSame('array<mixed, array<mixed, A|B>>', (string) Type::parseString('(A|B)[][]'));
    }

    /**
     * @return void
     */
    public function testPhpDocObjectLikeArray()
    {
        $this->assertSame(
            'array<mixed, array{b:bool, d:string}>',
            (string) Type::parseString('array{b:bool,d:string}[]')
        );
    }

    /**
     * @return void
     */
    public function testPhpDocUnionOfArrays()
    {
        $this->assertSame('array<mixed, A|B>', (string) Type::parseString('A[]|B[]'));
    }

    /**
     * @return void
     */
    public function testPhpDocUnionOfArraysOrObject()
    {
        $this->assertSame('array<mixed, A|B>|C', (string) Type::parseString('A[]|B[]|C'));
    }

    /**
     * @return void
     */
    public function testPsalmOnlyAtomic()
    {
        $this->assertSame('class-string', (string) Type::parseString('class-string'));
    }

    /**
     * @expectedException \Psalm\Exception\TypeParseTreeException
     *
     * @return void
     */
    public function testInvalidType()
    {
        Type::parseString('array(A)');
    }

    /**
     * @expectedException \Psalm\Exception\TypeParseTreeException
     *
     * @return void
     */
    public function testBracketedUnionAndIntersection()
    {
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
            'array{0:stdClass|null}',
            (string)Type::parseString('array{stdClass|null}')
        );
    }

    /**
     * @return void
     */
    public function testObjectLikeWithIntKeysAndGenericArgs()
    {
        $this->assertSame(
            'array{0:array<mixed, mixed>}',
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
    public function testCallableOrInt()
    {
        $this->assertSame(
            'callable(string):void|int',
            (string)Type::parseString('callable(string):void|int')
        );
    }

    /**
     * @expectedException \Psalm\Exception\TypeParseTreeException
     *
     * @return void
     */
    public function testCallableWithBadVariadic()
    {
        Type::parseString('callable(int, ...string) : void');
    }

    /**
     * @expectedException \Psalm\Exception\TypeParseTreeException
     *
     * @return void
     */
    public function testCallableWithTrailingColon()
    {
        Type::parseString('callable(int):');
    }

    /**
     * @expectedException \Psalm\Exception\TypeParseTreeException
     *
     * @return void
     */
    public function testCallableWithAnotherBadVariadic()
    {
        Type::parseString('callable(int, string..) : void');
    }

    /**
     * @expectedException \Psalm\Exception\TypeParseTreeException
     *
     * @return void
     */
    public function testCallableWithVariadicAndDefault()
    {
        Type::parseString('callable(int, string...=) : void');
    }

    /**
     * @expectedException \Psalm\Exception\TypeParseTreeException
     *
     * @return void
     */
    public function testBadVariadic()
    {
        Type::parseString('string...');
    }

    /**
     * @expectedException \Psalm\Exception\TypeParseTreeException
     *
     * @return void
     */
    public function testBadFullStop()
    {
        Type::parseString('string.');
    }

    /**
     * @expectedException \Psalm\Exception\TypeParseTreeException
     *
     * @return void
     */
    public function testBadSemicolon()
    {
        Type::parseString('string;');
    }

    /**
     * @expectedException \Psalm\Exception\TypeParseTreeException
     *
     * @return void
     */
    public function testBadAmpersand()
    {
        Type::parseString('&array');
    }

    /**
     * @expectedException \Psalm\Exception\TypeParseTreeException
     *
     * @return void
     */
    public function testBadColon()
    {
        Type::parseString(':array');
    }

    /**
     * @expectedException \Psalm\Exception\TypeParseTreeException
     *
     * @return void
     */
    public function testBadEquals()
    {
        Type::parseString('=array');
    }

    /**
     * @expectedException \Psalm\Exception\TypeParseTreeException
     *
     * @return void
     */
    public function testBadBar()
    {
        Type::parseString('|array');
    }

    /**
     * @expectedException \Psalm\Exception\TypeParseTreeException
     *
     * @return void
     */
    public function testBadColonDash()
    {
        Type::parseString('array|string:-');
    }

    /**
     * @expectedException \Psalm\Exception\TypeParseTreeException
     *
     * @return void
     */
    public function testDoubleBar()
    {
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
        $docblock_type = Type::parseString('( \'foo\\\'with\' | "bar\"bar" | "baz" | "bat\\\\" | \'bang bang\' | 1 | 2 | 3)');

        $resolved_type = new Type\Union([
            new Type\Atomic\TLiteralString('foo\'with'),
            new Type\Atomic\TLiteralString('bar"bar'),
            new Type\Atomic\TLiteralString('baz'),
            new Type\Atomic\TLiteralString('bat\\'),
            new Type\Atomic\TLiteralString('bang bang'),
            new Type\Atomic\TLiteralInt(1),
            new Type\Atomic\TLiteralInt(2),
            new Type\Atomic\TLiteralInt(3)
        ]);

        $this->assertSame($resolved_type->getId(), $docblock_type->getId());
    }

    /**
     * @return void
     */
    public function testEnumWithoutSpaces()
    {
        $docblock_type = Type::parseString('\'foo\\\'with\'|"bar\"bar"|"baz"|"bat\\\\"|\'bang bang\'|1|2|3');

        $resolved_type = new Type\Union([
            new Type\Atomic\TLiteralString('foo\'with'),
            new Type\Atomic\TLiteralString('bar"bar'),
            new Type\Atomic\TLiteralString('baz'),
            new Type\Atomic\TLiteralString('bat\\'),
            new Type\Atomic\TLiteralString('bang bang'),
            new Type\Atomic\TLiteralInt(1),
            new Type\Atomic\TLiteralInt(2),
            new Type\Atomic\TLiteralInt(3)
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
     * @dataProvider providerTestValidCallMapType
     *
     * @param string $return_type
     * @param string $param_type_1
     * @param string $param_type_2
     * @param string $param_type_3
     * @param string $param_type_4
     *
     * @return void
     */
    public function testValidCallMapType(
        $return_type,
        $param_type_1 = '',
        $param_type_2 = '',
        $param_type_3 = '',
        $param_type_4 = ''
    ) {
        if ($return_type && $return_type !== 'void') {
            \Psalm\Type::parseString($return_type);
        }

        if ($param_type_1 && $param_type_1 !== 'mixed') {
            if (strpos($param_type_1, 'oci-') !== false) {
                return;
            }

            \Psalm\Type::parseString($param_type_1);
        }

        if ($param_type_2 && $param_type_2 !== 'mixed') {
            \Psalm\Type::parseString($param_type_2);
        }

        if ($param_type_3 && $param_type_3 !== 'mixed') {
            \Psalm\Type::parseString($param_type_3);
        }

        if ($param_type_4 && $param_type_4 !== 'mixed') {
            \Psalm\Type::parseString($param_type_4);
        }
    }

    /**
     * @return array<string, array<int|string, string>>
     */
    public function providerTestValidCallMapType()
    {
        return \Psalm\Codebase\CallMap::getCallMap();
    }
}
