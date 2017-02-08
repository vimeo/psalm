<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class VariadicTest extends PHPUnit_Framework_TestCase
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
    public function testVariadic()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @return array<mixed>
         */
        function f($req, $opt = null, ...$params) {
            return $params;
        }

        f(1);
        f(1, 2);
        f(1, 2, 3);
        f(1, 2, 3, 4);
        f(1, 2, 3, 4, 5);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testVariadicArray()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @param array<int, int> $a_list
         * @return array<int, int>
         */
        function f(int ...$a_list) {
            return array_map(
                /**
                 * @return int
                 */
                function (int $a) {
                    return $a + 1;
                },
                $a_list
            );
        }

        f(1);
        f(1, 2);
        f(1, 2, 3);

        /**
         * @param string ...$a_list
         * @return void
         */
        function g(string ...$a_list) {
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidScalarArgument
     * @return                   void
     */
    public function testVariadicArrayBadParam()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @param array<int, int> $a_list
         * @return void
         */
        function f(int ...$a_list) {
        }
        f(1, 2, "3");
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
