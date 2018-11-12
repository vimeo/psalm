<?php
namespace Psalm\Tests;

use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Config;
use Psalm\Context;
use Psalm\Tests\Internal\Provider;

class UnusedCodeTest extends TestCase
{
    /** @var \Psalm\Internal\Analyzer\ProjectAnalyzer */
    protected $project_analyzer;

    /**
     * @return void
     */
    public function setUp()
    {
        FileAnalyzer::clearCache();

        $this->file_provider = new Provider\FakeFileProvider();

        $this->project_analyzer = new \Psalm\Internal\Analyzer\ProjectAnalyzer(
            new TestConfig(),
            new \Psalm\Internal\Provider\Providers(
                $this->file_provider,
                new Provider\FakeParserCacheProvider()
            )
        );

        $this->project_analyzer->getCodebase()->reportUnusedCode();
    }

    /**
     * @dataProvider providerValidCodeParse
     *
     * @param string $code
     * @param array<string> $error_levels
     *
     * @return void
     */
    public function testValidCode($code, array $error_levels = [])
    {
        $test_name = $this->getTestName();
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

        foreach ($error_levels as $error_level) {
            $this->project_analyzer->getCodebase()->config->setCustomErrorLevel($error_level, Config::REPORT_SUPPRESS);
        }

        $context = new Context();
        $context->collect_references = true;

        $this->analyzeFile($file_path, $context);

        $this->project_analyzer->checkClassReferences();
    }

    /**
     * @dataProvider providerInvalidCodeParse
     *
     * @param string $code
     * @param string $error_message
     * @param array<string> $error_levels
     *
     * @return void
     */
    public function testInvalidCode($code, $error_message, $error_levels = [])
    {
        if (strpos($this->getTestName(), 'SKIPPED-') !== false) {
            $this->markTestSkipped();
        }

        $this->expectException('\Psalm\Exception\CodeException');
        $this->expectExceptionMessageRegexp('/\b' . preg_quote($error_message, '/') . '\b/');

        $file_path = self::$src_dir_path . 'somefile.php';

        foreach ($error_levels as $error_level) {
            $this->project_analyzer->getCodebase()->config->setCustomErrorLevel($error_level, Config::REPORT_SUPPRESS);
        }

        $this->addFile(
            $file_path,
            $code
        );

        $context = new Context();
        $context->collect_references = true;

        $this->analyzeFile($file_path, $context);

        $this->project_analyzer->checkClassReferences();
    }

    /**
     * @return array
     */
    public function providerValidCodeParse()
    {
        return [
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
            'usedTraitMethodWithExplicitCall' => [
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
            'constructorIsUsed' => [
                '<?php
                    class A {
                        public function __construct() {
                            $this->foo();
                        }
                        private function foo() : void {}
                    }
                    $a = new A();
                    echo (bool) $a;',
            ],
            'everythingUsed' => [
                '<?php
                    interface I {
                        public function foo();
                    }
                    class B implements I {
                        public function foo() : void {}
                    }

                    class A
                    {
                        /**
                         * @var I
                         */
                        private $i;

                        /**
                         * @param int[] $as
                         */
                        public function __construct(array $as) {

                            foreach ($as as $a) {
                                $this->a($a, 1);
                            }

                            $this->i = new B();
                        }

                        private function a(int $a, int $b): self
                        {
                            $this->v($a, $b);

                            $this->i->foo();

                            return $this;
                        }

                        private function v(int $a, int $b): void
                        {
                            if ($a + $b > 0) {
                                throw new \RuntimeException("");
                            }
                        }
                    }

                    new A([1, 2, 3]);',
            ],
            'unusedParamWithUnderscore' => [
                '<?php
                    function foo(int $_) : void {}

                    foo(4);',
            ],
            'unusedParamWithUnusedPrefix' => [
                '<?php
                    function foo(int $unusedArg) : void {}

                    foo(4);',
            ],
            'possiblyUnusedParamWithUnderscore' => [
                '<?php
                    class A {
                        public static function foo(int $_ = null) : void {}
                    }

                    A::foo();',
            ],
            'possiblyUnusedParamWithUnusedPrefix' => [
                '<?php
                    class A {
                        public static function foo(int $unusedArg = null) : void {}
                    }

                    A::foo();',
            ],
            'usedClass' => [
                '<?php
                    class A { }
                    new A();',
            ],
            'usedTraitMethodWithImplicitCall' => [
                '<?php
                    class A {
                        public function foo() : void {}
                    }
                    trait T {
                        public function foo() : void {}
                    }
                    class B extends A {
                        use T;
                    }
                    function takesA(A $a) : void {
                        $a->foo();
                    }
                    takesA(new B);'
            ],
            'usedMethodInTryCatch' => [
                '<?php
                    class A {
                        protected function getC() : C {
                            return new C;
                        }
                    }
                    class C {
                        public function foo() : void {}
                    }

                    class B extends A {
                        public function bar() : void {
                            $c = $this->getC();

                            foreach ([1, 2, 3] as $i) {
                                try {
                                    $c->foo();
                                } catch (Exception $e) {}
                            }
                        }
                    }

                    (new B)->bar();',
            ],
            'suppressPrivateUnusedMethod' => [
                '<?php
                    class A {
                        /**
                         * @psalm-suppress UnusedMethod
                         * @return void
                         */
                        private function foo() {}
                    }

                    new A();',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerInvalidCodeParse()
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
                'error_levels' => ['UnusedVariable'],
            ],
            'unusedProperty' => [
                '<?php
                    class A {
                        /** @var string */
                        private $foo = "hello";
                    }

                    $a = new A();',
                'error_message' => 'UnusedProperty',
                'error_levels' => ['UnusedVariable'],
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
            'unusedTraitMethodInParent' => [
                '<?php
                    class A {
                        public function foo() : void {}
                    }
                    trait T {
                        public function foo() : void {}

                        public function bar() : void {}
                    }
                    class B extends A {
                        use T;
                    }
                    function takesA(A $a) : void {
                        $a->foo();
                    }
                    takesA(new B);',
                'error_message' => 'PossiblyUnusedMethod',
            ],
            'unusedRecursivelyUsedMethod' => [
                '<?php
                    class C {
                        public function foo() : void {
                            if (rand(0, 1)) {
                                $this->foo();
                            }
                        }

                        public function bar() : void {}
                    }

                    (new C)->bar();',
                'error_message' => 'PossiblyUnusedMethod',
            ],
            'unusedRecursivelyUsedStaticMethod' => [
                '<?php
                    class C {
                        public static function foo() : void {
                            if (rand(0, 1)) {
                                self::foo();
                            }
                        }

                        public function bar() : void {}
                    }

                    (new C)->bar();',
                'error_message' => 'PossiblyUnusedMethod',
            ],
        ];
    }
}
