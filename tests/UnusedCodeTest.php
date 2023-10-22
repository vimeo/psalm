<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\IssueBuffer;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;

use function getcwd;
use function preg_quote;
use function strpos;

use const DIRECTORY_SEPARATOR;

class UnusedCodeTest extends TestCase
{
    protected ProjectAnalyzer $project_analyzer;

    public function setUp(): void
    {
        RuntimeCaches::clearAll();

        $this->file_provider = new FakeFileProvider();

        $this->project_analyzer = new ProjectAnalyzer(
            new TestConfig(),
            new Providers(
                $this->file_provider,
                new FakeParserCacheProvider(),
            ),
        );

        $this->project_analyzer->getCodebase()->reportUnusedCode();
        $this->project_analyzer->setPhpVersion('7.3', 'tests');
    }

    /**
     * @dataProvider providerValidCodeParse
     * @param array<string> $ignored_issues
     */
    public function testValidCode(string $code, array $ignored_issues = []): void
    {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            $code,
        );

        $this->project_analyzer->setPhpVersion('8.0', 'tests');

        foreach ($ignored_issues as $error_level) {
            $this->project_analyzer->getCodebase()->config->setCustomErrorLevel($error_level, Config::REPORT_SUPPRESS);
        }

        $this->analyzeFile($file_path, new Context(), false);

        $this->project_analyzer->consolidateAnalyzedData();

        IssueBuffer::processUnusedSuppressions($this->project_analyzer->getCodebase()->file_provider);
    }

    /**
     * @dataProvider providerInvalidCodeParse
     * @param array<string> $ignored_issues
     */
    public function testInvalidCode(string $code, string $error_message, array $ignored_issues = []): void
    {
        if (strpos($this->getTestName(), 'SKIPPED-') !== false) {
            $this->markTestSkipped();
        }

        $this->expectException(CodeException::class);
        $this->expectExceptionMessageMatches('/\b' . preg_quote($error_message, '/') . '\b/');

        $file_path = self::$src_dir_path . 'somefile.php';

        foreach ($ignored_issues as $error_level) {
            $this->project_analyzer->getCodebase()->config->setCustomErrorLevel($error_level, Config::REPORT_SUPPRESS);
        }

        $this->addFile(
            $file_path,
            $code,
        );

        $this->analyzeFile($file_path, new Context(), false);

        $this->project_analyzer->consolidateAnalyzedData();

        IssueBuffer::processUnusedSuppressions($this->project_analyzer->getCodebase()->file_provider);
    }

    public function testSeesClassesUsedAfterUnevaluatedCodeIssue(): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;
        $file_path = (string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                if (rand(0, 1)) {
                    throw new Exception("foo");
                    echo "bar";
                } else {
                    $f = new Foo();
                    $f->bar();
                }

                class Foo {
                    function bar(): void{
                        echo "foo";
                    }
                }
            ',
        );
        $this->analyzeFile($file_path, new Context(), false);
        $this->project_analyzer->consolidateAnalyzedData();

        $this->assertSame(1, IssueBuffer::getErrorCount());
        $issue = IssueBuffer::getIssuesDataForFile($file_path)[0];
        $this->assertSame('UnevaluatedCode', $issue->type);
        $this->assertSame(4, $issue->line_from);
    }

    public function testSeesUnusedClassReferencedByUnevaluatedCode(): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;
        $file_path = (string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                if (rand(0, 1)) {
                    throw new Exception("foo");
                    $f = new Foo();
                    $f->bar();
                } else {
                    echo "bar";
                }

                class Foo {
                    function bar(): void{
                        echo "foo";
                    }
                }
            ',
        );
        $this->analyzeFile($file_path, new Context(), false);
        $this->project_analyzer->consolidateAnalyzedData();

        $this->assertSame(3, IssueBuffer::getErrorCount());
        $issue = IssueBuffer::getIssuesDataForFile($file_path)[2];
        $this->assertSame('UnusedClass', $issue->type);
        $this->assertSame(10, $issue->line_from);
    }

    /**
     * @return array<string, array{code:string}>
     */
    public function providerValidCodeParse(): array
    {
        return [
            'magicCall' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    interface I {
                        public function foo(): void;
                    }

                    class A implements I {
                        public function foo(): void {}
                    }

                    (new A)->foo();',
            ],
            'constructorIsUsed' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    function foo(int $_) : void {}

                    foo(4);',
            ],
            'unusedParamWithUnusedPrefix' => [
                'code' => '<?php
                    function foo(int $unusedArg) : void {}

                    foo(4);',
            ],
            'usedFunctionCall' => [
                'code' => '<?php
                    $a = strlen("goodbye");
                    echo $a;',
            ],
            'possiblyUnusedParamWithUnderscore' => [
                'code' => '<?php
                    class A {
                        public static function foo(int $_ = null) : void {}
                    }

                    A::foo();',
            ],
            'possiblyUnusedParamWithUnusedPrefix' => [
                'code' => '<?php
                    class A {
                        public static function foo(int $unusedArg = null) : void {}
                    }

                    A::foo();',
            ],
            'usedClass' => [
                'code' => '<?php
                    class A { }
                    new A();',
            ],
            'usedTraitMethodWithImplicitCall' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    class C {
                        public static function foo() : void {}
                    }

                    function takesCallable(callable $c) : void {
                        $c();
                    }

                    takesCallable([C::class, "foo"]);',
            ],
            'propertyAndMethodOverriddenDownstream' => [
                'code' => '<?php
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
                'code' => '<?php

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
                'code' => '<?php
                    class A {
                        public function __construct() {}
                    }

                    if (extension_loaded("fdsfdsfd")) {
                        new A();
                    }',
            ],
            'useMethodPropertiesAfterExtensionLoaded' => [
                'code' => '<?php

                    final class a {
                        public static self $a;
                        public static function get(): a {
                            return new a;
                        }
                    }
                    
                    final class b {
                        public function test(): a {
                            return new a;
                        }
                    }
                    
                    function process(b $handler): a {
                        if (\extension_loaded("fdsfdsfd")) {
                            return $handler->test();
                        }
                        if (\extension_loaded("fdsfdsfd")) {
                            return a::$a;
                        }
                        if (\extension_loaded("fdsfdsfd")) {
                            return a::get();
                        }
                        return $handler->test();
                    }',
            ],
            'usedParamInIf' => [
                'code' => '<?php
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
                'code' => '<?php
                    class A {
                        public static function bar() : void {}
                    }

                    if (class_exists(A::class)) {
                        A::bar();
                    }',
            ],
            'usedParamInLoopBeforeBreak' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    class A {
                        /**
                         * @psalm-suppress UnusedMethod
                         */
                        public function foo() : void {}
                    }

                    new A();',
            ],
            'usedFunctionInCall' => [
                'code' => '<?php
                    function fooBar(): void {}

                    $foo = "foo";
                    $bar = "bar";

                    ($foo . ucfirst($bar))();',
            ],
            'usedParamInUnknownMethodConcat' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress MixedMethodCall
                     */
                    function foo(string $s, object $o) : void {
                        $o->foo("COUNT{$s}");
                    }',
            ],
            'usedFunctioninMethodCallName' => [
                'code' => '<?php
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

                    (new Foo)->bar("request");',
            ],
            'usedMethodCallForExternalMutationFreeClass' => [
                'code' => '<?php
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
                'code' => '<?php
                    /** @param array<int, int> $arr */
                    function inc(array $arr) : array {
                        $arr[strlen("hello")]++;
                        return $arr;
                    }',
            ],
            'pureFunctionUsesMethodBeforeReturning' => [
                'code' => '<?php
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
            'setRawCookieImpure' => [
                'code' => '<?php
                    setrawcookie(
                        "name",
                        "value",
                    );',
            ],
            'usedUsort' => [
                'code' => '<?php
                    /** @param string[] $arr */
                    function foo(array $arr) : array {
                        usort($arr, "strnatcasecmp");
                        return $arr;
                    }',
            ],
            'allowArrayMapWithClosure' => [
                'code' => '<?php
                    $a = [1, 2, 3];

                    array_map(function($i) { echo $i;}, $a);',
            ],
            'usedAssertFunction' => [
                'code' => '<?php
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
                    }',
            ],
            'usedFunctionCallInEval' => [
                'code' => '<?php
                    eval(str_repeat("a", 10));',
            ],
            'usedFunctionCallInsideSwitchWithTernary' => [
                'code' => '<?php
                    function getArg(string $method) : void {
                        switch (strtolower($method ?: "")) {
                            case "post":
                                break;

                            case "get":
                                break;

                            default:
                                break;
                        }
                    }',
            ],
            'ignoreSerializerSerialize' => [
                'code' => '<?php
                    class Foo implements Serializable {
                        public function serialize() : string {
                            return "";
                        }

                        public function unserialize($_serialized) : void {}
                    }

                    new Foo();',
            ],
            'ignoreSerializeAndUnserialize' => [
                'code' => '<?php
                    class Foo
                    {
                        public function __sleep(): array
                        {
                            throw new BadMethodCallException();
                        }
                        public function __wakeup(): void
                        {
                            throw new BadMethodCallException();
                        }
                    }

                    function test(Foo|int $foo, mixed $bar, iterable $baz): bool {
                        try {
                            serialize(new Foo());
                            serialize([new Foo()]);
                            serialize([[new Foo()]]);
                            serialize($foo);
                            serialize($bar);
                            serialize($baz);
                            unserialize("");
                        } catch (\Throwable) {
                            return false;
                        }

                        return true;
                    }',
            ],
            'useIteratorMethodsWhenCallingForeach' => [
                'code' => '<?php
                    /** @psalm-suppress UnimplementedInterfaceMethod, MissingTemplateParam */
                    class IterableResult implements \Iterator {
                        public function current() {
                            return null;
                        }

                        public function key() {
                            return 5;
                        }
                    }

                    $items = new IterableResult();

                    foreach ($items as $_item) {}',
            ],
            'usedThroughNewClassStringOfBase' => [
                'code' => '<?php
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

                    createFoo(Foo::class)->baz();',
            ],
            'usedMethodReferencedByString' => [
                'code' => '<?php
                    class A {
                        static function b(): void {}
                    }
                    $methodRef = "A::b";
                    $methodRef();',
            ],
            'usedMethodReferencedByStringWithLeadingBackslash' => [
                'code' => '<?php
                    class A {
                        static function b(): void {}
                    }
                    $methodRef = "\A::b";
                    $methodRef();',
            ],
            'arrayPushFunctionCall' => [
                'code' => '<?php
                    $a = [];

                    array_push($a, strlen("hello"));

                    echo $a[0];',
            ],
            'callMethodThatUpdatesStaticVar' => [
                'code' => '<?php
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

                    (new References)->bar(["a" => "b"]);',
            ],
            'promotedPropertyIsUsed' => [
                'code' => '<?php
                    class Test {
                        public function __construct(public int $id, public string $name) {}
                    }

                    $test = new Test(1, "ame");
                    echo $test->id;
                    echo $test->name;',
            ],
            'unusedNoReturnFunctionCall' => [
                'code' => '<?php
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
                    }',
            ],
            'unusedByReferenceFunctionCall' => [
                'code' => '<?php
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
                    }',
            ],
            'unusedVoidByReferenceFunctionCall' => [
                'code' => '<?php
                    function bar(string &$str): void
                    {
                        $str .= "foo";
                    }

                    function baz(): string
                    {
                        $f = "foo";
                        bar($f);

                        return $f;
                    }',
            ],
            'unusedNamedByReferenceFunctionCall' => [
                'code' => '<?php
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
                    }',
            ],
            'unusedNamedByReferenceFunctionCallV2' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    /**
                     * @psalm-pure
                     */
                    function getException(): \Exception
                    {
                        return new \Exception();
                    }

                    throw getException();',
            ],
            'nullableMethodCallIsUsed' => [
                'code' => '<?php
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

                    throw ($exception->getPrevious() ?? $exception);',
            ],
            'publicPropertyReadInFile' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    class A {
                        public function foo() : Exception {
                            return new Exception;
                        }
                    }
                    throw (new A)->foo();
                ',
            ],
            'staticMethodReturnValueUsedInThrow' => [
                'code' => '<?php
                    class A {
                        public static function foo() : Exception {
                            return new Exception;
                        }
                    }
                    throw A::foo();
                ',
            ],
            'variableUsedAsUnaryMinusOperand' => [
                'code' => '<?php
                    function f(): int
                    {
                        $a = 1;
                        $b = -$a;
                        return $b;
                    }
                ',
            ],
            'variableUsedAsUnaryPlusOperand' => [
                'code' => '<?php
                    function f(): int
                    {
                        $a = 1;
                        $b = +$a;
                        return $b;
                    }
                ',
            ],
            'variableUsedInBacktick' => [
                'code' => '<?php
                    $used = "echo";
                    /** @psalm-suppress ForbiddenCode */
                    `$used`;
                ',
            ],
            'notUnevaluatedFunction' => [
                'code' => '<?php
                    /** @return never */
                    function neverReturns(){
                        die();
                    }
                    unrelated();
                    neverReturns();

                    function unrelated():void{
                        echo "hello";
                    }',
            ],
            'NotUnusedWhenAssert' => [
                'code' => '<?php

                    class A {
                        public function getVal(?string $val): string {
                            $this->assert($val);

                            return $val;
                        }

                        /**
                         * @psalm-assert string $val
                         * @psalm-mutation-free
                         */
                        private function assert(?string $val): void {
                            if (null === $val) {
                                throw new Exception();
                            }
                        }
                    }

                    $a = new A();
                    echo $a->getVal(null);',
            ],
            'NotUnusedWhenThrows' => [
                'code' => '<?php
                    declare(strict_types=1);

                    /** @psalm-immutable */
                    final class UserList
                    {
                        /**
                         * @throws InvalidArgumentException
                         */
                        public function validate(): void
                        {
                            // Some validation happens here
                            throw new \InvalidArgumentException();
                        }
                    }

                    $a = new UserList();
                    $a->validate();
                    ',
            ],
            '__halt_compiler_no_usage_check' => [
                'code' => '<?php
                    exit(0);
                    __halt_compiler();
                    foobar
                ',
            ],
            'usedPropertyAsAssignmentKey' => [
                'code' => '<?php
                    class A {
                        public string $foo = "bar";
                        public array $bar = [];
                    }

                    $a = new A();
                    $a->bar[$a->foo] = "bar";
                    print_r($a->bar);',
            ],
            'psalm-api with unused class' => [
                'code' => <<<'PHP'
                    <?php
                    /** @psalm-api */
                    class A {}
                    PHP,
            ],
            'psalm-api with unused public and protected property' => [
                'code' => <<<'PHP'
                    <?php
                    /** @psalm-api */
                    class A {
                        public int $b = 0;
                        protected int $c = 0;
                    }
                    PHP,
            ],
            'psalm-api with unused public and protected method' => [
                'code' => <<<'PHP'
                    <?php
                    /** @psalm-api */
                    class A {
                        public function b(): void {}
                        protected function c(): void {}
                    }
                    PHP,
            ],
            'psalm-api on unused public method' => [
                'code' => <<<'PHP'
                    <?php
                    class A {
                        /** @psalm-api */
                        public function b(): void {}
                    }
                    new A;
                    PHP,
            ],
            'api with unused class' => [
                'code' => <<<'PHP'
                    <?php
                    /** @api */
                    class A {}
                    PHP,
            ],
            'api on unused public method' => [
                'code' => <<<'PHP'
                    <?php
                    class A {
                        /** @api */
                        public function b(): void {}
                    }
                    new A;
                    PHP,
            ],
        ];
    }

    /**
     * @return array<string,array{code:string,error_message:string,ignored_issues?:list<string>}>
     */
    public function providerInvalidCodeParse(): array
    {
        return [
            'unusedClass' => [
                'code' => '<?php
                    class A { }',
                'error_message' => 'UnusedClass',
            ],
            'publicUnusedMethod' => [
                'code' => '<?php
                    class A {
                        /** @return void */
                        public function foo() {}
                    }

                    new A();',
                'error_message' => 'PossiblyUnusedMethod',
            ],
            'possiblyUnusedParam' => [
                'code' => '<?php
                    class A {
                        /** @return void */
                        public function foo(int $i) {}
                    }

                    (new A)->foo(4);',
                'error_message' => 'PossiblyUnusedParam - src' . DIRECTORY_SEPARATOR
                    . 'somefile.php:4:49 - Param #1 is never referenced in this method',
            ],
            'unusedParam' => [
                'code' => '<?php
                    function foo(int $i) {}

                    foo(4);',
                'error_message' => 'UnusedParam',
            ],
            'possiblyUnusedProperty' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        public $foo = "hello";
                    }

                    $a = new A();',
                'error_message' => 'PossiblyUnusedProperty',
                'ignored_issues' => ['UnusedVariable'],
            ],
            'possiblyUnusedPropertyWrittenNeverRead' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        public $foo = "hello";
                    }

                    $a = new A();
                    $a->foo = "bar";',
                'error_message' => 'PossiblyUnusedProperty',
                'ignored_issues' => ['UnusedVariable'],
            ],
            'possiblyUnusedPropertyWithArrayWrittenNeverRead' => [
                'code' => '<?php
                    class A {
                        /** @var list<string> */
                        public array $foo = [];
                    }

                    $a = new A();
                    $a->foo[] = "bar";',
                'error_message' => 'PossiblyUnusedProperty',
                'ignored_issues' => ['UnusedVariable'],
            ],
            'unusedProperty' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        private $foo = "hello";
                    }

                    $a = new A();',
                'error_message' => 'UnusedProperty',
                'ignored_issues' => ['UnusedVariable'],
            ],
            'privateUnusedMethod' => [
                'code' => '<?php
                    class A {
                        /** @return void */
                        private function foo() {}
                    }

                    new A();',
                'error_message' => 'UnusedMethod',
            ],
            'unevaluatedCode' => [
                'code' => '<?php
                    function foo(): void {
                        return;
                        $a = "foo";
                    }',
                'error_message' => 'UnevaluatedCode',
            ],
            'unusedTraitMethodInParent' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    strlen("goodbye");',
                'error_message' => 'UnusedFunctionCall',
            ],
            'unusedMethodCallSimple' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    function foo(DateTimeImmutable $dt) : void {
                        $dt->modify("+1 day");
                    }',
                'error_message' => 'UnusedMethodCall',
            ],
            'unusedClassReferencesItself' => [
                'code' => '<?php
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
                'code' => '<?php

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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    interface I {
                        public function work(): bool;
                    }

                    function f(I $worker): void {
                        $worker->work();
                    }',
                'error_message' => 'PossiblyUnusedReturnValue',
            ],
            'unusedInterfaceReturnValueWithImplementingClass' => [
                'code' => '<?php
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
                'code' => '<?php
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
            'UnusedFunctionInDoubleConditional' => [
                'code' => '<?php
                    $list = [];

                    if (rand(0,1) && rand(0,1)) {
                        array_merge($list, []);
                    };
                ',
                'error_message' => 'UnusedFunctionCall',
            ],
            'functionNeverUnevaluatedCode' => [
                'code' => '<?php
                    /** @return never */
                    function neverReturns() {
                        die();
                    }

                    function f(): void {
                        neverReturns();
                        echo "hello";
                    }
                ',
                'error_message' => 'UnevaluatedCode',
            ],
            'methodNeverUnevaluatedCode' => [
                'code' => '<?php
                    class A{
                        /** @return never */
                        function neverReturns() {
                            die();
                        }

                        function f(): void {
                            $this->neverReturns();
                            echo "hello";
                        }
                    }
                ',
                'error_message' => 'UnevaluatedCode',
            ],
            'exitNeverUnevaluatedCode' => [
                'code' => '<?php
                    function f(): void {
                        exit();
                        echo "hello";
                    }
                ',
                'error_message' => 'UnevaluatedCode',
            ],
            'exitInlineHtml' => [
                'code' => '<?php
                    exit(0);
                    ?' . '>foo
                ',
                'error_message' => 'UnevaluatedCode',
            ],
            'noCrashOnReadonlyStaticProp' => [
                'code' => '<?php
                    /** @psalm-immutable */
                    final class C { public int $val = 2; }

                    final class A {
                        private static C $prop;
                        public static function f()
                        {
                            self::$prop->val = 1;
                        }
                    }
                ',
                'error_message' => 'InaccessibleProperty',
            ],
            'psalm-api with unused private property' => [
                'code' => <<<'PHP'
                    <?php
                    /** @psalm-api */
                    class A {
                        private int $b = 0;
                    }
                    PHP,
                'error_message' => 'UnusedProperty',
            ],
            'psalm-api with final class and unused protected property' => [
                'code' => <<<'PHP'
                    <?php
                    /** @psalm-api */
                    final class A {
                        protected int $b = 0;
                    }
                    PHP,
                'error_message' => 'PossiblyUnusedProperty',
            ],
            'psalm-api with unused private method' => [
                'code' => <<<'PHP'
                    <?php
                    /** @psalm-api */
                    class A {
                        private function b(): void {}
                    }
                    PHP,
                'error_message' => 'UnusedMethod',
            ],
            'psalm-api with final class and unused protected method' => [
                'code' => <<<'PHP'
                    <?php
                    /** @psalm-api */
                    final class A {
                        protected function b(): void {}
                    }
                    PHP,
                'error_message' => 'PossiblyUnusedMethod',
            ],
            'psalm-api with unused class and unused param' => [
                'code' => <<<'PHP'
                    <?php
                    /** @psalm-api */
                    class A {
                        public function b(int $c): void {}
                    }
                    PHP,
                'error_message' => 'PossiblyUnusedParam',
            ],
            'unused param' => [
                'code' => <<<'PHP'
                    <?php
                    /** @psalm-suppress UnusedClass */
                    class A {
                        public function b(int $c): void {}
                    }
                    PHP,
                'error_message' => 'PossiblyUnusedParam',
            ],
            'unused param tag' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @param string $param
                     */
                    function f(): void {}
                    PHP,
                'error_message' => 'UnusedDocblockParam',
            ],
        ];
    }
}
