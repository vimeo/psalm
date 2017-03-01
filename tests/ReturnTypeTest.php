<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class ReturnTypeTest extends PHPUnit_Framework_TestCase
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
     * @return void
     */
    public function testReturnTypeAfterUselessNullcheck()
    {
        $stmts = self::$parser->parse('<?php
        class One {}

        class B {
            /**
             * @return One|null
             */
            public function barBar() {
                $baz = rand(0,100) > 50 ? new One() : null;

                // should have no effect
                if ($baz === null) {
                    $baz = null;
                }

                return $baz;
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testReturnTypeNotEmptyCheck()
    {
        $stmts = self::$parser->parse('<?php
        class B {
            /**
             * @param string|null $str
             * @return string
             */
            public function barBar($str) {
                if (empty($str)) {
                    $str = "";
                }
                return $str;
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testReturnTypeNotEmptyCheckInElseIf()
    {
        $stmts = self::$parser->parse('<?php
        class B {
            /**
             * @param string|null $str
             * @return string
             */
            public function barBar($str) {
                if ($str === "badger") {
                    // do nothing
                }
                elseif (empty($str)) {
                    $str = "";
                }
                return $str;
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testReturnTypeNotEmptyCheckInElse()
    {
        $stmts = self::$parser->parse('<?php
        class B {
            /**
             * @param string|null $str
             * @return string
             */
            public function barBar($str) {
                if (!empty($str)) {
                    // do nothing
                }
                else {
                    $str = "";
                }
                return $str;
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testReturnTypeAfterIf()
    {
        $stmts = self::$parser->parse('<?php
        class B {
            /**
             * @return string|null
             */
            public function barBar() {
                $str = null;
                $bar1 = rand(0, 100) > 40;
                if ($bar1) {
                    $str = "";
                }
                return $str;
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testReturnTypeAfterTwoIfsWithThrow()
    {
        $stmts = self::$parser->parse('<?php
        class A1 {
        }
        class A2 {
        }
        class B {
            /**
             * @return A1
             */
            public function barBar(A1 $a1 = null, A2 $a2 = null) {
                if (!$a1) {
                    throw new \Exception();
                }
                if (!$a2) {
                    throw new \Exception();
                }
                return $a1;
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testReturnTypeAfterIfElseIfWithThrow()
    {
        $stmts = self::$parser->parse('<?php
        class A1 {
        }
        class A2 {
        }
        class B {
            /**
             * @return A1
             */
            public function barBar(A1 $a1 = null, A2 $a2 = null) {
                if (!$a1) {
                    throw new \Exception();
                }
                elseif (!$a2) {
                    throw new \Exception();
                }
                return $a1;
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testTryCatchReturnType()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return bool */
            public function fooFoo() {
                try {
                    // do a thing
                    return true;
                }
                catch (\Exception $e) {
                    throw $e;
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testSwitchReturnTypeWithFallthrough()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return bool */
            public function fooFoo() {
                switch (rand(0,10)) {
                    case 1:
                    default:
                        return true;
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testSwitchReturnTypeWithFallthroughAndStatement()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return bool */
            public function fooFoo() {
                switch (rand(0,10)) {
                    case 1:
                        $a = 5;
                    default:
                        return true;
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @return            void
     */
    public function testSwitchReturnTypeWithFallthroughAndBreak()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return bool */
            public function fooFoo() {
                switch (rand(0,10)) {
                    case 1:
                        break;
                    default:
                        return true;
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @return            void
     */
    public function testSwitchReturnTypeWithFallthroughAndConditionalBreak()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return bool */
            public function fooFoo() {
                switch (rand(0,10)) {
                    case 1:
                        if (rand(0,10) === 5) {
                            break;
                        }
                    default:
                        return true;
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @return            void
     */
    public function testSwitchReturnTypeWithNoDefault()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return bool */
            public function fooFoo() {
                switch (rand(0,10)) {
                    case 1:
                    case 2:
                        return true;
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testSwitchReturnTypeWitDefaultException()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /**
             * @psalm-suppress TooManyArguments
             * @return bool
             */
            public function fooFoo() {
                switch (rand(0,10)) {
                    case 1:
                    case 2:
                        return true;

                    default:
                        throw new \Exception("badness");
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testExtendsStaticCallReturnType()
    {
        $stmts = self::$parser->parse('<?php
        abstract class A {
            /** @return static */
            public static function load() {
                return new static();
            }
        }

        class B extends A {
        }

        $b = B::load();
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);

        $this->assertEquals('B', (string) $context->vars_in_scope['$b']);
    }

    /**
     * @return void
     */
    public function testExtendsStaticCallArrayReturnType()
    {
        $stmts = self::$parser->parse('<?php
        abstract class A {
            /** @return array<int,static> */
            public static function loadMultiple() {
                return [new static()];
            }
        }

        class B extends A {
        }

        $bees = B::loadMultiple();
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);

        $this->assertEquals('array<int, B>', (string) $context->vars_in_scope['$bees']);
    }

    /**
     * @return void
     */
    public function testIssetReturnType()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @param  mixed $foo
         * @return bool
         */
        function a($foo = null) {
            return isset($foo);
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testThisReturnType()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return $this */
            public function getThis() {
                return $this;
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
    public function testOverrideReturnType()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return string|null */
            public function blah() {
                return rand(0, 10) === 4 ? "blah" : null;
            }
        }

        class B extends A {
            /** @return string */
            public function blah() {
                return "blah";
            }
        }

        $blah = (new B())->blah();
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('string', (string) $context->vars_in_scope['$blah']);
    }

    /**
     * @return void
     */
    public function testInterfaceReturnType()
    {
        $stmts = self::$parser->parse('<?php
        interface A {
            /** @return string|null */
            public function blah();
        }

        class B implements A {
            public function blah() {
                return rand(0, 10) === 4 ? "blah" : null;
            }
        }

        $blah = (new B())->blah();
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('string|null', (string) $context->vars_in_scope['$blah']);
    }

    /**
     * @return void
     */
    public function testOverrideReturnTypeInGrandparent()
    {
        $stmts = self::$parser->parse('<?php
        abstract class A {
            /** @return string|null */
            abstract public function blah();
        }

        class B extends A {
        }

        class C extends B {
            public function blah() {
                return rand(0, 10) === 4 ? "blahblah" : null;
            }
        }

        $blah = (new C())->blah();
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('string|null', (string) $context->vars_in_scope['$blah']);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidReturnType
     * @return                   void
     */
    public function testWrongReturnType1()
    {
        $stmts = self::$parser->parse('<?php
        function fooFoo() : string {
            return 5;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage MoreSpecificReturnType
     * @return                   void
     */
    public function testWrongReturnType2()
    {
        $stmts = self::$parser->parse('<?php
        function fooFoo() : string {
            return rand(0, 5) ? "hello" : null;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidReturnType
     * @return                   void
     */
    public function testWrongReturnTypeInNamespace1()
    {
        $stmts = self::$parser->parse('<?php
        namespace bar;

        function fooFoo() : string {
            return 5;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage MoreSpecificReturnType
     * @return                   void
     */
    public function testWrongReturnTypeInNamespace2()
    {
        $stmts = self::$parser->parse('<?php
        namespace bar;

        function fooFoo() : string {
            return rand(0, 5) ? "hello" : null;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage MissingReturnType
     * @return                   void
     */
    public function testMissingReturnType()
    {
        $stmts = self::$parser->parse('<?php
        function fooFoo() {
            return rand(0, 5) ? "hello" : null;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage MixedInferredReturnType
     * @return                   void
     */
    public function testMixedInferredReturnType()
    {
        $stmts = self::$parser->parse('<?php
        function fooFoo() : string {
            return array_pop([]);
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testBackwardsReturnType()
    {
        $stmts = self::$parser->parse('<?php
        class A {}
        class B extends A {}

        /** @return B|A */
        function foo() {
          return rand(0, 1) ? new A : new B;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedClass
     * @return                   void
     */
    public function testInvalidReturnTypeClass()
    {
        Config::getInstance()->setCustomErrorLevel('MixedInferredReturnType', Config::REPORT_SUPPRESS);

        $stmts = self::$parser->parse('<?php
        function fooFoo() : A {
            return array_pop([]);
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedClass
     * @return                   void
     */
    public function testInvalidClassOnCall()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @psalm-suppress UndefinedClass
         * @psalm-suppress MixedInferredReturnType
         */
        function fooFoo() : A {
            return array_pop([]);
        }

        fooFoo()->bar();
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
