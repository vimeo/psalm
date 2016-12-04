<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class Php71Test extends PHPUnit_Framework_TestCase
{
    protected static $parser;

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $config = Config::getInstance();
        $config->throw_exception = true;
        $config->use_docblock_types = true;
    }

    public function setUp()
    {
        FileChecker::clearCache();
    }

    public function testNullableReturnType()
    {
        $stmts = self::$parser->parse('<?php
        function a(): ?string
        {
            return rand(0, 10) ? "elePHPant" : null;
        }

        $a = a();
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('string|null', (string) $context->vars_in_scope['$a']);
    }

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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testPrivateClassConst()
    {
        $stmts = self::$parser->parse('<?php
        class A
        {
            private const IS_PRIVATE = 1;

            function foo() : int {
                return A::IS_PRIVATE;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InaccessibleClassConstant
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InaccessibleClassConstant
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
            function foo() : int {
                return A::IS_PRIVATE;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testProtectedClassConst()
    {
        $stmts = self::$parser->parse('<?php
        class A
        {
            protected const IS_PROTECTED = 1;
        }

        class B extends A
        {
            function foo() : int {
                return A::IS_PROTECTED;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InaccessibleClassConstant
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

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
            function foo() : int {
                echo A::IS_PUBLIC;
                return A::IS_ALSO_PUBLIC;
            }
        }

        echo A::IS_PUBLIC;
        echo A::IS_ALSO_PUBLIC;
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('string|int', (string) $context->vars_in_scope['$id1']);
        $this->assertEquals('string|int', (string) $context->vars_in_scope['$name1']);
        $this->assertEquals('string|int', (string) $context->vars_in_scope['$id2']);
        $this->assertEquals('string|int', (string) $context->vars_in_scope['$name2']);
    }

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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('int', (string) $context->vars_in_scope['$id1']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$name1']);
        $this->assertEquals('int', (string) $context->vars_in_scope['$id2']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$name2']);
    }

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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('null|int', (string) $context->vars_in_scope['$last_id']);
        $this->assertEquals('null|string', (string) $context->vars_in_scope['$last_name']);
    }

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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('null|int', (string) $context->vars_in_scope['$last_id']);
        $this->assertEquals('null|string', (string) $context->vars_in_scope['$last_name']);
    }
}
