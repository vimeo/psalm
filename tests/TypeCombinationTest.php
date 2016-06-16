<?php

namespace CodeInspector\Tests;

use CodeInspector\Type;
use PhpParser;
use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;

class TypeCombinationTest extends PHPUnit_Framework_TestCase
{
    protected static $_parser;

    public static function setUpBeforeClass()
    {
        self::$_parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }

    public function testIntOrString()
    {
        $this->assertEquals(
            'int|string',
            (string) Type::combineTypes([
                Type::parseString('int', false),
                Type::parseString('string', false)
            ])
        );
    }

    public function testArrayOfIntOrString()
    {
        $this->assertEquals(
            'array<int|string>',
            (string) Type::combineTypes([
                Type::parseString('array<int>', false),
                Type::parseString('array<string>', false)
            ])
        );
    }

    public function testArrayOfIntOrAlsoString()
    {
        $this->assertEquals('array<int>|string',
            (string) Type::combineTypes([
                Type::parseString('array<int>', false),
                Type::parseString('string', false)
            ])
        );
    }

    public function testEmptyArrays()
    {
        $this->assertEquals('array<empty>',
            (string) Type::combineTypes([
                Type::parseString('array<empty>', false),
                Type::parseString('array<empty>', false)
            ])
        );
    }

    public function testArrayStringOrEmptyArray()
    {
        $this->assertEquals('array<string>',
            (string) Type::combineTypes([
                Type::parseString('array<empty>', false),
                Type::parseString('array<string>', false)
            ])
        );
    }

    public function testArrayMixedOrString()
    {
        $this->assertEquals(
            'array<mixed>',
            (string) Type::combineTypes([
                Type::parseString('array<mixed>', false),
                Type::parseString('array<string>', false)
            ])
        );
    }

    public function testArrayMixedOrEmpty()
    {
        $this->assertEquals(
            'array<mixed>',
            (string) Type::combineTypes([
                Type::parseString('array<empty>', false),
                Type::parseString('array<mixed>', false)
            ])
        );
    }

    public function testArrayBigCombination()
    {
        $this->assertEquals(
            'array<int|float|string>',
            (string) Type::combineTypes([
                Type::parseString('array<int|float>', false),
                Type::parseString('array<string>', false)
            ])
        );
    }

    public function testArrayNestedCombination()
    {
        $this->assertEquals(
            'array<array<int>>',
            (string) Type::combineTypes([
                Type::parseString('array<array<empty>>', false),
                Type::parseString('array<array<int>>', false)
            ])
        );
    }

    public function testFalseDestruction()
    {
        $this->assertEquals(
            'bool',
            (string) Type::combineTypes([
                Type::parseString('false', false),
                Type::parseString('bool', false)
            ])
        );
    }

    public function testOnlyFalse()
    {
        $this->assertEquals(
            'bool',
            (string) Type::combineTypes([
                Type::parseString('false', false)
            ])
        );
    }

    public function testMultipleValuedArray()
    {
        $stmts = self::$_parser->parse('<?php
            class A {}
            class B {}
            $var = [];
            $var[] = new A();
            $var[] = new B();
        ');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }
}
