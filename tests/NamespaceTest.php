<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class NamespaceTest extends PHPUnit_Framework_TestCase
{
    /** @var \PhpParser\Parser */
    protected static $parser;

    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $config = new TestConfig();
    }

    public function setUp()
    {
        FileChecker::clearCache();
        $this->project_checker = new \Psalm\Checker\ProjectChecker();
    }

    public function testEmptyNamespace()
    {
        $stmts = self::$parser->parse('<?php
        namespace A {
            function foo() : void {

            }

            class Bar {

            }
        }
        namespace {
            A\foo();
            \A\foo();

            (new A\Bar);
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
