<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

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
                        return mt_rand();
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
                'error_message' => 'MissingDocblockType',
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
        ];
    }
}
