<?php
namespace Psalm\Tests;

use Psalm\Context;

class MethodSignatureTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;
    use Traits\InvalidCodeAnalysisTestTrait;

    /**
     * @return void
     */
    public function testExtendDocblockParamType()
    {
        if (class_exists('SoapClient') === false) {
            $this->markTestSkipped('Cannot run test, base class "SoapClient" does not exist!');

            return;
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
                        return $_GET["foo"];
                    }
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage MethodSignatureMismatch
     *
     * @return                   void
     */
    public function testExtendDocblockParamTypeWithWrongParam()
    {
        if (class_exists('SoapClient') === false) {
            $this->markTestSkipped('Cannot run test, base class "SoapClient" does not exist!');

            return;
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
     * @return array
     */
    public function providerValidCodeParse()
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
                        return new A();
                      }
                    }

                    class B extends A {
                      public static function foo() {
                        return new B();
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
                        public function testIterable(?iterable $i) : array {
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

                        public function unserialize($serialized) : void
                        {
                            [
                                $this->id,
                            ] = (array) \unserialize((string) $serialized);
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
        ];
    }

    /**
     * @return array
     */
    public function providerInvalidCodeParse()
    {
        return [
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
            'differentArguments' => [
                '<?php
                    class A {
                        public function fooFoo(int $a, bool $b): void {

                        }
                    }

                    class B extends A {
                        public function fooFoo(bool $b, int $a): void {

                        }
                    }',
                'error_message' => 'Argument 1 of B::fooFoo has wrong type \'bool\', expecting \'int\' as defined ' .
                    'by A::fooFoo',
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
                'error_message' => 'Argument 1 of B::foo has wrong type \'string\', expecting \'string|null\' as',
            ],
            'mismatchingCovariantReturn' => [
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
                'error_message' => 'MethodSignatureMismatch',
            ],
            'mismatchingCovariantReturnWithSelf' => [
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
                'error_message' => 'MethodSignatureMismatch',
            ],
            'misplacedRequiredParam' => [
                '<?php
                    function foo($bar = null, $bat): void {}',
                'error_message' => 'MisplacedRequiredParam',
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
            'disallowVoidToNullConversionSignature' => [
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
                'error_message' => 'MethodSignatureMismatch - src/somefile.php:6 - Method C::foo has more required',
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
                'error_message' => 'InvalidArgument'
            ],
        ];
    }
}
