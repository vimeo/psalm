<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Context;
use Psalm\Type;

class AssignmentTest extends PHPUnit_Framework_TestCase
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
        \Psalm\Checker\FileChecker::clearCache();
        $this->project_checker = new \Psalm\Checker\ProjectChecker();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage MixedAssignment
     */
    public function testMixedAssignment()
    {
        $context = new Context('somefile.php');
        $stmts = self::$parser->parse('<?php
        /** @var mixed */
        $a = 5;
        $b = $a;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
