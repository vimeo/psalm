<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class IssetTest extends PHPUnit_Framework_TestCase
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
    public function testIsset()
    {
        Config::getInstance()->setCustomErrorLevel('MixedAssignment', Config::REPORT_SUPPRESS);

        $file_checker = new FileChecker(
            'somefile.php',
            $this->project_checker,
            self::$parser->parse('<?php
            $a = isset($b) ? $b : null;
            ')
        );
        $context = new Context();
        $context->vars_in_scope['$foo'] = \Psalm\Type::getArray();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('mixed', (string) $context->vars_in_scope['$a']);
    }

    /**
     * @return void
     */
    public function testNullCoalesce()
    {
        Config::getInstance()->setCustomErrorLevel('MixedAssignment', Config::REPORT_SUPPRESS);

        $file_checker = new FileChecker(
            'somefile.php',
            $this->project_checker,
            self::$parser->parse('<?php
            $a = $b ?? null;
            ')
        );
        $context = new Context();
        $context->vars_in_scope['$foo'] = \Psalm\Type::getArray();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('mixed', (string) $context->vars_in_scope['$a']);
    }

    /**
     * @return void
     */
    public function testNullCoalesceWithGoodVariable()
    {
        $file_checker = new FileChecker(
            'somefile.php',
            $this->project_checker,
            self::$parser->parse('<?php
            $b = false;
            $a = $b ?? null;
            ')
        );
        $context = new Context();
        $context->vars_in_scope['$foo'] = \Psalm\Type::getArray();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('false|null', (string) $context->vars_in_scope['$a']);
    }

    /**
     * @return void
     */
    public function testIssetKeyedOffset()
    {
        $file_checker = new FileChecker(
            'somefile.php',
            $this->project_checker,
            self::$parser->parse('<?php
                if (!isset($foo["a"])) {
                    $foo["a"] = "hello";
                }
            ')
        );
        $context = new Context();
        $context->vars_in_scope['$foo'] = \Psalm\Type::getArray();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('mixed', (string) $context->vars_in_scope['$foo[\'a\']']);
    }

    /**
     * @return void
     */
    public function testIssetKeyedOffsetOrFalse()
    {
        $file_checker = new FileChecker(
            'somefile.php',
            $this->project_checker,
            self::$parser->parse('<?php
            /** @return void */
            function takesString(string $str) {}

            $bar = rand(0, 1) ? ["foo" => "bar"] : false;

            if (isset($bar["foo"])) {
                takesString($bar["foo"]);
            }
            ')
        );
        $context = new Context();
        $context->vars_in_scope['$foo'] = \Psalm\Type::getArray();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testNullCoalesceKeyedOffset()
    {
        Config::getInstance()->setCustomErrorLevel('MixedAssignment', Config::REPORT_SUPPRESS);

        $file_checker = new FileChecker(
            'somefile.php',
            $this->project_checker,
            self::$parser->parse('<?php
                $foo["a"] = $foo["a"] ?? "hello";
            ')
        );
        $context = new Context();
        $context->vars_in_scope['$foo'] = \Psalm\Type::getArray();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('mixed', (string) $context->vars_in_scope['$foo[\'a\']']);
    }
}
