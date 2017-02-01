<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class Php55Test extends PHPUnit_Framework_TestCase
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
    public function testGenerator()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @param  int  $start
         * @param  int  $limit
         * @param  int  $step
         * @return Generator<int>
         */
        function xrange($start, $limit, $step = 1) {
            for ($i = $start; $i <= $limit; $i += $step) {
                yield $i;
            }
        }

        $a = null;

        /*
         * Note that an array is never created or returned,
         * which saves memory.
         */
        foreach (xrange(1, 9, 2) as $number) {
            $a = $number;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('null|int', (string) $context->vars_in_scope['$a']);
    }

    /**
     * @return void
     */
    public function testFinally()
    {
        $stmts = self::$parser->parse('<?php
        try {
        }
        catch (\Exception $e) {
        }
        finally {
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testForeachList()
    {
        $stmts = self::$parser->parse('<?php
        $array = [
            [1, 2],
            [3, 4],
        ];

        foreach ($array as list($a, $b)) {
            echo "A: $a; B: $b\n";
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testArrayStringDereferencing()
    {
        $stmts = self::$parser->parse('<?php
        $a = [1, 2, 3][0];
        $b = "PHP"[0];
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('int', (string) $context->vars_in_scope['$a']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$b']);
    }

    /**
     * @return void
     */
    public function testClassString()
    {
        $stmts = self::$parser->parse('<?php
        class ClassName {}

        $a = ClassName::class;
        ?>
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);

        $this->assertEquals('string', (string) $context->vars_in_scope['$a']);
    }
}
