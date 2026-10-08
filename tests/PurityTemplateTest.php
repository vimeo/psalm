<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

/**
 * `@psalm-purity-template` / `@psalm-purity-from-template`: function-likes whose purity depends
 * on the closures they are given, and classes whose purity depends on their subclass or on
 * what they were constructed with.
 */
final class PurityTemplateTest extends TestCase
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
            'newOfAClassExtendingAPurityTemplatedClassResolvesItsUnboundTemplatesToTheirBounds' => [
                'code' => '<?php
                    /** @psalm-purity-template P */
                    abstract class View {}

                    /**
                     * @template TValue
                     * @template TContext as array
                     */
                    final class Table extends View {
                        /** @param list<TValue> $values */
                        public function __construct(public array $values) {}
                    }

                    /** @return Table<int, array> */
                    function make(): Table {
                        return new Table([1]);
                    }',
            ],
            'callbackStoredByAMethodWideningThePurityTemplate' => [
                'code' => '<?php
                    /**
                     * @psalm-purity-template P
                     * @psalm-capabilities read-props|write-this-props|write-refs
                     */
                    final class Box {
                        /** @var list<callable[P](): void> */
                        private array $cbs = [];

                        /**
                         * @psalm-purity-template R
                         * @param callable[R](): void $cb
                         * @psalm-self-out Box[P|R]
                         */
                        public function then(callable $cb): void {
                            $this->cbs[] = $cb;
                        }

                        /** @psalm-purity-from-template P */
                        public function run(): void {
                            foreach ($this->cbs as $cb) {
                                $cb();
                            }
                        }
                    }',
            ],
            'traitMethodOverridingAMethodOfAPurityTemplateTheClassDoesNotBind' => [
                'code' => '<?php
                    /** @psalm-purity-template P */
                    interface Getter {
                        /**
                         * @psalm-capabilities read-props
                         * @psalm-purity-from-template P
                         */
                        public function get(): string;
                    }

                    trait EchoingGetter {
                        public function get(): string {
                            echo "x";
                            return "x";
                        }
                    }

                    final class Doer implements Getter {
                        use EchoingGetter;
                    }',
            ],
            'traitMethodOverridingAMethodOfAPurityTemplateTheClassBindsExplicitly' => [
                'code' => '<?php
                    /** @psalm-purity-template P */
                    interface Getter {
                        /**
                         * @psalm-capabilities read-props
                         * @psalm-purity-from-template P
                         */
                        public function get(): string;
                    }

                    trait EchoingGetter {
                        public function get(): string {
                            echo "x";
                            return "x";
                        }
                    }

                    /** @implements Getter[impure] */
                    final class Doer implements Getter {
                        use EchoingGetter;
                    }',
            ],
            'instanceofNarrowingKeepsTheParentsArguments' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @psalm-purity-template P
                     * @implements Iterator[P]<int, T>
                     */
                    abstract class XIt implements Iterator {
                        /**
                         * @return list<T>
                         * @psalm-capabilities read-props
                         * @psalm-purity-from-template P
                         */
                        public function toList(): array {
                            $list = [];
                            foreach ($this as $value) {
                                $list[] = $value;
                            }
                            return $list;
                        }
                    }

                    /**
                     * @param Iterator[pure]<int, string> $source
                     * @return list<string>
                     * @psalm-pure
                     */
                    function toList(Iterator $source): array {
                        if ($source instanceof XIt) {
                            return $source->toList();
                        }
                        return [];
                    }',
            ],
            'lateStaticClassBindingThePurityTemplateDropsItsArgument' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @psalm-purity-template P
                     */
                    abstract class XIt {
                        /**
                         * @return static[P]<T>
                         * @psalm-purity-from-template P
                         * @psalm-mutation-free
                         */
                        public function limit(int $length): static {
                            return $this;
                        }
                    }

                    /**
                     * @template T
                     * @extends XIt[pure]<T>
                     */
                    final class XItOnArray extends XIt {
                        /** @var list<T> */
                        public array $list;

                        /**
                         * @param list<T> $list
                         * @psalm-pure
                         */
                        public function __construct(array $list) {
                            $this->list = $list;
                        }
                    }

                    $list = (new XItOnArray([1]))->limit(1);',
                'assertions' => [
                    '$list' => 'XItOnArray<int>',
                ],
            ],
            'purityArgumentLeftOutWithoutDefault' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @psalm-purity-template P
                     */
                    abstract class Box {
                        /** @return static<T> */
                        abstract public function self(): static;
                    }

                    /**
                     * @param Box<int> $box
                     * @return Box<int>
                     */
                    function keep(Box $box): Box {
                        return $box;
                    }',
            ],
            'pureClosureKeepsFunctionPure' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P](int): int $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): int {
                        return $f(1);
                    }

                    /** @psalm-pure */
                    function usePure(): int {
                        return apply(fn(int $x): int => $x + 1);
                    }',
            ],
            'closureParamsKeepTheirTypes' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P](string): int $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): int {
                        return $f("a");
                    }

                    /** @psalm-pure */
                    function usePure(): int {
                        return apply(fn(string $s): int => strlen($s));
                    }',
            ],
            'impureClosureAllowedInImpureCaller' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P](): void $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): int {
                        $f();
                        return 1;
                    }

                    function useImpure(): int {
                        return apply(function (): void { echo "hi"; });
                    }',
            ],
            'staticMethodAndConstructor' => [
                'code' => '<?php
                    final class Holder {
                        /**
                         * @psalm-pure
                         * @psalm-purity-template P
                         * @param Closure[P](): void $f
                         * @psalm-purity-from-template P
                         */
                        public static function run(Closure $f): int {
                            $f();
                            return 1;
                        }

                        /**
                         * @psalm-pure
                         * @psalm-purity-template P
                         * @param Closure[P](): void $f
                         * @psalm-purity-from-template P
                         */
                        public function __construct(Closure $f) {
                            $f();
                        }
                    }

                    /** @psalm-pure */
                    function usePure(): int {
                        new Holder(function (): void {});
                        return Holder::run(function (): void {});
                    }',
            ],
            'firstClassCallableOfPureFunction' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P](): int $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): int {
                        return $f();
                    }

                    /** @psalm-pure */
                    function one(): int {
                        return 1;
                    }

                    /** @psalm-pure */
                    function usePure(): int {
                        return apply(one(...));
                    }',
            ],
            'omittedAndNullClosureRequireNothing' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param ?Closure[P](): void $f
                     * @psalm-purity-from-template P
                     */
                    function apply(?Closure $f = null): int {
                        if ($f) {
                            $f();
                        }
                        return 1;
                    }

                    /** @psalm-pure */
                    function usePure(): int {
                        return apply() + apply(null) + apply(function (): void {});
                    }',
            ],
            'nestedClosureMayCallTheTemplatedClosure' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P](): void $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): int {
                        $inner = function () use ($f): void {
                            $f();
                        };
                        $inner();
                        return 1;
                    }',
            ],
            'ownCapabilitiesPlusClosure' => [
                'code' => '<?php
                    /**
                     * @psalm-capabilities write-props
                     * @psalm-purity-template P
                     * @param Closure[P](): void $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): int {
                        $f();
                        return 1;
                    }

                    /** @psalm-capabilities write-props */
                    function useWriteProps(): int {
                        return apply(function (): void {});
                    }',
            ],
            'twoPurityTemplates' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @psalm-purity-template Q
                     * @param Closure[P](): void $f
                     * @param Closure[Q](): void $g
                     * @psalm-purity-from-template P, Q
                     */
                    function both(Closure $f, Closure $g): int {
                        $f();
                        $g();
                        return 1;
                    }

                    /** @psalm-capabilities io */
                    function useIo(): int {
                        return both(function (): void {}, function (): void { echo "x"; });
                    }',
            ],
            'classPurityTemplateBoundBySubclass' => [
                'code' => '<?php
                    /** @psalm-purity-template C */
                    abstract class Doer {
                        /**
                         * @psalm-mutation-free
                         * @psalm-purity-from-template C
                         */
                        abstract public function doWork(): int;

                        /**
                         * @psalm-mutation-free
                         * @psalm-purity-from-template C
                         */
                        public function run(): int {
                            return $this->doWork();
                        }
                    }

                    /** @extends Doer[pure] */
                    final class PureDoer extends Doer {
                        /** @psalm-pure */
                        public function doWork(): int {
                            return 1;
                        }
                    }

                    /** @extends Doer[io] */
                    final class IoDoer extends Doer {
                        /** @psalm-capabilities io */
                        public function doWork(): int {
                            echo "x";
                            return 1;
                        }
                    }

                    /** @psalm-pure */
                    function usePure(PureDoer $d): int {
                        return $d->run();
                    }

                    /** @psalm-capabilities io */
                    function useIo(IoDoer $d): int {
                        return $d->run();
                    }',
            ],
            'functionDependingOnClassPurityTemplate' => [
                'code' => '<?php
                    /** @psalm-purity-template C */
                    abstract class Doer {
                        /**
                         * @psalm-mutation-free
                         * @psalm-purity-from-template C
                         */
                        abstract public function doWork(): int;
                    }

                    /** @extends Doer[pure] */
                    final class PureDoer extends Doer {
                        /** @psalm-pure */
                        public function doWork(): int {
                            return 1;
                        }
                    }

                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Doer[P] $d
                     * @psalm-purity-from-template P
                     */
                    function useAny(Doer $d): int {
                        return $d->doWork();
                    }

                    /** @psalm-pure */
                    function usePure(PureDoer $d): int {
                        return useAny($d);
                    }',
            ],
            'purityTemplateIsCovariant' => [
                'code' => '<?php
                    /** @psalm-purity-template C */
                    abstract class Doer {}

                    /** @extends Doer[pure] */
                    final class PureDoer extends Doer {}

                    /** @param Doer[io] $d */
                    function takesIoDoer(Doer $d): void {}

                    function test(PureDoer $d): void {
                        takesIoDoer($d);
                    }',
            ],
            'classPurityTemplateBoundByConstructor' => [
                'code' => '<?php
                    /** @psalm-purity-template C */
                    final class Box {
                        /**
                         * @param Closure[C](): void $cb
                         * @psalm-pure
                         */
                        public function __construct(private Closure $cb) {}

                        /**
                         * @psalm-mutation-free
                         * @psalm-purity-from-template C
                         */
                        public function fire(): int {
                            ($this->cb)();
                            return 1;
                        }
                    }

                    /** @psalm-pure */
                    function usePure(): int {
                        $b = new Box(function (): void {});
                        return $b->fire();
                    }',
            ],
            'typeTemplateBoundToClosure' => [
                'code' => '<?php
                    /**
                     * @template T of callable(): void
                     */
                    final class Deferred {
                        /** @var T */
                        private $callback;

                        /**
                         * @param T $callback
                         * @psalm-external-mutation-free
                         */
                        public function __construct($callback) {
                            $this->callback = $callback;
                        }

                        /**
                         * @psalm-mutation-free
                         * @psalm-purity-from-template T
                         */
                        public function run(): void {
                            ($this->callback)();
                        }
                    }

                    /**
                     * @psalm-mutation-free
                     * @param Deferred<pure-Closure(): void> $deferred
                     */
                    function runPure(Deferred $deferred): void {
                        $deferred->run();
                    }',
            ],
            'nestedClosureCarriesOuterPurityTemplate' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P](int): string $val
                     * @return Closure[P](int): string
                     */
                    function escape(Closure $val): Closure {
                        return static fn(int $item): string => htmlspecialchars($val($item));
                    }

                    /** @psalm-pure */
                    function usePure(): string {
                        $f = escape(function (int $i): string { echo $i; return (string) $i; }); // not called
                        return escape(fn(int $i): string => (string) $i)(1);
                    }',
            ],
            'functionLikeInheritingThePurityOfAParamItCallsAndReturnsAClosureCalling' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P](int): string $val
                     * @return list{string, Closure[P](int): string}
                     * @psalm-purity-from-template P
                     */
                    function both(Closure $val): array {
                        return [$val(0), static fn(int $item): string => $val($item)];
                    }

                    /** @psalm-pure */
                    function usePure(): string {
                        [$first, $rest] = both(fn(int $i): string => (string) $i);
                        return $first . $rest(1);
                    }',
            ],
            'impureFunctionLikeCallingAParamAndReturningAClosureCallingIt' => [
                'code' => '<?php
                    /**
                     * @psalm-purity-template P
                     * @param Closure[P](int): string $val
                     * @return list{string, Closure[P](int): string}
                     */
                    function both(Closure $val): array {
                        return [$val(0), static fn(int $item): string => $val($item)];
                    }',
            ],
            'nestedClosurePassingOnOuterPurityTemplateCarriesIt' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template Q
                     * @param Closure[Q](int): string $f
                     * @psalm-purity-from-template Q
                     */
                    function apply(Closure $f): string {
                        return $f(1);
                    }

                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P](int): string $val
                     * @return Closure[P](): string
                     */
                    function later(Closure $val): Closure {
                        return static fn(): string => apply($val);
                    }

                    /** @psalm-pure */
                    function usePure(): string {
                        return later(fn(int $i): string => (string) $i)();
                    }',
            ],
            'nestedClosureCarriesClassPurityTemplate' => [
                'code' => '<?php
                    /** @psalm-purity-template C */
                    final class Box {
                        /**
                         * @param Closure[C](): int $cb
                         * @psalm-pure
                         */
                        public function __construct(private Closure $cb) {}

                        /**
                         * @psalm-mutation-free
                         * @psalm-purity-from-template C
                         */
                        public function fire(): int {
                            return ($this->cb)();
                        }

                        /**
                         * @psalm-mutation-free
                         * @return Closure[C|read-props](): int
                         */
                        public function wrap(): Closure {
                            return fn(): int => ($this->cb)() + 1;
                        }

                        /**
                         * @psalm-mutation-free
                         * @return Closure[C|read-props](): int
                         */
                        public function later(): Closure {
                            return fn(): int => $this->fire();
                        }
                    }

                    /** @psalm-mutation-free */
                    function useMutationFree(): int {
                        $box = new Box(fn(): int => 1);
                        return $box->wrap()() + $box->later()();
                    }',
            ],
            'purityTemplateWithoutParams' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P] $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): int {
                        $f();
                        return 1;
                    }

                    /** @psalm-pure */
                    function usePure(): int {
                        return apply(fn(): int => 1);
                    }',
            ],
            'purityTemplateBound' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P <= read-globals
                     * @param Closure[P](): int $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): int {
                        return $f();
                    }

                    final class Counter {
                        public static int $n = 0;
                    }

                    /** @psalm-capabilities read-globals */
                    function total(): int {
                        return apply(fn(): int => Counter::$n) + apply(fn(): int => 1);
                    }',
            ],
            'classPurityTemplateBoundByFunctionPurityTemplate' => [
                'code' => '<?php
                    /** @psalm-purity-template C */
                    final class Box {
                        /**
                         * @param Closure[C](): int $cb
                         * @psalm-pure
                         */
                        public function __construct(private Closure $cb) {}

                        /**
                         * @psalm-mutation-free
                         * @psalm-purity-from-template C
                         */
                        public function fire(): int {
                            return ($this->cb)();
                        }
                    }

                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P](): int $f
                     * @return Box[P]
                     */
                    function makeBox(Closure $f): Box {
                        return new Box($f);
                    }

                    /** @psalm-pure */
                    function usePure(): int {
                        return makeBox(fn(): int => 1)->fire();
                    }',
            ],
            'newStaticInMethodDependingOnTheClassPurityTemplateOfTheConstructor' => [
                'code' => '<?php
                    /**
                     * @psalm-consistent-constructor
                     * @psalm-consistent-templates
                     * @psalm-purity-template C
                     */
                    abstract class Base {
                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template C
                         */
                        public function __construct() {}

                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template C
                         */
                        public static function make(): static {
                            return new static();
                        }
                    }',
            ],
            'constructorDependingOnClassPurityTemplateBoundBySubclass' => [
                'code' => '<?php
                    /**
                     * @psalm-consistent-constructor
                     * @psalm-purity-template C
                     */
                    abstract class Base {
                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template C
                         */
                        public function __construct() {}
                    }

                    /** @extends Base[pure] */
                    final class PureBase extends Base {}

                    /** @extends Base[io] */
                    final class IoBase extends Base {}

                    /** @psalm-pure */
                    function makePure(): PureBase {
                        return new PureBase();
                    }

                    /** @psalm-capabilities io */
                    function makeIo(): IoBase {
                        return new IoBase();
                    }',
            ],
            'classPurityTemplateDefault' => [
                'code' => '<?php
                    /** @psalm-purity-template C(pure) <= write-this-props|write-props */
                    abstract class Doer {
                        /**
                         * @psalm-mutation-free
                         * @psalm-purity-from-template C
                         */
                        public function run(): int {
                            return 1;
                        }
                    }

                    final class DefaultDoer extends Doer {}

                    /** @extends Doer[write-this-props] */
                    final class MutatingDoer extends Doer {}

                    /** @psalm-mutation-free */
                    function useDefault(DefaultDoer $d): int {
                        return $d->run();
                    }

                    /** @psalm-capabilities write-props */
                    function useMutating(MutatingDoer $d): int {
                        return $d->run();
                    }',
            ],
            'classPurityTemplateWritingThisCostsTheReceiver' => [
                'code' => '<?php
                    /** @psalm-purity-template C */
                    abstract class Doer {
                        /** @psalm-purity-from-template C */
                        abstract public function run(): void;
                    }

                    /** @extends Doer[write-this-props] */
                    final class MutatingDoer extends Doer {
                        public int $runs = 0;

                        /** @psalm-capabilities read-props|write-this-props */
                        public function run(): void {
                            $this->runs++;
                        }

                        /** @psalm-capabilities write-this-props */
                        public function again(): void {
                            $this->run();
                        }
                    }

                    final class Registry {
                        public static ?MutatingDoer $doer = null;
                    }

                    /** @psalm-capabilities write-props */
                    function other(MutatingDoer $d): void {
                        $d->run();
                    }

                    /** @psalm-capabilities write-props|read-globals|write-globals */
                    function global_(): void {
                        $d = Registry::$doer;
                        if ($d !== null) {
                            $d->run();
                        }
                    }',
            ],
            'wildcardPurity' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param Closure[_](): int $f
                     */
                    function apply(Closure $f): int {
                        return $f();
                    }

                    /**
                     * @psalm-pure
                     * @param Closure[_](): int $f
                     * @param callable[_](): int $g
                     */
                    function applyBoth(Closure $f, callable $g): int {
                        return $f() + $g();
                    }

                    final class Runner {
                        /**
                         * @psalm-mutation-free
                         * @param Closure[_](): int $f
                         */
                        public function run(Closure $f): int {
                            return $f();
                        }
                    }

                    /** @psalm-pure */
                    function usePure(Runner $r): int {
                        return apply(fn(): int => 1) + applyBoth(fn(): int => 1, fn(): int => 2) + $r->run(fn(): int => 3);
                    }

                    /** @psalm-capabilities io */
                    function useIo(): int {
                        return apply(function (): int {
                            echo "x";
                            return 1;
                        });
                    }',
            ],
            'wildcardPurityInNamespace' => [
                'code' => '<?php
                    namespace Foo;

                    use Closure;

                    /**
                     * @psalm-pure
                     * @param Closure[_](): int $f
                     * @param ?callable[_](): int $g
                     */
                    function apply(Closure $f, ?callable $g = null): int {
                        return $f() + ($g !== null ? $g() : 0);
                    }

                    /** @psalm-pure */
                    function usePure(): int {
                        return apply(fn(): int => 1, fn(): int => 2);
                    }',
            ],
            'wildcardPurityNested' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param array<Closure[_](int): int> $fs
                     * @return list<int>
                     */
                    function runAll(array $fs): array {
                        $r = [];
                        foreach ($fs as $f) {
                            $r[] = $f(1);
                        }
                        return $r;
                    }

                    /**
                     * @psalm-pure
                     * @param array{Closure[_](): int, ?callable[_](): int} $fs
                     */
                    function runPair(array $fs): int {
                        return $fs[0]() + ($fs[1] !== null ? $fs[1]() : 0);
                    }

                    abstract class Runner {
                        /**
                         * @psalm-mutation-free
                         * @param list<Closure[_](int): int> $fs
                         */
                        public function runAll(array $fs): int {
                            $sum = 0;
                            foreach ($fs as $f) {
                                $sum += $f(1);
                            }
                            return $sum;
                        }
                    }

                    /**
                     * @psalm-pure
                     * @return list<int>
                     */
                    function usePure(Runner $r): array {
                        return [
                            ...runAll([fn(int $x): int => $x + 1]),
                            runPair([fn(): int => 1, fn(): int => 2]),
                            $r->runAll([fn(int $x): int => $x]),
                        ];
                    }

                    /**
                     * @psalm-capabilities io
                     * @return list<int>
                     */
                    function useIo(): array {
                        return runAll([function (int $x): int {
                            echo "x";
                            return $x;
                        }]);
                    }',
            ],
            'wildcardPurityOnGenerics' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param Traversable[_]<int, int> $t
                     * @param iterable[_]<int, int> $i
                     */
                    function sum(Traversable $t, iterable $i): int {
                        $s = 0;
                        foreach ($t as $x) {
                            $s += $x;
                        }
                        foreach ($i as $x) {
                            $s += $x;
                        }
                        return $s;
                    }

                    /**
                     * @psalm-pure
                     * @return Generator<int, int, mixed, int>
                     */
                    function gen(): Generator {
                        yield 1;
                        return 1;
                    }

                    /** @psalm-pure */
                    function usePure(): int {
                        return sum(gen(), [1]);
                    }',
            ],
            'pureClosureFitsPurityTemplate' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param pure-Closure(Closure[P](): int): int $f
                     */
                    function apply(Closure $f): int {
                        return $f(fn(): int => 1);
                    }

                    /**
                     * @psalm-pure
                     * @param pure-Closure(Closure[_](): int): int $f
                     */
                    function applyWildcard(Closure $f): int {
                        return $f(fn(): int => 1);
                    }',
            ],
            'wildcardPurityOnPromotedProperty' => [
                'code' => '<?php
                    /** @psalm-immutable */
                    final class Holder {
                        /**
                         * @psalm-pure
                         * @param Closure[_](): int $f
                         */
                        public function __construct(public Closure $f) {}
                    }

                    /** @psalm-pure */
                    function make(): Holder {
                        return new Holder(fn(): int => 1);
                    }

                    $f = make()->f;',
                'assertions' => [
                    '$f' => 'Closure[impure]():int',
                ],
            ],
            'overrideWithFewerCapabilitiesThanDependentParent' => [
                'code' => '<?php
                    abstract class Base {
                        /**
                         * @psalm-pure
                         * @psalm-purity-template P
                         * @param Closure[P](): void $f
                         * @psalm-purity-from-template P
                         */
                        abstract public function run(Closure $f): int;
                    }

                    final class Ignoring extends Base {
                        /**
                         * @psalm-pure
                         * @param Closure[impure](): void $f
                         */
                        public function run(Closure $f): int {
                            return 1;
                        }
                    }',
            ],
            'wildcardPurityInOverrideOfImpureClosureParam' => [
                'code' => '<?php
                    abstract class Base {
                        /** @param Closure(int): int $g */
                        public function run(Closure $g): int {
                            return $g(1);
                        }
                    }

                    final class Child extends Base {
                        /** @param Closure[_](int): int $g */
                        #[Override]
                        public function run(Closure $g): int {
                            return $g(2);
                        }
                    }',
            ],
            'wildcardPurityInOverrideOfPureClosureParam' => [
                'code' => '<?php
                    abstract class Base {
                        /** @param Closure[pure](int): int $g */
                        public function run(Closure $g): int {
                            return $g(1);
                        }
                    }

                    final class Child extends Base {
                        /** @param Closure[_](int): int $g */
                        #[Override]
                        public function run(Closure $g): int {
                            return $g(2);
                        }
                    }',
            ],
            'wildcardPurityInOverrideOfNullableCallableParam' => [
                'code' => '<?php
                    abstract class Base {
                        /** @param callable(int): bool|null $f */
                        public function filter(?callable $f = null): int {
                            return $f !== null && $f(1) ? 1 : 0;
                        }
                    }

                    final class Child extends Base {
                        /** @param callable[_](int): bool|null $f */
                        #[Override]
                        public function filter(?callable $f = null): int {
                            return $f !== null && $f(2) ? 1 : 0;
                        }
                    }',
            ],
            'nestedWildcardPurityInOverrideOfImpureClosureParam' => [
                'code' => '<?php
                    abstract class Base {
                        /** @param list<Closure(int): int> $gs */
                        public function runAll(array $gs): int {
                            return count($gs);
                        }
                    }

                    final class Child extends Base {
                        /** @param list<Closure[_](int): int> $gs */
                        #[Override]
                        public function runAll(array $gs): int {
                            return count($gs);
                        }
                    }',
            ],
            'wildcardPurityOnGenericInOverrideOfImpureGenericParam' => [
                'code' => '<?php
                    abstract class Base {
                        /** @param Traversable<int, int> $t */
                        public function sum(Traversable $t): int {
                            return 0;
                        }
                    }

                    final class Child extends Base {
                        /** @param Traversable[_]<int, int> $t */
                        #[Override]
                        public function sum(Traversable $t): int {
                            return 0;
                        }
                    }',
            ],
            'purityTemplateInOverrideOfImpureClosureParam' => [
                'code' => '<?php
                    abstract class Base {
                        /** @param Closure(int): int $g */
                        public function run(Closure $g): int {
                            return $g(1);
                        }
                    }

                    final class Child extends Base {
                        /**
                         * @psalm-purity-template P
                         * @param Closure[P](int): int $g
                         */
                        #[Override]
                        public function run(Closure $g): int {
                            return $g(2);
                        }
                    }',
            ],
            'impureClosureParamInOverrideOfWildcardOne' => [
                'code' => '<?php
                    abstract class Base {
                        /** @param Closure[_](int): int $g */
                        public function run(Closure $g): int {
                            return $g(1);
                        }
                    }

                    final class Child extends Base {
                        /** @param Closure(int): int $g */
                        #[Override]
                        public function run(Closure $g): int {
                            return $g(2);
                        }
                    }',
            ],
            'classPurityTemplateLowerBound' => [
                'code' => '<?php
                    final class Box {
                        public int $x = 0;
                    }

                    /** @psalm-purity-template write-props <= C(write-props) <= write-props|io */
                    abstract class Doer {
                        /**
                         * @psalm-mutation-free
                         * @psalm-purity-from-template C
                         */
                        public function run(Box $b): int {
                            $b->x = 1;
                            return $b->x;
                        }
                    }

                    /** @extends Doer[write-props|io] */
                    final class IoDoer extends Doer {}

                    final class DefaultDoer extends Doer {}

                    /** @psalm-capabilities write-props */
                    function useDefault(DefaultDoer $d, Box $b): int {
                        return $d->run($b);
                    }',
            ],
            'callOfTypeTemplateBoundToClosureIsTyped' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @template TCallback as (Closure(int): string)|null
                     * @param TCallback $cb
                     * @psalm-purity-from-template TCallback
                     */
                    function apply(?Closure $cb = null): string {
                        if ($cb !== null) {
                            return $cb(1);
                        }
                        return "";
                    }',
            ],
            'callOfTypeTemplateBoundToCallableIsTyped' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @template TCallback as callable(int): string
                     * @param TCallback $cb
                     * @psalm-purity-from-template TCallback
                     */
                    function apply(callable $cb): string {
                        return $cb(1);
                    }

                    /** @psalm-pure */
                    function caller(): string {
                        return apply(static fn(int $i): string => (string) $i);
                    }',
            ],
            'builtinSortsInheritTheComparatorsPurity' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param array<string, int> $xs
                     * @return array<string, int>
                     */
                    function sortAll(array $xs): array {
                        uasort($xs, fn(int $a, int $b): int => $a <=> $b);
                        uksort($xs, "strcmp");
                        $ys = array_values($xs);
                        usort($ys, fn(int $a, int $b): int => $b <=> $a);
                        return $xs;
                    }

                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param list<int> $xs
                     * @param Closure[P](int, int): int $cmp
                     * @return list<int>
                     * @psalm-purity-from-template P
                     */
                    function sortWith(array $xs, Closure $cmp): array {
                        usort($xs, $cmp); // deferred to the callers of sortWith
                        return $xs;
                    }',
            ],
            'builtinIteratorFunctionsInheritTheIterationPurity' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @return Generator<int, int, mixed, int>
                     */
                    function gen(): Generator {
                        yield 1;
                        return 1;
                    }

                    /**
                     * @psalm-pure
                     * @param Iterator[pure]<int, string> $it
                     */
                    function consume(Iterator $it): int {
                        $all = iterator_to_array($it);
                        return count($all)
                            + iterator_count(gen())
                            + iterator_apply(gen(), fn(): bool => true);
                    }',
            ],
            'pregReplaceCallbackArrayInheritsTheCallbacksPurity' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function replace(string $s): ?string {
                        return preg_replace_callback_array(
                            [
                                "/a/" => fn(array $m): string => "b",
                                "/c/" => fn(array $m): string => "d",
                            ],
                            $s,
                        );
                    }',
            ],
            'arrayObjectSortInheritsTheComparatorsPurity' => [
                'code' => '<?php
                    /**
                     * @psalm-capabilities write-props
                     * @param ArrayObject<int, int> $o
                     * @param ArrayIterator<int, int> $i
                     */
                    function sortBoth(ArrayObject $o, ArrayIterator $i): void {
                        $o->uasort(fn(int $a, int $b): int => $a <=> $b);
                        $i->uksort(fn(int $a, int $b): int => $a <=> $b);
                    }',
            ],
            'closureRebindingKeepsTheClosureType' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function rebind(): int {
                        $c = fn(int $x): int => $x;
                        $d = Closure::bind($c, null, null);
                        $e = $c->bindTo(null);
                        return ($d ? $d(1) : 0) + ($e ? $e(2) : 0);
                    }

                    /** @psalm-pure */
                    function fromCallable(): int {
                        $c = Closure::fromCallable(fn(): int => 1);
                        return $c();
                    }',
                'assertions' => [],
            ],
            'closureCallIsACallOfTheClosure' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function run(): int {
                        $c = fn(int $x): int => $x;
                        return $c->call(new stdClass, 1);
                    }

                    $r = (fn(): string => "a")->call(new stdClass);',
                'assertions' => [
                    '$r===' => "'a'",
                ],
            ],
            'fiberInheritsTheCallbacksPurity' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function runFiber(): mixed {
                        $f = new Fiber(function (int $x): int {
                            $y = Fiber::suspend($x);
                            return is_int($y) ? $y : 0;
                        });
                        $f->start(1);
                        if (!$f->isTerminated()) {
                            $f->resume(2);
                        }
                        return $f->getReturn();
                    }

                    /**
                     * @psalm-pure
                     * @param Fiber[pure] $f
                     */
                    function resumePure(Fiber $f): mixed {
                        return $f->resume();
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
                'php_version' => '8.1',
            ],
            'classPurityTemplateInTypeTemplateBound' => [
                'code' => '<?php
                    /**
                     * @template TKey
                     * @template TValue
                     * @template TIterator as Traversable[TPurity]<TKey, TValue>
                     * @psalm-purity-template TPurity(impure)
                     */
                    final class Wrapper {
                        /**
                         * @param TIterator $inner
                         * @psalm-pure
                         */
                        public function __construct(Traversable $inner) {}

                        /**
                         * @psalm-capabilities read-props
                         * @psalm-purity-from-template TPurity
                         */
                        public function count(): int {
                            return 1;
                        }
                    }

                    /**
                     * @psalm-pure
                     * @return Generator<int, string, mixed, int>
                     */
                    function gen(): Generator {
                        yield 1 => "a";
                        return 1;
                    }

                    /** @psalm-pure */
                    function countIt(): int {
                        $w = new Wrapper(gen());
                        return $w->count();
                    }

                    /**
                     * @psalm-pure
                     * @param Wrapper[pure]<int, int, Iterator[pure]<int, int>> $w
                     */
                    function countGiven(Wrapper $w): int {
                        return $w->count();
                    }

                    $w = new Wrapper(gen());',
                'assertions' => [
                    '$w' => 'Wrapper[pure]<int, string, Generator[pure]<int, string, mixed, int>>',
                ],
            ],
            'forwardedClassPurityTemplateOverrideUsesTheTemplate' => [
                'code' => '<?php
                    /** @psalm-purity-template P */
                    class Filter {
                        /**
                         * @param Closure[P](): bool $cb
                         * @psalm-pure
                         */
                        public function __construct(private Closure $cb) {}

                        /**
                         * @psalm-capabilities read-props
                         * @psalm-purity-from-template P
                         */
                        public function accept(): bool {
                            return ($this->cb)();
                        }
                    }

                    /**
                     * @psalm-purity-template P
                     * @extends Filter[P]
                     */
                    final class Negated extends Filter {
                        /**
                         * @psalm-capabilities read-props
                         * @psalm-purity-from-template P
                         */
                        #[Override]
                        public function accept(): bool {
                            return !parent::accept();
                        }
                    }',
            ],
            'firstClassCallableOfPurityPolymorphicFunctionTakesAnyClosure' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P](int): int $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): int {
                        return $f(1);
                    }

                    $apply = apply(...);
                    $r = $apply(fn(int $x): int => $x);',
                'assertions' => [
                    '$apply' => 'Closure[impure](Closure[impure](int):int):int',
                ],
            ],
            'splWrapperIteratorsInheritTheirPurity' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @return Generator<int, int, mixed, int>
                     */
                    function gen(): Generator {
                        yield 1;
                        yield 2;
                        return 2;
                    }

                    /** @psalm-pure */
                    function countEvens(): int {
                        $it = new CallbackFilterIterator(gen(), fn(int $v): bool => $v % 2 === 0);
                        $n = 0;
                        foreach ($it as $_) {
                            $n++;
                        }
                        return $n;
                    }

                    /** @psalm-pure */
                    function countFirst(): int {
                        return iterator_count(new LimitIterator(new NoRewindIterator(gen()), 0, 1))
                            + iterator_count(new IteratorIterator(gen()));
                    }

                    $it = new CallbackFilterIterator(gen(), fn(int $v): bool => true);',
                'assertions' => [
                    '$it' => 'CallbackFilterIterator[pure]<int, int, Generator[pure]<int, int, mixed, int>>',
                ],
            ],
            'promotedPropertyTypedByTemplateWithTemplatedBound' => [
                'code' => '<?php
                    /**
                     * @template TKey
                     * @template TValue
                     * @template TIterator as Traversable<TKey, TValue>
                     */
                    final class Box {
                        /** @param TIterator $inner */
                        public function __construct(public Traversable $inner) {}
                    }

                    $box = new Box(new ArrayIterator([1 => "a"]));',
            ],
            'purityTemplateInferredFromSeveralPositionsJoinsTheirPurities' => [
                'code' => '<?php
                    /**
                     * @template-covariant T
                     * @psalm-purity-template P
                     */
                    final class Box {
                        /** @psalm-pure */
                        public function __construct() {}
                    }

                    /**
                     * @psalm-pure
                     * @return Box[pure]<int>
                     */
                    function pureBox(): Box { throw new RuntimeException(); }

                    /**
                     * @psalm-pure
                     * @return Box[io]<int>
                     */
                    function ioBox(): Box { throw new RuntimeException(); }

                    /**
                     * @psalm-purity-template Q
                     * @param Closure[Q](): Box[Q]<mixed> $a
                     * @return Box[Q]<mixed>
                     * @psalm-pure
                     */
                    function fromClosure(Closure $a): Box { throw new RuntimeException(); }

                    /**
                     * @psalm-purity-template Q
                     * @param Generator[Q]<mixed, Box[Q]<mixed>, mixed, mixed> $g
                     * @return Box[Q]<mixed>
                     * @psalm-pure
                     */
                    function fromGenerator(Generator $g): Box { throw new RuntimeException(); }

                    /**
                     * @psalm-purity-template Q
                     * @param Closure[Q](): mixed $a
                     * @param Closure[Q](): mixed $b
                     * @return Box[Q]<mixed>
                     * @psalm-pure
                     */
                    function fromTwo(Closure $a, Closure $b): Box { throw new RuntimeException(); }

                    $gen = static function (): Generator { echo 1; yield pureBox(); };

                    $io_closure = fromClosure(static function (): Box { echo 1; return pureBox(); });
                    $io_inner = fromClosure(static fn(): Box => ioBox());
                    $pure = fromClosure(static fn(): Box => pureBox());
                    $io_generator = fromGenerator($gen());
                    $io_second = fromTwo(static fn(): int => 1, static function (): int { echo 1; return 1; });
                    $pure_two = fromTwo(static fn(): int => 1, static fn(): int => 2);',
                'assertions' => [
                    '$io_closure' => 'Box[io]<mixed>',
                    '$io_inner' => 'Box[io]<mixed>',
                    '$pure' => 'Box[pure]<mixed>',
                    '$io_generator' => 'Box[io]<mixed>',
                    '$io_second' => 'Box[io]<mixed>',
                    '$pure_two' => 'Box[pure]<mixed>',
                ],
            ],
            'typeTemplateInferredFromSeveralPositionsKeepsTheDeepestBound' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param array<T>|T $value
                     * @return T
                     * @psalm-pure
                     */
                    function unwrap($value) { throw new RuntimeException(); }

                    $x = unwrap([1, 2]);',
                'assertions' => [
                    '$x===' => '1|2',
                ],
            ],
            'subclassFitsItsParentWithOmittedTemplateArguments' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @psalm-purity-template P
                     */
                    interface Driver {
                        /**
                         * @return T
                         * @psalm-capabilities read-props
                         * @psalm-purity-from-template P
                         */
                        public function consume();
                    }

                    /** @implements Driver[pure]<int> */
                    final class NullDriver implements Driver {
                        /** @psalm-pure */
                        #[Override]
                        public function consume(): int {
                            return 1;
                        }
                    }

                    /** @implements Driver<int> */
                    final class EchoDriver implements Driver {
                        #[Override]
                        public function consume(): int {
                            echo "consumed";
                            return 1;
                        }
                    }

                    function takeAny(Driver $_driver): void {}

                    /** @param Driver[pure] $_driver */
                    function takePure(Driver $_driver): void {}

                    /** @param Driver[impure]<int> $_driver */
                    function takeImpureInt(Driver $_driver): void {}

                    takeAny(new NullDriver());
                    takeAny(new EchoDriver());
                    takeImpureInt(new NullDriver());
                    takeImpureInt(new EchoDriver());
                    takePure(new NullDriver());',
            ],
            'instanceofSubclassBindingTheParentsPurityToItsOwn' => [
                'code' => '<?php
                    /**
                     * @template-covariant T
                     * @psalm-purity-template P
                     */
                    abstract class Future {}

                    /**
                     * @template TKey as array-key
                     * @template-covariant TValue
                     * @psalm-purity-template C
                     * @extends Future[C]<array<TKey, TValue>>
                     */
                    final class CombinedFuture extends Future {}

                    function isCombined(Future $future): bool {
                        if ($future instanceof CombinedFuture) {
                            return true;
                        }

                        return false;
                    }',
            ],
            'omittedPurityArgumentOfAReceiverIsItsDefault' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @psalm-purity-template P(write-props) <= write-props
                     */
                    class Box {
                        /**
                         * @param T $value
                         * @psalm-capabilities read-props|write-this-props
                         * @psalm-purity-from-template P
                         */
                        public function set($value): void {}
                    }

                    /**
                     * @psalm-capabilities read-props|write-props
                     * @param Box<int> $box
                     */
                    function fill(Box $box): void {
                        $box->set(1);
                    }',
            ],
            'constructorReturnTypeBindsAPurityTemplateFromTheArgument' => [
                'code' => '<?php
                    /**
                     * @template TValue
                     * @psalm-purity-template TStorage(write-props) <= write-props
                     * @psalm-capabilities read-props|write-this-props
                     */
                    final class Store {
                        private int $writes = 0;

                        /**
                         * @param array<TValue>|object $storage
                         * @return ($storage is array ? Store[pure]<TValue> : Store[write-props]<TValue>)
                         * @psalm-pure
                         */
                        public function __construct($storage = []) {}

                        /**
                         * @param TValue $value
                         * @psalm-purity-from-template TStorage
                         */
                        public function add($value): void {
                            $this->writes++;
                        }

                        /** @psalm-pure */
                        public static function make(): int {
                            $store = new static([1]);
                            $store->add(2);
                            return 1;
                        }
                    }

                    /** @psalm-pure */
                    function arrayBacked(): int {
                        $store = new Store([1]);
                        $store->add(2);
                        $empty = new Store();
                        $empty->add(3);
                        return 1;
                    }

                    $array_backed = new Store(["a"]);
                    $object_backed = new Store(new stdClass());',
                'assertions' => [
                    '$array_backed' => 'Store[pure]<string>',
                    '$object_backed' => 'Store[write-props]<mixed>',
                ],
            ],
            'constructorReturnTypeBindsATypeTemplate' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @return Box<int> */
                        public function __construct() {}
                    }

                    $box = new Box();',
                'assertions' => [
                    '$box' => 'Box<int>',
                ],
            ],
            'subclassInheritingAConstructorWithAReturnTypeGetsThePlainType' => [
                'code' => '<?php
                    /**
                     * @template TValue
                     * @psalm-purity-template TStorage(write-props) <= write-props
                     * @psalm-capabilities read-props|write-this-props
                     */
                    class Store {
                        private int $writes = 0;

                        /**
                         * @param array<TValue>|object $storage
                         * @return ($storage is array ? Store[pure]<TValue> : Store[write-props]<TValue>)
                         * @psalm-pure
                         */
                        public function __construct($storage = []) {}

                        /**
                         * @param TValue $value
                         * @psalm-purity-from-template TStorage
                         */
                        public function add($value): void {
                            $this->writes++;
                        }
                    }

                    /**
                     * @extends Store<int>
                     * @psalm-capabilities read-props|write-this-props
                     */
                    final class Sub extends Store {}

                    $sub = new Sub([1]);',
                'assertions' => [
                    '$sub' => 'Sub',
                ],
            ],
            'conditionalPurityArgumentOfAConstructorReturnType' => [
                'code' => '<?php
                    /**
                     * @template K of array-key
                     * @template V
                     * @psalm-purity-template P
                     */
                    final class Container {}

                    /**
                     * @psalm-purity-template P
                     * @template K of array-key
                     * @template V
                     */
                    final class Wrapper {
                        /**
                         * @psalm-purity-template PP
                         * @param array<K, V>|Container[PP]<K, V> $param
                         * @return self[$param is array ? pure : PP]<K, V>
                         * @psalm-pure
                         */
                        public function __construct($param) {}

                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P
                         */
                        public function run(): int {
                            return 1;
                        }
                    }

                    /** @return array<int, int> */
                    function ints(): array {
                        return [1, 2];
                    }

                    /** @return Container[io]<string, int> */
                    function ioContainer(): Container {
                        throw new RuntimeException();
                    }

                    /** @psalm-pure */
                    function viaArray(): int {
                        $wrapper = new Wrapper([1, 2]);
                        return $wrapper->run();
                    }

                    /**
                     * @psalm-capabilities io
                     * @param Container[io]<string, int> $container
                     */
                    function viaContainer(Container $container): int {
                        $wrapper = new Wrapper($container);
                        return $wrapper->run();
                    }

                    $from_literals = new Wrapper([1, 2]);
                    $from_array = new Wrapper(ints());
                    $from_container = new Wrapper(ioContainer());',
                'assertions' => [
                    '$from_literals' => 'Wrapper[pure]<int<0, 1>, int>',
                    '$from_array' => 'Wrapper[pure]<int, int>',
                    '$from_container' => 'Wrapper[io]<string, int>',
                ],
            ],
            'conditionalConstructorReturnTypeWithPurityAndTypeArguments' => [
                'code' => '<?php
                    /**
                     * @template K of array-key
                     * @template V
                     * @psalm-purity-template P
                     */
                    final class Container {}

                    /**
                     * @psalm-purity-template P
                     * @template K of array-key
                     * @template V
                     */
                    final class Wrapper {
                        /**
                         * @psalm-purity-template PP
                         * @param array<K, V>|Container[PP]<K, V> $param
                         * @return ($param is array ? self[pure]<K, V> : self[PP]<string, V>)
                         * @psalm-pure
                         */
                        public function __construct($param) {}

                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P
                         */
                        public function run(): int {
                            return 1;
                        }
                    }

                    /**
                     * @psalm-purity-template P
                     * @template K of array-key
                     * @template V
                     */
                    final class ArmWrapper {
                        /**
                         * @psalm-purity-template PP
                         * @param array<K, V>|Container[PP]<K, V> $param
                         * @return ($param is array ? self[pure]<int, V> : self[PP]<K, mixed>)
                         * @psalm-pure
                         */
                        public function __construct($param) {}
                    }

                    /** @return Container[io]<string, int> */
                    function ioContainer(): Container {
                        throw new RuntimeException();
                    }

                    /** @psalm-pure */
                    function viaArray(): int {
                        $wrapper = new Wrapper(["a" => 1]);
                        return $wrapper->run();
                    }

                    $from_array = new Wrapper(["a" => 1]);
                    $from_container = new Wrapper(ioContainer());
                    $arm_from_array = new ArmWrapper(["a" => 1]);
                    $arm_from_container = new ArmWrapper(ioContainer());',
                'assertions' => [
                    '$from_array' => 'Wrapper[pure]<string, int>',
                    '$from_container' => 'Wrapper[io]<string, int>',
                    '$arm_from_array' => 'ArmWrapper[pure]<int, int>',
                    '$arm_from_container' => 'ArmWrapper[io]<string, mixed>',
                ],
            ],
            'conditionalPurityArgumentsBindEachPurityTemplate' => [
                'code' => '<?php
                    /**
                     * @template K of array-key
                     * @template V
                     * @psalm-purity-template P
                     */
                    final class Container {}

                    /**
                     * @template T
                     * @psalm-purity-template P1
                     * @psalm-purity-template P2
                     */
                    final class Pair {
                        /**
                         * @psalm-purity-template PA
                         * @psalm-purity-template PB
                         * @param list<T>|Container[PA]<int, T> $first
                         * @param list<T>|Container[PB]<int, T> $second
                         * @return self[$first is array ? pure : PA, $second is array ? pure : PB]<T>
                         * @psalm-pure
                         */
                        public function __construct($first, $second) {}

                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P1
                         */
                        public function runFirst(): int {
                            return 1;
                        }

                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P2
                         */
                        public function runSecond(): int {
                            return 2;
                        }
                    }

                    /** @return Container[io]<int, int> */
                    function ioContainer(): Container {
                        throw new RuntimeException();
                    }

                    /** @return Container[write-props]<int, int> */
                    function writingContainer(): Container {
                        throw new RuntimeException();
                    }

                    /**
                     * @psalm-pure
                     * @param Container[io]<int, int> $container
                     */
                    function viaFirstArray(Container $container): int {
                        $pair = new Pair([1], $container);
                        return $pair->runFirst();
                    }

                    /**
                     * @psalm-pure
                     * @param Container[io]<int, int> $container
                     */
                    function viaSecondArray(Container $container): int {
                        $pair = new Pair($container, [1]);
                        return $pair->runSecond();
                    }

                    $first_array = new Pair([1], ioContainer());
                    $second_array = new Pair(writingContainer(), [1]);
                    $both_containers = new Pair(writingContainer(), ioContainer());',
                'assertions' => [
                    '$first_array' => 'Pair[pure, io]<int>',
                    '$second_array' => 'Pair[write-props, pure]<int>',
                    '$both_containers' => 'Pair[write-props, io]<int>',
                ],
            ],
            'conditionalPurityArgumentOfAnArgumentOfEitherKind' => [
                'code' => '<?php
                    /**
                     * @template K of array-key
                     * @template V
                     * @psalm-purity-template P
                     */
                    final class Container {}

                    /**
                     * @psalm-purity-template P
                     * @template K of array-key
                     * @template V
                     */
                    final class Wrapper {
                        /**
                         * @psalm-purity-template PP
                         * @param array<K, V>|Container[PP]<K, V> $param
                         * @return self[$param is array ? pure : PP]<K, V>
                         * @psalm-pure
                         */
                        public function __construct($param) {}
                    }

                    /** @return array<string, int>|Container[io]<string, int> */
                    function either() {
                        throw new RuntimeException();
                    }

                    $from_either = new Wrapper(either());',
                'assertions' => [
                    '$from_either' => 'Wrapper[io]<string, int>',
                ],
            ],
            'conditionalPurityArgumentOfAnUnboundArgument' => [
                'code' => '<?php
                    /**
                     * @template K of array-key
                     * @template V
                     * @psalm-purity-template P(write-props) <= write-props|io
                     */
                    final class Container {}

                    /**
                     * @psalm-purity-template P(io) <= write-props|io
                     * @template K of array-key
                     * @template V
                     */
                    final class Wrapper {
                        /**
                         * @psalm-purity-template PP <= write-props|io
                         * @param array<K, V>|Container[PP]<K, V> $param
                         * @return self[$param is array ? pure : PP]<K, V>
                         * @psalm-pure
                         */
                        public function __construct($param) {}
                    }

                    /** @return Container<string, int> */
                    function defaultContainer(): Container {
                        throw new RuntimeException();
                    }

                    /** @return Container */
                    function anyContainer(): Container {
                        throw new RuntimeException();
                    }

                    $from_default = new Wrapper(defaultContainer());
                    $from_any = new Wrapper(anyContainer());',
                'assertions' => [
                    '$from_default' => 'Wrapper[write-props]<string, int>',
                    '$from_any' => 'Wrapper[write-props|io]<array-key, mixed>',
                ],
            ],
            'conditionalPurityArgumentOfASelfOutType' => [
                'code' => '<?php
                    /** @psalm-purity-template P */
                    final class Container {}

                    /**
                     * @template T
                     * @psalm-purity-template P
                     */
                    final class Box {
                        /**
                         * @param list<T>|Container $storage
                         * @psalm-self-out self[$storage is array ? pure : write-props]<T>
                         * @psalm-mutation-free
                         */
                        public function setStorage($storage): void {}

                        /**
                         * @psalm-purity-template PP
                         * @param list<T>|Container[PP] $storage
                         * @psalm-this-out self[$storage is array ? pure : PP]<T>
                         * @psalm-mutation-free
                         */
                        public function setContainer($storage): void {}

                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P
                         */
                        public function run(): int {
                            return 1;
                        }
                    }

                    /**
                     * @return Box[io]<int>
                     * @psalm-pure
                     */
                    function box(): Box {
                        throw new RuntimeException();
                    }

                    /**
                     * @return Container[io]
                     * @psalm-pure
                     */
                    function ioContainer(): Container {
                        throw new RuntimeException();
                    }

                    /** @psalm-pure */
                    function viaArray(): int {
                        $box = box();
                        $box->setStorage([1]);
                        return $box->run();
                    }

                    /** @psalm-pure */
                    function viaArrayContainer(): int {
                        $box = box();
                        $box->setContainer([1]);
                        return $box->run();
                    }

                    $from_array = box();
                    $from_array->setStorage([1]);
                    $from_object = box();
                    $from_object->setStorage(new Container());
                    $from_container = box();
                    $from_container->setContainer(ioContainer());
                    $from_container_array = box();
                    $from_container_array->setContainer([1]);',
                'assertions' => [
                    '$from_array' => 'Box[pure]<int>',
                    '$from_object' => 'Box[write-props]<int>',
                    '$from_container' => 'Box[io]<int>',
                    '$from_container_array' => 'Box[pure]<int>',
                ],
            ],
            'conditionalPurityArgumentsOfASelfOutType' => [
                'code' => '<?php
                    /** @psalm-purity-template P */
                    final class Container {}

                    /**
                     * @psalm-purity-template P1 <= write-props|io
                     * @psalm-purity-template P2 <= write-props|io
                     */
                    final class Pair {
                        /**
                         * @param list<int>|Container $first
                         * @param list<int>|Container $second
                         * @psalm-self-out self[$first is array ? pure : io, $second is array ? pure : write-props]
                         * @psalm-mutation-free
                         */
                        public function set($first, $second): void {}
                    }

                    /** @return Pair[io, io] */
                    function pair(): Pair {
                        throw new RuntimeException();
                    }

                    $first_array = pair();
                    $first_array->set([1], new Container());
                    $second_array = pair();
                    $second_array->set(new Container(), [1]);',
                'assertions' => [
                    '$first_array' => 'Pair[pure, write-props]',
                    '$second_array' => 'Pair[io, pure]',
                ],
            ],
            'conditionalPurityArgumentOfAMethodReturnType' => [
                'code' => '<?php
                    /** @psalm-purity-template P */
                    final class Container {}

                    /**
                     * @template T
                     * @psalm-purity-template P
                     */
                    final class Box {
                        /**
                         * @param list<T>|Container $storage
                         * @return static[$storage is array ? pure : P]<T>
                         * @psalm-mutation-free
                         */
                        public function withStorage($storage): static {
                            throw new RuntimeException();
                        }

                        /**
                         * @param list<T>|Container $storage
                         * @return self[$storage is array ? pure : write-props]<T>
                         * @psalm-mutation-free
                         */
                        public function withSelf($storage): self {
                            throw new RuntimeException();
                        }

                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P
                         */
                        public function run(): int {
                            return 1;
                        }
                    }

                    /**
                     * @return Box[io]<int>
                     * @psalm-pure
                     */
                    function box(): Box {
                        throw new RuntimeException();
                    }

                    /** @psalm-pure */
                    function viaArray(): int {
                        return box()->withStorage([1])->run();
                    }

                    $static_array = box()->withStorage([1]);
                    $static_object = box()->withStorage(new Container());
                    $self_array = box()->withSelf([1]);
                    $self_object = box()->withSelf(new Container());',
                'assertions' => [
                    '$static_array' => 'Box[pure]<int>',
                    '$static_object' => 'Box[io]<int>',
                    '$self_array' => 'Box[pure]<int>',
                    '$self_object' => 'Box[write-props]<int>',
                ],
            ],
            'conditionalPurityArgumentOfAStaticFactoryReturnType' => [
                'code' => '<?php
                    /** @psalm-purity-template P */
                    final class Container {}

                    /**
                     * @template K of array-key
                     * @template V
                     * @psalm-purity-template P
                     */
                    final class Map {
                        /**
                         * @template TK of array-key
                         * @template TV
                         * @param array<TK, TV>|Container $storage
                         * @return self[$storage is array ? pure : write-props]<TK, TV>
                         * @psalm-pure
                         */
                        public static function from($storage): self {
                            throw new RuntimeException();
                        }

                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P
                         */
                        public function run(): int {
                            return 1;
                        }
                    }

                    /** @psalm-pure */
                    function viaArray(): int {
                        return Map::from(["a" => 1])->run();
                    }

                    $from_array = Map::from(["a" => 1]);
                    $from_object = Map::from(new Container());',
                'assertions' => [
                    '$from_array' => 'Map[pure]<string, int>',
                    '$from_object' => 'Map[write-props]<array-key, mixed>',
                ],
            ],
            'conditionalPurityArgumentOfAFunctionReturnType' => [
                'code' => '<?php
                    /** @psalm-purity-template P */
                    final class Container {}

                    /**
                     * @template T
                     * @psalm-purity-template P
                     */
                    final class Box {
                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P
                         */
                        public function run(): int {
                            return 1;
                        }
                    }

                    /**
                     * @param list<int>|Container $storage
                     * @return Box[$storage is array ? pure : write-props]<int>
                     * @psalm-pure
                     */
                    function box($storage): Box {
                        throw new RuntimeException();
                    }

                    /** @psalm-pure */
                    function viaArray(): int {
                        return box([1])->run();
                    }

                    $from_array = box([1]);
                    $from_object = box(new Container());',
                'assertions' => [
                    '$from_array' => 'Box[pure]<int>',
                    '$from_object' => 'Box[write-props]<int>',
                ],
            ],
            'conditionalPurityArgumentNestedInAReturnType' => [
                'code' => '<?php
                    /** @psalm-purity-template P */
                    final class Container {}

                    /**
                     * @template T
                     * @psalm-purity-template P
                     */
                    final class Box {
                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P
                         */
                        public function run(): int {
                            return 1;
                        }
                    }

                    /**
                     * @param list<int>|Container $storage
                     * @return list<Box[$storage is array ? pure : io]<int>>
                     * @psalm-pure
                     */
                    function boxes($storage): array {
                        throw new RuntimeException();
                    }

                    /**
                     * @param list<int>|Container $storage
                     * @return Box[$storage is array ? pure : io]<int>|null
                     * @psalm-pure
                     */
                    function maybeBox($storage): ?Box {
                        return null;
                    }

                    /** @psalm-pure */
                    function viaArray(): int {
                        return boxes([1])[0]->run();
                    }

                    $list_array = boxes([1]);
                    $list_object = boxes(new Container());
                    $union_array = maybeBox([1]);
                    $union_object = maybeBox(new Container());',
                'assertions' => [
                    '$list_array' => 'list<Box[pure]<int>>',
                    '$list_object' => 'list<Box[io]<int>>',
                    '$union_array' => 'Box[pure]<int>|null',
                    '$union_object' => 'Box[io]<int>|null',
                ],
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
            'callbackStoredByAMethodNotWideningThePurityTemplate' => [
                'code' => '<?php
                    /**
                     * @psalm-purity-template P
                     * @psalm-capabilities read-props|write-this-props|write-refs
                     */
                    final class Box {
                        /** @var list<callable[P](): void> */
                        private array $cbs = [];

                        /**
                         * @psalm-purity-template R
                         * @param callable[R](): void $cb
                         * @psalm-self-out Box[P]
                         */
                        public function then(callable $cb): void {
                            $this->cbs[] = $cb;
                        }
                    }',
                'error_message' => 'PropertyTypeCoercion',
            ],
            'traitMethodNeedingMoreThanThePurityTemplateTheClassBinds' => [
                'code' => '<?php
                    /** @psalm-purity-template P */
                    interface Getter {
                        /**
                         * @psalm-capabilities read-props
                         * @psalm-purity-from-template P
                         */
                        public function get(): string;
                    }

                    trait EchoingGetter {
                        public function get(): string {
                            echo "x";
                            return "x";
                        }
                    }

                    /** @implements Getter[pure] */
                    final class Doer implements Getter {
                        use EchoingGetter;
                    }',
                'error_message' => 'ImmutableDependency',
            ],
            'traitMethodNeedingMoreThanTheBoundOfAPurityTemplateTheClassDoesNotBind' => [
                'code' => '<?php
                    /** @psalm-purity-template P <= read-props|write-props */
                    interface Getter {
                        /**
                         * @psalm-capabilities read-props
                         * @psalm-purity-from-template P
                         */
                        public function get(): string;
                    }

                    trait EchoingGetter {
                        public function get(): string {
                            echo "x";
                            return "x";
                        }
                    }

                    final class Doer implements Getter {
                        use EchoingGetter;
                    }',
                'error_message' => 'ImmutableDependency',
            ],
            'methodNeedingMoreThanTheBoundOfAPurityTemplateTheClassDoesNotBind' => [
                'code' => '<?php
                    /** @psalm-purity-template P <= read-props|write-props */
                    interface Getter {
                        /**
                         * @psalm-capabilities read-props
                         * @psalm-purity-from-template P
                         */
                        public function get(): string;
                    }

                    final class Doer implements Getter {
                        public function get(): string {
                            echo "x";
                            return "x";
                        }
                    }',
                'error_message' => 'ImmutableDependency',
            ],
            'traitMethodNeedingMoreThanAMethodWithoutPurityTemplate' => [
                'code' => '<?php
                    interface Getter {
                        /** @psalm-capabilities read-props */
                        public function get(): string;
                    }

                    trait EchoingGetter {
                        public function get(): string {
                            echo "x";
                            return "x";
                        }
                    }

                    final class Doer implements Getter {
                        use EchoingGetter;
                    }',
                'error_message' => 'ImmutableDependency',
            ],
            'purityArgumentLeftOutWithoutDefaultIsItsUpperBound' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @psalm-purity-template P
                     */
                    abstract class Task {
                        /**
                         * @psalm-capabilities read-props
                         * @psalm-purity-from-template P
                         */
                        abstract public function run(): int;
                    }

                    /**
                     * @param Task<int> $task
                     * @psalm-pure
                     */
                    function runIt(Task $task): int {
                        return $task->run();
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'pureClosureParamInOverrideOfWildcardOneIsComparedWithTheWildcardsBound' => [
                'code' => '<?php
                    abstract class Base {
                        /** @param Closure[_](int): int $g */
                        public function run(Closure $g): int {
                            return $g(1);
                        }
                    }

                    final class Child extends Base {
                        /** @param Closure[pure](int): int $g */
                        #[Override]
                        public function run(Closure $g): int {
                            return $g(2);
                        }
                    }',
                'error_message' => 'MoreSpecificImplementedParamType - src' . DIRECTORY_SEPARATOR . 'somefile.php:12:53 - Argument 1 of Child::run has the more specific type \'Closure[pure](int):int\', expecting \'Closure[impure](int):int\' as defined by Base::run',
            ],
            'pureClosureParamInOverrideOfPurityTemplateOneIsComparedWithTheTemplatesBound' => [
                'code' => '<?php
                    abstract class Base {
                        /**
                         * @psalm-purity-template P
                         * @param Closure[P](int): int $g
                         */
                        public function run(Closure $g): int {
                            return $g(1);
                        }
                    }

                    final class Child extends Base {
                        /** @param Closure[pure](int): int $g */
                        #[Override]
                        public function run(Closure $g): int {
                            return $g(2);
                        }
                    }',
                'error_message' => 'MoreSpecificImplementedParamType - src' . DIRECTORY_SEPARATOR . 'somefile.php:15:53 - Argument 1 of Child::run has the more specific type \'Closure[pure](int):int\', expecting \'Closure[impure](int):int\' as defined by Base::run',
            ],
            'callOfTypeTemplateBoundToClosureChecksArguments' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @template TCallback as (Closure(int): string)|null
                     * @param TCallback $cb
                     * @psalm-purity-from-template TCallback
                     */
                    function apply(?Closure $cb = null): string {
                        if ($cb !== null) {
                            return $cb("x");
                        }
                        return "";
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
            'callOfTypeTemplateBoundToClosureReturnsTheClosuresType' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @template TCallback as (Closure(int): string)|null
                     * @param TCallback $cb
                     * @psalm-purity-from-template TCallback
                     */
                    function apply(?Closure $cb = null): int {
                        if ($cb !== null) {
                            return $cb(1);
                        }
                        return 0;
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'callOfTypeTemplateBoundToClosureChecksThePurityOfWhatIsDoneWithTheResult' => [
                'code' => '<?php
                    final class Counter {
                        public int $n = 0;

                        public function inc(): int {
                            return ++$this->n;
                        }
                    }

                    /**
                     * @psalm-pure
                     * @template TCallback as Closure(int): Counter
                     * @param TCallback $cb
                     * @psalm-purity-from-template TCallback
                     */
                    function apply(Closure $cb): int {
                        return $cb(1)->inc();
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'callOfTypeTemplateBoundToClosureInAMutationFreeMethodChecksThePurityOfWhatIsDoneWithTheResult' => [
                'code' => '<?php
                    final class Counter {
                        public int $n = 0;
                    }

                    final class Runner {
                        /**
                         * @psalm-mutation-free
                         * @template TCallback as Closure(int): Counter
                         * @param TCallback $cb
                         * @psalm-purity-from-template TCallback
                         */
                        public function run(Closure $cb): void {
                            $cb(1)->n = 2;
                        }
                    }',
                'error_message' => 'ImpurePropertyAssignment',
            ],
            'classPurityTemplateWritingThisOfAGlobalReceiverNeedsWriteGlobals' => [
                'code' => '<?php
                    /** @psalm-purity-template C */
                    abstract class Doer {
                        /** @psalm-purity-from-template C */
                        abstract public function run(): void;
                    }

                    /** @extends Doer[write-this-props] */
                    final class MutatingDoer extends Doer {
                        public int $runs = 0;

                        /** @psalm-capabilities read-props|write-this-props */
                        public function run(): void {
                            $this->runs++;
                        }
                    }

                    final class Registry {
                        public static ?MutatingDoer $doer = null;
                    }

                    /** @psalm-capabilities write-props|read-globals */
                    function global_(): void {
                        $d = Registry::$doer;
                        if ($d !== null) {
                            $d->run();
                        }
                    }',
                'error_message' => 'requires write-props|write-globals',
            ],
            'classPurityTemplateWritingThisOfAnotherReceiverNeedsWriteProps' => [
                'code' => '<?php
                    /** @psalm-purity-template C */
                    abstract class Doer {
                        /** @psalm-purity-from-template C */
                        abstract public function run(): void;
                    }

                    /** @extends Doer[write-this-props] */
                    final class MutatingDoer extends Doer {
                        public int $runs = 0;

                        /** @psalm-capabilities read-props|write-this-props */
                        public function run(): void {
                            $this->runs++;
                        }
                    }

                    /** @psalm-capabilities write-this-props */
                    function other(MutatingDoer $d): void {
                        $d->run();
                    }',
                'error_message' => 'requires write-props',
            ],
            'impureClosureMakesCallImpure' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P](): void $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): void {
                        $f();
                    }

                    /** @psalm-pure */
                    function usePure(): void {
                        apply(function (): void { echo "hi"; });
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'impureClosureParamMakesCallImpure' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P](): void $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): void {
                        $f();
                    }

                    /**
                     * @psalm-pure
                     * @param Closure(): void $f
                     */
                    function usePure(Closure $f): void {
                        apply($f);
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'impureClosureMakesStaticCallImpure' => [
                'code' => '<?php
                    final class Holder {
                        /**
                         * @psalm-pure
                         * @psalm-purity-template P
                         * @param Closure[P](): void $f
                         * @psalm-purity-from-template P
                         */
                        public static function run(Closure $f): void {
                            $f();
                        }
                    }

                    /** @psalm-pure */
                    function usePure(): void {
                        Holder::run(function (): void { echo "hi"; });
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'impureClosureMakesConstructorImpure' => [
                'code' => '<?php
                    final class Holder {
                        /**
                         * @psalm-pure
                         * @psalm-purity-template P
                         * @param Closure[P](): void $f
                         * @psalm-purity-from-template P
                         */
                        public function __construct(Closure $f) {
                            $f();
                        }
                    }

                    /** @psalm-pure */
                    function usePure(): Holder {
                        return new Holder(function (): void { echo "hi"; });
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'impureClosureMakesMethodCallImpure' => [
                'code' => '<?php
                    final class Holder {
                        /**
                         * @psalm-pure
                         * @psalm-purity-template P
                         * @param Closure[P](): void $f
                         * @psalm-purity-from-template P
                         */
                        public function run(Closure $f): void {
                            $f();
                        }
                    }

                    /** @psalm-pure */
                    function usePure(Holder $h): void {
                        $h->run(function (): void { echo "hi"; });
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'firstClassCallableOfImpureFunction' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P](): void $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): void {
                        $f();
                    }

                    function impure(): void {
                        echo "hi";
                    }

                    /** @psalm-pure */
                    function usePure(): void {
                        apply(impure(...));
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'bodyMayOnlyUseItsOwnCapabilities' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P](): void $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): void {
                        $f();
                        echo "done";
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'templatedClosureNotInheritedFrom' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P](): void $f
                     */
                    function apply(Closure $f): void {
                        $f();
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'reassignedClosureIsNoLongerTemplated' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P](): void $f
                     * @param Closure(): void $g
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f, Closure $g): void {
                        $f = $g;
                        $f();
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'ownCapabilitiesAreStillRequired' => [
                'code' => '<?php
                    /**
                     * @psalm-capabilities write-props
                     * @psalm-purity-template P
                     * @param Closure[P](): void $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): int {
                        $f();
                        return 1;
                    }

                    /** @psalm-pure */
                    function usePure(): int {
                        return apply(function (): void {});
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'closureCapabilitiesAreAddedToOwn' => [
                'code' => '<?php
                    /**
                     * @psalm-capabilities write-props
                     * @psalm-purity-template P
                     * @param Closure[P](): void $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): int {
                        $f();
                        return 1;
                    }

                    /** @psalm-capabilities write-props */
                    function useWriteProps(): int {
                        return apply(function (): void { echo "x"; });
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'classPurityTemplateBoundByFunctionPurityTemplateIsCharged' => [
                'code' => '<?php
                    /** @psalm-purity-template C */
                    final class Box {
                        /**
                         * @param Closure[C](): int $cb
                         * @psalm-pure
                         */
                        public function __construct(private Closure $cb) {}

                        /**
                         * @psalm-mutation-free
                         * @psalm-purity-from-template C
                         */
                        public function fire(): int {
                            return ($this->cb)();
                        }
                    }

                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P](): int $f
                     * @return Box[P]
                     */
                    function makeBox(Closure $f): Box {
                        return new Box($f);
                    }

                    /** @psalm-pure */
                    function useImpure(): int {
                        return makeBox(function (): int {
                            echo "x";
                            return 1;
                        })->fire();
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'classPurityTemplateBoundToImpure' => [
                'code' => '<?php
                    /** @psalm-purity-template C */
                    abstract class Doer {
                        /**
                         * @psalm-mutation-free
                         * @psalm-purity-from-template C
                         */
                        abstract public function doWork(): int;
                    }

                    /** @extends Doer[io] */
                    final class IoDoer extends Doer {
                        /** @psalm-capabilities io */
                        public function doWork(): int {
                            echo "x";
                            return 1;
                        }
                    }

                    /** @psalm-pure */
                    function usePure(IoDoer $d): int {
                        return $d->doWork();
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'unboundClassPurityTemplateIsImpure' => [
                'code' => '<?php
                    /** @psalm-purity-template C */
                    abstract class Doer {
                        /**
                         * @psalm-mutation-free
                         * @psalm-purity-from-template C
                         */
                        abstract public function doWork(): int;
                    }

                    /** @psalm-pure */
                    function usePure(Doer $d): int {
                        return $d->doWork();
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'purityTemplateCovarianceViolation' => [
                'code' => '<?php
                    /** @psalm-purity-template C */
                    abstract class Doer {}

                    /** @extends Doer[io] */
                    final class IoDoer extends Doer {}

                    /** @param Doer[pure] $d */
                    function takesPureDoer(Doer $d): void {}

                    function test(IoDoer $d): void {
                        takesPureDoer($d);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'constructorBindsClassPurityTemplate' => [
                'code' => '<?php
                    /** @psalm-purity-template C */
                    final class Box {
                        /**
                         * @param Closure[C](): void $cb
                         * @psalm-pure
                         */
                        public function __construct(private Closure $cb) {}

                        /**
                         * @psalm-mutation-free
                         * @psalm-purity-from-template C
                         */
                        public function fire(): int {
                            ($this->cb)();
                            return 1;
                        }
                    }

                    /** @psalm-pure */
                    function usePure(): int {
                        $b = new Box(function (): void { echo "x"; });
                        return $b->fire();
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'typeTemplateBoundToImpureClosure' => [
                'code' => '<?php
                    /**
                     * @template T of callable(): void
                     */
                    final class Deferred {
                        /** @var T */
                        private $callback;

                        /**
                         * @param T $callback
                         * @psalm-external-mutation-free
                         */
                        public function __construct($callback) {
                            $this->callback = $callback;
                        }

                        /**
                         * @psalm-external-mutation-free
                         * @psalm-purity-from-template T
                         */
                        public function run(): void {
                            ($this->callback)();
                        }
                    }

                    /**
                     * @psalm-external-mutation-free
                     * @param Deferred<Closure(): void> $deferred
                     */
                    function runImpure(Deferred $deferred): void {
                        $deferred->run();
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'purityFromUnknownTemplate' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-from-template P
                     */
                    function apply(): void {}',
                'error_message' => 'InvalidDocblock',
            ],
            'purityFromNonPurityTemplate' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @template T
                     * @param T $t
                     * @psalm-purity-from-template T
                     * @return T
                     */
                    function apply($t) {
                        return $t;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'nestedClosureCarryingOuterPurityTemplateIsChargedWhenCalled' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P](int): string $val
                     * @return Closure[P](int): string
                     */
                    function escape(Closure $val): Closure {
                        return static fn(int $item): string => htmlspecialchars($val($item));
                    }

                    /** @psalm-pure */
                    function useImpure(): string {
                        return escape(function (int $i): string { echo $i; return (string) $i; })(1);
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'callingNestedClosureWithOuterPurityTemplateCostsItsBound' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P](): int $f
                     */
                    function callsIt(Closure $f): int {
                        $g = fn(): int => $f();
                        return $g();
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'pureFunctionLikeCallingAParamAndReturningAClosureCallingItPaysForItsOwnCall' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P](int): string $val
                     * @return list{string, Closure[P](int): string}
                     */
                    function both(Closure $val): array {
                        return [$val(0), static fn(int $item): string => $val($item)];
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'functionLikeInheritingThePurityOfAParamItCallsAndReturnsAClosureCallingIsChargedForItsOwnCall' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P](int): string $val
                     * @return list{string, Closure[P](int): string}
                     * @psalm-purity-from-template P
                     */
                    function both(Closure $val): array {
                        return [$val(0), static fn(int $item): string => $val($item)];
                    }

                    /** @psalm-pure */
                    function useImpure(): string {
                        return both(function (int $i): string { echo $i; return (string) $i; })[0];
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'nestedClosurePassingOnOuterPurityTemplateIsChargedWhenCalled' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template Q
                     * @param Closure[Q](int): string $f
                     * @psalm-purity-from-template Q
                     */
                    function apply(Closure $f): string {
                        return $f(1);
                    }

                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P](int): string $val
                     * @return Closure[P](): string
                     */
                    function later(Closure $val): Closure {
                        return static fn(): string => apply($val);
                    }

                    /** @psalm-pure */
                    function useImpure(): string {
                        return later(function (int $i): string { echo $i; return (string) $i; })();
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'nestedClosurePassingOnPurityFromTemplateIsNotPure' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template Q
                     * @param Closure[Q](int): string $f
                     * @psalm-purity-from-template Q
                     */
                    function apply(Closure $f): string {
                        return $f(1);
                    }

                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P](int): string $val
                     * @return pure-Closure(): string
                     * @psalm-purity-from-template P
                     */
                    function later(Closure $val): Closure {
                        return static fn(): string => apply($val);
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'nestedClosureCarryingClassPurityTemplateIsChargedWhenCalled' => [
                'code' => '<?php
                    /** @psalm-purity-template C */
                    final class Box {
                        /**
                         * @param Closure[C](): int $cb
                         * @psalm-pure
                         */
                        public function __construct(private Closure $cb) {}

                        /**
                         * @psalm-mutation-free
                         * @return Closure[C|read-props](): int
                         */
                        public function wrap(): Closure {
                            return fn(): int => ($this->cb)() + 1;
                        }
                    }

                    /** @psalm-mutation-free */
                    function useImpure(): int {
                        return (new Box(function (): int { echo "x"; return 1; }))->wrap()();
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'closurePurityMustBeAPurityType' => [
                'code' => '<?php
                    /**
                     * @param Closure[int](): void $f
                     */
                    function apply(Closure $f): void {
                        $f();
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'overrideOfTemplatedMethodMayNotRequireMore' => [
                'code' => '<?php
                    /** @psalm-purity-template C */
                    abstract class Doer {
                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template C
                         */
                        abstract public function doWork(): int;
                    }

                    /** @extends Doer[pure] */
                    final class PureDoer extends Doer {
                        /** @psalm-pure */
                        public function doWork(): int {
                            return 1;
                        }
                    }

                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P](): int $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): int {
                        return $f();
                    }

                    interface Applier {
                        /** @psalm-pure */
                        public function get(): int;
                    }

                    final class Impl implements Applier {
                        public function get(): int {
                            return apply(function (): int { echo "x"; return 1; });
                        }
                    }',
                'error_message' => 'ImmutableDependency',
            ],
            'purityTemplateBoundRejectsWiderClosure' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P <= read-globals
                     * @param Closure[P](): int $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): int {
                        return $f();
                    }

                    function useIo(): int {
                        return apply(function (): int {
                            echo "x";
                            return 1;
                        });
                    }',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'classPurityTemplateBoundRejectsWiderExtends' => [
                'code' => '<?php
                    /** @psalm-purity-template C <= write-props */
                    abstract class Doer {
                        /**
                         * @psalm-mutation-free
                         * @psalm-purity-from-template C
                         */
                        public function run(): int {
                            return 1;
                        }
                    }

                    /** @extends Doer[write-globals] */
                    final class GlobalDoer extends Doer {}',
                'error_message' => 'InvalidTemplateParam',
            ],
            'purityTemplateBoundMustBeCapabilities' => [
                'code' => '<?php
                    /**
                     * @psalm-purity-template P <= int
                     */
                    function f(): void {}',
                'error_message' => 'InvalidDocblock',
            ],
            'functionPurityTemplateCannotHaveDefault' => [
                'code' => '<?php
                    /**
                     * @psalm-purity-template P(pure)
                     */
                    function f(): void {}',
                'error_message' => 'MissingDocblockType',
            ],
            'classPurityTemplateDefaultMustFitBound' => [
                'code' => '<?php
                    /** @psalm-purity-template C(io) <= read-globals */
                    abstract class Doer {}',
                'error_message' => 'InvalidDocblock',
            ],
            'overrideWithFixedCapabilitiesBeyondDependentParent' => [
                'code' => '<?php
                    abstract class Base {
                        /**
                         * @psalm-pure
                         * @psalm-purity-template P
                         * @param Closure[P](): void $f
                         * @psalm-purity-from-template P
                         */
                        abstract public function run(Closure $f): int;
                    }

                    final class Box {
                        public int $x = 0;
                    }

                    final class Mutating extends Base {
                        /**
                         * @psalm-capabilities write-props
                         * @param Closure[impure](): void $f
                         */
                        public function run(Closure $f): int {
                            $b = new Box();
                            $b->x = 1;
                            return $b->x;
                        }
                    }',
                'error_message' => 'ImmutableDependency',
            ],
            'wildcardPurityPropagatesImpureClosure' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param Closure[_](): int $f
                     */
                    function apply(Closure $f): int {
                        return $f();
                    }

                    /** @psalm-pure */
                    function bad(): int {
                        return apply(function (): int {
                            echo "x";
                            return 1;
                        });
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'wildcardPurityOnlyInParams' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @return Closure[_](): int
                     */
                    function make(): Closure {
                        return fn(): int => 1;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'wildcardPurityNestedPropagatesImpureClosure' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param array<Closure[_](int): int> $fs
                     * @return list<int>
                     */
                    function runAll(array $fs): array {
                        $r = [];
                        foreach ($fs as $f) {
                            $r[] = $f(1);
                        }
                        return $r;
                    }

                    /**
                     * @psalm-pure
                     * @return list<int>
                     */
                    function bad(): array {
                        return runAll([function (int $x): int {
                            echo "x";
                            return $x;
                        }]);
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'wildcardPurityNestedInReturnType' => [
                'code' => '<?php
                    /** @return list<Closure[_](): int> */
                    function make(): array {
                        return [fn(): int => 1];
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'wildcardPurityInParamOut' => [
                'code' => '<?php
                    /**
                     * @param-out Closure[_](): int $f
                     */
                    function make(?Closure &$f): void {
                        $f = fn(): int => 1;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'wildcardPurityInAssertion' => [
                'code' => '<?php
                    /** @psalm-assert Closure[_](): int $f */
                    function assertClosure(?Closure $f): void {
                        if (!$f instanceof Closure) {
                            throw new InvalidArgumentException();
                        }
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'wildcardPurityInSelfOut' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @psalm-self-out Box<Closure[_](): int> */
                        public function set(): void {}
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'wildcardPurityOnGenericReturnType' => [
                'code' => '<?php
                    /** @return Traversable[_]<int, int> */
                    function make(): Traversable {
                        return new ArrayIterator([]);
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'wildcardPurityInMagicProperty' => [
                'code' => '<?php
                    /** @property Closure[_](): int $f */
                    final class Holder {
                        public function __get(string $name): ?int {
                            return null;
                        }
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'wildcardPurityInTemplateBound' => [
                'code' => '<?php
                    /**
                     * @template T of Closure[_](): int
                     * @param T $f
                     */
                    function apply(Closure $f): void {}',
                'error_message' => 'InvalidDocblock',
            ],
            'wildcardPurityKeepsSameNamedTemplateBound' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template _fs <= read-globals
                     * @param Closure[_fs](): int $g
                     * @param list<Closure[_](): int> $fs
                     * @psalm-purity-from-template _fs
                     */
                    function run(Closure $g, array $fs): int {
                        return $g() + count($fs);
                    }

                    function useIo(): int {
                        return run(function (): int {
                            echo "x";
                            return 1;
                        }, []);
                    }',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'wildcardPurityOnPromotedPropertyPropagatesImpureClosure' => [
                'code' => '<?php
                    /** @psalm-immutable */
                    final class Holder {
                        /**
                         * @psalm-pure
                         * @param Closure[_](): int $f
                         */
                        public function __construct(public Closure $f) {}
                    }

                    /** @psalm-pure */
                    function make(): Holder {
                        return new Holder(function (): int {
                            echo "x";
                            return 1;
                        });
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'wildcardPurityOnPromotedPropertyIsImpureWhenCalled' => [
                'code' => '<?php
                    /** @psalm-immutable */
                    final class Holder {
                        /**
                         * @psalm-pure
                         * @param Closure[_](): int $f
                         */
                        public function __construct(public Closure $f) {}
                    }

                    /** @psalm-pure */
                    function run(Holder $h): int {
                        return ($h->f)();
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'wildcardPurityInPropertyType' => [
                'code' => '<?php
                    final class Holder {
                        /** @var Closure[_](): int|null */
                        public ?Closure $f = null;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'classPurityTemplateLowerBoundRejectsSmallerExtends' => [
                'code' => '<?php
                    /** @psalm-purity-template write-props <= C */
                    abstract class Doer {
                        /**
                         * @psalm-mutation-free
                         * @psalm-purity-from-template C
                         */
                        abstract public function run(): int;
                    }

                    /** @extends Doer[pure] */
                    final class PureDoer extends Doer {
                        /** @psalm-pure */
                        public function run(): int {
                            return 1;
                        }
                    }',
                'error_message' => 'InvalidTemplateParam',
            ],
            'classPurityTemplateLowerBoundIsPaidByCallers' => [
                'code' => '<?php
                    /** @psalm-purity-template write-props <= C(write-props) */
                    abstract class Doer {
                        /**
                         * @psalm-mutation-free
                         * @psalm-purity-from-template C
                         */
                        abstract public function run(): int;
                    }

                    final class DefaultDoer extends Doer {
                        /** @psalm-capabilities write-props */
                        public function run(): int {
                            return 1;
                        }
                    }

                    /** @psalm-mutation-free */
                    function useDefault(DefaultDoer $d): int {
                        return $d->run();
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'classPurityTemplateLowerBoundMustFitUpperBound' => [
                'code' => '<?php
                    /** @psalm-purity-template write-props <= C <= read-globals */
                    abstract class Doer {}',
                'error_message' => 'InvalidDocblock',
            ],
            'classPurityTemplateDefaultMustFitLowerBound' => [
                'code' => '<?php
                    /** @psalm-purity-template write-props <= C(pure) */
                    abstract class Doer {}',
                'error_message' => 'InvalidDocblock',
            ],
            'functionPurityTemplateCannotHaveLowerBound' => [
                'code' => '<?php
                    /**
                     * @psalm-purity-template write-props <= P
                     */
                    function f(): void {}',
                'error_message' => 'MissingDocblockType',
            ],
            'classPurityTemplateCapabilityNameBeforeTemplateIsLowerBound' => [
                'code' => '<?php
                    /** @psalm-purity-template io <= C */
                    abstract class Doer {}

                    /** @extends Doer[pure] */
                    final class PureDoer extends Doer {}',
                'error_message' => 'InvalidTemplateParam',
            ],
            'purityTemplateRejectsKeywordBounds' => [
                'code' => '<?php
                    /**
                     * @psalm-purity-template P of io
                     */
                    function f(): void {}',
                'error_message' => 'MissingDocblockType',
            ],
            'builtinIteratorFunctionIteratesImpureIterator' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param Iterator<int, string> $it
                     */
                    function count_it(Iterator $it): int {
                        return iterator_count($it);
                    }',
                'error_message' => 'ImpureFunctionCall - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:32 - The context is pure but function call on iterator_count requires impure',
            ],
            'iteratorApplyWithImpureCallback' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param Iterator[pure]<int, string> $it
                     */
                    function apply(Iterator $it): int {
                        return iterator_apply($it, function (): bool { echo "x"; return true; });
                    }',
                'error_message' => 'function call on iterator_apply requires io',
            ],
            'pregReplaceCallbackArrayWithImpureCallback' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function replace(string $s): ?string {
                        return preg_replace_callback_array(
                            [
                                "/a/" => fn(array $m): string => "b",
                                "/c/" => function (array $m): string { echo "x"; return "d"; },
                            ],
                            $s,
                        );
                    }',
                'error_message' => 'function call on preg_replace_callback_array requires io',
            ],
            'arrayObjectSortWithImpureComparator' => [
                'code' => '<?php
                    /**
                     * @psalm-capabilities write-props
                     * @param ArrayObject<int, int> $o
                     */
                    function sortIt(ArrayObject $o): void {
                        $o->uasort(function (int $a, int $b): int { echo "x"; return $a <=> $b; });
                    }',
                'error_message' => 'method ArrayObject::uasort requires write-props|io',
            ],
            'closureRebindingKeepsImpurity' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function rebind(): int {
                        $c = (function (): int { echo "x"; return 1; })->bindTo(null);
                        return $c ? $c() : 0;
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'closureCallOfImpureClosure' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function run(): int {
                        $c = function (): int { echo "x"; return 1; };
                        return $c->call(new stdClass);
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'closureCallWritesTheNewThis' => [
                'code' => '<?php
                    final class Counter {
                        public int $n = 0;
                    }

                    final class A {
                        public int $n = 0;

                        /** @psalm-capabilities read-props|write-this-props */
                        public function reset(Counter $counter): void {
                            $c = function (): void { $this->n = 0; };
                            $c->call($counter);
                        }
                    }',
                'error_message' => 'method Closure::call requires write-props',
            ],
            'fiberWithImpureCallback' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function runFiber(): mixed {
                        $f = new Fiber(function (): int { echo "x"; return 1; });
                        $f->start();
                        return $f->getReturn();
                    }',
                'error_message' => 'method Fiber::start requires io',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'classPurityTemplateInTypeTemplateBoundIsInferred' => [
                'code' => '<?php
                    /**
                     * @template TKey
                     * @template TValue
                     * @template TIterator as Traversable[TPurity]<TKey, TValue>
                     * @psalm-purity-template TPurity(impure)
                     */
                    final class Wrapper {
                        /**
                         * @param TIterator $inner
                         * @psalm-pure
                         */
                        public function __construct(Traversable $inner) {}

                        /**
                         * @psalm-capabilities read-props
                         * @psalm-purity-from-template TPurity
                         */
                        public function count(): int {
                            return 1;
                        }
                    }

                    /**
                     * @psalm-pure
                     * @param Iterator<int, string> $it
                     */
                    function countIt(Iterator $it): int {
                        $w = new Wrapper($it);
                        return $w->count();
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'classPurityTemplateInTypeTemplateBoundChecksArguments' => [
                'code' => '<?php
                    /**
                     * @template TKey
                     * @template TValue
                     * @template TIterator as Traversable[TPurity]<TKey, TValue>
                     * @psalm-purity-template TPurity(impure)
                     */
                    final class Wrapper {}

                    /** @param Wrapper[pure]<int, int, Iterator[io]<int, int>> $w */
                    function f(Wrapper $w): void {}',
                'error_message' => 'InvalidTemplateParam - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:32 - Extended template param TIterator of Wrapper[pure]<int, int, Iterator[io]<int, int>> expects type Traversable[pure]<int, int>, type Iterator[io]<int, int> given',
            ],
            'classPurityTemplateInTypeTemplateBoundChecksExtends' => [
                'code' => '<?php
                    /**
                     * @template TKey
                     * @template TValue
                     * @template TIterator as Traversable[TPurity]<TKey, TValue>
                     * @psalm-purity-template TPurity(impure)
                     */
                    abstract class Wrapper {}

                    /** @extends Wrapper[pure]<int, int, Iterator[io]<int, int>> */
                    final class Bad extends Wrapper {}',
                'error_message' => 'Extended template param TIterator expects type Traversable[pure]<int, int>, type Iterator[io]<int, int> given',
            ],
            'forwardedClassPurityTemplateOverrideCannotDoMore' => [
                'code' => '<?php
                    /** @psalm-purity-template P */
                    class Filter {
                        /**
                         * @param Closure[P](): bool $cb
                         * @psalm-pure
                         */
                        public function __construct(private Closure $cb) {}

                        /**
                         * @psalm-capabilities read-props
                         * @psalm-purity-from-template P
                         */
                        public function accept(): bool {
                            return ($this->cb)();
                        }
                    }

                    /**
                     * @psalm-purity-template P
                     * @extends Filter[P]
                     */
                    final class Noisy extends Filter {
                        /** @psalm-capabilities io */
                        #[Override]
                        public function accept(): bool {
                            echo "x";
                            return true;
                        }
                    }',
                'error_message' => 'ImmutableDependency - src' . DIRECTORY_SEPARATOR . 'somefile.php:25:25 - Filter::accept is read-props, but Noisy::accept additionally requires io',
            ],
            'nativeStaticMethodCallIsCharged' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function locale(): string {
                        return Locale::getDefault();
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'firstClassCallableOfPurityPolymorphicFunctionIsImpure' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure[P](int): int $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): int {
                        return $f(1);
                    }

                    /** @psalm-pure */
                    function viaCallable(): int {
                        $apply = apply(...);
                        return $apply(function (int $x): int { echo "x"; return $x; });
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'invokableObjectCallableCarriesItsBoundPurity' => [
                'code' => '<?php
                    /** @psalm-purity-template P */
                    final class Task {
                        /**
                         * @param Closure[P](): int $f
                         * @psalm-pure
                         */
                        public function __construct(private Closure $f) {}

                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P
                         */
                        public function __invoke(): int {
                            return 1;
                        }
                    }

                    /**
                     * @psalm-pure
                     * @param pure-callable(): int $c
                     */
                    function callIt(callable $c): int {
                        return $c();
                    }

                    /**
                     * @psalm-pure
                     * @param Task[io] $t
                     */
                    function run(Task $t): int {
                        return callIt($t);
                    }',
                'error_message' => 'callable[io]():int provided',
            ],
            'callbackFilterIteratorWithImpureCallback' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @return Generator<int, int, mixed, int>
                     */
                    function gen(): Generator {
                        yield 1;
                        return 1;
                    }

                    /** @psalm-pure */
                    function countAll(): int {
                        $it = new CallbackFilterIterator(gen(), function (int $v): bool { echo $v; return true; });
                        return iterator_count($it);
                    }',
                'error_message' => 'function call on iterator_count requires io',
            ],
            'userFilterIteratorWithoutPurityIsImpure' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @return Generator<int, int, mixed, int>
                     */
                    function gen(): Generator {
                        yield 1;
                        return 1;
                    }

                    /** @extends FilterIterator<int, int, Iterator<int, int>> */
                    final class Noisy extends FilterIterator {
                        #[Override]
                        public function accept(): bool {
                            echo "x";
                            return true;
                        }
                    }

                    /** @psalm-pure */
                    function countAll(): int {
                        $n = 0;
                        foreach (new Noisy(gen()) as $_) {
                            $n++;
                        }
                        return $n;
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'widenedIteratorMustKeepItsPurity' => [
                'code' => '<?php
                    /**
                     * @template TKey
                     * @template TValue
                     * @template TIterator as Traversable[TPurity]<TKey, TValue>
                     * @psalm-purity-template TPurity(impure)
                     */
                    final class Wrapper {
                        /**
                         * @param TIterator $inner
                         * @psalm-pure
                         */
                        public function __construct(public Traversable $inner) {}
                    }

                    /**
                     * @psalm-pure
                     * @return Generator<int, string, mixed, int>
                     */
                    function gen(): Generator {
                        yield 1 => "a";
                        return 1;
                    }

                    /** @param Iterator<int, string> $it */
                    function swap(Iterator $it): void {
                        $w = new Wrapper(gen());
                        $w->inner = $it;
                    }',
                'error_message' => 'IncompatibleTypeParameters',
            ],
            'purityTemplateInferredFromAClosureAndItsReturnMakesCallImpure' => [
                'code' => '<?php
                    /**
                     * @template-covariant T
                     * @psalm-purity-template P
                     */
                    final class Box {
                        /** @psalm-pure */
                        public function __construct() {}
                    }

                    /**
                     * @psalm-pure
                     * @return Box[pure]<int>
                     */
                    function pureBox(): Box { throw new RuntimeException(); }

                    /**
                     * @psalm-purity-template Q
                     * @param Closure[Q](): Box[Q]<mixed> $a
                     * @return Box[Q]<mixed>
                     * @psalm-pure
                     * @psalm-purity-from-template Q
                     */
                    function fromClosure(Closure $a): Box { throw new RuntimeException(); }

                    /** @psalm-pure */
                    function usePure(): int {
                        fromClosure(static function (): Box { echo 1; return pureBox(); });
                        return 1;
                    }',
                'error_message' => 'function call on fromclosure requires io',
            ],
            'typeTemplateInferredFromSeveralDepthsMakesCallImpure' => [
                'code' => '<?php
                    /**
                     * @template T of Closure(): int
                     * @param array{0: T, 1: list<T>} $a
                     * @return T
                     * @psalm-pure
                     * @psalm-purity-from-template T
                     */
                    function pick(array $a): Closure { throw new RuntimeException(); }

                    /** @psalm-pure */
                    function usePure(): int {
                        pick([static function (): int { echo 1; return 1; }, [static fn(): int => 1]]);
                        return 1;
                    }',
                'error_message' => 'function call on pick requires io',
            ],
            'subclassWithMorePurityDoesNotFitParentWithOmittedTypeArguments' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @psalm-purity-template P
                     */
                    interface Driver {
                        /**
                         * @return T
                         * @psalm-capabilities read-props
                         * @psalm-purity-from-template P
                         */
                        public function consume();
                    }

                    /** @implements Driver[io]<int> */
                    final class EchoDriver implements Driver {
                        /** @psalm-capabilities io */
                        #[Override]
                        public function consume(): int {
                            echo "consumed";
                            return 1;
                        }
                    }

                    /** @param Driver[pure] $_driver */
                    function takePure(Driver $_driver): void {}

                    takePure(new EchoDriver());',
                'error_message' => 'InvalidArgument',
            ],
            'constructorDependingOnClassPurityTemplateChargesWhatTheSubclassBinds' => [
                'code' => '<?php
                    /**
                     * @psalm-consistent-constructor
                     * @psalm-purity-template C
                     */
                    abstract class Base {
                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template C
                         */
                        public function __construct() {}
                    }

                    /** @extends Base[io] */
                    final class IoBase extends Base {}

                    /** @psalm-pure */
                    function make(): IoBase {
                        return new IoBase();
                    }',
                'error_message' => 'constructor Base::__construct requires io',
            ],
            'constructorDependingOnClassPurityTemplateOfUnboundSubclassChargesItsBound' => [
                'code' => '<?php
                    /**
                     * @psalm-consistent-constructor
                     * @psalm-purity-template C
                     */
                    abstract class Base {
                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template C
                         */
                        public function __construct() {}
                    }

                    final class AnyBase extends Base {}

                    /** @psalm-pure */
                    function make(): AnyBase {
                        return new AnyBase();
                    }',
                'error_message' => 'constructor Base::__construct requires impure',
            ],
            'newStaticChargesTheClassPurityTemplateOfTheConstructor' => [
                'code' => '<?php
                    /**
                     * @psalm-consistent-constructor
                     * @psalm-consistent-templates
                     * @psalm-purity-template C
                     */
                    abstract class Base {
                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template C
                         */
                        public function __construct() {}

                        /** @psalm-pure */
                        public static function make(): static {
                            return new static();
                        }
                    }
',
                'error_message' => 'constructor Base::__construct requires impure',
            ],
            'constructorReturnTypeOfAnObjectArgument' => [
                'code' => '<?php
                    /**
                     * @template TValue
                     * @psalm-purity-template TStorage(write-props) <= write-props
                     * @psalm-capabilities read-props|write-this-props
                     */
                    final class Store {
                        private int $writes = 0;

                        /**
                         * @param array<TValue>|object $storage
                         * @return ($storage is array ? Store[pure]<TValue> : Store[write-props]<TValue>)
                         * @psalm-pure
                         */
                        public function __construct($storage = []) {}

                        /**
                         * @param TValue $value
                         * @psalm-purity-from-template TStorage
                         */
                        public function add($value): void {
                            $this->writes++;
                        }
                    }

                    /** @psalm-pure */
                    function objectBacked(stdClass $object): int {
                        $store = new Store($object);
                        $store->add(1);
                        return 1;
                    }',
                'error_message' => 'ImpureMethodCall - src' . DIRECTORY_SEPARATOR . 'somefile.php:29:25 - The context is pure but method Store::add requires write-props',
            ],
            'constructorReturnTypeOfAnArgumentOfUnknownKind' => [
                'code' => '<?php
                    /**
                     * @template TValue
                     * @psalm-purity-template TStorage(write-props) <= write-props
                     * @psalm-capabilities read-props|write-this-props
                     */
                    final class Store {
                        private int $writes = 0;

                        /**
                         * @param array<TValue>|object $storage
                         * @return ($storage is array ? Store[pure]<TValue> : Store[write-props]<TValue>)
                         * @psalm-pure
                         */
                        public function __construct($storage = []) {}

                        /**
                         * @param TValue $value
                         * @psalm-purity-from-template TStorage
                         */
                        public function add($value): void {
                            $this->writes++;
                        }
                    }

                    /**
                     * @psalm-pure
                     * @param array<int>|stdClass $storage
                     */
                    function unknownBacked($storage): int {
                        $store = new Store($storage);
                        $store->add(1);
                        return 1;
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'newStaticFromANonFinalClassGetsThePlainType' => [
                'code' => '<?php
                    /**
                     * @template TValue
                     * @psalm-purity-template TStorage(write-props) <= write-props
                     * @psalm-capabilities read-props|write-this-props
                     * @psalm-consistent-constructor
                     */
                    class Store {
                        private int $writes = 0;

                        /**
                         * @param array<TValue>|object $storage
                         * @return ($storage is array ? Store[pure]<TValue> : Store[write-props]<TValue>)
                         * @psalm-pure
                         */
                        public function __construct($storage = []) {}

                        /**
                         * @param TValue $value
                         * @psalm-purity-from-template TStorage
                         */
                        public function add($value): void {
                            $this->writes++;
                        }

                        /** @psalm-pure */
                        public static function make(): int {
                            $store = new static([1]);
                            $store->add(2);
                            return 1;
                        }
                    }
',
                'error_message' => 'ImpureMethodCall - src' . DIRECTORY_SEPARATOR . 'somefile.php:29:29 - The context is pure but method Store::add requires write-props',
            ],
            'constructorReturnTypeNamingAnotherClass' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {}

                    final class Maker {
                        /** @return Box<int> */
                        public function __construct() {}
                    }',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:25 - The @return of Maker::__construct must be the type of the object it constructs, Maker',
            ],
            'conditionalPurityArgumentChargesTheBindingOfTheArgument' => [
                'code' => '<?php
                    /**
                     * @template K of array-key
                     * @template V
                     * @psalm-purity-template P
                     */
                    final class Container {}

                    /**
                     * @psalm-purity-template P
                     * @template K of array-key
                     * @template V
                     */
                    final class Wrapper {
                        /**
                         * @psalm-purity-template PP
                         * @param array<K, V>|Container[PP]<K, V> $param
                         * @return self[$param is array ? pure : PP]<K, V>
                         * @psalm-pure
                         */
                        public function __construct($param) {}

                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P
                         */
                        public function run(): int {
                            return 1;
                        }
                    }

                    /**
                     * @psalm-pure
                     * @param Container[io]<string, int> $container
                     */
                    function viaContainer(Container $container): int {
                        $wrapper = new Wrapper($container);
                        return $wrapper->run();
                    }',
                'error_message' => 'ImpureMethodCall - src' . DIRECTORY_SEPARATOR . 'somefile.php:38:32 - The context is pure but method Wrapper::run requires io',
            ],
            'conditionalConstructorReturnTypeChargesTheBindingOfTheArgument' => [
                'code' => '<?php
                    /**
                     * @template K of array-key
                     * @template V
                     * @psalm-purity-template P
                     */
                    final class Container {}

                    /**
                     * @psalm-purity-template P
                     * @template K of array-key
                     * @template V
                     */
                    final class Wrapper {
                        /**
                         * @psalm-purity-template PP
                         * @param array<K, V>|Container[PP]<K, V> $param
                         * @return ($param is array ? self[pure]<K, V> : self[PP]<string, V>)
                         * @psalm-pure
                         */
                        public function __construct($param) {}

                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P
                         */
                        public function run(): int {
                            return 1;
                        }
                    }

                    /**
                     * @psalm-pure
                     * @param Container[io]<string, int> $container
                     */
                    function viaContainer(Container $container): int {
                        $wrapper = new Wrapper($container);
                        return $wrapper->run();
                    }',
                'error_message' => 'The context is pure but method Wrapper::run requires io',
            ],
            'conditionalPurityArgumentsChargeTheSecondPurityTemplate' => [
                'code' => '<?php
                    /**
                     * @template K of array-key
                     * @template V
                     * @psalm-purity-template P
                     */
                    final class Container {}

                    /**
                     * @template T
                     * @psalm-purity-template P1
                     * @psalm-purity-template P2
                     */
                    final class Pair {
                        /**
                         * @psalm-purity-template PA
                         * @psalm-purity-template PB
                         * @param list<T>|Container[PA]<int, T> $first
                         * @param list<T>|Container[PB]<int, T> $second
                         * @return self[$first is array ? pure : PA, $second is array ? pure : PB]<T>
                         * @psalm-pure
                         */
                        public function __construct($first, $second) {}

                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P1
                         */
                        public function runFirst(): int {
                            return 1;
                        }

                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P2
                         */
                        public function runSecond(): int {
                            return 2;
                        }
                    }

                    /**
                     * @psalm-pure
                     * @param Container[io]<int, int> $container
                     */
                    function viaSecondContainer(Container $container): int {
                        $pair = new Pair([1], $container);
                        return $pair->runSecond();
                    }',
                'error_message' => 'The context is pure but method Pair::runSecond requires io',
            ],
            'conditionalPurityArgumentsChargeTheFirstPurityTemplate' => [
                'code' => '<?php
                    /**
                     * @template K of array-key
                     * @template V
                     * @psalm-purity-template P
                     */
                    final class Container {}

                    /**
                     * @template T
                     * @psalm-purity-template P1
                     * @psalm-purity-template P2
                     */
                    final class Pair {
                        /**
                         * @psalm-purity-template PA
                         * @psalm-purity-template PB
                         * @param list<T>|Container[PA]<int, T> $first
                         * @param list<T>|Container[PB]<int, T> $second
                         * @return self[$first is array ? pure : PA, $second is array ? pure : PB]<T>
                         * @psalm-pure
                         */
                        public function __construct($first, $second) {}

                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P1
                         */
                        public function runFirst(): int {
                            return 1;
                        }

                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P2
                         */
                        public function runSecond(): int {
                            return 2;
                        }
                    }

                    /**
                     * @psalm-pure
                     * @param Container[write-props]<int, int> $first
                     * @param Container[io]<int, int> $second
                     */
                    function viaFirstContainer(Container $first, Container $second): int {
                        $pair = new Pair($first, $second);
                        return $pair->runFirst();
                    }',
                'error_message' => 'The context is pure but method Pair::runFirst requires write-props',
            ],
            'conditionalPurityArgumentOfAnArgumentOfEitherKindChargesTheBinding' => [
                'code' => '<?php
                    /**
                     * @template K of array-key
                     * @template V
                     * @psalm-purity-template P
                     */
                    final class Container {}

                    /**
                     * @psalm-purity-template P
                     * @template K of array-key
                     * @template V
                     */
                    final class Wrapper {
                        /**
                         * @psalm-purity-template PP
                         * @param array<K, V>|Container[PP]<K, V> $param
                         * @return self[$param is array ? pure : PP]<K, V>
                         * @psalm-pure
                         */
                        public function __construct($param) {}

                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P
                         */
                        public function run(): int {
                            return 1;
                        }
                    }

                    /**
                     * @psalm-pure
                     * @param array<string, int>|Container[io]<string, int> $either
                     */
                    function viaEither($either): int {
                        $wrapper = new Wrapper($either);
                        return $wrapper->run();
                    }',
                'error_message' => 'The context is pure but method Wrapper::run requires io',
            ],
            'conditionalPurityArgumentOfAnUnboundArgumentChargesTheDefault' => [
                'code' => '<?php
                    /**
                     * @template K of array-key
                     * @template V
                     * @psalm-purity-template P(write-props) <= write-props|io
                     */
                    final class Container {}

                    /**
                     * @psalm-purity-template P(io) <= write-props|io
                     * @template K of array-key
                     * @template V
                     */
                    final class Wrapper {
                        /**
                         * @psalm-purity-template PP <= write-props|io
                         * @param array<K, V>|Container[PP]<K, V> $param
                         * @return self[$param is array ? pure : PP]<K, V>
                         * @psalm-pure
                         */
                        public function __construct($param) {}

                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P
                         */
                        public function run(): int {
                            return 1;
                        }
                    }

                    /**
                     * @psalm-pure
                     * @param Container<string, int> $container
                     */
                    function viaDefault(Container $container): int {
                        $wrapper = new Wrapper($container);
                        return $wrapper->run();
                    }',
                'error_message' => 'The context is pure but method Wrapper::run requires write-props',
            ],
            'conditionalPurityArgumentOfASelfOutTypeChargesTheBindingOfTheArgument' => [
                'code' => '<?php
                    /** @psalm-purity-template P */
                    final class Container {}

                    /**
                     * @template T
                     * @psalm-purity-template P
                     */
                    final class Box {
                        /**
                         * @psalm-purity-template PP
                         * @param list<T>|Container[PP] $storage
                         * @psalm-self-out self[$storage is array ? pure : PP]<T>
                         * @psalm-mutation-free
                         */
                        public function setContainer($storage): void {}

                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P
                         */
                        public function run(): int {
                            return 1;
                        }
                    }

                    /**
                     * @psalm-pure
                     * @param Box[pure]<int> $box
                     * @param Container[io] $container
                     */
                    function viaContainer(Box $box, Container $container): int {
                        $box->setContainer($container);
                        return $box->run();
                    }',
                'error_message' => 'The context is pure but method Box::run requires io',
            ],
            'conditionalPurityArgumentOfAMethodReturnTypeChargesTheElseBranch' => [
                'code' => '<?php
                    /** @psalm-purity-template P */
                    final class Container {}

                    /**
                     * @template T
                     * @psalm-purity-template P
                     */
                    final class Box {
                        /**
                         * @param list<T>|Container $storage
                         * @return static[$storage is array ? pure : P]<T>
                         * @psalm-mutation-free
                         */
                        public function withStorage($storage): static {
                            throw new RuntimeException();
                        }

                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P
                         */
                        public function run(): int {
                            return 1;
                        }
                    }

                    /**
                     * @psalm-pure
                     * @param Box[io]<int> $box
                     */
                    function viaObject(Box $box): int {
                        return $box->withStorage(new Container())->run();
                    }',
                'error_message' => 'The context is pure but method Box::run requires io',
            ],
            'conditionalPurityArgumentOfAStaticFactoryReturnTypeChargesTheElseBranch' => [
                'code' => '<?php
                    /** @psalm-purity-template P */
                    final class Container {}

                    /**
                     * @template T
                     * @psalm-purity-template P
                     */
                    final class Box {
                        /**
                         * @param list<T>|Container $storage
                         * @return self[$storage is array ? pure : write-props]<T>
                         * @psalm-pure
                         */
                        public static function from($storage): self {
                            throw new RuntimeException();
                        }

                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P
                         */
                        public function run(): int {
                            return 1;
                        }
                    }

                    /** @psalm-pure */
                    function viaObject(): int {
                        return Box::from(new Container())->run();
                    }',
                'error_message' => 'The context is pure but method Box::run requires write-props',
            ],
            'conditionalPurityArgumentOfAFunctionReturnTypeChargesTheElseBranch' => [
                'code' => '<?php
                    /** @psalm-purity-template P */
                    final class Container {}

                    /**
                     * @template T
                     * @psalm-purity-template P
                     */
                    final class Box {
                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P
                         */
                        public function run(): int {
                            return 1;
                        }
                    }

                    /**
                     * @param list<int>|Container $storage
                     * @return Box[$storage is array ? pure : write-props]<int>
                     * @psalm-pure
                     */
                    function box($storage): Box {
                        throw new RuntimeException();
                    }

                    /** @psalm-pure */
                    function viaObject(): int {
                        return box(new Container())->run();
                    }',
                'error_message' => 'The context is pure but method Box::run requires write-props',
            ],
            'conditionalPurityArgumentNestedInAReturnTypeChargesTheElseBranch' => [
                'code' => '<?php
                    /** @psalm-purity-template P */
                    final class Container {}

                    /**
                     * @template T
                     * @psalm-purity-template P
                     */
                    final class Box {
                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template P
                         */
                        public function run(): int {
                            return 1;
                        }
                    }

                    /**
                     * @param list<int>|Container $storage
                     * @return list<Box[$storage is array ? pure : io]<int>>
                     * @psalm-pure
                     */
                    function boxes($storage): array {
                        throw new RuntimeException();
                    }

                    /** @psalm-pure */
                    function viaObject(): int {
                        return boxes(new Container())[0]->run();
                    }',
                'error_message' => 'The context is pure but method Box::run requires io',
            ],
            'conditionalPurityArgumentOfASelfOutTypeOutsideTheBound' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @psalm-purity-template P <= read-props
                     */
                    final class Box {
                        /**
                         * @param list<T>|object $storage
                         * @psalm-self-out self[$storage is array ? pure : io]<T>
                         * @psalm-mutation-free
                         */
                        public function setStorage($storage): void {}
                    }',
                'error_message' => 'InvalidTemplateParam - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:28 - Extended template param P of Box[io]<T:Box as mixed> expects type read-props, type io given',
            ],
            'purityArgumentOfASelfOutTypeOutsideTheBound' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @psalm-purity-template P <= read-props
                     */
                    final class Box {
                        /**
                         * @psalm-self-out self[io]<T>
                         * @psalm-mutation-free
                         */
                        public function open(): void {}
                    }',
                'error_message' => 'InvalidTemplateParam - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:28 - Extended template param P of Box[io]<T:Box as mixed> expects type read-props, type io given',
            ],
            'conditionalPurityArgumentOfAFunctionReturnTypeOutsideTheBound' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @psalm-purity-template P <= read-props
                     */
                    final class Box {}

                    /**
                     * @param list<int>|object $storage
                     * @return Box[$storage is array ? pure : io]<int>
                     * @psalm-pure
                     */
                    function box($storage): Box {
                        throw new RuntimeException();
                    }',
                'error_message' => 'InvalidTemplateParam',
            ],
            'conditionalPurityArgumentOfASelfOutTypeOnAnUnknownParam' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @psalm-purity-template P
                     */
                    final class Box {
                        /**
                         * @param list<T>|object $storage
                         * @psalm-self-out self[$other is array ? pure : io]<T>
                         * @psalm-mutation-free
                         */
                        public function setStorage($storage): void {}
                    }',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:12:25 - Unrecognized template \'$other\' in docblock for Box::setStorage',
            ],
            'conditionalPurityArgumentOfAFunctionReturnTypeOnAnUnknownParam' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @psalm-purity-template P
                     */
                    final class Box {}

                    /**
                     * @param list<int>|object $storage
                     * @return Box[$other is array ? pure : io]<int>
                     * @psalm-pure
                     */
                    function box($storage): Box {
                        throw new RuntimeException();
                    }',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:21 - Unrecognized template \'$other\' in docblock for box',
            ],
        ];
    }
}
