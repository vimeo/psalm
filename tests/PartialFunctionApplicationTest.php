<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class PartialFunctionApplicationTest extends TestCase
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
            'PartialApplication:NamedFunction' => [
                'code' => <<<'PHP'
                    <?php
                        function add(int $a, int $b): int {
                            return $a + $b;
                        }
                        $add_one = add(1, ?);
                        $result = $add_one(2);
                    PHP,
                'assertions' => [
                    '$add_one===' => 'Closure[impure](int):int',
                    '$result===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:BuiltInFunction' => [
                'code' => <<<'PHP'
                    <?php
                        $closure = strlen(?);
                    PHP,
                'assertions' => [
                    '$closure===' => 'Closure[pure](string):int<0, max>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:PlaceholdersAreRequired' => [
                'code' => <<<'PHP'
                    <?php
                        function add(int $a, int $b = 2): int {
                            return $a + $b;
                        }
                        $closure = add(?, ?);
                    PHP,
                'assertions' => [
                    '$closure===' => 'Closure[impure](int, int):int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NamedPlaceholdersKeepTheirOrder' => [
                'code' => <<<'PHP'
                    <?php
                        function format(int $a, string $b, float $c, bool $d = false): string {
                            return "$a$b$c$d";
                        }
                        $closure = format(c: ?, a: ?, b: "b");
                    PHP,
                'assertions' => [
                    '$closure===' => 'Closure[impure](float, int):string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:VariadicPlaceholderKeepsTheRest' => [
                'code' => <<<'PHP'
                    <?php
                        function format(int $a, string $b, float $c, bool $d = false): string {
                            return "$a$b$c$d";
                        }
                        $rest = format(1, ...);
                        $named_and_rest = format(?, c: 1.5, ...);
                    PHP,
                'assertions' => [
                    '$rest===' => 'Closure[impure](string, float, bool=):string',
                    '$named_and_rest===' => 'Closure[impure](int, string, bool=):string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:Thunk' => [
                'code' => <<<'PHP'
                    <?php
                        function add(int $a, int $b): int {
                            return $a + $b;
                        }
                        $thunk = add(1, 2, ...);
                    PHP,
                'assertions' => [
                    '$thunk===' => 'Closure[impure]():int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:VariadicFunction' => [
                'code' => <<<'PHP'
                    <?php
                        function sum(int $a, int $b = 0, float ...$rest): float {
                            return $a + $b + array_sum($rest);
                        }
                        $keeps_variadic = sum(1, ...);
                        $fills_variadic = sum(?, ?, ?, ?);
                        $binds_variadic = sum(1, 2, 3.5, ...);
                    PHP,
                'assertions' => [
                    '$keeps_variadic===' => 'Closure[impure](int=, float...):float',
                    '$fills_variadic===' => 'Closure[impure](int, int, float, float):float',
                    '$binds_variadic===' => 'Closure[impure](float...):float',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:InstanceMethod' => [
                'code' => <<<'PHP'
                    <?php
                        class A {
                            public function add(int $a, int $b): int {
                                return $a + $b;
                            }
                        }
                        $closure = (new A)->add(?, 2);
                    PHP,
                'assertions' => [
                    '$closure===' => 'Closure[impure](int):int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:StaticMethod' => [
                'code' => <<<'PHP'
                    <?php
                        class A {
                            public static function make(int $a, string $b = ""): self {
                                return new self();
                            }
                        }
                        $closure = A::make(?);
                        $object = $closure(1);
                    PHP,
                'assertions' => [
                    '$closure===' => 'Closure[impure](int):A',
                    '$object===' => 'A',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:UnknownCallee' => [
                'code' => <<<'PHP'
                    <?php
                        function f(callable $callable, int $a): void {
                            $closure = $callable($a, ?);
                        }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:InAssertionScrapedPositions' => [
                'code' => <<<'PHP'
                    <?php
                        $a = count(?) > 0;
                        $b = count(?) === 1;
                        $c = gettype(?) === "string";
                        $d = get_class(?) === "stdClass";
                        $e = substr(?, 1) === "x";
                        if (is_int(?)) {}
                        if (!is_string(?)) {}
                        define(?, 1);
                        $f = match (count(?)) { default => 1 };
                    PHP,
                'assertions' => [],
                'ignored_issues' => [
                    'TypeDoesNotContainType',
                    'RedundantCondition',
                ],
                'php_version' => '8.6',
            ],
            'PartialApplication:BoundArgumentSideEffectsAreKept' => [
                'code' => <<<'PHP'
                    <?php
                        function combine(int $a, int $b): int {
                            return $a + $b;
                        }
                        $closure = combine($assigned = 1, ?);
                    PHP,
                'assertions' => [
                    '$assigned===' => '1',
                    '$closure===' => 'Closure[impure](int):int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:AssertionOfTheCalleeDoesNotNarrowBoundArguments' => [
                'code' => <<<'PHP'
                    <?php
                        /**
                         * @psalm-assert int $x
                         */
                        function assertInt(mixed $x, int $other): void {}

                        function f(mixed $x): void {
                            $closure = assertInt($x, ?);
                            /** @psalm-check-type-exact $x = mixed */
                        }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:ImpureCalleeDoesNotInvalidatePropertyNarrowing' => [
                'code' => <<<'PHP'
                    <?php
                        final class A {
                            public ?int $prop = null;
                            public function reset(int $a, int $b): void {
                                $this->prop = null;
                            }
                        }
                        function f(A $a): void {
                            if ($a->prop === null) {
                                return;
                            }
                            $closure = $a->reset(1, ?);
                            $prop = $a->prop;
                            /** @psalm-check-type-exact $prop = int */
                        }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:CalleeIsEvaluatedOnce' => [
                'code' => <<<'PHP'
                    <?php
                        /** @var list<Closure(int, int): int> $functions */
                        $functions = [];
                        $index = 0;
                        $closure = ($functions[$index++])(?, 1);
                    PHP,
                'assertions' => [
                    '$index===' => '1',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:UnionOfClosuresIsNotNarrowedToOne' => [
                'code' => <<<'PHP'
                    <?php
                        /**
                         * @param Closure(int): int|Closure(string): string $closure
                         */
                        function f(Closure $closure): void {
                            $partial = $closure(?);
                            /** @psalm-check-type-exact $partial = Closure */
                        }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:ClassTemplateMethodsOfAGenericReceiver' => [
                'code' => <<<'PHP'
                    <?php
                        /** @template T */
                        final class Collection {
                            /** @param list<T> $items */
                            public function __construct(private array $items) {}
                            /**
                             * @template A
                             * @param A $initial
                             * @param callable(A, T): A $callback
                             * @return A
                             */
                            public function reduce(mixed $initial, callable $callback): mixed {
                                return array_reduce($this->items, $callback, $initial);
                            }
                            /** @param callable(T, int): bool $predicate */
                            public function filter(callable $predicate): void {}
                            /**
                             * @template U
                             * @param callable(T): U $callback
                             * @return Collection<U>
                             */
                            public function map(callable $callback): self {
                                return new self(array_map($callback, $this->items));
                            }
                        }
                        /** @param Collection<int> $ints */
                        function f(Collection $ints): void {
                            $reduce = $ints->reduce(0, ?);
                            $filter = $ints->filter(?);
                            $map = $ints->map(?);
                            $map(fn(int $i): string => (string) $i);
                        }
                    PHP,
                'assertions' => [],
                'ignored_issues' => ['UnusedVariable'],
                'php_version' => '8.6',
            ],
            'PartialApplication:TemplateFunctionWithABoundCallable' => [
                'code' => <<<'PHP'
                    <?php
                        /**
                         * @template T
                         * @template U
                         * @param callable(T): U $callback
                         * @param list<T> $items
                         * @return list<U>
                         */
                        function map(callable $callback, array $items): array {
                            return array_map($callback, $items);
                        }
                        $closure = map(fn(int $i): int => $i * 2, ?);
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:VariadicPlaceholderLeavesTheFormatOpen' => [
                'code' => <<<'PHP'
                    <?php
                        $closure = sprintf('%s', ...);
                        $named = sprintf(format: '%s', ...);
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:FunctionWithTemplatesIsAPlainClosure' => [
                'code' => <<<'PHP'
                    <?php
                        /**
                         * @template T
                         * @param T $value
                         * @return T
                         */
                        function identity(mixed $value): mixed {
                            return $value;
                        }
                        $identity = identity(?);
                        $identity(5);
                    PHP,
                'assertions' => [
                    '$identity===' => 'Closure[impure]',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:BoundArgumentIsAnalyzedOnce' => [
                'code' => <<<'PHP'
                    <?php
                        function needsOne(int $a, int $b): int {
                            return $a + $b;
                        }
                        $i = 0;
                        $closure = needsOne(++$i, ?);
                    PHP,
                'assertions' => [
                    '$i===' => '1',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:BoundVariableOfAReferenceParameterIsDefined' => [
                'code' => <<<'PHP'
                    <?php
                        $closure = preg_match('/a/', 'b', $matches, ...);
                        echo gettype($matches);
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:VariadicParameterStaysOpenWhenBoundByName' => [
                'code' => <<<'PHP'
                    <?php
                        function example(int $a, string ...$rest): void {}
                        $closure = example(?, rest: 'bound', ...);
                    PHP,
                'assertions' => [
                    '$closure===' => 'Closure[impure](int, string...):void',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:StaticReturnIsTheClassOfTheReceiver' => [
                'code' => <<<'PHP'
                    <?php
                        class P {
                            public static function create(int $a): static {
                                return new static();
                            }
                            public function me(int $a): static {
                                return $this;
                            }
                        }
                        final class Q extends P {}
                        $static = Q::create(?);
                        $method = (new Q())->me(?);
                    PHP,
                'assertions' => [
                    '$static===' => 'Closure[impure](int):Q',
                    '$method===' => 'Closure[impure](int):Q',
                ],
                'ignored_issues' => ['UnsafeInstantiation'],
                'php_version' => '8.6',
            ],
            'PartialApplication:ParentOfAnInstanceMethodInAnInstanceContext' => [
                'code' => <<<'PHP'
                    <?php
                        class Base {
                            public function run(int $a, int $b): void {}
                        }
                        class Child extends Base {
                            public function go(): Closure {
                                return parent::run(1, ?);
                            }
                            public function again(): Closure {
                                return self::run(1, ?);
                            }
                        }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:FunctionWithSeveralSignaturesInTheCallMap' => [
                'code' => <<<'PHP'
                    <?php
                        $max = max(?);
                        $named = max(value: ?);
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:SignatureOfFunctionsWithByReferenceParameters' => [
                'code' => <<<'PHP'
                    <?php
                        $sort = sort(?);
                        $replace = str_replace('a', 'b', ?);
                        $exists = array_key_exists(?, ['a' => 1]);
                    PHP,
                'assertions' => [
                    '$sort===' => 'Closure[pure](array<array-key, mixed>):bool',
                    '$replace===' => 'Closure[write-refs](array<array-key, float|int|string>|string)'
                        . ':(array<array-key, string>|string)',
                    '$exists===' => 'Closure[pure](array-key):bool',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:CalleeDoesNotMakeTheEnclosingClosureImpure' => [
                'code' => <<<'PHP'
                    <?php
                        final class Counter {
                            public int $n = 0;
                            public function bump(int $by, int $times): void {
                                $this->n += $by * $times;
                            }
                        }
                        $partial = static fn(Counter $counter): Closure => $counter->bump(1, ?);
                        $first_class = static fn(Counter $counter): Closure => $counter->bump(...);
                    PHP,
                'assertions' => [
                    '$partial===' => 'Closure[pure](Counter):Closure[impure](int):void',
                    '$first_class===' => 'Closure[pure](Counter):Closure[impure](int, int):void',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:AsABoundArgumentOfAnotherOne' => [
                'code' => <<<'PHP'
                    <?php
                        function add(int $a, int $b): int {
                            return $a + $b;
                        }
                        $closure = add(add(1, ?)(2), ?);
                    PHP,
                'assertions' => [
                    '$closure===' => 'Closure[impure](int):int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:UnknownNamedArgumentIsNotAlsoOneTooMany' => [
                'code' => <<<'PHP'
                    <?php
                        abstract class A {
                            abstract public function add(int $a, int $b): int;
                        }
                        function f(A $a): void {
                            $closure = $a->add(c: 1, ...);
                        }
                    PHP,
                'assertions' => [],
                'ignored_issues' => ['InvalidNamedArgument', 'UnusedVariable'],
                'php_version' => '8.6',
            ],
            'PartialApplication:InCoalesceLeftOperand' => [
                'code' => <<<'PHP'
                    <?php
                        function add(int $a, int $b): int {
                            return $a + $b;
                        }
                        $closure = add(1, ?) ?? null;
                    PHP,
                'assertions' => [
                    '$closure===' => 'Closure[impure](int):int|null',
                ],
                'ignored_issues' => ['RedundantCondition', 'TypeDoesNotContainNull'],
                'php_version' => '8.6',
            ],
            'PartialApplication:ConstantExpressions' => [
                'code' => <<<'PHP'
                    <?php
                        /** @pure */
                        function add(int $a, int $b): int {
                            return $a + $b;
                        }
                        #[Attribute]
                        final class Attr {
                            public function __construct(public mixed $a = null) {}
                        }
                        const ADD = add(1, ?);
                        final class C {
                            public const ADD = add(2, ?);
                            public mixed $p = add(3, ?);
                            public function __construct(public mixed $q = add(4, ?)) {}
                            #[Attr(add(5, ?))]
                            public function m(mixed $r = add(6, ?)): mixed {
                                static $s = add(7, ?);
                                return $s;
                            }
                        }
                        $add = ADD;
                    PHP,
                'assertions' => [
                    '$add===' => 'Closure[pure](int):int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:MagicMethodWithPseudoMethod' => [
                'code' => <<<'PHP'
                    <?php
                        /** @method int add(int $a, int $b) */
                        final class M {
                            public function __call(string $name, array $args): mixed {
                                return 1;
                            }
                        }
                        $closure = (new M())->add(1, ?);
                    PHP,
                'assertions' => [
                    '$closure===' => 'Closure[impure](int):int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:BoundPropertyOfAReferenceParameter' => [
                'code' => <<<'PHP'
                    <?php
                        final class P {
                            public ?array $out = null;
                        }
                        function fill(int $n, ?array &$out, int $x): void {
                            $out = [$n, $x];
                        }
                        $p = new P();
                        $closure = fill(1, $p->out, ?);
                        $out = $p->out;
                    PHP,
                'assertions' => [
                    '$closure===' => 'Closure[impure](int):void',
                    '$out===' => 'array<array-key, mixed>|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:CallmapFunctionWithAStub' => [
                'code' => <<<'PHP'
                    <?php
                        $replace = str_replace(subject: ?, search: 'a', replace: 'b');
                    PHP,
                'assertions' => [
                    '$replace===' => 'Closure[write-refs](array<array-key, float|int|string>|string):(array<array-key, string>|string)',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:BoundReferencesKeepTheirValue' => [
                'code' => <<<'PHP'
                    <?php
                        /** @param-out int $x */
                        function fill(mixed &$x, int $v): void {
                            $x = $v;
                        }
                        final class H {
                            public int|string $value = 'before';
                            public static int|string $static = 'before';
                        }
                        function f(H $h): void {
                            $items = ['k' => 'before'];
                            $property = fill($h->value, ?);
                            $static = fill(H::$static, ?);
                            $element = fill($items['k'], ?);
                            /** @psalm-check-type-exact $h->value = int|string */
                            /** @psalm-check-type-exact H::$static = int|string */
                            /** @psalm-check-type-exact $items = array{k: 'before'|int} */
                        }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:BoundPropertyOfAReferenceParameterIsNotWritten' => [
                'code' => <<<'PHP'
                    <?php
                        /** @param-out int $x */
                        function fill(mixed &$x, int $v): void {
                            $x = $v;
                        }
                        final class H {
                            public string $value = 'before';
                        }
                        /** @psalm-capabilities read-props */
                        function factory(H $h): Closure {
                            return fill($h->value, ?);
                        }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:BoundItemOfAPropertyIsNotWritten' => [
                'code' => <<<'PHP'
                    <?php
                        /** @param-out int $x */
                        function fill(mixed &$x, int $v): void {
                            $x = $v;
                        }
                        final class H {
                            /** @var list<string> */
                            public array $items = ['before'];
                        }
                        /** @psalm-capabilities read-props */
                        function factory(H $h): Closure {
                            return fill($h->items[0], ?);
                        }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:BoundItemOfAStaticPropertyIsNotWritten' => [
                'code' => <<<'PHP'
                    <?php
                        /** @param-out int $x */
                        function fill(mixed &$x, int $v): void {
                            $x = $v;
                        }
                        final class H {
                            /** @var list<string> */
                            public static array $items = ['before'];
                        }
                        /** @psalm-capabilities read-globals */
                        function factory(): Closure {
                            return fill(H::$items[0], ?);
                        }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:BoundMissingOffsetOfAReferenceParameter' => [
                'code' => <<<'PHP'
                    <?php
                        /** @param-out int $x */
                        function fill(mixed &$x, int $v): void {
                            $x = $v;
                        }
                        $items = [];
                        $closure = fill($items['k'], ?);
                    PHP,
                'assertions' => [
                    '$items===' => 'array{k: int|null}',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NullsafeInTheClassOfAStaticCall' => [
                'code' => <<<'PHP'
                    <?php
                        final class G {
                            public static function go(int $a): int {
                                return $a;
                            }
                        }
                        final class O {
                            /** @var class-string<G> */
                            public string $p = G::class;
                        }
                        function f(O $o): Closure {
                            return $o?->p::go(?);
                        }
                    PHP,
                'assertions' => [],
                'ignored_issues' => ['RedundantCondition', 'TypeDoesNotContainNull'],
                'php_version' => '8.6',
            ],
            'PartialApplication:PlaceholderAsAttributeArgumentLeavesTheConstructorUnchecked' => [
                'code' => <<<'PHP'
                    <?php
                        #[Attribute]
                        final class Attr {
                            public function __construct(public int $a, public int $b = 0) {}
                        }
                        #[Attr(?)]
                        function f(): void {}
                    PHP,
                'assertions' => [],
                'ignored_issues' => ['ParseError'],
                'php_version' => '8.6',
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
            'PartialApplication:BeforePhp86' => [
                'code' => <<<'PHP'
                    <?php
                        function add(int $a, int $b): int {
                            return $a + $b;
                        }
                        $add_one = add(1, ?);
                    PHP,
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'PartialApplication:WithNew' => [
                'code' => <<<'PHP'
                    <?php
                        class A {
                            public function __construct(public int $a, public int $b) {}
                        }
                        $make = new A(1, ?);
                    PHP,
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:WithNullsafeMethodCall' => [
                'code' => <<<'PHP'
                    <?php
                        class A {
                            public function add(int $a, int $b): int {
                                return $a + $b;
                            }
                        }
                        function f(?A $a): void {
                            $add_one = $a?->add(1, ?);
                        }
                    PHP,
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:TooManyPlaceholders' => [
                'code' => <<<'PHP'
                    <?php
                        function add(int $a, int $b): int {
                            return $a + $b;
                        }
                        $add = add(?, ?, ?);
                    PHP,
                'error_message' => 'TooManyArguments',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:MissingRequiredArgument' => [
                'code' => <<<'PHP'
                    <?php
                        function add(int $a, int $b): int {
                            return $a + $b;
                        }
                        $add = add(b: ?);
                    PHP,
                'error_message' => 'TooFewArguments',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:BoundArgumentIsEvaluated' => [
                'code' => <<<'PHP'
                    <?php
                        function accept(int $a, int $b): int {
                            return $a + $b;
                        }
                        $closure = accept(does_not_exist(), ?);
                    PHP,
                'error_message' => 'UndefinedFunction',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NamedPlaceholderOfVariadicParameter' => [
                'code' => <<<'PHP'
                    <?php
                        function values(int ...$args): void {}
                        $closure = values(args: ?);
                    PHP,
                'error_message' => 'InvalidNamedArgument',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NamedPlaceholderOfUnknownParameter' => [
                'code' => <<<'PHP'
                    <?php
                        function values(int ...$args): void {}
                        $closure = values(x: ?);
                    PHP,
                'error_message' => 'InvalidNamedArgument',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NewInMethodOfClassWithPrivateConstructorProperties' => [
                'code' => <<<'PHP'
                    <?php
                        final class A {
                            public function __construct(private int $a, private readonly int $b) {}
                            public function make(): void {
                                $closure = new self(1, ?);
                            }
                        }
                    PHP,
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NamedArgumentOfCallableWithUnnamedParameters' => [
                'code' => <<<'PHP'
                    <?php
                        /**
                         * @param callable(int, int=): int $callable
                         */
                        function f(callable $callable): void {
                            $named_value = $callable(a: 1, ...);
                            $named_placeholder = $callable(b: ?, ...);
                        }
                    PHP,
                'error_message' => 'InvalidNamedArgument',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:VariadicPlaceholderBeforePlaceholder' => [
                'code' => <<<'PHP'
                    <?php
                        function f(int $a, int $b): void {}
                        $closure = f(..., ?);
                    PHP,
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:VariadicPlaceholderBeforeArgument' => [
                'code' => <<<'PHP'
                    <?php
                        function f(int $a, int $b): void {}
                        $closure = f(..., a: 1);
                    PHP,
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:VariadicPlaceholderTwice' => [
                'code' => <<<'PHP'
                    <?php
                        function f(int $a, int $b): void {}
                        $closure = f(?, ..., ...);
                    PHP,
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:UnpackingBeforePlaceholder' => [
                'code' => <<<'PHP'
                    <?php
                        function f(int $a, int $b): void {}
                        $closure = f(...[1], ?);
                    PHP,
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:UnpackingAfterPlaceholder' => [
                'code' => <<<'PHP'
                    <?php
                        function f(int $a, int $b): void {}
                        $closure = f(?, ...[1]);
                    PHP,
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NamedPlaceholderLeavesRequiredParametersOfAFunctionWithByReferenceParameters' => [
                'code' => <<<'PHP'
                    <?php
                        $closure = str_replace(subject: ?);
                    PHP,
                'error_message' => 'TooFewArguments',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NullsafeInTheReceiverChain' => [
                'code' => <<<'PHP'
                    <?php
                        final class Node {
                            public ?Node $next = null;
                            public function add(int $a, int $b): int {
                                return $a + $b;
                            }
                        }
                        function f(?Node $node): void {
                            $closure = $node?->next->add(1, ?);
                        }
                    PHP,
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NamedArgumentOverwritesAPlaceholder' => [
                'code' => <<<'PHP'
                    <?php
                        function example(int $a, int $b): void {}
                        $closure = example(?, a: 1);
                    PHP,
                'error_message' => 'InvalidNamedArgument',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NamedPlaceholderOverwritesAnArgument' => [
                'code' => <<<'PHP'
                    <?php
                        function example(int $a, int $b): void {}
                        $closure = example(1, a: ?);
                    PHP,
                'error_message' => 'InvalidNamedArgument',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NamedArgumentRepeated' => [
                'code' => <<<'PHP'
                    <?php
                        function example(int $a, int $b): void {}
                        $closure = example(a: 1, a: ?, ...);
                    PHP,
                'error_message' => 'InvalidNamedArgument',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NamedArgumentRepeatedForAVariadicParameter' => [
                'code' => <<<'PHP'
                    <?php
                        function values(int ...$rest): void {}
                        $closure = values(x: 1, x: 2, ...);
                    PHP,
                'error_message' => 'InvalidNamedArgument',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NullsafeBeforeAParenthesis' => [
                'code' => <<<'PHP'
                    <?php
                        final class Node {
                            public ?Node $next = null;
                            public function add(int $a, int $b): int {
                                return $a + $b;
                            }
                        }
                        function f(?Node $node): void {
                            $closure = ($node?->next)->add(1, ?);
                        }
                    PHP,
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:PlaceholderAsAttributeArgument' => [
                'code' => <<<'PHP'
                    <?php
                        #[Attribute]
                        final class Attr {
                            public function __construct(public int $a = 0) {}
                        }
                        #[Attr(?)]
                        function f(): void {}
                    PHP,
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:InIncludePath' => [
                'code' => <<<'PHP'
                    <?php
                        include dirname(?, 2);
                    PHP,
                'error_message' => 'UnresolvableInclude',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:UndefinedMagicMethod' => [
                'code' => <<<'PHP'
                    <?php
                        final class Test {
                            public function __call(string $name, array $args): mixed {
                                return null;
                            }
                        }
                        $closure = (new Test())->length(1, ?);
                    PHP,
                'error_message' => 'UndefinedMagicMethod',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:PlaceholderAsAttributeArgumentOfAClassConstant' => [
                'code' => <<<'PHP'
                    <?php
                        #[Attribute]
                        final class Attr {
                            public function __construct(public int $a = 0) {}
                        }
                        final class K {
                            #[Attr(?)]
                            public const int X = 1;
                        }
                    PHP,
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:11 - Cannot create Closure as attribute argument',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:PlaceholderAsAttributeArgumentOfAnEnumCase' => [
                'code' => <<<'PHP'
                    <?php
                        #[Attribute]
                        final class Attr {
                            public function __construct(public int $a = 0) {}
                        }
                        enum E {
                            #[Attr(...)]
                            case A;
                        }
                    PHP,
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:11 - Cannot create Closure as attribute argument',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NullsafeInTheClassOfAStaticCallInTheReceiverChain' => [
                'code' => <<<'PHP'
                    <?php
                        final class G {
                            public static function make(): G {
                                return new G();
                            }
                            public function m(int $a): int {
                                return $a;
                            }
                        }
                        final class O {
                            /** @var class-string<G> */
                            public string $p = G::class;
                        }
                        function f(?O $o): void {
                            $closure = $o?->p::make()->m(?);
                        }
                    PHP,
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:15:20 - Cannot combine nullsafe operator with Closure creation',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:TooManyPlaceholdersOfAMethod' => [
                'code' => <<<'PHP'
                    <?php
                        final class K {
                            public function m(int $a, int $b): int {
                                return $a + $b;
                            }
                        }
                        function f(K $k): void {
                            $closure = $k->m(1, 2, ?);
                        }
                    PHP,
                'error_message' => 'Too many arguments for partial application of K::m - expecting at most 2',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:BoundReadonlyPropertyOfAReferenceParameter' => [
                'code' => <<<'PHP'
                    <?php
                        /** @param-out int $x */
                        function fill(mixed &$x, int $v): void {
                            $x = $v;
                        }
                        final class R {
                            public function __construct(public readonly int $v = 1) {}
                        }
                        function f(R $r): void {
                            $closure = fill($r->v, ?);
                        }
                    PHP,
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:25 - R::$v is marked readonly',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:BoundItemOfAReadonlyPropertyOfAReferenceParameter' => [
                'code' => <<<'PHP'
                    <?php
                        /** @param-out int $x */
                        function fill(mixed &$x, int $v): void {
                            $x = $v;
                        }
                        final class R {
                            /** @param array<string, int> $items */
                            public function __construct(public readonly array $items = []) {}
                        }
                        function f(R $r): void {
                            $closure = fill($r->items['k'], ?);
                        }
                    PHP,
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:11:25 - R::$items is marked readonly',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:BoundReadonlyPropertyNamedByALiteralOfAReferenceParameter' => [
                'code' => <<<'PHP'
                    <?php
                        /** @param-out int $x */
                        function fill(mixed &$x, int $v): void {
                            $x = $v;
                        }
                        final class R {
                            public function __construct(public readonly int $v = 1) {}
                        }
                        function f(R $r): void {
                            $closure = fill($r->{'v'}, ?);
                        }
                    PHP,
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:25 - R::$v is marked readonly',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
        ];
    }
}
