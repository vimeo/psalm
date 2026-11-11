<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class TraitTemplateTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'receiverWithoutTypeArgumentsOfAGenericClassUsingATraitTakesTheBoundsOfItsTemplates' => [
                'code' => '<?php
                    /**
                     * @template TKey of array-key
                     * @template TValue
                     * @extends ArrayAccess<TKey, TValue>
                     */
                    interface ArrayInterface extends ArrayAccess {}

                    trait IsIterable {
                        public function offsetSet(mixed $offset, mixed $value): void {}
                        public function offsetGet(mixed $offset): mixed { return null; }
                        public function offsetExists(mixed $offset): bool { return false; }
                        public function offsetUnset(mixed $offset): void {}
                    }

                    /**
                     * @template TKey of array-key
                     * @template TValue
                     * @implements ArrayInterface<TKey, TValue>
                     */
                    final class ImmutableArray implements ArrayInterface {
                        use IsIterable;
                    }

                    final class Element {
                        private ImmutableArray $scopedVariables;

                        public function __construct() {
                            $this->scopedVariables = new ImmutableArray();
                        }

                        public function add(string $name): void {
                            $this->scopedVariables[$name] = $name;
                            $this->scopedVariables[5] = new stdClass();

                            if (isset($this->scopedVariables[$name])) {
                                $value = $this->scopedVariables[$name];
                            }

                            $this->scopedVariables[$name] ??= "default";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
                'php_version' => '8.0',
            ],
            'subclassOfAGenericClassUsingATraitThroughAnIntermediateInterfaceTakesItsTypeArguments' => [
                'code' => '<?php
                    /**
                     * @template TKey of array-key
                     * @template TValue
                     * @extends ArrayAccess<TKey, TValue>
                     */
                    interface ArrayInterface extends ArrayAccess {}

                    trait IsArrayAccess {
                        public function offsetSet(mixed $offset, mixed $value): void {}
                        public function offsetGet(mixed $offset): mixed { return null; }
                        public function offsetExists(mixed $offset): bool { return false; }
                        public function offsetUnset(mixed $offset): void {}
                    }

                    /**
                     * @template TKey of array-key
                     * @template TValue
                     * @implements ArrayInterface<TKey, TValue>
                     */
                    abstract class AbstractArray implements ArrayInterface {
                        use IsArrayAccess;
                    }

                    final class ImmutableString {}

                    /** @extends AbstractArray<string, ImmutableString> */
                    final class Concrete extends AbstractArray {}

                    function f(Concrete $c): void {
                        $c["key"] = new ImmutableString();
                        $value = $c["key"];
                        echo isset($c["other"]);
                    }

                    /** @param ArrayInterface<string, ImmutableString> $i */
                    function g(ArrayInterface $i): void {
                        $i["key"] = new ImmutableString();
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
                'php_version' => '8.0',
            ],
            'genericClassImplementingAnInterfaceThroughATraitTakesItsTypeArguments' => [
                'code' => '<?php
                    trait IsArrayAccess {
                        public function offsetSet(mixed $offset, mixed $value): void {}
                        public function offsetGet(mixed $offset): mixed { return null; }
                        public function offsetExists(mixed $offset): bool { return false; }
                        public function offsetUnset(mixed $offset): void {}
                    }

                    /**
                     * @template TKey of array-key
                     * @template TValue
                     * @implements ArrayAccess<TKey, TValue>
                     */
                    final class ImmutableArray implements ArrayAccess {
                        use IsArrayAccess;
                    }

                    final class ImmutableString {}

                    /** @param ImmutableArray<array-key, ImmutableString> $suggestions */
                    function f(ImmutableArray $suggestions): void {
                        $suggestions["key"] = new ImmutableString();
                        $suggestions[5] = new ImmutableString();
                        $value = $suggestions["key"];
                        echo isset($suggestions["other"]);
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
                'php_version' => '8.0',
            ],
            'traitMethodWithoutDocblockImplementsTemplatedParent' => [
                'code' => '<?php
                    /** @template T */
                    abstract class A
                    {
                        /**
                         * @param T $s
                         * @return T
                         */
                        abstract public function someMethod(mixed $s): mixed;
                    }

                    trait LetsTrait
                    {
                        public function someMethod(mixed $s): mixed
                        {
                            return $s;
                        }
                    }

                    /** @extends A<string> */
                    class C extends A
                    {
                        use LetsTrait;
                    }

                    $result = (new C())->someMethod("x");',
                'assertions' => ['$result' => 'string'],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'traitMethodWithoutDocblockImplementsTemplatedParentOfTheIssue' => [
                'code' => '<?php
                    /** @psalm-template T */
                    abstract class A
                    {
                        /**
                         * @psalm-param T $s
                         * @psalm-return T
                         */
                        abstract public function someMethod($s): mixed;
                    }

                    /** @extends A<string> */
                    class B extends A
                    {
                        public function someMethod($s): mixed
                        {
                            return $s;
                        }
                    }

                    /** @extends A<string> */
                    class C extends A
                    {
                        use LetsTrait;
                    }

                    trait LetsTrait
                    {
                        public function someMethod($s): mixed
                        {
                            return $s;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'traitMethodWithoutDocblockImplementsTemplatedInterface' => [
                'code' => '<?php
                    /** @template T */
                    interface I
                    {
                        /** @return T */
                        public function get(): mixed;
                    }

                    trait GetTrait
                    {
                        public function get(): mixed
                        {
                            return "a";
                        }
                    }

                    /** @implements I<string> */
                    class C implements I
                    {
                        use GetTrait;
                    }

                    $result = (new C())->get();',
                'assertions' => ['$result' => 'string'],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'traitMethodWithoutDocblockImplementsParentWithOnlyAReturnTag' => [
                'code' => '<?php
                    /** @template T */
                    abstract class A
                    {
                        /** @return T */
                        abstract public function get(): mixed;
                    }

                    trait GetTrait
                    {
                        public function get(): mixed
                        {
                            return "a";
                        }
                    }

                    /** @extends A<string> */
                    class C extends A
                    {
                        use GetTrait;
                    }

                    $result = (new C())->get();',
                'assertions' => ['$result' => 'string'],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'traitMethodWithoutDocblockUsedByClassesWithDifferentTemplateArguments' => [
                'code' => '<?php
                    /** @template T */
                    abstract class A
                    {
                        /** @return T */
                        abstract public function get(): mixed;
                    }

                    trait GetTrait
                    {
                        public function get(): mixed
                        {
                            throw new Exception();
                        }
                    }

                    /** @extends A<string> */
                    class C extends A
                    {
                        use GetTrait;
                    }

                    /** @extends A<int> */
                    class D extends A
                    {
                        use GetTrait;
                    }

                    $c = (new C())->get();
                    $d = (new D())->get();',
                'assertions' => ['$c' => 'string', '$d' => 'int'],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'traitUseNotExtended' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    trait CollectionTrait
                    {
                        /**
                         * @return array<T>
                         */
                        abstract function elements() : array;

                        /**
                         * @return T|null
                         */
                        public function first()
                        {
                            return $this->elements()[0] ?? null;
                        }
                    }

                    class Service
                    {
                        /**
                         * @use CollectionTrait<int>
                         */
                        use CollectionTrait;

                        /**
                         * @return array<int>
                         */
                        public function elements(): array
                        {
                            return [1, 2, 3, 4];
                        }
                    }',
            ],
            'extendedTraitUse' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    trait CollectionTrait
                    {
                        /**
                         * @return array<T>
                         */
                        abstract function elements() : array;

                        /**
                         * @return T|null
                         */
                        public function first()
                        {
                            return $this->elements()[0] ?? null;
                        }
                    }

                    /**
                     * @template TValue
                     */
                    trait BridgeTrait
                    {
                        /**
                         * @use CollectionTrait<TValue>
                         */
                        use CollectionTrait;
                    }

                    class Service
                    {
                        /**
                         * @use BridgeTrait<int>
                         */
                        use BridgeTrait;

                        /**
                         * @return array<int>
                         */
                        public function elements(): array
                        {
                            return [1, 2, 3, 4];
                        }
                    }',
            ],
            'extendedTraitUseAlreadyBound' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    trait CollectionTrait
                    {
                        /**
                         * @return array<T>
                         */
                        abstract function elements() : array;

                        /**
                         * @return T|null
                         */
                        public function first()
                        {
                            return $this->elements()[0] ?? null;
                        }
                    }

                    trait BridgeTrait
                    {
                        /**
                         * @use CollectionTrait<int>
                         */
                        use CollectionTrait;
                    }

                    class Service
                    {
                        use BridgeTrait;

                        /**
                         * @return array<int>
                         */
                        public function elements(): array
                        {
                            return [1, 2, 3, 4];
                        }
                    }',
            ],
            'badTemplateUseUnionType' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    trait T {
                        /** @var T */
                        public $t;

                        /** @param T $t */
                        public function __construct($t) {
                            $this->t = $t;
                        }
                    }

                    /**
                     * @template TT
                     */
                    class B {
                        /**
                         * @template-use T<int|string>
                         */
                        use T;
                    }',
            ],
            'multilineTemplateUse' => [
                'code' => '<?php
                    /**
                     * @template T1
                     * @template T2
                     * @template T3
                     */
                    trait MyTrait {}

                    class Foo {
                        /**
                         * @template-use MyTrait<int, int, array{
                         * 	foo: mixed,
                         * 	bar: mixed,
                         * }>
                         */
                        use MyTrait;
                    }

                    class Bar {
                        /**
                         * @template-use MyTrait<int, string, bar>
                         */
                        use MyTrait;
                    }',
            ],
            'allowTraitExtendAndImplementWithExplicitParamType' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    trait ValueObjectTrait
                    {
                        /**
                         * @psalm-var ?T
                         */
                        protected $value;

                        /**
                         * @psalm-param T $value
                         *
                         * @param $value
                         */
                        private function setValue($value): void {
                            $this->validate($value);

                            $this->value = $value;
                        }

                        /**
                         * @psalm-param T $value
                         *
                         * @param $value
                         */
                        abstract protected function validate($value): void;
                    }

                    final class StringValidator {
                        /**
                         * @template-use ValueObjectTrait<string>
                         */
                        use ValueObjectTrait;

                        /**
                         * @param string $value
                         */
                        protected function validate($value): void
                        {
                            if (strlen($value) > 30) {
                                throw new \Exception("bad");
                            }
                        }
                    }',
            ],
            'allowTraitExtendAndImplementWithoutExplicitParamType' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    trait ValueObjectTrait
                    {
                        /**
                         * @psalm-var ?T
                         */
                        protected $value;

                        /**
                         * @psalm-param T $value
                         *
                         * @param $value
                         */
                        private function setValue($value): void {
                            $this->validate($value);

                            $this->value = $value;
                        }

                        /**
                         * @psalm-param T $value
                         *
                         * @param $value
                         */
                        abstract protected function validate($value): void;
                    }

                    final class StringValidator {
                        /**
                         * @template-use ValueObjectTrait<string>
                         */
                        use ValueObjectTrait;

                        protected function validate($value): void
                        {
                            if (strlen($value) > 30) {
                                throw new \Exception("bad");
                            }
                        }
                    }',
            ],
            'traitInImplicitExtendedClass' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    interface Foo {
                        /**
                         * @return T
                         */
                        public function getItem();
                    }

                    trait FooTrait {
                        public function getItem() {
                            return "hello";
                        }
                    }

                    /**
                     * @template-implements Foo<string>
                     */
                    class Bar implements Foo {
                        use FooTrait;
                    }',
            ],
            'useTraitReturnTypeForInheritedInterface' => [
                'code' => '<?php
                    /**
                     * @template TValue
                     * @template TNormalizedValue
                     */
                    interface Normalizer
                    {
                        /**
                         * @param TValue $v
                         * @return TNormalizedValue
                         */
                        function normalize($v);
                    }

                    /**
                     * @template TTraitValue
                     * @template TTraitNormalizedValue
                     */
                    trait NormalizerTrait
                    {
                        /**
                         * @param TTraitValue $v
                         * @return TTraitNormalizedValue
                         */
                        function normalize($v)
                        {
                            return $this->doNormalize($v);
                        }

                        /**
                         * @param TTraitValue $v
                         * @return TTraitNormalizedValue
                         */
                        abstract protected function doNormalize($v);
                    }

                    /** @implements Normalizer<string, string> */
                    class StringNormalizer implements Normalizer
                    {
                        /** @use NormalizerTrait<string, string> */
                        use NormalizerTrait;

                        protected function doNormalize($v): string
                        {
                            return trim($v);
                        }
                    }',
            ],
            'useTraitReturnTypeForInheritedClass' => [
                'code' => '<?php
                    /**
                     * @template TValue
                     * @template TNormalizedValue
                     */
                    abstract class Normalizer
                    {
                        /**
                         * @param TValue $v
                         * @return TNormalizedValue
                         */
                        abstract function normalize($v);
                    }

                    /**
                     * @template TTraitValue
                     * @template TTraitNormalizedValue
                     */
                    trait NormalizerTrait
                    {
                        /**
                         * @param TTraitValue $v
                         * @return TTraitNormalizedValue
                         */
                        function normalize($v)
                        {
                            return $this->doNormalize($v);
                        }

                        /**
                         * @param TTraitValue $v
                         * @return TTraitNormalizedValue
                         */
                        abstract protected function doNormalize($v);
                    }

                    /** @extends Normalizer<string, string> */
                    class StringNormalizer extends Normalizer
                    {
                        /** @use NormalizerTrait<string, string> */
                        use NormalizerTrait;

                        protected function doNormalize($v): string
                        {
                            return trim($v);
                        }
                    }',
            ],
            'inheritTraitPropertyTKeyedArray' => [
                'code' => '<?php
                    /** @template TValue */
                    trait A {
                        /** @psalm-var array{TValue} */
                        private $foo;

                        /** @psalm-param array{TValue} $foo */
                        public function __construct(array $foo)
                        {
                            $this->foo = $foo;
                        }
                    }

                    /** @template TValue */
                    class B {
                        /** @use A<TValue> */
                        use A;
                    }',
            ],
            'inheritTraitPropertyArray' => [
                'code' => '<?php
                    /** @template TValue */
                    trait A {
                        /** @psalm-var array<TValue> */
                        private $foo;

                        /** @psalm-param array<TValue> $foo */
                        public function __construct(array $foo)
                        {
                            $this->foo = $foo;
                        }
                    }

                    /** @template TValue */
                    class B {
                        /** @use A<TValue> */
                        use A;
                    }',
            ],
            'applyTemplatedValueInTraitProperty' => [
                'code' => '<?php
                    /** @template T */
                    trait ValueTrait {
                        /** @psalm-param T $value */
                        public function setValue($value): void {
                            $this->value = $value;
                        }
                    }

                    class C {
                        /** @use ValueTrait<string> */
                        use ValueTrait;

                        /** @var string */
                        private $value;

                        public function __construct(string $value) {
                            $this->value = $value;
                        }
                    }',
            ],
            'traitSelfAsParam' => [
                'code' => '<?php
                    trait InstancePool {
                        /**
                         * @template T as self
                         * @param callable():?T $callback
                         * @return ?T
                         */
                        public static function getInstance(callable $callback)
                        {
                            return $callback();
                        }
                    }

                    class Foo
                    {
                        use InstancePool;
                    }

                    class Bar
                    {
                        public function a(): void
                        {
                            Foo::getInstance(function () {
                                return new Foo();
                            });
                        }
                    }',
            ],
            'templateExtendedGenericTrait' => [
                'code' => '<?php
                    /**
                     * @template F
                     */
                    trait Foo {
                        /**
                         * @param callable(F): int $callback
                         */
                        public function bar(callable $callback): int {
                            return $callback($this->get());
                        }
                    }

                    /**
                     * @template B
                     */
                    class Bar {

                        /**
                         * @use Foo<B>
                         */
                        use Foo;

                        /**
                         * @param B $value
                         */
                        public function __construct(public mixed $value) { }

                        /**
                         * @return B
                         */
                        public function get() {
                            return $this->value;
                        }
                    }',
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
            'receiverWithoutTypeArgumentsOfAGenericClassUsingATraitStillChecksTheBound' => [
                'code' => '<?php
                    /**
                     * @template TKey of array-key
                     * @template TValue
                     * @extends ArrayAccess<TKey, TValue>
                     */
                    interface ArrayInterface extends ArrayAccess {}

                    trait IsIterable {
                        public function offsetSet(mixed $offset, mixed $value): void {}
                        public function offsetGet(mixed $offset): mixed { return null; }
                        public function offsetExists(mixed $offset): bool { return false; }
                        public function offsetUnset(mixed $offset): void {}
                    }

                    /**
                     * @template TKey of array-key
                     * @template TValue
                     * @implements ArrayInterface<TKey, TValue>
                     */
                    final class ImmutableArray implements ArrayInterface {
                        use IsIterable;
                    }

                    final class Element {
                        private ImmutableArray $scopedVariables;

                        public function __construct() {
                            $this->scopedVariables = new ImmutableArray();
                        }

                        public function add(): void {
                            $this->scopedVariables[1.5] = "x";
                        }
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:33:52 - Argument 1 of ImmutableArray::offsetSet expects array-key|null, but float(1.5) provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'subclassOfAGenericClassUsingATraitChecksTheKeyType' => [
                'code' => '<?php
                    /**
                     * @template TKey of array-key
                     * @template TValue
                     * @extends ArrayAccess<TKey, TValue>
                     */
                    interface ArrayInterface extends ArrayAccess {}

                    trait IsArrayAccess {
                        public function offsetSet(mixed $offset, mixed $value): void {}
                        public function offsetGet(mixed $offset): mixed { return null; }
                        public function offsetExists(mixed $offset): bool { return false; }
                        public function offsetUnset(mixed $offset): void {}
                    }

                    /**
                     * @template TKey of array-key
                     * @template TValue
                     * @implements ArrayInterface<TKey, TValue>
                     */
                    abstract class AbstractArray implements ArrayInterface {
                        use IsArrayAccess;
                    }

                    final class ImmutableString {}

                    /** @extends AbstractArray<string, ImmutableString> */
                    final class Concrete extends AbstractArray {}

                    function f(Concrete $c): void {
                        $c[5] = new ImmutableString();
                    }',
                'error_message' => 'Argument 1 of Concrete::offsetSet expects null|string, but 5 provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'subclassOfAGenericClassUsingATraitChecksTheValueType' => [
                'code' => '<?php
                    /**
                     * @template TKey of array-key
                     * @template TValue
                     * @extends ArrayAccess<TKey, TValue>
                     */
                    interface ArrayInterface extends ArrayAccess {}

                    trait IsArrayAccess {
                        public function offsetSet(mixed $offset, mixed $value): void {}
                        public function offsetGet(mixed $offset): mixed { return null; }
                        public function offsetExists(mixed $offset): bool { return false; }
                        public function offsetUnset(mixed $offset): void {}
                    }

                    /**
                     * @template TKey of array-key
                     * @template TValue
                     * @implements ArrayInterface<TKey, TValue>
                     */
                    abstract class AbstractArray implements ArrayInterface {
                        use IsArrayAccess;
                    }

                    final class ImmutableString {}

                    /** @extends AbstractArray<string, ImmutableString> */
                    final class Concrete extends AbstractArray {}

                    function f(Concrete $c): void {
                        $c["key"] = "not an ImmutableString";
                    }',
                'error_message' => 'Argument 2 of Concrete::offsetSet expects ImmutableString, but',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'genericClassImplementingAnInterfaceThroughATraitChecksTheKeyType' => [
                'code' => '<?php
                    trait IsArrayAccess {
                        public function offsetSet(mixed $offset, mixed $value): void {}
                        public function offsetGet(mixed $offset): mixed { return null; }
                        public function offsetExists(mixed $offset): bool { return false; }
                        public function offsetUnset(mixed $offset): void {}
                    }

                    /**
                     * @template TKey of array-key
                     * @template TValue
                     * @implements ArrayAccess<TKey, TValue>
                     */
                    final class ImmutableArray implements ArrayAccess {
                        use IsArrayAccess;
                    }

                    final class ImmutableString {}

                    /** @param ImmutableArray<string, ImmutableString> $suggestions */
                    function f(ImmutableArray $suggestions): void {
                        $suggestions[5] = new ImmutableString();
                    }',
                'error_message' => 'Argument 1 of ImmutableArray::offsetSet expects null|string, but 5 provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'genericClassImplementingAnInterfaceThroughATraitChecksTheValueType' => [
                'code' => '<?php
                    trait IsArrayAccess {
                        public function offsetSet(mixed $offset, mixed $value): void {}
                        public function offsetGet(mixed $offset): mixed { return null; }
                        public function offsetExists(mixed $offset): bool { return false; }
                        public function offsetUnset(mixed $offset): void {}
                    }

                    /**
                     * @template TKey of array-key
                     * @template TValue
                     * @implements ArrayAccess<TKey, TValue>
                     */
                    final class ImmutableArray implements ArrayAccess {
                        use IsArrayAccess;
                    }

                    final class ImmutableString {}

                    /** @param ImmutableArray<string, ImmutableString> $suggestions */
                    function f(ImmutableArray $suggestions): void {
                        $suggestions["key"] = "not an ImmutableString";
                    }',
                'error_message' => 'Argument 2 of ImmutableArray::offsetSet expects ImmutableString, but',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'traitMethodWithItsOwnDocblockIsComparedWithTheParent' => [
                'code' => '<?php
                    /** @template T */
                    abstract class A
                    {
                        /**
                         * @param T $s
                         * @return T
                         */
                        abstract public function someMethod(mixed $s): mixed;
                    }

                    trait LetsTrait
                    {
                        /**
                         * @param mixed $s
                         * @return mixed
                         */
                        public function someMethod(mixed $s): mixed
                        {
                            return $s;
                        }
                    }

                    /** @extends A<string> */
                    class C extends A
                    {
                        use LetsTrait;
                    }',
                'error_message' => "The inherited return type 'string' for A::someMethod",
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'traitMethodWithoutDocblockCalledWithAnArgumentOfTheWrongType' => [
                'code' => '<?php
                    /** @template T */
                    abstract class A
                    {
                        /**
                         * @param T $s
                         * @return T
                         */
                        abstract public function someMethod(mixed $s): mixed;
                    }

                    trait LetsTrait
                    {
                        public function someMethod(mixed $s): mixed
                        {
                            return $s;
                        }
                    }

                    /** @extends A<string> */
                    class C extends A
                    {
                        use LetsTrait;
                    }

                    (new C())->someMethod(5);',
                'error_message' => 'Argument 1 of C::someMethod expects string, but 5 provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'traitMethodWithoutDocblockReturningNullForATemplatedParent' => [
                'code' => '<?php
                    /** @template T */
                    abstract class A
                    {
                        /** @return T */
                        abstract public function get(): mixed;
                    }

                    trait GetTrait
                    {
                        public function get(): ?string
                        {
                            return null;
                        }
                    }

                    /** @extends A<string> */
                    class C extends A
                    {
                        use GetTrait;
                    }',
                'error_message' => 'InvalidNullableReturnType',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'traitMethodWithoutDocblockImplementingTwoInterfacesDocumentingTheMethod' => [
                'code' => '<?php
                    interface I1
                    {
                        /** @return non-empty-string */
                        public function f(): string;
                    }

                    interface I2
                    {
                        /** @return numeric-string */
                        public function f(): string;
                    }

                    trait T
                    {
                        public function f(): string
                        {
                            return "1";
                        }
                    }

                    final class E implements I1, I2
                    {
                        use T;
                    }',
                'error_message' => "The inherited return type 'non-empty-string' for I1::f",
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'badTemplateUse' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    trait T {
                        /** @var T */
                        public $t;

                        /** @param T $t */
                        public function __construct($t) {
                            $this->t = $t;
                        }
                    }

                    /**
                     * @template TT
                     */
                    class B {
                        /**
                         * @template-use T<Z>
                         */
                        use T;
                    }',
                'error_message' => 'UndefinedDocblockClass',
            ],
            'badTemplateUseBadFormat' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    trait T {
                        /** @var T */
                        public $t;

                        /** @param T $t */
                        public function __construct($t) {
                            $this->t = $t;
                        }
                    }

                    /**
                     * @template TT
                     */
                    class B {
                        /**
                         * @template-use T< >
                         */
                        use T;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'badTemplateUseInt' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    trait T {
                        /** @var T */
                        public $t;

                        /** @param T $t */
                        public function __construct($t) {
                            $this->t = $t;
                        }
                    }

                    /**
                     * @template TT
                     */
                    class B {
                        /**
                         * @template-use int
                         */
                        use T;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'badTemplateExtendsShouldBeUse' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    trait T {
                        /** @var T */
                        public $t;

                        /** @param T $t */
                        public function __construct($t) {
                            $this->t = $t;
                        }
                    }

                    /**
                     * @template TT
                     */
                    class B {
                        /**
                         * @template-extends T<int>
                         */
                        use T;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'possiblyNullReferenceOnTraitDefinedMethod' => [
                'code' => '<?php
                    /**
                     * @template TKey as array-key
                     * @template TValue
                     */
                    trait T1 {
                        /**
                         * @var array<TKey, TValue>
                         */
                        protected $mocks = [];

                        /**
                         * @param TKey $offset
                         * @return TValue|null
                         * @psalm-suppress LessSpecificImplementedReturnType
                         * @psalm-suppress ImplementedParamTypeMismatch
                         */
                        public function offsetGet($offset) {
                            return $this->mocks[$offset] ?? null;
                        }
                    }

                    /**
                     * @template TKey as array-key
                     * @template TValue
                     */
                    interface Arr {
                        /**
                         * @param TKey $offset
                         * @return TValue|null
                         */
                        public function offsetGet($offset);
                    }

                    /**
                     * @template TKey as array-key
                     * @template TValue
                     * @implements Arr<TKey, TValue>
                     */
                    class C implements Arr {
                        /** @use T1<TKey, TValue> */
                        use T1;

                        /**
                         * @param TKey $offset
                         * @psalm-suppress MixedMethodCall
                         */
                        public function foo($offset) : void {
                            $this->offsetGet($offset)->bar();
                        }
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'possiblyNullReferenceOnTraitDefinedMethodExtended' => [
                'code' => '<?php
                    /**
                     * @template TKey as array-key
                     * @template TValue
                     */
                    trait T1 {
                        /**
                         * @var array<TKey, TValue>
                         */
                        protected $mocks = [];

                        /**
                         * @param TKey $offset
                         * @return TValue|null
                         * @psalm-suppress LessSpecificImplementedReturnType
                         * @psalm-suppress ImplementedParamTypeMismatch
                         */
                        public function offsetGet($offset) {
                            return $this->mocks[$offset] ?? null;
                        }
                    }

                    /**
                     * @template TKey as array-key
                     * @template TValue
                     */
                    interface Arr {
                        /**
                         * @param TKey $offset
                         * @return TValue|null
                         */
                        public function offsetGet($offset);
                    }

                    /**
                     * @template TKey as array-key
                     * @template TValue
                     * @implements Arr<TKey, TValue>
                     */
                    class C implements Arr {
                        /** @use T1<TKey, TValue> */
                        use T1;
                    }

                    /**
                     * @psalm-suppress MissingTemplateParam
                     */
                    class D extends C {
                        /**
                         * @param mixed $offset
                         * @psalm-suppress MixedArgument
                         */
                        public function foo($offset) : void {
                            $this->offsetGet($offset)->bar();
                        }
                    }',
                'error_message' => 'MixedMethodCall',
            ],
        ];
    }
}
