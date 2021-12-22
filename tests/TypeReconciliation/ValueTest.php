<?php

namespace Psalm\Tests\TypeReconciliation;

use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ValueTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function setUp(): void
    {
        RuntimeCaches::clearAll();

        $this->file_provider = new FakeFileProvider();

        $this->project_analyzer = new ProjectAnalyzer(
            new TestConfig(),
            new Providers(
                $this->file_provider,
                new FakeParserCacheProvider()
            )
        );

        $this->project_analyzer->setPhpVersion('7.3', 'tests');
    }

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
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
                    }',
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
                    if ($i === 1) {}',
            ],
            'incrementInClosureAndCheck' => [
                '<?php
                    $i = 0;
                    $a = function() use (&$i) : void {
                        if (rand(0, 1)) {
                            $i++;
                        }
                    };
                    $a();
                    if ($i === 0) {}',
                'assertions' => [],
                'error_levels' => ['MixedOperand', 'MixedAssignment'],
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
                'error_levels' => ['MissingParamType', 'MixedMethodCall', 'MixedOperand', 'MixedAssignment'],
            ],
            'regularValueReconciliation' => [
                '<?php
                    $s = rand(0, 1) ? "a" : "b";
                    if (rand(0, 1)) {
                        $s = "c";
                    }

                    if ($s === "a" || $s === "b") {
                        if ($s === "a") {}
                    }',
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
                    if (!$s) {}',
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
                    }',
            ],
            'removeLiteralIntForNotIsInt' => [
                '<?php
                    function takesString(string $i) : void {}

                    $f = [0, 1, 2];
                    $f[rand(0, 2)] = "hello";

                    $i = rand(0, 2);
                    if (isset($f[$i]) && !is_int($f[$i])) {
                        takesString($f[$i]);
                    }',
            ],
            'removeLiteralFloatForNotIsFloat' => [
                '<?php
                    function takesString(string $i) : void {}

                    $f = [1.1, 1.2, 1.3];
                    $f[rand(0, 2)] = "hello";

                    $i = rand(0, 2);
                    if (isset($f[$i]) && !is_float($f[$i])) {
                        takesString($f[$i]);
                    }',
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

                            if ($this->s !== null && isset($map[$this->s])) {}
                        }
                    }',
            ],
            'noRedundantConditionWithMixed' => [
                '<?php
                    function foo($a) : void {
                        if ($a == "a") {
                        } else {
                            if ($a == "b" && rand(0, 1)) {}
                        }
                    }',
                'assertions' => [],
                'error_levels' => ['MissingParamType', 'MixedAssignment'],
            ],
            'numericToStringComparison' => [
                '<?php
                    /** @psalm-suppress MissingParamType */
                    function foo($s) : void {
                        if (is_numeric($s)) {
                            if ($s === 1) {}
                        }
                    }',
            ],
            'newlineIssue' => [
                '<?php
                    $a = "foo";
                    $b = "


                    ";

                    $c = $a;
                    if (rand(0, 1)) {
                        $c = $b;
                    }

                    if ($c === $b) {}',
            ],
            'don’tChangeType' => [
                '<?php
                    $x = 0;
                    $y = rand(0, 1);
                    $x++;
                    if ($x !== $y) {
                        chr($x);
                    }',
            ],
            'don’tChangeTypeInElse' => [
                '<?php
                    /** @var 0|string */
                    $x = 0;
                    $y = rand(0, 1) ? 0 : 1;
                    if ($x !== $y) {
                    } else {
                        if (!is_string($x)) {
                            chr($x);
                        }
                    }

                    /** @var int|string */
                    $x = 0;
                    if ($x !== $y) {
                    } else {
                        if (!is_string($x)) {
                            chr($x);
                        }
                    }',
            ],
            'convertNullArrayKeyToEmptyString' => [
                '<?php
                    $a = [
                        1 => 1,
                        2 => 2,
                        null => "hello",
                    ];

                    $b = $a[""];',
                'assertions' => [
                    '$b' => 'string',
                ],
            ],
            'yodaConditionalsShouldHaveSameOutput1' => [
                '<?php
                    class Foo {
                        /**
                         * @var array{from:bool, to:bool}
                         */
                        protected $things = ["from" => false, "to" => false];

                        public function foo(string ...$things) : void {
                            foreach ($things as $thing) {
                                if ($thing !== "from" && $thing !== "to") {
                                    continue;
                                }

                                $this->things[$thing] = !$this->things[$thing];
                            }
                        }
                    }
                ',
            ],
            'yodaConditionalsShouldHaveSameOutput2' => [
                '<?php
                    class Foo {
                        /**
                         * @var array{from:bool, to:bool}
                         */
                        protected $things = ["from" => false, "to" => false];

                        public function foo(string ...$things) : void {
                            foreach ($things as $thing) {
                                if ("from" !== $thing && "to" !== $thing) {
                                    continue;
                                }

                                $this->things[$thing] = !$this->things[$thing];
                            }
                        }
                    }
                ',
            ],
            'supportSingleLiteralType' => [
                '<?php
                    class A {
                        /**
                         * @var string
                         * @psalm-var "easy"
                         */
                        private $type = "easy";
                    }'
            ],
            'supportMultipleValues' => [
                '<?php
                    class A {
                        /**
                         * @var 0|-1|1
                         */
                        private $type = -1;
                    }'
            ],
            'typecastTrueToInt' => [
                '<?php
                /**
                * @param 0|1 $int
                */
                function foo(int $int) : void {
                    echo (string) $int;
                }

                foo((int) true);',
            ],
            'typecastFalseToInt' => [
                '<?php
                /**
                * @param 0|1 $int
                */
                function foo(int $int) : void {
                    echo (string) $int;
                }

                foo((int) false);',
            ],
            'typecastedBoolToInt' => [
                '<?php
                /**
                * @param 0|1 $int
                */
                function foo(int $int) : void {
                    echo (string) $int;
                }

                foo((int) ((bool) 2));',
            ],
            'notEqualToEachOther' => [
                '<?php
                    function example(object $a, object $b): bool {
                        if ($a !== $b && \get_class($a) === \get_class($b)) {
                            return true;
                        }

                        return false;
                    }'
            ],
            'numericStringValue' => [
                '<?php
                    /** @psalm-return numeric-string */
                    function makeNumeric() : string {
                      return "12.34";
                    }

                    /** @psalm-param numeric-string $string */
                    function consumeNumeric(string $string) : void {
                      \error_log($string);
                    }

                    consumeNumeric("12.34");'
            ],
            'resolveScalarClassConstant' => [
                '<?php
                    class PaymentFailure {
                        const NO_CLIENT = "no_client";
                        const NO_CARD = "no_card";
                    }

                    /**
                     * @return PaymentFailure::NO_CARD|PaymentFailure::NO_CLIENT
                     */
                    function something() {
                        if (rand(0, 1)) {
                            return PaymentFailure::NO_CARD;
                        }

                        return PaymentFailure::NO_CLIENT;
                    }

                    function blah(): void {
                        $test = something();
                        if ($test === PaymentFailure::NO_CLIENT) {}
                    }'
            ],
            'removeNullAfterLessThanZero' => [
                '<?php
                    function fcn(?int $val): int {
                        if ($val < 0) {
                            return $val;
                        }

                        return 5;
                    }',
            ],
            'numericStringCastFromInt' => [
                '<?php
                    /**
                     * @return numeric-string
                     */
                    function makeNumStringFromInt(int $v) {
                        return (string) $v;
                    }',
            ],
            'numericStringCastFromFloat' => [
                '<?php
                    /**
                     * @return numeric-string
                     */
                    function makeNumStringFromFloat(float $v) {
                        return (string) $v;
                    }'
            ],
            'compareNegatedValue' => [
                '<?php
                    $i = rand(-1, 5);

                    if (!($i > 0)) {
                        echo $i;
                    }',
            ],
            'refinePositiveInt' => [
                '<?php
                    $f = rand(0, 1) ? -1 : 1;
                    if ($f > 0) {}'
            ],
            'assignOpThenCheck' => [
                '<?php
                    $data = ["e" => 0];
                    if (rand(0, 1)) {
                        $data["e"]++;
                    }
                    if ($data["e"] > 0) {}'
            ],
            'compareToNullImplicitly' => [
                '<?php
                    final class Foo {
                        public const VALUE_ANY = null;
                        public const VALUE_ONE = "one";

                        /** @return self::VALUE_* */
                        public static function getValues() {
                            return rand(0, 1) ? null : self::VALUE_ONE;
                        }
                    }

                    $data = Foo::getValues();

                    if ($data === Foo::VALUE_ANY) {
                        $data = "default";
                    }

                    echo strlen($data);'
            ],
            'negateValueInUnion' => [
                '<?php
                    function f(): int {
                        $ret = 0;
                        for ($i = 20; $i >= 0; $i--) {
                            $ret = ($ret === 10) ? 1 : $ret + 1;
                        }
                        return $ret;
                    }'
            ],
            'inArrayPreserveNull' => [
                '<?php
                    function x(?string $foo): void {
                        if (!in_array($foo, ["foo", "bar", null], true)) {
                            throw new Exception();
                        }

                        if ($foo) {}
                    }',
            ],
            'allowCheckOnPositiveNumericInverse' => [
                '<?php
                    function foo(int $a): void {
                        if (false === ($a > 1)){}
                    }'
            ],
            'returnFromUnionLiteral' => [
                '<?php
                    /**
                     * @return array{"a1", "a2"}
                     */
                    function getSupportedConsts() {
                        return ["a1", "a2"];
                    }

                    function foo(mixed $file) : string {
                        if (in_array($file, getSupportedConsts(), true)) {
                            return $file;
                        }

                        return "";
                    }',
                [],
                [],
                '8.0'
            ],
            'returnFromUnionLiteralNegated' => [
                '<?php
                    /**
                     * @return array{"a1", "a2"}
                     */
                    function getSupportedConsts() {
                        return ["a1", "a2"];
                    }

                    function foo(mixed $file) : string {
                        if (!in_array($file, getSupportedConsts(), true)) {
                            return "";
                        }

                        return $file;
                    }',
                [],
                [],
                '8.0'
            ],
            'inArrayInsideLoop' => [
                '<?php
                    class A {
                        const ACTION_ONE = "one";
                        const ACTION_TWO = "two";
                        const ACTION_THREE = "two";
                    }

                    while (rand(0, 1)) {
                        /** @var list<A::ACTION_*> */
                        $case_actions = [];

                        if (!in_array(A::ACTION_ONE, $case_actions, true)) {}
                    }'
            ],
            'checkIdenticalArray' => [
                '<?php
                    /** @psalm-suppress MixedAssignment */
                    $array = json_decode(file_get_contents(\'php://stdin\'));

                    if (is_array($array)) {
                        $filtered = array_filter($array, fn ($value) => \is_string($value));

                        if ($array === $filtered) {
                            foreach ($array as $obj) {
                                echo strlen($obj);
                            }
                        }
                    }',
                [],
                [],
                '7.4'
            ],
            'zeroIsNonEmptyString' => [
                '<?php
                    /**
                     * @param non-empty-string $s
                     */
                    function foo(string $s) : void {}

                    foo("0");',
            ],
            'notLiteralEmptyCanBeNotEmptyString' => [
                '<?php
                    /**
                     * @param non-empty-string $s
                     */
                    function foo(string $s) : void {}

                    function takesString(string $s) : void {
                        if ($s !== "") {
                            foo($s);
                        }
                    }',
            ],
            'nonEmptyStringCanBeStringZero' => [
                '<?php
                    /**
                     * @param non-empty-string $s
                     */
                    function foo(string $s) : void {
                        if ($s === "0") {}
                        if (empty($s)) {}
                    }',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
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
            'inArrayRemoveNull' => [
                '<?php
                    function x(?string $foo, string $bar): void {
                        if (!in_array($foo, [$bar], true)) {
                            throw new Exception();
                        }

                        if (is_string($foo)) {}
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
            'casedComparison' => [
                '<?php
                    if ("C" === "c") {}',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'compareValueTwice' => [
                '<?php
                    $i = rand(-1, 5);

                    if ($i > 0 && $i > 0) {}',
                'error_message' => 'RedundantCondition',
            ],
            'numericStringCoerceToLiteral' => [
                '<?php
                    /** @param "0"|"1" $s */
                    function foo(string $s) : void {}

                    function bar(string $s) : void {
                        if (is_numeric($s)) {
                            foo($s);
                        }
                    }',
                'error_message' => 'ArgumentTypeCoercion'
            ],
            'stringCoercedToNonEmptyString' => [
                '<?php
                    /**
                     * @param non-empty-string $name
                     */
                    function sayHello(string $name) : void {}

                    function takeInput(string $name) : void {
                        sayHello($name);
                    }',
                'error_message' => 'ArgumentTypeCoercion',
            ],
        ];
    }
}
