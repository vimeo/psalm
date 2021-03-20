<?php
namespace Psalm\Tests;

use function class_exists;

class ClassTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return void
     */
    public function testExtendsMysqli()
    {
        if (class_exists('mysqli') === false) {
            $this->markTestSkipped('Cannot run test, base class "mysqli" does not exist!');

            return;
        }

        $this->addFile(
            'somefile.php',
            '<?php
                class db extends mysqli {
                    public function close()
                    {
                        return true;
                    }

                    public function prepare(string $sql)
                    {
                        return false;
                    }

                    public function commit(?int $flags = null, ?string $name = null)
                    {
                        return true;
                    }

                    public function real_escape_string(string $string)
                    {
                        return "escaped";
                    }
                }'
        );
    }

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'overrideProtectedAccessLevelToPublic' => [
                '<?php
                    class A {
                        protected function fooFoo(): void {}
                    }

                    class B extends A {
                        public function fooFoo(): void {}
                    }',
            ],
            'reflectedParents' => [
                '<?php
                    $e = rand(0, 10)
                      ? new RuntimeException("m")
                      : null;

                    if ($e instanceof Exception) {
                      echo "good";
                    }',
            ],
            'namespacedAliasedClassCall' => [
                '<?php
                    namespace Aye {
                        class Foo {}
                    }
                    namespace Bee {
                        use Aye as A;

                        new A\Foo();
                    }',
            ],
            'abstractExtendsAbstract' => [
                '<?php
                    abstract class A {
                        /** @return void */
                        abstract public function foo();
                    }

                    abstract class B extends A {
                        /** @return void */
                        public function bar() {
                            $this->foo();
                        }
                    }',
            ],
            'missingParentWithFunction' => [
                '<?php
                    class B extends C {
                        public function fooA() { }
                    }',
                'assertions' => [],
                'error_levels' => [
                    'UndefinedClass',
                    'MissingReturnType',
                ],
            ],
            'subclassWithSimplerArg' => [
                '<?php
                    class A {}
                    class B extends A {}

                    class E1 {
                        /**
                         * @param A|B|null $a
                         */
                        public function __construct($a) {
                        }
                    }

                    class E2 extends E1 {
                        /**
                         * @param A|null $a
                         */
                        public function __construct($a) {
                            parent::__construct($a);
                        }
                    }',
            ],
            'subclassOfInvalidArgumentExceptionWithSimplerArg' => [
                '<?php
                    class A extends InvalidArgumentException {
                        /**
                         * @param string $message
                         * @param int $code
                         * @param Throwable|null $previous_exception
                         */
                        public function __construct($message, $code, $previous_exception) {
                            parent::__construct($message, $code, $previous_exception);
                        }
                    }',
            ],
            'classStringInstantiation' => [
                '<?php
                    class Foo {}
                    class Bar {}
                    $class = mt_rand(0, 1) === 1 ? Foo::class : Bar::class;
                    $object = new $class();',
                'assertions' => [
                    '$object' => 'Bar|Foo',
                ],
            ],
            'instantiateClassAndIsA' => [
                '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    class Foo {
                        public function bar() : void{}
                    }

                    /**
                     * @return string|null
                     */
                    function getFooClass() {
                        return mt_rand(0, 1) === 1 ? Foo::class : null;
                    }

                    $foo_class = getFooClass();

                    if (is_string($foo_class) && is_a($foo_class, Foo::class, true)) {
                        $foo = new $foo_class();
                        $foo->bar();
                    }',
            ],
            'returnStringAfterIsACheckWithClassConst' => [
                '<?php
                    class Foo{}
                    function bar(string $maybeBaz) : string {
                      if (!is_a($maybeBaz, Foo::class, true)) {
                        throw new Exception("not Foo");
                      }
                      return $maybeBaz;
                    }',
            ],
            'returnStringAfterIsACheckWithString' => [
                '<?php
                    class Foo{}
                    function bar(string $maybeBaz) : string {
                      if (!is_a($maybeBaz, "Foo", true)) {
                        throw new Exception("not Foo");
                      }
                      return $maybeBaz;
                    }',
            ],
            'assignAnonymousClassToArray' => [
                '<?php
                    /**
                     * @param array<string, object> $array
                     */
                    function foo(array $array, string $key) : void {
                        foreach ($array as $i => $item) {
                            $array[$key] = new class() {};

                            if ($array[$i] === $array[$key]) {}
                        }
                    }',
            ],
            'getClassSelfClass' => [
                '<?php
                    class C {
                        public function work(object $obj): string {
                            if (get_class($obj) === self::class) {
                                return $obj->baz();
                            }
                            return "";
                        }

                        public function baz(): string {
                            return "baz";
                        }
                    }',
            ],
            'staticClassComparison' => [
                '<?php
                    class C {
                        public function foo1(): string {
                            if (static::class === D::class) {
                                return $this->baz();
                            }
                            return "";
                        }

                        public static function foo2(): string {
                            if (static::class === D::class) {
                                return static::bat();
                            }
                            return "";
                        }
                    }

                    class D extends C {
                        public function baz(): string {
                            return "baz";
                        }

                        public static function bat(): string {
                            return "baz";
                        }
                    }',
            ],
            'isAStaticClass' => [
                '<?php
                    class C {
                        public function foo1(): string {
                            if (is_a(static::class, D::class, true)) {
                                return $this->baz();
                            }
                            return "";
                        }

                        public static function foo2(): string {
                            if (is_a(static::class, D::class, true)) {
                                return static::bat();
                            }
                            return "";
                        }
                    }

                    class D extends C {
                        public function baz(): string {
                            return "baz";
                        }

                        public static function bat(): string {
                            return "baz";
                        }
                    }',
            ],
            'typedMagicCall' => [
                '<?php
                    class B {
                        public function __call(string $methodName, array $args) : string {
                            return __METHOD__;
                        }
                    }
                    class A {
                        public function __call(string $methodName, array $args) : B {
                            return new B;
                        }
                    }
                    $a = (new A)->zugzug();
                    $b = (new A)->bar()->baz();',
                'assertions' => [
                    '$a' => 'B',
                    '$b' => 'string',
                ],
            ],
            'abstractCallToInterfaceMethod' => [
                '<?php
                    interface I {
                        public function fooBar(): array;
                    }

                    abstract class A implements I
                    {
                        public function g(): array {
                            return $this->fooBar();
                        }
                    }',
            ],
            'noCrashWhenIgnoringUndefinedClass' => [
                '<?php
                    class A extends B {
                        public function foo() {
                            parent::bar();
                        }
                    }',
                'assertions' => [],
                'error_levels' => [
                    'UndefinedClass',
                ],
            ],
            'noCrashWhenIgnoringUndefinedParam' => [
                '<?php
                    function bar(iterable $_i) : void {}
                    function foo(C $c) : void {
                        bar($c);
                    }',
                'assertions' => [],
                'error_levels' => [
                    'UndefinedClass',
                    'InvalidArgument',
                ],
            ],
            'noCrashWhenIgnoringUndefinedReturnIterableArg' => [
                '<?php
                    function bar(iterable $_i) : void {}
                    function foo() : D {
                        return new D();
                    }
                    bar(foo());',
                'assertions' => [],
                'error_levels' => [
                    'UndefinedClass',
                    'MixedInferredReturnType',
                    'InvalidArgument',
                ],
            ],
            'noCrashWhenIgnoringUndefinedReturnClassArg' => [
                '<?php
                    class Exists {}
                    function bar(Exists $_i) : void {}
                    function foo() : D {
                        return new D();
                    }
                    bar(foo());',
                'assertions' => [],
                'error_levels' => [
                    'UndefinedClass',
                    'MixedInferredReturnType',
                    'InvalidArgument',
                ],
            ],
            'allowAbstractInstantiationOnPossibleChild' => [
                '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    abstract class A {}

                    function foo(string $a_class) : void {
                        if (is_a($a_class, A::class, true)) {
                            new $a_class();
                        }
                    }',
            ],
            'interfaceExistsCreatesClassString' => [
                '<?php
                    function funB(string $className) : ?ReflectionClass {
                        if (class_exists($className)) {
                            return new ReflectionClass($className);
                        }

                        if (interface_exists($className)) {
                            return new ReflectionClass($className);
                        }

                        return null;
                    }',
            ],
            'allowClassExistsAndInterfaceExists' => [
                '<?php
                    function foo(string $s) : void {
                        if (class_exists($s) || interface_exists($s)) {}
                    }',
            ],
            'classExistsWithFalseArg' => [
                '<?php
                    /**
                     * @param class-string $class
                     * @return string
                     */
                    function autoload(string $class) : string {
                        if (class_exists($class, false)) {
                            return $class;
                        }

                        return $class;
                    }',
            ],
            'allowNegatingClassExistsWithoutAutloading' => [
                '<?php
                    function specifyString(string $className): void{
                        if (!class_exists($className, false)) {
                            return;
                        }
                        new ReflectionClass($className);
                    }'
            ],
            'classExistsWithFalseArgInside' => [
                '<?php
                    function foo(string $s) : void {
                        if (class_exists($s, false)) {
                            /** @psalm-suppress MixedMethodCall */
                            new $s();
                        }
                    }',
            ],
            'classAliasOnNonexistantClass' => [
                '<?php
                    if (!class_exists(\PHPUnit\Framework\TestCase::class)) {
                        /** @psalm-suppress UndefinedClass */
                        class_alias(\PHPUnit_Framework_TestCase::class, \PHPUnit\Framework\TestCase::class);
                    }

                    class T extends \PHPUnit\Framework\TestCase {

                    }',
                [],
                ['PropertyNotSetInConstructor'],
            ],
            'classAliasNoException' => [
                '<?php
                    class_alias("Bar\F1", "Bar\F2");

                    namespace Bar {
                        class F1 {
                            public static function baz() : void {}
                        }
                    }

                    namespace {
                        Bar\F2::baz();
                    }',
            ],
            'classAliasEcho' => [
                '<?php
                    class A { }
                    class_alias("A", "A_A");

                    echo A_A::class;'
            ],
            'classAliasTrait' => [
                '<?php
                    trait FeatureV1 {}
                    class_alias(FeatureV1::class, Feature::class);
                    class App { use Feature; }
                '
            ],
            'classAliasParent' => [
                '<?php
                    class NewA {}
                    class_alias(NewA::class, OldA::class);
                    function action(NewA $_m): void {}

                    class OldAChild extends OldA {}
                    action(new OldA());
                    action(new OldAChild());'
            ],
            'classAliasStaticProperty' => [
                '<?php
                    class A {
                        /** @var int */
                        public static $prop = 1;
                    }
                    class_alias(A::class, B::class);
                    B::$prop = 123;'
            ],
            'resourceAndNumericSoftlyReserved' => [
                '<?php
                    namespace {
                        class Numeric {}
                        class Never {}
                    }

                    namespace Foo {
                        class Resource {}
                        class Numeric {}
                        class Never {}
                    }

                    namespace Bar {
                        use \Foo\Resource;
                        use \Foo\Numeric;
                        use \Foo\Never;

                        new \Foo\Resource();
                        new \Foo\Numeric();
                        new \Foo\Never();

                        new Resource();
                        new Numeric();

                        /**
                         * @param  Resource $r
                         * @param  Numeric  $n
                         * @param  Never $nev
                         * @return void
                         */
                        function foo(Resource $r, Numeric $n, Never $nev) : void {}
                    }'
            ],
            'inheritInterfaceFromParent' => [
                '<?php
                    class A {}
                    class AChild extends A {}

                    interface IParent {
                        public function get(): A;
                    }

                    interface IChild extends IParent {
                        /**
                         * @psalm-return AChild
                         */
                        public function get(): A;
                    }

                    class Concrete implements IChild {
                        public function get(): A {
                            return new AChild;
                        }
                    }',
            ],
            'noErrorsAfterClassExists' => [
                '<?php
                    if (class_exists(A::class)) {
                        if (method_exists(A::class, "method")) {
                            /** @psalm-suppress MixedArgument */
                            echo A::method();
                        }

                        echo A::class;
                        /** @psalm-suppress MixedArgument */
                        echo A::SOME_CONST;
                    }'
            ],
            'noCrashOnClassExists' => [
                '<?php
                    if (!class_exists(ReflectionGenerator::class)) {
                        class ReflectionGenerator {
                            private $prop;
                        }
                    }',
            ],
            'extendException' => [
                '<?php
                    class ME extends Exception {
                        protected $message = "hello";
                    }',
            ],
            'allowFinalReturnerForStatic' => [
                '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    class A {
                        /** @return static */
                        public static function getInstance() {
                            return new static();
                        }
                    }

                    final class AChild extends A {
                        public static function getInstance() {
                            return new AChild();
                        }
                    }',
            ],
            'intersectWithStatic' => [
                '<?php
                    interface M1 {
                        /** @return M2&static */
                        function mock();
                    }

                    interface M2 {}

                    class A {}

                    /** @return A&M1 */
                    function intersect(A $a) {
                        assert($a instanceof M1);

                        if (rand(0, 1)) {
                            return $a;
                        }

                        $b = $a->mock();

                        return $b;
                    }'
            ],
            'allowTraversableImplementationAlongWithIteratorAggregate' => [
                '<?php
                    final class C implements Traversable, IteratorAggregate {
                        public function getIterator() {
                            yield 1;
                        }
                    }
                ',
            ],
            'allowTraversableImplementationAlongWithIterator' => [
                '<?php
                    final class C implements Traversable, Iterator {
                        public function current() { return 1; }
                        public function key() { return 1; }
                        public function next() { }
                        public function rewind() { }
                        public function valid() { return false; }
                    }
                ',
            ],
            'allowTraversableImplementationOnAbstractClass' => [
                '<?php
                    abstract class C implements Traversable {}
                ',
            ],
            'allowIndirectTraversableImplementationOnAbstractClass' => [
                '<?php
                    interface I extends Traversable {}
                    abstract class C implements I {}
                ',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'undefinedClass' => [
                '<?php
                    (new Foo());',
                'error_message' => 'UndefinedClass',
            ],
            'wrongCaseClass' => [
                '<?php
                    class Foo {}
                    (new foo());',
                'error_message' => 'InvalidClass',
            ],
            'wrongCaseClassWithCall' => [
                '<?php
                    class A {}
                    needsA(new A);
                    function needsA(a $x): void {}',
                'error_message' => 'InvalidClass',
            ],
            'invalidThisFetch' => [
                '<?php
                    echo $this;',
                'error_message' => 'InvalidScope',
            ],
            'invalidThisArgument' => [
                '<?php
                    $this = "hello";',
                'error_message' => 'InvalidScope',
            ],
            'undefinedConstant' => [
                '<?php
                    echo HELLO;',
                'error_message' => 'UndefinedConstant',
            ],
            'undefinedClassConstant' => [
                '<?php
                    class A {}
                    echo A::HELLO;',
                'error_message' => 'UndefinedConstant',
            ],
            'overridePublicAccessLevelToPrivate' => [
                '<?php
                    class A {
                        public function fooFoo(): void {}
                    }

                    class B extends A {
                        private function fooFoo(): void {}
                    }',
                'error_message' => 'OverriddenMethodAccess',
            ],
            'overridePublicAccessLevelToProtected' => [
                '<?php
                    class A {
                        public function fooFoo(): void {}
                    }

                    class B extends A {
                        protected function fooFoo(): void {}
                    }',
                'error_message' => 'OverriddenMethodAccess',
            ],
            'overrideProtectedAccessLevelToPrivate' => [
                '<?php
                    class A {
                        protected function fooFoo(): void {}
                    }

                    class B extends A {
                        private function fooFoo(): void {}
                    }',
                'error_message' => 'OverriddenMethodAccess',
            ],
            'overridePublicPropertyAccessLevelToPrivate' => [
                '<?php
                    class A {
                        /** @var string|null */
                        public $foo;
                    }

                    class B extends A {
                        /** @var string|null */
                        private $foo;
                    }',
                'error_message' => 'OverriddenPropertyAccess',
            ],
            'overridePublicPropertyAccessLevelToProtected' => [
                '<?php
                    class A {
                        /** @var string|null */
                        public $foo;
                    }

                    class B extends A {
                        /** @var string|null */
                        protected $foo;
                    }',
                'error_message' => 'OverriddenPropertyAccess',
            ],
            'overrideProtectedPropertyAccessLevelToPrivate' => [
                '<?php
                    class A {
                        /** @var string|null */
                        protected $foo;
                    }

                    class B extends A {
                        /** @var string|null */
                        private $foo;
                    }',
                'error_message' => 'OverriddenPropertyAccess',
            ],
            'classRedefinition' => [
                '<?php
                    class Foo {}
                    class Foo {}',
                'error_message' => 'DuplicateClass',
            ],
            'classRedefinitionInNamespace' => [
                '<?php
                    namespace Aye {
                        class Foo {}
                        class Foo {}
                    }',
                'error_message' => 'DuplicateClass',
            ],
            'classRedefinitionInSeparateNamespace' => [
                '<?php
                    namespace Aye {
                        class Foo {}
                    }
                    namespace Aye {
                        class Foo {}
                    }',
                'error_message' => 'DuplicateClass',
            ],
            'abstractClassInstantiation' => [
                '<?php
                    abstract class A {}
                    new A();',
                'error_message' => 'AbstractInstantiation',
            ],
            'abstractClassMethod' => [
                '<?php
                    abstract class A {
                        abstract public function foo() : void;
                    }

                    class B extends A { }',
                'error_message' => 'UnimplementedAbstractMethod',
            ],
            'abstractReflectedClassMethod' => [
                '<?php
                    class DedupeIterator extends FilterIterator {
                        public function __construct(Iterator $i) {
                            parent::__construct($i);
                        }
                    }',
                'error_message' => 'UnimplementedAbstractMethod',
            ],
            'missingParent' => [
                '<?php
                    class A extends B { }',
                'error_message' => 'UndefinedClass',
            ],
            'lessSpecificReturnStatement' => [
                '<?php
                    class A {}
                    class B extends A {}

                    function foo(A $a): B {
                        return $a;
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'circularReference' => [
                '<?php
                    class A extends A {}',
                'error_message' => 'CircularReference',
            ],
            'preventAbstractInstantiationDefiniteClasss' => [
                '<?php
                    abstract class A {}

                    function foo(string $a_class) : void {
                        if ($a_class === A::class) {
                            new $a_class();
                        }
                    }',
                'error_message' => 'AbstractInstantiation',
            ],
            'preventExtendingInterface' => [
                '<?php
                    interface Foo {}

                    class Bar extends Foo {}',
                'error_message' => 'UndefinedClass',
            ],
            'preventImplementingClass' => [
                '<?php
                    class Foo {}

                    class Bar implements Foo {}',
                'error_message' => 'UndefinedInterface',
            ],
            'classAliasAlreadyDefinedClass' => [
                '<?php
                    class A {}

                    class B {}

                    if (false) {
                        class_alias(A::class, B::class);
                    }

                    function foo(A $a, B $b) : void {
                        if ($a === $b) {}
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'cannotOverrideFinalType' => [
                '<?php
                    class P {
                        public final function f() : void {}
                    }

                    class C extends P {
                        public function f() : void {}
                    }',
                'error_message' => 'MethodSignatureMismatch',
            ],
            'preventFinalOverriding' => [
                '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    class A {
                        /** @return static */
                        public static function getInstance() {
                            return new static();
                        }
                    }

                    class AChild extends A {
                        public static function getInstance() {
                            return new AChild();
                        }
                    }

                    class AGrandChild extends AChild {
                        public function foo() : void {}
                    }

                    AGrandChild::getInstance()->foo();',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'preventTraversableImplementation' => [
                '<?php
                    final class C implements Traversable {}
                ',
                'error_message' => 'InvalidTraversableImplementation',
            ],
            'preventIndirectTraversableImplementation' => [
                '<?php
                    interface I extends Traversable {}
                    final class C implements I {}
                ',
                'error_message' => 'InvalidTraversableImplementation',
            ],
        ];
    }
}
