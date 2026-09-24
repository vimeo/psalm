<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

/**
 * The capability model: `@psalm-capabilities`, what each named purity level allows, closure
 * types with a purity, and the operations that require a capability.
 */
final class CapabilitiesTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'readGlobals' => [
                'code' => '<?php
                    final class S { public static int $n = 0; }

                    /** @psalm-capabilities read-globals */
                    function rg(): int {
                        return S::$n;
                    }

                    /** @psalm-capabilities write-globals */
                    function wg(): int {
                        S::$n = 1;
                        return rg();
                    }',
            ],
            'ioAllowsBuiltinsWithSideEffects' => [
                'code' => '<?php
                    /** @psalm-capabilities io */
                    function io(): int {
                        echo "x";
                        print "y";
                        return time();
                    }',
            ],
            'writePropsOnAnyObject' => [
                'code' => '<?php
                    final class Obj { public int $x = 0; }

                    /** @psalm-capabilities write-props */
                    function wp(Obj $o): int {
                        $o->x = 1;
                        return $o->x;
                    }

                    final class Self_ {
                        public int $x = 0;

                        /** @psalm-capabilities write-this-props */
                        public function wtp(): int {
                            $this->x = 1;
                            return $this->x;
                        }
                    }',
            ],
            'severalCapabilities' => [
                'code' => '<?php
                    final class S { public static int $n = 0; }

                    /** @psalm-capabilities write-globals, io */
                    function both(): int {
                        echo "x";
                        S::$n = 1;
                        return 1;
                    }

                    /** @psalm-capabilities write-globals|io */
                    function pipes(): int {
                        return both();
                    }

                    /** @psalm-impure */
                    function everything(): int {
                        return both() + pipes();
                    }',
            ],
            'namedLevelsAsCapabilities' => [
                'code' => '<?php
                    final class Self_ {
                        public int $x = 0;

                        /** @psalm-capabilities external-mutation-free */
                        public function emf(): int {
                            $this->x = 1;
                            return $this->mf();
                        }

                        /** @psalm-capabilities mutation-free */
                        public function mf(): int {
                            return $this->x;
                        }
                    }',
            ],
            'capabilitiesOnClass' => [
                'code' => '<?php
                    /** @psalm-capabilities io */
                    final class Logger {
                        public function log(string $s): int {
                            echo $s;
                            return 1;
                        }
                    }

                    /** @psalm-capabilities io */
                    function useLogger(Logger $l): int {
                        return $l->log("x");
                    }',
            ],
            'creatingAnImpureClosureIsNotAnEffect' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @return Closure(): void
                     */
                    function factory(): Closure {
                        return function (): void {
                            echo "hi";
                        };
                    }',
            ],
            'closureTypeWithCapabilities' => [
                'code' => '<?php
                    /**
                     * @param Closure<io>(): void $f
                     * @psalm-capabilities io
                     */
                    function run(Closure $f): int {
                        $f();
                        return 1;
                    }

                    /** @psalm-capabilities io */
                    function test(): int {
                        return run(function (): void { echo "x"; }) + run(function (): void {});
                    }',
            ],
            'closureTypeWithoutParamsAndCallable' => [
                'code' => '<?php
                    /**
                     * @param Closure<pure> $f
                     * @param callable<read-globals>(): int $g
                     * @psalm-capabilities read-globals
                     */
                    function run(Closure $f, callable $g): int {
                        $f();
                        return $g();
                    }',
            ],
            'throwingAnExceptionIsPure' => [
                'code' => '<?php
                    final class MyException extends Exception {}

                    /** @psalm-pure */
                    function fail(int $i): int {
                        if ($i > 9000) {
                            throw new MyException("too big");
                        }
                        if ($i > 900) {
                            throw new RuntimeException("big");
                        }
                        return $i;
                    }',
            ],
            'pureClone' => [
                'code' => '<?php
                    final class Point {
                        public int $x = 0;

                        /** @psalm-external-mutation-free */
                        public function __clone() {
                            $this->x = 0;
                        }
                    }

                    /** @psalm-pure */
                    function copyPoint(Point $p): Point {
                        return clone $p;
                    }',
            ],
            'pureToStringInterpolation' => [
                'code' => '<?php
                    final class Name {
                        /** @psalm-pure */
                        public function __toString(): string {
                            return "x";
                        }
                    }

                    /** @psalm-pure */
                    function greet(Name $n): string {
                        return "hello $n" . (string) $n;
                    }',
            ],
            'pureInvoke' => [
                'code' => '<?php
                    final class Inc {
                        /** @psalm-pure */
                        public function __invoke(int $i): int {
                            return $i + 1;
                        }
                    }

                    /** @psalm-pure */
                    function inc(Inc $inc): int {
                        return $inc(1);
                    }',
            ],
            'literalCallableStringOfPureFunction' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function len(): int {
                        $f = "strlen";
                        return $f("x") + call_user_func("strlen", "y");
                    }',
            ],
            'defaultValueWithPureConstructor' => [
                'code' => '<?php
                    final class Options {
                        /** @psalm-external-mutation-free */
                        public function __construct(public int $n = 1) {}
                    }

                    /** @psalm-mutation-free */
                    function withDefault(Options $o = new Options()): int {
                        return $o->n;
                    }',
            ],
            'globalStateMayBeReadAndFreshObjectsMutated' => [
                'code' => '<?php
                    final class Box {
                        public int $x = 0;
                        public static ?Box $g = null;
                    }

                    /** @psalm-capabilities read-globals|write-props */
                    function f(): int {
                        $b = Box::$g;
                        $fresh = new Box();
                        $fresh->x = 1;
                        return $b !== null ? $b->x + $fresh->x : 0;
                    }

                    /** @psalm-capabilities write-globals|write-props */
                    function g(): void {
                        $b = Box::$g;
                        if ($b !== null) {
                            $b->x = 1;
                        }
                    }',
            ],
            'builtinFirstClassCallableCarriesItsCapabilities' => [
                'code' => '<?php
                    /** @psalm-capabilities write-globals */
                    function roll(): int {
                        $r = mt_rand(...);
                        return $r();
                    }

                    /** @psalm-pure */
                    function len(string $s): int {
                        $f = strlen(...);
                        return $f($s);
                    }',
            ],
            'dynamicNewOfPureConstructor' => [
                'code' => '<?php
                    final class Pure {
                        /** @psalm-pure */
                        public function __construct() {}
                    }

                    /**
                     * @psalm-pure
                     * @param class-string<Pure> $c
                     */
                    function make(string $c): Pure {
                        return new $c();
                    }',
            ],
            'throwingExceptionWithPureConstructor' => [
                'code' => '<?php
                    final class MyException extends Exception {
                        /** @psalm-external-mutation-free */
                        public function __construct(int $code) {
                            parent::__construct("failed", $code);
                        }
                    }

                    /** @psalm-pure */
                    function fail(int $i): int {
                        if ($i > 9000) {
                            throw new MyException($i);
                        }
                        return $i;
                    }',
            ],
            'capabilitiesAlias' => [
                'code' => '<?php
                    /** @psalm-type Storage = write-props|io */
                    final class Repo {
                        /** @psalm-capabilities Storage */
                        public function save(): void {
                            echo "saved";
                        }
                    }

                    /** @psalm-import-type Storage from Repo */
                    final class Service {
                        /** @psalm-capabilities Storage */
                        public function run(Repo $r): void {
                            $r->save();
                        }

                        /** @psalm-capabilities Storage, read-globals */
                        public function runAndRead(Repo $r): int {
                            $r->save();
                            return Counter::$n;
                        }

                        /**
                         * @psalm-capabilities Storage
                         * @param Closure<Storage>(): void $f
                         */
                        public function call(Closure $f): void {
                            $f();
                        }
                    }

                    final class Counter {
                        public static int $n = 0;
                    }',
            ],
            'issetAndUnsetOnArrayAccessCallTheirOwnMethods' => [
                'code' => '<?php
                    /** @implements ArrayAccess<int, int> */
                    final class Vec implements ArrayAccess {
                        /** @var array<int, int> */
                        private array $a = [];
                        /** @psalm-mutation-free */
                        public function offsetExists($o): bool { return isset($this->a[$o]); }
                        public function offsetGet($o): int { echo "get"; return $this->a[$o]; }
                        public function offsetSet($o, $v): void { echo "set"; }
                        /** @psalm-external-mutation-free */
                        public function offsetUnset($o): void { unset($this->a[$o]); }
                    }

                    /** @psalm-mutation-free */
                    function has(Vec $v): bool {
                        return isset($v[0]);
                    }

                    /** @psalm-external-mutation-free */
                    function drop(): Vec {
                        $v = new Vec();
                        unset($v[0]);
                        return $v;
                    }',
            ],
            'closureCapturingByReferenceStaysLocalToItsScope' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param list<int> $xs
                     */
                    function sum(array $xs): int {
                        $total = 0;
                        $add = function (int $v) use (&$total): void {
                            $total += $v;
                        };
                        foreach ($xs as $x) {
                            $add($x);
                        }
                        return $total;
                    }

                    /** @psalm-pure */
                    function readLater(): int {
                        $x = 1;
                        $get = function () use (&$x): int {
                            return $x;
                        };
                        $x = 2;
                        return $get();
                    }

                    /** @psalm-pure */
                    function nested(): int {
                        $x = 1;
                        $outer = function () use (&$x): int {
                            $inner = function () use (&$x): void {
                                $x = 3;
                            };
                            $inner();
                            return $x;
                        };
                        return $outer();
                    }

                    /** @psalm-pure */
                    function factorial(int $n): int {
                        $fact = function (int $n) use (&$fact): int {
                            /** @var Closure(int): int $fact */
                            return $n <= 1 ? 1 : $n * $fact($n - 1);
                        };
                        return $fact($n);
                    }

                    /**
                     * @psalm-pure
                     * @param Closure<pure>(): int $f
                     */
                    function takesPure(Closure $f): int {
                        return $f();
                    }

                    /** @psalm-pure */
                    function byValue(): int {
                        $x = 1;
                        return takesPure(function () use ($x): int {
                            return $x;
                        });
                    }',
            ],
            'closureWritingByRefParamNeedsWriteRefsFromTheEnclosingScope' => [
                'code' => '<?php
                    /** @psalm-capabilities write-refs */
                    function set(int &$x): void {
                        $write = function () use (&$x): void {
                            $x = 1;
                        };
                        $write();
                    }

                    /** @psalm-capabilities write-refs */
                    function setDirectly(int &$x): void {
                        $x = 1;
                    }

                    /** @psalm-capabilities write-globals */
                    function setGlobal(): void {
                        global $g;
                        $g = 1;
                    }',
            ],
            'byReferenceArgumentsCostWhatTheyWrite' => [
                'code' => '<?php
                    final class Counter {
                        public int $n = 0;
                        /** @var list<int> */
                        public array $items = [];

                        /** @psalm-external-mutation-free */
                        public function bump(): void {
                            $this->n++;
                        }

                        /** @psalm-external-mutation-free */
                        public function sortItems(): void {
                            sort($this->items);
                        }
                    }

                    /** @psalm-capabilities write-props */
                    function viaMethod(Counter $c): void {
                        $c->bump();
                    }

                    /** @psalm-capabilities write-props */
                    function sortProperty(Counter $c): void {
                        sort($c->items);
                    }

                    /**
                     * @psalm-pure
                     * @param list<int> $a
                     * @return list<int>
                     */
                    function sortLocal(array $a): array {
                        sort($a);
                        return $a;
                    }

                    /** @psalm-capabilities write-refs */
                    function setRef(int &$x): void {
                        $x = 1;
                    }

                    /** @psalm-pure */
                    function passesLocal(): int {
                        $x = 0;
                        setRef($x);
                        return $x;
                    }

                    /** @psalm-capabilities write-refs */
                    function passesOwnByRefParam(int &$x): void {
                        setRef($x);
                    }',
            ],
            'bindingGlobalOnlyReadsIt' => [
                'code' => '<?php
                    /** @psalm-capabilities read-globals */
                    function readsGlobal(): int {
                        global $g;
                        return is_int($g) ? $g : 0;
                    }

                    /** @psalm-capabilities write-globals */
                    function writesGlobal(): void {
                        global $g;
                        $g = 1;
                    }',
            ],
            'foreachCallsTheIteratorMethodsOfAFreshIterator' => [
                'code' => '<?php
                    /**
                     * @implements Iterator<int, int>
                     * @psalm-external-mutation-free
                     */
                    final class Counter implements Iterator {
                        private int $i = 0;
                        public function current(): int { return $this->i; }
                        public function key(): int { return $this->i; }
                        public function next(): void { $this->i++; }
                        public function rewind(): void { $this->i = 0; }
                        public function valid(): bool { return $this->i < 3; }
                    }

                    /** @psalm-pure */
                    function sum(): int {
                        $s = 0;
                        foreach (new Counter() as $x) {
                            $s += $x;
                        }
                        return $s;
                    }

                    /** @psalm-external-mutation-free */
                    function sumGiven(Counter $c): int {
                        $s = 0;
                        foreach ($c as $x) {
                            $s += $x;
                        }
                        return $s;
                    }',
            ],
            'foreachOverAGeneratorACallProducedIsPure' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @return Generator<int, int>
                     */
                    function gen(): Generator {
                        yield 1;
                        yield 2;
                        return 0;
                    }

                    /**
                     * @implements IteratorAggregate<int, int>
                     */
                    final class Bag implements IteratorAggregate {
                        /**
                         * @psalm-mutation-free
                         * @return Generator<int, int>
                         */
                        public function getIterator(): Generator {
                            yield 3;
                        }
                    }

                    /** @psalm-pure */
                    function sum(Bag $bag): int {
                        $s = 0;
                        foreach (gen() as $x) {
                            $s += $x;
                        }
                        foreach ($bag as $x) {
                            $s += $x;
                        }
                        return $s;
                    }',
            ],
            'paramDefaultsMayUseTheGlobalsOfAWriteGlobalsFunction' => [
                'code' => '<?php
                    final class Box {
                        /** @psalm-capabilities read-globals */
                        public function __construct() {}
                    }

                    /** @psalm-capabilities write-globals */
                    function make(Box $b = new Box()): Box {
                        return $b;
                    }',
            ],
            'paramDefaultsOfUnannotatedFunctionsAreFree' => [
                'code' => '<?php
                    final class Box {
                        public function __construct() { echo "made"; }
                    }

                    function make(Box $b = new Box()): Box {
                        return $b;
                    }',
            ],
            'objectsWithADestructorThatLeaveTheFunctionAreNotDestroyedThere' => [
                'code' => '<?php
                    final class Guard {
                        /** @psalm-pure */
                        public function __construct() {}
                        public function __destruct() { echo "released"; }
                    }

                    /** @psalm-pure */
                    function make(): Guard {
                        $g = new Guard();
                        return $g;
                    }

                    /**
                     * @psalm-pure
                     * @param list<Guard> $guards
                     * @return list<Guard>
                     */
                    function keep(array $guards): array {
                        $g = new Guard();
                        $guards[] = $g;
                        return $guards;
                    }

                    /** @psalm-pure */
                    function given(Guard $g): int {
                        return 1;
                    }',
            ],
            'generatorPurityTemplateIsBoundByTheGeneratorFunction' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @return Generator<int, int>
                     */
                    function pureGen(): Generator { yield 1; return 0; }

                    /**
                     * @psalm-capabilities io
                     * @return Generator<int, int>
                     */
                    function ioGen(): Generator { echo "x"; yield 1; return 0; }

                    /** @psalm-pure */
                    function sumFresh(): int {
                        $s = 0;
                        foreach (pureGen() as $x) {
                            $s += $x;
                        }
                        return $s;
                    }

                    /** @psalm-pure */
                    function sumHeld(): int {
                        $g = pureGen();
                        $s = 0;
                        foreach ($g as $x) {
                            $s += $x;
                        }
                        $g->rewind();
                        return $s;
                    }

                    /**
                     * @psalm-external-mutation-free
                     * @param Generator<int, int, mixed, mixed, pure> $g
                     */
                    function sumGiven(Generator $g): int {
                        $s = 0;
                        foreach ($g as $x) {
                            $s += $x;
                        }
                        return $s;
                    }

                    /**
                     * @psalm-capabilities external-mutation-free|io
                     * @param Traversable<int, int, io> $t
                     */
                    function sumAny(Traversable $t): int {
                        $s = 0;
                        foreach ($t as $x) {
                            $s += $x;
                        }
                        return $s;
                    }

                    /** @psalm-capabilities external-mutation-free|io */
                    function pass(): int {
                        return sumGiven(pureGen()) + sumAny(pureGen()) + sumAny(ioGen());
                    }',
            ],
            'generatorPurityTemplateFollowsThePurityTemplatesOfTheGeneratorFunction' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param Closure<_>(): int $f
                     * @return Generator<int, int>
                     */
                    function polyGen(Closure $f): Generator { yield $f(); return 0; }

                    /**
                     * @psalm-external-mutation-free
                     * @param Generator<int, int, mixed, mixed, pure> $g
                     */
                    function sumGiven(Generator $g): int {
                        $s = 0;
                        foreach ($g as $x) {
                            $s += $x;
                        }
                        return $s;
                    }

                    /** @psalm-external-mutation-free */
                    function pass(): int {
                        return sumGiven(polyGen(fn(): int => 1));
                    }',
            ],
            'generatorClosureBindsThePurityTemplateToItsOwnPurity' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function sum(): int {
                        $gen = function (): Generator { yield 1; return 0; };
                        $s = 0;
                        foreach ($gen() as $x) {
                            $s += $x;
                        }
                        return $s;
                    }',
            ],
            'iteratorPurityTemplateIsBoundFromTheIterationMethods' => [
                'code' => '<?php
                    /**
                     * @implements Iterator<int, int>
                     * @psalm-external-mutation-free
                     */
                    final class Counter implements Iterator {
                        private int $i = 0;
                        public function current(): int { return $this->i; }
                        public function key(): int { return $this->i; }
                        public function next(): void { $this->i++; }
                        public function rewind(): void { $this->i = 0; }
                        public function valid(): bool { return $this->i < 3; }
                    }

                    /** @implements IteratorAggregate<int, int> */
                    final class Bag implements IteratorAggregate {
                        /**
                         * @psalm-mutation-free
                         * @return Generator<int, int>
                         */
                        public function getIterator(): Generator { yield 3; return 0; }
                    }

                    /**
                     * @psalm-external-mutation-free
                     * @param Iterator<int, int, external-mutation-free> $it
                     */
                    function sumCounter(Iterator $it): int {
                        $s = 0;
                        foreach ($it as $x) {
                            $s += $x;
                        }
                        return $s;
                    }

                    /**
                     * @psalm-external-mutation-free
                     * @param IteratorAggregate<int, int, pure> $bag
                     */
                    function sumBag(IteratorAggregate $bag): int {
                        $s = 0;
                        foreach ($bag as $x) {
                            $s += $x;
                        }
                        return $s;
                    }

                    /** @psalm-external-mutation-free */
                    function pass(Counter $c, Bag $b): int {
                        return sumCounter($c) + sumBag($b) + sumCounter($b->getIterator());
                    }',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'externalMutationFreeCannotReadStatics' => [
                'code' => '<?php
                    final class S { public static int $n = 0; }

                    /** @psalm-external-mutation-free */
                    function emf(): int {
                        return S::$n;
                    }',
                'error_message' => 'ImpureStaticProperty',
            ],
            'externalMutationFreeCannotWriteStatics' => [
                'code' => '<?php
                    final class S { public static int $n = 0; }

                    /** @psalm-external-mutation-free */
                    function emf(): int {
                        S::$n = 1;
                        return 1;
                    }',
                'error_message' => 'ImpureStaticProperty',
            ],
            'readGlobalsCannotWrite' => [
                'code' => '<?php
                    final class S { public static int $n = 0; }

                    /** @psalm-capabilities read-globals */
                    function rg(): int {
                        S::$n = 1;
                        return 1;
                    }',
                'error_message' => 'ImpureStaticProperty',
            ],
            'readGlobalsCannotCallWriteGlobals' => [
                'code' => '<?php
                    /** @psalm-capabilities write-globals */
                    function wg(): int {
                        return 1;
                    }

                    /** @psalm-capabilities read-globals */
                    function rg(): int {
                        return wg();
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'writePropsCannotDoIo' => [
                'code' => '<?php
                    /** @psalm-capabilities write-props */
                    function wp(): int {
                        echo "x";
                        return 1;
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'writePropsCannotCallBuiltinWithSideEffects' => [
                'code' => '<?php
                    /** @psalm-capabilities write-props */
                    function wp(): int {
                        return mt_rand();
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'writeThisPropsCannotWriteOtherObjects' => [
                'code' => '<?php
                    final class Obj { public int $x = 0; }

                    /** @psalm-capabilities write-this-props */
                    function wtp(Obj $o): int {
                        $o->x = 1;
                        return 1;
                    }',
                'error_message' => 'ImpurePropertyAssignment',
            ],
            'staticVariableNeedsWriteGlobals' => [
                'code' => '<?php
                    /** @psalm-external-mutation-free */
                    function counter(): int {
                        static $i = 0;
                        return ++$i;
                    }',
                'error_message' => 'ImpureStaticVariable',
            ],
            'unknownCapability' => [
                'code' => '<?php
                    /** @psalm-capabilities teleport */
                    function f(): int {
                        return 1;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'closureWithCapabilitiesPassedWhereFewerExpected' => [
                'code' => '<?php
                    /** @param Closure<read-globals>(): void $f */
                    function run(Closure $f): void {}

                    function test(): void {
                        run(function (): void { echo "x"; });
                    }',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'callingAnImpureClosureIsAnEffect' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function calls(): int {
                        $f = function (): void { echo "hi"; };
                        $f();
                        return 1;
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'overrideMayNotRequireMoreThanPure' => [
                'code' => '<?php
                    abstract class P {
                        /** @psalm-pure */
                        abstract public function m(): int;
                    }

                    final class C extends P {
                        /** @psalm-external-mutation-free */
                        public function m(): int {
                            return 1;
                        }
                    }',
                'error_message' => 'ImmutableDependency',
            ],
            'overrideMayNotRequireMoreThanMutationFree' => [
                'code' => '<?php
                    abstract class P {
                        /** @psalm-mutation-free */
                        abstract public function m(): int;
                    }

                    final class C extends P {
                        /** @psalm-external-mutation-free */
                        public function m(): int {
                            return 1;
                        }
                    }',
                'error_message' => 'ImmutableDependency',
            ],
            'overrideMayNotRequireOtherCapabilities' => [
                'code' => '<?php
                    abstract class P {
                        /** @psalm-capabilities io */
                        abstract public function m(): int;
                    }

                    final class C extends P {
                        /** @psalm-capabilities write-props */
                        public function m(): int {
                            return 1;
                        }
                    }',
                'error_message' => 'ImmutableDependency',
            ],
            'impureCloneMethod' => [
                'code' => '<?php
                    final class Point {
                        public function __clone() {
                            echo "cloned";
                        }
                    }

                    /** @psalm-pure */
                    function copyPoint(Point $p): Point {
                        return clone $p;
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'impureToStringInInterpolation' => [
                'code' => '<?php
                    final class Name {
                        public function __toString(): string {
                            echo "x";
                            return "x";
                        }
                    }

                    /** @psalm-pure */
                    function greet(Name $n): string {
                        return "hello $n";
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'impureToStringInCast' => [
                'code' => '<?php
                    final class Name {
                        public function __toString(): string {
                            echo "x";
                            return "x";
                        }
                    }

                    /** @psalm-pure */
                    function greet(Name $n): string {
                        return (string) $n;
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'impureInvoke' => [
                'code' => '<?php
                    final class Inc {
                        public function __invoke(int $i): int {
                            echo "x";
                            return $i + 1;
                        }
                    }

                    /** @psalm-pure */
                    function inc(Inc $inc): int {
                        return $inc(1);
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'impureMagicGet' => [
                'code' => '<?php
                    final class Magic {
                        public function __get(string $name): int {
                            echo $name;
                            return 1;
                        }
                    }

                    /**
                     * @psalm-pure
                     * @psalm-suppress UndefinedMagicPropertyFetch, UndefinedThisPropertyFetch
                     */
                    function get(Magic $m): int {
                        return $m->foo;
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'impureOffsetGet' => [
                'code' => '<?php
                    /** @template-implements ArrayAccess<string, int> */
                    final class Bag implements ArrayAccess {
                        public function offsetExists(mixed $o): bool { return true; }
                        public function offsetGet(mixed $o): int { echo "x"; return 1; }
                        public function offsetSet(mixed $o, mixed $v): void {}
                        public function offsetUnset(mixed $o): void {}
                    }

                    /** @psalm-pure */
                    function get(Bag $b): int {
                        return $b["x"];
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'impureOffsetSet' => [
                'code' => '<?php
                    /** @template-implements ArrayAccess<string, int> */
                    final class Bag implements ArrayAccess {
                        public function offsetExists(mixed $o): bool { return true; }
                        /** @psalm-pure */
                        public function offsetGet(mixed $o): int { return 1; }
                        public function offsetSet(mixed $o, mixed $v): void { echo "x"; }
                        public function offsetUnset(mixed $o): void {}
                    }

                    /** @psalm-pure */
                    function set(Bag $b): int {
                        $b["x"] = 1;
                        return 1;
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'throwingWithImpureConstructor' => [
                'code' => '<?php
                    final class MyException extends Exception {
                        /** @psalm-capabilities io */
                        public function __construct() {
                            echo "created";
                            parent::__construct("x");
                        }
                    }

                    /** @psalm-pure */
                    function fail(): int {
                        throw new MyException();
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'literalCallableStringOfImpureFunction' => [
                'code' => '<?php
                    function impure(): void {
                        echo "x";
                    }

                    /** @psalm-pure */
                    function call(): int {
                        $f = "impure";
                        $f();
                        return 1;
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'callUserFuncOfImpureFunction' => [
                'code' => '<?php
                    function impure(): void {
                        echo "x";
                    }

                    /** @psalm-pure */
                    function call(): int {
                        call_user_func("impure");
                        return 1;
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'unknownCallableStringMayDoAnything' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param callable-string $f
                     */
                    function call(string $f): int {
                        $f();
                        return 1;
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'unknownCallableArrayMayDoAnything' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param array{0: object, 1: string} $f
                     */
                    function call(array $f): int {
                        $f();
                        return 1;
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'defaultValueWithImpureConstructor' => [
                'code' => '<?php
                    final class Options {
                        public function __construct() {
                            echo "created";
                        }
                    }

                    /** @psalm-pure */
                    function withDefault(Options $o = new Options()): int {
                        return 1;
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'readGlobalsCannotMutateGlobalObject' => [
                'code' => '<?php
                    final class Box {
                        public int $x = 0;
                        public static ?Box $g = null;
                    }

                    /** @psalm-capabilities read-globals|write-props */
                    function leak(): void {
                        $b = Box::$g;
                        if ($b !== null) {
                            $b->x = 1;
                        }
                    }',
                'error_message' => 'ImpurePropertyAssignment',
            ],
            'readGlobalsGetterResultCannotBeMutated' => [
                'code' => '<?php
                    final class Box {
                        public int $x = 0;
                        public static ?Box $g = null;
                    }

                    /** @psalm-capabilities read-globals */
                    function get(): ?Box {
                        return Box::$g;
                    }

                    /** @psalm-capabilities read-globals|write-props */
                    function leak(): void {
                        $b = get();
                        if ($b !== null) {
                            $b->x = 1;
                        }
                    }',
                'error_message' => 'ImpurePropertyAssignment',
            ],
            'mutatingMethodOnGlobalObjectNeedsWriteGlobals' => [
                'code' => '<?php
                    final class Box {
                        public int $x = 0;
                        public static ?Box $g = null;

                        /** @psalm-external-mutation-free */
                        public function bump(): void {
                            $this->x++;
                        }
                    }

                    /** @psalm-capabilities read-globals|external-mutation-free|write-props */
                    function leak(): void {
                        $b = Box::$g;
                        if ($b !== null) {
                            $b->bump();
                        }
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'globalObjectReachedThroughPropertyCannotBeMutated' => [
                'code' => '<?php
                    final class Box {
                        public int $x = 0;
                        public ?Box $child = null;
                        public static ?Box $g = null;
                    }

                    /** @psalm-capabilities read-globals|write-props */
                    function leak(): void {
                        $b = Box::$g;
                        if ($b !== null && $b->child !== null) {
                            $b->child->x = 1;
                        }
                    }',
                'error_message' => 'ImpurePropertyAssignment',
            ],
            'passingGlobalObjectToMutatorNeedsWriteGlobals' => [
                'code' => '<?php
                    final class Box {
                        public int $x = 0;
                        public static ?Box $g = null;
                    }

                    /** @psalm-capabilities write-props */
                    function mutate(Box $b): void {
                        $b->x = 1;
                    }

                    /** @psalm-capabilities read-globals|write-props */
                    function leak(): void {
                        $b = Box::$g;
                        if ($b !== null) {
                            mutate($b);
                        }
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'superglobalObjectCannotBeMutated' => [
                'code' => '<?php
                    final class Box {
                        public int $x = 0;
                    }

                    /**
                     * @psalm-capabilities read-globals|write-props
                     * @psalm-suppress MixedAssignment
                     */
                    function leak(): void {
                        $b = $GLOBALS["box"];
                        if ($b instanceof Box) {
                            $b->x = 1;
                        }
                    }',
                'error_message' => 'ImpurePropertyAssignment',
            ],
            'builtinFirstClassCallableIsImpure' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function roll(): int {
                        $r = mt_rand(...);
                        return $r();
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'dynamicNewChecksConstructor' => [
                'code' => '<?php
                    final class Noisy {
                        public function __construct() {
                            echo "created";
                        }
                    }

                    /**
                     * @psalm-pure
                     * @param class-string<Noisy> $c
                     */
                    function make(string $c): Noisy {
                        return new $c();
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'dynamicNewOfUnknownClassIsImpure' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param class-string $c
                     */
                    function make(string $c): object {
                        return new $c();
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'throwingExceptionWithUnannotatedImpureConstructor' => [
                'code' => '<?php
                    final class MyException extends Exception {
                        public function __construct() {
                            echo "created";
                            parent::__construct("x");
                        }
                    }

                    /** @psalm-pure */
                    function fail(): int {
                        throw new MyException();
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'capabilitiesAliasMustDenoteCapabilities' => [
                'code' => '<?php
                    /** @psalm-type Count = int */
                    final class Repo {
                        /** @psalm-capabilities Count */
                        public function save(): void {}
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'capabilitiesAliasIsEnforced' => [
                'code' => '<?php
                    /** @psalm-type Storage = write-props|io */
                    final class Repo {
                        public static int $n = 0;

                        /** @psalm-capabilities Storage */
                        public function save(): void {
                            self::$n++;
                        }
                    }',
                'error_message' => 'ImpureStaticProperty',
            ],
            'importedCapabilitiesAliasIsEnforced' => [
                'code' => '<?php
                    /** @psalm-type Storage = write-props|io */
                    final class Repo {
                        public static int $n = 0;
                    }

                    /** @psalm-import-type Storage from Repo */
                    final class Service {
                        /** @psalm-capabilities Storage */
                        public function run(): void {
                            Repo::$n++;
                        }
                    }',
                'error_message' => 'ImpureStaticProperty',
            ],
            'unsetOnArrayAccessCallsOffsetUnset' => [
                'code' => '<?php
                    /** @implements ArrayAccess<int, int> */
                    final class Vec implements ArrayAccess {
                        /** @psalm-mutation-free */
                        public function offsetExists($o): bool { return true; }
                        /** @psalm-mutation-free */
                        public function offsetGet($o): int { return 1; }
                        /** @psalm-external-mutation-free */
                        public function offsetSet($o, $v): void {}
                        public function offsetUnset($o): void { echo "unset"; }
                    }

                    /** @psalm-external-mutation-free */
                    function drop(Vec $v): void {
                        unset($v[0]);
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'issetOnArrayAccessCallsOffsetExists' => [
                'code' => '<?php
                    /** @implements ArrayAccess<int, int> */
                    final class Vec implements ArrayAccess {
                        public function offsetExists($o): bool { echo "exists"; return true; }
                        /** @psalm-mutation-free */
                        public function offsetGet($o): int { return 1; }
                        /** @psalm-external-mutation-free */
                        public function offsetSet($o, $v): void {}
                        /** @psalm-external-mutation-free */
                        public function offsetUnset($o): void {}
                    }

                    /** @psalm-mutation-free */
                    function has(Vec $v): bool {
                        return isset($v[0]);
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'closureWritingCapturedVariableIsNotPure' => [
                'code' => '<?php
                    /** @param Closure<pure>(int): void $f */
                    function takesPure(Closure $f): void {}

                    /** @psalm-pure */
                    function escape(): void {
                        $total = 0;
                        $add = function (int $v) use (&$total): void {
                            $total += $v;
                        };
                        takesPure($add);
                    }',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'closureReadingCapturedVariableIsNotPure' => [
                'code' => '<?php
                    /** @param Closure<pure>(): int $f */
                    function takesPure(Closure $f): void {}

                    /** @psalm-pure */
                    function escape(): void {
                        $x = 0;
                        $get = function () use (&$x): int {
                            return $x;
                        };
                        takesPure($get);
                    }',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'closureWritingByRefParamChargesTheEnclosingScope' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function set(int &$x): void {
                        $write = function () use (&$x): void {
                            $x = 1;
                        };
                        $write();
                    }',
                'error_message' => 'ImpureByReferenceAssignment',
            ],
            'closureMutatingCapturedObjectNeedsWriteProps' => [
                'code' => '<?php
                    final class Box {
                        public int $x = 0;
                    }

                    /** @psalm-pure */
                    function f(Box $b): void {
                        $set = function () use ($b): void {
                            $b->x = 1;
                        };
                        $set();
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'byReferenceArgumentOnPropertyNeedsWriteProps' => [
                'code' => '<?php
                    final class Counter {
                        /** @var list<int> */
                        public array $items = [];
                    }

                    /** @psalm-mutation-free */
                    function sortProperty(Counter $c): void {
                        sort($c->items);
                    }',
                'error_message' => 'ImpurePropertyAssignment',
            ],
            'byReferenceArgumentOnOwnByRefParamNeedsWriteRefs' => [
                'code' => '<?php
                    /** @psalm-capabilities write-refs */
                    function setRef(int &$x): void {
                        $x = 1;
                    }

                    /** @psalm-pure */
                    function passesOwnByRefParam(int &$x): int {
                        setRef($x);
                        return $x;
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'writingBoundGlobalNeedsWriteGlobals' => [
                'code' => '<?php
                    /** @psalm-capabilities read-globals */
                    function writesGlobal(): void {
                        global $g;
                        $g = 1;
                    }',
                'error_message' => 'ImpureGlobalVariable',
            ],
            'coalesceOnArrayAccessCallsOffsetGet' => [
                'code' => '<?php
                    /** @implements ArrayAccess<int, int> */
                    final class Vec implements ArrayAccess {
                        /** @psalm-mutation-free */
                        public function offsetExists($o): bool { return true; }
                        public function offsetGet($o): int { echo "get"; return 1; }
                        /** @psalm-external-mutation-free */
                        public function offsetSet($o, $v): void {}
                        /** @psalm-external-mutation-free */
                        public function offsetUnset($o): void {}
                    }

                    /** @psalm-mutation-free */
                    function first(Vec $v): int {
                        return $v[0] ?? 2;
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'foreachCallsTheIteratorMethodsOfAGivenIterator' => [
                'code' => '<?php
                    /**
                     * @implements Iterator<int, int>
                     * @psalm-external-mutation-free
                     */
                    final class Counter implements Iterator {
                        private int $i = 0;
                        public function current(): int { return $this->i; }
                        public function key(): int { return $this->i; }
                        public function next(): void { $this->i++; }
                        public function rewind(): void { $this->i = 0; }
                        public function valid(): bool { return $this->i < 3; }
                    }

                    /** @psalm-pure */
                    function sum(Counter $c): int {
                        $s = 0;
                        foreach ($c as $x) {
                            $s += $x;
                        }
                        return $s;
                    }',
                'error_message' => 'ImpureMethodCall - src' . DIRECTORY_SEPARATOR . 'somefile.php:18:34 - The context is pure but iterating over Counter requires',
            ],
            'foreachOverAnIteratorWithImpureMethods' => [
                'code' => '<?php
                    /**
                     * @implements Iterator<int, int>
                     */
                    final class Reader implements Iterator {
                        /** @psalm-mutation-free */
                        public function current(): int { return 1; }
                        /** @psalm-mutation-free */
                        public function key(): int { return 1; }
                        /** @psalm-capabilities io */
                        public function next(): void { echo "read"; }
                        /** @psalm-mutation-free */
                        public function rewind(): void {}
                        /** @psalm-mutation-free */
                        public function valid(): bool { return false; }
                    }

                    /** @psalm-capabilities write-props */
                    function sum(): int {
                        $s = 0;
                        foreach (new Reader() as $x) {
                            $s += $x;
                        }
                        return $s;
                    }',
                'error_message' => 'ImpureMethodCall - src' . DIRECTORY_SEPARATOR . 'somefile.php:21:34 - The context is write-props but iterating over Reader requires io',
            ],
            'paramDefaultsOfAnnotatedFunctionsArePure' => [
                'code' => '<?php
                    final class Box {
                        /** @psalm-capabilities io */
                        public function __construct() { echo "made"; }
                    }

                    /** @psalm-capabilities io */
                    function make(Box $b = new Box()): Box {
                        return $b;
                    }',
                'error_message' => 'ImpureMethodCall - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:44 - Parameter default values are pure but constructor Box::__construct requires io',
            ],
            'anObjectDroppedWhenTheFunctionEndsRunsItsDestructor' => [
                'code' => '<?php
                    final class Guard {
                        /** @psalm-pure */
                        public function __construct() {}
                        public function __destruct() { echo "released"; }
                    }

                    /** @psalm-pure */
                    function guarded(): int {
                        $g = new Guard();
                        return 1;
                    }',
                'error_message' => 'ImpureMethodCall - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:30 - The context is pure but destroying $g (Guard::__destruct) requires',
            ],
            'anUnsetObjectRunsItsDestructor' => [
                'code' => '<?php
                    final class Guard {
                        public function __destruct() { echo "released"; }
                    }

                    /** @psalm-pure */
                    function release(Guard $g): int {
                        unset($g);
                        return 1;
                    }',
                'error_message' => 'ImpureMethodCall - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:25 - The context is pure but destroying $g (Guard::__destruct) requires',
            ],
            'generatorWithoutAKnownPurityIsImpureToIterate' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param Generator<int, int> $g
                     */
                    function sum(Generator $g): int {
                        $s = 0;
                        foreach ($g as $x) {
                            $s += $x;
                        }
                        return $s;
                    }',
                'error_message' => 'ImpureMethodCall - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:34 - The context is pure but iterating over Generator<int, int, mixed, mixed> requires impure',
            ],
            'callingAGeneratorFunctionCostsItsBody' => [
                'code' => '<?php
                    /**
                     * @psalm-capabilities io
                     * @return Generator<int, int>
                     */
                    function ioGen(): Generator { echo "x"; yield 1; return 0; }

                    /** @psalm-capabilities write-globals */
                    function make(): Generator {
                        return ioGen();
                    }',
                'error_message' => 'ImpureFunctionCall - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:32 - The context is write-globals but function call on iogen requires io',
            ],
            'iteratingAnIoGeneratorNeedsIo' => [
                'code' => '<?php
                    /**
                     * @psalm-external-mutation-free
                     * @param Generator<int, int, mixed, mixed, io> $g
                     */
                    function sum(Generator $g): int {
                        $s = 0;
                        foreach ($g as $x) {
                            $s += $x;
                        }
                        return $s;
                    }',
                'error_message' => 'ImpureMethodCall - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:34 - The context is external-mutation-free but iterating over Generator<int, int, mixed, mixed, io> requires',
            ],
            'resumingAnIoGeneratorNeedsIo' => [
                'code' => '<?php
                    /**
                     * @psalm-external-mutation-free
                     * @param Generator<int, int, mixed, mixed, io> $g
                     */
                    function step(Generator $g): void {
                        $g->next();
                    }',
                'error_message' => 'ImpureMethodCall - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:25 - The context is external-mutation-free but method Generator::next requires',
            ],
            'generatorPurityTemplateIsCovariant' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param Closure<_>(): int $f
                     * @return Generator<int, int>
                     */
                    function polyGen(Closure $f): Generator { yield $f(); return 0; }

                    /**
                     * @psalm-external-mutation-free
                     * @param Generator<int, int, mixed, mixed, pure> $g
                     */
                    function sumGiven(Generator $g): int {
                        $s = 0;
                        foreach ($g as $x) {
                            $s += $x;
                        }
                        return $s;
                    }

                    /** @psalm-capabilities io */
                    function pass(): int {
                        return sumGiven(polyGen(function (): int { echo "y"; return 1; }));
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:23:41 - Argument 1 of sumGiven expects Generator<int, int, mixed, mixed, pure>, but Generator<int, int, mixed, mixed, io> provided',
            ],
            'iteratorMethodsMustFitTheBoundPurityTemplate' => [
                'code' => '<?php
                    /** @implements Iterator<int, int, pure> */
                    final class Reader implements Iterator {
                        /** @psalm-mutation-free */
                        public function current(): int { return 1; }
                        /** @psalm-mutation-free */
                        public function key(): int { return 1; }
                        public function next(): void { echo "read"; }
                        /** @psalm-mutation-free */
                        public function rewind(): void {}
                        /** @psalm-mutation-free */
                        public function valid(): bool { return false; }
                    }',
                'error_message' => 'ImmutableDependency - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:33 - Iterator::next is external-mutation-free, but Reader::next additionally requires',
            ],
            'iteratorPurityBoundFromTheMethodsIsUsedForSubtyping' => [
                'code' => '<?php
                    /**
                     * @implements Iterator<int, int>
                     * @psalm-external-mutation-free
                     */
                    final class Counter implements Iterator {
                        private int $i = 0;
                        public function current(): int { return $this->i; }
                        public function key(): int { return $this->i; }
                        public function next(): void { $this->i++; }
                        public function rewind(): void { $this->i = 0; }
                        public function valid(): bool { return $this->i < 3; }
                    }

                    /**
                     * @psalm-external-mutation-free
                     * @param Iterator<int, int, pure> $it
                     */
                    function sum(Iterator $it): int {
                        $s = 0;
                        foreach ($it as $x) {
                            $s += $x;
                        }
                        return $s;
                    }

                    /** @psalm-external-mutation-free */
                    function pass(Counter $c): int {
                        return sum($c);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:29:36 - Argument 1 of sum expects Iterator<int, int, pure>, but Counter provided',
            ],
            'aDiscardedNewObjectRunsItsDestructor' => [
                'code' => '<?php
                    final class Guard {
                        /** @psalm-pure */
                        public function __construct() {}
                        public function __destruct() { echo "released"; }
                    }

                    /** @psalm-pure */
                    function guarded(): int {
                        new Guard();
                        return 1;
                    }',
                'error_message' => 'ImpureMethodCall - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:25 - The context is pure but destroying the discarded new object (Guard::__destruct) requires',
            ],
        ];
    }

    public function testPureNewAndStaticCallsKeepPropertyRefinements(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                final class A { public ?int $x = null; }

                final class S {
                    /** @psalm-pure */
                    public function __construct() {}
                    /** @psalm-pure */
                    public static function s(): int { return 1; }
                }

                /** @psalm-capabilities write-globals */
                function touchGlobals(): void {}

                function keepNew(A $a): int {
                    if ($a->x === null) {
                        return 0;
                    }
                    new S();
                    return $a->x;
                }

                function keepStatic(A $a): int {
                    if ($a->x === null) {
                        return 0;
                    }
                    S::s();
                    return $a->x;
                }

                function keepAfterGlobals(A $a): int {
                    if ($a->x === null) {
                        return 0;
                    }
                    touchGlobals();
                    return $a->x;
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testWriteGlobalsCallForgetsStaticPropertyRefinements(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('NullableReturnStatement');
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                final class A { public static ?int $x = null; }

                /** @psalm-capabilities write-globals */
                function touchGlobals(): void { A::$x = null; }

                function forget(): int {
                    if (A::$x === null) {
                        return 0;
                    }
                    touchGlobals();
                    return A::$x;
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testWritePropsStaticCallForgetsPropertyRefinements(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('NullableReturnStatement');
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                final class A { public ?int $x = null; }

                final class S {
                    /** @psalm-capabilities write-props */
                    public static function w(A $a): void { $a->x = null; }
                }

                function forget(A $a): int {
                    if ($a->x === null) {
                        return 0;
                    }
                    S::w($a);
                    return $a->x;
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }
}
