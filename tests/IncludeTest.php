<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;

class IncludeTest extends PHPUnit_Framework_TestCase
{
    /** @var \PhpParser\Parser */
    protected static $parser;

    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;
    protected static $file_filter;

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }

    public function setUp()
    {
        $config = new TestConfig();
        $config->throw_exception = true;
        FileChecker::clearCache();
        $this->project_checker = new \Psalm\Checker\ProjectChecker();
    }

    public function testBasicRequire()
    {
        $this->project_checker->registerFile(
            getcwd() . '/file1.php',
            '<?php
            class A{
            }
            '
        );

        $file2_checker = new FileChecker(
            'file2.php',
            $this->project_checker,
            self::$parser->parse('<?php
            require("file1.php");

            class B {
                public function foo() : void {
                    (new A);
                }
            }
            ')
        );

        $file2_checker->visitAndAnalyzeMethods();
    }

    public function testNestedRequire()
    {
        $this->project_checker->registerFile(
            getcwd() . '/file1.php',
            '<?php
            class A{
                public function fooFoo() : void {

                }
            }
            '
        );

        $this->project_checker->registerFile(
            getcwd() . '/file2.php',
            '<?php
            require("file1.php");

            class B extends A{
            }
            '
        );

        $file2_checker = new FileChecker(
            'file3.php',
            $this->project_checker,
            self::$parser->parse('<?php
            require("file2.php");

            class C extends B {
                public function doFoo() : void {
                    $this->fooFoo();
                }
            }
            ')
        );

        $file2_checker->visitAndAnalyzeMethods();
    }

    public function testRequireNamespace()
    {
        $this->project_checker->registerFile(
            getcwd() . '/file1.php',
            '<?php
            namespace Foo;

            class A{
            }
            '
        );

        $file2_checker = new FileChecker(
            'file2.php',
            $this->project_checker,
            self::$parser->parse('<?php
            require("file1.php");

            class B {
                public function foo() : void {
                    (new Foo\A);
                }
            }
            ')
        );

        $file2_checker->visitAndAnalyzeMethods();
    }
}
