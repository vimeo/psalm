<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class ScopeTest extends PHPUnit_Framework_TestCase
{
    /** @var \PhpParser\Parser */
    protected static $parser;

    /** @var TestConfig */
    protected static $config;

    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    /**
     * @return void
     */
    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        self::$config = new TestConfig();
    }

    /**
     * @return void
     */
    public function setUp()
    {
        FileChecker::clearCache();
        $this->project_checker = new \Psalm\Checker\ProjectChecker();
        $this->project_checker->setConfig(self::$config);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage PossiblyUndefinedVariable - somefile.php:6 - Possibly undefined variable $b, first seen on line 3
     * @return                   void
     */
    public function testPossiblyUndefinedVarInIf()
    {
        $stmts = self::$parser->parse('<?php
        if (rand(0,100) === 10) {
            $b = "s";
        }

        echo $b;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testNewVarInIf()
    {
        $stmts = self::$parser->parse('<?php
        if (rand(0,100) === 10) {
            $badge = "hello";
        }
        else {
            $badge = "goodbye";
        }

        echo $badge;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testNewVarInIfWithElseReturn()
    {
        $stmts = self::$parser->parse('<?php
        if (rand(0,100) === 10) {
            $badge = "hello";
        }
        else {
            throw new \Exception();
        }

        echo $badge;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage PossiblyUndefinedVariable - somefile.php:3 - Possibly undefined variable $array, first seen on line 3
     * @return                   void
     */
    public function testPossiblyUndefinedArrayInIf()
    {
        $stmts = self::$parser->parse('<?php
        if (rand(0,100) === 10) {
            $array[] = "hello";
        }

        echo $array;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }



    /**
     * @return void
     */
    public function testTryCatchVar()
    {
        $stmts = self::$parser->parse('<?php
        try {
            $worked = true;
        }
        catch (\Exception $e) {
            $worked = false;
        }

        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('bool', (string) $context->vars_in_scope['$worked']);
    }

    /**
     * @return void
     */
    public function testAssignmentInIf()
    {
        $stmts = self::$parser->parse('<?php
        if ($row = (rand(0, 10) ? [5] : null)) {
            echo $row[0];
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testNegatedAssignmentInIf()
    {
        $stmts = self::$parser->parse('<?php
        if (!($row = (rand(0, 10) ? [5] : null))) {
            // do nothing
        }
        else {
            echo $row[0];
        }

        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testAssignInElseIf()
    {
        $stmts = self::$parser->parse('<?php
        if (rand(0, 10) > 5) {
            echo "hello";
        } elseif ($row = (rand(0, 10) ? [5] : null)) {
            echo $row[0];
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testIfNotEqualsFalse()
    {
        $stmts = self::$parser->parse('<?php
        if (($row = rand(0,10) ? [1] : false) !== false) {
           echo $row[0];
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testIfNotEqualsNull()
    {
        $stmts = self::$parser->parse('<?php
        if (($row = rand(0,10) ? [1] : null) !== null) {
           echo $row[0];
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testIfNullNotEquals()
    {
        $stmts = self::$parser->parse('<?php
        if (null !== ($row = rand(0,10) ? [1] : null)) {
           echo $row[0];
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testIfNullEquals()
    {
        $stmts = self::$parser->parse('<?php
        if (null === ($row = rand(0,10) ? [1] : null)) {

        } else {
            echo $row[0];
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testPassByRefInIf()
    {
        $stmts = self::$parser->parse('<?php
        if (preg_match("/bad/", "badger", $matches)) {
            echo (string)$matches[0];
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testPassByRefInIfCheckAfter()
    {
        $stmts = self::$parser->parse('<?php
        if (!preg_match("/bad/", "badger", $matches)) {
            exit();
        }
        echo (string)$matches[0];
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testPassByRefInIfWithBoolean()
    {
        $stmts = self::$parser->parse('<?php
        $a = true;
        if ($a && preg_match("/bad/", "badger", $matches)) {
            echo (string)$matches[0];
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testPassByRefInIVarWithBoolean()
    {
        $stmts = self::$parser->parse('<?php
        $a = preg_match("/bad/", "badger", $matches) > 0;
        if ($a) {
            echo (string)$matches[1];
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testFunctionExists()
    {
        $stmts = self::$parser->parse('<?php
        if (true && function_exists("flabble")) {
            flabble();
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testNestedPropertyFetchInElseif()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var A|null */
            public $foo;

            public function __toString() : string {
                return "boop";
            }
        }

        $a = rand(0, 10) === 5 ? new A() : null;

        if (false) {

        }
        elseif ($a && $a->foo) {
            echo $a;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testGlobalReturn()
    {
        $stmts = self::$parser->parse('<?php
        $foo = "foo";

        function a() : string {
            global $foo;

            return $foo;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testStatic()
    {
        $stmts = self::$parser->parse('<?php
        function a() : string {
            static $foo = "foo";

            return $foo;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidGlobal
     * @return                   void
     */
    public function testInvalidGlobal()
    {
        $stmts = self::$parser->parse('<?php
        $a = "heli";

        global $a;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidStaticVariable
     * @return                   void
     */
    public function testThisInStatic()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public static function fooFoo() {
                echo $this;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testTwoVarLogic()
    {
        $stmts = self::$parser->parse('<?php
        $a = rand(0, 10) ? "hello" : null;
        $b = rand(0, 10) ? "goodbye" : null;

        if ($a !== null || $b !== null) {
            if ($a !== null) {
                $c = $a;
            } else {
                $c = $b;
            }

            echo strpos($c, "e");
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testThreeVarLogic()
    {
        $stmts = self::$parser->parse('<?php
        $a = rand(0, 10) ? "hello" : null;
        $b = rand(0, 10) ? "goodbye" : null;
        $c = rand(0, 10) ? "hello" : null;

        if ($a !== null || $b !== null || $c !== null) {
            if ($a !== null) {
                $d = $a;
            } elseif ($b !== null) {
                $d = $b;
            } else {
                $d = $c;
            }

            echo strpos($d, "e");
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage NullArgument
     * @return                   void
     */
    public function testThreeVarLogicWithChange()
    {
        $stmts = self::$parser->parse('<?php
        $a = rand(0, 10) ? "hello" : null;
        $b = rand(0, 10) ? "goodbye" : null;
        $c = rand(0, 10) ? "hello" : null;

        if ($a !== null || $b !== null || $c !== null) {
            $c = null;

            if ($a !== null) {
                $d = $a;
            } elseif ($b !== null) {
                $d = $b;
            } else {
                $d = $c;
            }

            echo strpos($d, "e");
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage NullArgument
     * @return                   void
     */
    public function testThreeVarLogicWithException()
    {
        $stmts = self::$parser->parse('<?php
        $a = rand(0, 10) ? "hello" : null;
        $b = rand(0, 10) ? "goodbye" : null;
        $c = rand(0, 10) ? "hello" : null;

        if ($a !== null || $b !== null || $c !== null) {
            if ($c !== null) {
                throw new \Exception("bad");
            }

            if ($a !== null) {
                $d = $a;
            } elseif ($b !== null) {
                $d = $b;
            } else {
                $d = $c;
            }

            echo strpos($d, "e");
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testNegateAssertionAndOther()
    {
        $stmts = self::$parser->parse('<?php
        $a = rand(0, 10) ? "hello" : null;

        if (rand(0, 10) > 1 && is_string($a)) {
            throw new \Exception("bad");
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('string|null', (string) $context->vars_in_scope['$a']);
    }

    /**
     * @return void
     */
    public function testRepeatAssertionWithOther()
    {
        $stmts = self::$parser->parse('<?php
        $a = rand(0, 10) ? "hello" : null;

        if (rand(0, 10) > 1 || is_string($a)) {
            if (is_string($a)) {
                echo strpos("e", $a);
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('string|null', (string) $context->vars_in_scope['$a']);
    }

    /**
     * @return void
     */
    public function testRefineORedType()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public function doThing() : void
            {
                if ($this instanceof B || $this instanceof C) {
                    if ($this instanceof B) {

                    }
                }
            }
        }
        class B extends A {}
        class C extends A {}
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testIntstanceOfSubtraction()
    {
        $stmts = self::$parser->parse('<?php
        class Foo {}
        class FooBar extends Foo{}
        class FooBarBat extends FooBar{}
        class FooMoo extends Foo{}

        $a = new Foo();

        if ($a instanceof FooBar && !$a instanceof FooBarBat) {

        } elseif ($a instanceof FooMoo) {

        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }
}
