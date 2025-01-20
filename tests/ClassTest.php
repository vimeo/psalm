<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class ClassTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function testExtendsMysqli(): void
    {
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
                }',
        );
    }

    public function providerValidCodeParse(): iterable
    {
        return [
            'overrideProtectedAccessLevelToPublic' => [
                'code' => '<?php
                    class A {
                        protected function fooFoo(): void {}
                    }

                    class B extends A {
                        public function fooFoo(): void {}
                    }',
            ],
            'reflectedParents' => [
                'code' => '<?php
                    $e = rand(0, 10)
                      ? new RuntimeException("m")
                      : null;

                    if ($e instanceof Exception) {
                      echo "good";
                    }',
            ],
            'namespacedAliasedClassCall' => [
                'code' => '<?php
                    namespace Aye {
                        class Foo {}
                    }
                    namespace Bee {
                        use Aye as A;

                        new A\Foo();
                    }',
            ],
            'abstractExtendsAbstract' => [
                'code' => '<?php
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
                'code' => '<?php
                    class B extends C {
                        public function fooA() { }
                    }',
                'assertions' => [],
                'ignored_issues' => [
                    'UndefinedClass',
                    'MissingReturnType',
                ],
            ],
            'subclassWithSimplerArg' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    class Foo {}
                    class Bar {}
                    $class = mt_rand(0, 1) === 1 ? Foo::class : Bar::class;
                    $object = new $class();',
                'assertions' => [
                    '$object' => 'Bar|Foo',
                ],
            ],
            'instantiateClassAndIsA' => [
                'code' => '<?php
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
                'code' => '<?php
                    class Foo{}
                    function bar(string $maybeBaz) : string {
                      if (!is_a($maybeBaz, Foo::class, true)) {
                        throw new Exception("not Foo");
                      }
                      return $maybeBaz;
                    }',
            ],
            'returnStringAfterIsACheckWithString' => [
                'code' => '<?php
                    class Foo{}
                    /** @param class-string $maybeBaz */
                    function bar(string $maybeBaz) : string {
                      if (!is_a($maybeBaz, Foo::class, true)) {
                        throw new Exception("not Foo");
                      }
                      return $maybeBaz;
                    }',
            ],
            'assignAnonymousClassToArray' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    class A extends B {
                        public function foo() {
                            parent::bar();
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [
                    'UndefinedClass',
                ],
            ],
            'noCrashWhenIgnoringUndefinedParam' => [
                'code' => '<?php
                    function bar(iterable $_i) : void {}
                    function foo(C $c) : void {
                        bar($c);
                    }',
                'assertions' => [],
                'ignored_issues' => [
                    'UndefinedClass',
                    'InvalidArgument',
                ],
            ],
            'noCrashWhenIgnoringUndefinedReturnIterableArg' => [
                'code' => '<?php
                    function bar(iterable $_i) : void {}
                    function foo() : D {
                        return new D();
                    }
                    bar(foo());',
                'assertions' => [],
                'ignored_issues' => [
                    'UndefinedClass',
                    'MixedInferredReturnType',
                    'InvalidArgument',
                ],
            ],
            'noCrashWhenIgnoringUndefinedReturnClassArg' => [
                'code' => '<?php
                    class Exists {}
                    function bar(Exists $_i) : void {}
                    function foo() : D {
                        return new D();
                    }
                    bar(foo());',
                'assertions' => [],
                'ignored_issues' => [
                    'UndefinedClass',
                    'MixedInferredReturnType',
                    'InvalidArgument',
                ],
            ],
            'allowAbstractInstantiationOnPossibleChild' => [
                'code' => '<?php
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
            'markInferredMutationFreeDuringPropertyTypeInferenceAsActuallyInferred' => [
                'code' => '<?php
                    class A {}

                    /**
                     * @psalm-consistent-constructor
                     */
                    abstract class AbstractClass
                    {
                        protected $renderer;

                        public function __construct(A $r)
                        {
                            $this->renderer = $r;
                        }
                    }

                    class ConcreteClass extends AbstractClass
                    {
                        public function __construct(A $r)
                        {
                            parent::__construct($r);
                        }
                    }
                ',
            ],
            'interfaceExistsCreatesClassString' => [
                'code' => '<?php
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
                'code' => '<?php
                    function foo(string $s) : void {
                        if (class_exists($s) || interface_exists($s)) {}
                    }',
            ],
            'classExistsWithFalseArgRefinedAsString' => [
                'code' => '<?php
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
                'code' => '<?php
                    function specifyString(string $className): void{
                        if (!class_exists($className, false)) {
                            return;
                        }
                        new ReflectionClass($className);
                    }',
            ],
            'classExistsWithFalseArgInside' => [
                'code' => '<?php
                    function foo(string $s) : void {
                        if (class_exists($s, false)) {
                            /** @psalm-suppress MixedMethodCall */
                            new $s();
                        }
                    }',
            ],
            'classAliasOnNonexistentClass' => [
                'code' => '<?php
                    if (!class_exists(\PHPUnit\Framework\TestCase::class)) {
                        /** @psalm-suppress UndefinedClass */
                        class_alias(\PHPUnit_Framework_TestCase::class, \PHPUnit\Framework\TestCase::class);
                    }

                    class T extends \PHPUnit\Framework\TestCase {

                    }',
                'assertions' => [],
                'ignored_issues' => ['PropertyNotSetInConstructor'],
            ],
            'classAliasNoException' => [
                'code' => '<?php
                    namespace {
                        class_alias("Bar\F1", "Bar\F2");
                    }

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
                'code' => '<?php
                    class A { }
                    class_alias("A", "A_A");

                    echo A_A::class;',
            ],
            'classAliasTrait' => [
                'code' => '<?php
                    trait FeatureV1 {}
                    class_alias(FeatureV1::class, Feature::class);
                    class App { use Feature; }
                ',
            ],
            'classAliasParent' => [
                'code' => '<?php
                    class NewA {}
                    class_alias(NewA::class, OldA::class);
                    function action(NewA $_m): void {}

                    class OldAChild extends OldA {}
                    action(new OldA());
                    action(new OldAChild());',
            ],
            'classAliasStaticProperty' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public static $prop = 1;
                    }
                    class_alias(A::class, B::class);
                    B::$prop = 123;',
            ],
            'resourceAndNumericSoftlyReserved' => [
                'code' => '<?php
                    namespace {
                        class Numeric {}
                    }

                    namespace Foo {
                        class Resource {}
                        class Numeric {}
                    }

                    namespace Bar {
                        use \Foo\Resource;
                        use \Foo\Numeric;

                        new \Foo\Resource();
                        new \Foo\Numeric();

                        new Resource();
                        new Numeric();

                        /**
                         * @param  Resource $r
                         * @param  Numeric  $n
                         * @return void
                         */
                        function foo(Resource $r, Numeric $n) : void {}
                    }',
            ],
            'inheritInterfaceFromParent' => [
                'code' => '<?php
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
                'code' => '<?php
                    if (class_exists(A::class)) {
                        if (method_exists(A::class, "method")) {
                            /** @psalm-suppress MixedArgument */
                            echo A::method();
                        }

                        echo A::class;
                        /** @psalm-suppress MixedArgument */
                        echo A::SOME_CONST;
                    }',
            ],
            'noCrashOnClassExists' => [
                'code' => '<?php
                    if (!class_exists(ReflectionGenerator::class)) {
                        class ReflectionGenerator {
                            private $prop;
                        }
                    }',
            ],
            'instanceofWithPhantomClass' => [
                'code' => '<?php
                    if (class_exists(NS\UnknonwClass::class)) {
                        null instanceof NS\UnknonwClass;
                    }
                ',
            ],
            'extendException' => [
                'code' => '<?php
                    class ME extends Exception {
                        protected $message = "hello";
                    }',
            ],
            'allowFinalReturnerForStatic' => [
                'code' => '<?php
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
                'code' => '<?php
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
                    }',
            ],
            'preventDoubleStaticResolution1' => [
                'code' => '<?php

                    /**
                     * @template TTKey of array-key
                     * @template TTValue
                     *
                     * @extends ArrayObject<TTKey, TTValue>
                     */
                    class iter extends ArrayObject {
                        /**
                         * @return self<TTKey, TTValue>
                         */
                        public function stabilize(): self {
                            return $this;
                        }
                    }

                    class a {
                        /**
                         * @return iter<int, static>
                         */
                        public function ret(): iter {
                            return new iter([$this]);
                        }
                    }
                    class b extends a {
                    }

                    $a = new b;
                    $a = $a->ret();
                    $a = $a->stabilize();',
                'assertions' => [
                    '$a===' => 'iter<int, b&static>',
                ],
            ],
            'preventDoubleStaticResolution2' => [
                'code' => '<?php
                    /**
                     * @template TTKey of array-key
                     * @template TTValue
                     *
                     * @extends ArrayObject<TTKey, TTValue>
                     */
                    class iter extends ArrayObject {
                        /**
                         * @return self<TTKey, TTValue>
                         */
                        public function stabilize(): self {
                            return $this;
                        }
                    }

                    interface a {
                        /**
                         * @return iter<int, static>
                         */
                        public function ret(): iter;
                    }
                    class b implements a {
                        public function ret(): iter {
                            return new iter([$this]);
                        }
                    }

                    /** @var a */
                    $a = new b;
                    $a = $a->ret();
                    $a = $a->stabilize();',
                'assertions' => [
                    '$a===' => 'iter<int, a&static>',
                ],
            ],
            'preventDoubleStaticResolution3' => [
                'code' => '<?php
                    /**
                     * @template TTKey of array-key
                     * @template TTValue
                     *
                     * @extends ArrayObject<TTKey, TTValue>
                     */
                    class iter extends ArrayObject {
                        /**
                         * @return self<TTKey, TTValue>
                         */
                        public function stabilize(): self {
                            return $this;
                        }
                    }

                    interface a {
                        /**
                         * @return iter<int, a&static>
                         */
                        public function ret(): iter;
                    }
                    class b implements a {
                        public function ret(): iter {
                            return new iter([$this]);
                        }
                    }

                    /** @var a */
                    $a = new b;
                    $a = $a->ret();
                    $a = $a->stabilize();',
                'assertions' => [
                    '$a===' => 'iter<int, a&static>',
                ],
            ],
            'allowTraversableImplementationAlongWithIteratorAggregate' => [
                'code' => '<?php
                    /**
                     * @implements Traversable<int, 1>
                     * @implements IteratorAggregate<int, 1>
                     */
                    final class C implements Traversable, IteratorAggregate {
                        public function getIterator() {
                            yield 1;
                        }
                    }
                ',
            ],
            'allowTraversableImplementationAlongWithIterator' => [
                'code' => '<?php
                    /**
                     * @implements Traversable<1, 1>
                     * @implements Iterator<1, 1>
                     */
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
                'code' => '<?php
                    /**
                     * @template TKey
                     * @template TValue
                     *
                     * @implements Traversable<TKey, TValue>
                     */
                    abstract class C implements Traversable {}
                ',
            ],
            'allowIndirectTraversableImplementationOnAbstractClass' => [
                'code' => '<?php
                    /**
                     * @extends Traversable<int, int>
                     */
                    interface I extends Traversable {}
                    abstract class C implements I {}
                ',
            ],
            'newOnNamedObject' => [
                'code' => '<?php
                    $o = new stdClass;
                    $o2 = new $o;
                ',
                'assertions' => [
                    '$o2===' => 'stdClass',
                ],
            ],
            'newOnObjectOfAnonymousClass' => [
                'code' => '<?php
                    function f(): object {
                        $o = new class {};
                        return new $o;
                    }
                ',
            ],
            'newOnObjectOfAnonymousExtendingNamed' => [
                'code' => '<?php
                    function f(): Exception {
                        $o = new class extends Exception {};
                        return new $o;
                    }
                ',
            ],
            'newOnObjectOfAnonymousClassImplementingNamed' => [
                'code' => '<?php
                    interface I {}
                    function f(): I {
                        $o = new class implements I {};
                        return new $o;
                    }
                ',
            ],
            'throwAnonymousObjects' => [
                'code' => '<?php
                    throw new class extends Exception {};
                ',
            ],
            'throwTheResultOfNewOnAnAnonymousClass' => [
                'code' => '<?php
                    declare(strict_types=1);

                    $test = new class extends \Exception { };
                    throw new $test();
                ',
            ],
            'privateFinalConstructorsAreAllowed' => [
                'code' => <<<'PHP'
                    <?php
                    class Foo {
                        private final function __construct() {}
                    }
                    PHP,
            ],
            'singleInheritorIsAllowed' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @psalm-inheritors FooClass
                     */
                    class BaseClass {}
                    class FooClass extends BaseClass {}
                    PHP,
            ],
            'unionInheritorIsAllowed' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @psalm-inheritors FooClass|BarClass
                     */
                    class BaseClass {}
                    class FooClass extends BaseClass {}
                    class BarClass extends FooClass {}
                    PHP,
            ],
            'multiInheritorIsAllowed' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @psalm-inheritors FooClass|arClass
                     */
                    class BaseClass {}
                    class FooClass extends BaseClass {}
                    class BarClass extends FooClass {}
                    PHP,
            ],
            'skippedInheritorIsAllowed' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @psalm-inheritors FooClass|BarClass
                     */
                    class BaseClass {}
                    class FooClass extends BaseClass {}
                    class BarClass extends FooClass {}
                    PHP,
            ],
            'CompositeInheritorIsAllowed' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @psalm-inheritors BarClass&FooInterface
                     */
                    class BaseClass {}
                    interface FooInterface {}
                    class BarClass extends BaseClass implements FooInterface {}
                    PHP,
            ],
            'InterfaceInheritorIsAllowed' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @psalm-inheritors FooClass|BarClass
                     */
                    interface BaseInterface {}
                    class FooClass implements BaseInterface {}
                    class BarClass implements BaseInterface {}
                    PHP,
            ],
            'MultiInterfaceInheritorIsAllowed' => [
                    'code' => <<<'PHP'
                    <?php
                    /**
                     * @psalm-inheritors FooClass|BarClass
                     */
                    interface InterfaceA {}
                    /**
                     * @psalm-inheritors FooClass|BarClass
                     */
                    interface InterfaceB {}
                    class FooClass implements InterfaceA, InterfaceB {}
                    PHP,
                ],
            'InterfaceOfInterfaceInheritorIsAllowed' => [
                        'code' => <<<'PHP'
                    <?php
                    /**
                     * @psalm-inheritors InterfaceB
                     */
                    interface InterfaceA {}
                    interface InterfaceB extends InterfaceA {}
                    PHP,
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'undefinedClass' => [
                'code' => '<?php
                    (new Foo());',
                'error_message' => 'UndefinedClass',
            ],
            'wrongCaseClass' => [
                'code' => '<?php
                    class Foo {}
                    (new foo());',
                'error_message' => 'InvalidClass',
            ],
            'wrongCaseClassWithCall' => [
                'code' => '<?php
                    class A {}
                    needsA(new A);
                    function needsA(a $x): void {}',
                'error_message' => 'InvalidClass',
            ],
            'invalidThisFetch' => [
                'code' => '<?php
                    echo $this;',
                'error_message' => 'InvalidScope',
            ],
            'invalidThisArgument' => [
                'code' => '<?php
                    $this = "hello";',
                'error_message' => 'InvalidScope',
            ],
            'undefinedConstant' => [
                'code' => '<?php
                    echo HELLO;',
                'error_message' => 'UndefinedConstant',
            ],
            'undefinedClassConstant' => [
                'code' => '<?php
                    class A {}
                    echo A::HELLO;',
                'error_message' => 'UndefinedConstant',
            ],
            'consistentNamesConstructor' => [
                'code' => '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    class A
                    {
                        public function __construct(
                            string $name,
                            string $email,
                        ) {}
                    }

                    class B extends A
                    {
                        public function __construct(
                            string $names,
                            string $email,
                        ) {}
                    }
                    ',
                'error_message' => 'ParamNameMismatch',
            ],
            'overridePublicAccessLevelToPrivate' => [
                'code' => '<?php
                    class A {
                        public function fooFoo(): void {}
                    }

                    class B extends A {
                        private function fooFoo(): void {}
                    }',
                'error_message' => 'OverriddenMethodAccess',
            ],
            'overridePublicAccessLevelToProtected' => [
                'code' => '<?php
                    class A {
                        public function fooFoo(): void {}
                    }

                    class B extends A {
                        protected function fooFoo(): void {}
                    }',
                'error_message' => 'OverriddenMethodAccess',
            ],
            'overrideProtectedAccessLevelToPrivate' => [
                'code' => '<?php
                    class A {
                        protected function fooFoo(): void {}
                    }

                    class B extends A {
                        private function fooFoo(): void {}
                    }',
                'error_message' => 'OverriddenMethodAccess',
            ],
            'overridePublicPropertyAccessLevelToPrivate' => [
                'code' => '<?php
                    class A {
                        /** @var string|null */
                        public $foo;
                    }

                    class B extends A {
                        /** @var string|null */
                        private $foo;
                    }',
                'error_message' => 'OverriddenPropertyAccess - src'  . DIRECTORY_SEPARATOR . 'somefile.php:9:33 - Property B::$foo has different access level than A::$foo',
            ],
            'overridePublicPropertyAccessLevelToProtected' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    class Foo {}
                    class Foo {}',
                'error_message' => 'DuplicateClass',
            ],
            'classRedefinitionInNamespace' => [
                'code' => '<?php
                    namespace Aye {
                        class Foo {}
                        class Foo {}
                    }',
                'error_message' => 'DuplicateClass',
            ],
            'classRedefinitionInSeparateNamespace' => [
                'code' => '<?php
                    namespace Aye {
                        class Foo {}
                    }
                    namespace Aye {
                        class Foo {}
                    }',
                'error_message' => 'DuplicateClass',
            ],
            'abstractClassInstantiation' => [
                'code' => '<?php
                    abstract class A {}
                    new A();',
                'error_message' => 'AbstractInstantiation',
            ],
            'abstractClassMethod' => [
                'code' => '<?php
                    abstract class A {
                        abstract public function foo() : void;
                    }

                    class B extends A { }',
                'error_message' => 'UnimplementedAbstractMethod',
            ],
            'abstractReflectedClassMethod' => [
                'code' => '<?php
                    /**
                     * @template TKey
                     * @template TValue
                     * @extends FilterIterator<TKey, TValue, Iterator<TKey, TValue>>
                     */
                    class DedupeIterator extends FilterIterator {
                        /**
                         * @param Iterator<TKey, TValue> $i
                         */
                        public function __construct(Iterator $i) {
                            parent::__construct($i);
                        }
                    }',
                'error_message' => 'UnimplementedAbstractMethod',
            ],
            'missingParent' => [
                'code' => '<?php
                    class A extends B { }',
                'error_message' => 'UndefinedClass',
            ],
            'lessSpecificReturnStatement' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}

                    function foo(A $a): B {
                        return $a;
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'circularReference' => [
                'code' => '<?php
                    class A extends A {}',
                'error_message' => 'CircularReference',
            ],
            'preventAbstractInstantiationDefiniteClass' => [
                'code' => '<?php
                    abstract class A {}

                    function foo(string $a_class) : void {
                        if ($a_class === A::class) {
                            new $a_class();
                        }
                    }',
                'error_message' => 'AbstractInstantiation',
            ],
            'preventExtendingInterface' => [
                'code' => '<?php
                    interface Foo {}

                    class Bar extends Foo {}',
                'error_message' => 'UndefinedClass',
            ],
            'preventImplementingClass' => [
                'code' => '<?php
                    class Foo {}

                    class Bar implements Foo {}',
                'error_message' => 'UndefinedInterface',
            ],
            'classAliasAlreadyDefinedClass' => [
                'code' => '<?php
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
                'code' => '<?php
                    class P {
                        public final function f() : void {}
                    }

                    class C extends P {
                        public function f() : void {}
                    }',
                'error_message' => 'MethodSignatureMismatch',
            ],
            'preventFinalOverriding' => [
                'code' => '<?php
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
                'code' => '<?php
                    /**
                     * @implements Traversable<int, int>
                     */
                    final class C implements Traversable {}
                ',
                'error_message' => 'InvalidTraversableImplementation',
            ],
            'preventIndirectTraversableImplementation' => [
                'code' => '<?php
                    /**
                     * @extends Traversable<int, int>
                     */
                    interface I extends Traversable {}
                    final class C implements I {}
                ',
                'error_message' => 'InvalidTraversableImplementation',
            ],
            'detectMissingTemplateExtends' => [
                'code' => '<?php
                    /** @template T */
                    abstract class A {}
                    final class B extends A {}
                ',
                'error_message' => 'MissingTemplateParam',
            ],
            'detectMissingTemplateImplements' => [
                'code' => '<?php
                    /** @template T */
                    interface A {}
                    final class B implements A {}
                ',
                'error_message' => 'MissingTemplateParam',
            ],
            'detectMissingTemplateUse' => [
                'code' => '<?php
                    /** @template T */
                    trait A {}
                    final class B {
                        use A;
                    }
                ',
                'error_message' => 'MissingTemplateParam',
            ],

            'detectMissingTemplateExtendsNative' => [
                'code' => '<?php
                    final class C extends ArrayObject {}
                ',
                'error_message' => 'MissingTemplateParam',
            ],

            'detectMissingTemplateImplementsNative' => [
                'code' => '<?php
                    final class C implements Iterator {
                        public function current(): mixed {
                            return 0;
                        }
                        public function key(): mixed {
                            return 0;
                        }
                        public function next(): void {
                        }
                        public function rewind(): void {
                        }
                        public function valid(): bool {
                            return false;
                        }
                    }
                ',
                'error_message' => 'MissingTemplateParam',
            ],
            'cannotNameClassConstantClass' => [
                'code' => '<?php
                class Foo
                {
                    /** @var class-string<Bar> */
                    protected const CLASS = Bar::class;
                }

                class Bar {}
                ',
                'error_message' => 'ReservedWord',
            ],
            'newOnObject' => [
                'code' => '<?php
                    function f(object $o): object
                    {
                        return new $o;
                    }
                ',
                'error_message' => 'MixedMethodCall',
            ],
            'forbiddenThrowableImplementation' => [
                'code' => '<?php
                    class C implements Throwable {}
                ',
                'error_message' => 'InvalidInterfaceImplementation',
                'ignored_issues' => [],
                'php_version' => '7.0',
            ],
            'directConstructorCall' => [
                'code' => '<?php
                    class A {
                        public function __construct() {}
                    }
                    $a = new A;
                    $a->__construct();
                ',
                'error_message' => 'DirectConstructorCall',
            ],
            'directConstructorCallOnThis' => [
                'code' => '<?php
                    class A {
                        public function __construct() {}
                        public function f(): void { $this->__construct(); }
                    }
                    $a = new A;
                    $a->f();
                ',
                'error_message' => 'DirectConstructorCall',
            ],
            'privateFinalMethodsAreForbidden' => [
                'code' => <<<'PHP'
                    <?php
                    class Foo {
                        final private function baz(): void {}
                    }
                    PHP,
                'error_message' => 'PrivateFinalMethod',
            ],
            'readonlyClass' => [
                'code' => <<<'PHP'
                    <?php
                    readonly class Foo {
                        public int $a = 22;
                    }
                    $foo = new Foo;
                    $foo->a = 33;
                    PHP,
                'error_message' => 'InaccessibleProperty',
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'readonlyClassRequiresTypedProperties' => [
                'code' => <<<'PHP'
                    <?php
                    readonly class Foo {
                        /** @var int */
                        public $a = 22;
                    }
                    PHP,
                'error_message' => 'MissingPropertyType',
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'readonlyClassCannotHaveDynamicProperties' => [
                'code' => <<<'PHP'
                    <?php
                    #[AllowDynamicProperties]
                    readonly class Foo {}
                    PHP,
                'error_message' => 'InvalidAttribute',
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'readonlyClassesCannotBeExtendedByNonReadonlyOnes' => [
                'code' => <<<'PHP'
                    <?php
                    readonly class Foo {}
                    class Bar extends Foo {}
                    PHP,
                'error_message' => 'InvalidExtendClass',
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'classCannotExtendIfNotInInheritors' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @psalm-inheritors FooClass|BarClass
                     */
                    class BaseClass {}
                    class BazClass extends BaseClass {} // this is an error
                    PHP,
                'error_message' => 'InheritorViolation',
                'ignored_issues' => [],
            ],
            'classCannotImplementIfNotInInheritors' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @psalm-inheritors FooClass|BarClass
                     */
                    interface BaseInterface {}
                    class BazClass implements BaseInterface {}
                    PHP,
                'error_message' => 'InheritorViolation',
                'ignored_issues' => [],
            ],
            'interfaceCannotImplementIfNotInInheritors' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @psalm-inheritors FooClass|BarClass
                     */
                    interface BaseInterface {}
                    interface BazInterface extends BaseInterface {}
                    PHP,
                'error_message' => 'InheritorViolation',
                'ignored_issues' => [],
            ],
            'UnfulfilledInterfaceInheritors' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @psalm-inheritors FooClass
                     */
                    interface InterfaceA {}
                    /**
                     * @psalm-inheritors BarClass
                     */
                    interface InterfaceB {}
                    class BazClass implements InterFaceA, InterFaceB {}
                    PHP,
                'error_message' => 'InheritorViolation',
                'ignored_issues' => [],
            ],
        ];
    }
}
