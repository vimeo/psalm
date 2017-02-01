<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class Php71Test extends PHPUnit_Framework_TestCase
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
    public function testNullableReturnType()
    {
        $stmts = self::$parser->parse('<?php
        function a(): ?string
        {
            return rand(0, 10) ? "elePHPant" : null;
        }

        $a = a();
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('string|null', (string) $context->vars_in_scope['$a']);
    }

    /**
     * @return void
     */
    public function testNullableReturnTypeInDocblock()
    {
        $stmts = self::$parser->parse('<?php
        /** @return ?string */
        function a() {
            return rand(0, 10) ? "elePHPant" : null;
        }

        $a = a();
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('null|string', (string) $context->vars_in_scope['$a']);
    }

    /**
     * @return void
     */
    public function testNullableArgument()
    {
        $stmts = self::$parser->parse('<?php
        function test(?string $name) : ?string
        {
            return $name;
        }

        test("elePHPant");
        test(null);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testPrivateClassConst()
    {
        $stmts = self::$parser->parse('<?php
        class A
        {
            private const IS_PRIVATE = 1;

            function fooFoo() : int {
                return A::IS_PRIVATE;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InaccessibleClassConstant
     * @return                   void
     */
    public function testInvalidPrivateClassConstFetch()
    {
        $stmts = self::$parser->parse('<?php
        class A
        {
            private const IS_PRIVATE = 1;
        }

        echo A::IS_PRIVATE;
        ');

        $file_checker = new FileChecker('/somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InaccessibleClassConstant
     * @return                   void
     */
    public function testInvalidPrivateClassConstFetchFromSubclass()
    {
        $stmts = self::$parser->parse('<?php
        class A
        {
            private const IS_PRIVATE = 1;
        }

        class B extends A
        {
            function fooFoo() : int {
                return A::IS_PRIVATE;
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
    public function testProtectedClassConst()
    {
        $stmts = self::$parser->parse('<?php
        class A
        {
            protected const IS_PROTECTED = 1;
        }

        class B extends A
        {
            function fooFoo() : int {
                return A::IS_PROTECTED;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InaccessibleClassConstant
     * @return                   void
     */
    public function testInvalidProtectedClassConstFetch()
    {
        $stmts = self::$parser->parse('<?php
        class A
        {
            protected const IS_PROTECTED = 1;
        }

        echo A::IS_PROTECTED;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testPublicClassConstFetch()
    {
        $stmts = self::$parser->parse('<?php
        class A
        {
            public const IS_PUBLIC = 1;
            const IS_ALSO_PUBLIC = 2;
        }

        class B extends A
        {
            function fooFoo() : int {
                echo A::IS_PUBLIC;
                return A::IS_ALSO_PUBLIC;
            }
        }

        echo A::IS_PUBLIC;
        echo A::IS_ALSO_PUBLIC;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testArrayDestructuring()
    {
        $stmts = self::$parser->parse('<?php
        $data = [
            [1, "Tom"],
            [2, "Fred"],
        ];

        // list() style
        list($id1, $name1) = $data[0];

        // [] style
        [$id2, $name2] = $data[1];
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('string|int', (string) $context->vars_in_scope['$id1']);
        $this->assertEquals('string|int', (string) $context->vars_in_scope['$name1']);
        $this->assertEquals('string|int', (string) $context->vars_in_scope['$id2']);
        $this->assertEquals('string|int', (string) $context->vars_in_scope['$name2']);
    }

    /**
     * @return void
     */
    public function testArrayDestructuringInForeach()
    {
        $stmts = self::$parser->parse('<?php
        $data = [
            [1, "Tom"],
            [2, "Fred"],
        ];

        // [] style
        foreach ($data as [$id, $name]) {
            echo $id;
            echo $name;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testArrayDestructuringWithKeys()
    {
        $stmts = self::$parser->parse('<?php
        $data = [
            ["id" => 1, "name" => "Tom"],
            ["id" => 2, "name" => "Fred"],
        ];

        // list() style
        list("id" => $id1, "name" => $name1) = $data[0];

        // [] style
        ["id" => $id2, "name" => $name2] = $data[1];
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('int', (string) $context->vars_in_scope['$id1']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$name1']);
        $this->assertEquals('int', (string) $context->vars_in_scope['$id2']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$name2']);
    }

    /**
     * @return void
     */
    public function testArrayListDestructuringInForeachWithKeys()
    {
        $stmts = self::$parser->parse('<?php
        $data = [
            ["id" => 1, "name" => "Tom"],
            ["id" => 2, "name" => "Fred"],
        ];

        $last_id = null;
        $last_name = null;

        // list() style
        foreach ($data as list("id" => $id, "name" => $name)) {
            $last_id = $id;
            $last_name = $name;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('null|int', (string) $context->vars_in_scope['$last_id']);
        $this->assertEquals('null|string', (string) $context->vars_in_scope['$last_name']);
    }

    /**
     * @return void
     */
    public function testArrayDestructuringInForeachWithKeys()
    {
        $stmts = self::$parser->parse('<?php
        $data = [
            ["id" => 1, "name" => "Tom"],
            ["id" => 2, "name" => "Fred"],
        ];

        $last_id = null;
        $last_name = null;

        // [] style
        foreach ($data as ["id" => $id, "name" => $name]) {
            $last_id = $id;
            $last_name = $name;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('null|int', (string) $context->vars_in_scope['$last_id']);
        $this->assertEquals('null|string', (string) $context->vars_in_scope['$last_name']);
    }

    /**
     * @return void
     */
    public function testIterableArg()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @param  iterable<int> $iter
         */
        function iterator(iterable $iter) : void
        {
            foreach ($iter as $val) {
                //
            }
        }

        iterator([1, 2, 3, 4]);
        iterator(new SplFixedArray(5));
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidArgument
     * @return                   void
     */
    public function testInvalidIterableArg()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @param  iterable<string> $iter
         */
        function iterator(iterable $iter) : void
        {
            foreach ($iter as $val) {
                //
            }
        }

        class A {
        }

        iterator(new A());
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testTraversableObject()
    {
        $stmts = self::$parser->parse('<?php
        class IteratorObj implements Iterator {
            function rewind() : void {}
            /** @return mixed */
            function current() { return null; }
            function key() : int { return 0; }
            function next() : void {}
            function valid() : bool { return false; }
        }

        function foo(\Traversable $t) : void {
        }

        foo(new IteratorObj);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
