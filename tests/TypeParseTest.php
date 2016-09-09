<?php

namespace Psalm\Tests;

use Psalm\Type;
use PhpParser;
use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;

class TypeParseTest extends PHPUnit_Framework_TestCase
{
    protected static $_parser;

    public static function setUpBeforeClass()
    {
        self::$_parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }

    private static function getAtomic($string)
    {
        return array_values(Type::parseString($string)->types)[0];
    }

    public function testIntOrString()
    {
        $this->assertEquals('int|string', (string) Type::parseString('int|string'));

        $this->assertEquals('array<int,int>', (string) Type::parseString('array<int,int>'));
        $this->assertEquals('array<int,string>', (string) Type::parseString('array<int,string>'));
        $this->assertEquals('array<int,static>', (string) Type::parseString('array<int,static>'));

        $this->assertEquals('B<int>', (string) Type::parseString('B<int>'));
    }


}
