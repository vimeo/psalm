<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Type;

class TypeParseTest extends PHPUnit_Framework_TestCase
{
    protected static $parser;

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }

    private static function getAtomic($string)
    {
        return array_values(Type::parseString($string)->types)[0];
    }

    public function testIntOrString()
    {
        $this->assertEquals('int|string', (string) Type::parseString('int|string'));
    }

    public function testArray()
    {
        $this->assertEquals('array<int, int>', (string) Type::parseString('array<int, int>'));
        $this->assertEquals('array<int, string>', (string) Type::parseString('array<int, string>'));
        $this->assertEquals('array<int, static>', (string) Type::parseString('array<int, static>'));
        $this->assertEquals('array<int|string, string>', (string) Type::parseString('array<int|string, string>'));
    }

    public function testGeneric()
    {
        $this->assertEquals('B<int>', (string) Type::parseString('B<int>'));
    }

    public function testPhpDocStyle()
    {
        $this->assertEquals('array<mixed, A>', (string) Type::parseString('A[]'));
        $this->assertEquals('array<mixed, A|B>', (string) Type::parseString('(A|B)[]'));
        $this->assertEquals('array<mixed, array<mixed, A>>', (string) Type::parseString('A[][]'));
        $this->assertEquals('array<mixed, array<mixed, A|B>>', (string) Type::parseString('(A|B)[][]'));
    }

    public function testObjectLike()
    {
        $this->assertEquals('array{a:int, b:string}', (string) Type::parseString('array{a:int, b:string}'));
        $this->assertEquals(
            'array{a:int|string, b:string}',
            (string) Type::parseString('array{a:int|string, b:string}')
        );

        $this->assertEquals(
            'array{a:array<int, string|int>, b:string}',
            (string) Type::parseString('array{a:array<int, string|int>, b:string}')
        );
    }
}
