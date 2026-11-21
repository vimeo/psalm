<?php

declare(strict_types=1);

namespace Psalm\Tests\ReturnTypeProvider;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class ReflectionReferenceFromArrayElementTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        yield 'byRefElementIsNotNull' => [
            'code' => '<?php
                class ReferenceTracker {
                    private array $references = [];

                    public function seenThisArray(array &$var): bool {
                        $ref_id = ReflectionReference::fromArrayElement([&$var], 0)->getId();
                        $seen_it = isset($this->references[$ref_id]);
                        $this->references[$ref_id] = true;
                        return $seen_it;
                    }
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];

        yield 'byRefElementAtImplicitIndex' => [
            'code' => '<?php
                $a = 1;
                $b = 2;
                $r = ReflectionReference::fromArrayElement([5 => $a, &$b], 6);
            ',
            'assertions' => [
                '$r===' => 'ReflectionReference',
            ],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];

        yield 'byRefElementAtStringKey' => [
            'code' => '<?php
                $a = 1;
                $r = ReflectionReference::fromArrayElement(["a" => &$a], "a");
            ',
            'assertions' => [
                '$r===' => 'ReflectionReference',
            ],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];

        yield 'byRefElementAtNumericStringKey' => [
            'code' => '<?php
                $a = 1;
                $r = ReflectionReference::fromArrayElement(["1" => &$a], 1);
                $s = ReflectionReference::fromArrayElement([1 => &$a], "1");
            ',
            'assertions' => [
                '$r===' => 'ReflectionReference',
                '$s===' => 'ReflectionReference',
            ],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];

        yield 'byRefElementWithNamedArgs' => [
            'code' => '<?php
                $a = 1;
                $r = ReflectionReference::fromArrayElement(key: 0, array: [&$a]);
            ',
            'assertions' => [
                '$r===' => 'ReflectionReference',
            ],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];

        yield 'laterDuplicateKeyByRef' => [
            'code' => '<?php
                $a = 1;
                $b = 2;
                $r = ReflectionReference::fromArrayElement([0 => $a, 0 => &$b], 0);
            ',
            'assertions' => [
                '$r===' => 'ReflectionReference',
            ],
            'ignored_issues' => ['DuplicateArrayKey'],
            'php_version' => '8.0',
        ];

        yield 'byValueElementStaysNullable' => [
            'code' => '<?php
                $a = 1;
                $r = ReflectionReference::fromArrayElement([$a], 0);
            ',
            'assertions' => [
                '$r===' => 'ReflectionReference|null',
            ],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];

        yield 'laterDuplicateKeyByValueStaysNullable' => [
            'code' => '<?php
                $a = 1;
                $b = 2;
                $r = ReflectionReference::fromArrayElement([0 => &$a, 0 => $b], 0);
            ',
            'assertions' => [
                '$r===' => 'ReflectionReference|null',
            ],
            'ignored_issues' => ['DuplicateArrayKey'],
            'php_version' => '8.0',
        ];

        yield 'missingKeyStaysNullable' => [
            'code' => '<?php
                $a = 1;
                $r = ReflectionReference::fromArrayElement([&$a], 1);
            ',
            'assertions' => [
                '$r===' => 'ReflectionReference|null',
            ],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];

        yield 'nonLiteralKeyStaysNullable' => [
            'code' => '<?php
                $a = 1;
                $r = ReflectionReference::fromArrayElement([&$a], rand(0, 1));
            ',
            'assertions' => [
                '$r===' => 'ReflectionReference|null',
            ],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];

        yield 'byRefElementAtNegativeKey' => [
            'code' => '<?php
                $a = 1;
                $r = ReflectionReference::fromArrayElement([-5 => &$a], -5);
            ',
            'assertions' => [
                '$r===' => 'ReflectionReference',
            ],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];

        yield 'implicitIndexAfterNegativeKeyBeforePhp83' => [
            'code' => '<?php
                $a = 1;
                $b = 2;
                $r = ReflectionReference::fromArrayElement([-5 => $a, &$b], 0);
                $s = ReflectionReference::fromArrayElement([-5 => $a, &$b], -4);
            ',
            'assertions' => [
                '$r===' => 'ReflectionReference',
                '$s===' => 'ReflectionReference|null',
            ],
            'ignored_issues' => [],
            'php_version' => '8.2',
        ];

        yield 'implicitIndexAfterNegativeKeySincePhp83' => [
            'code' => '<?php
                $a = 1;
                $b = 2;
                $c = 3;
                $r = ReflectionReference::fromArrayElement([-5 => $a, &$b], -4);
                $s = ReflectionReference::fromArrayElement([-5 => $a, &$b], 0);
                $t = ReflectionReference::fromArrayElement([-5 => $a, -3 => $b, &$c], -2);
            ',
            'assertions' => [
                '$r===' => 'ReflectionReference',
                '$s===' => 'ReflectionReference|null',
                '$t===' => 'ReflectionReference',
            ],
            'ignored_issues' => [],
            'php_version' => '8.3',
        ];

        yield 'byRefArrayElementOrStaticProperty' => [
            'code' => '<?php
                class K {
                    public static int $s = 1;
                }
                $x = ["k" => ["l" => 1]];
                $r = ReflectionReference::fromArrayElement([&$x["k"]["l"]], 0);
                $s = ReflectionReference::fromArrayElement([&K::$s], 0);
            ',
            'assertions' => [
                '$r===' => 'ReflectionReference',
                '$s===' => 'ReflectionReference',
            ],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];

        yield 'byRefTemporaryStaysNullable' => [
            'code' => '<?php
                /** @return list{int} */
                function makeArray(): array {
                    return [1];
                }
                $r = ReflectionReference::fromArrayElement([&makeArray()[0]], 0);
            ',
            'assertions' => [
                '$r===' => 'ReflectionReference|null',
            ],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];

        yield 'byRefObjectPropertyOrOffsetStaysNullable' => [
            'code' => '<?php
                class Base {
                    public int $p = 1;
                }

                // a subclass with __get() that unsets $p makes the property fetch return a value, not a reference
                /** @param ArrayAccess<string, int> $a */
                function f(Base $o, ArrayAccess $a): void {
                    $r = ReflectionReference::fromArrayElement([&$o->p], 0);
                    $s = ReflectionReference::fromArrayElement([&$a["k"]], 0);
                    /** @psalm-check-type-exact $r = ReflectionReference|null */
                    /** @psalm-check-type-exact $s = ReflectionReference|null */
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];

        yield 'spreadStaysNullable' => [
            'code' => '<?php
                $a = 1;
                $r = ReflectionReference::fromArrayElement([...[1], &$a], 1);
            ',
            'assertions' => [
                '$r===' => 'ReflectionReference|null',
            ],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];

        yield 'arrayVariableStaysNullable' => [
            'code' => '<?php
                $a = 1;
                $arr = [&$a];
                $r = ReflectionReference::fromArrayElement($arr, 0);
            ',
            'assertions' => [
                '$r===' => 'ReflectionReference|null',
            ],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        yield 'byValueElementIsPossiblyNull' => [
            'code' => '<?php
                $a = 1;
                ReflectionReference::fromArrayElement([$a], 0)->getId();
            ',
            'error_message' => 'PossiblyNullReference',
            'error_levels' => [],
            'php_version' => '8.0',
        ];
    }
}
