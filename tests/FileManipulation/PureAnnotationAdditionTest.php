<?php

declare(strict_types=1);

namespace Psalm\Tests\FileManipulation;

use Override;

final class PureAnnotationAdditionTest extends FileManipulationTestCase
{
    /**
     * @psalm-pure
     */
    #[Override]
    public function providerValidCodeParse(): array
    {
        return [
            'correctClassCasing' => [
                'input' => '<?php
                    interface F {
                        /**
                         * @return static
                         * @psalm-mutation-free
                         */
                        public function m(): self;
                    }

                    abstract class G implements F {}

                    class H extends G {
                        public function m(): F {
                            return $this;
                        }
                    }',
                'output' => '<?php
                    interface F {
                        /**
                         * @return static
                         * @psalm-mutation-free
                         */
                        public function m(): self;
                    }

                    abstract class G implements F {}

                    class H extends G {
                        /**
                         * @psalm-capabilities read-props
                         */
                        public function m(): F {
                            return $this;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'addPureAnnotationToFunction' => [
                'input' => '<?php
                    function foo(string $s): string {
                        return $s;
                    }',
                'output' => '<?php
                    /**
                     * @psalm-pure
                     */
                    function foo(string $s): string {
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'addPureAnnotationToFunctionCreatingArrowFunction' => [
                'input' => '<?php
                    /**
                     * @param Closure[_](int): string $f
                     */
                    function foo(Closure $f): Closure {
                        return fn(int $i): string => $f($i);
                    }',
                'output' => '<?php
                    /**
                     * @param Closure[_](int): string $f
                     *
                     * @psalm-pure
                     */
                    function foo(Closure $f): Closure {
                        return fn(int $i): string => $f($i);
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'selfCall' => [
                'input' => '<?php
                    function foo(string $s, int $v): string {
                        if ($v > 5) {
                            return foo($s, $v - 1);
                        }
                        return $s;
                    }',
                'output' => '<?php
                    /**
                     * @psalm-pure
                     */
                    function foo(string $s, int $v): string {
                        if ($v > 5) {
                            return foo($s, $v - 1);
                        }
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'addPurityWildcardToCalledClosureParams' => [
                'input' => '<?php
                    /**
                     * @param Closure(int): string $f
                     */
                    function mapInt(Closure $f, int $i): string {
                        return $f($i);
                    }

                    function apply(?callable $cb, int $x): int {
                        return $cb !== null ? $cb($x) : $x;
                    }

                    function reassigned(Closure $f, Closure $g): int {
                        $f = $g;
                        return $f();
                    }',
                'output' => '<?php
                    /**
                     * @param Closure[_](int): string $f
                     *
                     * @psalm-pure
                     */
                    function mapInt(Closure $f, int $i): string {
                        return $f($i);
                    }

                    /**
                     * @param ?callable[_] $cb
                     *
                     * @psalm-pure
                     */
                    function apply(?callable $cb, int $x): int {
                        return $cb !== null ? $cb($x) : $x;
                    }

                    function reassigned(Closure $f, Closure $g): int {
                        $f = $g;
                        return $f();
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'addPurityWildcardReplacingTheDefaultPurityOnly' => [
                'input' => '<?php
                    /**
                     * @param impure-Closure(int): string $f
                     * @param Closure[read-props](int): string $g
                     */
                    function both(Closure $f, Closure $g, int $i): string {
                        return $f($i) . $g($i);
                    }',
                'output' => '<?php
                    /**
                     * @param Closure[_](int): string $f
                     * @param Closure[read-props](int): string $g
                     *
                     * @psalm-capabilities read-props
                     */
                    function both(Closure $f, Closure $g, int $i): string {
                        return $f($i) . $g($i);
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'addNestedPurityWildcardToCalledClosures' => [
                'input' => '<?php
                    /**
                     * @param list<Closure(): int> $fs
                     */
                    function sumLoop(array $fs): int {
                        $s = 0;
                        foreach ($fs as $f) {
                            $s += $f();
                        }
                        return $s;
                    }

                    /**
                     * @param array{run: Closure(int): int, other: int} $opts
                     */
                    function viaShape(array $opts): int {
                        return $opts["run"]($opts["other"]);
                    }

                    /**
                     * @param Closure(): Closure(): int $make
                     */
                    function curry(Closure $make): int {
                        return $make()();
                    }',
                'output' => '<?php
                    /**
                     * @param list<Closure[_](): int> $fs
                     *
                     * @psalm-pure
                     */
                    function sumLoop(array $fs): int {
                        $s = 0;
                        foreach ($fs as $f) {
                            $s += $f();
                        }
                        return $s;
                    }

                    /**
                     * @param array{run: Closure[_](int): int, other: int} $opts
                     *
                     * @psalm-pure
                     */
                    function viaShape(array $opts): int {
                        return $opts["run"]($opts["other"]);
                    }

                    /**
                     * @param Closure[_](): Closure[_](): int $make
                     *
                     * @psalm-pure
                     */
                    function curry(Closure $make): int {
                        return $make()();
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'addPurityWildcardToIteratedParams' => [
                'input' => '<?php
                    /**
                     * @param Traversable<int, int> $t
                     */
                    function sumTraversable(Traversable $t): int {
                        $s = 0;
                        foreach ($t as $x) {
                            $s += $x;
                        }
                        return $s;
                    }

                    function countAll(Traversable $t): int {
                        $n = 0;
                        foreach ($t as $_) {
                            $n++;
                        }
                        return $n;
                    }

                    /**
                     * @param iterable<int, Closure(): int> $fs
                     */
                    function sumIterable(iterable $fs): int {
                        $s = 0;
                        foreach ($fs as $f) {
                            $s += $f();
                        }
                        return $s;
                    }

                    /**
                     * @param IteratorAggregate<int, int> $a
                     */
                    function sumAggregate(IteratorAggregate $a): int {
                        $s = 0;
                        foreach ($a as $x) {
                            $s += $x;
                        }
                        return $s;
                    }',
                'output' => '<?php
                    /**
                     * @param Traversable[_]<int, int> $t
                     *
                     * @psalm-pure
                     */
                    function sumTraversable(Traversable $t): int {
                        $s = 0;
                        foreach ($t as $x) {
                            $s += $x;
                        }
                        return $s;
                    }

                    /**
                     * @param Traversable[_] $t
                     *
                     * @psalm-pure
                     */
                    function countAll(Traversable $t): int {
                        $n = 0;
                        foreach ($t as $_) {
                            $n++;
                        }
                        return $n;
                    }

                    /**
                     * @param iterable[_]<int, Closure[_](): int> $fs
                     *
                     * @psalm-pure
                     */
                    function sumIterable(iterable $fs): int {
                        $s = 0;
                        foreach ($fs as $f) {
                            $s += $f();
                        }
                        return $s;
                    }

                    /**
                     * @param IteratorAggregate[_]<int, int> $a
                     *
                     * @psalm-pure
                     */
                    function sumAggregate(IteratorAggregate $a): int {
                        $s = 0;
                        foreach ($a as $x) {
                            $s += $x;
                        }
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'addPurityWildcardToReceiverPurityArguments' => [
                'input' => '<?php
                    /** @psalm-purity-template P */
                    interface Doer {
                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P
                         */
                        public function run(): int;
                    }

                    /**
                     * @psalm-purity-template A
                     * @psalm-purity-template B
                     */
                    interface Two {
                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template B
                         */
                        public function b(): int;
                    }

                    function callDoer(Doer $d): int {
                        return $d->run();
                    }

                    /**
                     * @param list<Doer> $ds
                     */
                    function callDoers(array $ds): int {
                        $s = 0;
                        foreach ($ds as $d) {
                            $s += $d->run();
                        }
                        return $s;
                    }

                    function useTwo(Two $t): int {
                        return $t->b();
                    }',
                'output' => '<?php
                    /** @psalm-purity-template P */
                    interface Doer {
                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P
                         */
                        public function run(): int;
                    }

                    /**
                     * @psalm-purity-template A
                     * @psalm-purity-template B
                     */
                    interface Two {
                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template B
                         */
                        public function b(): int;
                    }

                    /**
                     * @param Doer[_] $d
                     *
                     * @psalm-pure
                     */
                    function callDoer(Doer $d): int {
                        return $d->run();
                    }

                    /**
                     * @param list<Doer[_]> $ds
                     *
                     * @psalm-pure
                     */
                    function callDoers(array $ds): int {
                        $s = 0;
                        foreach ($ds as $d) {
                            $s += $d->run();
                        }
                        return $s;
                    }

                    /**
                     * @param Two[impure, _] $t
                     *
                     * @psalm-pure
                     */
                    function useTwo(Two $t): int {
                        return $t->b();
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'addPurityWildcardToTheTypePsalmReads' => [
                'input' => '<?php
                    /**
                     * @psalm-param list<Closure(): int> $fs
                     * @param array $fs
                     */
                    function first(array $fs): int {
                        return $fs[0]();
                    }',
                'output' => '<?php
                    /**
                     * @psalm-param list<Closure[_](): int> $fs
                     *
                     * @param array $fs
                     *
                     * @psalm-pure
                     */
                    function first(array $fs): int {
                        return $fs[0]();
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPurityWildcardWhereItCannotStand' => [
                'input' => '<?php
                    /** @psalm-purity-template P */
                    interface Doer {
                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P
                         */
                        public function run(): int;
                    }

                    /** @extends Doer[impure] */
                    interface ImpureDoer extends Doer {}

                    /** @psalm-type Fns = list<Closure(): int> */
                    final class Aliased {
                        /**
                         * @param Fns $fs
                         */
                        public static function first(array $fs): int {
                            return $fs[0]();
                        }
                    }

                    function useImpureDoer(ImpureDoer $d): int {
                        return $d->run();
                    }

                    /**
                     * @param list<Closure(): int> $fs
                     */
                    function viaVariable(array $fs): int {
                        $g = $fs[0];
                        return $g();
                    }

                    /**
                     * @param list<Closure(): int> $fs
                     */
                    function reassigned(array $fs): int {
                        $fs[] = fn(): int => 1;
                        return $fs[0]();
                    }

                    /**
                     * @param list<Closure(): int> $fs
                     */
                    function inClosure(array $fs): array {
                        return array_map(fn(Closure $f): int => $f(), $fs);
                    }',
                'output' => '<?php
                    /** @psalm-purity-template P */
                    interface Doer {
                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P
                         */
                        public function run(): int;
                    }

                    /** @extends Doer[impure] */
                    interface ImpureDoer extends Doer {}

                    /** @psalm-type Fns = list<Closure(): int> */
                    final class Aliased {
                        /**
                         * @param Fns $fs
                         */
                        public static function first(array $fs): int {
                            return $fs[0]();
                        }
                    }

                    function useImpureDoer(ImpureDoer $d): int {
                        return $d->run();
                    }

                    /**
                     * @param list<Closure(): int> $fs
                     */
                    function viaVariable(array $fs): int {
                        $g = $fs[0];
                        return $g();
                    }

                    /**
                     * @param list<Closure(): int> $fs
                     */
                    function reassigned(array $fs): int {
                        $fs[] = fn(): int => 1;
                        return $fs[0]();
                    }

                    /**
                     * @param list<Closure(): int> $fs
                     */
                    function inClosure(array $fs): array {
                        return array_map(fn(Closure $f): int => $f(), $fs);
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'callsPureFunctionDeclaredLater' => [
                'input' => '<?php
                    function foo(string $s): string {
                        return bar($s) . "!";
                    }
                    function bar(string $s): string {
                        return $s;
                    }',
                'output' => '<?php
                    /**
                     * @psalm-pure
                     */
                    function foo(string $s): string {
                        return bar($s) . "!";
                    }
                    /**
                     * @psalm-pure
                     */
                    function bar(string $s): string {
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureWhenCalleeDeclaredLaterIsImpure' => [
                'input' => '<?php
                    function foo(string $s): string {
                        return bar($s) . "!";
                    }
                    function bar(string $s): string {
                        echo $s;
                        return $s;
                    }',
                'output' => '<?php
                    function foo(string $s): string {
                        return bar($s) . "!";
                    }
                    function bar(string $s): string {
                        echo $s;
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureWhenIndirectCalleeIsImpure' => [
                'input' => '<?php
                    function a(string $s): string {
                        return b($s);
                    }
                    function b(string $s): string {
                        return c($s);
                    }
                    function c(string $s): string {
                        return (string) file_get_contents($s);
                    }',
                'output' => '<?php
                    function a(string $s): string {
                        return b($s);
                    }
                    function b(string $s): string {
                        return c($s);
                    }
                    function c(string $s): string {
                        return (string) file_get_contents($s);
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'staticMethodCallsPureStaticMethodDeclaredLater' => [
                'input' => '<?php
                    final class A {
                        public static function foo(string $s): string {
                            return self::bar($s) . "!";
                        }
                        public static function bar(string $s): string {
                            return $s;
                        }
                    }',
                'output' => '<?php
                    final class A {
                        /**
                         * @psalm-pure
                         */
                        public static function foo(string $s): string {
                            return self::bar($s) . "!";
                        }
                        /**
                         * @psalm-pure
                         */
                        public static function bar(string $s): string {
                            return $s;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'methodCallsPureMethodOfFinalClassDeclaredLater' => [
                'input' => '<?php
                    final class B {
                        public function bar(string $s): string {
                            return $s;
                        }
                    }
                    final class A {
                        public function foo(B $b, string $s): string {
                            return $b->bar($s) . "!";
                        }
                    }',
                'output' => '<?php
                    final class B {
                        /**
                         * @psalm-pure
                         */
                        public function bar(string $s): string {
                            return $s;
                        }
                    }
                    final class A {
                        /**
                         * @psalm-pure
                         */
                        public function foo(B $b, string $s): string {
                            return $b->bar($s) . "!";
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureWhenCalleeCanBeOverridden' => [
                'input' => '<?php
                    class B {
                        public function bar(string $s): string {
                            return $s;
                        }
                    }
                    final class A {
                        public function foo(B $b, string $s): string {
                            return $b->bar($s) . "!";
                        }
                    }',
                'output' => '<?php
                    class B {
                        /**
                         * @psalm-pure
                         */
                        public function bar(string $s): string {
                            return $s;
                        }
                    }
                    final class A {
                        public function foo(B $b, string $s): string {
                            return $b->bar($s) . "!";
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'selfStaticMethodCallIndirect' => [
                'input' => '<?php
                    final class A {
                        public static function foo(string $s, int $v): string {
                            if ($v > 5) {
                                return self::bar($s, $v - 1);
                            }
                            return $s;
                        }
                        public static function bar(string $s, int $v): string {
                            return self::foo($s, $v);
                        }
                    }',
                'output' => '<?php
                    final class A {
                        /**
                         * @psalm-pure
                         */
                        public static function foo(string $s, int $v): string {
                            if ($v > 5) {
                                return self::bar($s, $v - 1);
                            }
                            return $s;
                        }
                        /**
                         * @psalm-pure
                         */
                        public static function bar(string $s, int $v): string {
                            return self::foo($s, $v);
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'selfCallIndirectWithImpureBase' => [
                'input' => '<?php
                    function foo(string $s, int $v): string {
                        if ($v > 5) {
                            return bar($s, $v - 1);
                        }
                        echo $s;
                        return $s;
                    }
                    function bar(string $s, int $v): string {
                        return foo($s, $v);
                    }',
                'output' => '<?php
                    function foo(string $s, int $v): string {
                        if ($v > 5) {
                            return bar($s, $v - 1);
                        }
                        echo $s;
                        return $s;
                    }
                    function bar(string $s, int $v): string {
                        return foo($s, $v);
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'closureCallsPureFunctionDeclaredLater' => [
                'input' => '<?php
                    $f = function(string $s): string {
                        return bar($s);
                    };
                    function bar(string $s): string {
                        return $s;
                    }',
                'output' => '<?php
                    $f = /**
                     * @psalm-pure
                     */
                    function(string $s): string {
                        return bar($s);
                    };
                    /**
                     * @psalm-pure
                     */
                    function bar(string $s): string {
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'closuresOnTheSameLineAreBothAnnotated' => [
                'input' => '<?php
                    $a = function(): int { return 1; }; $b = function(): int { return 2; };',
                'output' => '<?php
                    $a = /**
                     * @psalm-pure
                     */
                    function(): int { return 1; }; $b = /**
                     * @psalm-pure
                     */
                    function(): int { return 2; };',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'selfCallIndirect' => [
                'input' => '<?php
                    function foo(string $s, int $v): string {
                        if ($v > 5) {
                            return bar($s, $v - 1);
                        }
                        return $s;
                    }
                    function bar(string $s, int $v): string {
                        return foo($s, $v);
                    }',
                'output' => '<?php
                    /**
                     * @psalm-pure
                     */
                    function foo(string $s, int $v): string {
                        if ($v > 5) {
                            return bar($s, $v - 1);
                        }
                        return $s;
                    }
                    /**
                     * @psalm-pure
                     */
                    function bar(string $s, int $v): string {
                        return foo($s, $v);
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'selfMethodCall' => [
                'input' => '<?php
                    class A {
                        public function foo(string $s, int $v): string {
                            if ($v > 5) {
                                return $this->foo($s, $v - 1);
                            }
                            return $s;
                        }
                    }',
                'output' => '<?php
                    class A {
                        /**
                         * @psalm-capabilities read-props
                         */
                        public function foo(string $s, int $v): string {
                            if ($v > 5) {
                                return $this->foo($s, $v - 1);
                            }
                            return $s;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'selfMethodCall2' => [
                'input' => '<?php
                    class A {
                        public A $a;
                        public function foo(int $ex): int {
                            if ($ex === 0) {
                                return $ex;
                            }
                            return $this->a->foo($ex - 1);
                        }
                    }
                    class B extends A {
                        public function foo(int $ex): int {
                            echo "test";
                            if ($ex === 0) {
                                return $ex;
                            }
                            return $this->a->foo($ex - 1);
                        }
                    }',
                'output' => '<?php
                    class A {
                        public A $a;
                        public function foo(int $ex): int {
                            if ($ex === 0) {
                                return $ex;
                            }
                            return $this->a->foo($ex - 1);
                        }
                    }
                    class B extends A {
                        public function foo(int $ex): int {
                            echo "test";
                            if ($ex === 0) {
                                return $ex;
                            }
                            return $this->a->foo($ex - 1);
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'selfMethodCall3' => [
                'input' => '<?php
                    class A {
                        public function foo(int $ex): int {
                            if ($ex === 0) {
                                return $ex;
                            }
                            return $this->foo($ex - 1);
                        }
                    }
                    class B extends A {
                        public function foo(int $ex): int {
                            echo "test";
                            if ($ex === 0) {
                                return $ex;
                            }
                            return $this->foo($ex - 1);
                        }
                    }',
                'output' => '<?php
                    class A {
                        /**
                         * @psalm-capabilities read-props
                         */
                        public function foo(int $ex): int {
                            if ($ex === 0) {
                                return $ex;
                            }
                            return $this->foo($ex - 1);
                        }
                    }
                    class B extends A {
                        public function foo(int $ex): int {
                            echo "test";
                            if ($ex === 0) {
                                return $ex;
                            }
                            return $this->foo($ex - 1);
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'selfStaticMethodCall' => [
                'input' => '<?php
                    class A {
                        public static function foo(string $s, int $v): string {
                            if ($v > 5) {
                                return self::foo($s, $v - 1);
                            }
                            return $s;
                        }
                    }',
                'output' => '<?php
                    class A {
                        /**
                         * @psalm-pure
                         */
                        public static function foo(string $s, int $v): string {
                            if ($v > 5) {
                                return self::foo($s, $v - 1);
                            }
                            return $s;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'selfClosureCall' => [
                'input' => '<?php
                    $f = function(string $s, int $v) use (&$f): string {
                        if ($v > 5) {
                            return $f($s, $v - 1);
                        }
                        return $s;
                    }',
                'output' => '<?php
                    $f = /**
                     * @psalm-pure
                     */
                    function(string $s, int $v) use (&$f): string {
                        if ($v > 5) {
                            return $f($s, $v - 1);
                        }
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'propertySetIsNotMutationFree' => [
                'input' => '<?php
                    class A {
                        /**
                         * @psalm-readonly-allow-private-mutation
                         * @var list<FunctionLikeParameter>
                         */
                        public array $params = [];

                        /**
                         * @psalm-readonly-allow-private-mutation
                         * @var array<string, bool>
                         */
                        public array $param_lookup = [];

                        /**
                         * @internal
                         */
                        public function addParam(FunctionLikeParameter $param, ?bool $lookup_value = null): void
                        {
                            $this->params[] = $param;
                            $this->param_lookup[$param->name] = $lookup_value ?? true;
                        }
                    }',
                'output' => '<?php
                    class A {
                        /**
                         * @psalm-readonly-allow-private-mutation
                         * @var list<FunctionLikeParameter>
                         */
                        public array $params = [];

                        /**
                         * @psalm-readonly-allow-private-mutation
                         * @var array<string, bool>
                         */
                        public array $param_lookup = [];

                        /**
                         * @internal
                         *
                         * @psalm-capabilities read-props|write-this-props|write-refs
                         */
                        public function addParam(FunctionLikeParameter $param, ?bool $lookup_value = null): void
                        {
                            $this->params[] = $param;
                            $this->param_lookup[$param->name] = $lookup_value ?? true;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'addPureAnnotationToFunctionWithExistingDocblock' => [
                'input' => '<?php
                    /**
                     * @return string
                     */
                    function foo(string $s) {
                        return $s;
                    }',
                'output' => '<?php
                    /**
                     * @return string
                     *
                     * @psalm-pure
                     */
                    function foo(string $s) {
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureAnnotationToImpureFunction' => [
                'input' => '<?php
                    function foo(string $s): string {
                        echo $s;
                        return $s;
                    }',
                'output' => '<?php
                    function foo(string $s): string {
                        echo $s;
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureAnnotationToMutationFreeMethod' => [
                'input' => '<?php
                    class A {
                        public string $foo = "hello";

                        public function getFoo() : string {
                            return $this->foo;
                        }
                    }',
                'output' => '<?php
                    class A {
                        public string $foo = "hello";

                        public function getFoo() : string {
                            return $this->foo;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureAnnotationToFunctionWithImpureCall' => [
                'input' => '<?php
                    function foo(string $s): string {
                        if (file_exists($s)) {
                            return "";
                        }
                        return $s;
                    }',
                'output' => '<?php
                    function foo(string $s): string {
                        if (file_exists($s)) {
                            return "";
                        }
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureAnnotationToFunctionWithImpureClosure' => [
                'input' => '<?php
                    /** @param list<string> $arr */
                    function foo(array $arr): array {
                        return array_map($arr, function ($s) { echo $s; return $s;});
                    }',
                'output' => '<?php
                    /** @param list<string> $arr */
                    function foo(array $arr): array {
                        return array_map($arr, function ($s) { echo $s; return $s;});
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddWhenReferencingThis' => [
                'input' => '<?php
                    abstract class A {
                        public int $a = 5;

                        public function foo() : self {
                            return $this;
                        }
                    }

                    class B extends A {}',
                'output' => '<?php
                    abstract class A {
                        public int $a = 5;

                        /**
                         * @psalm-capabilities read-props
                         */
                        public function foo() : self {
                            return $this;
                        }
                    }

                    class B extends A {}',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddInChildMethod' => [
                'input' => '<?php
                    class A {
                        public int $a = 5;

                        public function foo(string $s) : string {
                            echo "test";
                            return $string . $this->a;
                        }
                    }

                    class B extends A {
                        public function foo(string $s) : string {
                            return $string;
                        }
                    }',
                'output' => '<?php
                    class A {
                        public int $a = 5;

                        public function foo(string $s) : string {
                            echo "test";
                            return $string . $this->a;
                        }
                    }

                    class B extends A {
                        /**
                         * @psalm-pure
                         */
                        public function foo(string $s) : string {
                            return $string;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'doAddInOtherMethod' => [
                'input' => '<?php
                    class A {
                        public int $a = 5;

                        public function foo(string $s) : string {
                            return $string . $this->a;
                        }
                    }

                    class B extends A {
                        public function bar(string $s) : string {
                            return $string;
                        }
                    }',
                'output' => '<?php
                    class A {
                        public int $a = 5;

                        /**
                         * @psalm-capabilities read-props
                         */
                        public function foo(string $s) : string {
                            return $string . $this->a;
                        }
                    }

                    class B extends A {
                        /**
                         * @psalm-pure
                         */
                        public function bar(string $s) : string {
                            return $string;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureIfCallableNotPure' => [
                'input' => '<?php
                    function pure(callable &$callable): string{
                        return $callable();
                    }',
                'output' => '<?php
                    function pure(callable &$callable): string{
                        return $callable();
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
        ];
    }
}
