<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class UnusedCodeTest extends PHPUnit_Framework_TestCase
{
    /** @var \PhpParser\Parser */
    protected static $parser;

    /** @var string */
    protected static $project_dir;

    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    /**
     * @return void
     */
    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        self::$project_dir = getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    }

    /**
     * @return void
     */
    public function setUp()
    {
        FileChecker::clearCache();
        $this->project_checker = new \Psalm\Checker\ProjectChecker();
        $this->project_checker->setConfig(Config::loadFromXML(
            'psalm.xml',
            '<?xml version="1.0"?>
            <psalm
                throwExceptionOnError="true"
                useDocblockTypes="true"
                totallyTyped="true"
            >
                <projectFiles>
                    <directory name="src" />
                </projectFiles>
            </psalm>'
        ));
        $this->project_checker->count_references = true;
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage UnusedVariable
     * @return                   void
     */
    public function testFunction()
    {
        $stmts = self::$parser->parse('<?php
        /** @return int */
        function foo() {
            $a = 5;
            $b = [];
            return $a;
        }
        ');

        $file_checker = new FileChecker(self::$project_dir . 'somefile.php', $this->project_checker, $stmts);

        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage UnusedVariable
     * @return                   void
     */
    public function testIfInFunction()
    {
        $stmts = self::$parser->parse('<?php
        /** @return int */
        function foo() {
            $a = 5;
            if (rand(0, 1)) {
                $b = "hello";
            } else {
                $b = "goodbye";
            }
            return $a;
        }
        ');

        $file_checker = new FileChecker(self::$project_dir . 'somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testUnset()
    {
        $stmts = self::$parser->parse('<?php
        /** @return void */
        function foo() {
            $a = 0;

            $arr = ["hello"];

            unset($arr[$a]);
        }
        ');

        $file_checker = new FileChecker(self::$project_dir . 'somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage UnusedClass
     * @return                   void
     */
    public function testUnusedClass()
    {
        $stmts = self::$parser->parse('<?php
        class A { }
        ');

        $file_checker = new FileChecker(self::$project_dir . 'somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->project_checker->checkClassReferences();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage PossiblyUnusedMethod
     * @return                   void
     */
    public function testPublicUnusedMethod()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return void */
            public function foo() {}
        }

        new A();
        ');

        $file_checker = new FileChecker(self::$project_dir . 'somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->project_checker->checkClassReferences();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage UnusedMethod
     * @return                   void
     */
    public function testPrivateUnusedMethod()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return void */
            private function foo() {}
        }

        new A();
        ');

        $file_checker = new FileChecker(self::$project_dir . 'somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->project_checker->checkClassReferences();
    }
}
