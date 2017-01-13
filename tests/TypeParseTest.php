<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Type;

class TypeParseTest extends PHPUnit_Framework_TestCase
{
    /** @var \PhpParser\Parser */
    protected static $parser;

    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    /**
     * @return void
     */
    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }

    /**
     * @return \Psalm\Type\Atomic
     */
    private static function getAtomic($string)
    {
        return array_values(Type::parseString($string)->types)[0];
    }

    /**
     * @return void
     */
    public function testIntOrString()
    {
        $this->assertEquals('int|string', (string) Type::parseString('int|string'));
    }

    /**
     * @return void
     */
    public function testArray()
    {
        $this->assertEquals('array<int, int>', (string) Type::parseString('array<int, int>'));
        $this->assertEquals('array<int, string>', (string) Type::parseString('array<int, string>'));
        $this->assertEquals('array<int, static>', (string) Type::parseString('array<int, static>'));
        $this->assertEquals('array<int|string, string>', (string) Type::parseString('array<int|string, string>'));
    }

    /**
     * @return void
     */
    public function testGeneric()
    {
        $this->assertEquals('B<int>', (string) Type::parseString('B<int>'));
    }

    /**
     * @return void
     */
    public function testPhpDocStyle()
    {
        $this->assertEquals('array<mixed, A>', (string) Type::parseString('A[]'));
        $this->assertEquals('array<mixed, A|B>', (string) Type::parseString('(A|B)[]'));
        $this->assertEquals('array<mixed, array<mixed, A>>', (string) Type::parseString('A[][]'));
        $this->assertEquals('array<mixed, array<mixed, A|B>>', (string) Type::parseString('(A|B)[][]'));
        $this->assertEquals('array<mixed, A|B>', (string) Type::parseString('A[]|B[]'));
        $this->assertEquals('array<mixed, A|B>|C', (string) Type::parseString('A[]|B[]|C'));
    }

    /**
     * @return void
     */
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
