<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class ArrayAccessTest extends PHPUnit_Framework_TestCase
{
    /** @var \PhpParser\Parser */
    protected static $parser;

    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }

    public function setUp()
    {
        $config = new TestConfig();
        FileChecker::clearCache();
        $this->project_checker = new \Psalm\Checker\ProjectChecker();
    }

    public function testInstanceOfStringOffset()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public function fooFoo() : void { }
        }
        function bar (array $a) : void {
            if ($a["a"] instanceof A) {
                $a["a"]->fooFoo();
            }
        }
        ');
        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    public function testInstanceOfIntOffset()
    {
        $context = new Context('somefile.php');
        $stmts = self::$parser->parse('<?php
        class A {
            public function fooFoo() : void { }
        }
        function bar (array $a) : void {
            if ($a[0] instanceof A) {
                $a[0]->fooFoo();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods($context);
    }

    public function testNotEmptyStringOffset()
    {
        $context = new Context('somefile.php');
        $stmts = self::$parser->parse('<?php
        /**
         * @param  array<string>  $a
         */
        function bar (array $a) : string {
            if ($a["bat"]) {
                return $a["bat"];
            }

            return "blah";
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods($context);
    }

    public function testNotEmptyIntOffset()
    {
        $context = new Context('somefile.php');
        $stmts = self::$parser->parse('<?php
        /**
         * @param  array<string>  $a
         */
        function bar (array $a) : string {
            if ($a[0]) {
                return $a[0];
            }

            return "blah";
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidArrayAccess
     */
    public function testInvalidArrayAccess()
    {
        $context = new Context('somefile.php');
        $stmts = self::$parser->parse('<?php
        $a = 5;
        echo $a[0];
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage MixedArrayAccess
     */
    public function testMixedArrayAccess()
    {
        Config::getInstance()->setCustomErrorLevel('MixedAssignment', Config::REPORT_SUPPRESS);

        $context = new Context('somefile.php');
        $stmts = self::$parser->parse('<?php
        /** @var mixed */
        $a = [];
        echo $a[0];
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage MixedArrayOffset
     */
    public function testMixedArrayOffset()
    {
        Config::getInstance()->setCustomErrorLevel('MixedAssignment', Config::REPORT_SUPPRESS);

        $context = new Context('somefile.php');
        $stmts = self::$parser->parse('<?php
        /** @var mixed */
        $a = 5;
        echo [1, 2, 3, 4][$a];
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage NullArrayAccess
     */
    public function testNullArrayAccess()
    {
        $context = new Context('somefile.php');
        $stmts = self::$parser->parse('<?php
        $a = null;
        echo $a[0];
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
