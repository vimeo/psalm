<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class BadFormatTest extends PHPUnit_Framework_TestCase
{
    /** @var \PhpParser\Parser */
    protected static $parser;

    /** @var TestConfig */
    protected static $config;

    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    /**
     * @return void
     */
    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        self::$config = new TestConfig();
    }

    /**
     * @return void
     */
    public function setUp()
    {
        FileChecker::clearCache();
        $this->project_checker = new \Psalm\Checker\ProjectChecker();
        $this->project_checker->setConfig(self::$config);
    }

    /**
     * @return void
     */
    public function testMissingSemicolon()
    {
        $this->project_checker->registerFile(
            getcwd() . '/somefile.php',
            '<?php
        class A {
            /** @var int|null */
            protected $hello;

            /** @return void */
            function foo() {
                $this->hello = 5
            }
        }'
        );

        $file_checker = new FileChecker(getcwd() . '/somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods();
    }
}
