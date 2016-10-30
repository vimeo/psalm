<?php

namespace Psalm\Tests;

use PhpParser;
use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Context;
use Psalm\Checker\TypeChecker;
use Psalm\Type;

class InterfaceTest extends PHPUnit_Framework_TestCase
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

    public function testSimpleVars()
    {
        $stmts = self::$_parser->parse('<?php
        list($a, $b) = ["a", "b"];
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('string', (string) $context->vars_in_scope['$a']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$b']);
    }

    public function testSimpleVarsWithSeparateTypes()
    {
        $stmts = self::$_parser->parse('<?php
        list($a, $b) = ["a", 2];
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('string', (string) $context->vars_in_scope['$a']);
        $this->assertEquals('int', (string) $context->vars_in_scope['$b']);
    }

    public function testSimpleVarsWithSeparateTypesInVar()
    {
        $stmts = self::$_parser->parse('<?php
        $bar = ["a", 2];
        list($a, $b) = $bar;
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('int|string', (string) $context->vars_in_scope['$a']);
        $this->assertEquals('int|string', (string) $context->vars_in_scope['$b']);
    }

    public function testThisVar()
    {
        $stmts = self::$_parser->parse('<?php
        class A {
            public $a;
            public $b;

            public function foo() : string
            {
                list($this->a, $this->b) = ["a", "b"];

                return $this->a;
            }
        }
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }
}
