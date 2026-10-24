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
            'covariantInputToWildcardOfTraversable' => [
                'code' => '<?php' . self::HIERARCHY . '
                    /** @param Traversable<int, covariant A> $_t */
                    function takes(Traversable $_t): void {}

                    /** @param Traversable<int, B> $t */
                    function test(Traversable $t): void {
                        takes($t);
                    }',
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
            'starTemplateArgumentInference' => [
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
