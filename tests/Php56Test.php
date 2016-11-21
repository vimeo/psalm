<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class Php56Test extends PHPUnit_Framework_TestCase
{
    protected static $parser;

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $config = Config::getInstance();
        $config->throw_exception = true;
        $config->use_docblock_types = true;
    }

    public function setUp()
    {
        FileChecker::clearCache();
    }

    public function testConstArray()
    {
        $stmts = self::$parser->parse('<?php
        const ARR = ["a", "b"];
        $a = ARR[0];
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('string', (string) $context->vars_in_scope['$a']);
    }

    public function testConstFeatures()
    {
        $stmts = self::$parser->parse('<?php
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('int', (string) $context->vars_in_scope['$d']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$e']);
        $this->assertEquals('int', (string) $context->vars_in_scope['$f']);
        $this->assertEquals('float', (string) $context->vars_in_scope['$g']);
    }

    public function testVariadic()
    {
        $stmts = self::$parser->parse('<?php
        function f($req, $opt = null, ...$params) {
        }

        f(1);
        f(1, 2);
        f(1, 2, 3);
        f(1, 2, 3, 4);
        f(1, 2, 3, 4, 5);
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testVariadicArray()
    {
        $stmts = self::$parser->parse('<?php
        /** @return array<int> */
        function f(int ...$a_list) {
            return array_map(function (int $a) {
                return $a + 1;
            }, $a_list);
        }

        f(1);
        f(1, 2);
        f(1, 2, 3);
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testArgumentUnpacking()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @return int
         * @param int $a
         * @param int $b
         * @param int $c
         */
        function add($a, $b, $c) {
            return $a + $b + $c;
        }

        $operators = [2, 3];
        echo add(1, ...$operators);
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testExponentiation()
    {
        $stmts = self::$parser->parse('<?php
        $a = 2;
        $a **= 3;
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testConstantAliasInNamespace()
    {
        $stmts = self::$parser->parse('<?php
        namespace Name\Space {
            const FOO = 42;
        }

        namespace Noom\Spice {
            use const Name\Space\FOO;

            echo FOO . "\n";
            echo \Name\Space\FOO;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testConstantAliasInClass()
    {
        $stmts = self::$parser->parse('<?php
        namespace Name\Space {
            const FOO = 42;
        }

        namespace Noom\Spice {
            use const Name\Space\FOO;

            class A {
                /** @return void */
                public function foo() {
                    echo FOO . "\n";
                    echo \Name\Space\FOO;
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testFunctionAliasInNamespace()
    {
        $stmts = self::$parser->parse('<?php
        namespace Name\Space {
            function f() { echo __FUNCTION__."\n"; }
        }

        namespace Noom\Spice {
            use function Name\Space\f;

            f();
            \Name\Space\f();
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testFunctionAliasInClass()
    {
        $stmts = self::$parser->parse('<?php
        namespace Name\Space {
            function f() { echo __FUNCTION__."\n"; }
        }

        namespace Noom\Spice {
            use function Name\Space\f;

            class A {
                /** @return void */
                public function foo() {
                    f();
                    \Name\Space\f();
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }
}
