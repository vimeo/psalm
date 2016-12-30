<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class Php70Test extends PHPUnit_Framework_TestCase
{
    /** @var \PhpParser\Parser */
    protected static $parser;

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }

    public function setUp()
    {
        $config = new TestConfig();
        FileChecker::clearCache();
    }

    public function testFunctionTypeHints()
    {
        $stmts = self::$parser->parse('<?php
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('int', (string) $context->vars_in_scope['$a']);
    }

    public function testMethodTypeHints()
    {
        $stmts = self::$parser->parse('<?php
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('int', (string) $context->vars_in_scope['$a']);
    }

    public function testNullCoalesce()
    {
        Config::getInstance()->setCustomErrorLevel('MixedAssignment', Config::REPORT_SUPPRESS);

        $stmts = self::$parser->parse('<?php
        $a = $_GET["bar"] ?? "nobody";
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('mixed', (string) $context->vars_in_scope['$a']);
    }

    public function testSpaceship()
    {
        $stmts = self::$parser->parse('<?php
        $a = 1 <=> 1;
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('int', (string) $context->vars_in_scope['$a']);
    }

    public function testDefineArray()
    {
        $stmts = self::$parser->parse('<?php
        define("ANIMALS", [
            "dog",
            "cat",
            "bird"
        ]);

        $a = ANIMALS[1];
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('string', (string) $context->vars_in_scope['$a']);
    }

    public function testAnonymousClass()
    {
        $stmts = self::$parser->parse('<?php
        interface Logger {
            /** @return void */
            public function log(string $msg);
        }

        class Application {
            /** @var Logger|null */
            private $logger;

            /** @return void */
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testGeneratorWithReturn()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @return Generator<int,int>
         * @psalm-generator-return string
         */
        function fooFoo(int $i) : Generator {
            if ($i === 1) {
                return "bash";
            }

            yield 1;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testGeneratorDelegation()
    {
        Config::getInstance()->setCustomErrorLevel('MixedAssignment', Config::REPORT_SUPPRESS);

        $stmts = self::$parser->parse('<?php
        /**
         * @return Generator<int,int>
         * @psalm-generator-return int
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
         * @psalm-generator-return int
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('Generator<int, int>', (string) $context->vars_in_scope['$gen']);
        $this->assertEquals('mixed', (string) $context->vars_in_scope['$gen2']);
    }

    public function testMultipleUse()
    {
        $stmts = self::$parser->parse('<?php
        namespace Name\Space {
            class A {

            }

            class B {

            }
        }

        namespace Noom\Spice {
            use Name\Space\{
                A,
                B
            };

            new A();
            new B();
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }
}
