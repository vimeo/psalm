<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Context;

class UnusedCodeTest extends TestCase
{
    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    /**
     * @return void
     */
    public function setUp()
    {
        FileChecker::clearCache();

        $this->file_provider = new Provider\FakeFileProvider();

        $this->project_checker = new \Psalm\Checker\ProjectChecker(
            $this->file_provider,
            new Provider\FakeParserCacheProvider()
        );

        $this->project_checker->setConfig(new TestConfig());

        $this->project_checker->collect_references = true;
    }

    /**
     * @dataProvider providerFileCheckerValidCodeParse
     *
     * @param string $code
     * @param array<string, string> $assertions
     * @param array<string> $error_levels
     *
     * @return void
     */
    public function testValidCode($code)
    {
        $test_name = $this->getName();
        if (strpos($test_name, 'PHP7-') !== false) {
            if (version_compare(PHP_VERSION, '7.0.0dev', '<')) {
                $this->markTestSkipped('Test case requires PHP 7.');

                return;
            }
        } elseif (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $context = new Context();

        $this->addFile(
            self::$src_dir_path . 'somefile.php',
            $code
        );

        $file_checker = new FileChecker(self::$src_dir_path . 'somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods($context);
        $this->project_checker->checkClassReferences();
    }

    /**
     * @dataProvider providerFileCheckerInvalidCodeParse
     *
     * @param string $code
     * @param string $error_message
     *
     * @return void
     */
    public function testInvalidCode($code, $error_message)
    {
        if (strpos($this->getName(), 'SKIPPED-') !== false) {
            $this->markTestSkipped();
        }

        $this->expectException('\Psalm\Exception\CodeException');
        $this->expectExceptionMessageRegexp('/\b' . preg_quote($error_message, '/') . '/');

        $this->addFile(
            self::$src_dir_path . 'somefile.php',
            $code
        );

        $context = new Context();

        $file_checker = new FileChecker(self::$src_dir_path . 'somefile.php', $this->project_checker);
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
            'usedVariables' => [
                '<?php
                    /** @return string */
                    function foo() {
                        $a = 5;
                        $b = [];
                        return $a . implode(",", $b);
                    }',
            ],
            'ifInFunction' => [
                '<?php
                    /** @return string */
                    function foo() {
                        $a = 5;
                        if (rand(0, 1)) {
                            $b = "hello";
                        } else {
                            $b = "goodbye";
                        }
                        return $a . $b;
                    }',
            ],
            'booleanOr' => [
                '<?php
                    function foo(int $a, int $b): bool {
                        return $a || $b;
                    }',
            ],
            'magicCall' => [
                '<?php
                    class A {
                        /** @var string */
                        private $value = "default";

                        /** @param string[] $args */
                        public function __call(string $name, array $args) {
                            if (count($args) == 1) {
                                $this->modify($name, $args[0]);
                            }
                        }

                        private function modify(string $name, string $value) : void {
                            call_user_func(array($this, "modify_" . $name), $value);
                        }

                        public function modifyFoo(string $value) : void {
                            $this->value = $value;
                        }
                    }

                    $m = new A();
                    $m->foo("value");
                    $m->modifyFoo("value2");',
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
