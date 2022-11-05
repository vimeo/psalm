<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class KeyOfArrayTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     *
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'keyOfListClassConstant' => [
                'code' => '<?php
                    class A {
                        const FOO = [
                            "bar"
                        ];
                        /** @return key-of<A::FOO> */
                        public function getKey() {
                            return 0;
                        }
                    }
                '
            ],
            'keyOfAssociativeArrayClassConstant' => [
                'code' => '<?php
                    class A {
                        const FOO = [
                            "bar" => 42
                        ];
                        /** @return key-of<A::FOO> */
                        public function getKey() {
                            return "bar";
                        }
                    }
                '
            ],
            'allKeysOfAssociativeArrayPossible' => [
                'code' => '<?php
                    class A {
                        const FOO = [
                            "bar" => 42,
                            "adams" => 43,
                        ];
                        /** @return key-of<A::FOO> */
                        public function getKey(bool $adams) {
                            if ($adams) {
                                return "adams";
                            }
                            return "bar";
                        }
                    }
                '
            ],
            'keyOfAsArray' => [
                'code' => '<?php
                    class A {
                        /** @var array */
                        const FOO = [
                            "bar" => 42,
                            "adams" => 43,
                        ];
                        /** @return key-of<self::FOO>[] */
                        public function getKey() {
                            return array_keys(self::FOO);
                        }
                    }
                '
            ],
            'keyOfArrayLiteral' => [
                'code' => '<?php
                    /**
                     * @return key-of<array<int, string>>
                     */
                    function getKey() {
                        return 32;
                    }
                '
            ],
            'keyOfUnionArrayLiteral' => [
                'code' => '<?php
                    /**
                     * @return key-of<array<int, string>|array<float, string>>
                     */
                    function getKey(bool $asFloat) {
                        if ($asFloat) {
                            return 42.0;
                        }
                        return 42;
                    }
                '
            ],
            'keyOfUnionListAndKeyedArray' => [
                'code' => '<?php
                    /**
                     * @return key-of<list<int>|strict-array{a: int, b: int}>
                     */
                    function getKey(bool $asInt) {
                        if ($asInt) {
                            return 42;
                        }
                        return "a";
                    }
                ',
            ],
            'keyOfListArrayLiteral' => [
                'code' => '<?php
                    /**
                     * @return key-of<list<string>>
                     */
                    function getKey() {
                        return 42;
                    }
                '
            ],
            'keyOfStringArrayConformsToString' => [
                'code' => '<?php
                    /**
                     * @return string
                     */
                    function getKey2() {
                        /** @var key-of<array<string, string>>[] */
                        $keys2 = ["foo"];
                        return $keys2[0];
                    }
                '
            ],
            'keyOfExpandsPropertiesOf' => [
                'code' => '<?php
                    class A {
                        /** @var bool */
                        public $foo = false;
                        /** @var string */
                        private $bar = "";
                        /** @var int */
                        protected $adams = 42;
                    }

                    /** @return list<key-of<properties-of<A>>> */
                    function returnPropertyOfA() {
                        return ["foo", "bar", "adams"];
                    }
                ',
            ],
        ];
    }

    /**
     *
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'onlyDefinedKeysOfAssociativeArray' => [
                'code' => '<?php
                    class A {
                        const FOO = [
                            "bar" => 42
                        ];
                        /** @return key-of<A::FOO> */
                        public function getKey() {
                            return "adams";
                        }
                    }
                ',
                'error_message' => 'InvalidReturnStatement'
            ],
            'keyOfArrayLiteral' => [
                'code' => '<?php
                    class A {
                        /**
                         * @return key-of<array<int, string>>
                         */
                        public function getKey() {
                            return "foo";
                        }
                    }
                ',
                'error_message' => 'InvalidReturnStatement'
            ],
            'onlyIntAllowedForKeyOfList' => [
                'code' => '<?php
                    class A {
                        /**
                         * @return key-of<list<string>>
                         */
                        public function getKey() {
                            return "42";
                        }
                    }
                ',
                'error_message' => 'InvalidReturnStatement'
            ],
            'noStringAllowedInKeyOfIntFloatArray' => [
                'code' => '<?php
                    /**
                     * @return key-of<array<int, string>|array<float, string>>
                     */
                    function getKey(bool $asFloat) {
                        if ($asFloat) {
                            return 42.0;
                        }
                        return "42";
                    }
                ',
                'error_message' => 'InvalidReturnStatement'
            ],
            'noLiteralCAllowedInKeyOfUnionListAndKeyedArray' => [
                'code' => '<?php
                    /**
                     * @return key-of<list<int>|strict-array{a: int, b: int}>
                     */
                    function getKey() {
                        return "c";
                    }
                ',
                'error_message' => 'InvalidReturnStatement'
            ],
        ];
    }
}
