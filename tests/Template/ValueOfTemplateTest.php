<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class ValueOfTemplateTest extends TestCase
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
            'acceptsArrayValuesFn' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     * @return value-of<T>[]
                     */
                    function getValues($array) {
                        return array_values($array);
                    }
                ',
            ],
            'mixedValueOf' => [
                'code' => '<?php
                    /** @var array<int>|mixed */
                    $a = [];

                    /** @psalm-suppress MixedArgument */
                    $a = array_values($a);',
                'assertions' => [
                    '$a' => 'list<mixed>',
                ],
            ],
            'SKIPPED-acceptsIfInArrayFn' => [ // #13931
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     * @return value-of<T>|null
                     */
                    function getValue(string $value, $array) {
                        if (in_array($value, $array)) {
                            return $value;
                        }
                        return null;
                    }
                ',
            ],
            'valueOfUnreplacedTemplateParam' => [
                'code' => '<?php
                    /**
                     * @template T as array<bool>
                     */
                    abstract class Foo {
                        /**
                         * @return value-of<T>
                         */
                        abstract public function getRandomValue(): bool;
                    }
                ',
            ],
            'valueOfNestedTemplates' => [
                'code' => '<?php
                    /**
                     * @template TValue
                     * @template TArray of array<TValue>
                     * @param TArray $array
                     * @return list<TValue>
                     */
                    function toList(array $array): array {
                        return array_values($array);
                    }',
            ],
            'valueOfTemplatePassedToUnionOfItsBound' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, int|string>
                     * @param T $array
                     * @param value-of<T> $v
                     */
                    function f(array $array, $v): void {
                        takesIntOrString($v);
                    }

                    function takesIntOrString(int|string $v): void {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'valueOfTemplateNarrowedByIdentity' => [
                'code' => '<?php
                    /** @param 3 $i */
                    function takesThree(int $i): void {}

                    /**
                     * @template T of array<string, int>
                     * @param T $array
                     * @param value-of<T> $v
                     * @return value-of<T>
                     */
                    function f(array $array, $v) {
                        if ($v === 3) {
                            takesThree($v);
                        }

                        return $v;
                    }',
            ],
            'valueOfTemplateNarrowedByStrictInArray' => [
                'code' => '<?php
                    /** @param 1|2 $i */
                    function takesOneOrTwo(int $i): void {}

                    /** @param 2 $i */
                    function takesTwo(int $i): void {}

                    /**
                     * @template T of array<string, int>
                     * @param T $array
                     * @param value-of<T> $v
                     * @return value-of<T>
                     */
                    function f(array $array, $v) {
                        if (in_array($v, [1, 2], true)) {
                            takesOneOrTwo($v);

                            if ($v !== 1) {
                                takesTwo($v);
                            }
                        }

                        return $v;
                    }',
            ],
            'valueOfTemplateNarrowedByIsInt' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, int|string>
                     * @param T $array
                     * @param value-of<T> $v
                     * @return value-of<T>
                     */
                    function f(array $array, $v) {
                        if (is_int($v)) {
                            takesInt($v);
                        } else {
                            takesString($v);
                        }

                        return $v;
                    }

                    function takesInt(int $i): void {}
                    function takesString(string $s): void {}',
            ],
            'valueOfTemplateNarrowedByIsString' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, int|string>
                     * @param T $array
                     * @param value-of<T> $v
                     * @return value-of<T>
                     */
                    function f(array $array, $v) {
                        if (is_string($v)) {
                            takesString($v);
                        } else {
                            takesInt($v);
                        }

                        return $v;
                    }

                    function takesInt(int $i): void {}
                    function takesString(string $s): void {}',
            ],
            'valueOfTemplateNarrowedByIsNumeric' => [
                'code' => '<?php
                    /** @param int|numeric-string $n */
                    function takesNumeric($n): void {}

                    /**
                     * @template T of array<string, int|string|bool>
                     * @param T $array
                     * @param value-of<T> $v
                     * @return value-of<T>
                     */
                    function f(array $array, $v) {
                        if (is_numeric($v)) {
                            takesNumeric($v);
                        }

                        return $v;
                    }',
            ],
            'valueOfTemplateNarrowedByInstanceof' => [
                'code' => '<?php
                    final class A {}
                    final class B {}

                    /**
                     * @template T of array<string, A|B>
                     * @param T $array
                     * @param value-of<T> $v
                     * @return value-of<T>
                     */
                    function f(array $array, $v) {
                        if ($v instanceof A) {
                            takesA($v);
                        } else {
                            takesB($v);
                        }

                        return $v;
                    }

                    function takesA(A $a): void {}
                    function takesB(B $b): void {}',
            ],
            'valueOfTemplateNarrowedByNotNull' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, int|null>
                     * @param T $array
                     * @param value-of<T> $v
                     * @return value-of<T>
                     */
                    function f(array $array, $v) {
                        if ($v !== null) {
                            takesInt($v);
                        }

                        return $v;
                    }

                    function takesInt(int $i): void {}',
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
            'valueOfTemplateIdenticalToImpossibleLiteral' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, int>
                     * @param T $array
                     * @param value-of<T> $v
                     */
                    function f(array $array, $v): void {
                        if ($v === "a") {}
                    }
                ',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'valueOfTemplateNotIncludesString' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     * @return value-of<T>
                     */
                    function getValue($array) {
                        return "foo";
                    }
                ',
                'error_message' => 'InvalidReturnStatement',
            ],
            'valueOfTemplateNotIncludesInt' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     * @return value-of<T>
                     */
                    function getValue($array) {
                        return 0;
                    }
                ',
                'error_message' => 'InvalidReturnStatement',
            ],
            'valueOfUnresolvedTemplateParamIsStillChecked' => [
                'code' => '<?php
                    /**
                     * @template T as array<bool>
                     */
                    abstract class Foo {
                        /**
                         * @return value-of<T>
                         */
                        abstract public function getRandomValue(): string;
                    }
                ',
                'error_message' => 'MismatchingDocblockReturnType',
            ],
        ];
    }
}
