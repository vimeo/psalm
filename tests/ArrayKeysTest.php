<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class ArrayKeysTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'arrayKeysOfEmptyArrayReturnsListOfEmpty' => [
                'code' => '<?php
                    $keys = array_keys([]);
                ',
                'assertions' => [
                    '$keys' => 'array<never, never>',
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
                    '$keys' => 'non-empty-list<int<0, 1>>',
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
                ',
            ],
            'arrayKeysOfKeyedArrayConformsToCorrectLiteralStringList' => [
                'code' => '<?php
                    /**
                     * @return non-empty-list<"foo"|"bar">
                     */
                    function getKeys() {
                        return array_keys(["foo" => 42, "bar" => 42]);
                    }
                ',
            ],
            'arrayKeysOfLiteralListConformsToCorrectLiteralOffsets' => [
                'code' => '<?php
                    /**
                     * @return non-empty-list<0|1>
                     */
                    function getKeys() {
                        return array_keys(["foo", "bar"]);
                    }
                ',
            ],
            'arrayKeyFirstOfLiteralListConformsToCorrectLiteralOffsets' => [
                'code' => '<?php
                    /**
                     * @return 0|1
                     */
                    function getKey() {
                        return array_key_first(["foo", "bar"]);
                    }
                ',
            ],
            'arrayKeyLastOfLiteralListConformsToCorrectLiteralOffsets' => [
                'code' => '<?php
                    /**
                     * @return 0|1
                     */
                    function getKey() {
                        return array_key_last(["foo", "bar"]);
                    }
                ',
            ],
            'literalStringAsIntArrayKey' => [
                'code' => '<?php
                    class a {
                        private const REDIRECTS = [
                            "a" => [
                                "from" => "79268724911",
                                "to" => "74950235931",
                            ],
                            "b" => [
                                "from" => "79313044964",
                                "to" => "78124169167",
                            ],
                        ];

                        private const SIP_FORMAT = "sip:%s@voip.test.com:9090";

                        /** @return array<int, string> */
                        public function test(): array {
                            $redirects = [];
                            foreach (self::REDIRECTS as $redirect) {
                                $redirects[$redirect["from"]] = sprintf(self::SIP_FORMAT, $redirect["to"]);
                            }

                            return $redirects;
                        }
                    }',
            ],
            'variousArrayKeys' => [
                'code' => '<?php
                    /**
                     * @psalm-type TAlias = 123
                     */
                    class a {}

                    /**
                     * @psalm-import-type TAlias from a
                     * @template TKey as array-key
                     * @template TValue as array-key
                     * @template T as array<TKey, TValue>
                     *
                     * @template TOrig as a|b
                     * @template TT as class-string<TOrig>
                     *
                     * @template TBool as bool
                     */
                    class b {
                        /**
                         * @var array<TAlias, int>
                         */
                        private array $a = [123 => 123];

                        /** @var array<value-of<T>, int> */
                        public array $c = [];

                        /** @var array<key-of<T>, int> */
                        public array $d = [];

                        /** @var array<TT, int> */
                        public array $e = [];

                        /** @var array<key-of<array<int, string>>, int> */
                        private array $f = [123 => 123];

                        /** @var array<value-of<array<int, string>>, int> */
                        private array $g = ["test" => 123];

                        /** @var array<TBool is true ? string : int, int> */
                        private array $h = [123 => 123];

                        /**
                         * @return array<$v is true ? "a" : 123, 123>
                         */
                        public function test(bool $v): array {
                            return $v ? ["a" => 123] : [123 => 123];
                        }
                    }

                    /** @var b<"testKey", "testValue", array<"testKey", "testValue">, b, class-string<b>, true> */
                    $b = new b;
                    $b->d["testKey"] = 123;

                    // TODO
                    //$b->c["testValue"] = 123;
                    //$b->e["b"] = 123;
                    ',
            ],
            'intStringKeyAsInt' => [
                'code' => '<?php
                    $a = ["15" => "a"];
                    $b = ["15.7" => "a"];
                    // since PHP 8 this is_numeric but will not be int key
                    $c = ["15 " => "a"];
                    $d = ["-15" => "a"];
                    // see https://github.com/php/php-src/issues/9029#issuecomment-1186226676
                    $e = ["+15" => "a"];
                    $f = ["015" => "a"];
                    $g = ["1e2" => "a"];
                    $h = ["1_0" => "a"];
                    ',
                'assertions' => [
                    '$a===' => "array{15: 'a'}",
                    '$b===' => "array{'15.7': 'a'}",
                    '$c===' => "array{'15 ': 'a'}",
                    '$d===' => "array{-15: 'a'}",
                    '$e===' => "array{'+15': 'a'}",
                    '$f===' => "array{'015': 'a'}",
                    '$g===' => "array{'1e2': 'a'}",
                    '$h===' => "array{'1_0': 'a'}",
                ],
            ],
        ];
    }

    #[Override]
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
                'error_message' => 'InvalidReturnStatement',
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
                'error_message' => 'InvalidReturnStatement',
            ],
            'literalStringAsIntArrayKey' => [
                'code' => '<?php
                    class a {
                        private const REDIRECTS = [
                            "a" => [
                                "from" => "79268724911",
                                "to" => "74950235931",
                            ],
                            "b" => [
                                "from" => "79313044964",
                                "to" => "78124169167",
                            ],
                        ];

                        private const SIP_FORMAT = "sip:%s@voip.test.com:9090";

                        /** @return array<string, string> */
                        public function test(): array {
                            $redirects = [];
                            foreach (self::REDIRECTS as $redirect) {
                                $redirects[$redirect["from"]] = sprintf(self::SIP_FORMAT, $redirect["to"]);
                            }

                            return $redirects;
                        }
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
        ];
    }
}
