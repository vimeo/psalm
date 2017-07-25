<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Config;
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
            new Provider\FakeCacheProvider()
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
        $this->expectExceptionMessage($error_message);

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
                'check_unused_references' => true,
            ],
            'usedVariables' => [
                '<?php
                    /** @return string */
                    function foo() {
                        $a = 5;
                        $b = [];
                        return $a . implode(",", $b);
                    }',
                'check_unused_references' => true,
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
                'check_unused_references' => true,
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
                'check_unused_references' => true,
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
                'check_unused_references' => true,
            ],
            'unusedClass' => [
                '<?php
                    class A { }',
                'error_message' => 'UnusedClass',
                'check_unused_references' => true,
            ],
            'publicUnusedMethod' => [
                '<?php
                    class A {
                        /** @return void */
                        public function foo() {}
                    }

                    new A();',
                'error_message' => 'PossiblyUnusedMethod',
                'check_unused_references' => true,
            ],
            'privateUnusedMethod' => [
                '<?php
                    class A {
                        /** @return void */
                        private function foo() {}
                    }

                    new A();',
                'error_message' => 'UnusedMethod',
                'check_unused_references' => true,
            ],
        ];
    }
}
