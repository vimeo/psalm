<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class ClosureTest extends PHPUnit_Framework_TestCase
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

    public function testByRefUseVar()
    {
        $stmts = self::$parser->parse('<?php
        /** @return void */
        function run_function(\Closure $fnc) {
            $fnc();
        }

        // here we have to make sure $data exists as a side-effect of calling `run_function`
        // because it could exist depending on how run_function is implemented
        /**
         * @return void
         * @psalm-suppress MixedArgument
         */
        function fn() {
            run_function(
                /**
                 * @return void
                 */
                function() use(&$data) {
                    $data = 1;
                }
            );
            echo $data;
        }

        fn();
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidScalarArgument
     */
    public function testWrongArg()
    {
        $stmts = self::$parser->parse('<?php
        $bar = ["foo", "bar"];

        $bam = array_map(
            function(int $a) : int {
                return $a + 1;
            },
            $bar
        );
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidReturnType
     */
    public function testNoReturn()
    {
        $stmts = self::$parser->parse('<?php
        $bar = ["foo", "bar"];

        $bam = array_map(
            function(string $a) : string {
            },
            $bar
        );
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testInferredArg()
    {
        $stmts = self::$parser->parse('<?php
        $bar = ["foo", "bar"];

        $bam = array_map(
            /**
             * @psalm-suppress MissingReturnType
             */
            function(string $a) {
                return $a . "blah";
            },
            $bar
        );
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }
}
