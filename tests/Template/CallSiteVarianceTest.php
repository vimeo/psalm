<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class CallSiteVarianceTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    private const HIERARCHY = '
        class A {}
        class B extends A {}
        class C extends B {}

        /** @template T */
        class Box {}

        /** @template-covariant T */
        class ReadOnlyBox {}
    ';

    private const PROJECTIONS = '
        /** @template T */
        interface PBox
        {
            /** @param T $item */
            public function pack($item): void;

            /** @return T */
            public function unpack();

            /** @return PBox<T> */
            public function me();
        }

        /** @template T of A */
        interface BoundedBox
        {
            /** @return T */
            public function unpack();
        }

        /** @template T */
        interface Mapped
        {
            /** @param callable(T): void $mapper */
            public function mapIn(callable $mapper): void;

            /** @param callable(): T $mapper */
            public function mapOut(callable $mapper): void;
        }
';

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'covariantContainerAcceptsSubtypes' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @param Box<covariant B> $_box */
                    function takes(Box $_box): void {}

                    /**
                     * @param Box<B> $b
                     * @param Box<C> $c
                     * @param Box<covariant B> $cb
                     * @param Box<covariant C> $cc
                     */
                    function test(Box $b, Box $c, Box $cb, Box $cc): void {
                        takes($b);
                        takes($c);
                        takes($cb);
                        takes($cc);
                    }',
            ],
            'contravariantContainerAcceptsSupertypes' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @param Box<contravariant B> $_box */
                    function takes(Box $_box): void {}

                    /**
                     * @param Box<A> $a
                     * @param Box<B> $b
                     * @param Box<contravariant A> $ca
                     */
                    function test(Box $a, Box $b, Box $ca): void {
                        takes($a);
                        takes($b);
                        takes($ca);
                    }',
            ],
            'starContainerAcceptsEverything' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @param Box<*> $_box */
                    function takes(Box $_box): void {}

                    /**
                     * @param Box<A> $a
                     * @param Box<int> $i
                     * @param Box<covariant A> $ca
                     * @param Box<contravariant A> $xa
                     * @param Box<*> $star
                     */
                    function test(Box $a, Box $i, Box $ca, Box $xa, Box $star): void {
                        takes($a);
                        takes($i);
                        takes($ca);
                        takes($xa);
                        takes($star);
                    }',
            ],
            'callSiteVarianceMatchingTheDeclaredOneIsNotAPosition' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @param ReadOnlyBox<A> $_box */
                    function takes(ReadOnlyBox $_box): void {}

                    /** @param ReadOnlyBox<covariant B> $box */
                    function test(ReadOnlyBox $box): void {
                        takes($box);
                    }',
                'assertions' => [],
                'ignored_issues' => ['RedundantCallSiteVariance'],
            ],
            'covariantTemplateInferredFromSubtypeAndOtherArgument' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /**
                     * @template T
                     * @param Box<covariant T> $_box
                     * @param T $other
                     * @return T
                     */
                    function pick(Box $_box, $other) { return $other; }

                    /** @param Box<B> $b */
                    function test(Box $b): void {
                        $result = pick($b, new A());
                        /** @psalm-check-type-exact $result = A */
                    }',
            ],
            'covariantTemplateArgumentInferredFromTheArgument' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /**
                     * @template T
                     * @param Box<covariant T> $_box
                     * @return T
                     */
                    function unbox(Box $_box) { throw new Exception(); }

                    /** @param Box<C> $c */
                    function test(Box $c): void {
                        $result = unbox($c);
                        /** @psalm-check-type-exact $result = C */
                    }',
            ],
            'starAndClassNamedCovariantInNamespace' => [
                'code' => '<?php
                    namespace N;

                    class covariant {}

                    /** @template T */
                    class NBox {}

                    /**
                     * @param NBox<*> $_star
                     * @param NBox<covariant> $_named
                     */
                    function takes(NBox $_star, NBox $_named): void {}',
            ],
            'ancestorArgumentKeepsCallSiteVariance' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /**
                     * @template U
                     * @extends Box<U>
                     */
                    class SubBox extends Box {}

                    /** @param Box<covariant B> $_box */
                    function takes(Box $_box): void {}

                    /** @param SubBox<covariant B> $sub */
                    function test(SubBox $sub): void {
                        takes($sub);
                    }',
            ],
            'ancestorWithCompositeArgumentFitsOnlyAStar' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /**
                     * @template U
                     * @extends Box<array<U>>
                     */
                    class ArraysBox extends Box {}

                    /** @param Box<*> $_box */
                    function takes(Box $_box): void {}

                    /** @param ArraysBox<covariant B> $sub */
                    function test(ArraysBox $sub): void {
                        takes($sub);
                    }',
            ],
            'covariantArgumentInferredIntoCovariantMethodTemplate' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /**
                     * @template T
                     * @param Box<covariant T> $_box
                     * @return T
                     */
                    function unbox(Box $_box) { throw new Exception(); }

                    /** @param Box<covariant C> $box */
                    function test(Box $box): void {
                        $result = unbox($box);
                        /** @psalm-check-type-exact $result = C */
                    }',
            ],
            'contravariantContainerInfersTheArgument' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /**
                     * @template T
                     * @param Box<contravariant T> $_box
                     * @return T
                     */
                    function sink(Box $_box) { throw new Exception(); }

                    /** @param Box<A> $box */
                    function test(Box $box): void {
                        $result = sink($box);
                        /** @psalm-check-type-exact $result = A */
                    }',
            ],
            'projectionCovariantReadsTheArgument' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @param PBox<covariant B> $box */
                    function test(PBox $box): void {
                        $result = $box->unpack();
                        /** @psalm-check-type-exact $result = B */
                    }',
            ],
            'projectionContravariantAcceptsTheArgumentAndReadsMixed' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @param PBox<contravariant B> $box */
                    function test(PBox $box, A $a, C $c): void {
                        $box->pack($c);
                        $result = $box->unpack();
                        /** @psalm-check-type-exact $result = mixed */
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'projectionContravariantReadsTheBound' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @param BoundedBox<contravariant B> $box */
                    function test(BoundedBox $box): void {
                        $result = $box->unpack();
                        /** @psalm-check-type-exact $result = A */
                    }',
            ],
            'projectionStarReadsMixed' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @param PBox<*> $box */
                    function test(PBox $box): void {
                        $result = $box->unpack();
                        /** @psalm-check-type-exact $result = mixed */
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'projectionCovariantCallbackParamIsTheArgument' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @param Mapped<covariant B> $mapped */
                    function test(Mapped $mapped): void {
                        $mapped->mapIn(function ($foo) {
                            /** @psalm-check-type-exact $foo = B */
                        });
                    }',
            ],
            'projectionContravariantCallbackParamIsMixed' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /**
                     * @param Mapped<contravariant B> $contra
                     * @param Mapped<*> $star
                     */
                    function test(Mapped $contra, Mapped $star): void {
                        $contra->mapIn(function ($foo) {
                            /** @psalm-check-type-exact $foo = mixed */
                        });
                        $star->mapIn(function ($foo) {
                            /** @psalm-check-type-exact $foo = mixed */
                        });
                    }',
            ],
            'projectionContravariantCallbackReturnAcceptsSubtypes' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @param Mapped<contravariant B> $mapped */
                    function test(Mapped $mapped): void {
                        $mapped->mapOut(fn() => new B());
                        $mapped->mapOut(fn() => new C());
                    }',
            ],
            'projectionKeepsCallSiteVarianceOfAnInvariantArgumentRead' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @param PBox<covariant B> $box */
                    function test(PBox $box): void {
                        $self = $box->me();
                        /** @psalm-check-type-exact $self = PBox<covariant B> */
                    }',
            ],
            'projectionThroughAnAncestor' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /**
                     * @template U
                     * @implements PBox<U>
                     */
                    abstract class SubPBox implements PBox {}

                    /** @param SubPBox<covariant B> $box */
                    function test(SubPBox $box): void {
                        $result = $box->unpack();
                        /** @psalm-check-type-exact $result = B */
                    }',
            ],
            'projectionOfAPropertyRead' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @template T */
                    class Holder {
                        /** @var T */
                        public $value;
                        /** @param T $value */
                        public function __construct($value) { $this->value = $value; }
                    }

                    /** @param Holder<covariant B> $h */
                    function test(Holder $h): void {
                        $result = $h->value;
                        /** @psalm-check-type-exact $result = B */
                    }',
            ],
            'projectionOfAPropertyWriteWithAContravariantArgument' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @template T */
                    class Holder {
                        /** @var T */
                        public $value;
                        /** @param T $value */
                        public function __construct($value) { $this->value = $value; }
                    }

                    /** @param Holder<contravariant B> $h */
                    function test(Holder $h, C $c): void {
                        $h->value = $c;
                    }',
            ],
            'projectionOfAFirstClassCallable' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @param PBox<covariant B> $box */
                    function test(PBox $box): void {
                        $unpack = $box->unpack(...);
                        $pack = $box->pack(...);
                        /** @psalm-check-type-exact $unpack = Closure():B */
                        /** @psalm-check-type-exact $pack = Closure(never):void */
                    }',
            ],
            'projectionOfAPseudoMethod' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /**
                     * @template T
                     * @method T get()
                     */
                    class Magic { public function __call(string $name, array $args) { return null; } }

                    /** @param Magic<contravariant B> $magic */
                    function test(Magic $magic): void {
                        $result = $magic->get();
                        /** @psalm-check-type-exact $result = mixed */
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'projectionThroughAMixin' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /**
                     * @template T
                     * @mixin PBox<T>
                     */
                    class Wrapper {}

                    /** @param Wrapper<covariant B> $w */
                    function test(Wrapper $w): void {
                        $result = $w->unpack();
                        /** @psalm-check-type-exact $result = B */
                    }',
            ],
            'projectionOnATemplateWithACallSiteVarianceBound' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /**
                     * @template X of PBox<covariant B>
                     * @param X $box
                     */
                    function test(PBox $box): void {
                        $result = $box->unpack();
                        /** @psalm-check-type-exact $result = B */
                    }',
            ],
            'projectionOnAnIntersection' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @param PBox<covariant B>&Countable $box */
                    function test(PBox $box): void {
                        $result = $box->unpack();
                        /** @psalm-check-type-exact $result = B */
                    }',
            ],
            'projectionOnAnInstanceofNarrowedReceiver' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /**
                     * @template U
                     * @implements PBox<U>
                     */
                    abstract class ImplBox implements PBox {}

                    /** @param PBox<covariant B> $box */
                    function test(PBox $box): void {
                        if ($box instanceof ImplBox) {
                            $result = $box->unpack();
                            /** @psalm-check-type-exact $result = B */
                        }
                    }',
            ],
            'projectionOfAForeachOverAContravariantIterator' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /**
                     * @template T
                     * @implements IteratorAggregate<int, T>
                     */
                    abstract class Collection implements IteratorAggregate {}

                    /** @param Collection<contravariant B> $iterable */
                    function test(Collection $iterable): void {
                        foreach ($iterable as $value) {
                            /** @psalm-check-type-exact $value = mixed */
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'projectionOfAForeachOverACovariantIterator' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /**
                     * @template T
                     * @implements IteratorAggregate<int, T>
                     */
                    abstract class Collection implements IteratorAggregate {}

                    /** @param Collection<covariant B> $iterator */
                    function test(Collection $iterator): void {
                        foreach ($iterator as $value) {
                            /** @psalm-check-type-exact $value = B */
                        }
                    }',
            ],
            'starAndExemptedCallSiteVariancesAreNotReported' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @template-covariant T of A */
                    class BoundedReadOnlyBox {}

                    /**
                     * @param ReadOnlyBox<*> $_star
                     * @param BoundedReadOnlyBox<*> $_bounded
                     * @param ReflectionClass<covariant B> $_reflection
                     */
                    function takes(ReadOnlyBox $_star, BoundedReadOnlyBox $_bounded, ReflectionClass $_reflection): void {}',
            ],
            'callSiteVarianceFollowsPositionsInACovariantClass' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @template-covariant T */
                    abstract class Source
                    {
                        /** @return Box<covariant T> */
                        abstract public function boxes(): Box;

                        /** @param Box<contravariant T> $box */
                        abstract public function accept(Box $box): void;

                        /** @return Box<*> */
                        abstract public function anything(): Box;
                    }',
            ],
            'covariantCollectionPassedToIterableParams' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /**
                     * @template T
                     * @implements IteratorAggregate<int, T>
                     */
                    abstract class Coll implements IteratorAggregate {}

                    /** @param iterable<int, mixed> $_i */
                    function takesIterable($_i): void {}

                    /** @param Traversable<int, mixed> $_t */
                    function takesTraversable($_t): void {}

                    /**
                     * @param Coll<*> $star
                     * @param Coll<contravariant B> $contra
                     */
                    function test(Coll $star, Coll $contra): void {
                        takesIterable($star);
                        takesIterable($contra);
                        takesTraversable($star);
                        takesTraversable($contra);
                        iterator_to_array($star);
                    }',
            ],
            'projectionAcceptsASubtypeForAMethodTemplateBoundedByAContravariantArgument' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @template T of A */
                    class RBox
                    {
                        /** @param T $x */
                        public function pack($x): void {}

                        /** @return T */
                        public function unpack() { throw new Exception(); }

                        /**
                         * @template U of T
                         * @param U $u
                         */
                        public function packBounded($u): void {}

                        /** @param T $x */
                        public function swap(&$x): void {}

                        /** @return RBox<T> */
                        public function me() { return $this; }

                        /** @return class-string<T> */
                        public function cls() { throw new Exception(); }

                        /** @param T $x */
                        public function __invoke($x): void {}

                        /** @param list<T> $xs */
                        public function packAll(array $xs): void {}

                        /** @return static */
                        public function selfReturn() { return $this; }
                    }

                    /**
                     * @template U of A
                     * @extends RBox<U>
                     */
                    class SubRBox extends RBox {}

                    /** @param RBox<contravariant B> $box */
                    function test(RBox $box): void { $box->packBounded(new C()); }',
            ],
            'projectionReadsWhatAByReferenceParamIsLeftHoldingAsTheBound' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @template T of A */
                    class RBox
                    {
                        /** @param T $x */
                        public function pack($x): void {}

                        /** @return T */
                        public function unpack() { throw new Exception(); }

                        /**
                         * @template U of T
                         * @param U $u
                         */
                        public function packBounded($u): void {}

                        /** @param T $x */
                        public function swap(&$x): void {}

                        /** @return RBox<T> */
                        public function me() { return $this; }

                        /** @return class-string<T> */
                        public function cls() { throw new Exception(); }

                        /** @param T $x */
                        public function __invoke($x): void {}

                        /** @param list<T> $xs */
                        public function packAll(array $xs): void {}

                        /** @return static */
                        public function selfReturn() { return $this; }
                    }

                    /**
                     * @template U of A
                     * @extends RBox<U>
                     */
                    class SubRBox extends RBox {}

                    /** @param RBox<contravariant B> $box */
                    function test(RBox $box): void {
                        $b = new B();
                        $box->swap($b);
                        /** @psalm-check-type-exact $b = A */
                    }',
            ],
            'projectionThroughAnAncestorProjectsAClassString' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @template T of A */
                    class RBox
                    {
                        /** @param T $x */
                        public function pack($x): void {}

                        /** @return T */
                        public function unpack() { throw new Exception(); }

                        /**
                         * @template U of T
                         * @param U $u
                         */
                        public function packBounded($u): void {}

                        /** @param T $x */
                        public function swap(&$x): void {}

                        /** @return RBox<T> */
                        public function me() { return $this; }

                        /** @return class-string<T> */
                        public function cls() { throw new Exception(); }

                        /** @param T $x */
                        public function __invoke($x): void {}

                        /** @param list<T> $xs */
                        public function packAll(array $xs): void {}

                        /** @return static */
                        public function selfReturn() { return $this; }
                    }

                    /**
                     * @template U of A
                     * @extends RBox<U>
                     */
                    class SubRBox extends RBox {}

                    /** @param SubRBox<contravariant B> $box */
                    function test(SubRBox $box): void {
                        $class = $box->cls();
                        /** @psalm-check-type-exact $class = class-string<A> */
                    }',
            ],
            'projectionOfAStaticInvocationOnAnObject' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @template T of A */
                    class RBox
                    {
                        /** @param T $x */
                        public function pack($x): void {}

                        /** @return T */
                        public function unpack() { throw new Exception(); }

                        /**
                         * @template U of T
                         * @param U $u
                         */
                        public function packBounded($u): void {}

                        /** @param T $x */
                        public function swap(&$x): void {}

                        /** @return RBox<T> */
                        public function me() { return $this; }

                        /** @return class-string<T> */
                        public function cls() { throw new Exception(); }

                        /** @param T $x */
                        public function __invoke($x): void {}

                        /** @param list<T> $xs */
                        public function packAll(array $xs): void {}

                        /** @return static */
                        public function selfReturn() { return $this; }
                    }

                    /**
                     * @template U of A
                     * @extends RBox<U>
                     */
                    class SubRBox extends RBox {}

                    /** @param RBox<contravariant B> $box */
                    function test(RBox $box): void {
                        $result = $box::unpack();
                        /** @psalm-check-type-exact $result = A */
                    }',
                'assertions' => [],
                'ignored_issues' => ['InvalidStaticInvocation'],
            ],
            'projectionOfAKeyOfAnOpenArrayShape' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @template K of array-key */
                    class KBox
                    {
                        /** @return array{fixed: int, ...<K, int>} */
                        public function shape(): array { return [\'fixed\' => 1]; }
                    }

                    /** @param KBox<contravariant string> $box */
                    function test(KBox $box): void {
                        $shape = $box->shape();
                        /** @psalm-check-type-exact $shape = array{fixed: int, ...<array-key, int>} */
                    }',
            ],
            'projectionOfAClassStringWithAUnionBound' => [
                'code' => '<?php' . self::HIERARCHY . '
                    class X {}
                    class Y {}

                    /** @template T of X|Y */
                    class UBox
                    {
                        /** @return class-string<T> */
                        public function cls(): string { return X::class; }
                    }

                    /** @param UBox<contravariant X> $box */
                    function test(UBox $box): void {
                        $class = $box->cls();
                        /** @psalm-check-type-exact $class = class-string<X>|class-string<Y> */
                    }',
            ],
            'projectionOfAnInheritedPropertyRead' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @template T of A */
                    class RBox
                    {
                        /** @param T $x */
                        public function pack($x): void {}

                        /** @return T */
                        public function unpack() { throw new Exception(); }

                        /**
                         * @template U of T
                         * @param U $u
                         */
                        public function packBounded($u): void {}

                        /** @param T $x */
                        public function swap(&$x): void {}

                        /** @return RBox<T> */
                        public function me() { return $this; }

                        /** @return class-string<T> */
                        public function cls() { throw new Exception(); }

                        /** @param T $x */
                        public function __invoke($x): void {}

                        /** @param list<T> $xs */
                        public function packAll(array $xs): void {}

                        /** @return static */
                        public function selfReturn() { return $this; }
                    }

                    /**
                     * @template U of A
                     * @extends RBox<U>
                     */
                    class SubRBox extends RBox {}

                    /** @template T */
                    class PHolder
                    {
                        /** @var T */
                        public $value;

                        /** @param T $value */
                        public function __construct($value) { $this->value = $value; }
                    }

                    /**
                     * @template U
                     * @extends PHolder<U>
                     */
                    class SubPHolder extends PHolder {}

                    /** @param SubPHolder<covariant B> $holder */
                    function test(SubPHolder $holder): void {
                        $result = $holder->value;
                        /** @psalm-check-type-exact $result = B */
                    }',
            ],
            'containerWithACovariantThisArgument' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /**
                     * @template T
                     * @template S
                     */
                    class Container
                    {
                        /** @return Container<T, covariant $this> */
                        public function self() { throw new Exception(); }
                    }

                    /**
                     * @template T
                     * @extends Container<T, mixed>
                     */
                    class Coll extends Container {}

                    /** @param Container<B, covariant Coll<B>> $_c */
                    function takes(Container $_c): void {}

                    /** @param Coll<B> $coll */
                    function test(Coll $coll): void { takes($coll->self()); }',
            ],
            'projectionAcceptsAnEmptyListWriteOnACovariantReceiver' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @template T of A */
                    class RBox
                    {
                        /** @param T $x */
                        public function pack($x): void {}

                        /** @return T */
                        public function unpack() { throw new Exception(); }

                        /**
                         * @template U of T
                         * @param U $u
                         */
                        public function packBounded($u): void {}

                        /** @param T $x */
                        public function swap(&$x): void {}

                        /** @return RBox<T> */
                        public function me() { return $this; }

                        /** @return class-string<T> */
                        public function cls() { throw new Exception(); }

                        /** @param T $x */
                        public function __invoke($x): void {}

                        /** @param list<T> $xs */
                        public function packAll(array $xs): void {}

                        /** @return static */
                        public function selfReturn() { return $this; }
                    }

                    /**
                     * @template U of A
                     * @extends RBox<U>
                     */
                    class SubRBox extends RBox {}

                    /** @param RBox<covariant B> $box */
                    function test(RBox $box): void { $box->packAll([]); }',
            ],
            'projectionOnATraitMember' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @template T of A */
                    trait Packs
                    {
                        /** @return T */
                        public function unpack() { throw new Exception(); }
                    }

                    /** @template T of A */
                    class TraitBox
                    {
                        /** @use Packs<T> */
                        use Packs;
                    }

                    /** @param TraitBox<covariant B> $box */
                    function test(TraitBox $box): void {
                        $result = $box->unpack();
                        /** @psalm-check-type-exact $result = B */
                    }',
            ],
            'combinedCallSiteVariancesAreKeptApart' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @param Box<covariant A> $_box */
                    function takesCovariant(Box $_box): void {}

                    /**
                     * @param Box<A> $a
                     * @param Box<covariant A> $ca
                     */
                    function test(Box $a, Box $ca, bool $flag): void {
                        $either = $flag ? $a : $ca;
                        takesCovariant($either);
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
            'invariantContainerRejectsCovariantInput' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @param Box<A> $_box */
                    function takes(Box $_box): void {}

                    /** @param Box<covariant A> $box */
                    function test(Box $box): void {
                        takes($box);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'invariantContainerRejectsStarInput' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @param Box<A> $_box */
                    function takes(Box $_box): void {}

                    /** @param Box<*> $box */
                    function test(Box $box): void {
                        takes($box);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'covariantContainerRejectsContravariantInput' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @param Box<covariant A> $_box */
                    function takes(Box $_box): void {}

                    /** @param Box<contravariant A> $box */
                    function test(Box $box): void {
                        takes($box);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'covariantContainerRejectsStarInput' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @param Box<covariant A> $_box */
                    function takes(Box $_box): void {}

                    /** @param Box<*> $box */
                    function test(Box $box): void {
                        takes($box);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'covariantContainerRejectsSupertype' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @param Box<covariant B> $_box */
                    function takes(Box $_box): void {}

                    /** @param Box<A> $box */
                    function test(Box $box): void {
                        takes($box);
                    }',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'contravariantContainerRejectsSubtype' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @param Box<contravariant B> $_box */
                    function takes(Box $_box): void {}

                    /** @param Box<C> $box */
                    function test(Box $box): void {
                        takes($box);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'contravariantContainerRejectsCovariantInput' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @param Box<contravariant A> $_box */
                    function takes(Box $_box): void {}

                    /** @param Box<covariant A> $box */
                    function test(Box $box): void {
                        takes($box);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'ancestorArgumentKeepsCallSiteVarianceAgainstInvariantContainer' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /**
                     * @template U
                     * @extends Box<U>
                     */
                    class SubBox extends Box {}

                    /** @param Box<B> $_box */
                    function takes(Box $_box): void {}

                    /** @param SubBox<covariant B> $sub */
                    function test(SubBox $sub): void {
                        takes($sub);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'ancestorWithCompositeArgumentIsRejectedByAnInvariantContainer' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /**
                     * @template U
                     * @extends Box<array<U>>
                     */
                    class ArraysBox extends Box {}

                    /** @param Box<array<B>> $_box */
                    function takes(Box $_box): void {}

                    /** @param ArraysBox<covariant B> $sub */
                    function test(ArraysBox $sub): void {
                        takes($sub);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'covariantArgumentIsNotAnInvariantMethodTemplate' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /**
                     * @template T
                     * @param Box<T> $_box
                     * @return T
                     */
                    function unbox(Box $_box) { throw new Exception(); }

                    /** @param Box<covariant C> $box */
                    function test(Box $box): void {
                        unbox($box);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'projectionCovariantRejectsAnyArgument' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @param PBox<covariant B> $box */
                    function test(PBox $box, B $b): void {
                        $box->pack($b);
                    }',
                'error_message' => 'expects never',
            ],
            'projectionContravariantRejectsASupertypeOfTheArgument' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @param PBox<contravariant B> $box */
                    function test(PBox $box, A $a): void {
                        $box->pack($a);
                    }',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'projectionStarRejectsAnyArgument' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @param PBox<*> $box */
                    function test(PBox $box, A $a): void {
                        $box->pack($a);
                    }',
                'error_message' => 'expects never',
            ],
            'projectionCovariantCallbackReturnIsNever' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @param Mapped<covariant B> $mapped */
                    function test(Mapped $mapped): void {
                        $mapped->mapOut(fn() => new B());
                    }',
                'error_message' => 'never',
            ],
            'projectionContravariantCallbackReturnRejectsASupertype' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @param Mapped<contravariant B> $mapped */
                    function test(Mapped $mapped): void {
                        $mapped->mapOut(fn() => new A());
                    }',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'projectionStarCallbackReturnIsNever' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @param Mapped<*> $mapped */
                    function test(Mapped $mapped): void {
                        $mapped->mapOut(fn() => new A());
                    }',
                'error_message' => 'never',
            ],
            'projectionRejectsAWriteThroughTheReadValueOfACarriedArgument' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @param PBox<covariant B> $box */
                    function test(PBox $box, B $b): void {
                        $box->me()->pack($b);
                    }',
                'error_message' => 'expects never',
            ],
            'projectionThroughAnAncestorRejectsAWrite' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /**
                     * @template U
                     * @implements PBox<U>
                     */
                    abstract class SubPBox implements PBox {}

                    /** @param SubPBox<covariant B> $box */
                    function test(SubPBox $box, B $b): void {
                        $box->pack($b);
                    }',
                'error_message' => 'expects never',
            ],
            'projectionOfAPropertyWriteWithACovariantArgument' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @template T */
                    class Holder {
                        /** @var T */
                        public $value;
                        /** @param T $value */
                        public function __construct($value) { $this->value = $value; }
                    }

                    /** @param Holder<covariant B> $h */
                    function test(Holder $h, B $b): void {
                        $h->value = $b;
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'projectionOfAFirstClassCallableTakesNever' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @param PBox<covariant B> $box */
                    function test(PBox $box, B $b): void {
                        $pack = $box->pack(...);
                        $pack($b);
                    }',
                'error_message' => 'expects never',
            ],
            'projectionOfAPseudoMethodParam' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /**
                     * @template T
                     * @method void put(T $x)
                     */
                    class Magic { public function __call(string $name, array $args) {} }

                    /** @param Magic<covariant B> $magic */
                    function test(Magic $magic, B $b): void {
                        $magic->put($b);
                    }',
                'error_message' => 'expects never',
            ],
            'projectionThroughAMixinRejectsAWrite' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /**
                     * @template T
                     * @mixin PBox<T>
                     */
                    class Wrapper {}

                    /** @param Wrapper<covariant B> $w */
                    function test(Wrapper $w, B $b): void {
                        $w->pack($b);
                    }',
                'error_message' => 'expects never',
            ],
            'projectionOnATemplateWithACallSiteVarianceBoundRejectsAWrite' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /**
                     * @template X of PBox<covariant B>
                     * @param X $box
                     */
                    function test(PBox $box, B $b): void {
                        $box->pack($b);
                    }',
                'error_message' => 'expects never',
            ],
            'callSiteContravarianceOfACovariantTemplate' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @param ReadOnlyBox<contravariant A> $_box */
                    function takes(ReadOnlyBox $_box): void {}',
                'error_message' => 'InvalidDocblock',
            ],
            'callSiteCovarianceOfACovariantTemplateIsRedundant' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @param ReadOnlyBox<covariant A> $_box */
                    function takes(ReadOnlyBox $_box): void {}',
                'error_message' => 'RedundantCallSiteVariance',
            ],
            'callSiteVarianceInExtends' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /**
                     * @template T
                     * @extends Box<covariant T>
                     */
                    class SubBox extends Box {}',
                'error_message' => 'InvalidDocblock',
            ],
            'callSiteVarianceInImplements' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @template T */
                    interface BoxInterface {}

                    /**
                     * @template T
                     * @implements BoxInterface<*>
                     */
                    class ImplBox implements BoxInterface {}',
                'error_message' => 'InvalidDocblock',
            ],
            'callSiteVarianceInUse' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @template T */
                    trait BoxTrait {}

                    /** @template T */
                    class UsesBox {
                        /** @use BoxTrait<contravariant T> */
                        use BoxTrait;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'covariantTemplateInACovariantCallSiteParam' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @template-covariant T */
                    abstract class Source
                    {
                        /** @param Box<covariant T> $box */
                        abstract public function accept(Box $box): void;
                    }',
                'error_message' => 'InvalidTemplateParam',
            ],
            'covariantTemplateInAContravariantCallSiteReturn' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @template-covariant T */
                    abstract class Source
                    {
                        /** @return Box<contravariant T> */
                        abstract public function boxes(): Box;
                    }',
                'error_message' => 'InvalidTemplateParam',
            ],
            'callSiteVarianceOutsideAGenericArgument' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @param (covariant A) $_a */
                    function takes($_a): void {}',
                'error_message' => 'InvalidDocblock',
            ],
            'contravariantContainerRejectsAGenericInput' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /**
                     * @template U
                     * @param Box<U> $box
                     */
                    function test(Box $box): void { takes($box); }

                    /** @param Box<contravariant A> $_box */
                    function takes(Box $_box): void {}',
                'error_message' => 'InvalidArgument',
            ],
            'contravariantContainerChecksAnArgumentInferredFromAnother' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /**
                     * @template T
                     * @param T $_x
                     * @param Box<contravariant T> $_box
                     */
                    function feed($_x, Box $_box): void {}

                    /** @param Box<B> $box */
                    function test(Box $box): void { feed(new A(), $box); }',
                'error_message' => 'Incompatible types found for T',
            ],
            'contravariantCollectionIsReadAsItsBound' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /**
                     * @template T
                     * @implements IteratorAggregate<int, T>
                     */
                    abstract class Coll implements IteratorAggregate {}

                    /** @param Traversable<int, A> $_t */
                    function takes($_t): void {}

                    /** @param Coll<contravariant B> $coll */
                    function test(Coll $coll): void { takes($coll); }',
                'error_message' => 'MixedArgumentTypeCoercion',
            ],
            'projectionRejectsAWriteToAMethodTemplateBoundedByACovariantTemplate' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @template T of A */
                    class RBox
                    {
                        /** @param T $x */
                        public function pack($x): void {}

                        /** @return T */
                        public function unpack() { throw new Exception(); }

                        /**
                         * @template U of T
                         * @param U $u
                         */
                        public function packBounded($u): void {}

                        /** @param T $x */
                        public function swap(&$x): void {}

                        /** @return RBox<T> */
                        public function me() { return $this; }

                        /** @return class-string<T> */
                        public function cls() { throw new Exception(); }

                        /** @param T $x */
                        public function __invoke($x): void {}

                        /** @param list<T> $xs */
                        public function packAll(array $xs): void {}

                        /** @return static */
                        public function selfReturn() { return $this; }
                    }

                    /**
                     * @template U of A
                     * @extends RBox<U>
                     */
                    class SubRBox extends RBox {}

                    /** @param RBox<covariant B> $box */
                    function test(RBox $box): void { $box->packBounded(new B()); }',
                'error_message' => 'expects never',
            ],
            'projectionRejectsAWriteToAMethodTemplateBoundedByAStarTemplate' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @template T of A */
                    class RBox
                    {
                        /** @param T $x */
                        public function pack($x): void {}

                        /** @return T */
                        public function unpack() { throw new Exception(); }

                        /**
                         * @template U of T
                         * @param U $u
                         */
                        public function packBounded($u): void {}

                        /** @param T $x */
                        public function swap(&$x): void {}

                        /** @return RBox<T> */
                        public function me() { return $this; }

                        /** @return class-string<T> */
                        public function cls() { throw new Exception(); }

                        /** @param T $x */
                        public function __invoke($x): void {}

                        /** @param list<T> $xs */
                        public function packAll(array $xs): void {}

                        /** @return static */
                        public function selfReturn() { return $this; }
                    }

                    /**
                     * @template U of A
                     * @extends RBox<U>
                     */
                    class SubRBox extends RBox {}

                    /** @param RBox<*> $box */
                    function test(RBox $box): void { $box->packBounded(new B()); }',
                'error_message' => 'expects never',
            ],
            'projectionBoundsAMethodTemplateByAContravariantArgument' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @template T of A */
                    class RBox
                    {
                        /** @param T $x */
                        public function pack($x): void {}

                        /** @return T */
                        public function unpack() { throw new Exception(); }

                        /**
                         * @template U of T
                         * @param U $u
                         */
                        public function packBounded($u): void {}

                        /** @param T $x */
                        public function swap(&$x): void {}

                        /** @return RBox<T> */
                        public function me() { return $this; }

                        /** @return class-string<T> */
                        public function cls() { throw new Exception(); }

                        /** @param T $x */
                        public function __invoke($x): void {}

                        /** @param list<T> $xs */
                        public function packAll(array $xs): void {}

                        /** @return static */
                        public function selfReturn() { return $this; }
                    }

                    /**
                     * @template U of A
                     * @extends RBox<U>
                     */
                    class SubRBox extends RBox {}

                    /** @param RBox<contravariant B> $box */
                    function test(RBox $box): void { $box->packBounded(new A()); }',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'projectionRejectsAByReferenceWriteOnACovariantReceiver' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @template T of A */
                    class RBox
                    {
                        /** @param T $x */
                        public function pack($x): void {}

                        /** @return T */
                        public function unpack() { throw new Exception(); }

                        /**
                         * @template U of T
                         * @param U $u
                         */
                        public function packBounded($u): void {}

                        /** @param T $x */
                        public function swap(&$x): void {}

                        /** @return RBox<T> */
                        public function me() { return $this; }

                        /** @return class-string<T> */
                        public function cls() { throw new Exception(); }

                        /** @param T $x */
                        public function __invoke($x): void {}

                        /** @param list<T> $xs */
                        public function packAll(array $xs): void {}

                        /** @return static */
                        public function selfReturn() { return $this; }
                    }

                    /**
                     * @template U of A
                     * @extends RBox<U>
                     */
                    class SubRBox extends RBox {}

                    /** @param RBox<covariant B> $box */
                    function test(RBox $box, B $b): void { $box->swap($b); }',
                'error_message' => 'expects never',
            ],
            'projectionThroughAnAncestorKeepsTheVarianceOfAnInvariantResult' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @template T of A */
                    class RBox
                    {
                        /** @param T $x */
                        public function pack($x): void {}

                        /** @return T */
                        public function unpack() { throw new Exception(); }

                        /**
                         * @template U of T
                         * @param U $u
                         */
                        public function packBounded($u): void {}

                        /** @param T $x */
                        public function swap(&$x): void {}

                        /** @return RBox<T> */
                        public function me() { return $this; }

                        /** @return class-string<T> */
                        public function cls() { throw new Exception(); }

                        /** @param T $x */
                        public function __invoke($x): void {}

                        /** @param list<T> $xs */
                        public function packAll(array $xs): void {}

                        /** @return static */
                        public function selfReturn() { return $this; }
                    }

                    /**
                     * @template U of A
                     * @extends RBox<U>
                     */
                    class SubRBox extends RBox {}

                    /** @param SubRBox<covariant B> $box */
                    function test(SubRBox $box, B $b): void { $box->me()->pack($b); }',
                'error_message' => 'expects never',
            ],
            'projectionRejectsAnInvokeOnACovariantReceiver' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @template T of A */
                    class RBox
                    {
                        /** @param T $x */
                        public function pack($x): void {}

                        /** @return T */
                        public function unpack() { throw new Exception(); }

                        /**
                         * @template U of T
                         * @param U $u
                         */
                        public function packBounded($u): void {}

                        /** @param T $x */
                        public function swap(&$x): void {}

                        /** @return RBox<T> */
                        public function me() { return $this; }

                        /** @return class-string<T> */
                        public function cls() { throw new Exception(); }

                        /** @param T $x */
                        public function __invoke($x): void {}

                        /** @param list<T> $xs */
                        public function packAll(array $xs): void {}

                        /** @return static */
                        public function selfReturn() { return $this; }
                    }

                    /**
                     * @template U of A
                     * @extends RBox<U>
                     */
                    class SubRBox extends RBox {}

                    /** @param RBox<covariant B> $box */
                    function test(RBox $box): void { $box(new B()); }',
                'error_message' => 'expects never',
            ],
            'projectionRejectsAListWriteOnACovariantReceiver' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @template T of A */
                    class RBox
                    {
                        /** @param T $x */
                        public function pack($x): void {}

                        /** @return T */
                        public function unpack() { throw new Exception(); }

                        /**
                         * @template U of T
                         * @param U $u
                         */
                        public function packBounded($u): void {}

                        /** @param T $x */
                        public function swap(&$x): void {}

                        /** @return RBox<T> */
                        public function me() { return $this; }

                        /** @return class-string<T> */
                        public function cls() { throw new Exception(); }

                        /** @param T $x */
                        public function __invoke($x): void {}

                        /** @param list<T> $xs */
                        public function packAll(array $xs): void {}

                        /** @return static */
                        public function selfReturn() { return $this; }
                    }

                    /**
                     * @template U of A
                     * @extends RBox<U>
                     */
                    class SubRBox extends RBox {}

                    /** @param RBox<covariant B> $box */
                    function test(RBox $box): void { $box->packAll([new B()]); }',
                'error_message' => 'never',
            ],
            'projectionRejectsAWriteOnAStaticResultOfACovariantReceiver' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @template T of A */
                    class RBox
                    {
                        /** @param T $x */
                        public function pack($x): void {}

                        /** @return T */
                        public function unpack() { throw new Exception(); }

                        /**
                         * @template U of T
                         * @param U $u
                         */
                        public function packBounded($u): void {}

                        /** @param T $x */
                        public function swap(&$x): void {}

                        /** @return RBox<T> */
                        public function me() { return $this; }

                        /** @return class-string<T> */
                        public function cls() { throw new Exception(); }

                        /** @param T $x */
                        public function __invoke($x): void {}

                        /** @param list<T> $xs */
                        public function packAll(array $xs): void {}

                        /** @return static */
                        public function selfReturn() { return $this; }
                    }

                    /**
                     * @template U of A
                     * @extends RBox<U>
                     */
                    class SubRBox extends RBox {}

                    /** @param RBox<covariant B> $box */
                    function test(RBox $box): void { $box->selfReturn()->pack(new B()); }',
                'error_message' => 'expects never',
            ],
            'projectionRejectsAWriteOnATraitMemberOfACovariantReceiver' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @template T of A */
                    trait Packs
                    {
                        /** @param T $x */
                        public function pack($x): void {}
                    }

                    /** @template T of A */
                    class TraitBox
                    {
                        /** @use Packs<T> */
                        use Packs;
                    }

                    /** @param TraitBox<covariant B> $box */
                    function test(TraitBox $box): void { $box->pack(new B()); }',
                'error_message' => 'expects never',
            ],
            'starBoxIsNotAnArgumentOfACovariantParameterOfAMethodTemplate' => [
                'code' => '<?php' . self::HIERARCHY . self::PROJECTIONS . '
                    /** @template T of A */
                    class RBox
                    {
                        /** @param T $x */
                        public function pack($x): void {}

                        /** @return T */
                        public function unpack() { throw new Exception(); }

                        /**
                         * @template U of T
                         * @param U $u
                         */
                        public function packBounded($u): void {}

                        /** @param T $x */
                        public function swap(&$x): void {}

                        /** @return RBox<T> */
                        public function me() { return $this; }

                        /** @return class-string<T> */
                        public function cls() { throw new Exception(); }

                        /** @param T $x */
                        public function __invoke($x): void {}

                        /** @param list<T> $xs */
                        public function packAll(array $xs): void {}

                        /** @return static */
                        public function selfReturn() { return $this; }
                    }

                    /**
                     * @template U of A
                     * @extends RBox<U>
                     */
                    class SubRBox extends RBox {}

                    /**
                     * @template T of A
                     * @param RBox<covariant T> $_box
                     */
                    function takes(RBox $_box): void {}

                    /** @param RBox<*> $box */
                    function test(RBox $box): void { takes($box); }',
                'error_message' => 'InvalidArgument',
            ],
            'combinedCallSiteVariancesAreNotMerged' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @param Box<A> $_box */
                    function takesInvariant(Box $_box): void {}

                    /**
                     * @param Box<A> $a
                     * @param Box<covariant A> $ca
                     */
                    function test(Box $a, Box $ca, bool $flag): void {
                        $either = $flag ? $a : $ca;
                        takesInvariant($either);
                    }',
                'error_message' => 'PossiblyInvalidArgument',
            ],
        ];
    }
}
