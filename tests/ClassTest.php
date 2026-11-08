<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class ClassTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-capabilities read-props|write-this-props|write-props
     */
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

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'preventDoubleStaticResolutionOnAPlainReceiver1' => [
                'code' => '<?php

                    /**
                     * @template TTKey of array-key
                     * @template TTValue
                     *
                     * @extends ArrayObject<TTKey, TTValue>
                     * @psalm-capabilities read-props|write-this-props
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
            'preventDoubleStaticResolutionOnAPlainReceiver2' => [
                'code' => '<?php
                    /**
                     * @template TTKey of array-key
                     * @template TTValue
                     *
                     * @extends ArrayObject<TTKey, TTValue>
                     * @psalm-capabilities read-props|write-this-props
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
            'preventDoubleStaticResolutionOnAPlainReceiver3' => [
                'code' => '<?php
                    /**
                     * @template TTKey of array-key
                     * @template TTValue
                     *
                     * @extends ArrayObject<TTKey, TTValue>
                     * @psalm-capabilities read-props|write-this-props
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
            'staticOfAPlainReceiverIsBoundOnlyWhereTheTypeIsCovariant' => [
                'code' => '<?php
                    class Base {
                        /** @param static $other */
                        public function consume(self $other): void {}

                        /** @return Closure(static):void */
                        public function callback(): Closure {
                            return $this->consume(...);
                        }

                        /** @return Box<static> */
                        public function boxed(): Box {
                            throw new RuntimeException();
                        }

                        /** @return CovariantBox<static> */
                        public function covariantBoxed(): CovariantBox {
                            throw new RuntimeException();
                        }
                    }

                    /** @template T */
                    final class Box {}

                    /** @template-covariant T */
                    final class CovariantBox {}

                    function test(Base $receiver): void {
                        $callback = $receiver->callback();
                        /** @psalm-check-type-exact $callback = Closure(Base&static):void */;
                        $boxed = $receiver->boxed();
                        /** @psalm-check-type-exact $boxed = Box<Base&static> */;
                        $covariant = $receiver->covariantBoxed();
                        /** @psalm-check-type-exact $covariant = CovariantBox<Base> */;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'lateStaticOfAGenericClassKeepsItsTypeArgumentsInATemplateArgument' => [
                'code' => '<?php
                    /** @template T of int */
                    class GenInt {
                        /** @param static $other */
                        public function test($other): void {
                            $boxed = box($other);
                            /** @psalm-check-type-exact $boxed = Box<GenInt<int>&static> */;
                        }
                    }

                    /** @template T */
                    final class Box {}

                    /**
                     * @template T
                     * @param T $value
                     * @return Box<T>
                     */
                    function box(mixed $value): Box {
                        return new Box();
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'classesOfBothBranchesOfAnUnknownAnd' => [
                'code' => '<?php
                    if (rand(0, 1) && rand(0, 1)) {
                        class First {}
                    } else {
                        class Second {}
                    }

                    new First();
                    new Second();',
            ],
            'classesOfBothBranchesOfAnUnknownLogicalAnd' => [
                'code' => '<?php
                    if (rand(0, 1) and rand(0, 1)) {
                        class First {}
                    } else {
                        class Second {}
                    }

                    new First();
                    new Second();',
            ],
            'classesOfBothBranchesOfAnUnknownOr' => [
                'code' => '<?php
                    if (rand(0, 1) || rand(0, 1)) {
                        class First {}
                    } else {
                        class Second {}
                    }

                    new First();
                    new Second();',
            ],
            'classesOfBothBranchesOfAnUnknownLogicalOr' => [
                'code' => '<?php
                    if (rand(0, 1) or rand(0, 1)) {
                        class First {}
                    } else {
                        class Second {}
                    }

                    new First();
                    new Second();',
            ],
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

                        /** @psalm-mutation-free */
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

                        /** @psalm-mutation-free */
                        public static function bat(): string {
                            return "baz";
                        }
                    }',
            ],
            'typedMagicCall' => [
                'code' => '<?php
                    /** @psalm-no-seal-methods */
                    class B {
                        public function __call(string $methodName, array $args) : string {
                            return __METHOD__;
                        }
                    }
                    /** @psalm-no-seal-methods */
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
                    if (!class_exists(\Exception::class)) {
                        /** @psalm-suppress UndefinedClass */
                        class_alias(\Legacy_Exception::class, \Exception::class);
                    }

                    class T extends \Exception {

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
            'classAliasInterfaceMethodCall' => [
                'code' => '<?php
                    interface NewI {}
                    class_alias(NewI::class, OldI::class);

                    class A implements OldI {
                        public function foo(): void {}
                    }

                    function action(A $a): void {
                        $a->foo();
                    }',
            ],
            'classAliasEnumCase' => [
                'code' => '<?php
                    enum Suit { case Hearts; }
                    class_alias(Suit::class, "SuitAlias");

                    $case = SuitAlias::Hearts;',
                'assertions' => [
                    '$case===' => 'enum(Suit::Hearts)',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'classAliasEnumCaseThroughClassString' => [
                'code' => '<?php
                    enum Suit { case Hearts; }
                    class_alias(Suit::class, "SuitAlias");

                    /** @param SuitAlias::class $enum */
                    function hearts(string $enum): Suit {
                        return $enum::Hearts;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'classAliasClassLiteralKeepsAliasSpelling' => [
                'code' => '<?php
                    enum Suit {}
                    class_alias(Suit::class, "SuitAlias");
                    interface HasLabel {}
                    class_alias(HasLabel::class, "LabelAlias");

                    $enum = SuitAlias::class;
                    $interface = LabelAlias::class;',
                'assertions' => [
                    '$enum===' => 'SuitAlias::class',
                    '$interface===' => 'LabelAlias::class',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'classAliasInterfaceConstant' => [
                'code' => '<?php
                    interface HasLabel {
                        /** @var string */
                        public const LABEL = "x";
                    }
                    class_alias(HasLabel::class, "LabelAlias");

                    function f(): string {
                        return LabelAlias::LABEL;
                    }',
            ],
            'classAliasAttribute' => [
                'code' => '<?php
                    #[Attribute]
                    final class Tag {}
                    class_alias(Tag::class, "TagAlias");

                    #[Tag, TagAlias]
                    final class Tagged {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'classAliasTraitInitializedInConstructor' => [
                'code' => '<?php
                    trait Greets {
                        private string $greeting;
                        private function init(): void {
                            $this->greeting = "hi";
                        }
                    }
                    class_alias(Greets::class, "GreetsAlias");

                    final class Greeter {
                        use GreetsAlias;
                        public function __construct() {
                            $this->init();
                        }
                    }',
            ],
            'classAliasPropertiesOf' => [
                'code' => '<?php
                    final class Row {
                        public int $id = 1;
                    }
                    class_alias(Row::class, "RowAlias");

                    /** @param properties-of<RowAlias> $p */
                    function id(array $p): int {
                        return $p["id"];
                    }',
            ],
            'classAliasImportType' => [
                'code' => '<?php
                    /** @psalm-type Row = array{id: int} */
                    final class Rows {}
                    class_alias(Rows::class, "RowsAlias");

                    /** @psalm-import-type Row from RowsAlias */
                    final class UsesRow {
                        /** @param Row $r */
                        public function id(array $r): int {
                            return $r["id"];
                        }
                    }',
            ],
            'classAliasImportTypeInIntersection' => [
                'code' => '<?php
                    /** @psalm-type Row = array{id: int} */
                    final class Rows {}
                    class_alias(Rows::class, "RowsAlias");

                    /** @psalm-import-type Row from RowsAlias */
                    final class UsesRow {
                        /** @param Row&array{name: string} $r */
                        public function id(array $r): int {
                            return $r["id"];
                        }
                    }',
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
                     * @psalm-capabilities read-props|write-this-props
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
                        public function test(): void {
                            $a = $this->ret();
                            $a = $a->stabilize();
                            /** @psalm-check-type-exact $a = iter<int, b&static> */;
                        }
                    }',
            ],
            'preventDoubleStaticResolution2' => [
                'code' => '<?php
                    /**
                     * @template TTKey of array-key
                     * @template TTValue
                     *
                     * @extends ArrayObject<TTKey, TTValue>
                     * @psalm-capabilities read-props|write-this-props
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
                    abstract class b implements a {
                        public function test(): void {
                            $a = $this->ret();
                            $a = $a->stabilize();
                            /** @psalm-check-type-exact $a = iter<int, b&static> */;
                        }
                    }',
            ],
            'preventDoubleStaticResolution3' => [
                'code' => '<?php
                    /**
                     * @template TTKey of array-key
                     * @template TTValue
                     *
                     * @extends ArrayObject<TTKey, TTValue>
                     * @psalm-capabilities read-props|write-this-props
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
                    abstract class b implements a {
                        public function test(): void {
                            $a = $this->ret();
                            $a = $a->stabilize();
                            /** @psalm-check-type-exact $a = iter<int, b&static&a> */;
                        }
                    }',
            ],
            'preventDoubleStaticResolutionAfterUnionWithNull' => [
                'code' => '<?php
                    /** @template T */
                    class Box {
                        /** @return self<T> */
                        public function same(): self {
                            return $this;
                        }
                    }

                    /** @template T */
                    abstract class Bag {
                        /** @return static<int> */
                        abstract public function map(): static;

                        public function test(): void {
                            $a = box($this->map());
                            /** @psalm-check-type-exact $a = Box<Bag<int>&static|null> */;
                            $b = $a->same();
                            $c = $b->same();
                            /** @psalm-check-type-exact $c = Box<Bag<int>&static|null> */;
                        }
                    }

                    /**
                     * @template T
                     * @param T $value
                     * @return Box<T|null>
                     */
                    function box(mixed $value): Box {
                        return new Box();
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'preventDoubleStaticResolutionAfterUnionOfStatics' => [
                'code' => '<?php
                    /** @template T */
                    class Box {
                        /** @return self<T> */
                        public function same(): self {
                            return $this;
                        }
                    }

                    /** @template T */
                    abstract class Bag {
                        /** @return static<int> */
                        abstract public function toInt(): static;

                        /** @return static<float> */
                        abstract public function toFloat(): static;

                        public function test(): void {
                            $a = box(rand(0, 1) ? $this->toInt() : $this->toFloat());
                            $b = $a->same();
                            /** @psalm-check-type-exact $b = Box<Bag<float>&static|Bag<int>&static> */;
                        }
                    }

                    /**
                     * @template T
                     * @param T $value
                     * @return Box<T>
                     */
                    function box(mixed $value): Box {
                        return new Box();
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'preventDoubleStaticResolutionAfterClosureInference' => [
                'code' => '<?php
                    /** @template T */
                    class Box {
                        /** @return self<T> */
                        public function same(): self {
                            return $this;
                        }

                        /**
                         * @template U
                         * @param Closure(T): U $f
                         * @return Box<U>
                         */
                        public function apply(Closure $f): self {
                            throw new RuntimeException();
                        }
                    }

                    /** @template T */
                    abstract class Bag {
                        /** @return static<int> */
                        abstract public function map(): static;

                        public function test(): void {
                            $a = box($this->map())->apply(fn($x) => $x);
                            /** @psalm-check-type-exact $a = Box<Bag<int>&static> */;
                            $b = $a->same();
                            /** @psalm-check-type-exact $b = Box<Bag<int>&static> */;
                        }
                    }

                    /**
                     * @template T
                     * @param T $value
                     * @return Box<T>
                     */
                    function box(mixed $value): Box {
                        return new Box();
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'staticReturnOfANonStaticReceiverIsItsClass' => [
                'code' => '<?php
                    /** @template T */
                    class Box {
                        /** @return self<T> */
                        public function same(): self {
                            return $this;
                        }
                    }

                    /** @template T */
                    class Bag {
                        /** @return static<int> */
                        public function map(): static {
                            throw new RuntimeException();
                        }
                    }

                    /**
                     * @template T
                     * @param T $value
                     * @return Box<T|null>
                     */
                    function box(mixed $value): Box {
                        return new Box();
                    }

                    /** @var Bag<string> $bag */
                    $bag = new Bag();
                    $mapped = $bag->map();
                    $a = box($bag->map());
                    $b = $a->same();',
                'assertions' => [
                    '$mapped===' => 'Bag<int>',
                    '$a===' => 'Box<Bag<int>|null>',
                    '$b===' => 'Box<Bag<int>|null>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'firstClassCallableOfANonStaticReceiverBindsTheStaticOfItsReturnType' => [
                'code' => '<?php
                    /** @template T */
                    class Bag {
                        /** @return static<int> */
                        public function map(): static {
                            throw new RuntimeException();
                        }

                        /** @param static $other */
                        public function merge($other): static {
                            return $this;
                        }
                    }

                    /** @var Bag<string> $bag */
                    $bag = new Bag();
                    $map = $bag->map(...);
                    $merge = $bag->merge(...);',
                'assertions' => [
                    '$map===' => 'Closure[impure]():Bag<int>',
                    '$merge===' => 'Closure[impure](Bag<string>&static):Bag<string>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'callersLateStaticIsNotBoundToTheReceiverOfTheCall' => [
                'code' => '<?php
                    final class Container {
                        /**
                         * @template T of object
                         * @param class-string<T> $abstract
                         * @return T
                         */
                        public function make(string $abstract): object {
                            throw new RuntimeException();
                        }
                    }

                    /**
                     * @template-covariant TValue
                     */
                    final class Coll {
                        /**
                         * @template U
                         * @param callable(TValue): U $f
                         * @return Coll<U>
                         */
                        public function map(callable $f): Coll {
                            throw new RuntimeException();
                        }
                    }

                    class Component {
                        public function make(Container $container): static {
                            $made = $container->make(static::class);
                            /** @psalm-check-type-exact $made = Component&static */;
                            return $made;
                        }

                        /**
                         * @param Coll<int> $ids
                         * @return Coll<static>
                         */
                        public function copies(Coll $ids): Coll {
                            $copies = $ids->map(fn (int $_id): static => clone $this);
                            /** @psalm-check-type-exact $copies = Coll<Component&static> */;
                            return $copies;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'staticReturnOnATemplatedReceiverKeepsTheCallersTemplate' => [
                'code' => '<?php
                    /** @template T */
                    class Bag {
                        /**
                         * @param T $value
                         * @return static
                         */
                        public function with($value): static {
                            return $this;
                        }
                    }

                    /**
                     * @template TBag of Bag<int>
                     * @param TBag $bag
                     * @return TBag
                     */
                    function withOne(Bag $bag): Bag {
                        $with = $bag->with(1);
                        /** @psalm-check-type-exact $with = TBag */;
                        return $with;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'staticOfTheCalleesTemplateArgumentsIsBoundToTheReceiver' => [
                'code' => '<?php
                    /** @template TModel of object */
                    class Factory {
                        public static function new(): static {
                            return new static();
                        }

                        public function count(int $count): static {
                            return $this;
                        }

                        public static function times(int $count): static {
                            $factory = static::new()->count($count);
                            /** @psalm-check-type-exact $factory = Factory<TModel>&static */;
                            return $factory;
                        }
                    }

                    /** @template-covariant TValue */
                    final class Coll {}

                    /** @template TColl of Coll */
                    trait HasColl {
                        /** @return TColl */
                        public function newCollection(): Coll {
                            throw new RuntimeException();
                        }
                    }

                    abstract class Model {
                        /** @use HasColl<Coll<static & self>> */
                        use HasColl;
                    }

                    final class User extends Model {}

                    $users = (new User())->newCollection();',
                'assertions' => [
                    '$users===' => 'Coll<User>',
                ],
                'ignored_issues' => ['UnsafeInstantiation'],
                'php_version' => '8.0',
            ],
            'staticOfABoundedGenericClassKeepsItsTypeArgumentsAndStaticInATemplateArgument' => [
                'code' => '<?php
                    /** @template T of int */
                    class Foo {
                        /** @param static $foo */
                        public function test($foo): void {
                            $a = box($foo);
                            /** @psalm-check-type-exact $a = Box<Foo<int>&static> */;
                        }
                    }

                    /** @template T */
                    final class Box {}

                    /**
                     * @template T
                     * @param T $value
                     * @return Box<T>
                     */
                    function box(mixed $value): Box {
                        return new Box();
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'thisInATemplateArgumentIsNotBoundToLaterReceivers' => [
                'code' => '<?php
                    class Foo {
                        public function test(): void {
                            $a = box($this)->same()->same()->same();
                            /** @psalm-check-type-exact $a = Box<Foo&static> */;
                            $n = boxOrNull($this)->same()->same()->same();
                            /** @psalm-check-type-exact $n = Box<Foo&static|null> */;
                        }
                    }

                    /** @template T */
                    final class Box {
                        /** @return self<T> */
                        public function same(): self {
                            return $this;
                        }
                    }

                    /**
                     * @template T
                     * @param T $value
                     * @return Box<T>
                     */
                    function box(mixed $value): Box {
                        return new Box();
                    }

                    /**
                     * @template T
                     * @param T $value
                     * @return Box<T|null>
                     */
                    function boxOrNull(mixed $value): Box {
                        return new Box();
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'thisInATemplateArgumentIsNotBoundOnOtherCallPaths' => [
                'code' => '<?php
                    class Foo {
                        /**
                         * @template T
                         * @param T $value
                         * @return Box<T>
                         */
                        public function wrap(mixed $value): Box {
                            return new Box($value);
                        }

                        public function test(Bar $bar): void {
                            $subclass_receiver = $bar->wrap($this);
                            /** @psalm-check-type-exact $subclass_receiver = Box<Foo&static> */;
                            $first_class_callable = (box($this)->same(...))();
                            /** @psalm-check-type-exact $first_class_callable = Box<Foo&static> */;
                            $template_return = box($this)->get();
                            /** @psalm-check-type-exact $template_return = Foo&static */;
                            $static_return = box($this)->stat()->stat();
                            /** @psalm-check-type-exact $static_return = Box<Foo&static> */;
                            $stubbed = iterator($this)->current();
                            /** @psalm-check-type-exact $stubbed = Foo&static|null */;
                        }
                    }

                    class Bar extends Foo {}

                    /** @template T */
                    class Box {
                        /** @param T $value */
                        public function __construct(public mixed $value) {}

                        /** @return self<T> */
                        public function same(): self {
                            return $this;
                        }

                        /** @return T */
                        public function get(): mixed {
                            return $this->value;
                        }

                        public function stat(): static {
                            return $this;
                        }
                    }

                    /**
                     * @template T
                     * @param T $value
                     * @return Box<T>
                     */
                    function box(mixed $value): Box {
                        return new Box($value);
                    }

                    /**
                     * @template T
                     * @param T $value
                     * @return ArrayIterator<int, T>
                     */
                    function iterator(mixed $value): ArrayIterator {
                        return new ArrayIterator([$value]);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'staticOfTheCallingContextIsNotBoundToLaterReceivers' => [
                'code' => '<?php
                    class Bar {
                        public string $initialized_in_constructor;

                        public function __construct() {
                            $constructor = box($this)->same()->same();
                            /** @psalm-check-type-exact $constructor = Box<Bar&static> */;
                            $this->initialized_in_constructor = "";
                        }
                    }

                    trait Boxes {
                        public function test(): void {
                            $trait_method = box($this)->same()->same();
                            /** @psalm-check-type-exact $trait_method = Box<Baz&static> */;
                        }
                    }

                    class Baz {
                        use Boxes;
                    }

                    class Foo {
                        final public function __construct() {}

                        public function test(object $object): void {
                            $new_static = box(new static())->same()->same();
                            /** @psalm-check-type-exact $new_static = Box<Foo&static> */;
                            $class = static::class;
                            $static_class = box(new $class())->same()->same();
                            /** @psalm-check-type-exact $static_class = Box<Foo&static> */;
                            $called_class = get_called_class();
                            $called = box(new $called_class())->same()->same();
                            /** @psalm-check-type-exact $called = Box<Foo&static> */;
                            if ($object instanceof static) {
                                $instanceof_static = box($object)->same()->same();
                                /** @psalm-check-type-exact $instanceof_static = Box<Foo&static> */;
                            }
                            if ($object instanceof $this) {
                                $instanceof_this = box($object)->same()->same();
                                /** @psalm-check-type-exact $instanceof_this = Box<Foo&static> */;
                            }
                            if (is_a($object, static::class)) {
                                $is_a = box($object)->same()->same();
                                /** @psalm-check-type-exact $is_a = Box<Foo&static> */;
                            }
                            $closure = (function () {
                                return box($this)->same()->same();
                            })();
                            /** @psalm-check-type-exact $closure = Box<Foo&static> */;
                        }
                    }

                    /** @template T */
                    final class Box {
                        /** @return self<T> */
                        public function same(): self {
                            return $this;
                        }
                    }

                    /**
                     * @template T
                     * @param T $value
                     * @return Box<T>
                     */
                    function box(mixed $value): Box {
                        return new Box();
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'staticNarrowedInAGenericClassIsNotBoundToLaterReceivers' => [
                'code' => '<?php
                    /** @template T */
                    interface Collection {}

                    /**
                     * @template T
                     * @implements Collection<T>
                     */
                    class ListCollection implements Collection {
                        /** @param Collection<int> $collection */
                        public function test(Collection $collection): void {
                            if ($collection instanceof static) {
                                $a = box($collection)->same()->same();
                                /** @psalm-check-type-exact $a = Box<ListCollection<int>&Collection<int>&static> */;
                            }
                        }
                    }

                    /** @template T of int */
                    class IntCollection {
                        public function test(object $object): void {
                            if ($object instanceof static) {
                                $b = box($object)->same()->same();
                                /** @psalm-check-type-exact $b = Box<IntCollection<int>&static> */;
                            }
                        }
                    }

                    /** @template T */
                    final class Box {
                        /** @return self<T> */
                        public function same(): self {
                            return $this;
                        }
                    }

                    /**
                     * @template T
                     * @param T $value
                     * @return Box<T>
                     */
                    function box(mixed $value): Box {
                        return new Box();
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'thisOfAClassBoundByStaticIsNotBoundToLaterReceivers' => [
                'code' => '<?php
                    /** @template T of static */
                    class Foo {
                        public function test(): void {
                            $a = box($this)->same()->same();
                            /** @psalm-check-type-exact $a = Box<Foo<T>&static> */;
                        }
                    }

                    /** @template T */
                    final class Box {
                        /** @return self<T> */
                        public function same(): self {
                            return $this;
                        }
                    }

                    /**
                     * @template T
                     * @param T $value
                     * @return Box<T>
                     */
                    function box(mixed $value): Box {
                        return new Box();
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'staticOfAMethodOverriddenWithSelfIsBoundToTheCalledClass' => [
                'code' => '<?php
                    abstract class Factory {
                        /** @return static */
                        abstract public static function make(): self;

                        /** @return static|null */
                        abstract public static function makeOrNull(): self|null|false;
                    }

                    /** @psalm-consistent-constructor */
                    class Concrete extends Factory {
                        public static function make(): self {
                            return new static();
                        }

                        public static function makeOrNull(): self|false {
                            return new static();
                        }
                    }

                    class Child extends Concrete {
                        public function onlyOnChild(): void {}
                    }

                    $child = Child::make();
                    $child->onlyOnChild();
                    $child_or_null = Child::makeOrNull();
                    $child_or_null->onlyOnChild();',
                'assertions' => [
                    '$child===' => 'Child',
                    '$child_or_null===' => 'Child',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'genericStaticOfAnInheritedStaticFactoryIsBoundToTheFinalCalledClass' => [
                'code' => '<?php
                    /** @template T */
                    abstract class Collection {
                        /** @return static<T> */
                        abstract public static function empty(): static;
                    }

                    /**
                     * @template T
                     * @extends Collection<T>
                     * @psalm-consistent-constructor
                     * @psalm-consistent-templates
                     */
                    class ListCollection extends Collection {
                        public static function empty(): static {
                            return new static();
                        }
                    }

                    /**
                     * @template T
                     * @extends ListCollection<T>
                     */
                    final class Leaf extends ListCollection {
                        public function onlyOnLeaf(): void {}
                    }

                    $leaf = Leaf::empty();
                    $leaf->onlyOnLeaf();',
                'assertions' => [
                    '$leaf===' => 'Leaf<T:Leaf as mixed>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'staticDeclaredByTheReceiverClassIsStillBoundToTheReceiver' => [
                'code' => '<?php
                    /** @template T */
                    abstract class Repository {
                        /** @return T */
                        public function find(): mixed {
                            throw new Exception();
                        }
                    }

                    /**
                     * @template TKey of array-key
                     * @template TValue
                     */
                    final class Collection {}

                    /** @template TCollection of Collection */
                    trait HasCollection {
                        /** @return TCollection */
                        public function newCollection(): Collection {
                            throw new Exception();
                        }
                    }

                    /** @extends Repository<static> */
                    abstract class Model extends Repository {
                        /** @use HasCollection<Collection<array-key, static & self>> */
                        use HasCollection;
                    }

                    final class User extends Model {}

                    class Post extends Model {}

                    final class Draft extends Post {}

                    $user = (new User())->find();
                    $post = (new Post())->find();
                    $draft = (new Draft())->find();
                    $users = (new User())->newCollection();
                    $posts = (new Post())->newCollection();',
                'assertions' => [
                    '$user===' => 'User',
                    '$post===' => 'Post',
                    '$draft===' => 'Draft',
                    '$users===' => 'Collection<array-key, User>',
                    '$posts===' => 'Collection<array-key, Post&static>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'staticDeclaredByAMethodCalledOnThisIsStillBoundToThis' => [
                'code' => '<?php
                    /**
                     * @template TKey of array-key
                     * @template TValue
                     */
                    abstract class Collection {
                        /** @return static */
                        public function same(): static {
                            return $this;
                        }

                        /** @return static<int, TValue> */
                        abstract public function values(): static;

                        /** @return Collection<int, TKey> */
                        abstract public function keys(): Collection;

                        public function test(): void {
                            $same = $this->same();
                            /** @psalm-check-type-exact $same = Collection<TKey, TValue>&static */;
                            $values = $this->values();
                            /** @psalm-check-type-exact $values = Collection<int, TValue>&static */;
                            $keys = $this->keys()->same();
                            /** @psalm-check-type-exact $keys = Collection<int, TKey> */;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
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

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'constructorDoesNotBindTheLateStaticOfItsArgument' => [
                'code' => '<?php
                    /** @template T */
                    class Base {
                        /** @var T */
                        public mixed $value;

                        /**
                         * @param T $value
                         * @psalm-self-out static<T>
                         */
                        public function __construct(mixed $value) {
                            $this->value = $value;
                        }

                        public function test(): void {
                            $box = new Child($this);
                            $box->value->childOnly();
                        }
                    }

                    /**
                     * @template T
                     * @extends Base<T>
                     */
                    class Child extends Base {
                        public function childOnly(): void {}
                    }',
                'error_message' => 'UndefinedMethod - src' . DIRECTORY_SEPARATOR . 'somefile.php:17:42 - Method Base::childOnly',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'callbackOfAPlainReceiverKeepsTheLateStaticOfItsParameter' => [
                'code' => '<?php
                    class Base {
                        /** @param static $other */
                        public function consume(self $other): void {}

                        /** @return Closure(static):void */
                        public function callback(): Closure {
                            return $this->consume(...);
                        }
                    }

                    function test(Base $receiver): void {
                        $callback = $receiver->callback();
                        $callback(new Base());
                    }',
                'error_message' => 'ArgumentTypeCoercion',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'elseOfAKnownTrueAndIsNotDeclared' => [
                'code' => '<?php
                    if (function_exists("strlen") && function_exists("strpos")) {
                        class First {}
                    } else {
                        class Second {}
                    }

                    new Second();',
                'error_message' => 'UndefinedClass',
            ],
            'elseOfAKnownTrueLogicalAndIsNotDeclared' => [
                'code' => '<?php
                    if (function_exists("strlen") and function_exists("strpos")) {
                        class First {}
                    } else {
                        class Second {}
                    }

                    new Second();',
                'error_message' => 'UndefinedClass',
            ],
            'elseOfAKnownTrueOrIsNotDeclared' => [
                'code' => '<?php
                    if (function_exists("strlen") || rand(0, 1)) {
                        class First {}
                    } else {
                        class Second {}
                    }

                    new Second();',
                'error_message' => 'UndefinedClass',
            ],
            'elseOfAKnownTrueLogicalOrIsNotDeclared' => [
                'code' => '<?php
                    if (rand(0, 1) or function_exists("strlen")) {
                        class First {}
                    } else {
                        class Second {}
                    }

                    new Second();',
                'error_message' => 'UndefinedClass',
            ],
            'ifOfAKnownFalseAndIsNotDeclared' => [
                'code' => '<?php
                    if (!function_exists("strlen") && rand(0, 1)) {
                        class First {}
                    } else {
                        class Second {}
                    }

                    new First();',
                'error_message' => 'UndefinedClass',
            ],
            'ifOfAKnownFalseLogicalAndIsNotDeclared' => [
                'code' => '<?php
                    if (rand(0, 1) and !function_exists("strlen")) {
                        class First {}
                    } else {
                        class Second {}
                    }

                    new First();',
                'error_message' => 'UndefinedClass',
            ],
            'ifOfAKnownFalseOrIsNotDeclared' => [
                'code' => '<?php
                    if (!function_exists("strlen") || !function_exists("strpos")) {
                        class First {}
                    } else {
                        class Second {}
                    }

                    new First();',
                'error_message' => 'UndefinedClass',
            ],
            'ifOfAKnownFalseLogicalOrIsNotDeclared' => [
                'code' => '<?php
                    if (!function_exists("strlen") or !function_exists("strpos")) {
                        class First {}
                    } else {
                        class Second {}
                    }

                    new First();',
                'error_message' => 'UndefinedClass',
            ],
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
                    /** @psalm-capabilities read-props|write-this-props */
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
                'error_levels' => [],
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
                'error_levels' => [],
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
                'error_levels' => [],
                'php_version' => '8.2',
            ],
            'readonlyClassCannotHaveDynamicProperties' => [
                'code' => <<<'PHP'
                    <?php
                    #[AllowDynamicProperties]
                    readonly class Foo {}
                    PHP,
                'error_message' => 'InvalidAttribute',
                'error_levels' => [],
                'php_version' => '8.2',
            ],
            'readonlyClassesCannotBeExtendedByNonReadonlyOnes' => [
                'code' => <<<'PHP'
                    <?php
                    readonly class Foo {}
                    class Bar extends Foo {}
                    PHP,
                'error_message' => 'InvalidExtendClass',
                'error_levels' => [],
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
                'error_levels' => [],
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
                'error_levels' => [],
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
                'error_levels' => [],
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
                'error_levels' => [],
            ],
            'duplicateInstanceProperties' => [
                'code' => <<<'PHP'
                    <?php
                    class Foo {
                        public mixed $bar;
                        public int $bar;
                    }
                    PHP,
                'error_message' => 'DuplicateProperty',
                'error_levels' => [],
            ],
            'duplicateStaticProperties' => [
                'code' => <<<'PHP'
                    <?php
                    class Foo {
                        public static mixed $bar = null;
                        public static string $bar = 'bar';
                    }
                    PHP,
                'error_message' => 'DuplicateProperty',
                'error_levels' => [],
            ],
            'duplicateMixedProperties' => [
                'code' => <<<'PHP'
                    <?php
                    class Foo {
                        public bool $bar = true;
                        public static bool $bar = false;
                    }
                    PHP,
                'error_message' => 'DuplicateProperty',
                'error_levels' => [],
            ],
            'duplicatePropertiesDifferentVisibility' => [
                'code' => <<<'PHP'
                    <?php
                    class Foo {
                        public bool $bar;
                        private string $bar;
                    }
                    PHP,
                'error_message' => 'DuplicateProperty',
                'error_levels' => [],
            ],
            'classAliasRepeatedNonRepeatableAttribute' => [
                'code' => '<?php
                    #[Attribute]
                    final class Tag {}
                    class_alias(Tag::class, "TagAlias");

                    #[TagAlias, TagAlias]
                    final class Tagged {}',
                'error_message' => 'Attribute TagAlias is not repeatable',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'classAliasDocblockConstant' => [
                'code' => '<?php
                    final class Level {
                        public const A = "a";
                        public const B = "b";
                    }
                    class_alias(Level::class, "LevelAlias");

                    /** @param LevelAlias::A|LevelAlias::B $v */
                    function f(string $v): void {}
                    f("c");',
                'error_message' => 'expects \'a\'|\'b\', but \'c\' provided',
            ],
            'classAliasReflectionGetAttributes' => [
                'code' => '<?php
                    #[Attribute(Attribute::TARGET_METHOD)]
                    final class Tag {}
                    class_alias(Tag::class, "TagAlias");

                    final class Tagged {}
                    (new ReflectionClass(Tagged::class))->getAttributes("TagAlias");',
                'error_message' => 'Attribute TagAlias cannot be used on a class',
                'error_levels' => ['ArgumentTypeCoercion'],
                'php_version' => '8.0',
            ],
            'classAliasCallableArray' => [
                'code' => '<?php
                    final class Num {
                        public static function twice(int $i): int {
                            return $i * 2;
                        }
                    }
                    class_alias(Num::class, "NumAlias");

                    /** @param callable(string):int $f */
                    function takeWrong(callable $f): void {}
                    takeWrong(["NumAlias", "twice"]);',
                'error_message' => 'InvalidScalarArgument',
            ],
        ];
    }
}
