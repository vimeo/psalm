<?php

namespace Psalm\Tests;

use PhpParser;
use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Context;
use Psalm\Checker\TypeChecker;
use Psalm\Type;

class Php70Test extends PHPUnit_Framework_TestCase
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

    public function testFunctionTypeHints()
    {
        $stmts = self::$_parser->parse('<?php
        function indexof(string $haystack, string $needle) : int
        {
            $pos = strpos($haystack, $needle);

            if ($pos === false) {
                return -1;
            }

            return $pos;
        }

        $a = indexof("arr", "a");
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('int', (string) $context->vars_in_scope['$a']);
    }

    public function testMethodTypeHints()
    {
        $stmts = self::$_parser->parse('<?php
        class Foo {
            public static function indexof(string $haystack, string $needle) : int
            {
                $pos = strpos($haystack, $needle);

                if ($pos === false) {
                    return -1;
                }

                return $pos;
            }
        }

        $a = Foo::indexof("arr", "a");
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('int', (string) $context->vars_in_scope['$a']);
    }

    public function testNullCoalesce()
    {
        $stmts = self::$_parser->parse('<?php
        $a = $_GET["bar"] ?? "nobody";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('mixed', (string) $context->vars_in_scope['$a']);
    }

    public function testSpaceship()
    {
        $stmts = self::$_parser->parse('<?php
        $a = 1 <=> 1;
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('int', (string) $context->vars_in_scope['$a']);
    }

    public function testDefineArray()
    {
        $this->markTestIncomplete('Not yet supported');
        $stmts = self::$_parser->parse('<?php
        define("ANIMALS", [
            "dog",
            "cat",
            "bird"
        ]);

        $a = ANIMALS[1];
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('string', (string) $context->vars_in_scope['$a']);
    }

    public function testAnonymousClass()
    {
        $this->markTestIncomplete('Not yet supported');
        $stmts = self::$_parser->parse('<?php
        interface Logger {
            public function log(string $msg);
        }

        class Application {
            private $logger;

            public function getLogger(): Logger {
                 return $this->logger;
            }

            public function setLogger(Logger $logger) {
                 $this->logger = $logger;
            }
        }

        $app = new Application;
        $app->setLogger(new class implements Logger {
            public function log(string $msg) {
                echo $msg;
            }
        });
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testGeneratorWithReturn()
    {
        $this->markTestIncomplete('Not yet supported');
        $stmts = self::$_parser->parse('<?php
        /**
         * @return array<int,int>
         */
        function foo(int $i) : Generator {
            if ($i === 1) {
                return "bash";
            }

            yield 1;
        }
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<int,int>', (string) $context->vars_in_scope['$gen']);
        $this->assertEquals('int', (string) $context->vars_in_scope['$gen2']);
    }

    public function testClosureCall()
    {
        $this->markTestIncomplete('Not yet supported');
        $stmts = self::$_parser->parse('<?php
        class A {private $x = 1;}

        // Pre PHP 7 code
        $getXCB = function() {return $this->x;};
        $getX = $getXCB->bindTo(new A, "A"); // intermediate closure
        $a = $getX();

        // PHP 7+ code
        $getX = function() {return $this->x;};
        $b = $getX->call(new A);
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('mixed', (string) $context->vars_in_scope['$a']);
        $this->assertEquals('mixed', (string) $context->vars_in_scope['$b']);
    }

    public function testGeneratorDelegation()
    {
        $stmts = self::$_parser->parse('<?php
        /**
         * @return Generator<int,int>
         */
        function count_to_ten() : Generator {
            yield 1;
            yield 2;
            yield from [3, 4];
            yield from new ArrayIterator([5, 6]);
            yield from seven_eight();
            return yield from nine_ten();
        }

        /**
         * @return Generator<int,int>
         */
        function seven_eight() : Generator {
            yield 7;
            yield from eight();
        }

        /**
         * @return Generator<int,int>
         */
        function eight() : Generator {
            yield 8;
        }

        /**
         * @return Generator<int,int>
         */
        function nine_ten() : Generator {
            yield 9;
            return 10;
        }

        $gen = count_to_ten();
        foreach ($gen as $num) {
            echo "$num ";
        }
        $gen2 = $gen->getReturn();
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('Generator<int,int>', (string) $context->vars_in_scope['$gen']);
        $this->assertEquals('mixed', (string) $context->vars_in_scope['$gen2']);
    }
}
