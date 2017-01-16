<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class ToStringTest extends PHPUnit_Framework_TestCase
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
        $config = new TestConfig();
        FileChecker::clearCache();
        $this->project_checker = new \Psalm\Checker\ProjectChecker();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidArgument
     * @return                   void
     */
    public function testEchoClass()
    {
        $stmts = self::$parser->parse('<?php
        class A {}
        echo (new A);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidToString
     * @return                   void
     */
    public function testInvalidToStringReturnType()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            function __toString() : void { }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidToString
     * @return                   void
     */
    public function testInvalidInferredToStringReturnType()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /**
             * @psalm-suppress MissingReturnType
             */
            function __toString() { }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testValidToString()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            function __toString() : string {
                return "hello";
            }
        }
        echo (new A);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testValidInferredToStringType()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /**
             * @psalm-suppress MissingReturnType
             */
            function __toString() {
                return "hello";
            }
        }
        echo (new A);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage ImplicitToStringCast
     * @return                   void
     */
    public function testImplicitCast()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public function __toString() : string
            {
                return "hello";
            }
        }

        function fooFoo(string $b) : void {}
        fooFoo(new A());
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
