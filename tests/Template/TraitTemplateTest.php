<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class TraitTemplateTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
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

    public function providerInvalidCodeParse(): iterable
    {
        return [
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
