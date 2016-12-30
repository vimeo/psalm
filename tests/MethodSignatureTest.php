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

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $config = new TestConfig();
    }

    public function setUp()
    {
        FileChecker::clearCache();
    }

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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage Method B::fooFoo has fewer arguments than parent method A::foo
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage Argument 1 of B::fooFoo has wrong type 'bool', expecting 'int' as defined by A::foo
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }
}
