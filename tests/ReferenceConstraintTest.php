<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class ReferenceConstraintTest extends PHPUnit_Framework_TestCase
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
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage ReferenceConstraintViolation
     * @return                   void
     */
    public function testFunctionParameterViolation()
    {
        $stmts = self::$parser->parse('<?php
        /** @return void */
        function changeInt(int &$a) {
          $a = "hello";
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testFunctionParameterNoViolation()
    {
        $stmts = self::$parser->parse('<?php
        /** @return void */
        function changeInt(int &$a) {
          $a = 5;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage ReferenceConstraintViolation
     * @return                   void
     */
    public function testClassMethodParameterViolation()
    {
        $stmts = self::$parser->parse('<?php
        class A {
          /** @var int */
          private $foo;

            public function __construct(int &$foo) {
                $this->foo = &$foo;
                $foo = "hello";
            }
        }

        $bar = 5;
        $a = new A($bar); // $bar is constrained to an int
        $bar = null; // ReferenceConstraintViolation issue emitted
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage ReferenceConstraintViolation
     * @return                   void
     */
    public function testClassMethodParameterViolationInPostAssignment()
    {
        $stmts = self::$parser->parse('<?php
        class A {
          /** @var int */
          private $foo;

            public function __construct(int &$foo) {
                $this->foo = &$foo;
            }
        }

        $bar = 5;
        $a = new A($bar);
        $bar = null;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage ConflictingReferenceConstraint
     * @return                   void
     */
    public function testContradictoryReferenceConstraints()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var int */
            private $foo;

            public function __construct(int &$foo) {
                $this->foo = &$foo;
            }
        }

        class B {
            /** @var string */
            private $bar;

            public function __construct(string &$bar) {
                $this->bar = &$bar;
            }
        }

        if (rand(0, 1)) {
            $v = 5;
            $c = (new A($v)); // $v is constrained to an int
        } else {
            $v = "hello";
            $c =  (new B($v)); // $v is constrained to a string
        }

        $v = 8;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
