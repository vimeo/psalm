<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class Php56Test extends PHPUnit_Framework_TestCase
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
     * @return void
     */
    public function setUp()
    {
        $config = new TestConfig();
        FileChecker::clearCache();
        $this->project_checker = new \Psalm\Checker\ProjectChecker();
    }

    /**
     * @return void
     */
    public function testConstArray()
    {
        $stmts = self::$parser->parse('<?php
        const ARR = ["a", "b"];
        $a = ARR[0];
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('string', (string) $context->vars_in_scope['$a']);
    }

    /**
     * @return void
     */
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

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('int', (string) $context->vars_in_scope['$d']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$e']);
        $this->assertEquals('int', (string) $context->vars_in_scope['$f']);
        $this->assertEquals('float|int', (string) $context->vars_in_scope['$g']);
    }

    /**
     * @return void
     */
    public function testVariadic()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @return void
         */
        function f($req, $opt = null, ...$params) {
        }

        f(1);
        f(1, 2);
        f(1, 2, 3);
        f(1, 2, 3, 4);
        f(1, 2, 3, 4, 5);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testVariadicArray()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @return array<int>
         */
        function f(int ...$a_list) {
            return array_map(
                /**
                 * @return int
                 */
                function (int $a) {
                    return $a + 1;
                },
                $a_list
            );
        }

        f(1);
        f(1, 2);
        f(1, 2, 3);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
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

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testExponentiation()
    {
        $stmts = self::$parser->parse('<?php
        $a = 2;
        $a **= 3;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
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

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
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
                public function fooFoo() {
                    echo FOO . "\n";
                    echo \Name\Space\FOO;
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testFunctionAliasInNamespace()
    {
        $stmts = self::$parser->parse('<?php
        namespace Name\Space {
            /**
             * @return void
             */
            function f() { echo __FUNCTION__."\n"; }
        }

        namespace Noom\Spice {
            use function Name\Space\f;

            f();
            \Name\Space\f();
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testFunctionAliasInClass()
    {
        $stmts = self::$parser->parse('<?php
        namespace Name\Space {
            /**
             * @return void
             */
            function f() { echo __FUNCTION__."\n"; }
        }

        namespace Noom\Spice {
            use function Name\Space\f;

            class A {
                /** @return void */
                public function fooFoo() {
                    f();
                    \Name\Space\f();
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
