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
        //parent::setUp();
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
            'callable(int, string) : void',
            (string)Type::parseString('callable(int, string) : void')
        );
    }

    /**
     * @return void
     */
    public function testEmptyCallable()
    {
        $this->assertSame(
            'callable() : void',
            (string)Type::parseString('callable() : void')
        );
    }

    /**
     * @return void
     */
    public function testCallableWithUnionLastType()
    {
        $this->assertSame(
            'callable(int, int|string) : void',
            (string)Type::parseString('callable(int, int|string) : void')
        );
    }

    /**
     * @return void
     */
    public function testCallableWithVariadic()
    {
        $this->assertSame(
            'callable(int, string...) : void',
            (string)Type::parseString('callable(int, string...) : void')
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
            'callable(int, string=) : void',
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
}
