<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class FunctionCallTest extends PHPUnit_Framework_TestCase
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
        FileChecker::clearCache();
        $this->project_checker = new \Psalm\Checker\ProjectChecker();
        $this->project_checker->setConfig(new TestConfig());
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidScalarArgument
     * @return                   void
     */
    public function testInvalidScalarArgument()
    {
        $stmts = self::$parser->parse('<?php
        function fooFoo(int $a) : void {}
        fooFoo("string");
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage MixedArgument
     * @return                   void
     */
    public function testMixedArgument()
    {
        Config::getInstance()->setCustomErrorLevel('MixedAssignment', Config::REPORT_SUPPRESS);

        $stmts = self::$parser->parse('<?php
        function fooFoo(int $a) : void {}
        /** @var mixed */
        $a = "hello";
        fooFoo($a);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage NullArgument
     * @return                   void
     */
    public function testNullArgument()
    {
        $stmts = self::$parser->parse('<?php
        function fooFoo(int $a) : void {}
        fooFoo(null);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage TooFewArguments
     * @return                   void
     */
    public function testTooFewArguments()
    {
        $stmts = self::$parser->parse('<?php
        function fooFoo(int $a) : void {}
        fooFoo();
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage TooManyArguments
     * @return                   void
     */
    public function testTooManyArguments()
    {
        $stmts = self::$parser->parse('<?php
        function fooFoo(int $a) : void {}
        fooFoo(5, "dfd");
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage TypeCoercion
     * @return                   void
     */
    public function testTypeCoercion()
    {
        $stmts = self::$parser->parse('<?php
        class A {}
        class B extends A{}

        function fooFoo(B $b) : void {}
        fooFoo(new A());
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testTypedArrayWithDefault()
    {
        $stmts = self::$parser->parse('<?php
        class A {}

        /** @param array<A> $a */
        function fooFoo(array $a = []) : void {

        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage DuplicateParam
     * @return                   void
     */
    public function testDuplicateParam()
    {
        $stmts = self::$parser->parse('<?php
        function f($p, $p) {}
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidParamDefault
     * @return                   void
     */
    public function testInvalidParamDefault()
    {
        $stmts = self::$parser->parse('<?php
        function f(int $p = false) {}
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidParamDefault
     * @return                   void
     */
    public function testInvalidDocblockParamDefault()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @param  int $p
         * @return void
         */
        function f($p = false) {}
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testValidDocblockParamDefault()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @param  int|false $p
         * @return void
         */
        function f($p = false) {}
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testByRef()
    {
        $stmts = self::$parser->parse('<?php
        function fooFoo(string &$v) : void {}
        fooFoo($a);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidPassByReference
     * @return                   void
     */
    public function testBadByRef()
    {
        $this->markTestSkipped('Does not throw an error');
        $stmts = self::$parser->parse('<?php
        function fooFoo(string &$v) : void {}
        fooFoo("a");
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testNamespaced()
    {
        $stmts = self::$parser->parse('<?php
        namespace A;

        /** @return void */
        function f(int $p) {}
        f(5);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testNamespacedRootFunctionCall()
    {
        $stmts = self::$parser->parse('<?php
        namespace {
            /** @return void */
            function foo() { }
        }
        namespace A\B\C {
            foo();
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testNamespacedAliasedFunctionCall()
    {
        $stmts = self::$parser->parse('<?php
        namespace Aye {
            /** @return void */
            function foo() { }
        }
        namespace Bee {
            use Aye as A;

            A\foo();
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testArrayFunctions()
    {
        $stmts = self::$parser->parse('<?php
        $a = array_keys(["a" => 1, "b" => 2]);
        $b = array_values(["a" => 1, "b" => 2]);
        $c = array_combine(["a", "b", "c"], [1, 2, 3]);
        $d = array_merge(["a", "b", "c"], [1, 2, 3]);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array<int, string>', (string) $context->vars_in_scope['$a']);
        $this->assertEquals('array<int, int>', (string) $context->vars_in_scope['$b']);
        $this->assertEquals('array<string, int>', (string) $context->vars_in_scope['$c']);
        $this->assertEquals('array<int, int|string>', (string) $context->vars_in_scope['$d']);
    }

    /**
     * @return void
     */
    public function testNamespacedRootFunctionCall()
    {
        $stmts = self::$parser->parse('<?php
        namespace {
            /** @return void */
            function foo() { }
        }
        namespace A\B\C {
            foo();
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testNamespacedAliasedFunctionCall()
    {
        $stmts = self::$parser->parse('<?php
        namespace Aye {
            /** @return void */
            function foo() { }
        }
        namespace Bee {
            use Aye as A;

            A\foo();
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
