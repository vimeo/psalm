<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class TemplateTest extends PHPUnit_Framework_TestCase
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
     * @return void
     */
    public function testClassTemplate()
    {
        $stmts = self::$parser->parse('<?php
        class A {}
        class B {}
        class C {}
        class D {}

        /**
         * @template T as object
         */
        class Foo {
            /** @var string */
            public $T;

            /**
             * @param string $T
             * @template-typeof T $T
             */
            public function __construct(string $T) {
                $this->T = $T;
            }

            /**
             * @return T
             */
            public function bar() {
                $t = $this->T;
                return new $t();
            }
        }

        $at = "A";

        /** @var Foo<A> */
        $afoo = new Foo($at);
        $afoo_bar = $afoo->bar();

        $bfoo = new Foo(B::class);
        $bfoo_bar = $bfoo->bar();

        $cfoo = new Foo("C");
        $cfoo_bar = $cfoo->bar();

        $dt = "D";
        $dfoo = new Foo($dt);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('Foo<A>', (string) $context->vars_in_scope['$afoo']);
        $this->assertEquals('A', (string) $context->vars_in_scope['$afoo_bar']);

        $this->assertEquals('Foo<B>', (string) $context->vars_in_scope['$bfoo']);
        $this->assertEquals('B', (string) $context->vars_in_scope['$bfoo_bar']);

        $this->assertEquals('Foo<C>', (string) $context->vars_in_scope['$cfoo']);
        $this->assertEquals('C', (string) $context->vars_in_scope['$cfoo_bar']);

        $this->assertEquals('Foo<mixed>', (string) $context->vars_in_scope['$dfoo']);
    }

    /**
     * @return void
     */
    public function testClassTemplateContainer()
    {
        $stmts = self::$parser->parse('<?php
        class A {}

        /**
         * @template T
         */
        class Foo {
            /** @var T */
            public $obj;

            /**
             * @param T $obj
             */
            public function __construct($obj) {
                $this->obj = $obj;
            }

            /**
             * @return T
             */
            public function bar() {
                return $this->obj;
            }
        }

        $afoo = new Foo(new A());
        $afoo_bar = $afoo->bar();
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('Foo<A>', (string) $context->vars_in_scope['$afoo']);
        $this->assertEquals('A', (string) $context->vars_in_scope['$afoo_bar']);
    }

    /**
     * @return void
     */
    public function testValidTemplatedType()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @template T
         * @param T $x
         * @return T
         */
        function foo($x) {
            return $x;
        }

        function bar(string $a) : void { }

        bar(foo("string"));
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidScalarArgument
     * @return                   void
     */
    public function testInvalidTemplatedType()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @template T
         * @param T $x
         * @return T
         */
        function foo($x) {
            return $x;
        }

        function bar(string $a) : void { }

        bar(foo(4));
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
