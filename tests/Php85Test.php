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

use const DIRECTORY_SEPARATOR;

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
            'pipeIntoPartialApplicationWithANamedArgumentBeforeTheVariadicPlaceholder' => [
                'code' => '<?php
                    final class A {
                        public function two(int $a, string $b): string { return $b . $a; }
                    }

                    $o = new A();

                    $namedBeforeVariadic = "z" |> $o->two(a: 1, ...);',
                'assertions' => [
                    '$namedBeforeVariadic===' => 'string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'pipeCreatesThePartialApplicationAfterTheLeftHandSide' => [
                'code' => '<?php
                    function add(int $a, int $b): int { return $a + $b; }

                    $r = ($i = 5) |> add(b: $i, ...);',
                'assertions' => [
                    '$r===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'pipeIntoDirectPartialApplicationsPhpCompiles' => [
                'code' => '<?php
                    final class Q {
                        public function m(int $x): int { return $x; }
                    }

                    final class O {
                        public function __construct(public Q $p) {}
                    }

                    function two(int $a, int $b = 0): int { return $a + $b; }

                    function f(?O $o): void {
                        $unpacking = 3 |> two(?, ...[1]);
                        $nullsafe = 3 |> $o?->p->m(?);
                        /** @psalm-check-type-exact $unpacking = int */
                        /** @psalm-check-type-exact $nullsafe = int|null */
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'pipeIntoPartialApplicationWithTheVariadicPlaceholderFirst' => [
                'code' => '<?php
                    function two(int $a, int $b = 0): int { return $a + $b; }

                    $positional = 3 |> two(..., 1);
                    $named = 3 |> two(..., b: 1);
                    $unpacking = 3 |> two(..., ...[1]);',
                'assertions' => [
                    '$positional===' => 'int',
                    '$named===' => 'int',
                    '$unpacking===' => 'int',
                ],
                'ignored_issues' => [],
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
            'pipeIntoPartialApplicationWithTwoPlaceholders' => [
                'code' => '<?php
                    function add(int $a, int $b): int { return $a + $b; }

                    $twoPlaceholders = 3 |> add(?, ?);',
                'error_message' => 'TooFewArguments',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'pipeIntoPartialApplicationOfNew' => [
                'code' => '<?php
                    final class N {
                        public function __construct(public int $x) {}
                    }

                    $new = 3 |> new N(?);',
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:33 - Cannot create Closure for new expression',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'pipeIntoPartialApplicationBeforePhp86' => [
                'code' => '<?php
                    function add(int $a, int $b): int { return $a + $b; }

                    $r = 3 |> add(1, ?);',
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:31 - Partial function application requires PHP 8.6',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'pipeIntoPartialApplicationWithTheVariadicPlaceholderTwice' => [
                'code' => '<?php
                    function one(int $a): int { return $a; }

                    $r = 3 |> one(?, ..., ...);',
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:31 - Variadic placeholder may only appear once',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'pipeIntoPartialApplicationWithAPlaceholderAfterTheVariadicPlaceholder' => [
                'code' => '<?php
                    function two(int $a, int $b = 0): int { return $a + $b; }

                    $r = 3 |> two(..., ?);',
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:31 - Variadic placeholder must be last',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'pipeIntoPartialApplicationWithAPlaceholderAfterANamedArgument' => [
                'code' => '<?php
                    function two(int $a, int $b = 0): int { return $a + $b; }

                    $r = 3 |> two(b: 1, ?);',
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:31 - Cannot use positional argument after named argument',
                'error_levels' => [],
                'php_version' => '8.6',
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
