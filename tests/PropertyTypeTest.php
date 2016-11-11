<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class PropertyTypeTest extends PHPUnit_Framework_TestCase
{
    protected static $parser;
    protected static $file_filter;

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $config = Config::getInstance();
        $config->throw_exception = true;
    }

    public function setUp()
    {
        FileChecker::clearCache();
    }

    public function testNewVarInIf()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /**
             * @var mixed
             */
            public $foo;

            /** @return void */
            public function bar()
            {
                if (rand(0,10) === 5) {
                    $this->foo = [];
                }

                if (!is_array($this->foo)) {
                    // do something
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testSharedPropertyInIf()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var int */
            public $foo;
        }
        class B {
            /** @var string */
            public $foo;
        }

        $a = null;
        $b = null;

        if ($a instanceof A || $a instanceof B) {
            $b = $a->foo;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('null|string|int', (string) $context->vars_in_scope['$b']);
    }

    public function testSharedPropertyInElseIf()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var int */
            public $foo;
        }
        class B {
            /** @var string */
            public $foo;
        }

        $a = null;
        $b = null;

        if (rand(0, 10) === 4) {
            // do nothing
        }
        elseif ($a instanceof A || $a instanceof B) {
            $b = $a->foo;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('null|string|int', (string) $context->vars_in_scope['$b']);
    }
}
