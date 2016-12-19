<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class ListTest extends PHPUnit_Framework_TestCase
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

    public function testSimpleVars()
    {
        $stmts = self::$parser->parse('<?php
        list($a, $b) = ["a", "b"];
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('string', (string) $context->vars_in_scope['$a']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$b']);
    }

    public function testSimpleVarsWithSeparateTypes()
    {
        $stmts = self::$parser->parse('<?php
        list($a, $b) = ["a", 2];
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('string', (string) $context->vars_in_scope['$a']);
        $this->assertEquals('int', (string) $context->vars_in_scope['$b']);
    }

    public function testSimpleVarsWithSeparateTypesInVar()
    {
        $stmts = self::$parser->parse('<?php
        $bar = ["a", 2];
        list($a, $b) = $bar;
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('int|string', (string) $context->vars_in_scope['$a']);
        $this->assertEquals('int|string', (string) $context->vars_in_scope['$b']);
    }

    public function testThisVar()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var string */
            public $a;

            /** @var string */
            public $b;

            public function foo() : string
            {
                list($this->a, $this->b) = ["a", "b"];

                return $this->a;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidPropertyAssignment - somefile.php:11
     */
    public function testThisVarWithBadType()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var int */
            public $a;

            /** @var string */
            public $b;

            public function foo() : string
            {
                list($this->a, $this->b) = ["a", "b"];

                return $this->a;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }
}
