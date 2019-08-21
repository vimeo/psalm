<?php
namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Tests\Internal\Provider;

class UnusedCodeTest extends TestCase
{
    /** @var \Psalm\Internal\Analyzer\ProjectAnalyzer */
    protected $project_analyzer;

    /**
     * @return void
     */
    public function setUp() : void
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
        $this->project_analyzer->setPhpVersion('7.3');
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
        if (\strpos($test_name, 'SKIPPED-') !== false) {
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

        $this->analyzeFile($file_path, $context, false);

        $this->project_analyzer->checkClassReferences();

        \Psalm\IssueBuffer::processUnusedSuppressions($this->project_analyzer->getCodebase()->file_provider);
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
        if (\strpos($this->getTestName(), 'SKIPPED-') !== false) {
            $this->markTestSkipped();
        }

        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessageRegExp('/\b' . \preg_quote($error_message, '/') . '\b/');

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

        $this->analyzeFile($file_path, $context, false);

        $this->project_analyzer->checkClassReferences();

        \Psalm\IssueBuffer::processUnusedSuppressions($this->project_analyzer->getCodebase()->file_provider);
    }

    /**
     * @return array<string, array{string}>
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
                            call_user_func([$this, "modify" . $name], $value);
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
                        public function foo() : void;
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
                            $this->i = new B();

                            foreach ($as as $a) {
                                $this->a($a, 1);
                            }
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
            'usedFunctionCall' => [
                '<?php
                    $a = strlen("goodbye");
                    echo $a;',
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
                    takesA(new B);',
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

                            foreach ([1, 2, 3] as $_) {
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
            'abstractMethodImplementerCoveredByParentCall' => [
                '<?php
                    abstract class Foobar {
                        public function doIt(): void {
                            $this->inner();
                        }

                        abstract protected function inner(): void;
                    }

                    class MyFooBar extends Foobar {
                        protected function inner(): void {
                            // Do nothing
                        }
                    }

                    $myFooBar = new MyFooBar();
                    $myFooBar->doIt();',
            ],
            'methodUsedAsCallable' => [
                '<?php
                    class C {
                        public static function foo() : void {}
                    }

                    function takesCallable(callable $c) : void {
                        $c();
                    }

                    takesCallable([C::class, "foo"]);',
            ],
            'propertyAndMethodOverriddenDownstream' => [
                '<?php
                    class A {
                        /** @var string */
                        public $foo = "hello";

                        public function bar() : void {}
                    }

                    class B extends A {
                        /** @var string */
                        public $foo = "goodbye";

                        public function bar() : void {}
                    }

                    function foo(A $a) : void {
                        echo $a->foo;
                        $a->bar();
                    }

                    foo(new B());',
            ],
            'protectedPropertyOverriddenDownstream' => [
                '<?php

                class C {
                    /** @var int */
                    protected $foo = 1;
                    public function bar() : int {
                        return $this->foo;
                    }
                }

                class D extends C {
                    protected $foo = 2;
                }

                (new D)->bar();',
            ],
            'usedClassAfterExtensionLoaded' => [
                '<?php
                    class A {
                        public function __construct() {}
                    }

                    if (extension_loaded("fdsfdsfd")) {
                        new A();
                    }',
            ],
            'usedParamInIf' => [
                '<?php
                    class O {}
                    class C {
                        private bool $a = false;
                        public array $_types = [];

                        private static function mirror(array $a) : array {
                            return $a;
                        }

                        /**
                         * @param class-string<O>|null $type
                         * @return self
                         */
                        public function addType(?string $type, array $ids = array())
                        {
                            if ($this->a) {
                                $ids = self::mirror($ids);
                            }
                            $this->_types[$type ?: ""] = new ArrayObject($ids);
                            return $this;
                        }
                    }

                    (new C)->addType(null);',
            ],
            'usedMethodAfterClassExists' => [
                '<?php
                    class A {
                        public static function bar() : void {}
                    }

                    if (class_exists(A::class)) {
                        A::bar();
                    }',
            ],
            'usedParamInLoopBeforeBreak' => [
                '<?php
                    class Foo {}

                    function takesFoo(Foo $foo1, Foo $foo2): Foo {
                        while (rand(0, 1)) {
                            echo get_class($foo1);

                            if (rand(0, 1)) {
                                $foo1 = $foo2;

                                break;
                            }
                        }

                        return $foo1;
                    }',
            ],
            'usedParamInLoopBeforeContinue' => [
                '<?php
                    class Foo {}

                    function takesFoo(Foo $foo1, Foo $foo2): Foo {
                        while (rand(0, 1)) {
                            echo get_class($foo1);

                            if (rand(0, 1)) {
                                $foo1 = $foo2;

                                continue;
                            }
                        }

                        return $foo1;
                    }',
            ],
            'usedParamInLoopBeforeWithChangeContinue' => [
                '<?php
                    class Foo {}

                    class Bar {
                        public static function build(Foo $foo) : ?self {
                            echo get_class($foo);
                            return new self();
                        }

                        public function produceFoo(): Foo {
                            return new Foo();
                        }
                    }

                    function takesFoo(Foo $foo): Foo {
                        while (rand(0, 1)) {
                            $bar = Bar::build($foo);

                            if ($bar) {
                                $foo = $bar->produceFoo();

                                continue;
                            }
                        }

                        return $foo;
                    }',
            ],
            'suppressUnusedMethod' => [
                '<?php
                    class A {
                        /**
                         * @psalm-suppress UnusedMethod
                         */
                        public function foo() : void {}
                    }

                    new A();'
            ],
        ];
    }

    /**
     * @return array<string,array{string,error_message:string}>
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
                'error_message' => 'PossiblyUnusedParam - src' . \DIRECTORY_SEPARATOR
                    . 'somefile.php:4:49 - Param #1 is never referenced in this method',
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
            'unusedFunctionCall' => [
                '<?php
                    strlen("goodbye");',
                'error_message' => 'UnusedFunctionCall',
            ],
            'propertyOverriddenDownstreamAndNotUsed' => [
                '<?php
                    class A {
                        /** @var string */
                        public $foo = "hello";
                    }

                    class B extends A {
                        /** @var string */
                        public $foo = "goodbye";
                    }

                    new B();',
                'error_message' => 'PossiblyUnusedProperty',
            ],
            'propertyUsedOnlyInConstructor' => [
                '<?php
                    class A {
                        /** @var int */
                        private $used;

                        /** @var int */
                        private $unused;

                        /** @var int */
                        private static $staticUnused;

                        public function __construct() {
                            $this->used = 4;
                            $this->unused = 4;
                            self::$staticUnused = 4;
                        }

                        public function handle(): void
                        {
                            $this->used++;
                        }
                    }
                    (new A())->handle();',
                'error_message' => 'UnusedProperty',
            ],
        ];
    }
}
