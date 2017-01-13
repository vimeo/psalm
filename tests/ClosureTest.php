<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class ClosureTest extends PHPUnit_Framework_TestCase
{
    /** @var \PhpParser\Parser */
    protected static $parser;

    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $config = new TestConfig();
    }

    public function setUp()
    {
        FileChecker::clearCache();
        $this->project_checker = new \Psalm\Checker\ProjectChecker();
    }

    public function testByRefUseVar()
    {
        $stmts = self::$parser->parse('<?php
        /** @return void */
        function run_function(\Closure $fnc) {
            $fnc();
        }

        // here we have to make sure $data exists as a side-effect of calling `run_function`
        // because it could exist depending on how run_function is implemented
        /**
         * @return void
         * @psalm-suppress MixedArgument
         */
        function fn() {
            run_function(
                /**
                 * @return void
                 */
                function() use(&$data) {
                    $data = 1;
                }
            );
            echo $data;
        }

        fn();
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidScalarArgument
     */
    public function testWrongArg()
    {
        $stmts = self::$parser->parse('<?php
        $bar = ["foo", "bar"];

        $bam = array_map(
            function(int $a) : int {
                return $a + 1;
            },
            $bar
        );
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidReturnType
     */
    public function testNoReturn()
    {
        $stmts = self::$parser->parse('<?php
        $bar = ["foo", "bar"];

        $bam = array_map(
            function(string $a) : string {
            },
            $bar
        );
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    public function testInferredArg()
    {
        $stmts = self::$parser->parse('<?php
        $bar = ["foo", "bar"];

        $bam = array_map(
            /**
             * @psalm-suppress MissingReturnType
             */
            function(string $a) {
                return $a . "blah";
            },
            $bar
        );
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    public function testVarReturnType()
    {
        $stmts = self::$parser->parse('<?php

        $add_one = function(int $a) : int {
            return $a + 1;
        };

        $a = $add_one(1);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('int', (string) $context->vars_in_scope['$a']);
    }

    public function testCallableToClosure()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @return callable
         */
        function foo() {
            return function(string $a) : string {
                return $a . "blah";
            };
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    public function testCallable()
    {
        $stmts = self::$parser->parse('<?php
        function foo(Callable $c) : void {
            echo (string)$c();
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidFunctionCall
     */
    public function testStringFunctionCall()
    {
        $stmts = self::$parser->parse('<?php
        $bad_one = "hello";
        $a = $bad_one(1);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('int', (string) $context->vars_in_scope['$a']);
    }

    public function testCorrectParamType()
    {
        $stmts = self::$parser->parse('<?php
        $take_string = function(string $s) : string { return $s; };
        $take_string("string");
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidScalarArgument
     */
    public function testWrongParamType()
    {
        $stmts = self::$parser->parse('<?php
        $take_string = function(string $s) : string { return $s; };
        $take_string(42);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
