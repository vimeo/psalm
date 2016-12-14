<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class InterfaceTest extends PHPUnit_Framework_TestCase
{
    protected static $parser;

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $config = new TestConfig();
        $config->throw_exception = true;
        $config->use_docblock_types = true;
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
}
