<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Checker\TypeChecker;
use Psalm\Config;
use Psalm\Context;
use Psalm\Type;

class SwitchTypeTest extends PHPUnit_Framework_TestCase
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

    public function testGetClassArg()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public function foo() {

            }
        }

        class B {
            public function bar() {

            }
        }

        $a = rand(0, 10) ? new A() : new B();

        switch (get_class($a)) {
            case "A":
                $a->foo();
                break;

            case "B":
                $a->bar();
                break;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedMethod
     */
    public function testGetClassArgWrongClass()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public function foo() {

            }
        }

        class B {
            public function bar() {

            }
        }

        $a = rand(0, 10) ? new A() : new B();

        switch (get_class($a)) {
            case "A":
                $a->bar();
                break;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testGetTypeArg()
    {
        $stmts = self::$parser->parse('<?php
        function testInt(int $var) : void {

        }

        function testString(string $var) : void {

        }

        $a = rand(0, 10) ? 1 : "two";

        switch (gettype($a)) {
            case "string":
                testString($a);
                break;

            case "int":
                testInt($a);
                break;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidScalarArgument
     */
    public function testGetTypeArgWrongArgs()
    {
        $stmts = self::$parser->parse('<?php
        function testInt(int $var) : void {

        }

        function testString(string $var) : void {

        }

        $a = rand(0, 10) ? 1 : "two";

        switch (gettype($a)) {
            case "string":
                testInt($a);

            case "int":
                testString($a);
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }
}
