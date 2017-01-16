<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class MethodSignatureTest extends PHPUnit_Framework_TestCase
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
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage Method B::fooFoo has more arguments than parent method A::fooFoo
     * @return                   void
     */
    public function testMoreArguments()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public function fooFoo(int $a, bool $b) : void {

            }
        }

        class B extends A {
            public function fooFoo(int $a, bool $b, array $c) : void {

            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage Method B::fooFoo has fewer arguments than parent method A::fooFoo
     * @return                   void
     */
    public function testFewerArguments()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public function fooFoo(int $a, bool $b) : void {

            }
        }

        class B extends A {
            public function fooFoo(int $a) : void {

            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage Argument 1 of B::fooFoo has wrong type 'bool', expecting 'int' as defined by A::foo
     * @return                   void
     */
    public function testDifferentArguments()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public function fooFoo(int $a, bool $b) : void {

            }
        }

        class B extends A {
            public function fooFoo(bool $b, int $a) : void {

            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
