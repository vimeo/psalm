<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class ClosureTest extends PHPUnit_Framework_TestCase
{
    protected static $parser;

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $config = Config::getInstance();
        $config->throw_exception = true;
        $config->use_docblock_types = true;
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
        /** @return void */
        function fn() {
            run_function(
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
            function(int $a) {
                return $a + 1;
            },
            $bar
        );
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }
}
