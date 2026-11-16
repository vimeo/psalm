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
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
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
        ];
    }
}
