<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class Php71Test extends PHPUnit_Framework_TestCase
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

    public function testNullableReturnType()
    {
        $stmts = self::$parser->parse('<?php
        function a(): ?string
        {
            return rand(0, 10) ? "elePHPant" : null;
        }

        $a = a();
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('string|null', (string) $context->vars_in_scope['$a']);
    }

    public function testNullableArgument()
    {
        $stmts = self::$parser->parse('<?php
        function test(?string $name) : ?string
        {
            return $name;
        }

        test("elePHPant");
        test(null);
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }
}
