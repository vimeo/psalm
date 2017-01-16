<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class InterfaceTest extends PHPUnit_Framework_TestCase
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

        $config = new TestConfig();
    }

    /**
     * @return void
     */
    public function setUp()
    {
        FileChecker::clearCache();
        $this->project_checker = new \Psalm\Checker\ProjectChecker();
    }

    /**
     * @return void
     */
    public function testExtendsAndImplements()
    {
        $stmts = self::$parser->parse('<?php
        interface A
        {
            /**
             * @return string
             */
            public function fooFoo();
        }

        interface B
        {
            /**
             * @return string
             */
            public function barBar();
        }

        interface C extends A, B
        {
            /**
             * @return string
             */
            public function baz();
        }

        class D implements C
        {
            public function fooFoo()
            {
                return "hello";
            }

            public function barBar()
            {
                return "goodbye";
            }

            public function baz()
            {
                return "hello again";
            }
        }

        $cee = (new D())->baz();
        $dee = (new D())->fooFoo();
        ?>
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('string', (string) $context->vars_in_scope['$cee']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$dee']);
    }

    /**
     * @return void
     */
    public function testIsExtendedInterface()
    {
        $stmts = self::$parser->parse('<?php
        interface A
        {
            /**
             * @return string
             */
            public function fooFoo();
        }

        interface B extends A
        {
            /**
             * @return string
             */
            public function baz();
        }

        class C implements B
        {
            public function fooFoo()
            {
                return "hello";
            }

            public function baz()
            {
                return "goodbye";
            }
        }

        /**
         * @param  A      $a
         * @return void
         */
        function qux(A $a) {
        }

        qux(new C());
        ?>
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testExtendsWithMethod()
    {
        $stmts = self::$parser->parse('<?php
        interface A
        {
            /**
             * @return string
             */
            public function fooFoo();
        }

        interface B extends A
        {
            public function barBar();
        }

        /** @return void */
        function mux(B $b) {
            $b->fooFoo();
        }
        ?>
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage NoInterfaceProperties
     * @return                   void
     */
    public function testNoInterfaceProperties()
    {
        $stmts = self::$parser->parse('<?php
        interface A { }

        function fooFoo(A $a) : void {
            if ($a->bar) {

            }
        }
        ?>
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage UnimplementedInterfaceMethod
     * @return                   void
     */
    public function testUnimplementedInterfaceMethod()
    {
        $stmts = self::$parser->parse('<?php
        interface A {
            public function fooFoo();
        }

        class B implements A { }
        ?>
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage MethodSignatureMismatch
     * @return                   void
     */
    public function testMismatchingInterfaceMethodSignature()
    {
        $stmts = self::$parser->parse('<?php
        interface A {
            public function fooFoo(int $a) : void;
        }

        class B implements A {
            public function fooFoo(string $a) : void {

            }
        }
        ?>
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testCorrectInterfaceMethodSignature()
    {
        $stmts = self::$parser->parse('<?php
        interface A {
            public function fooFoo(int $a) : void;
        }

        class B implements A {
            public function fooFoo(int $a) : void {

            }
        }
        ?>
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testInterfaceMethodImplementedInParent()
    {
        $stmts = self::$parser->parse('<?php
        interface MyInterface {
            public function fooFoo(int $a) : void;
        }

        class B {
            public function fooFoo(int $a) : void {

            }
        }

        class C extends B implements MyInterface { }
        ?>
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage MethodSignatureMismatch
     * @return                   void
     */
    public function testMismatchingInterfaceMethodSignatureInTrait()
    {
        $stmts = self::$parser->parse('<?php
        interface A {
            public function fooFoo(int $a, int $b) : void;
        }

        trait T {
            public function fooFoo(int $a) : void {
            }
        }

        class B implements A {
            use T;
        }
        ?>
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testInterfaceMethodSignatureInTrait()
    {
        $stmts = self::$parser->parse('<?php
        interface A {
            public function fooFoo(int $a, int $b) : void;
        }

        trait T {
            public function fooFoo(int $a, int $b) : void {
            }
        }

        class B implements A {
            use T;
        }
        ?>
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage MethodSignatureMismatch
     * @return                   void
     */
    public function testMismatchingInterfaceMethodSignatureInImplementer()
    {
        $stmts = self::$parser->parse('<?php
        interface A {
            public function fooFoo(int $a, int $b) : void;
        }

        trait T {
            public function fooFoo(int $a, int $b) : void {
            }
        }

        class B implements A {
            use T;

            public function fooFoo(int $a) : void {
            }
        }
        ?>
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testDelayedInterface()
    {
        $stmts = self::$parser->parse('<?php
        // fails in PHP, whatcha gonna do
        $c = new C;

        class A { }

        interface B { }

        class C extends A implements B { }
        ');
        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedClass
     * @return                   void
     */
    public function testInvalidImplements()
    {
        $this->project_checker->registerFile(
            getcwd() . '/somefile.php',
            '<?php
        class C2 implements A {
        }
        ');
        $file_checker = new FileChecker(getcwd() . '/somefile.php', $this->project_checker);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testTypeDoesNotContainType()
    {
        $stmts = self::$parser->parse('<?php
        interface A { }
        interface B {
            function foo();
        }
        function bar(A $a) : void {
            if ($a instanceof B) {
                $a->foo();
            }
        }');
        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testAbstractInterfaceImplements()
    {
        $stmts = self::$parser->parse('<?php
        interface I {
            public function fnc();
        }

        abstract class A implements I {}
        ');
        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage UnimplementedInterfaceMethod
     * @return                   void
     */
    public function testAbstractInterfaceImplementsWithSubclass()
    {
        $stmts = self::$parser->parse('<?php
        interface I {
            public function fnc();
        }

        abstract class A implements I {}

        class B extends A {}
        ');
        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
