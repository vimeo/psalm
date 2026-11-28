<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class LegacySyntaxTest extends TestCase
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
            'emptyKeepsMeaningNever' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return list<empty> */
                    function none(): array {
                        return [];
                    }
                    /** @return array<string, empty> */
                    function noneByName(): array {
                        return [];
                    }
                    $a = none();
                    $b = noneByName();
                    PHP,
                'assertions' => [
                    '$a===' => 'array<never, never>',
                    '$b===' => 'array<string, never>',
                ],
                'ignored_issues' => ['LegacySyntax'],
            ],
            'arrayTypesWithoutEmpty' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @param array{} $a
                     * @param non-empty-array<string, int> $b
                     * @param non-empty-list<non-empty-string> $c
                     * @param array<string, never> $d
                     * @param array{empty: int} $e
                     * @param list<array<string, int>> $f
                     */
                    function f(array $a, array $b, array $c, array $d, array $e, array $f): void {}
                    PHP,
            ],
            'emptyInAssertion' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @param mixed $value
                     * @psalm-assert list<empty> $value
                     */
                    function assertNone($value): void {}
                    PHP,
            ],
            'emptyInDescription' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * Returns an empty array, which was written list<empty>
                     *
                     * @return array{}
                     */
                    function none(): array {
                        return [];
                    }
                    PHP,
            ],
            'emptyInTagDescription' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @param int $x if 0, list<empty> is returned
                     * @return list<int>
                     */
                    function f(int $x): array {
                        return $x === 0 ? [] : [$x];
                    }
                    PHP,
            ],
            'emptyInLiteralString' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return 'list<empty>'|int */
                    function f() {
                        return 'list<empty>';
                    }
                    PHP,
            ],
            'aliasNamedEmpty' => [
                'code' => <<<'PHP'
                    <?php
                    /** @psalm-type Empty = string */
                    final class A {
                        /** @param list<Empty> $names */
                        public function f(array $names): void {}
                    }
                    PHP,
            ],
            'emptyInUnionParameter' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param list<empty|int> $a */
                    function f(array $a): void {}
                    PHP,
            ],
            'suppressedOnFunction' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @psalm-suppress LegacySyntax
                     * @return list<empty>
                     */
                    function f(): array {
                        /** @var list<empty> $a */
                        $a = [];
                        return $a;
                    }
                    PHP,
            ],
            'suppressedOnClass' => [
                'code' => <<<'PHP'
                    <?php
                    /** @psalm-suppress LegacySyntax */
                    final class A {
                        /** @var list<empty> */
                        public array $a = [];

                        /** @return list<empty> */
                        public function f(): array {
                            return [];
                        }
                    }
                    PHP,
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
            'arrayOfEmptyKeyAndValue' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param array<empty, empty> $a */
                    function f(array $a): void {}
                    PHP,
                'error_message' => 'LegacySyntax - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:12 - array<empty, empty>'
                    . ' is legacy syntax for the empty array, write array{}: empty changes meaning in Psalm 8',
            ],
            'listOfEmpty' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return list<empty> */
                    function f(): array {
                        return [];
                    }
                    PHP,
                'error_message' => 'LegacySyntax - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:13 - list<empty> is',
            ],
            'arrayOfEmptyValue' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param array<string, empty> $a */
                    function f(array $a): void {}
                    PHP,
                'error_message' => 'LegacySyntax - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:12 - array<string, empty> is',
            ],
            'arrayOfEmptyKey' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param array<empty, int> $a */
                    function f(array $a): void {}
                    PHP,
                'error_message' => 'array<empty, int> is legacy syntax',
            ],
            'associativeArrayOfEmpty' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param associative-array<empty, empty> $a */
                    function f(array $a): void {}
                    PHP,
                'error_message' => 'associative-array<empty, empty> is legacy syntax',
            ],
            'nestedListOfEmpty' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param array<string, list<empty>> $a */
                    function f(array $a): void {}
                    PHP,
                'error_message' => 'LegacySyntax - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:26 - list<empty> is',
            ],
            'multilineArrayOfEmpty' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @param array<
                     *     empty,
                     *     empty
                     * > $a
                     */
                    function f(array $a): void {}
                    PHP,
                'error_message' => 'LegacySyntax - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:11 - array< empty, empty > is',
            ],
            'varOfEmptyInFunctionBody' => [
                'code' => <<<'PHP'
                    <?php
                    function f(): void {
                        /** @var list<empty> $a */
                        $a = [];
                    }
                    PHP,
                'error_message' => 'LegacySyntax - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:14 - list<empty> is',
            ],
            'propertyOfEmpty' => [
                'code' => <<<'PHP'
                    <?php
                    final class A {
                        /** @var list<empty> */
                        public array $a = [];
                    }
                    PHP,
                'error_message' => 'list<empty> is legacy syntax',
            ],
            'typeAliasOfEmptyOnClass' => [
                'code' => <<<'PHP'
                    <?php
                    /** @psalm-type None = list<empty> */
                    final class A {}
                    PHP,
                'error_message' => 'list<empty> is legacy syntax',
            ],
            'templateBoundOfEmpty' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @template T of list<empty>
                     * @param T $a
                     */
                    function f(array $a): void {}
                    PHP,
                'error_message' => 'list<empty> is legacy syntax',
            ],
            'staticMethodReturningEmpty' => [
                'code' => <<<'PHP'
                    <?php
                    /** @method static list<empty> none() */
                    final class A {}
                    PHP,
                'error_message' => 'list<empty> is legacy syntax',
            ],
            'unionMemberOfEmpty' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return int|list<empty> */
                    function f() {
                        return [];
                    }
                    PHP,
                'error_message' => 'LegacySyntax - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:17 - list<empty> is',
            ],
            'suppressedForAnotherIssue' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @psalm-suppress InvalidReturnType
                     * @return list<empty>
                     */
                    function f(): array {
                        return [];
                    }
                    PHP,
                'error_message' => 'list<empty> is legacy syntax',
            ],
        ];
    }
}
