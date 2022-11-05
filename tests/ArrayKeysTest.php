<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ArrayKeysTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     *
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'arrayKeysOfEmptyArrayReturnsListOfEmpty' => [
                'code' => '<?php
                    $keys = array_keys([]);
                ',
                'assertions' => [
                    '$keys' => 'list<never>',
                ],
            ],
            'arrayKeysOfKeyedArrayReturnsNonEmptyListOfStrings' => [
                'code' => '<?php
                    $keys = array_keys(["foo" => "bar"]);
                ',
                'assertions' => [
                    '$keys' => 'non-empty-list<string>',
                ],
            ],
            'arrayKeysOfListReturnsNonEmptyListOfInts' => [
                'code' => '<?php
                    $keys = array_keys(["foo", "bar"]);
                ',
                'assertions' => [
                    '$keys' => 'non-empty-list<int>',
                ],
            ],
            'arrayKeysOfKeyedStringIntArrayReturnsNonEmptyListOfIntsOrStrings' => [
                'code' => '<?php
                    $keys = array_keys(["foo" => "bar", 42]);
                ',
                'assertions' => [
                    '$keys' => 'non-empty-list<int|string>',
                ],
            ],
            'arrayKeysOfArrayConformsToArrayKeys' => [
                'code' => '<?php
                    /**
                     * @return list<array-key>
                     */
                    function getKeys(array $array) {
                        return array_keys($array);
                    }
                '
            ],
            'arrayKeysOfKeyedArrayConformsToCorrectLiteralStringList' => [
                'code' => '<?php
                    /**
                     * @return non-empty-list<"foo"|"bar">
                     */
                    function getKeys() {
                        return array_keys(["foo" => 42, "bar" => 42]);
                    }
                '
            ],
            'arrayKeysOfLiteralListConformsToCorrectLiteralOffsets' => [
                'code' => '<?php
                    /**
                     * @return non-empty-list<0|1>
                     */
                    function getKeys() {
                        return array_keys(["foo", "bar"]);
                    }
                '
            ],
            'arrayKeyFirstOfLiteralListConformsToCorrectLiteralOffsets' => [
                'code' => '<?php
                    /**
                     * @return 0|1
                     */
                    function getKey() {
                        return array_key_first(["foo", "bar"]);
                    }
                '
            ],
            'arrayKeyLastOfLiteralListConformsToCorrectLiteralOffsets' => [
                'code' => '<?php
                    /**
                     * @return 0|1
                     */
                    function getKey() {
                        return array_key_last(["foo", "bar"]);
                    }
                '
            ],
        ];
    }

    /**
     *
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'arrayKeysOfStringArrayDoesntConformsToIntList' => [
                'code' => '<?php
                    /**
                     * @param array<string, mixed> $array
                     * @return list<int>
                     */
                    function getKeys(array $array) {
                        return array_keys($array);
                    }
                ',
                'error_message' => 'InvalidReturnStatement'
            ],
            'arrayKeysOfStringKeyedArrayDoesntConformToIntList' => [
                'code' => '<?php
                    /**
                     * @return list<int>
                     */
                    function getKeys() {
                        return array_keys(["foo" => 42, "bar" => 42]);
                    }
                ',
                'error_message' => 'InvalidReturnStatement'
            ]
        ];
    }
}
