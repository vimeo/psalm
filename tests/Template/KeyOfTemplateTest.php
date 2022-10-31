<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class KeyOfTemplateTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{code:string,assertions?:array<string,string>,ignored_issues?:list<string>,php_version?:string}>
     */
    public function providerValidCodeParse(): iterable
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
                '
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
                '
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
                '
            ],
            'SKIPPED-acceptsIfArrayKeyExistsFn' => [
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
                '
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
                    }'
            ],
        ];
    }

    /**
     * @return iterable<string,array{code:string,error_message:string,ignored_issues?:list<string>,php_version?:string}>
     */
    public function providerInvalidCodeParse(): iterable
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
                'error_message' => 'InvalidReturnStatement'
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
                'error_message' => 'InvalidReturnStatement'
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
                'error_message' => 'MismatchingDocblockReturnType'
            ],
        ];
    }
}
