<?php
namespace Psalm\Tests;

class ValueTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'whileCountUpdate' => [
                '<?php
                    $array = [1, 2, 3];
                    while (rand(1, 10) === 1) {
                        $array[] = 4;
                        $array[] = 5;
                        $array[] = 6;
                    }

                    if (count($array) === 7) {}',
            ],
            'tryCountCatch' => [
                '<?php
                    $errors = [];

                    try {
                        if (rand(0, 1)) {
                            throw new Exception("bad");
                        }
                    } catch (Exception $e) {
                        $errors[] = $e;
                    }

                    if (count($errors) !== 0) {
                        echo "Errors";
                    }',
            ],
            'ternaryDifferentString' => [
                '<?php
                    $foo = rand(0, 1) ? "bar" : "bat";

                    if ($foo === "bar") {}

                    if ($foo !== "bar") {}

                    if (rand(0, 1)) {
                        $foo = "baz";
                    }

                    if ($foo === "baz") {}

                    if ($foo !== "bat") {}',
            ],
            'ifDifferentString' => [
                '<?php
                    $foo = "bar";

                    if (rand(0, 1)) {
                        $foo = "bat";
                    } elseif (rand(0, 1)) {
                        $foo = "baz";
                    }

                    $bar = "bar";
                    $baz = "baz";

                    if ($foo === "bar") {}
                    if ($foo !== "bar") {}
                    if ($foo === "baz") {}
                    if ($foo === $bar) {}
                    if ($foo !== $bar) {}
                    if ($foo === $baz) {}',
            ],
            'ifThisOrThat' => [
                '<?php
                    $foo = "bar";

                    if (rand(0, 1)) {
                        $foo = "bat";
                    } elseif (rand(0, 1)) {
                        $foo = "baz";
                    }

                    if ($foo === "baz" || $foo === "bar") {}',
            ],
            'ifDifferentNullableString' => [
                '<?php
                    $foo = null;

                    if (rand(0, 1)) {
                        $foo = "bar";
                    }

                    $bar = "bar";

                    if ($foo === "bar") {}
                    if ($foo !== "bar") {}',
            ],
            'whileIncremented' => [
                '<?php
                    $i = 1;
                    $j = 2;
                    while (rand(0, 1)) {
                        if ($i === $j) {}
                        $i++;
                    }'
            ],
            'checkStringKeyValue' => [
                '<?php
                    $foo = [
                        "0" => 3,
                        "1" => 4,
                        "2" => 5,
                    ];

                    function takesInt(int $s) : void {}

                    foreach ($foo as $i => $b) {
                        takesInt($i);
                    }',
            ],
            'getValidIntStringOffset' => [
                '<?php
                    $foo = [
                        "0" => 3,
                        "1" => 4,
                        "2" => 5,
                    ];

                    $a = "2";

                    echo $foo["2"];
                    echo $foo[$a];',
            ],
            'checkStringKeyValueAfterKnownIntStringOffset' => [
                '<?php
                    $foo = [
                        "0" => 3,
                        "1" => 4,
                        "2" => 5,
                    ];

                    $a = "2";
                    $foo[$a] = 6;

                    function takesInt(int $s) : void {}

                    foreach ($foo as $i => $b) {
                        takesInt($i);
                    }',
            ],
            'regularComparison1' => [
                '<?php
                    function foo(string $s1, string $s2, ?int $i) : string {
                        if ($s1 !== $s2) {
                            return $s1;
                        }

                        return $s2;
                    }',
            ],
            'regularComparison2' => [
                '<?php
                    function foo(string $s1, string $s2) : string {
                        if ($s1 !== "hello") {
                            if ($s1 !== "goodbye") {
                                return $s1;
                            }
                        }

                        return $s2;
                    }',
            ],
            'regularComparison3' => [
                '<?php
                    class A {
                        const B = 1;
                        const C = 2;

                    }
                    function foo(string $s1, string $s2, ?int $i) : string {
                        if ($i !== A::B && $i !== A::C) {}

                        return $s2;
                    }',
            ],
            'regularComparisonOnPossiblyNull' => [
                '<?php
                    /** @psalm-ignore-nullable-return */
                    function generate() : ?string {
                        return rand(0, 1000) ? "hello" : null;
                    }

                    function foo() : string {
                        $str = generate();

                        if ($str[0] === "h") {
                            return $str;
                        }

                        return "hello";
                    }',
            ],
            'incrementAndCheck' => [
                '<?php
                    $i = 0;
                    if (rand(0, 1)) $i++;
                    if ($i === 1) {}'
            ],
            'incrementInClosureAndCheck' => [
                '<?php
                    $i = 0;
                    $a = function() use (&$i) : void {
                      if (rand(0, 1)) $i++;
                    };
                    $a();
                    if ($i === 0) {}',
                'assertions' => [],
                'error_levels' => ['MixedOperand'],
            ],
            'incrementMixedCall' => [
                '<?php
                    function foo($f) : void {
                        $i = 0;
                        $f->add(function() use (&$i) : void {
                            if (rand(0, 1)) $i++;
                        });
                        if ($i === 0) {}
                    }',
                'assertions' => [],
                'error_levels' => ['MissingParamType', 'MixedMethodCall', 'MixedOperand'],
            ],
            'regularValueReconciliation' => [
                '<?php
                    $s = rand(0, 1) ? "a" : "b";
                    if (rand(0, 1)) {
                        $s = "c";
                    }

                    if ($s === "a" || $s === "b") {
                        if ($s === "a") {}
                    }'
            ],
            'moreValueReconciliation' => [
                '<?php
                    $a = rand(0, 1) ? "a" : "b";
                    $b = rand(0, 1) ? "a" : "b";

                    $s = rand(0, 1) ? $a : $b;
                    if (rand(0, 1)) $s = "c";

                    if ($s === $a) {
                    } elseif ($s === $b) {}',
            ],
            'negativeInts' => [
                '<?php
                    class C {
                        const A = 1;
                        const B = -1;
                    }

                    const A = 1;
                    const B = -1;

                    $i = rand(0, 1) ? A : B;
                    if (rand(0, 1)) {
                        $i = 0;
                    }

                    if ($i === A) {
                        echo "here";
                    } elseif ($i === B) {
                        echo "here";
                    }

                    $i = rand(0, 1) ? C::A : C::B;

                    if (rand(0, 1)) {
                        $i = 0;
                    }

                    if ($i === C::A) {
                        echo "here";
                    } elseif ($i === C::B) {
                        echo "here";
                    }',
            ],
            'falsyReconciliation' => [
                '<?php
                    $s = rand(0, 1) ? 200 : null;
                    if (!$s) {}'
            ],
            'redefinedIntInIfAndPossibleComparison' => [
                '<?php
                    $s = rand(0, 1) ? 0 : 1;

                    if ($s && rand(0, 1)) {
                        if (rand(0, 1)) {
                            $s = 2;
                        }
                    }

                    if ($s == 2) {}',
            ],
            'noEmpties' => [
                '<?php
                    $context = \'a\';
                    while ( true ) {
                        if (rand(0, 1)) {
                            if (rand(0, 1)) {
                                exit;
                            }

                            $context = \'b\';
                        } elseif (rand(0, 1)) {
                            if ($context !== \'c\' && $context !== \'b\') {
                                exit;
                            }

                            $context = \'c\';
                        }
                    }',
            ],
            'ifOrAssertionWithSwitch' => [
                '<?php
                    function foo(string $s) : void {
                        switch ($s) {
                            case "a":
                            case "b":
                            case "c":
                                if ($s === "a" || $s === "b") {
                                    throw new \InvalidArgumentException;
                                }
                                break;
                        }
                    }',
            ],
            'inArrayAssertionProperty' => [
                '<?php
                    class Foo
                    {
                        /**
                         * @psalm-var "a"|"b"
                         */
                        private $s;

                        public function __construct(string $s)
                        {
                            if (!in_array($s, ["a", "b"], true)) {
                                throw new \InvalidArgumentException;
                            }
                            $this->s = $s;
                        }
                    }',
            ],
            'inArrayAssertionWithSwitch' => [
                '<?php
                    function foo(string $s) : void {
                        switch ($s) {
                            case "a":
                            case "b":
                            case "c":
                                if (in_array($s, ["a", "b"], true)) {
                                    throw new \InvalidArgumentException;
                                }
                                break;
                        }
                    }',
            ],
            'removeLiteralStringForNotIsString' => [
                '<?php
                    function takesInt(int $i) : void {}

                    $f = ["a", "b", "c"];
                    $f[rand(0, 2)] = 5;

                    $i = rand(0, 2);
                    if (isset($f[$i]) && !is_string($f[$i])) {
                        takesInt($f[$i]);
                    }'
            ],
            'removeLiteralIntForNotIsInt' => [
                '<?php
                    function takesString(string $i) : void {}

                    $f = [0, 1, 2];
                    $f[rand(0, 2)] = "hello";

                    $i = rand(0, 2);
                    if (isset($f[$i]) && !is_int($f[$i])) {
                        takesString($f[$i]);
                    }'
            ],
            'removeLiteralFloatForNotIsFloat' => [
                '<?php
                    function takesString(string $i) : void {}

                    $f = [1.1, 1.2, 1.3];
                    $f[rand(0, 2)] = "hello";

                    $i = rand(0, 2);
                    if (isset($f[$i]) && !is_float($f[$i])) {
                        takesString($f[$i]);
                    }'
            ],
            'coerceFromMixed' => [
                '<?php
                    function type(int $b): void {}

                    /**
                     * @param mixed $a
                     */
                    function foo($a) : void {
                        if ($a === 1 || $a === 2) {
                            type($a);
                        }

                        if (in_array($a, [1, 2], true)) {
                            type($a);
                        }
                    }',
            ],
            'coerceFromString' => [
                '<?php
                    /** @param "a"|"b" $b */
                    function type(string $b): void {}

                    function foo(string $a) : void {
                        if ($a === "a" || $a === "b") {
                            type($a);
                        }
                    }',
            ],
            'coercePossibleOffset' => [
                '<?php
                    class A {
                        const FOO = "foo";
                        const BAR = "bar";
                        const BAT = "bat";
                        const BAM = "bam";

                        /** @var self::FOO|self::BAR|self::BAT|null $s */
                        public $s;

                        public function isFooOrBar() : void {
                            $map = [
                                A::FOO => 1,
                                A::BAR => 1,
                                A::BAM => 1,
                            ];

                            if (isset($map[$this->s])) {}
                        }
                    }'
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'neverEqualsType' => [
                '<?php
                    $a = 4;
                    if ($a === 5) {
                        // do something
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'alwaysIdenticalType' => [
                '<?php
                    $a = 4;
                    if ($a === 4) {
                        // do something
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'alwaysNotIdenticalType' => [
                '<?php
                    $a = 4;
                    if ($a !== 5) {
                        // do something
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'neverNotIdenticalType' => [
                '<?php
                    $a = 4;
                    if ($a !== 4) {
                        // do something
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'neverEqualsFloatType' => [
                '<?php
                    $a = 4.0;
                    if ($a === 4.1) {
                        // do something
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'alwaysIdenticalFloatType' => [
                '<?php
                    $a = 4.1;
                    if ($a === 4.1) {
                        // do something
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'alwaysNotIdenticalFloatType' => [
                '<?php
                    $a = 4.0;
                    if ($a !== 4.1) {
                        // do something
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'neverNotIdenticalFloatType' => [
                '<?php
                    $a = 4.1;
                    if ($a !== 4.1) {
                        // do something
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'SKIPPED-phpstanPostedArrayTest' => [
                '<?php
                    $array = [1, 2, 3];
                    if (rand(1, 10) === 1) {
                        $array[] = 4;
                        $array[] = 5;
                        $array[] = 6;
                    }

                    if (count($array) === 7) {

                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'ifImpossibleString' => [
                '<?php
                    $foo = rand(0, 1) ? "bar" : "bat";

                    if ($foo === "baz") {}',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'arrayOffsetImpossibleValue' => [
                '<?php
                    $foo = [
                        "a" => 1,
                        "b" => 2,
                    ];

                    if ($foo["a"] === 2) {}',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'impossibleKeyInForeach' => [
                '<?php
                    $foo = [
                        "0" => 3,
                        "1" => 4,
                        "2" => 5,
                    ];

                    function takesInt(int $s) : void {}

                    foreach ($foo as $i => $b) {
                        if ($i === 3) {}
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'impossibleValueInForeach' => [
                '<?php
                    $foo = [
                        "0" => 3,
                        "1" => 4,
                        "2" => 5,
                    ];

                    function takesInt(int $s) : void {}

                    foreach ($foo as $i => $b) {
                        if ($b === $i) {}
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'invalidIntStringOffset' => [
                '<?php
                    $foo = [
                        "0" => 3,
                        "1" => 4,
                        "2" => 5,
                    ];

                    $a = "3";

                    echo $foo[$a];',
                'error_message' => 'InvalidArrayOffset',
            ],
            'noChangeToVariable' => [
                '<?php
                    $i = 0;

                    $a = function() use ($i) : void {
                        $i++;
                    };

                    $a();

                    if ($i === 0) {}',
                'error_message' => 'RedundantCondition',
            ],
            'redefinedIntInIfAndImpossbleComparison' => [
                '<?php
                    $s = rand(0, 1) ? 0 : 1;

                    if ($s && rand(0, 1)) {
                        if (rand(0, 1)) {
                            $s = 2;
                        }
                    }

                    if ($s == 3) {}',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'badIfOrAssertionWithSwitch' => [
                '<?php
                    function foo(string $s) : void {
                        switch ($s) {
                            case "a":
                            case "b":
                            case "c":
                                if ($s === "a" || $s === "b") {
                                    throw new \InvalidArgumentException;
                                }

                                if ($s === "c") {}
                        }
                    }',
                'error_message' => 'RedundantCondition',
            ],
        ];
    }
}
