<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class ClassTemplateContravarianceTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    private const SINK = '<?php
        /** @template-contravariant T */
        interface Sink {
            /** @param T $value */
            public function put(mixed $value): void;
        }
        /** @template-covariant T */
        interface Source {
            /** @return T */
            public function get(): mixed;
        }
        /** @template T */
        interface Inv {
            /** @param T $value */
            public function put(mixed $value): void;
        }
        ';

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'widerSinkFitsNarrowerSink' => [
                'code' => self::SINK . '
                    /** @param Sink<int> $sink */
                    function takesIntSink(Sink $sink): void {}

                    /** @param Sink<int|string> $wide */
                    function f(Sink $wide): void {
                        takesIntSink($wide);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'psalmAndPhpstanTagForms' => [
                'code' => '<?php
                    /** @psalm-template-contravariant T */
                    interface A {
                        /** @param T $value */
                        public function put(mixed $value): void;
                    }
                    /** @phpstan-template-contravariant T */
                    interface B {
                        /** @param T $value */
                        public function put(mixed $value): void;
                    }
                    /** @param A<int> $a */
                    function takesA(A $a): void {}
                    /** @param B<int> $b */
                    function takesB(B $b): void {}
                    /**
                     * @param A<int|string> $a
                     * @param B<int|string> $b
                     */
                    function f(A $a, B $b): void {
                        takesA($a);
                        takesB($b);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'contravariantTemplateInInputPositions' => [
                'code' => self::SINK . '
                    /** @template-contravariant T */
                    interface Positions {
                        /** @return Sink<T> */
                        public function sink(): Sink;
                        /** @return Closure(T): void */
                        public function consumer(): Closure;
                        /** @param Closure(): T $f */
                        public function produce(Closure $f): void;
                        /** @param Source<T> $source */
                        public function drain(Source $source): void;
                        /** @param Sink<Sink<T>> $sink */
                        public function nested(Sink $sink): void;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'contravariantTemplateInPropertiesOnlyTheClassReads' => [
                'code' => self::SINK . '
                    /** @template-contravariant T */
                    final class Comparator {
                        /** @var Closure(T, T): int */
                        public readonly Closure $compare;
                        /** @var T */
                        private mixed $last;
                        /**
                         * @param Closure(T, T): int $compare
                         * @param T $first
                         */
                        public function __construct(Closure $compare, mixed $first) {
                            $this->compare = $compare;
                            $this->last = $first;
                        }
                        /** @param T $value */
                        public function isAfterLast(mixed $value): bool {
                            $after = ($this->compare)($value, $this->last) > 0;
                            $this->last = $value;
                            return $after;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'extendContravariantWithContravariantOrInvariant' => [
                'code' => self::SINK . '
                    /**
                     * @template-contravariant T
                     * @extends Sink<T>
                     */
                    interface ContravariantChild extends Sink {}
                    /**
                     * @template T
                     * @extends Sink<T>
                     */
                    interface InvariantChild extends Sink {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'newInfersTheMostGeneralInstantiation' => [
                'code' => self::SINK . '
                    /**
                     * @template-contravariant T
                     * @implements Sink<T>
                     */
                    final class NullSink implements Sink {
                        public function put(mixed $value): void {}
                    }
                    /**
                     * @template-contravariant T of Countable
                     * @implements Sink<T>
                     */
                    final class CountableSink implements Sink {
                        public function put(mixed $value): void {}
                    }
                    /** @template-contravariant T */
                    final class Comparator {
                        /** @param Closure(T, T): int $compare */
                        public function __construct(private Closure $compare) {}
                        /**
                         * @param T $a
                         * @param T $b
                         */
                        public function compare(mixed $a, mixed $b): int {
                            return ($this->compare)($a, $b);
                        }
                    }
                    /** @return NullSink<int> */
                    function intSink(): NullSink {
                        return new NullSink();
                    }
                    $sink = new NullSink();
                    $sink->put(1);
                    $sink->put("a");
                    $countable = new CountableSink();
                    $comparator = new Comparator(fn(int $a, int $b): int => $a <=> $b);',
                'assertions' => [
                    '$sink===' => 'NullSink<mixed>',
                    '$countable===' => 'CountableSink<Countable>',
                    '$comparator===' => 'Comparator<int>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'functionTemplateThroughContravariantParam' => [
                'code' => self::SINK . '
                    /**
                     * @template U
                     * @param Sink<U> $sink
                     * @return U|null
                     */
                    function drain(Sink $sink): mixed { return null; }
                    /**
                     * @template U
                     * @param Sink<U> $sink
                     * @param U $value
                     * @return U
                     */
                    function feed(Sink $sink, mixed $value): mixed {
                        $sink->put($value);
                        return $value;
                    }
                    /**
                     * @param Sink<int> $int
                     * @param Sink<int|string> $wide
                     */
                    function f(Sink $int, Sink $wide): void {
                        $drained = drain($int);
                        /** @psalm-check-type-exact $drained = int|null */
                        $fed = feed($wide, 1);
                        /** @psalm-check-type-exact $fed = 1 */
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjection' => [
                'code' => self::SINK . '
                    /** @param Sink<*> $sink */
                    function takesAnySink(Sink $sink): void {}
                    /** @param Sink<never> $sink */
                    function takesSinkOfNothing(Sink $sink): void {}
                    /**
                     * @param Sink<int> $int
                     * @param Sink<*> $any
                     */
                    function f(Sink $int, Sink $any): void {
                        takesAnySink($int);
                        takesSinkOfNothing($int);
                        takesSinkOfNothing($any);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'typeVariableInContravariantParamTakesLowerBound' => [
                'code' => self::SINK . '
                    /** @template T */
                    final class Coll {
                        /** @var list<T> */
                        private array $items = [];
                        /** @param T $item */
                        public function add(mixed $item): void { $this->items[] = $item; }
                        /** @return list<T> */
                        public function all(): array { return $this->items; }
                    }
                    /**
                     * @template T
                     * @param Coll<T> $coll
                     * @return Sink<T>
                     */
                    function sinkFor(Coll $coll): Sink { throw new Exception(); }
                    /** @param Sink<int> $sink */
                    function takesIntSink(Sink $sink): void {}

                    function f(): void {
                        $coll = new Coll();
                        takesIntSink(sinkFor($coll));
                        $coll->add("a");
                        foreach ($coll->all() as $item) {
                            /** @psalm-check-type-exact $item = int|string */
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'twoContravariantArgsBindTheNarrowerType' => [
                'code' => self::SINK . '
                    /**
                     * @template U
                     * @param Sink<U> $a
                     * @param Sink<U> $b
                     * @return U|null
                     */
                    function both(Sink $a, Sink $b): mixed { return null; }
                    /**
                     * @param Sink<int> $int
                     * @param Sink<int|string> $wide
                     */
                    function f(Sink $int, Sink $wide): void {
                        $x = both($int, $wide);
                        /** @psalm-check-type-exact $x = int|null */
                        $y = both($wide, $int);
                        /** @psalm-check-type-exact $y = int|null */
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'contravariantTemplateInTheBoundOfAMethodTemplate' => [
                'code' => self::SINK . '
                    /** @template-contravariant T */
                    interface C {
                        /**
                         * @template U of T
                         * @param U $value
                         * @return U
                         */
                        public function same(mixed $value): mixed;
                        /**
                         * @template U of T
                         * @return Closure(U): U
                         */
                        public function identity(): Closure;
                        /**
                         * @template U of Source<T>
                         * @param U $source
                         * @return U
                         */
                        public function pass(Source $source): Source;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'covariantTemplateInContravariantArgs' => [
                'code' => self::SINK . '
                    /** @template-covariant T */
                    interface C {
                        /** @param Sink<T> $sink */
                        public function drainTo(Sink $sink): void;
                        /** @return Sink<Sink<T>> */
                        public function sinks(): Sink;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'contravariantTemplateInPropertiesOnlyTheClassWrites' => [
                'code' => '<?php
                    /** @template-contravariant T */
                    final class C {
                        /** @var Closure(T): void */
                        public private(set) Closure $set;
                        /**
                         * @var Closure(T): void
                         * @psalm-readonly
                         */
                        public Closure $readonly;
                        /** @param Closure(T): void $consumer */
                        public function __construct(Closure $consumer) {
                            $this->set = $consumer;
                            $this->readonly = $consumer;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'newThroughASinkArgOfAnInvariantTemplateTakesWhatTheSinkTakes' => [
                'code' => self::SINK . '
                    /** @template T */
                    final class Holder {
                        /** @param Sink<T> $sink */
                        public function __construct(private Sink $sink) {}
                        /** @param T $value */
                        public function give(mixed $value): void { $this->sink->put($value); }
                    }
                    /** @param Sink<int> $ints */
                    function f(Sink $ints): void {
                        $holder = new Holder($ints);
                        $holder->give(1);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'newWithAValueOrClassStringArgTakesTheBound' => [
                'code' => '<?php
                    final class Dog {}
                    /** @template-contravariant T of object */
                    final class Handler {
                        /** @param class-string<T> $class */
                        public function __construct(private string $class) {}
                        /** @param T $value */
                        public function handle(object $value): void {}
                    }
                    /** @template-contravariant T */
                    final class Logger {
                        /** @param T $first */
                        public function __construct(mixed $first) {}
                        /** @param T $value */
                        public function log(mixed $value): void {}
                    }
                    $handler = new Handler(Dog::class);
                    $logger = new Logger(1);',
                'assertions' => [
                    '$handler===' => 'Handler<object>',
                    '$logger===' => 'Logger<mixed>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'newWithCovariantAndContravariantTemplates' => [
                'code' => '<?php
                    /**
                     * @template-covariant A
                     * @template-contravariant B
                     */
                    final class Pipe {
                        /**
                         * @param A $value
                         * @param Closure(B): void $consumer
                         */
                        public function __construct(public readonly mixed $value, private Closure $consumer) {}
                        /** @param B $value */
                        public function put(mixed $value): void { ($this->consumer)($value); }
                    }
                    $pipe = new Pipe(1, function (string $s): void {});',
                'assertions' => [
                    '$pipe===' => 'Pipe<1, string>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
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
            'narrowerSinkDoesNotFitWiderSink' => [
                'code' => self::SINK . '
                    /** @param Sink<int|string> $sink */
                    function takesWideSink(Sink $sink): void {}

                    /** @param Sink<int> $narrow */
                    function f(Sink $narrow): void {
                        takesWideSink($narrow);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:23:39'
                    . ' - Argument 1 of takesWideSink expects Sink<int|string>, but Sink<int> provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'preventContravariantReturn' => [
                'code' => '<?php
                    /** @template-contravariant T */
                    interface BadSource {
                        /** @return T */
                        public function get(): mixed;
                    }',
                'error_message' => 'InvalidTemplateParam - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:37'
                    . ' - Template param T of BadSource is marked contravariant and cannot be used here',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'preventContravariantTemplateInACovariantParamOfReturn' => [
                'code' => self::SINK . '
                    /** @template-contravariant T */
                    interface C {
                        /** @return Source<T> */
                        public function source(): Source;
                    }',
                'error_message' => 'Template param T of C is marked contravariant',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'preventContravariantTemplateInAContravariantParamOfParam' => [
                'code' => self::SINK . '
                    /** @template-contravariant T */
                    interface C {
                        /** @param Sink<T> $sink */
                        public function to(Sink $sink): void;
                    }',
                'error_message' => 'Template param T of C is marked contravariant',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'preventContravariantTemplateInNestedContravariantParamsOfReturn' => [
                'code' => self::SINK . '
                    /** @template-contravariant T */
                    interface C {
                        /** @return Sink<Sink<T>> */
                        public function sinks(): Sink;
                    }',
                'error_message' => 'Template param T of C is marked contravariant',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'preventContravariantTemplateInAnInvariantParam' => [
                'code' => self::SINK . '
                    /** @template-contravariant T */
                    interface C {
                        /** @param Inv<T> $inv */
                        public function to(Inv $inv): void;
                    }',
                'error_message' => 'Template param T of C is marked contravariant',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'preventContravariantTemplateInCallableParamOfParam' => [
                'code' => '<?php
                    /** @template-contravariant T */
                    interface C {
                        /** @param Closure(T): void $f */
                        public function each(Closure $f): void;
                    }',
                'error_message' => 'Template param T of C is marked contravariant',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'preventContravariantTemplateInCallableReturnOfReturn' => [
                'code' => '<?php
                    /** @template-contravariant T */
                    interface C {
                        /** @return Closure(): T */
                        public function producer(): Closure;
                    }',
                'error_message' => 'Template param T of C is marked contravariant',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'preventContravariantTemplateInReadonlyProperty' => [
                'code' => '<?php
                    /** @template-contravariant T */
                    final class C {
                        /** @param T $value */
                        public function __construct(public readonly mixed $value) {}
                    }',
                'error_message' => 'Template param T of C is marked contravariant',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'preventContravariantTemplateInWritableProperty' => [
                'code' => '<?php
                    /** @template-contravariant T */
                    final class C {
                        /** @var Closure(T): void */
                        public Closure $consumer;
                        /** @param Closure(T): void $consumer */
                        public function __construct(Closure $consumer) {
                            $this->consumer = $consumer;
                        }
                    }',
                'error_message' => 'InvalidTemplateParam - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:34'
                    . ' - Template param T of C is marked contravariant and cannot be used here',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'preventContravariantTemplateInAProtectedProperty' => [
                'code' => '<?php
                    /** @template-contravariant T */
                    abstract class C {
                        /** @var Closure(T): void */
                        protected Closure $consumer;
                    }',
                'error_message' => 'Template param T of C is marked contravariant',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'preventExtendingCovariantWithContravariance' => [
                'code' => self::SINK . '
                    /**
                     * @template-contravariant T
                     * @extends Source<T>
                     */
                    interface C extends Source {}',
                'error_message' => 'InvalidTemplateParam - src' . DIRECTORY_SEPARATOR . 'somefile.php:22:41'
                    . ' - Cannot extend a covariant template param T into a contravariant context',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'preventExtendingInvariantWithContravariance' => [
                'code' => self::SINK . '
                    /**
                     * @template-contravariant T
                     * @extends Inv<T>
                     */
                    interface C extends Inv {}',
                'error_message' => 'Cannot extend an invariant template param T into a contravariant context',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'preventExtendingContravariantWithCovariance' => [
                'code' => self::SINK . '
                    /**
                     * @template-covariant T
                     * @extends Sink<T>
                     */
                    interface C extends Sink {}',
                'error_message' => 'Cannot extend a contravariant template param T into a covariant context',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'newWithBoundRejectsWhatTheBoundDoes' => [
                'code' => self::SINK . '
                    /**
                     * @template-contravariant T of Countable
                     * @implements Sink<T>
                     */
                    final class CountableSink implements Sink {
                        public function put(mixed $value): void {}
                    }
                    (new CountableSink())->put(1);',
                'error_message' => 'Argument 1 of CountableSink::put expects Countable, but 1 provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'functionTemplateConflictThroughContravariantParam' => [
                'code' => self::SINK . '
                    /**
                     * @template U
                     * @param Sink<U> $sink
                     * @param U $value
                     */
                    function feed(Sink $sink, mixed $value): void {
                        $sink->put($value);
                    }
                    /** @param Sink<int> $int */
                    function f(Sink $int): void {
                        feed($int, "a");
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:28:25'
                    . ' - Type \'a\' should be a subtype of int',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'starSinkDoesNotFitASinkOfAType' => [
                'code' => self::SINK . '
                    /** @param Sink<int> $sink */
                    function takesIntSink(Sink $sink): void {}
                    /** @param Sink<*> $any */
                    function f(Sink $any): void {
                        takesIntSink($any);
                    }',
                'error_message' => 'ArgumentTypeCoercion',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'sinkOfATypeDoesNotFitASinkOfMixed' => [
                'code' => self::SINK . '
                    /** @param Sink<mixed> $sink */
                    function takesMixedSink(Sink $sink): void {}
                    /** @param Sink<int> $int */
                    function f(Sink $int): void {
                        takesMixedSink($int);
                    }',
                'error_message' => 'ArgumentTypeCoercion',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'preventContravariantTemplateInTheBoundOfAMethodTemplateInAContravariantArg' => [
                'code' => self::SINK . '
                    /** @template-contravariant T */
                    interface C {
                        /**
                         * @template U of Sink<T>
                         * @param U $sink
                         */
                        public function take(Sink $sink): void;
                    }',
                'error_message' => 'Template param T of C is marked contravariant',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'preventCovariantTemplateInAContravariantArgOfReturn' => [
                'code' => self::SINK . '
                    /** @template-covariant T */
                    interface C {
                        /** @return Sink<T> */
                        public function sink(): Sink;
                    }',
                'error_message' => 'Template param T of C is marked covariant',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'preventContravariantTemplateInAByRefParam' => [
                'code' => '<?php
                    /** @template-contravariant T */
                    interface C {
                        /** @param T $value */
                        public function swap(mixed &$value): void;
                    }',
                'error_message' => 'Template param T of C is marked contravariant',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'preventContravariantTemplateInAParamOut' => [
                'code' => '<?php
                    /** @template-contravariant T */
                    interface C {
                        /**
                         * @param mixed $value
                         * @param-out T $value
                         */
                        public function fill(mixed &$value): void;
                    }',
                'error_message' => 'Template param T of C is marked contravariant',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'preventContravariantTemplateInTheParamOutOfAnOverride' => [
                'code' => '<?php
                    interface Filler {
                        /** @param mixed $value */
                        public function fill(mixed &$value): void;
                    }
                    /** @template-contravariant T */
                    abstract class C implements Filler {
                        /** @param-out T $value */
                        abstract public function fill(mixed &$value): void;
                    }',
                'error_message' => 'InvalidTemplateParam - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:62'
                    . ' - Template param T of C is marked contravariant and cannot be used here',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'preventContravariantTemplateInAnAssertion' => [
                'code' => '<?php
                    /** @template-contravariant T */
                    interface C {
                        /** @psalm-assert-if-true T $value */
                        public function accepts(mixed $value): bool;
                    }',
                'error_message' => 'Template param T of C is marked contravariant',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'sinkWithoutTypeArgsIsACoercionFromMixed' => [
                'code' => self::SINK . '
                    /** @param Sink<int> $sink */
                    function takesIntSink(Sink $sink): void {}
                    function f(Sink $sink): void {
                        takesIntSink($sink);
                    }',
                'error_message' => 'MixedArgumentTypeCoercion',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'subclassOfASinkWithoutTypeArgsIsACoercionFromMixed' => [
                'code' => self::SINK . '
                    /**
                     * @template-contravariant T
                     * @implements Sink<T>
                     */
                    final class IntoSink implements Sink {
                        public function put(mixed $value): void {}
                    }
                    /** @param Sink<int> $sink */
                    function takesIntSink(Sink $sink): void {}
                    function f(IntoSink $sink): void {
                        takesIntSink($sink);
                    }',
                'error_message' => 'MixedArgumentTypeCoercion',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'newThroughASinkArgTakesWhatTheSinkTakes' => [
                'code' => self::SINK . '
                    /** @template-contravariant T */
                    final class Forwarder {
                        /** @param Sink<T> $sink */
                        public function __construct(private Sink $sink) {}
                        /** @param T $value */
                        public function put(mixed $value): void { $this->sink->put($value); }
                    }
                    /** @param Sink<int> $sink */
                    function f(Sink $sink): void {
                        (new Forwarder($sink))->put("a");
                    }',
                'error_message' => 'Argument 1 of Forwarder::put expects int, but \'a\' provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'newThroughASinkArgOfAnInvariantTemplateRejectsWhatTheSinkDoesNot' => [
                'code' => self::SINK . '
                    /** @template T */
                    final class Holder {
                        /** @param Sink<T> $sink */
                        public function __construct(private Sink $sink) {}
                        /** @param T $value */
                        public function give(mixed $value): void { $this->sink->put($value); }
                    }
                    /** @param Sink<int> $ints */
                    function f(Sink $ints): void {
                        $holder = new Holder($ints);
                        $holder->give("a");
                    }',
                'error_message' => 'IncompatibleTypeParameters - src' . DIRECTORY_SEPARATOR . 'somefile.php:28:39'
                    . ' - Type \'a\' should be a subtype of int',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'preventContravariantTemplateInAPropertyOfAUsedTrait' => [
                'code' => '<?php
                    /** @template-contravariant T */
                    trait HoldsValue {
                        /** @var T|null */
                        public mixed $value = null;
                    }
                    /** @template-contravariant T */
                    final class UsesTrait {
                        /** @use HoldsValue<T> */
                        use HoldsValue;
                    }',
                'error_message' => 'Template param T of UsesTrait is marked contravariant and cannot be used here',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'subclassOfANarrowSinkDoesNotFitAWideSink' => [
                'code' => '<?php
                    /** @template-contravariant T */
                    abstract class Base {
                        /** @param T $value */
                        abstract public function put(mixed $value): void;
                    }
                    /** @extends Base<int> */
                    final class IntSink extends Base {
                        public function put(mixed $value): void {}
                    }
                    /** @param Base<1|2> $sink */
                    function takesNarrowSink(Base $sink): void {}
                    /** @param Base<int|string> $sink */
                    function takesWideSink(Base $sink): void {}

                    function f(IntSink $sink): void {
                        takesNarrowSink($sink);
                        takesWideSink($sink);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:18:39'
                    . ' - Argument 1 of takesWideSink expects Base<int|string>, but IntSink provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'contravariantTemplateAfterAPurityTemplate' => [
                'code' => '<?php
                    /**
                     * @psalm-purity-template P
                     * @template-contravariant T
                     * @template-covariant U
                     */
                    interface Transformer {}
                    /** @param Transformer<int, int|string> $transformer */
                    function takes(Transformer $transformer): void {}
                    /**
                     * @param Transformer<int|string, int> $fits
                     * @param Transformer<string, int> $narrow
                     */
                    function f(Transformer $fits, Transformer $narrow): void {
                        takes($fits);
                        takes($narrow);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:16:31'
                    . ' - Argument 1 of takes expects Transformer<int, int|string>, but Transformer<string, int> provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'typeVariableBoundThroughContravariantParamConflictsWithTheReturnType' => [
                'code' => self::SINK . '
                    /** @template T */
                    final class Coll {
                        /** @param list<T> $items */
                        public function __construct(private array $items) {}
                        /** @param T $item */
                        public function add(mixed $item): void { $this->items[] = $item; }
                    }
                    /**
                     * @template T
                     * @param Coll<T> $coll
                     * @return Sink<T>
                     */
                    function sinkFor(Coll $coll): Sink { throw new Exception(); }
                    /** @param Sink<string> $sink */
                    function takesStringSink(Sink $sink): void {}
                    /** @return Coll<float> */
                    function f(): Coll {
                        $coll = new Coll([1.5]);
                        takesStringSink(sinkFor($coll));
                        return $coll;
                    }',
                'error_message' => 'IncompatibleTypeParameters - src' . DIRECTORY_SEPARATOR . 'somefile.php:36:41'
                    . ' - Type string should be a subtype of float',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'starProjectionReadThroughAnOuterTypeTakesNothing' => [
                'code' => self::SINK . '
                    /** @template T */
                    interface Outer {
                        /** @return Sink<T> */
                        public function sink(): Sink;
                    }
                    /** @param Outer<*> $outer */
                    function f(Outer $outer): void {
                        $outer->sink()->put(1);
                    }',
                'error_message' => 'Argument 1 of Sink::put expects never, but 1 provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'newThroughAnInheritedConstructorKeepsTheCallbackBound' => [
                'code' => '<?php
                    /** @template-contravariant T */
                    class ParentSink {
                        /** @param Closure(T): void $callback */
                        public function __construct(private Closure $callback) {}
                        /** @param T $value */
                        public function put(mixed $value): void { ($this->callback)($value); }
                    }
                    /**
                     * @template-contravariant U
                     * @extends ParentSink<U>
                     */
                    final class ChildSink extends ParentSink {}
                    $sink = new ChildSink(function (int $value): void {});
                    $sink->put("a");',
                'error_message' => 'Argument 1 of ChildSink::put expects int, but \'a\' provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'newThroughAnInheritedConstructorTakesWhatEveryCallbackTakes' => [
                'code' => '<?php
                    /**
                     * @template-contravariant A
                     * @template-contravariant B
                     */
                    class ParentSink {
                        /**
                         * @param Closure(A): void $first
                         * @param Closure(B): void $second
                         */
                        public function __construct(private Closure $first, private Closure $second) {}
                        /** @param B $value */
                        public function second(mixed $value): void { ($this->second)($value); }
                    }
                    /**
                     * @template-contravariant T
                     * @extends ParentSink<T, T>
                     */
                    final class ChildSink extends ParentSink {}
                    $sink = new ChildSink(function (int|string $value): void {}, function (int $value): void {});
                    $sink->second("a");',
                'error_message' => 'Argument 1 of ChildSink::second expects int, but \'a\' provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'newThroughAnInheritedConstructorMeetsAnInvariantArgAndACallback' => [
                'code' => '<?php
                    /** @template V */
                    final class Box {
                        /** @param V $x */
                        public function set(mixed $x): void {}
                    }
                    /**
                     * @template-contravariant A
                     * @template-contravariant B
                     */
                    class ParentSink {
                        /**
                         * @param Closure(A): void $first
                         * @param Box<B> $box
                         */
                        public function __construct(private Closure $first, private Box $box) {}
                        /** @param A $value */
                        public function first(mixed $value): void { ($this->first)($value); }
                    }
                    /**
                     * @template-contravariant T
                     * @extends ParentSink<T, T>
                     */
                    final class ChildSink extends ParentSink {}
                    /** @param Box<string> $box */
                    function f(Box $box): void {
                        $sink = new ChildSink(function (int $value): void {}, $box);
                        $sink->first("a");
                    }',
                'error_message' => 'Argument 1 of ChildSink::first expects never, but \'a\' provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'newThroughAnInvariantArgPinsTheTemplate' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @param T $x */
                        public function set(mixed $x): void {}
                    }
                    /** @template-contravariant T */
                    final class Writer {
                        /** @param Box<T> $box */
                        public function __construct(private Box $box) {}
                        /** @param T $x */
                        public function put(mixed $x): void { $this->box->set($x); }
                    }
                    /** @param Box<int> $box */
                    function f(Box $box): void {
                        $writer = new Writer($box);
                        $writer->put("a");
                    }',
                'error_message' => 'Argument 1 of Writer::put expects int, but \'a\' provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'typeVariableOfAnEmptyConstructionTakesTheBoundOfASink' => [
                'code' => self::SINK . '
                    /** @template T */
                    final class Buffer {
                        /** @param array<T> $items */
                        public function __construct(private array $items) {}
                        /** @param T $item */
                        public function add(mixed $item): void { $this->items[] = $item; }
                        /** @param Sink<T> $sink */
                        public function pipeTo(Sink $sink): void {}
                    }
                    /** @param Sink<int> $int */
                    function f(Sink $int): void {
                        $buffer = new Buffer([]);
                        $buffer->pipeTo($int);
                        $buffer->add("a");
                    }',
                'error_message' => 'IncompatibleTypeParameters - src' . DIRECTORY_SEPARATOR . 'somefile.php:31:38'
                    . ' - Type \'a\' should be a subtype of int',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
        ];
    }
}
