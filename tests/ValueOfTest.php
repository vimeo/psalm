<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ValueOfTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     *
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
                     * @return value-of<list<0|1|2>|strict-array{0: 3, 1: 4}>
                     */
                    function getValue(int $i) {
                        if ($i >= 0 && $i <= 4) {
                            return $i;
                        }
                        return 0;
                    }
                ',
            ],
            'valueOfExpandsPropertiesOf' => [
                'code' => '<?php
                    class A {
                        /** @var bool */
                        public $foo = false;
                        /** @var string */
                        private $bar = "";
                        /** @var int */
                        protected $adams = 42;
                    }

                    /** @return list<value-of<properties-of<A>>> */
                    function returnPropertyOfA() {
                        return [true, "bar", 42];
                    }
                ',
            ],
            'valueOfStringEnum' => [
                'code' => '<?php
                    enum Foo: string
                    {
                        case Foo = "foo";
                        case Bar = "bar";
                    }

                    /** @param value-of<Foo> $arg */
                    function foobar(string $arg): void
                    {
                        /** @psalm-check-type-exact $arg = "foo"|"bar" */;
                    }

                    /** @var Foo */
                    $foo = Foo::Foo;
                    foobar($foo->value);
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'valueOfIntEnum' => [
                'code' => '<?php
                    enum Foo: int
                    {
                        case Foo = 2;
                        case Bar = 3;
                    }

                    /** @param value-of<Foo> $arg */
                    function foobar(int $arg): void
                    {
                        /** @psalm-check-type-exact $arg = 2|3 */;
                    }

                    /** @var Foo */
                    $foo = Foo::Foo;
                    foobar($foo->value);
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'valueOfEnumUnion' => [
                'code' => '<?php
                    enum Foo: int
                    {
                        case Foo = 2;
                        case Bar = 3;
                    }

                    enum Bar: string
                    {
                        case Foo = "foo";
                        case Bar = "bar";
                    }

                    /** @param value-of<Foo|Bar> $arg */
                    function foobar(int|string $arg): void
                    {
                        /** @psalm-check-type-exact $arg = 2|3|"foo"|"bar" */;
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
        ];
    }

    /**
     *
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
                     * @return value-of<strict-array{a: "foo", b: "bar"}>
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
                     * @return value-of<list<0|1|2>|strict-array{0: 3, 1: 4}>
                     */
                    function getValue() {
                        return 5;
                    }
                ',
                'error_message' => 'InvalidReturnStatement'
            ],
            'valueOfUnitEnum' => [
                'code' => '<?php
                    enum Foo
                    {
                        case Foo;
                        case Bar;
                    }

                    /** @param value-of<Foo> $arg */
                    function foobar(string $arg): void {}
                ',
                // TODO turn this into an InvalidDocblock with a better error message. This is difficult because it
                // has to happen after scanning has finished, otherwise the class might not have been scanned yet.
                'error_message' => 'MismatchingDocblockParamType',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
        ];
    }
}
