<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class LoopScopeTest extends PHPUnit_Framework_TestCase
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
     * @expectedExceptionMessage PossiblyUndefinedVariable - somefile.php:3 - Possibly undefined variable $array, first seen on line 3
     * @return                   void
     */
    public function testPossiblyUndefinedArrayInForeach()
    {
        $stmts = self::$parser->parse('<?php
        foreach ([1, 2, 3, 4] as $b) {
            $array[] = "hello";
        }

        echo $array;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage PossiblyUndefinedVariable - somefile.php:4 - Possibly undefined variable $array, first seen on line 4
     * @return                   void
     */
    public function testPossiblyUndefinedArrayInWhileAndForeach()
    {
        $stmts = self::$parser->parse('<?php
        for ($i = 0; $i < 4; $i++) {
            while (rand(0,10) === 5) {
                $array[] = "hello";
            }
        }

        echo $array;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage PossiblyUndefinedVariable - somefile.php:6 - Possibly undefined variable $car, first seen on line 3
     * @return                   void
     */
    public function testPossiblyUndefinedVariableInForeach()
    {
        $stmts = self::$parser->parse('<?php
        foreach ([1, 2, 3, 4] as $b) {
            $car = "Volvo";
        }

        echo $car;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testSwitchVariableWithContinue()
    {
        $stmts = self::$parser->parse('<?php
        foreach ([\'a\', \'b\', \'c\'] as $letter) {
            switch ($letter) {
                case \'a\':
                    $foo = 1;
                    break;
                case \'b\':
                    $foo = 2;
                    break;
                default:
                    continue;
            }

            $moo = $foo;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testSwitchVariableWithContinueAndIfs()
    {
        $stmts = self::$parser->parse('<?php
        foreach ([\'a\', \'b\', \'c\'] as $letter) {
            switch ($letter) {
                case \'a\':
                    if (rand(0, 10) === 1) {
                        continue;
                    }
                    $foo = 1;
                    break;
                case \'b\':
                    if (rand(0, 10) === 1) {
                        continue;
                    }
                    $foo = 2;
                    break;
                default:
                    continue;
            }

            $moo = $foo;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testSwitchVariableWithFallthrough()
    {
        $stmts = self::$parser->parse('<?php
        foreach ([\'a\', \'b\', \'c\'] as $letter) {
            switch ($letter) {
                case \'a\':
                case \'b\':
                    $foo = 2;
                    break;

                default:
                    $foo = 3;
                    break;
            }

            $moo = $foo;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testSwitchVariableWithFallthroughStatement()
    {
        $stmts = self::$parser->parse('<?php
        foreach ([\'a\', \'b\', \'c\'] as $letter) {
            switch ($letter) {
                case \'a\':
                    $bar = 1;

                case \'b\':
                    $foo = 2;
                    break;

                default:
                    $foo = 3;
                    break;
            }

            $moo = $foo;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testWhileVar()
    {
        $stmts = self::$parser->parse('<?php

        $worked = false;

        while (rand(0,100) === 10) {
            $worked = true;
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
    public function testDoWhileVar()
    {
        $stmts = self::$parser->parse('<?php
        $worked = false;

        do {
            $worked = true;
        }
        while (rand(0,100) === 10);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('bool', (string) $context->vars_in_scope['$worked']);
    }

     /**
     * @return void
     */
    public function testDoWhileVarAndBreak()
    {
        $stmts = self::$parser->parse('<?php
        /** @return void */
        function foo(string $b) {}

        do {
            if (null === ($a = rand(0, 1) ? "hello" : null)) {
                break;
            }

            foo($a);
        }
        while (rand(0,100) === 10);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage PossiblyUndefinedVariable - somefile.php:9 - Possibly undefined variable $a, first seen on line 4
     * @return                   void
     */
    public function testPossiblyUndefinedVariableInForeachAndIf()
    {
        $stmts = self::$parser->parse('<?php
        foreach ([1,2,3,4] as $i) {
            if ($i === 1) {
                $a = true;
                break;
            }
        }

        echo $a;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testObjectValue()
    {
        $stmts = self::$parser->parse('<?php
        class B {}
        class A {
            /** @var A|B */
            public $child;

            public function __construct() {
                $this->child = rand(0, 1) ? new A() : new B();
            }
        }

        function makeA() : A {
            return new A();
        }

        $a = makeA();

        while ($a instanceof A) {
            $a = $a->child;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('B', (string) $context->vars_in_scope['$a']);
    }

    /**
     * @return void
     */
    public function testSecondLoopWithNotNullCheck()
    {
        $stmts = self::$parser->parse('<?php
        /** @return void **/
        function takesInt(int $i) {}

        $a = null;

        foreach ([1, 2, 3] as $i) {
            if ($a !== null) takesInt($a);
            $a = $i;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testSecondLoopWithIntCheck()
    {
        $stmts = self::$parser->parse('<?php
        /** @return void **/
        function takesInt(int $i) {}

        $a = null;

        foreach ([1, 2, 3] as $i) {
            if (is_int($a)) takesInt($a);
            $a = $i;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testSecondLoopWithIntCheckAndConditionalSet()
    {
        $stmts = self::$parser->parse('<?php
        /** @return void **/
        function takesInt(int $i) {}

        $a = null;

        foreach ([1, 2, 3] as $i) {
            if (is_int($a)) takesInt($a);

            if (rand(0, 1)) {
                $a = $i;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testSecondLoopWithIntCheckAndAssignmentsInIfAndElse()
    {
        $stmts = self::$parser->parse('<?php
        /** @return void **/
        function takesInt(int $i) {}

        $a = null;

        foreach ([1, 2, 3] as $i) {
            if (is_int($a)) {
                $a = 6;
            } else {
                $a = $i;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testSecondLoopWithIntCheckAndLoopSet()
    {
        $stmts = self::$parser->parse('<?php
        /** @return void **/
        function takesInt(int $i) {}

        $a = null;

        foreach ([1, 2, 3] as $i) {
            if (is_int($a)) takesInt($a);

            while (rand(0, 1)) {
                $a = $i;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testSecondLoopWithReturnInElseif()
    {
        $stmts = self::$parser->parse('<?php
        class A {}
        class B extends A {}
        class C extends A {}

        $b = null;

        foreach ([new A, new A] as $a) {
            if ($a instanceof B) {

            } elseif (!$a instanceof C) {
                return "goodbye";
            }

            if ($b instanceof C) {
                return "hello";
            }

            $b = $a;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testThirdLoopWithIntCheckAndLoopSet()
    {
        $stmts = self::$parser->parse('<?php
        /** @return void **/
        function takesInt(int $i) {}

        $a = null;
        $b = null;

        foreach ([1, 2, 3] as $i) {
            if ($b !== null) {
                takesInt($b);
            }

            if ($a !== null) {
                takesInt($a);
                $b = $a;
            }

            $a = $i;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testAssignInsideForeach()
    {
        $stmts = self::$parser->parse('<?php
        $b = false;

        foreach ([1, 2, 3, 4] as $a) {
            if ($a === rand(0, 10)) {
                $b = true;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);

        $this->assertSame('bool', (string) $context->vars_in_scope['$b']);
    }

    /**
     * @return void
     */
    public function testAssignInsideForeachWithBreak()
    {
        $stmts = self::$parser->parse('<?php
        $b = false;

        foreach ([1, 2, 3, 4] as $a) {
            if ($a === rand(0, 10)) {
                $b = true;
                break;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);

        $this->assertSame('bool', (string) $context->vars_in_scope['$b']);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage PossiblyNullReference
     * @return                   void
     */
    public function testPossiblyNullCheckInsideForeachWithNoLeaveStatement()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return array<A|null> */
            public static function loadMultiple()
            {
                return [new A, null];
            }

            /** @return void */
            public function barBar() {

            }
        }

        foreach (A::loadMultiple() as $a) {
            if ($a === null) {
                // do nothing
            }

            $a->barBar();
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testNullCheckInsideForeachWithContinue()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return array<A|null> */
            public static function loadMultiple()
            {
                return [new A, null];
            }

            /** @return void */
            public function barBar() {

            }
        }

        foreach (A::loadMultiple() as $a) {
            if ($a === null) {
                continue;
            }

            $a->barBar();
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }
}
