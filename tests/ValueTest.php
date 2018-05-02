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

                    if ($foo === "bar") {}
                    if ($foo !== "bar") {}
                    if ($foo === "baz") {}',
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
