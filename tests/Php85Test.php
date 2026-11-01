<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Context;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\IssueBuffer;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function array_map;

final class Php85Test extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function testEachCallOfAChainReportsItsOwnIssues(): void
    {
        $this->testConfig->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile($file_path, '<?php
            function add(int $a, int $b): int { return $a + $b; }

            function f(int $x): int {
                return $x |> add(...) |> add(...);
            }
        ');
        $this->project_analyzer->setPhpVersion('8.5', 'tests');

        $this->analyzeFile($file_path, new Context());

        self::assertSame(
            [
                [5, 30, 'TooFewArguments'],
                [5, 42, 'TooFewArguments'],
            ],
            array_map(
                static fn(IssueData $issue): array => [$issue->line_from, $issue->column_from, $issue->type],
                IssueBuffer::getIssuesDataForFile($file_path),
            ),
        );
    }

    public function testAConditionReportsWhatAPipeAssertsAsTheCallItMakes(): void
    {
        $this->testConfig->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile($file_path, '<?php
            /**
             * @psalm-assert int $x
             */
            function assertInt(mixed $x): void { if (!is_int($x)) { throw new Exception(); } }

            function f(int $i): void {
                if ($i |> is_int(...)) {}
                if (!($i |> is_int(...))) {}
                echo $i |> is_int(...) ? 1 : 2;
                assert($i |> is_int(...));
                $i |> assertInt(...);
            }
        ');
        $this->project_analyzer->setPhpVersion('8.5', 'tests');

        $this->analyzeFile($file_path, new Context());

        self::assertSame(
            [
                [8, 21, 'RedundantCondition'],
                [9, 23, 'TypeDoesNotContainType'],
                [10, 22, 'RedundantCondition'],
                [10, 46, 'TypeDoesNotContainType'],
                [11, 24, 'RedundantCondition'],
                [11, 17, 'RedundantCondition'],
                [12, 17, 'RedundantConditionGivenDocblockType'],
            ],
            array_map(
                static fn(IssueData $issue): array => [$issue->line_from, $issue->column_from, $issue->type],
                IssueBuffer::getIssuesDataForFile($file_path),
            ),
        );
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'pipeIntoFirstClassCallable' => [
                'code' => '<?php
                    final class A {
                        public function m(int $x): string { return (string) $x; }
                        public static function s(string $x): int { return strlen($x); }
                    }

                    /**
                     * @template T
                     * @param T $x
                     * @return list<T>
                     */
                    function wrap(mixed $x): array { return [$x]; }

                    /** @return array<string, int> */
                    function counts(): array { return []; }

                    $o = new A();
                    $arr = counts();

                    $keys = $arr |> array_keys(...);
                    $wrapped = 1 |> wrap(...);
                    $method = 3 |> $o->m(...);
                    $static = "x" |> A::s(...);
                    $chain = "a" |> strtoupper(...) |> strlen(...);',
                'assertions' => [
                    '$keys===' => 'list<string>',
                    '$wrapped===' => 'list<1>',
                    '$method===' => 'string',
                    '$static===' => 'int',
                    '$chain===' => 'int<1, max>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'pipeIntoCallableValue' => [
                'code' => '<?php
                    final class Inv {
                        public function __invoke(int $x): float { return $x * 1.5; }
                    }

                    $f = strtoupper(...);

                    $invokable = 3 |> new Inv();
                    $closure = 3 |> function (int $x): string { return (string) $x; };
                    $arrow = 3 |> (fn(int $x): int => $x * 2);
                    $closureVar = "a" |> $f;
                    $string = "x" |> "strlen";',
                'assertions' => [
                    '$invokable===' => 'float',
                    '$closure===' => 'decimal-int-string',
                    '$arrow===' => 'int',
                    '$closureVar===' => 'string',
                    '$string===' => 'int<1, max>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'pipeIntoPartialApplication' => [
                'code' => '<?php
                    final class A {
                        public function two(int $a, string $b): string { return $b . $a; }
                        public static function s(string $x): int { return strlen($x); }
                    }

                    function add(int $a, int $b): int { return $a + $b; }

                    $o = new A();

                    $first = 3 |> add(?, 1);
                    $second = 3 |> add(1, ?);
                    $variadic = 3 |> add(1, ...);
                    $named = "z" |> $o->two(b: ?, a: 1);
                    $static = "x" |> A::s(?);
                    $builtin = [1, 2] |> array_map(fn(int $x): string => (string) $x, ?);',
                'assertions' => [
                    '$first===' => 'int',
                    '$second===' => 'int',
                    '$variadic===' => 'int',
                    '$named===' => 'string',
                    '$static===' => 'int',
                    '$builtin===' => 'list{\'1\'|\'2\', \'1\'|\'2\'}',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'pipeEvaluatesLeftHandSideFirst' => [
                'code' => '<?php
                    function add(int $a, int $b): int { return $a + $b; }

                    $r = ($x = 5) |> add(?, $x);',
                'assertions' => [
                    '$r===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'pipeKeepsPurity' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @return list<array-key>
                     */
                    function keys(array $array): array {
                        return $array |> array_keys(...);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'pipeIntoPartialApplicationInLoop' => [
                'code' => '<?php
                    function add(int $a, int $b): int { return $a + $b; }
                    function takesInt(int $i): void {}

                    $r = 0;
                    foreach ([1, 2] as $i) {
                        $r = $i |> add(?, 1);
                        takesInt($i |> add(?, 1));
                    }',
                'assertions' => [
                    '$r===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'pipeIntoPrivateMethodPartialApplication' => [
                'code' => '<?php
                    final class A {
                        public function run(int $x): string {
                            return $x |> $this->format(?, "!");
                        }

                        private function format(int $x, string $suffix): string {
                            return $x . $suffix;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'pipeIntoUnsupportedPartialApplication' => [
                'code' => '<?php
                    final class A {
                        public function two(int $a, string $b): string { return $b . $a; }
                    }

                    final class N {
                        public function __construct(public int $x) {}
                    }

                    function add(int $a, int $b): int { return $a + $b; }

                    $o = new A();

                    $twoPlaceholders = 3 |> add(?, ?);
                    $namedBeforeVariadic = "z" |> $o->two(a: 1, ...);
                    $new = 3 |> new N(?);',
                'assertions' => [
                    '$twoPlaceholders===' => 'mixed',
                    '$namedBeforeVariadic===' => 'mixed',
                    '$new===' => 'mixed',
                ],
                'ignored_issues' => ['MixedAssignment'],
                'php_version' => '8.6',
            ],
            'pipeKeepsTheContextOfACallableLiteral' => [
                'code' => '<?php
                    /** @return list<int> */
                    function ints(): array { return [1, 2]; }

                    $r = (fn($v) => $v * 2) |> array_map(?, [1, 2]);
                    $s = (fn($v) => $v * 2) |> array_map(?, ints());',
                'assertions' => [
                    '$r===' => 'list{2|4, 2|4}',
                    '$s===' => 'list<int>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'pipeOnTheLeftOfCoalesce' => [
                'code' => '<?php
                    function findName(string $id): ?string { return $id === "" ? null : $id; }

                    /** @return array<string, string> */
                    function ids(): array { return []; }

                    $id = "a";

                    $r = $id |> findName(...) ?? "default";
                    $s = ids()["k"] |> findName(...) ?? "default";',
                'assertions' => [
                    '$r===' => 'string',
                    '$s===' => 'string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'pipeNarrowsAsTheCallItMakes' => [
                'code' => '<?php
                    final class A {
                        /**
                         * @psalm-assert-if-true int $x
                         */
                        public function isInt(mixed $x): bool { return is_int($x); }

                        /**
                         * @psalm-assert-if-true string $x
                         */
                        public static function isString(mixed $x): bool { return is_string($x); }
                    }

                    /**
                     * @psalm-assert-if-true int $x
                     */
                    function isInt(mixed $x): bool { return is_int($x); }

                    /**
                     * @psalm-assert int $x
                     */
                    function assertInt(mixed $x): void { if (!is_int($x)) { throw new Exception(); } }

                    function m(): mixed { return 1; }

                    $o = new A();

                    $a = m();
                    if ($a |> isInt(...)) { $if = $a; } else { $if = 0; }

                    $b = m();
                    if (!($b |> is_string(...))) { $b = ""; }
                    $not = $b;

                    $c = m();
                    $ternary = $c |> is_int(...) ? $c : 0;

                    $d = m();
                    $and = $d |> is_int(...) && $d > 0 ? $d : 1;

                    $e = m();
                    $match = match (true) { $e |> is_int(...) => $e, default => 0 };

                    $f = m();
                    assert($f |> is_int(...));
                    $assert = $f;

                    $g = m();
                    while (!($g |> is_int(...))) { $g = m(); }
                    $while = $g;

                    $h = m();
                    $h |> assertInt(...);
                    $statement = $h;

                    $i = m();
                    $method = $i |> $o->isInt(...) ? $i : 0;

                    $j = m();
                    $static = $j |> A::isString(...) ? $j : "";',
                'assertions' => [
                    '$if===' => 'int',
                    '$not===' => 'string',
                    '$ternary===' => 'int',
                    '$and===' => 'int<1, max>',
                    '$match===' => 'int',
                    '$assert===' => 'int',
                    '$while===' => 'int',
                    '$statement===' => 'int',
                    '$method===' => 'int',
                    '$static===' => 'string',
                ],
                'ignored_issues' => ['MixedAssignment'],
                'php_version' => '8.5',
            ],
            'pipeIntoPartialApplicationNarrows' => [
                'code' => '<?php
                    /**
                     * @psalm-assert-if-true int $x
                     */
                    function isInt(mixed $x): bool { return is_int($x); }

                    function m(): mixed { return 1; }

                    $a = m();
                    $r = $a |> isInt(?) ? $a : 0;',
                'assertions' => [
                    '$r===' => 'int',
                ],
                'ignored_issues' => ['MixedAssignment'],
                'php_version' => '8.6',
            ],
            'pipeAssignedToAVariableNarrowsNothingAsTheCallItMakes' => [
                'code' => '<?php
                    function m(): mixed { return 1; }

                    $a = m();
                    $isInt = $a |> is_int(...);
                    $r = $isInt ? $a : 0;',
                'assertions' => [
                    '$r===' => '0|mixed',
                ],
                'ignored_issues' => ['MixedAssignment'],
                'php_version' => '8.5',
            ],
            'pipeNarrowsAPropertyOrAnOffset' => [
                'code' => '<?php
                    class C {
                        public ?string $x = null;
                        public static ?string $s = null;

                        /**
                         * @psalm-assert-if-true string $v
                         */
                        private function isString(mixed $v): bool { return is_string($v); }

                        public function property(): string {
                            return $this->x |> is_string(...) ? $this->x : "";
                        }

                        public function method(): string {
                            return $this->x |> $this->isString(...) ? $this->x : "";
                        }

                        public static function staticProperty(): string {
                            return self::$s |> is_string(...) ? self::$s : "";
                        }
                    }

                    /**
                     * @param array<string, mixed> $a
                     */
                    function offset(array $a): int {
                        while ($a["k"] |> is_int(...)) {
                            return $a["k"];
                        }

                        return 0;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'pipeNarrowsAPropertyOfItsArgument' => [
                'code' => '<?php
                    final class HasP {
                        public ?string $p = null;
                    }

                    final class Holder {
                        public function __construct(public HasP $h) {}
                    }

                    final class Checks {
                        /**
                         * @psalm-assert-if-true !null $h->p
                         */
                        public static function has(HasP $h): bool { return $h->p !== null; }
                    }

                    /**
                     * @psalm-assert-if-false !null $h->p
                     */
                    function lacksP(HasP $h): bool { return $h->p === null; }

                    /**
                     * @psalm-assert !null $h->p
                     */
                    function assertHasP(HasP $h): void { if ($h->p === null) { throw new Exception(); } }

                    function ifTrue(Holder $hd): string {
                        return $hd->h |> Checks::has(...) ? $hd->h->p : "";
                    }

                    function ifFalse(Holder $hd): string {
                        return $hd->h |> lacksP(...) ? "" : $hd->h->p;
                    }

                    function asserted(Holder $hd): string {
                        $hd->h |> assertHasP(...);

                        return $hd->h->p;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'pipedValueAssertsNothingAboutItsProperty' => [
                'code' => '<?php
                    final class HasP {
                        public ?string $p = null;
                    }

                    final class Holder {
                        public function __construct(public HasP $h) {}
                    }

                    final class Checks {
                        /**
                         * @psalm-assert-if-true !null $h->p
                         */
                        public function has(HasP $h): bool { return $h->p !== null; }

                        /**
                         * @psalm-assert-if-false !null $h->p
                         */
                        public static function lacks(HasP $h): bool { return $h->p === null; }

                        /**
                         * @psalm-assert !null $h->p
                         */
                        public function assertHas(HasP $h): void { if ($h->p === null) { throw new Exception(); } }
                    }

                    function mkHasP(): HasP { return new HasP(); }

                    function f(Holder $hd, Checks $checks): bool {
                        if ($hd->h |> $checks->has(...)) {}
                        if (mkHasP() |> Checks::lacks(...)) {}
                        $hd->h |> $checks->assertHas(...);

                        $has = mkHasP() |> $checks->has(...);
                        $lacks = mkHasP() |> Checks::lacks(...);

                        return $has && $lacks;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'pipeOutsideAConditionAssertsAsAMethodCallDoes' => [
                'code' => '<?php
                    final class HasP {
                        public ?string $p = null;
                    }

                    final class Checks {
                        /**
                         * @psalm-assert-if-true !null $h->p
                         */
                        public function has(HasP $h, int $x): bool { return $h->p !== null && $x > 0; }
                    }

                    function mkHasP(): HasP { return new HasP(); }

                    function f(Checks $checks, int $x): bool {
                        return $x |> $checks->has(mkHasP(), ?);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
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
            'pipeChecksArgument' => [
                'code' => '<?php
                    final class A {
                        public function m(int $x): string { return (string) $x; }
                    }

                    function f(A $o): string {
                        return "x" |> $o->m(...);
                    }',
                'error_message' => 'InvalidScalarArgument',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'pipeIntoUndefinedFunction' => [
                'code' => '<?php
                    $r = "x" |> undefined_fn(?);',
                'error_message' => 'UndefinedFunction',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'pipeIntoNonCallable' => [
                'code' => '<?php
                    $k = 42;
                    $r = "x" |> $k;',
                'error_message' => 'InvalidFunctionCall',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'pipeChecksPurity' => [
                'code' => '<?php
                    function impure(int $x): int { echo $x; return $x; }

                    /** @psalm-pure */
                    function f(int $x): int {
                        return $x |> impure(...);
                    }',
                'error_message' => 'ImpureFunctionCall',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'pipeReadsAnArrayAccessOffsetFirst' => [
                'code' => '<?php
                    /** @implements ArrayAccess<string, string> */
                    final class B implements ArrayAccess {
                        public ?string $p = null;

                        public function offsetExists(mixed $offset): bool { return true; }
                        public function offsetGet(mixed $offset): string { $this->p = null; return "v"; }
                        public function offsetSet(mixed $offset, mixed $value): void {}
                        public function offsetUnset(mixed $offset): void {}
                    }

                    /** @param Closure(): int $f */
                    function apply(Closure $f, string $s): int { return $f() + strlen($s); }

                    function f(B $b): int {
                        if ($b->p === null) {
                            return 0;
                        }

                        return $b["k"] |> apply(function () use ($b): int { return strlen($b->p); }, ?);
                    }',
                'error_message' => 'PossiblyNullArgument',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'pipeReadsAnArrayAccessOffsetFirstWhateverTheVariableName' => [
                'code' => '<?php
                    /** @implements ArrayAccess<string, string> */
                    final class B implements ArrayAccess {
                        public ?string $p = null;

                        public function offsetExists(mixed $offset): bool { return true; }
                        public function offsetGet(mixed $offset): string { $this->p = null; return "v"; }
                        public function offsetSet(mixed $offset, mixed $value): void {}
                        public function offsetUnset(mixed $offset): void {}
                    }

                    /** @param Closure(): int $f */
                    function apply(Closure $f, string $s): int { return $f() + strlen($s); }

                    function f(B $http_response_header): int {
                        if ($http_response_header->p === null) {
                            return 0;
                        }

                        return $http_response_header["k"]
                            |> apply(function () use ($http_response_header): int {
                                return strlen($http_response_header->p);
                            }, ?);
                    }',
                'error_message' => 'PossiblyNullArgument',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
        ];
    }
}
