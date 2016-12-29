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

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $config = new TestConfig();
    }

    public function setUp()
    {
        FileChecker::clearCache();
    }

    public function testExtendsAndImplements()
    {
        $stmts = self::$parser->parse('<?php
        interface A
        {
            /**
             * @return string
             */
            public function foo();
        }

        interface B
        {
            /**
             * @return string
             */
            public function bar();
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
            public function foo()
            {
                return "hello";
            }

            public function bar()
            {
                return "goodbye";
            }

            public function baz()
            {
                return "hello again";
            }
        }

        $cee = (new D())->baz();
        $dee = (new D())->foo();
        ?>
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('string', (string) $context->vars_in_scope['$cee']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$dee']);
    }

    public function testIsExtendedInterface()
    {
        $stmts = self::$parser->parse('<?php
        interface A
        {
            /**
             * @return string
             */
            public function foo();
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
            public function foo()
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testExtendsWithMethod()
    {
        $stmts = self::$parser->parse('<?php
        interface A
        {
            /**
             * @return string
             */
            public function foo();
        }

        interface B extends A
        {
            public function bar();
        }

        /** @return void */
        function mux(B $b) {
            $b->foo();
        }
        ?>
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage NoInterfaceProperties
     */
    public function testNoInterfaceProperties()
    {
        $stmts = self::$parser->parse('<?php
        interface A { }

        function foo(A $a) : void {
            if ($a->bar) {

            }
        }
        ?>
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage UnimplementedInterfaceMethod
     */
    public function testUnimplementedInterfaceMethod()
    {
        $stmts = self::$parser->parse('<?php
        interface A {
            public function foo();
        }

        class B implements A { }
        ?>
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage MethodSignatureMismatch
     */
    public function testMismatchingInterfaceMethodSignature()
    {
        $stmts = self::$parser->parse('<?php
        interface A {
            public function foo(int $a);
        }

        class B implements A {
            public function foo(string $a) {

            }
        }
        ?>
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage MethodSignatureMismatch
     */
    public function testMismatchingInterfaceMethodSignatureInTrait()
    {
        $stmts = self::$parser->parse('<?php
        interface A {
            public function foo(int $a, int $b) : void;
        }

        trait T {
            public function foo(int $a) : void {
            }
        }

        class B implements A {
            use T;
        }
        ?>
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }
}
