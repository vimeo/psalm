<?php

namespace Psalm\Tests;

use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\InvalidCodeAnalysisWithIssuesTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class MethodSignatureTest extends TestCase
{
    use InvalidCodeAnalysisWithIssuesTestTrait;
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    public function testExtendSoapClientWithDocblockTypes(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                class A extends SoapClient
                {
                   /**
                     * @param string $function_name
                     * @param array<mixed> $arguments
                     * @param array<mixed> $options default null
                     * @param array|SoapHeader $input_headers default null
                     * @param array<mixed> $output_headers default null
                     * @return mixed
                     */
                    public function __soapCall(
                        $function_name,
                        $arguments,
                        $options = [],
                        $input_headers = [],
                        &$output_headers = []
                    ) {
                        return $_GET["foo"];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testExtendSoapClientWithNoDocblockTypes(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                class C extends SoapClient
                {
                    public function __soapCall(
                        string $function_name,
                        $arguments,
                        $options = [],
                        $input_headers = [],
                        &$output_headers = []
                    ) {
                        return $_GET["foo"];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testExtendSoapClientWithParamType(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                class C extends SoapClient
                {
                    public function __soapCall(
                        string $function_name,
                        $arguments,
                        $options = [],
                        $input_headers = [],
                        &$output_headers = []
                    ) {
                        return $_GET["foo"];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testMismatchingCovariantReturnIn73(): void
    {
        $this->expectExceptionMessage('MethodSignatureMismatch');
        $this->expectException(CodeException::class);

        $this->project_analyzer->setPhpVersion('7.3', 'tests');

        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    function foo(): C {
                        return new C();
                    }
                }
                class B extends A {
                    function foo(): D {
                        return new D();
                    }
                }
                class C {}
                class D extends C {}',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testMismatchingCovariantReturnIn74(): void
    {
        $this->project_analyzer->setPhpVersion('7.4', 'tests');

        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    function foo(): C {
                        return new C();
                    }
                }
                class B extends A {
                    function foo(): D {
                        return new D();
                    }
                }
                class C {}
                class D extends C {}',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testMismatchingCovariantReturnIn73WithSelf(): void
    {
        $this->expectExceptionMessage('MethodSignatureMismatch');
        $this->expectException(CodeException::class);

        $this->project_analyzer->setPhpVersion('7.3', 'tests');

        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    function foo(): self {
                        return new A();
                    }
                }
                class B extends A {
                    function foo(): self {
                        return new B();
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testMismatchingCovariantReturnIn74WithSelf(): void
    {
        $this->project_analyzer->setPhpVersion('7.4', 'tests');

        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    function foo(): self {
                        return new A();
                    }
                }
                class B extends A {
                    function foo(): self {
                        return new B();
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testMismatchingCovariantParamIn73(): void
    {
        $this->expectExceptionMessage('MethodSignatureMismatch');
        $this->expectException(CodeException::class);

        $this->project_analyzer->setPhpVersion('7.3', 'tests');

        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    public function foo(D $d) : void {}
                }
                class B extends A {
                    public function foo(C $c): void {}
                }

                class C {}
                class D extends C {}',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testMismatchingCovariantParamIn74(): void
    {
        $this->project_analyzer->setPhpVersion('7.4', 'tests');

        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    public function foo(D $d) : void {}
                }
                class B extends A {
                    public function foo(C $c): void {}
                }

                class C {}
                class D extends C {}',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testExtendDocblockParamTypeWithWrongDocblockParam(): void
    {
        $this->expectExceptionMessage('ImplementedParamTypeMismatch');
        $this->expectException(CodeException::class);

        $this->addFile(
            'somefile.php',
            '<?php
                class A extends SoapClient
                {
                   /**
                     * @param string $function_name
                     * @param string $arguments
                     * @param array<mixed> $options default null
                     * @param array<mixed> $input_headers default null
                     * @param array<mixed> $output_headers default null
                     * @return mixed
                     */
                    public function __soapCall(
                        $function_name,
                        $arguments,
                        $options = [],
                        $input_headers = [],
                        &$output_headers = []
                    ) {

                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testExtendDocblockParamTypeWithWrongParam(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('MethodSignatureMismatch');

        $this->addFile(
            'somefile.php',
            '<?php
                class A extends SoapClient
                {
                    public function __soapCall(
                        $function_name,
                        string $arguments,
                        $options = [],
                        $input_headers = [],
                        &$output_headers = []
                    ) {

                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function providerValidCodeParse(): iterable
    {
        return [
            'privateArgs' => [
                'code' => '<?php
                    class A {
                        private function foo(): void {}
                    }
                    class B extends A {
                        private function foo(int $arg): void {}
                    }',
            ],
            'nullableSubclassParam' => [
                'code' => '<?php
                    class A {
                        public function foo(string $s): ?string {
                            return rand(0, 1) ? $s : null;
                        }
                    }

                    class B extends A {
                        public function foo(?string $s): string {
                            return $s !== null ? $s : "hello";
                        }
                    }

                    echo (new B)->foo(null);',
            ],
            'nullableSubclassParamWithDefault' => [
                'code' => '<?php
                    class A {
                        public function foo(string $s): string {
                            return $s;
                        }
                    }

                    class B extends A {
                        public function foo(string $s = null): string {
                            return $s !== null ? $s : "hello";
                        }
                    }

                    echo (new B)->foo();',
            ],
            'allowSubclassesForNonInheritedMethodParams' => [
                'code' => '<?php
                    class A {}
                    class B extends A {
                      public function bar(): void {}
                    }
                    class C extends A {
                      public function bar(): void {}
                    }

                    /** @param B|C $a */
                    function foo(A $a): void {
                      $a->bar();
                    }',
            ],
            'allowNoReturnInSubclassWithNullableReturnType' => [
                'code' => '<?php
                    class A {
                        /** @return ?int */
                        public function foo() {
                            if (rand(0, 1)) return 5;
                        }
                    }

                    class B extends A {
                        public function foo() {}
                    }',
            ],
            'selfReturnShouldBeParent' => [
                'code' => '<?php
                    class A {
                        /** @return self */
                        public function foo() {
                            return new A();
                        }
                    }

                    class B extends A {
                        public function foo() {
                            return new A();
                        }
                    }',
            ],
            'staticReturnShouldBeStatic' => [
                'code' => '<?php
                    class A {
                        /** @return static */
                        public static function foo() {
                            return new static();
                        }

                        final public function __construct() {}
                    }

                    class B extends A {
                        public static function foo() {
                            return new static();
                        }
                    }

                    $b = B::foo();',
                'assertions' => [
                    '$b' => 'B',
                ],
            ],
            'allowSomeCovariance' => [
                'code' => '<?php
                    interface I1 {
                        public function test(string $s) : ?string;
                        public function testIterable(array $a) : ?iterable;
                    }

                    class A1 implements I1 {
                        public function test(?string $s) : string {
                            return "value";
                        }
                        public function testIterable(?iterable $a) : array {
                            return [];
                        }
                    }',
            ],
            'allowVoidToNullConversion' => [
                'code' => '<?php
                    class A {
                        /** @return ?string */
                        public function foo() {
                            return rand(0, 1) ? "hello" : null;
                        }
                    }

                    class B extends A {
                        public function foo(): void {
                            return;
                        }
                    }

                    class C extends A {
                        /** @return void */
                        public function foo() {
                            return;
                        }
                    }

                    class D extends A {
                        /** @return null */
                        public function foo() {
                            return null;
                        }
                    }',
            ],
            'allowNoChildClassPropertyWhenMixed' => [
                'code' => '<?php
                    class A implements Serializable {
                        /** @var int */
                        private $id = 1;

                        /**
                         * @param string $data
                         */
                        public function unserialize($data) : void
                        {
                            [
                                $this->id,
                            ] = (array) \unserialize($data);
                        }

                        public function serialize() : string
                        {
                            return serialize([$this->id]);
                        }
                    }',
            ],
            'clashWithCallMapClass' => [
                'code' => '<?php
                    class HaruDestination {}
                    class AClass
                    {
                        public function get(): HaruDestination
                        {
                            return new HaruDestination;
                        }
                    }',
            ],
            'classWithTraitExtendsNonAbstractWithMethod' => [
                'code' => '<?php
                    class A {
                        public function foo() : void {}
                    }

                    trait T {
                        abstract public function foo() : void;
                    }

                    class B extends A {
                        use T;
                    }',
            ],
            'inheritsSplClasses' => [
                'code' => '<?php
                    namespace App;

                    use SplObserver;
                    use SplSubject;

                    class Observer implements \SplObserver
                    {
                        public function update(SplSubject $subject): void
                        {
                        }
                    }

                    class Subject implements \SplSubject
                    {
                        public function attach(SplObserver $observer): void
                        {
                        }

                        public function detach(SplObserver $observer): void
                        {
                        }

                        public function notify(): void
                        {
                        }
                    }',
            ],
            'noMixedIssueWhenInheritParamTypes' => [
                'code' => '<?php
                    class A {
                      /**
                       * @param string $bar
                       * @return void
                       */
                      public function foo($bar) {
                        echo $bar;
                      }
                    }

                    class B extends A {
                      public function foo($bar) {
                        echo "hello " . $bar;
                      }
                    }',
            ],
            'inheritDocumentedSelf' => [
                'code' => '<?php
                    interface I {
                        /**
                         * @param self $f
                         */
                        public function foo(self $f) : self;
                    }

                    class C implements I {
                        public function foo(I $f) : I {
                            return new C();
                        }
                    }',
            ],
            'allowInterfaceImplementation' => [
                'code' => '<?php
                    abstract class A {
                        /** @return static */
                        public function foo() {
                            return $this;
                        }
                    }

                    interface I {
                        /** @return I */
                        public function foo();
                    }

                    class C extends A implements I {}',
            ],
            'enforceParameterInheritanceWithInheritDocAndParam' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}

                    class X {
                        /**
                         * @param B $class
                         */
                        public function boo(A $class): void {}
                    }

                    class Y extends X {
                        /**
                         * @inheritdoc
                         * @param A $class
                         */
                        public function boo(A $class): void {}
                    }

                    class Z extends X {
                        /**
                         * @inheritDoc
                         * @param A $class
                         */
                        public function boo(A $class): void {}
                    }

                    (new Y())->boo(new A());
                    (new Z())->boo(new A());',
            ],
            'allowMixedExtensionOfIteratorAggregate' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress MissingTemplateParam
                     */
                    class C implements IteratorAggregate {
                        public function getIterator(): Iterator {
                            return new ArrayIterator([]);
                        }
                    }',
            ],
            'allowExtraVariadic' => [
                'code' => '<?php
                    interface I {
                        public function f(string $a, int $b): void;
                    }

                    class C implements I {
                        public function f(string $a = "a", int $b = 1, float ...$rest): void {}
                    }

                    (new C)->f();
                    (new C)->f("b");
                    (new C)->f("b", 3);
                    (new C)->f("b", 3, 0.5);
                    (new C)->f("b", 3, 0.5, 0.8);',
            ],
            'allowLessSpecificDocblockTypeOnParent' => [
                'code' => '<?php
                    abstract class Foo {
                        /**
                         * @return array|string
                         */
                        abstract public function getTargets();
                    }

                    class Bar extends Foo {
                        public function getTargets(): string {
                            return "baz";
                        }
                    }

                    $a = (new Bar)->getTargets();',
                'assertions' => [
                    '$a' => 'string',
                ],
            ],
            'parentIsKnown' => [
                'code' => '<?php
                    class A {
                        public function returnSelf() : self {
                            return $this;
                        }
                    }

                    class B extends A {
                        public function returnSelf() : parent {
                            return parent::returnSelf();
                        }

                    }',
            ],
            'returnStaticParent' => [
                'code' => '<?php
                    class A {
                        /**
                         * @return static
                         */
                        public static function foo() {
                            return new static();
                        }

                        final public function __construct() {}
                    }

                    class B extends A {
                        /**
                         * @return static
                         */
                        public static function foo() {
                            return parent::foo();
                        }
                    }',
            ],
            'selfInTraitAbstractIsFine' => [
                'code' => '<?php
                    trait SomeTrait {
                        abstract public function a(self $b): self;
                    }

                    class SomeClass {
                        use SomeTrait;

                        public function a(self $b): self {
                            return $this;
                        }
                    }',
            ],
            'allowMatchIn74' => [
                'code' => '<?php
                    trait FooTrait {
                        /**
                         * @return static
                         */
                        public function bar(): self  {
                            return $this;
                        }
                    }

                    interface FooInterface {
                        /**
                         * @return static
                         */
                        public function bar(): self;
                    }

                    class FooClass implements FooInterface {
                        use FooTrait;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'allowOverridingThrowable' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     */
                    interface MyException extends \Throwable
                    {
                        /**
                         * Informative comment
                         */
                        public function getMessage(): string;
                        public function getCode();
                        public function getFile(): string;
                        public function getLine(): int;
                        public function getTrace(): array;
                        public function getPrevious(): ?\Throwable;
                        public function getTraceAsString(): string;
                    }',
            ],
            'allowExceptionToStringWithNoType' => [
                'code' => '<?php
                    class E extends Exception {
                        public function __toString() {
                            return "hello";
                        }
                    }',
            ],
            'allowExceptionToStringIn71' => [
                'code' => '<?php
                    class E extends Exception {
                        public function __toString() : string {
                            return "hello";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.1',
            ],
            'consistentConstructor' => [
                'code' => '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    class A {
                        public function getInstance() : self {
                            return new static();
                        }
                    }

                    class AChild extends A {
                        public function __construct() {}
                    }',
            ],
            'allowStaticInheritance' => [
                'code' => '<?php
                    class A {
                        public function method(): static {
                            return $this;
                        }
                    }
                    class B extends A {
                        public function method(): static {
                            return $this;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'suppressDocblockFinal' => [
                'code' => '<?php
                    /**
                     * @final
                     */
                    class A {
                       public function foo(): void {}
                    }

                    /**
                     * @psalm-suppress InvalidExtendClass
                     */
                    class B extends A {
                        /**
                         * @psalm-suppress MethodSignatureMismatch
                         */
                        public function foo(): void {}
                    }',
            ],
            'inheritParamTypeWhenSignatureReturnTypeChanged' => [
                'code' => '<?php
                    class A {
                        public function __construct(string $s) {}
                    }

                    class AChild extends A {}

                    interface B  {
                        /** @param string $data */
                        public function create($data): A;
                    }

                    class C implements B {
                        public function create($data): AChild {
                            return new AChild($data);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'extendStaticReturnTypeInFinal' => [
                'code' => '<?php
                    final class B extends A
                    {
                        public static function doCreate1(): self
                        {
                            return self::create1();
                        }

                        public static function doCreate2(): self
                        {
                            return self::create2();
                        }
                    }

                    abstract class A
                    {
                        final private function __construct() {}

                        final protected static function create1(): static
                        {
                            return new static();
                        }

                        /** @return static */
                        final protected static function create2()
                        {
                            return new static();
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'notExtendedStaticReturntypeInFinal' => [
                'code' => '<?php
                    final class X
                    {
                        public static function create(): static
                        {
                            return new self();
                        }
                    }',
            ],
            'callParentMethodFromTrait' => [
                'code' => '<?php
                    class MyParentClass
                    {
                        /** @return static */
                        public function myMethod()
                        {
                            return $this;
                        }
                    }

                    trait MyTrait
                    {
                        final public function myMethod() : self
                        {
                            return parent::myMethod();
                        }
                    }

                    class MyChildClass extends MyParentClass
                    {
                        use MyTrait;
                    }',
            ],
            'MixedParamInImplementation' => [
                'code' => '<?php
                    interface I
                    {
                        /**
                         * @param mixed $a
                         */
                        public function a($a): void;
                    }


                    final class B implements I
                    {
                        public function a(mixed $a): void {}
                    }',
            ],
            'doesNotRequireInterfaceDestructorsToHaveReturnType' => [
                'code' => '<?php
                    interface I
                    {
                        public function __destruct();
                    }

                    class C implements I
                    {
                        public function __destruct() {}
                    }
                ',
            ],
            'allowByRefReturn' => [
                'code' => '<?php
                    interface Foo {
                        public function &foo(): int;
                    }

                    class Bar implements Foo {
                        private int $x = 0;
                        public function &foo(): int {
                            return $this->x;
                        }
                    }
                ',
            ],
            'descendantAddsByRefReturn' => [
                'code' => '<?php
                    interface Foo {
                        public function foo(): int;
                    }

                    class Bar implements Foo {
                        private int $x = 0;
                        public function &foo(): int {
                            return $this->x;
                        }
                    }
                ',
            ],
            'callmapInheritedMethodParamsDoNotHavePrefixes' => [
                'code' => <<<'PHP'
                    <?php

                    class NoopFilter extends \php_user_filter
                    {
                        /**
                         * @param resource $in
                         * @param resource $out
                         * @param int $consumed   -- this is called &rw_consumed in the callmap
                         */
                        public function filter($in, $out, &$consumed, bool $closing): int
                        {
                            return PSFS_PASS_ON;
                        }
                    }
                PHP,
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'oneParam' => [
                'code' => '<?php
                    interface I {
                        /**
                         * @param array $i
                         */
                        public function foo(array $i) : void;
                    }

                    class C implements I {
                        public function foo(array $c) : void {
                            return;
                        }
                    }',
                'error_message' => 'Argument 1 of C::foo has wrong name $c, expecting $i as defined by I::foo',
            ],
            'moreArguments' => [
                'code' => '<?php
                    class A {
                        public function fooFoo(int $a, bool $b): void {

                        }
                    }

                    class B extends A {
                        public function fooFoo(int $a, bool $b, array $c): void {

                        }
                    }',
                'error_message' => 'Method B::fooFoo has more required parameters than parent method A::fooFoo',
            ],
            'fewerArguments' => [
                'code' => '<?php
                    class A {
                        public function fooFoo(int $a, bool $b): void {

                        }
                    }

                    class B extends A {
                        public function fooFoo(int $a): void {

                        }
                    }',
                'error_message' => 'Method B::fooFoo has fewer parameters than parent method A::fooFoo',
            ],
            'differentArgumentTypes' => [
                'code' => '<?php
                    class A {
                        public function fooFoo(int $a, bool $b): void {

                        }
                    }

                    class B extends A {
                        public function fooFoo(int $a, int $b): void {

                        }
                    }',
                'error_message' => 'Argument 2 of B::fooFoo has wrong type \'int\', expecting \'bool\' as defined ' .
                    'by A::fooFoo',
            ],
            'differentArgumentNames' => [
                'code' => '<?php
                    class A {
                        public function fooFoo(int $a, bool $b): void {

                        }
                    }

                    class B extends A {
                        public function fooFoo(int $a, bool $c): void {

                        }
                    }',
                'error_message' => 'ParamNameMismatch',
            ],
            'differentArgumentName' => [
                'code' => '<?php
                    class A {
                        public function fooFoo(int $a): void {

                        }
                    }

                    class B extends A {
                        public function fooFoo(int $b): void {

                        }
                    }',
                'error_message' => 'ParamNameMismatch',
            ],
            'nonNullableSubclassParam' => [
                'code' => '<?php
                    class A {
                        public function foo(?string $s): string {
                            return $s !== null ? $s : "hello";
                        }
                    }

                    class B extends A {
                        public function foo(string $s): string {
                            return $s;
                        }
                    }',
                'error_message' => 'Argument 1 of B::foo has wrong type \'string\', expecting \'null|string\' as',
            ],
            'misplacedRequiredParam' => [
                'code' => '<?php
                    function foo(string $bar = null, int $bat): void {}
                    foo();',
                'error_message' => 'TooFewArguments',
            ],
            'clasginByRef' => [
                'code' => '<?php
                    class A {
                      public function foo(string $a): void {
                        echo $a;
                      }
                    }
                    class B extends A {
                      public function foo(string &$a): void {
                        echo $a;
                      }
                    }',
                'error_message' => 'MethodSignatureMismatch',
            ],
            'disallowSubclassesForNonInheritedMethodParams' => [
                'code' => '<?php
                    class A {}
                    class B extends A {
                      public function bar(): void {}
                    }
                    class C extends A {
                      public function bar(): void {}
                    }

                    class D {
                      public function foo(A $a): void {}
                    }

                    class E extends D {
                      /** @param B|C $a */
                      public function foo(A $a): void {
                        $a->bar();
                      }
                    }',
                'error_message' => 'MoreSpecificImplementedParamType',
            ],
            'preventVoidToNullConversionSignature' => [
                'code' => '<?php
                    class A {
                        public function foo(): ?string {
                            return rand(0, 1) ? "hello" : null;
                        }
                    }

                    class B extends A {
                        public function foo(): void {
                            return;
                        }
                    }',
                'error_message' => 'MethodSignatureMismatch',
            ],
            'abstractExtendsNonAbstractWithMethod' => [
                'code' => '<?php
                    class A {
                        public function foo() : void {}
                    }

                    abstract class B extends A {
                        abstract public function foo() : void;
                    }',
                'error_message' => 'MethodSignatureMismatch',
            ],
            'traitReturnTypeMismatch' => [
                'code' => '<?php
                    class A {
                        public function foo() : void {}
                    }

                    trait T {
                        abstract public function foo() : string;
                    }

                    class B extends A {
                        use T;
                    }',
                'error_message' => 'MethodSignatureMismatch',
            ],
            'abstractTraitMethodWithDifferentReturnType' => [
                'code' => '<?php
                    class A {}
                    class B {}

                    trait T {
                        abstract public function foo() : A;
                    }

                    class C {
                        use T;

                        public function foo() : B{
                            return new B();
                        }
                    }',
                'error_message' => 'TraitMethodSignatureMismatch',
            ],
            'traitMoreParams' => [
                'code' => '<?php
                    class A {
                        public function foo() : void {}
                    }

                    trait T {
                        abstract public function foo(string $s) : string;
                    }

                    class B extends A {
                        use T;
                    }',
                'error_message' => 'MethodSignatureMismatch',
            ],
            'abstractTraitMethodWithDifferentParamType' => [
                'code' => '<?php
                    class A {}
                    class B {}

                    trait T {
                        abstract public function foo(A $a) : void;
                    }

                    class C {
                        use T;

                        public function foo(B $a) : void {}
                    }',
                'error_message' => 'TraitMethodSignatureMismatch',
            ],
            'mustOmitReturnType' => [
                'code' => '<?php
                    class A
                    {
                        public function __construct(): void
                        {
                        }
                    }',
                'error_message' => 'MethodSignatureMustOmitReturnType',
            ],
            'requireParam' => [
                'code' => '<?php
                    interface I {
                        function foo(bool $b = false): void;
                    }

                    class C implements I {
                        public function foo(bool $b): void {}
                    }',
                'error_message' => 'MethodSignatureMismatch - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:27 - Method C::foo has more required',
            ],
            'inheritParamTypes' => [
                'code' => '<?php
                    class A {
                      /**
                       * @param string $bar
                       * @return void
                       */
                      public function foo($bar) {
                        echo $bar;
                      }
                    }

                    class B extends A {
                      public function foo($bar) {
                        echo "hello " . $bar;
                      }
                    }

                    (new B)->foo(new stdClass);',
                'error_message' => 'InvalidArgument',
            ],
            'interfaceHasFewerConstructorArgs' => [
                'code' => '<?php
                    interface Foo {
                        public function __construct();
                    }

                    class Bar implements Foo {
                        public function __construct(bool $foo) {}
                    }',
                'error_message' => 'ConstructorSignatureMismatch',
            ],
            'enforceParameterInheritanceWithInheritDoc' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}

                    class X {
                        /**
                         * @param B $class
                         */
                        public function boo(A $class): void {}
                    }

                    class Y extends X {
                        /**
                         * @inheritdoc
                         */
                        public function boo(A $class): void {}
                    }

                    (new Y())->boo(new A());',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'enforceParameterInheritanceWithCapitalizedInheritDoc' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}

                    class X {
                        /**
                         * @param B $class
                         */
                        public function boo(A $class): void {}
                    }

                    class Y extends X {
                        /**
                         * @inheritDoc
                         */
                        public function boo(A $class): void {}
                    }

                    (new Y())->boo(new A());',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'warnAboutMismatchingClassParamDoc' => [
                'code' => '<?php
                    class A {}
                    class B {}

                    class X {
                        /**
                         * @param B $class
                         */
                        public function boo(A $class): void {}
                    }',
                'error_message' => 'MismatchingDocblockParamType',
            ],
            'warnAboutMismatchingInterfaceParamDoc' => [
                'code' => '<?php
                    class A {}
                    class B {}

                    interface X {
                        /**
                         * @param B $class
                         */
                        public function boo(A $class): void {}
                    }',
                'error_message' => 'MismatchingDocblockParamType',
            ],
            'interfaceInsertDocblockTypes' => [
                'code' => '<?php
                    class Foo {}
                    class Bar {}

                    interface I {
                      /** @return array<int, Foo> */
                      public function getFoos() : array;
                    }

                    class A implements I {
                        public function getFoos() : array {
                            return [new Bar()];
                        }
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'classInsertDocblockTypesFromParent' => [
                'code' => '<?php
                    class Foo {}
                    class Bar {}

                    class B {
                        /** @return array<int, Foo> */
                        public function getFoos() : array {
                            return [new Foo()];
                        }
                    }

                    class A extends B {
                        public function getFoos() : array {
                            return [new Bar()];
                        }
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'preventInterfaceOverload' => [
                'code' => '<?php
                    interface I {
                        public function f(float ...$rest): void;
                    }

                    class C implements I {
                        /**
                         * @param array<int,float> $f
                         * @psalm-suppress ParamNameMismatch
                         */
                        public function f($f): void {}
                    }',
                'error_message' => 'MethodSignatureMismatch',
                'ignored_issues' => ['MoreSpecificImplementedParamType'],
            ],
            'preventOneOfUnionMoreSpecific' => [
                'code' => '<?php
                    class A {
                        /** @param string|int $s */
                        public function foo($s) : void {}
                    }

                    class B extends A {
                        /** @param string $s */
                        public function foo($s) : void {}
                    }',
                'error_message' => 'MoreSpecificImplementedParamType',
            ],
            'preventImplementingSerializableWithWrongDocblockType' => [
                'code' => '<?php
                    class Foo implements \Serializable {
                        /** @param int $data */
                        public function unserialize($data) {}
                        public function serialize() {}
                    }',
                'error_message' => 'ImplementedParamTypeMismatch',
            ],
            'returnsParentWithNoParent' => [
                'code' => '<?php
                    class Foo {
                        public function f(): parent {}
                    }
                ',
                'error_message' => 'InvalidParent',
            ],
            'returnsParentWithNoParentAndInvalidParentSuppressed' => [
                'code' => '<?php
                    class Foo {
                        public function f(): parent {
                        }
                    }
                ',
                'error_message' => 'InvalidReturnType',
                'ignored_issues' => ['InvalidParent'],
            ],
            // not sure how to handle it
            'SKIPPED-returnsParentWithNoParentAndInvalidParentSuppressedMismatchingReturn' => [
                'code' => '<?php
                    class Foo {
                        public function f(): parent {
                            return false;
                        }
                    }
                ',
                'error_message' => 'InvalidReturnType',
                'ignored_issues' => ['InvalidParent'],
            ],
            'regularMethodMismatchFromParentUse' => [
                'code' => '<?php
                    trait T2 {
                        abstract public function test(int $x) : void;
                    }

                    abstract class P2 {
                        use T2;
                    }

                    class C2 extends P2 {
                        public function test(string $x) : void {}
                    }',
                'error_message' => 'MethodSignatureMismatch',
            ],
            'regularMethodMismatchFromChildUse' => [
                'code' => '<?php
                    trait T3 {
                        abstract public function test(int $x) : void;
                    }

                    class P3 {
                        public function test(string $x) : void {}
                    }

                    class C3 extends P3 {
                        use T3;
                    }',
                'error_message' => 'MethodSignatureMismatch',
            ],
            'traitMethodAccessLevel' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}

                    trait T1 {
                        abstract protected static function test(A $x) : void;
                    }

                    class C1 {
                        use T1;

                        private static function test(B $x) : void {}
                    }',
                'error_message' => 'TraitMethodSignatureMismatch',
            ],
            'abstractClassReturnMismatch' => [
                'code' => '<?php
                    interface I {
                        function foo(): array;
                    }

                    abstract class C implements I {
                        public function foo(): void {}
                    }',
                'error_message' => 'MethodSignatureMismatch',
            ],
            'abstractClassParamMismatch' => [
                'code' => '<?php
                    interface I {
                        function foo(int $s): void;
                    }

                    abstract class C implements I {
                        public function foo(string $s): void {}
                    }',
                'error_message' => 'MethodSignatureMismatch',
            ],
            'preventTraitMatchIn73' => [
                'code' => '<?php
                    trait FooTrait {
                        /**
                         * @return static
                         */
                        public function bar(): self  {
                            return $this;
                        }
                    }

                    interface FooInterface {
                        /**
                         * @return static
                         */
                        public function bar(): self;
                    }

                    class FooClass implements FooInterface {
                        use FooTrait;
                    }',
                'error_message' => 'MethodSignatureMismatch',
                'ignored_issues' => [],
                'php_version' => '7.3',
            ],
            'inconsistentConstructorExplicitParentConstructorArgCount' => [
                'code' => '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    class A {
                        public function getInstance() : self
                        {
                            return new static();
                        }

                        public function __construct() {}
                    }

                    class BadAChild extends A {
                        public function __construct(string $s) {}
                    }',
                'error_message' => 'ConstructorSignatureMismatch',
            ],
            'inconsistentConstructorExplicitParentConstructorType' => [
                'code' => '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    class A {
                        public function getInstance() : self
                        {
                            return new static(5);
                        }

                        public function __construct(int $s) {}
                    }

                    class BadAChild extends A {
                        public function __construct(string $s) {}
                    }',
                'error_message' => 'ConstructorSignatureMismatch',
            ],
            'inconsistentConstructorImplicitParentConstructor' => [
                'code' => '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    class A {
                        public function getInstance() : self {
                            return new static();
                        }
                    }

                    class BadAChild extends A {
                        public function __construct(string $s) {}
                    }',
                'error_message' => 'ConstructorSignatureMismatch',
            ],
            'inheritDocblockReturnFromInterface' => [
                'code' => '<?php
                    interface A {
                        /** @return ?string */
                        function foo();
                    }

                    class C implements A {
                        public function foo() : ?string {}
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'disableNamedArgumentsInDescendant' => [
                'code' => '<?php
                    interface Foo {
                        public function bar(string ...$_args): void;
                    }
                    final class Baz implements Foo {
                        /** @no-named-arguments */
                        public function bar(string ...$_args): void {}
                    }
                ',
                'error_message' => 'MethodSignatureMismatch',
            ],
            'SKIPPED-noMixedTypehintInDescendant' => [
                'code' => '<?php
                    class a {
                        public function test(): mixed {
                            return 0;
                        }
                    }
                    class b extends a {
                        public function test() {
                            return 0;
                        }
                    }
                ',
                'error_message' => 'MethodSignatureMismatch',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'noTypehintInNativeDescendant' => [
                'code' => '<?php
                    class a implements JsonSerializable {
                        public function jsonSerialize() {
                            return 0;
                        }
                    }
                ',
                'error_message' => 'MethodSignatureMustProvideReturnType',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'absentByRefReturnInDescendant' => [
                'code' => '<?php
                    interface Foo {
                        public function &foo(): int;
                    }

                    class Bar implements Foo {
                        public function foo(): int {
                            return 1;
                        }
                    }
                ',
                'error_message' => 'MethodSignatureMismatch',
            ],
        ];
    }
}
