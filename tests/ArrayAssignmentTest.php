<?php

namespace Psalm\Tests;

use PhpParser;
use Psalm\Context;
use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;

class ArrayAssignmentTest extends PHPUnit_Framework_TestCase
{
    protected static $_parser;

    public static function setUpBeforeClass()
    {
        self::$_parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $config = \Psalm\Config::getInstance();
        $config->throw_exception = true;
        $config->use_docblock_types = true;
    }

    public function setUp()
    {
        \Psalm\Checker\FileChecker::clearCache();
    }

    public function testImplicitIntArrayCreation()
    {
        $stmts = self::$_parser->parse('<?php
        $foo = [];
        $foo[] = "hello";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<int,string>', (string) $context->vars_in_scope['foo']);

    }

    public function testImplicit2DIntArrayCreation()
    {
        $stmts = self::$_parser->parse('<?php
        $foo = [];
        $foo[][] = "hello";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<int,array<int,string>>', (string) $context->vars_in_scope['foo']);

    }

    public function testImplicit3DIntArrayCreation()
    {
        $stmts = self::$_parser->parse('<?php
        $foo = [];
        $foo[][][] = "hello";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<int,array<int,array<int,string>>>', (string) $context->vars_in_scope['foo']);
    }

    public function testImplicit4DIntArrayCreation()
    {
        $stmts = self::$_parser->parse('<?php
        $foo = [];
        $foo[][][][] = "hello";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<int,array<int,array<int,array<int,string>>>>', (string) $context->vars_in_scope['foo']);
    }

    public function testImplicitIndexedIntArrayCreation()
    {
        $stmts = self::$_parser->parse('<?php
        $foo = [];
        $foo[0] = "hello";
        $foo[1] = "hello";
        $foo[2] = "hello";

        $bar = [0, 1, 2];

        $bat = [];

        foreach ($foo as $i => $text) {
            $bat[$text] = $bar[$i];
        }
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<int,string>', (string) $context->vars_in_scope['foo']);
        $this->assertEquals('array<int,int>', (string) $context->vars_in_scope['bar']);
        $this->assertEquals('array<string,int>', (string) $context->vars_in_scope['bat']);
    }

    public function testImplicitStringArrayCreation()
    {
        $stmts = self::$_parser->parse('<?php
        $foo = [];
        $foo["bar"] = "hello";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<string,string>', (string) $context->vars_in_scope['foo']);
        $this->assertEquals('string', (string) $context->vars_in_scope['foo[\'bar\']']);
    }

    public function testImplicit2DStringArrayCreation()
    {
        $stmts = self::$_parser->parse('<?php
        $foo = [];
        $foo["bar"]["baz"] = "hello";
        ');

        // check array access of baz on foo
        // with some extra data – if we need to create an array for type $foo["bar"],

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<string,array<string,string>>', (string) $context->vars_in_scope['foo']);
        $this->assertEquals('string', (string) $context->vars_in_scope['foo[\'bar\'][\'baz\']']);
    }

    public function testImplicit3DStringArrayCreation()
    {
        $stmts = self::$_parser->parse('<?php
        $foo = [];
        $foo["bar"]["baz"]["bat"] = "hello";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<string,array<string,array<string,string>>>', (string) $context->vars_in_scope['foo']);
        $this->assertEquals('string', (string) $context->vars_in_scope['foo[\'bar\'][\'baz\'][\'bat\']']);
    }

    public function testImplicit4DStringArrayCreation()
    {
        $stmts = self::$_parser->parse('<?php
        $foo = [];
        $foo["bar"]["baz"]["bat"]["bap"] = "hello";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<string,array<string,array<string,array<string,string>>>>', (string) $context->vars_in_scope['foo']);
        $this->assertEquals('string', (string) $context->vars_in_scope['foo[\'bar\'][\'baz\'][\'bat\'][\'bap\']']);
    }

    public function test2Step2DIntArrayCreation()
    {
        $stmts = self::$_parser->parse('<?php
        $foo = ["bar" => []];
        $foo["bar"]["baz"] = "hello";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<string,array<string,string>>', (string) $context->vars_in_scope['foo']);
        $this->assertEquals('string', (string) $context->vars_in_scope['foo[\'bar\'][\'baz\']']);
    }

    public function test2StepImplicit3DIntArrayCreation()
    {
        $stmts = self::$_parser->parse('<?php
        $foo = ["bar" => []];
        $foo["bar"]["baz"]["bat"] = "hello";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<string,array<string,array<string,string>>>', (string) $context->vars_in_scope['foo']);
    }

    public function testConflictingTypes()
    {
        $stmts = self::$_parser->parse('<?php
        $foo = [
            "bar" => ["a" => "b"],
            "baz" => [1]
        ];
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('object-like{bar:array<string,string>,baz:array<int,int>}', (string) $context->vars_in_scope['foo']);
    }

    public function testConflictingTypesWithAssignment()
    {
        $stmts = self::$_parser->parse('<?php
        $foo = [
            "bar" => ["a" => "b"],
            "baz" => [1]
        ];
        $foo["bar"]["bam"]["baz"] = "hello";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('object-like{bar:array<string,string|array<string,string>>,baz:array<int,int>}', (string) $context->vars_in_scope['foo']);
    }

    public function testConflictingTypesWithAssignment2()
    {
        $stmts = self::$_parser->parse('<?php
        $foo = [];
        $foo["a"] = "hello";
        $foo["b"][] = "goodbye";
        $bar = $foo["a"];
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<string,string|array<int,string>>', (string) $context->vars_in_scope['foo']);
        $this->assertEquals('string', (string) $context->vars_in_scope['foo[\'a\']']);
        $this->assertEquals('array<int,string>', (string) $context->vars_in_scope['foo[\'b\']']);
        $this->assertEquals('string', (string) $context->vars_in_scope['bar']);
    }

    public function testConflictingTypesWithAssignment3()
    {
        $stmts = self::$_parser->parse('<?php
        $foo = [];
        $foo["a"] = "hello";
        $foo["b"]["c"]["d"] = "goodbye";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<string,string|array<string,array<string,string>>>', (string) $context->vars_in_scope['foo']);
    }

    public function testIssetKeyedOffset()
    {
        $file_checker = new \Psalm\Checker\FileChecker(
            'somefile.php',
            self::$_parser->parse('<?php
                if (!isset($foo["a"])) {
                    $foo["a"] = "hello";
                }
            ')
        );
        $context = new Context('somefile.php');
        $context->vars_in_scope['foo'] = \Psalm\Type::getArray();
        $file_checker->check(true, true, $context);
        $this->assertEquals('string', (string) $context->vars_in_scope['foo[\'a\']']);
    }
}
