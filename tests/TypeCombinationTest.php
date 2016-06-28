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

    private static function getAtomic($string)
    {
        return array_values(Type::parseString($string)->types)[0];
    }

    public function testIntOrString()
    {
        $this->assertEquals(
            'int|string',
            (string) Type::combineTypes([
                self::getAtomic('int'),
                self::getAtomic('string')
            ])
        );
    }

    public function testArrayOfIntOrString()
    {
        $this->assertEquals(
            'array<int|string>',
            (string) Type::combineTypes([
                self::getAtomic('array<int>'),
                self::getAtomic('array<string>')
            ])
        );
    }

    public function testArrayOfIntOrAlsoString()
    {
        $this->assertEquals(
            'array<int>|string',
            (string) Type::combineTypes([
                self::getAtomic('array<int>'),
                self::getAtomic('string')
            ])
        );
    }

    public function testEmptyArrays()
    {
        $this->assertEquals(
            'array<empty>',
            (string) Type::combineTypes([
                self::getAtomic('array<empty>'),
                self::getAtomic('array<empty>')
            ])
        );
    }

    public function testArrayStringOrEmptyArray()
    {
        $this->assertEquals(
            'array<string>',
            (string) Type::combineTypes([
                self::getAtomic('array<empty>'),
                self::getAtomic('array<string>')
            ])
        );
    }

    public function testArrayMixedOrString()
    {
        $this->assertEquals(
            'array<mixed>',
            (string) Type::combineTypes([
                self::getAtomic('array<mixed>'),
                self::getAtomic('array<string>')
            ])
        );
    }

    public function testArrayMixedOrEmpty()
    {
        $this->assertEquals(
            'array<mixed>',
            (string) Type::combineTypes([
                self::getAtomic('array<empty>'),
                self::getAtomic('array<mixed>')
            ])
        );
    }

    public function testArrayBigCombination()
    {
        $this->assertEquals(
            'array<int|float|string>',
            (string) Type::combineTypes([
                self::getAtomic('array<int|float>'),
                self::getAtomic('array<string>')
            ])
        );
    }

    public function testFalseDestruction()
    {
        $this->assertEquals(
            'bool',
            (string) Type::combineTypes([
                self::getAtomic('false'),
                self::getAtomic('bool')
            ])
        );
    }

    public function testOnlyFalse()
    {
        $this->assertEquals(
            'bool',
            (string) Type::combineTypes([
                self::getAtomic('false')
            ])
        );
    }

    public function testFalseFalseDestruction()
    {
        $this->assertEquals(
            'bool',
            (string) Type::combineTypes([
                self::getAtomic('false'),
                self::getAtomic('false')
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
