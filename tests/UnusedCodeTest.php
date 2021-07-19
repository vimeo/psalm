<?php
namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\Internal\Provider;

class UnusedCodeTest extends TestCase
{
    /** @var \Psalm\Internal\Analyzer\ProjectAnalyzer */
    protected $project_analyzer;

    public function setUp() : void
    {
        RuntimeCaches::clearAll();

        $this->file_provider = new FakeFileProvider();

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
     */
    public function testValidCode($code, array $error_levels = []): void
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

        $this->project_analyzer->setPhpVersion('8.0');

        foreach ($error_levels as $error_level) {
            $this->project_analyzer->getCodebase()->config->setCustomErrorLevel($error_level, Config::REPORT_SUPPRESS);
        }

        $this->analyzeFile($file_path, new Context(), false);

        $this->project_analyzer->consolidateAnalyzedData();

        \Psalm\IssueBuffer::processUnusedSuppressions($this->project_analyzer->getCodebase()->file_provider);
    }

    /**
     * @dataProvider providerInvalidCodeParse
     *
     * @param string $code
     * @param string $error_message
     * @param array<string> $error_levels
     *
     */
    public function testInvalidCode($code, $error_message, $error_levels = []): void
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

        $this->analyzeFile($file_path, new Context(), false);

        $this->project_analyzer->consolidateAnalyzedData();

        \Psalm\IssueBuffer::processUnusedSuppressions($this->project_analyzer->getCodebase()->file_provider);
    }

    /**
     * @return array<string, array{string}>
     */
    public function providerValidCodeParse(): array
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

                        private function a(int $a, int $b): void
                        {
                            $this->v($a, $b);

                            $this->i->foo();
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
                    protected int $foo = 1;
                    public function bar() : void {
                        $this->foo = 5;
                    }

                    public function getFoo(): void {
                        echo $this->foo;
                    }
                }

                class D extends C {
                    protected int $foo = 2;
                }

                (new D)->bar();
                (new D)->getFoo();',
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
                         */
                        public function addType(?string $type, array $ids = array()): void
                        {
                            if ($this->a) {
                                $ids = self::mirror($ids);
                            }
                            $this->_types[$type ?: ""] = new ArrayObject($ids);
                            return;
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
            'usedFunctionInCall' => [
                '<?php
                    function fooBar(): void {}

                    $foo = "foo";
                    $bar = "bar";

                    ($foo . ucfirst($bar))();',
            ],
            'usedParamInUnknownMethodConcat' => [
                '<?php
                    /**
                     * @psalm-suppress MixedMethodCall
                     */
                    function foo(string $s, object $o) : void {
                        $o->foo("COUNT{$s}");
                    }'
            ],
            'usedFunctioninMethodCallName' => [
                '<?php
                    class Foo {
                        /**
                         * @psalm-suppress MixedArgument
                         */
                        public function bar(string $request): void {
                            /** @var mixed $action */
                            $action = "";
                            $this->{"execute" . ucfirst($action)}($request);
                        }
                    }

                    (new Foo)->bar("request");'
            ],
            'usedMethodCallForExternalMutationFreeClass' => [
                '<?php
                    /**
                     * @psalm-external-mutation-free
                     */
                    class A {
                        private string $foo;

                        public function __construct(string $foo) {
                            $this->foo = $foo;
                        }

                        public function setFoo(string $foo) : void {
                            $this->foo = $foo;
                        }

                        public function getFoo() : string {
                            return $this->foo;
                        }
                    }

                    $a = new A("hello");
                    $a->setFoo($a->getFoo() . "cool");',
            ],
            'functionUsedAsArrayKeyInc' => [
                '<?php
                    /** @param array<int, int> $arr */
                    function inc(array $arr) : array {
                        $arr[strlen("hello")]++;
                        return $arr;
                    }'
            ],
            'pureFunctionUsesMethodBeforeReturning' => [
                '<?php
                    /** @psalm-external-mutation-free */
                    class Counter {
                        private int $count = 0;

                        public function __construct(int $count) {
                            $this->count = $count;
                        }

                        public function increment() : void {
                            $this->count++;
                        }
                    }

                    /** @psalm-pure */
                    function makesACounter(int $i) : Counter {
                        $c = new Counter($i);
                        $c->increment();
                        return $c;
                    }',
            ],
            'usedUsort' => [
                '<?php
                    /** @param string[] $arr */
                    function foo(array $arr) : array {
                        usort($arr, "strnatcasecmp");
                        return $arr;
                    }'
            ],
            'allowArrayMapWithClosure' => [
                '<?php
                    $a = [1, 2, 3];

                    array_map(function($i) { echo $i;}, $a);'
            ],
            'usedAssertFunction' => [
                '<?php
                    /**
                     * @param mixed $v
                     * @psalm-pure
                     * @psalm-assert int $v
                     */
                    function assertInt($v):void {
                        if (!is_int($v)) {
                            throw new \RuntimeException();
                        }
                    }

                    /**
                     * @psalm-pure
                     * @param mixed $i
                     */
                    function takesMixed($i) : int {
                        assertInt($i);
                        return $i;
                    }'
            ],
            'usedFunctionCallInsideSwitchWithTernary' => [
                '<?php
                    function getArg(string $method) : void {
                        switch (strtolower($method ?: "")) {
                            case "post":
                                break;

                            case "get":
                                break;

                            default:
                                break;
                        }
                    }'
            ],
            'ignoreSerializerSerialize' => [
                '<?php
                    class Foo implements Serializable {
                        public function serialize() : string {
                            return "";
                        }

                        public function unserialize($_serialized) : void {}
                    }

                    new Foo();'
            ],
            'useIteratorMethodsWhenCallingForeach' => [
                '<?php
                    /** @psalm-suppress UnimplementedInterfaceMethod */
                    class IterableResult implements \Iterator {
                        public function current() {
                            return null;
                        }

                        public function key() {
                            return 5;
                        }
                    }

                    $items = new IterableResult();

                    foreach ($items as $_item) {}'
            ],
            'usedThroughNewClassStringOfBase' => [
                '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    abstract class FooBase {
                        public final function __construct() {}

                        public function baz() : void {
                            echo "hello";
                        }
                    }

                    /**
                     * @psalm-template T as FooBase
                     * @psalm-param class-string<T> $type
                     * @psalm-return T
                     */
                    function createFoo($type): FooBase {
                        return new $type();
                    }

                    class Foo extends FooBase {}

                    createFoo(Foo::class)->baz();'
            ],
            'usedMethodReferencedByString' => [
                '<?php
                    class A {
                        static function b(): void {}
                    }
                    $methodRef = "A::b";
                    $methodRef();',
            ],
            'usedMethodReferencedByStringWithLeadingBackslash' => [
                '<?php
                    class A {
                        static function b(): void {}
                    }
                    $methodRef = "\A::b";
                    $methodRef();',
            ],
            'arrayPushFunctionCall' => [
                '<?php
                    $a = [];

                    array_push($a, strlen("hello"));

                    echo $a[0];'
            ],
            'callMethodThatUpdatesStaticVar' => [
                '<?php
                    class References {
                        /**
                         * @var array<string, string>
                         */
                        public static $foo = [];

                        /**
                         * @param array<string, string> $map
                         */
                        public function bar(array $map) : void {
                            self::$foo += $map;
                        }
                    }

                    (new References)->bar(["a" => "b"]);'
            ],
            'promotedPropertyIsUsed' => [
                '<?php
                    class Test {
                        public function __construct(public int $id, public string $name) {}
                    }

                    $test = new Test(1, "ame");
                    echo $test->id;
                    echo $test->name;'
            ],
            'unusedNoReturnFunctionCall' => [
                '<?php
                    /**
                     * @return no-return
                     *
                     * @pure
                     *
                     * @throws RuntimeException
                     */
                    function invariant_violation(string $message): void
                    {
                        throw new RuntimeException($message);
                    }

                    /**
                     * @pure
                     */
                    function reverse(string $string): string
                    {
                        if ("" === $string) {
                            invariant_violation("i do not like empty strings.");
                        }

                        return strrev($string);
                    }'
            ],
            'unusedByReferenceFunctionCall' => [
                '<?php
                    function bar(string &$str): string
                    {
                        $str .= "foo";

                        return $str;
                    }

                    function baz(): string
                    {
                        $f = "foo";
                        bar($f);

                        return $f;
                    }'
            ],
            'unusedVoidByReferenceFunctionCall' => [
                '<?php
                    function bar(string &$str): void
                    {
                        $str .= "foo";
                    }

                    function baz(): string
                    {
                        $f = "foo";
                        bar($f);

                        return $f;
                    }'
            ],
            'unusedNamedByReferenceFunctionCall' => [
                '<?php
                    function bar(string $c = "", string &$str = ""): string
                    {
                        $c .= $str;
                        $str .= $c;

                        return $c;
                    }

                    function baz(): string
                    {
                        $f = "foo";
                        bar(str: $f);

                        return $f;
                    }'
            ],
            'unusedNamedByReferenceFunctionCallV2' => [
                '<?php
                    function bar(string &$st, string &$str = ""): string
                    {
                        $st .= $str;

                        return $st;
                    }

                    function baz(): string
                    {
                        $f = "foo";
                        bar(st: $f);

                        return $f;
                    }',
            ],
            'unusedNamedByReferenceFunctionCallV3' => [
                '<?php
                    function bar(string &$st, ?string &$str = ""): string
                    {
                        $st .= (string) $str;

                        return $st;
                    }

                    function baz(): string
                    {
                        $f = "foo";
                        bar(st: $f, str: $c);

                        return $f;
                    }',
            ],
            'functionCallUsedInThrow' => [
                '<?php
                    /**
                     * @psalm-pure
                     */
                    function getException(): \Exception
                    {
                        return new \Exception();
                    }

                    throw getException();'
            ],
            'nullableMethodCallIsUsed' => [
                '<?php
                    final class Test {
                        public function test(): void {
                        }
                    }

                    final class TestFactory {
                        /**
                         * @psalm-pure
                         */
                        public function create(bool $returnNull): ?Test {
                            if ($returnNull) {
                                return null;
                            }

                            return new Test();
                        }
                    }

                    $factory = new TestFactory();
                    $factory->create(false)?->test();

                    $exception = new \Exception();

                    throw ($exception->getPrevious() ?? $exception);'
            ],
            'publicPropertyReadInFile' => [
                '<?php
                    class A {
                        public string $a;

                        public function __construct() {
                            $this->a = "hello";
                        }
                    }

                    $foo = new A();
                    echo $foo->a;',
            ],
            'publicPropertyReadInMethod' => [
                '<?php
                    class A {
                        public string $a = "hello";
                    }

                    class B {
                        public function foo(A $a): void {
                            if ($a->a === "goodbye") {}
                        }
                    }

                    (new B)->foo(new A());',
            ],
            'privatePropertyReadInMethod' => [
                '<?php
                    class A {
                        private string $a;

                        public function __construct() {
                            $this->a = "hello";
                        }

                        public function emitA(): void {
                            echo $this->a;
                        }
                    }

                    (new A())->emitA();',
            ],
            'fluentMethodsAllowed' => [
                '<?php
                    class A {
                        public function foo(): static {
                            return $this;
                        }

                        public function bar(): static {
                            return $this;
                        }
                    }

                    (new A())->foo()->bar();',
            ],
            'unusedInterfaceReturnValueWithImplementingClassSuppressed' => [
                '<?php
                    interface IWorker {
                        /** @psalm-suppress PossiblyUnusedReturnValue */
                        public function work(): bool;
                    }

                    class Worker implements IWorker{
                        public function work(): bool {
                            return true;
                        }
                    }

                    function f(IWorker $worker): void {
                        $worker->work();
                    }

                    f(new Worker());',
            ],
            'interfaceReturnValueWithImplementingAndAbstractClass' => [
                '<?php
                    interface IWorker {
                        public function work(): int;
                    }

                    class AbstractWorker implements IWorker {
                        public function work(): int {
                            return 0;
                        }
                    }

                    class Worker extends AbstractWorker {
                        public function work(): int {
                            return 1;
                        }
                    }

                    class AnotherWorker extends AbstractWorker {}

                    function f(IWorker $worker): void {
                        echo $worker->work();
                    }

                    f(new Worker());
                    f(new AnotherWorker());',
            ],
            'methodReturnValueUsedInThrow' => [
                '<?php
                    class A {
                        public function foo() : Exception {
                            return new Exception;
                        }
                    }
                    throw (new A)->foo();
                '
            ],
            'staticMethodReturnValueUsedInThrow' => [
                '<?php
                    class A {
                        public static function foo() : Exception {
                            return new Exception;
                        }
                    }
                    throw A::foo();
                '
            ],
        ];
    }

    /**
     * @return array<string,array{string,error_message:string}>
     */
    public function providerInvalidCodeParse(): array
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
            'unusedMethodCallSimple' => [
                '<?php
                    final class A {
                        private string $foo;

                        public function __construct(string $foo) {
                            $this->foo = $foo;
                        }

                        public function getFoo() : string {
                            return $this->foo;
                        }
                    }

                    $a = new A("hello");
                    $a->getFoo();',
                'error_message' => 'UnusedMethodCall',
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
            'unusedMethodCallForExternalMutationFreeClass' => [
                '<?php
                    /**
                     * @psalm-external-mutation-free
                     */
                    class A {
                        private string $foo;

                        public function __construct(string $foo) {
                            $this->foo = $foo;
                        }

                        public function setFoo(string $foo) : void {
                            $this->foo = $foo;
                        }
                    }

                    function foo() : void {
                        (new A("hello"))->setFoo("goodbye");
                    }',
                'error_message' => 'UnusedMethodCall',
            ],
            'unusedMethodCallForGeneratingMethod' => [
                '<?php
                    /**
                     * @psalm-external-mutation-free
                     */
                    class A {
                        private string $foo;

                        public function __construct(string $foo) {
                            $this->foo = $foo;
                        }

                        public function getFoo() : string {
                            return "abular" . $this->foo;
                        }
                    }

                    /**
                     * @psalm-pure
                     */
                    function makeA(string $s) : A {
                        return new A($s);
                    }

                    function foo() : void {
                        makeA("hello")->getFoo();
                    }',
                'error_message' => 'UnusedMethodCall',
            ],
            'annotatedMutationFreeUnused' => [
                '<?php
                    class A {
                        private string $s;

                        public function __construct(string $s) {
                            $this->s = $s;
                        }

                        /** @psalm-mutation-free */
                        public function getShort() : string {
                            return substr($this->s, 0, 5);
                        }
                    }

                    $a = new A("hello");
                    $a->getShort();',
                'error_message' => 'UnusedMethodCall',
            ],
            'dateTimeImmutable' => [
                '<?php
                    function foo(DateTimeImmutable $dt) : void {
                        $dt->modify("+1 day");
                    }',
                'error_message' => 'UnusedMethodCall',
            ],
            'unusedClassReferencesItself' => [
                '<?php
                    class A {}

                    class AChild extends A {
                        public function __construct() {
                            self::foo();
                        }
                        public static function foo() : void {}
                    }',
                'error_message' => 'UnusedClass',
            ],
            'returnInBothIfConditions' => [
                '<?php

                    function doAThing(): bool {
                        if (rand(0, 1)) {
                            return true;
                        } else {
                            return false;
                        }
                        return false;
                    }',
                'error_message' => 'UnevaluatedCode',
            ],
            'unevaluatedCodeAfterReturnInFinally' => [
                '<?php
                    function noOp(): void {
                        return;
                    }

                    function doAThing(): bool {
                        try {
                            noOp();
                        } finally {
                            return true;
                        }

                        return false;
                    }',
                'error_message' => 'UnevaluatedCode',
            ],
            'UnusedFunctionCallWithOptionalByReferenceParameter' => [
                '<?php
                    /**
                     * @pure
                     */
                    function bar(string $c, string &$str = ""): string
                    {
                        $c .= $str;

                        return $c;
                    }

                    /**
                     * @pure
                     */
                    function baz(): string
                    {
                        $f = "foo";
                        bar($f);

                        return $f;
                    }',
                'error_message' => 'UnusedFunctionCall',
            ],
            'UnusedFunctionCallWithOptionalByReferenceParameterV2' => [
                '<?php
                    /**
                     * @pure
                     */
                    function bar(string $st, string &$str = ""): string
                    {
                        $st .= $str;

                        return $st;
                    }

                    /**
                     * @pure
                     */
                    function baz(): string
                    {
                        $f = "foo";
                        bar(st: $f);

                        return $f;
                    }',
                'error_message' => 'UnusedFunctionCall',
            ],
            'propertyWrittenButNotRead' => [
                '<?php
                    class A {
                        public string $a = "hello";
                        public string $b = "world";

                        public function __construct() {
                            $this->a = "hello";
                            $this->b = "world";
                        }
                    }

                    $foo = new A();
                    echo $foo->a;',
                'error_message' => 'PossiblyUnusedProperty',
            ],
            'unusedInterfaceReturnValue' => [
                '<?php
                    interface I {
                        public function work(): bool;
                    }

                    function f(I $worker): void {
                        $worker->work();
                    }',
                'error_message' => 'PossiblyUnusedReturnValue',
            ],
            'unusedInterfaceReturnValueWithImplementingClass' => [
                '<?php
                    interface IWorker {
                        public function work(): bool;
                    }

                    class Worker implements IWorker{
                        public function work(): bool {
                            return true;
                        }
                    }

                    function f(IWorker $worker): void {
                        $worker->work();
                    }

                    f(new Worker());',
                'error_message' => 'PossiblyUnusedReturnValue',
            ],
            'interfaceWithImplementingClassMethodUnused' => [
                '<?php
                    interface IWorker {
                        public function work(): void;
                    }

                    class Worker implements IWorker {
                        public function work(): void {}
                    }

                    function f(IWorker $worker): void {
                        echo get_class($worker);
                    }

                    f(new Worker());',
                'error_message' => 'PossiblyUnusedMethod',
            ],
        ];
    }
}
