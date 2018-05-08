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
                'error_levels' => ['MissingParamType', 'MixedMethodCall'],
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
                'error_message' => 'RedundantCondition',
            ],
            'phpstanPostedArrayTest' => [
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
        ];
    }
}
