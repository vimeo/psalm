<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class UnusedCodeTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /** @var string */
    protected static $project_dir;

    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    /**
     * @return void
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
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
            dirname(__DIR__),
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

        $this->project_checker->collect_references = true;
    }

    /**
     * @dataProvider providerTestUnusedCodeWithClassReferences
     * @param string $code
     * @param string $error_message
     * @return void
     */
    public function testUnusedCodeWithClassReferences($code, $error_message)
    {
        $this->expectException('\Psalm\Exception\CodeException');
        $this->expectExceptionMessage($error_message);

        $stmts = self::$parser->parse($code);

        $file_checker = new FileChecker(self::$project_dir . 'somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->project_checker->checkClassReferences();
    }

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'unset' => [
                '<?php
                    /** @return void */
                    function foo() {
                        $a = 0;

                        $arr = ["hello"];

                        unset($arr[$a]);
                    }',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'function' => [
                '<?php
                    /** @return int */
                    function foo() {
                        $a = 5;
                        $b = [];
                        return $a;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'ifInFunction' => [
                '<?php
                    /** @return int */
                    function foo() {
                        $a = 5;
                        if (rand(0, 1)) {
                            $b = "hello";
                        } else {
                            $b = "goodbye";
                        }
                        return $a;
                    }',
                'error_message' => 'UnusedVariable',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerTestUnusedCodeWithClassReferences()
    {
        return [
            'unusedClass' => [
                '<?php
                    class A { }',
                'error_message' => 'UnusedClass',
            ],
            'publicUnusedMethod' => [
                '<?php
                    class A {
                        /** @return void */
                        public function foo() {}
                    }

                    new A();',
                'error_message' => 'PossiblyUnusedMethod',
            ],
            'privateUnusedMethod' => [
                '<?php
                    class A {
                        /** @return void */
                        private function foo() {}
                    }

                    new A();',
                'error_message' => 'UnusedMethod',
            ],
        ];
    }
}
