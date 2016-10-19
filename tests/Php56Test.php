<?php

namespace Psalm\Tests;

use PhpParser;
use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Context;
use Psalm\Checker\TypeChecker;
use Psalm\Type;

class Php56Test extends PHPUnit_Framework_TestCase
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

    public function testConstArray()
    {
        $stmts = self::$_parser->parse('<?php
        const ARR = ["a", "b"];
        $a = ARR[0];
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('string', (string) $context->vars_in_scope['$a']);
    }

    public function testConstFeatures()
    {
        $stmts = self::$_parser->parse('<?php
        const ONE = 1;
        const TWO = ONE * 2;

        class C {
            const THREE = TWO + 1;
            const ONE_THIRD = ONE / self::THREE;
            const SENTENCE = "The value of THREE is " . self::THREE;

            /**
             * @param  int $a
             * @return int
             */
            public function f($a = ONE + self::THREE) {
                return $a;
            }
        }

        $d = (new C)->f();
        $e = C::SENTENCE;
        $f = TWO;
        $g = C::ONE_THIRD;
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('int', (string) $context->vars_in_scope['$d']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$e']);
        $this->assertEquals('int', (string) $context->vars_in_scope['$f']);
        $this->assertEquals('float', (string) $context->vars_in_scope['$g']);
    }

    public function testVariadic()
    {
        $stmts = self::$_parser->parse('<?php
        function f($req, $opt = null, ...$params) {
        }

        f(1);
        f(1, 2);
        f(1, 2, 3);
        f(1, 2, 3, 4);
        f(1, 2, 3, 4, 5);
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testArgumentUnpacking()
    {
        $stmts = self::$_parser->parse('<?php
        function add($a, $b, $c) {
            return $a + $b + $c;
        }

        $operators = [2, 3];
        echo add(1, ...$operators);
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testExponentiation()
    {
        $stmts = self::$_parser->parse('<?php
        $a = 2;
        $a **= 3;
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testUse()
    {
        $this->markTestIncomplete('This passes, but I think there‘s cheating afoot');

        $stmts = self::$_parser->parse('<?php
        namespace Name\Space {
            const FOO = 42;
            function f() { echo __FUNCTION__."\n"; }
        }

        namespace {
            use const Name\Space\FOO;
            use function Name\Space\f;

            echo FOO . "\n";
            f();
        }
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }
}
