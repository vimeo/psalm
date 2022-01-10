<?php

namespace Psalm\Tests;

use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function class_exists;

use const DIRECTORY_SEPARATOR;

class MethodSignatureTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    public function testExtendSoapClientWithDocblockTypes(): void
    {
        if (class_exists('SoapClient') === false) {
            $this->markTestSkipped('Cannot run test, base class "SoapClient" does not exist!');
        }

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
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testExtendSoapClientWithNoDocblockTypes(): void
    {
        if (class_exists('SoapClient') === false) {
            $this->markTestSkipped('Cannot run test, base class "SoapClient" does not exist!');
        }

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
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testExtendSoapClientWithParamType(): void
    {
        if (class_exists('SoapClient') === false) {
            $this->markTestSkipped('Cannot run test, base class "SoapClient" does not exist!');
        }

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
                }'
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
                class D extends C {}'
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
                class D extends C {}'
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
                }'
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
                }'
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
                class D extends C {}'
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
                class D extends C {}'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testExtendDocblockParamTypeWithWrongDocblockParam(): void
    {
        $this->expectExceptionMessage('ImplementedParamTypeMismatch');
        $this->expectException(CodeException::class);
        if (class_exists('SoapClient') === false) {
            $this->markTestSkipped('Cannot run test, base class "SoapClient" does not exist!');
        }

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
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testExtendDocblockParamTypeWithWrongParam(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('MethodSignatureMismatch');

        if (class_exists('SoapClient') === false) {
            $this->markTestSkipped('Cannot run test, base class "SoapClient" does not exist!');
        }

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
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'privateArgs' => [
                '<?php
                    class A {
                        private function foo(): void {}
                    }
                    class B extends A {
                        private function foo(int $arg): void {}
                    }',
            ],
            'nullableSubclassParam' => [
                '<?php
                    class A {
                        public function foo(string $s): ?string {
                            return rand(0, 1) ? $s : null;
                        }
                    }

                    class B extends A {
                        public function foo(?string $s): string {
                            return $s ?: "hello";
                        }
                    }

                    echo (new B)->foo(null);',
            ],
            'nullableSubclassParamWithDefault' => [
                '<?php
                    class A {
                        public function foo(string $s): string {
                            return $s;
                        }
                    }

                    class B extends A {
                        public function foo(string $s = null): string {
                            return $s ?: "hello";
                        }
                    }

                    echo (new B)->foo();',
            ],
            'allowSubclassesForNonInheritedMethodParams' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    class A implements Serializable {
                        /** @var int */
                        private $id = 1;

                        /**
                         * @param string $serialized
                         */
                        public function unserialize($serialized) : void
                        {
                            [
                                $this->id,
                            ] = (array) \unserialize($serialized);
                        }

                        public function serialize() : string
                        {
                            return serialize([$this->id]);
                        }
                    }',
            ],
            'clashWithCallMapClass' => [
                '<?php
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
                '<?php
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
                '<?php
                    namespace App;

                    use SplObserver;
                    use SplSubject;

                    class Observer implements \SplObserver
                    {
                        public function update(SplSubject $subject)
                        {
                        }
                    }

                    class Subject implements \SplSubject
                    {
                        public function attach(SplObserver $observer)
                        {
                        }

                        public function detach(SplObserver $observer)
                        {
                        }

                        public function notify()
                        {
                        }
                    }',
            ],
            'noMixedIssueWhenInheritParamTypes' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    class C implements IteratorAggregate {
                        public function getIterator(): Iterator {
                            return new ArrayIterator([]);
                        }
                    }',
            ],
            'allowExtraVariadic' => [
                '<?php
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
                '<?php
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
                [
                    '$a' => 'string',
                ],
            ],
            'parentIsKnown' => [
                '<?php
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
                '<?php
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
                '<?php
                    trait SomeTrait {
                        abstract public function a(self $b): self;
                    }

                    class SomeClass {
                        use SomeTrait;

                        public function a(self $b): self {
                            return $this;
                        }
                    }'
            ],
            'allowMatchIn74' => [
                '<?php
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
                [],
                [],
                '7.4'
            ],
            'allowOverridingThrowable' => [
                '<?php
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
                    }'
            ],
            'allowExecptionToStringWithNoType' => [
                '<?php
                    class E extends Exception {
                        public function __toString() {
                            return "hello";
                        }
                    }'
            ],
            'allowExecptionToStringIn71' => [
                '<?php
                    class E extends Exception {
                        public function __toString() : string {
                            return "hello";
                        }
                    }',
                [],
                [],
                '7.1'
            ],
            'consistentConstructor' => [
                '<?php
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
                    }'
            ],
            'allowStaticInheritance' => [
                '<?php
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
                [],
                [],
                '8.0'
            ],
            'suppressDocblockFinal' => [
                '<?php
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
                    }'
            ],
            'inheritParamTypeWhenSignatureReturnTypeChanged' => [
                '<?php
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
                [],
                [],
                '7.4'
            ],
            'extendStaticReturnTypeInFinal' => [
                '<?php
                    final class B extends A
                    {
                        public static function doCretate1(): self
                        {
                            return self::create1();
                        }

                        public static function doCretate2(): self
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
                [],
                [],
                '8.0'
            ],
            'notExtendedStaticReturntypeInFinal' => [
                '<?php
                    final class X
                    {
                        public static function create(): static
                        {
                            return new self();
                        }
                    }'
            ],
            'callParentMethodFromTrait' => [
                '<?php
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
                    }'
            ],
            'MixedParamInImplementation' => [
                '<?php
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
                    }'
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'oneParam' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
            'nonNullableSubclassParam' => [
                '<?php
                    class A {
                        public function foo(?string $s): string {
                            return $s ?: "hello";
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
                '<?php
                    function foo(string $bar = null, int $bat): void {}
                    foo();',
                'error_message' => 'TooFewArguments',
            ],
            'clasginByRef' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    class A {
                        public function foo() : void {}
                    }

                    abstract class B extends A {
                        abstract public function foo() : void;
                    }',
                'error_message' => 'MethodSignatureMismatch',
            ],
            'traitReturnTypeMismatch' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    class A
                    {
                        public function __construct(): void
                        {
                        }
                    }',
                'error_message' => 'MethodSignatureMustOmitReturnType',
            ],
            'requireParam' => [
                '<?php
                    interface I {
                        function foo(bool $b = false): void;
                    }

                    class C implements I {
                        public function foo(bool $b): void {}
                    }',
                'error_message' => 'MethodSignatureMismatch - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:27 - Method C::foo has more required',
            ],
            'inheritParamTypes' => [
                '<?php
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
                '<?php
                    interface Foo {
                        public function __construct();
                    }

                    class Bar implements Foo {
                        public function __construct(bool $foo) {}
                    }',
                'error_message' => 'ConstructorSignatureMismatch',
            ],
            'enforceParameterInheritanceWithInheritDoc' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                ['MoreSpecificImplementedParamType'],
            ],
            'preventOneOfUnionMoreSpecific' => [
                '<?php
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
                '<?php
                    class Foo implements \Serializable {
                        /** @param int $serialized */
                        public function unserialize($serialized) {}
                        public function serialize() {}
                    }',
                'error_message' => 'ImplementedParamTypeMismatch',
            ],
            'returnsParentWithNoParent' => [
                '<?php
                    class Foo {
                        public function f(): parent {}
                    }
                ',
                'error_message' => 'InvalidParent',
            ],
            'returnsParentWithNoParentAndInvalidParentSuppressed' => [
                '<?php
                    class Foo {
                        public function f(): parent {
                        }
                    }
                ',
                'error_message' => 'InvalidReturnType',
                ['InvalidParent'],
            ],
            // not sure how to handle it
            'SKIPPED-returnsParentWithNoParentAndInvalidParentSuppressedMismatchingReturn' => [
                '<?php
                    class Foo {
                        public function f(): parent {
                            return false;
                        }
                    }
                ',
                'error_message' => 'InvalidReturnType',
                ['InvalidParent'],
            ],
            'regularMethodMismatchFromParentUse' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    interface I {
                        function foo(): array;
                    }

                    abstract class C implements I {
                        public function foo(): void {}
                    }',
                'error_message' => 'MethodSignatureMismatch',
            ],
            'abstractClassParamMismatch' => [
                '<?php
                    interface I {
                        function foo(int $s): void;
                    }

                    abstract class C implements I {
                        public function foo(string $s): void {}
                    }',
                'error_message' => 'MethodSignatureMismatch',
            ],
            'preventTraitMatchIn73' => [
                '<?php
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
                [],
                false,
                '7.3'
            ],
            'inconsistentConstructorExplicitParentConstructorArgCount' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
            'noMixedTypehintInDescendant' => [
                '<?php
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
                [],
                false,
                '8.0'
            ],
            'noTypehintInNativeDescendant' => [
                '<?php
                    class a implements JsonSerializable {
                        public function jsonSerialize() {
                            return 0;
                        }
                    }
                ',
                'error_message' => 'MethodSignatureMismatch',
                [],
                false,
                '8.1'
            ],
        ];
    }
}
