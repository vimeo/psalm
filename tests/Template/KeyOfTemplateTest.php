<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class KeyOfTemplateTest extends TestCase
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
            'acceptsArrayKeysFn' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     * @return key-of<T>[]
                     */
                    function getKey($array) {
                        return array_keys($array);
                    }
                ',
            ],
            'mixedKeyOf' => [
                'code' => '<?php
                    /** @var array<int>|mixed */
                    $a = [];

                    /** @psalm-suppress MixedArgument */
                    $a = array_keys($a);',
                'assertions' => [
                    '$a' => 'list<array-key>',
                ],
            ],
            'acceptsArrayKeyFirstFn' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     * @return key-of<T>|null
                     */
                    function getKey($array) {
                        return array_key_first($array);
                    }
                ',
            ],
            'acceptsArrayKeyLastFn' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     * @return key-of<T>|null
                     */
                    function getKey($array) {
                        return array_key_last($array);
                    }
                ',
            ],
            'acceptsIfArrayKeyExistsFn' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     * @return key-of<T>|null
                     */
                    function getKey(string $key, $array) {
                        if (array_key_exists($key, $array)) {
                            return $key;
                        }
                        return null;
                    }
                ',
            ],
            'issetOfAnOffsetOfATemplateArrayNarrowsTheKeyToKeyOf' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     * @return key-of<T>|null
                     */
                    function getKey(string $key, $array) {
                        if (isset($array[$key])) {
                            return $key;
                        }
                        return null;
                    }

                    /**
                     * @template T of array<string, array>
                     * @param T $array
                     * @return key-of<T>|null
                     */
                    function getKeyOfNested(string $key, $array) {
                        if (isset($array[$key]["x"])) {
                            return $key;
                        }
                        return null;
                    }

                    /**
                     * @template T of array
                     * @param T $array
                     * @param key-of<T> $key
                     * @return key-of<T>
                     */
                    function notSetMayStillBeAKey($array, $key) {
                        if (!isset($array[$key])) {
                            return $key;
                        }
                        return $key;
                    }
                ',
            ],
            'useAfterArrayKeyExistsOnTemplate' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     * @param list<int> $other
                     */
                    function f(int $key, $array, array $other): int {
                        if (array_key_exists($key, $array)) {
                            echo $key . "x";
                            return ($other[$key] ?? 0) + $key * 2;
                        }
                        return 0;
                    }
                ',
            ],
            'typeCheckAfterArrayKeyExistsOnTemplate' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     */
                    function f(int $key, $array): void {
                        if (array_key_exists($key, $array)) {
                            if ($key === 0) {
                                echo "first";
                            }
                        }
                    }
                ',
            ],
            'arrayKeyExistsOnTemplateKeepsKeyType' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     * @return key-of<T>|null
                     */
                    function getKey(string $key, $array) {
                        if (array_key_exists($key, $array)) {
                            echo strlen($key);
                            return $key;
                        }
                        echo strlen($key);
                        return null;
                    }
                ',
            ],
            'negatedKeyExistsOnShapeTemplate' => [
                'code' => '<?php
                    /**
                     * @template T of array{a: int, b: int}
                     * @param T $array
                     * @return key-of<T>
                     */
                    function getKey(int|string $key, $array) {
                        if (!key_exists($key, $array)) {
                            throw new Exception();
                        }
                        return $key;
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'arrayKeyExistsOnTemplateWithIntKeysKeepsStringKey' => [
                'code' => '<?php
                    /**
                     * @template T of array<int, string>
                     * @param T $array
                     */
                    function getValue(string $key, $array): string {
                        if (array_key_exists($key, $array)) {
                            return $key;
                        }
                        return "";
                    }
                ',
            ],
            'keyOfUnreplacedTemplateParam' => [
                'code' => '<?php
                    /**
                     * @template T as array<string, bool>
                     */
                    abstract class Foo {
                        /**
                         * @return key-of<T>
                         */
                        abstract public function getRandomKey(): string;
                    }
                ',
            ],
            'keyOfNestedTemplates' => [
                'code' => '<?php
                    /**
                     * @template TKey of int
                     * @template TArray of array<TKey, bool>
                     * @param TArray $array
                     * @return list<TKey>
                     */
                    function toListOfKeys(array $array): array {
                        return array_keys($array);
                    }',
            ],
            'keyOfTemplateNarrowedByIdentity' => [
                'code' => '<?php
                    /** @param "a" $s */
                    function takesA(string $s): void {}

                    /**
                     * @template T of array<string, int>
                     * @param T $array
                     * @param key-of<T> $k
                     * @return key-of<T>
                     */
                    function f(array $array, $k) {
                        if ($k === "a") {
                            takesA($k);
                        }

                        return $k;
                    }',
            ],
            'keyOfTemplateNarrowedByNotIdentical' => [
                'code' => '<?php
                    /** @param "b" $s */
                    function takesB(string $s): void {}

                    /**
                     * @template T of array<"a"|"b", int>
                     * @param T $array
                     * @param key-of<T> $k
                     * @return key-of<T>
                     */
                    function f(array $array, $k) {
                        if ($k !== "a") {
                            takesB($k);
                        }

                        return $k;
                    }',
            ],
            'keyOfTemplateNarrowedByIdentityAlternatives' => [
                'code' => '<?php
                    /** @param "a"|"b" $s */
                    function takesAOrB(string $s): void {}

                    /**
                     * @template T of array<string, int>
                     * @param T $array
                     * @param key-of<T> $k
                     * @return key-of<T>
                     */
                    function f(array $array, $k) {
                        if ($k === "a" || $k === "b") {
                            takesAOrB($k);
                        }

                        return $k;
                    }',
            ],
            'keyOfTemplateNarrowedByIsInt' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     * @param key-of<T> $k
                     * @return key-of<T>
                     */
                    function f(array $array, $k) {
                        if (is_int($k)) {
                            takesInt($k);
                        } else {
                            takesString($k);
                        }

                        return $k;
                    }

                    function takesInt(int $i): void {}
                    function takesString(string $s): void {}',
            ],
            'keyOfTemplateNarrowedByIsString' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     * @param key-of<T> $k
                     * @return key-of<T>
                     */
                    function f(array $array, $k) {
                        if (is_string($k)) {
                            takesString($k);
                        } else {
                            takesInt($k);
                        }

                        return $k;
                    }

                    function takesInt(int $i): void {}
                    function takesString(string $s): void {}',
            ],
            'keyOfTemplateNarrowedByIsNumeric' => [
                'code' => '<?php
                    /** @param numeric-string $s */
                    function takesNumericString(string $s): void {}

                    /**
                     * @template T of array<string, int>
                     * @param T $array
                     * @param key-of<T> $k
                     * @return key-of<T>
                     */
                    function f(array $array, $k) {
                        if (is_numeric($k)) {
                            takesNumericString($k);
                        }

                        return $k;
                    }',
            ],
            'keyOfTemplateNarrowedByTruthiness' => [
                'code' => '<?php
                    /** @param non-falsy-string $s */
                    function takesNonFalsyString(string $s): void {}

                    /**
                     * @template T of array<string, int>
                     * @param T $array
                     * @param key-of<T> $k
                     * @return key-of<T>
                     */
                    function f(array $array, $k) {
                        if ($k) {
                            takesNonFalsyString($k);
                        }

                        return $k;
                    }',
            ],
            'keyOfTemplateInUnionNarrowedByIsInt' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, int>
                     * @param T $array
                     * @param key-of<T>|int $k
                     * @return key-of<T>
                     */
                    function f(array $array, $k) {
                        if (is_int($k)) {
                            takesInt($k);

                            throw new Exception();
                        }

                        return $k;
                    }

                    function takesInt(int $i): void {}',
            ],
            'keyOfTemplateOrNullNarrowedByNotNull' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, int>
                     * @param T $array
                     * @param key-of<T>|null $k
                     * @return key-of<T>
                     */
                    function f(array $array, $k) {
                        if ($k !== null) {
                            return $k;
                        }

                        throw new Exception();
                    }',
            ],
            'keyOfTemplateInArithmetic' => [
                'code' => '<?php
                    /**
                     * @template T of array<int, string>
                     * @param T $array
                     * @param key-of<T> $k
                     */
                    function f(array $array, $k): int {
                        $r = $k * 2;
                        /** @psalm-check-type-exact $r = int */;
                        return $r;
                    }',
            ],
            'keyOfTemplateIncremented' => [
                'code' => '<?php
                    /**
                     * @template T of array<int, string>
                     * @param T $array
                     * @param key-of<T> $k
                     */
                    function f(array $array, $k): int {
                        $k++;
                        return $k;
                    }',
            ],
            'keyOfTemplateConcatenated' => [
                'code' => '<?php
                    /**
                     * @template T of array{a: int, b: int}
                     * @param T $array
                     * @param key-of<T> $k
                     */
                    function f(array $array, $k): string {
                        $r = $k . "x";
                        /** @psalm-check-type-exact $r = "ax"|"bx" */;
                        return $r;
                    }',
            ],
            'keyOfTemplateCastToString' => [
                'code' => '<?php
                    /**
                     * @template T of array<int, string>
                     * @param T $array
                     * @param key-of<T> $k
                     */
                    function f(array $array, $k): string {
                        return (string) $k;
                    }',
            ],
            'keyOfTemplateAsOffsetOfAnotherArray' => [
                'code' => '<?php
                    /**
                     * @template T of array<int, string>
                     * @param T $array
                     * @param key-of<T> $k
                     * @param array<int, string> $other
                     * @return array<int, string>
                     */
                    function f(array $array, $k, array $other): array {
                        echo $other[$k] ?? "";
                        $other[$k] = "x";
                        return $other;
                    }',
            ],
            'keyOfTemplateStringOffset' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, int>
                     * @param T $array
                     * @param key-of<T> $k
                     */
                    function f(array $array, $k): string {
                        return $k[0];
                    }',
            ],
            'templateBoundedByKeyOfTemplateConcatenated' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, int>
                     * @template K of key-of<T>
                     * @param T $array
                     * @param K $k
                     */
                    function f(array $array, $k): string {
                        return $k . "x";
                    }',
            ],
            'keyOfClassTemplateInArithmetic' => [
                'code' => '<?php
                    /**
                     * @template T of array<int, string>
                     */
                    final class C {
                        /**
                         * @param key-of<T> $k
                         */
                        public function next($k): int {
                            return $k + 1;
                        }
                    }',
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
            'keyOfTemplateIdenticalToImpossibleLiteral' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, int>
                     * @param T $array
                     * @param key-of<T> $k
                     */
                    function f(array $array, $k): void {
                        if ($k === 5) {}
                    }
                ',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'keyOfTemplateIsImpossibleType' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, int>
                     * @param T $array
                     * @param key-of<T> $k
                     */
                    function f(array $array, $k): void {
                        if (is_int($k)) {}
                    }
                ',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'keyOfTemplateNotIncludesString' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     * @return key-of<T>
                     */
                    function getKey($array) {
                        return "foo";
                    }
                ',
                'error_message' => 'InvalidReturnStatement',
            ],
            'arrayKeyExistsOnTemplateKeepsUnionKey' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     */
                    function getKey(int|string $key, $array): bool {
                        if (array_key_exists($key, $array)) {
                            return $key;
                        }
                        return false;
                    }
                ',
                'error_message' => "The inferred type 'key-of<T:fn-getkey as array<int|string, mixed",
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'arrayKeyExistsOnTemplateWithIntKeysKeepsNumericStringArm' => [
                'code' => '<?php
                    /**
                     * @template T of array<int, string>
                     * @param T $array
                     */
                    function getKey(int|string $key, $array): bool {
                        if (array_key_exists($key, $array)) {
                            return $key;
                        }
                        return false;
                    }
                ',
                'error_message' => "The inferred type 'int|string' does not match",
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'keyOfTemplateNotIncludesInt' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     * @return key-of<T>
                     */
                    function getKey($array) {
                        return 0;
                    }
                ',
                'error_message' => 'InvalidReturnStatement',
            ],
            'keyOfUnresolvedTemplateParamIsStillChecked' => [
                'code' => '<?php
                    /**
                     * @template T as array<int, bool>
                     */
                    abstract class Foo {
                        /**
                         * @return key-of<T>
                         */
                        abstract public function getRandomKey(): string;
                    }
                ',
                'error_message' => 'MismatchingDocblockReturnType',
            ],
            'keyOfTemplateWithStringKeysInArithmetic' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, int>
                     * @param T $array
                     * @param key-of<T> $k
                     */
                    function f(array $array, $k): void {
                        echo $k * 2;
                    }',
                'error_message' => 'Cannot perform a numeric operation with a non-numeric type string',
            ],
        ];
    }
}
