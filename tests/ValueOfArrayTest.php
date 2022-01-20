<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ValueOfArrayTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{code:string,assertions?:array<string,string>,ignored_issues?:list<string>,php_version?:string}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'valueOfListClassConstant' => [
                'code' => '<?php
                    class A {
                        const FOO = [
                            "bar"
                        ];
                        /** @return value-of<A::FOO> */
                        public function getKey() {
                            return "bar";
                        }
                    }
                '
            ],
            'valueOfAssociativeArrayClassConstant' => [
                'code' => '<?php
                    class A {
                        const FOO = [
                            "bar" => 42
                        ];
                        /** @return value-of<A::FOO> */
                        public function getValue() {
                            return 42;
                        }
                    }
                '
            ],
            'allValuesOfAssociativeArrayPossible' => [
                'code' => '<?php
                    class A {
                        const FOO = [
                            "bar" => 42,
                            "adams" => 43,
                        ];
                        /** @return value-of<A::FOO> */
                        public function getValue(bool $adams) {
                            if ($adams) {
                                return 42;
                            }
                            return 43;
                        }
                    }
                '
            ],
            'valueOfAsArray' => [
                'code' => '<?php
                    class A {
                        /** @var array */
                        const FOO = [
                            "bar" => 42,
                            "adams" => 43,
                        ];
                        /** @return value-of<self::FOO>[] */
                        public function getValues() {
                            return array_values(self::FOO);
                        }
                    }
                '
            ],
            'valueOfArrayLiteral' => [
                'code' => '<?php
                    /**
                     * @return value-of<array<int, string>>
                     */
                    function getKey() {
                        return "42";
                    }
                '
            ],
            'valueOfUnionArrayLiteral' => [
                'code' => '<?php
                    /**
                     * @return value-of<array<array-key, int>|array<string, float>>
                     */
                    function getValue(bool $asFloat) {
                        if ($asFloat) {
                            return 42.0;
                        }
                        return 42;
                    }
                '
            ],
            'valueOfStringArrayConformsToString' => [
                'code' => '<?php
                    /**
                     * @return string
                     */
                    function getKey2() {
                        /** @var value-of<array<string>>[] */
                        $keys2 = ["foo"];
                        return $keys2[0];
                    }
                '
            ],
            'acceptLiteralIntInValueOfUnionLiteralInts' => [
                'code' => '<?php
                    /**
                     * @return value-of<list<0|1|2>|array{0: 3, 1: 4}>
                     */
                    function getValue(int $i) {
                        if ($i >= 0 && $i <= 4) {
                            return $i;
                        }
                        return 0;
                    }
                ',
            ],
        ];
    }

    /**
     * @return iterable<string,array{code:string,error_message:string,ignored_issues?:list<string>,php_version?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'onlyDefinedValuesOfConstantList' => [
                'code' => '<?php
                    class A {
                        const FOO = [
                            "bar"
                        ];
                        /** @return key-of<A::FOO> */
                        public function getValue() {
                            return "adams";
                        }
                    }
                ',
                'error_message' => 'InvalidReturnStatement'
            ],
            'noIntForValueOfStringArrayLiteral' => [
                'code' => '<?php
                    class A {
                        /**
                         * @return value-of<array<int, string>>
                         */
                        public function getValue() {
                            return 42;
                        }
                    }
                ',
                'error_message' => 'InvalidReturnStatement'
            ],
            'noStringForValueOfIntList' => [
                'code' => '<?php
                    class A {
                        /**
                         * @return value-of<list<int>>
                         */
                        public function getValue() {
                            return "42";
                        }
                    }
                ',
                'error_message' => 'InvalidReturnStatement'
            ],
            'noOtherStringAllowedForValueOfKeyedArray' => [
                'code' => '<?php
                    /**
                     * @return value-of<array{a: "foo", b: "bar"}>
                     */
                    function getValue() {
                        return "adams";
                    }
                ',
                'error_message' => 'InvalidReturnStatement'
            ],
            'noOtherIntAllowedInValueOfUnionLiteralInts' => [
                'code' => '<?php
                    /**
                     * @return value-of<list<0|1|2>|array{0: 3, 1: 4}>
                     */
                    function getValue() {
                        return 5;
                    }
                ',
                'error_message' => 'InvalidReturnStatement'
            ],
        ];
    }
}
