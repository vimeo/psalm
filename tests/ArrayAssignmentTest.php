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

    }

    public function testImplicit2DStringArrayCreation()
    {
        $stmts = self::$_parser->parse('<?php
        $foo = [];
        $foo["bar"]["baz"] = "hello";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<string,array<string,string>>', (string) $context->vars_in_scope['foo']);

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
        $this->assertEquals('array<string,array<int|string,mixed>>', (string) $context->vars_in_scope['foo']);
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
        $this->assertEquals('array<string,array<int|string,mixed>>', (string) $context->vars_in_scope['foo']);
    }


}
