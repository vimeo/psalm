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
            'argumentRebuiltForACallableParamKeepsTheTraitTemplateInTheScope' => [
                'code' => '<?php
                    function takesCallable(callable $c): void {
                        $c();
                    }

                    /** @template T of callable */
                    trait Leak {
                        /** @param T $x */
                        public function h($x): void {
                            takesCallable($x);
                            if ($x instanceof Closure) {
                                echo "closure";
                            }
                        }
                    }

                    final class LeakClosure {
                        /** @use Leak<Closure> */
                        use Leak;
                    }

                    /** @template T */
                    trait Leak2 {
                        /** @param T $x */
                        public function h2($x): string {
                            takesCallable($x);
                            if (is_string($x)) {
                                return $x;
                            }
                            return "o";
                        }
                    }

                    final class LeakStr {
                        /** @use Leak2<callable-string> */
                        use Leak2;
                    }',
            ],
            'traitMethodParamIsTheTypeGivenToUse' => [
                'code' => '<?php
                    /** @template T of object */
                    trait Foo {
                        /**
                         * @param T $data
                         * @return T
                         */
                        function a($data) {
                            return $this->b($data);
                        }
                    }

                    class Bar {
                        /** @use Foo<DateTime> */
                        use Foo;

                        function b(DateTime $data): DateTime {
                            return $data;
                        }
                    }',
            ],
            'traitMethodNarrowsItsTemplateWhateverTheUseTypeIs' => [
                'code' => '<?php
                    function takesString(string $s): void {
                        echo $s;
                    }

                    function takesInt(int $i): void {
                        echo $i;
                    }

                    /** @template T */
                    trait Red {
                        /** @param T $x */
                        public function f($x): void {
                            if (is_string($x)) {
                                takesString($x);
                            }
                            if (is_int($x)) {
                                takesInt($x);
                            }
                        }
                    }

                    final class RedStr {
                        /** @use Red<string> */
                        use Red;
                    }

                    final class RedInt {
                        /** @use Red<int> */
                        use Red;
                    }',
            ],
            'traitMethodNarrowedTemplateIsStillCheckedAfterACall' => [
                'code' => '<?php
                    function takesString(string $s): void {
                        echo $s;
                    }

                    /** @template T */
                    trait Leak {
                        /** @param T $x */
                        public function g($x): string {
                            if (is_string($x)) {
                                takesString($x);

                                return $x;
                            }

                            return "";
                        }
                    }

                    final class LeakStr {
                        /** @use Leak<string> */
                        use Leak;
                    }',
            ],
            'traitMethodArgumentKeepsItsTemplateTypeAfterTheCall' => [
                'code' => '<?php
                    function ts(string $s): void {
                        echo $s;
                    }

                    function ti(int $i): void {
                        echo $i;
                    }

                    /** @template T */
                    trait Leak {
                        /** @param T $x */
                        public function h($x): string {
                            ts($x);
                            if (is_string($x)) {
                                return $x;
                            }
                            return "o";
                        }

                        /** @param T $x */
                        public function h2($x): void {
                            ts($x);
                            if (is_int($x)) {
                                ti($x);
                            }
                        }
                    }

                    final class LeakStr {
                        /** @use Leak<string> */
                        use Leak;
                    }',
            ],
            'traitMethodArgumentOfATemplateUsedAsMixedKeepsItsTemplateTypeAfterTheCall' => [
                'code' => '<?php
                    function ts(string $s): void {
                        echo $s;
                    }

                    /** @template T */
                    trait Leak {
                        /** @param T $x */
                        public function h($x): string {
                            ts($x);
                            if (is_string($x)) {
                                return $x;
                            }
                            return "o";
                        }
                    }

                    final class LeakMixed {
                        /** @use Leak<mixed> */
                        use Leak;
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArgument'],
            ],
            'traitMethodPassesItsTemplatedArrayAndCallbackToATemplatedFunction' => [
                'code' => '<?php
                    /**
                     * @template TIn of array-key
                     * @template TVal
                     * @template TInit
                     * @template TRet
                     * @param array<TIn, TVal> $arr
                     * @param callable(TInit|TRet, TVal, TIn): TRet $callback
                     * @param TInit $initial
                     * @return TInit|TRet
                     */
                    function red(array $arr, callable $callback, mixed $initial = null): mixed {
                        $acc = $initial;
                        foreach ($arr as $k => $v) {
                            $acc = $callback($acc, $v, $k);
                        }

                        return $acc;
                    }

                    /**
                     * @template TKey of array-key
                     * @template TValue
                     */
                    trait Manip {
                        /** @var array<TKey, TValue> */
                        private array $value = [];

                        /**
                         * @template TInit
                         * @template TRet
                         * @param callable(TInit|TRet, TValue, TKey): TRet $callback
                         * @param TInit $initial
                         * @return TInit|TRet
                         */
                        public function reduce(callable $callback, mixed $initial = null): mixed {
                            return red($this->value, $callback, $initial);
                        }
                    }

                    /**
                     * @template TKey of array-key
                     * @template TValue
                     */
                    final class Arr {
                        /** @use Manip<TKey, TValue> */
                        use Manip;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'traitMethodMayBranchOnItsTemplateWhateverTheUseTypeIs' => [
                'code' => '<?php
                    /** @template T */
                    trait Red {
                        /** @param T $x */
                        public function f($x): string {
                            if (is_string($x)) {
                                return $x;
                            }

                            return "other";
                        }
                    }

                    final class RedStr {
                        /** @use Red<string> */
                        use Red;
                    }

                    final class RedInt {
                        /** @use Red<int> */
                        use Red;
                    }',
            ],
            'traitMethodMayTakeItsTemplateByReference' => [
                'code' => '<?php
                    /** @template T */
                    trait Ref {
                        /** @param T $d */
                        public function r(&$d): void {
                            echo "x";
                        }
                    }

                    final class RefCls {
                        /** @use Ref<int> */
                        use Ref;
                    }',
            ],
            'traitTemplateIsBoundInAListParamAndInANestedTrait' => [
                'code' => '<?php
                    /** @template U of object */
                    trait Inner {
                        /** @param U $d */
                        public function inner($d): void {
                            $this->take($d);
                        }
                    }

                    /** @template T of object */
                    trait Outer {
                        /** @use Inner<T> */
                        use Inner;

                        /** @param list<T> $ds */
                        public function outer(array $ds): void {
                            foreach ($ds as $d) {
                                $this->take($d);
                            }
                        }
                    }

                    final class A {
                        /** @use Outer<DateTime> */
                        use Outer;

                        public function take(DateTime $d): void {}
                    }',
            ],
            'traitUsedWithDifferentTypesBindsEachOfThem' => [
                'code' => '<?php
                    /** @template T of object */
                    trait Foo {
                        /** @param T $data */
                        function a($data): void {
                            $this->b($data);
                        }
                    }

                    class Bar {
                        /** @use Foo<DateTime> */
                        use Foo;

                        function b(DateTime $data): void {}
                    }

                    class Baz {
                        /** @use Foo<stdClass> */
                        use Foo;

                        function b(stdClass $data): void {}
                    }',
            ],
            'closureReturnTypeNamesTraitTemplate' => [
                'code' => '<?php
                    interface a {}
                    class b implements a {}

                    /**
                     * @template T as a
                     */
                    trait c {
                        /** @return T */
                        abstract public function get(): a;

                        public function getCb(): callable {
                            return
                                /** @return T */
                                function (): a {
                                    return $this->get();
                                };
                        }
                    }

                    class d {
                        /** @use c<b> */
                        use c;

                        public function get(): b {
                            return new b;
                        }
                    }',
            ],
            'closureReturnTypeNamesTraitTemplateOfACapturedValue' => [
                'code' => '<?php
                    interface a {}
                    final class b implements a {}

                    /** @template T of a */
                    trait e {
                        /** @param T $p */
                        public function m($p): Closure {
                            return /** @return T */ function () use ($p) { return $p; };
                        }

                        public function n(): Closure {
                            return /** @return T */ function () {
                                /** @var T $t */
                                $t = $this->make();
                                return $t;
                            };
                        }

                        /** @param T $p */
                        public function o($p, bool $b): Closure {
                            return /** @return T */ function () use ($p, $b) {
                                if ($b) {
                                    return $p;
                                }

                                return $this->make();
                            };
                        }

                        abstract public function make(): a;
                    }

                    final class f {
                        /** @use e<b> */
                        use e;

                        public function make(): b {
                            return new b;
                        }
                    }',
            ],
            'closureReturnTypeNamesInheritedTemplate' => [
                'code' => '<?php
                    interface a {}
                    class b implements a {}

                    /**
                     * @template T as a
                     */
                    abstract class Base {
                        /** @return T */
                        abstract public function get(): a;

                        public function getCb(): callable {
                            return
                                /** @return T */
                                function (): a {
                                    return $this->get();
                                };
                        }
                    }

                    /** @extends Base<b> */
                    final class Child extends Base {
                        public function get(): b {
                            return new b;
                        }
                    }',
            ],
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
            'insteadofTraitMethodWithoutDocblockBindsTheMethodTemplateOfTheParent' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Query {}

                    trait Original
                    {
                        /**
                         * @template T of object
                         * @param class-string<T> $class
                         * @param Closure(T, Query<T>): void $callback
                         */
                        public function each(string $class, Closure $callback): void {}
                    }

                    trait Replacement
                    {
                        public function each($class, $callback): void {}
                    }

                    class Base
                    {
                        use Original;
                    }

                    final class Box extends Base
                    {
                        use Original, Replacement {
                            Replacement::each insteadof Original;
                        }
                    }

                    function takesStdClass(stdClass $_s): void {}

                    /** @param Query<stdClass> $_q */
                    function takesQuery(Query $_q): void {}

                    function f(Box $box): void
                    {
                        $box->each(stdClass::class, function ($item, $query): void {
                            takesStdClass($item);
                            takesQuery($query);
                        });
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
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
            'traitMethodParamIsNotAcceptedWhereTheUseTypeDoesNotFit' => [
                'code' => '<?php
                    /** @template T of object */
                    trait Foo {
                        /** @param T $data */
                        function a($data): void {
                            $this->b($data);
                        }
                    }

                    class Bar {
                        /** @use Foo<DateTime> */
                        use Foo;

                        function b(stdClass $data): void {}
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:38 - '
                    . 'Argument 1 of Bar::b expects stdClass, but DateTime provided',
            ],
            'covariantTraitTemplateInAParameterIsStillReportedWithAConcreteUse' => [
                'code' => '<?php
                    /** @template-covariant TValue */
                    trait EV {
                        /** @param TValue $v */
                        public function has($v): bool { return (bool) $v; }
                    }

                    class Coll {
                        /** @use EV<int> */
                        use EV;
                    }',
                'error_message' => 'InvalidTemplateParam',
            ],
            'covariantClassTemplateInAParameterIsStillReportedWhereTheTraitIsUsedWithIt' => [
                'code' => '<?php
                    /** @template-covariant TValue */
                    trait EV {
                        /** @param TValue $v */
                        public function has($v): bool { return (bool) $v; }
                    }

                    /** @template-covariant TValue */
                    class Coll {
                        /** @use EV<TValue> */
                        use EV;
                    }',
                'error_message' => 'InvalidTemplateParam',
            ],
            'closureReturnTypeNamesTraitTemplateStillChecked' => [
                'code' => '<?php
                    interface a {}
                    class b implements a {}
                    class other implements a {}

                    /**
                     * @template T as a
                     */
                    trait c {
                        public function getCb(): callable {
                            return
                                /** @return T */
                                function (): a {
                                    return new other;
                                };
                        }
                    }

                    class d {
                        /** @use c<b> */
                        use c;
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
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
