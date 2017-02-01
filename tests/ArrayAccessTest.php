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
        FileChecker::clearCache();
        $this->project_checker = new \Psalm\Checker\ProjectChecker();
        $this->project_checker->setConfig(new TestConfig());
    }

    /**
     * @return void
     */
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
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testInstanceOfIntOffset()
    {
        $context = new Context();
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

    /**
     * @return void
     */
    public function testNotEmptyStringOffset()
    {
        $context = new Context();
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

    /**
     * @return void
     */
    public function testNotEmptyIntOffset()
    {
        $context = new Context();
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
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidArrayAccess
     * @return                   void
     */
    public function testInvalidArrayAccess()
    {
        $context = new Context();
        $stmts = self::$parser->parse('<?php
        $a = 5;
        echo $a[0];
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage MixedArrayAccess
     * @return                   void
     */
    public function testMixedArrayAccess()
    {
        Config::getInstance()->setCustomErrorLevel('MixedAssignment', Config::REPORT_SUPPRESS);

        $context = new Context();
        $stmts = self::$parser->parse('<?php
        /** @var mixed */
        $a = [];
        echo $a[0];
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage MixedArrayOffset
     * @return                   void
     */
    public function testMixedArrayOffset()
    {
        Config::getInstance()->setCustomErrorLevel('MixedAssignment', Config::REPORT_SUPPRESS);

        $context = new Context();
        $stmts = self::$parser->parse('<?php
        /** @var mixed */
        $a = 5;
        echo [1, 2, 3, 4][$a];
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage NullArrayAccess
     * @return                   void
     */
    public function testNullArrayAccess()
    {
        $context = new Context();
        $stmts = self::$parser->parse('<?php
        $a = null;
        echo $a[0];
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
