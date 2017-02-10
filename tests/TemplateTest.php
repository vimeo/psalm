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
    public function testPhanTuple()
    {
        $stmts = self::$parser->parse('<?php
        namespace Phan\Library;

        /**
         * An abstract tuple.
         */
        abstract class Tuple
        {
            const ARITY = 0;

            /**
             * @return int
             * The arity of this tuple
             */
            public function arity() : int
            {
                return (int)static::ARITY;
            }

            /**
             * @return array
             * An array of all elements in this tuple.
             */
            abstract public function toArray() : array;
        }

        /**
         * A tuple of 1 element.
         *
         * @template T0
         * The type of element zero
         */
        class Tuple1 extends Tuple
        {
            /** @var int */
            const ARITY = 1;

            /** @var T0 */
            public $_0;

            /**
             * @param T0 $_0
             * The 0th element
             */
            public function __construct($_0) {
                $this->_0 = $_0;
            }

            /**
             * @return int
             * The arity of this tuple
             */
            public function arity() : int
            {
                return (int)static::ARITY;
            }

            /**
             * @return array
             * An array of all elements in this tuple.
             */
            public function toArray() : array
            {
                return [
                    $this->_0,
                ];
            }
        }

        /**
         * A tuple of 2 elements.
         *
         * @template T0
         * The type of element zero
         *
         * @template T1
         * The type of element one
         *
         * @inherits Tuple1<T0>
         */
        class Tuple2 extends Tuple1
        {
            /** @var int */
            const ARITY = 2;

            /** @var T1 */
            public $_1;

            /**
             * @param T0 $_0
             * The 0th element
             *
             * @param T1 $_1
             * The 1st element
             */
            public function __construct($_0, $_1) {
                parent::__construct($_0);
                $this->_1 = $_1;
            }

            /**
             * @return array
             * An array of all elements in this tuple.
             */
            public function toArray() : array
            {
                return [
                    $this->_0,
                    $this->_1,
                ];
            }
        }

        $a = new Tuple2("cool", 5);

        /** @return void */
        function takes_int(int $i) {}

        /** @return void */
        function takes_string(string $s) {}

        takes_string($a->_0);
        takes_int($a->_1);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
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

    /**
     * @return void
     */
    public function testValidTemplatedStaticMethodType()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /**
             * @template T
             * @param T $x
             * @return T
             */
            public static function foo($x) {
                return $x;
            }
        }

        function bar(string $a) : void { }

        bar(A::foo("string"));
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
    public function testInvalidTemplatedStaticMethodType()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /**
             * @template T
             * @param T $x
             * @return T
             */
            public static function foo($x) {
                return $x;
            }
        }

        function bar(string $a) : void { }

        bar(A::foo(4));
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testValidTemplatedInstanceMethodType()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /**
             * @template T
             * @param T $x
             * @return T
             */
            public function foo($x) {
                return $x;
            }
        }

        function bar(string $a) : void { }

        bar((new A())->foo("string"));
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
    public function testInvalidTemplatedInstanceMethodType()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /**
             * @template T
             * @param T $x
             * @return T
             */
            public function foo($x) {
                return $x;
            }
        }

        function bar(string $a) : void { }

        bar((new A())->foo(4));
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testGenericArrayKeys()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @template T
         *
         * @param array<T, mixed> $arr
         * @return array<int, T>
         */
        function my_array_keys($arr) {
            return array_keys($arr);
        }

        $a = my_array_keys(["hello" => 5, "goodbye" => new Exception()]);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array<int, string>', (string) $context->vars_in_scope['$a']);
    }

    /**
     * @return void
     */
    public function testGenericArrayReverse()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @template TKey
         * @template TValue
         *
         * @param array<TKey, TValue> $arr
         * @return array<TValue, TKey>
         */
        function my_array_reverse($arr) {
            return array_reverse($arr);
        }

        $b = my_array_reverse(["hello" => 5, "goodbye" => 6]);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array<int, string>', (string) $context->vars_in_scope['$b']);
    }
}
