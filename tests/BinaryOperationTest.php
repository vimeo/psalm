<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class BinaryOperationTest extends PHPUnit_Framework_TestCase
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
    public function testRegularAddition()
    {
        $stmts = self::$parser->parse('<?php
        $a = 5 + 4;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidOperand
     * @return                   void
     */
    public function testBadAddition()
    {
        $stmts = self::$parser->parse('<?php
        $a = "b" + 5;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testDifferingNumericTypesAdditionInWeakMode()
    {
        $stmts = self::$parser->parse('<?php
        $a = 5 + 4.1;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidOperand
     * @return                   void
     */
    public function testDifferingNumericTypesAdditionInStrictMode()
    {
        Config::getInstance()->strict_binary_operands = true;

        $stmts = self::$parser->parse('<?php
        $a = 5 + 4.1;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testNumericAddition()
    {
        $stmts = self::$parser->parse('<?php
        $a = "5";

        if (is_numeric($a)) {
            $b = $a + 4;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testConcatenation()
    {
        $stmts = self::$parser->parse('<?php
        $a = "Hey " . "Jude,";
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testConcatenationWithNumberInWeakMode()
    {
        $stmts = self::$parser->parse('<?php
        $a = "hi" . 5;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidOperand
     * @return                   void
     */
    public function testConcatenationWithNumberInStrictMode()
    {
        Config::getInstance()->strict_binary_operands = true;

        $stmts = self::$parser->parse('<?php
        $a = "hi" . 5;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidOperand
     * @return                   void
     */
    public function testAddArrayToNumber()
    {
        Config::getInstance()->strict_binary_operands = true;

        $stmts = self::$parser->parse('<?php
        $a = [1] + 1;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
