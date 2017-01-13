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
    public function testGetClassArg()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /**
             * @return void
             */
            public function fooFoo() {

            }
        }

        class B {
            /**
             * @return void
             */
            public function barBar() {

            }
        }

        $a = rand(0, 10) ? new A() : new B();

        switch (get_class($a)) {
            case "A":
                $a->fooFoo();
                break;

            case "B":
                $a->barBar();
                break;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedMethod
     * @return                   void
     */
    public function testGetClassArgWrongClass()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return void */
            public function fooFoo() {

            }
        }

        class B {
            /** @return void */
            public function barBar() {

            }
        }

        $a = rand(0, 10) ? new A() : new B();

        switch (get_class($a)) {
            case "A":
                $a->barBar();
                break;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
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

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidScalarArgument
     * @return                   void
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

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
