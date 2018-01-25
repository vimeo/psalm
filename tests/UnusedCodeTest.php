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
            new TestConfig(),
            $this->file_provider,
            new Provider\FakeParserCacheProvider()
        );

        $this->project_checker->getCodebase()->collect_references = true;
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

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            $code
        );

        $this->analyzeFile($file_path, new Context());
        $this->project_checker->getCodebase()->checkClassReferences();
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
        $this->expectExceptionMessageRegexp('/\b' . preg_quote($error_message, '/') . '\b/');

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            $code
        );

        $this->analyzeFile($file_path, new Context());
        $this->project_checker->getCodebase()->checkClassReferences();
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

                        private function modify(string $name, string $value): void {
                            call_user_func(array($this, "modify_" . $name), $value);
                        }

                        public function modifyFoo(string $value): void {
                            $this->value = $value;
                        }

                        public function getFoo() : string {
                            return $this->value;
                        }
                    }

                    $m = new A();
                    $m->foo("value");
                    $m->modifyFoo("value2");
                    echo $m->getFoo();',
            ],
            'usedTraitMethod' => [
                '<?php
                    class A {
                        public function foo(): void {
                            echo "parent method";
                        }
                    }

                    trait T {
                        public function foo(): void {
                            echo "trait method";
                        }
                    }

                    class B extends A {
                        use T;
                    }

                    (new A)->foo();
                    (new B)->foo();',
            ],
            'usedInterfaceMethod' => [
                '<?php
                    interface I {
                        public function foo(): void;
                    }

                    class A implements I {
                        public function foo(): void {}
                    }

                    (new A)->foo();',
            ],
            'dummyByRefVar' => [
                '<?php
                    function foo(string &$a = null, string $b = null): void {
                        if ($a) {
                            echo $a;
                        }
                        if ($b) {
                            echo $b;
                        }
                    }

                    function bar(): void {
                        foo($dummy_byref_var, "hello");
                    }

                    bar();',
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
            'unusuedVariableInBranchOfIf' => [
                '<?php
                    function foo(): void {
                        if (rand(0, 1)) {
                            $a = "foo";
                            echo $a;
                        }

                        if (rand(0, 1)) {
                            $a = "foo";
                        }
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
            'possiblyUnusedParam' => [
                '<?php
                    class A {
                        /** @return void */
                        public function foo(int $i) {}
                    }

                    (new A)->foo(4);',
                'error_message' => 'PossiblyUnusedParam',
            ],
            'unusedParam' => [
                '<?php
                    function foo(int $i) {}

                    foo(4);',
                'error_message' => 'UnusedParam',
            ],
            'possiblyUnusedProperty' => [
                '<?php
                    class A {
                        /** @var string */
                        public $foo = "hello";
                    }

                    $a = new A();',
                'error_message' => 'PossiblyUnusedProperty',
            ],
            'unusedProperty' => [
                '<?php
                    class A {
                        /** @var string */
                        private $foo = "hello";
                    }

                    $a = new A();',
                'error_message' => 'UnusedProperty',
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
            'unevaluatedCode' => [
                '<?php
                    function foo(): void {
                        return;
                        $a = "foo";
                    }',
                'error_message' => 'UnevaluatedCode',
            ],
        ];
    }
}
