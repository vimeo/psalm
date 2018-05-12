<?php
namespace Psalm\Tests;

class ConstantTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'constantInFunction' => [
                '<?php
                    useTest();
                    const TEST = 2;

                    function useTest(): int {
                        return TEST;
                    }',
            ],
            'constantInClosure' => [
                '<?php
                    const TEST = 2;

                    $useTest = function(): int {
                        return TEST;
                    };
                    $useTest();',
            ],
            'constantDefinedInFunction' => [
                '<?php
                    /**
                     * @return void
                     */
                    function defineConstant() {
                        define("CONSTANT", 1);
                    }

                    defineConstant();

                    echo CONSTANT;',
            ],
            'magicConstant' => [
                '<?php
                    $a = __LINE__;
                    $b = __file__;',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'string',
                ],
            ],
            'getClassConstantValue' => [
                '<?php
                    class A {
                        const B = [0, 1, 2];
                    }

                    $a = A::B[1];',
            ],
            'staticConstEval' => [
                '<?php
                    abstract class Enum {
                        /**
                         * @var string[]
                         */
                        protected const VALUES = [];
                        public static function export(): string
                        {
                            assert(!empty(static::VALUES));
                            $values = array_map(
                                function(string $val): string {
                                    return "\'" . $val . "\'";
                                },
                                static::VALUES
                            );
                            return join(",", $values);
                        }
                    }',
                'assertions' => [],
                'error_levels' => ['MixedArgument'],
            ],
            'undefinedConstant' => [
                '<?php
                    switch (rand(0, 50)) {
                        case FORTY: // Observed a valid UndeclaredConstant warning
                            $x = "value";
                            break;
                        default:
                            $x = "other";
                        }

                        echo $x;',
                'assertions' => [],
                'error_levels' => ['UndefinedConstant'],
            ],
            'suppressUndefinedClassConstant' => [
                '<?php
                    class C {}

                    /** @psalm-suppress UndefinedConstant */
                    $a = POTATO;

                    /** @psalm-suppress UndefinedConstant */
                    $a = C::POTATO;',
                'assertions' => [],
                'error_levels' => ['MixedAssignment'],
            ],
            'hardToDefineClassConstant' => [
                '<?php
                    class A {
                        const C = [
                            self::B => 4,
                            "name" => 3
                        ];

                        const B = 4;
                    }

                    echo A::C[4];',
            ],
            'sameNamedConstInOtherClass' => [
                '<?php
                    class B {
                        const B = 4;
                    }
                    class A {
                        const B = "four";
                        const C = [
                            B::B => "one",
                        ];
                    }

                    echo A::C[4];',
            ],
            'onlyMatchingConstantOffset' => [
                '<?php
                    class A {
                        const KEYS = ["one", "two", "three"];
                        const ARR = [
                            "one" => 1,
                            "two" => 2
                        ];
                    }

                    foreach (A::KEYS as $key) {
                        if (isset(A::ARR[$key])) {
                            echo A::ARR[$key];
                        }
                    }',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'constantDefinedInFunctionButNotCalled' => [
                '<?php
                    /**
                     * @return void
                     */
                    function defineConstant() {
                        define("CONSTANT", 1);
                    }

                    echo CONSTANT;',
                'error_message' => 'UndefinedConstant',
            ],
            'undefinedClassConstantInParamDefault' => [
                '<?php
                    class A {
                        public function doSomething(int $howManyTimes = self::DEFAULT_TIMES): void {}
                    }',
                'error_message' => 'UndefinedConstant',
            ],
            'nonMatchingConstantOffset' => [
                '<?php
                    class A {
                        const KEYS = ["one", "two", "three", "four"];
                        const ARR = [
                            "one" => 1,
                            "two" => 2
                        ];

                        const ARR2 = [
                            "three" => 3,
                            "four" => 4
                        ];
                    }

                    foreach (A::KEYS as $key) {
                        if (isset(A::ARR[$key])) {
                            echo A::ARR2[$key];
                        }
                    }',
                'error_message' => 'InvalidArrayOffset',
            ],
        ];
    }
}
