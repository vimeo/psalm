<?php
namespace Psalm\Tests\Template;

use const DIRECTORY_SEPARATOR;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits;

class ConditionalReturnTypeTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'conditionalReturnType' => [
                '<?php

                    class A {
                        /** @var array<string, string> */
                        private array $itemAttr = [];

                        /**
                         * @template T as ?string
                         * @param T $name
                         * @return string|string[]
                         * @psalm-return (T is string ? string : array<string, string>)
                         */
                        public function getAttribute(?string $name, string $default = "")
                        {
                            if (null === $name) {
                                return $this->itemAttr;
                            }
                            return isset($this->itemAttr[$name]) ? $this->itemAttr[$name] : $default;
                        }
                    }

                    $a = (new A)->getAttribute("colour", "red"); // typed as string
                    $b = (new A)->getAttribute(null); // typed as array<string, string>
                    /** @psalm-suppress MixedArgument */
                    $c = (new A)->getAttribute($_GET["foo"]); // typed as string|array<string, string>',
                [
                    '$a' => 'string',
                    '$b' => 'array<string, string>',
                    '$c' => 'array<string, string>|string'
                ]
            ],
            'nestedConditionalOnIntReturnType' => [
                '<?php
                    /**
                     * @template T as int
                     * @param T $i
                     * @psalm-return (T is 0 ? string : (T is 1 ? int : bool))
                     */
                    function getDifferentType(int $i) {
                        if ($i === 0) {
                            return "hello";
                        }

                        if ($i === 1) {
                            return 5;
                        }

                        return true;
                    }'
            ],
            'nestedConditionalOnStringsReturnType' => [
                '<?php
                    /**
                     * @template T as string
                     * @param T $i
                     * @psalm-return (T is "0" ? string : (T is "1" ? int : bool))
                     */
                    function getDifferentType(string $i) {
                        if ($i === "0") {
                            return "hello";
                        }

                        if ($i === "1") {
                            return 5;
                        }

                        return true;
                    }'
            ],
            'nestedConditionalOnClassStringsReturnType' => [
                '<?php
                    class A {}
                    class B {}

                    /**
                     * @template T as string
                     * @param T $i
                     * @psalm-return (T is A::class ? string : (T is B::class ? int : bool))
                     */
                    function getDifferentType(string $i) {
                        if ($i === A::class) {
                            return "hello";
                        }

                        if ($i === B::class) {
                            return 5;
                        }

                        return true;
                    }'
            ],
            'userlandVarExport' => [
                '<?php
                    /**
                     * @template TReturnFlag as bool
                     * @param mixed $expression
                     * @param TReturnFlag $return
                     * @psalm-return (TReturnFlag is true ? string : void)
                     */
                    function my_var_export($expression, bool $return = false) {
                        if ($return) {
                            return var_export($expression, true);
                        }

                        var_export($expression);
                    }'
            ],
            'userlandAddition' => [
                '<?php
                    /**
                     * @template T as int|float
                     * @param T $a
                     * @param T $b
                     * @return int|float
                     * @psalm-return (T is int ? int : float)
                     */
                    function add($a, $b) {
                        return $a + $b;
                    }

                    $int = add(3, 5);
                    $float1 = add(2.5, 3);
                    $float2 = add(2.7, 3.1);
                    $float3 = add(3, 3.5);
                    /** @psalm-suppress PossiblyNullArgument */
                    $int = add(rand(0, 1) ? null : 1, 1);',
                [
                    '$int' => 'int',
                    '$float1' => 'float',
                    '$float2' => 'float',
                    '$float3' => 'float',
                ]
            ],
            'possiblyNullArgumentStillMatchesType' => [
                '<?php
                    /**
                     * @template T as int|float
                     * @param T $a
                     * @param T $b
                     * @return int|float
                     * @psalm-return (T is int ? int : float)
                     */
                    function add($a, $b) {
                        return $a + $b;
                    }

                    /** @psalm-suppress PossiblyNullArgument */
                    $int = add(rand(0, 1) ? null : 1, 4);',
                [
                    '$int' => 'int',
                ]
            ],
            'nestedClassConstantConditionalComparison' => [
                '<?php
                    class A {
                        const TYPE_STRING = 0;
                        const TYPE_INT = 1;

                        /**
                         * @template T as int
                         * @param T $i
                         * @psalm-return (
                         *     T is self::TYPE_STRING
                         *     ? string
                         *     : (T is self::TYPE_INT ? int : bool)
                         * )
                         */
                        public static function getDifferentType(int $i) {
                            if ($i === self::TYPE_STRING) {
                                return "hello";
                            }

                            if ($i === self::TYPE_INT) {
                                return 5;
                            }

                            return true;
                        }
                    }

                    $string = A::getDifferentType(0);
                    $int = A::getDifferentType(1);
                    $bool = A::getDifferentType(4);
                    $string2 = (new A)->getDifferentType(0);
                    $int2 = (new A)->getDifferentType(1);
                    $bool2 = (new A)->getDifferentType(4);',
                [
                    '$string' => 'string',
                    '$int' => 'int',
                    '$bool' => 'bool',
                    '$string2' => 'string',
                    '$int2' => 'int',
                    '$bool2' => 'bool',
                ]
            ],
            'variableConditionalSyntax' => [
                '<?php
                    /**
                     * @psalm-return ($i is 0 ? string : ($i is 1 ? int : bool))
                     */
                    function getDifferentType(int $i) {
                        if ($i === 0) {
                            return "hello";
                        }

                        if ($i === 1) {
                            return 5;
                        }

                        return true;
                    }'
            ],
            'variableConditionalSyntaxWithNewlines' => [
                '<?php
                    /**
                     * @psalm-return (
                     *      $i is 0
                     *      ? string
                     *      : (
                     *          $i is 1
                     *          ? int
                     *          : bool
                     *      )
                     *  )
                     */
                    function getDifferentType(int $i) {
                        if ($i === 0) {
                            return "hello";
                        }

                        if ($i === 1) {
                            return 5;
                        }

                        return true;
                    }'
            ],
        ];
    }
}
