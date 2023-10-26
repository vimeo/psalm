<?php

declare(strict_types=1);

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
                new FakeParserCacheProvider(),
            ),
        );

        $this->project_analyzer->setPhpVersion('7.3', 'tests');
    }

    public function providerValidCodeParse(): iterable
    {
        return [
            'whileCountUpdate' => [
                'code' => '<?php
                    $array = [1, 2, 3];
                    while (rand(1, 10) === 1) {
                        $array[] = 4;
                        $array[] = 5;
                        $array[] = 6;
                    }

                    if (count($array) === 7) {}',
            ],
            'tryCountCatch' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    $foo = "bar";

                    if (rand(0, 1)) {
                        $foo = "bat";
                    } elseif (rand(0, 1)) {
                        $foo = "baz";
                    }

                    if ($foo === "baz" || $foo === "bar") {}',
            ],
            'ifDifferentNullableString' => [
                'code' => '<?php
                    $foo = null;

                    if (rand(0, 1)) {
                        $foo = "bar";
                    }

                    $bar = "bar";

                    if ($foo === "bar") {}
                    if ($foo !== "bar") {}',
            ],
            'whileIncremented' => [
                'code' => '<?php
                    $i = 1;
                    $j = 2;
                    while (rand(0, 1)) {
                        if ($i === $j) {}
                        $i++;
                    }',
            ],
            'checkStringKeyValue' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    function foo(string $s1, string $s2, ?int $i) : string {
                        if ($s1 !== $s2) {
                            return $s1;
                        }

                        return $s2;
                    }',
            ],
            'regularComparison2' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    $i = 0;
                    if (rand(0, 1)) $i++;
                    if ($i === 1) {}',
            ],
            'incrementInClosureAndCheck' => [
                'code' => '<?php
                    $i = 0;
                    $a = function() use (&$i) : void {
                        if (rand(0, 1)) {
                            $i++;
                        }
                    };
                    $a();
                    if ($i === 0) {}',
                'assertions' => [],
                'ignored_issues' => ['MixedOperand', 'MixedAssignment'],
            ],
            'incrementMixedCall' => [
                'code' => '<?php
                    function foo($f) : void {
                        $i = 0;
                        $f->add(function() use (&$i) : void {
                            if (rand(0, 1)) $i++;
                        });
                        if ($i === 0) {}
                    }',
                'assertions' => [],
                'ignored_issues' => ['MissingParamType', 'MixedMethodCall', 'MixedOperand', 'MixedAssignment'],
            ],
            'regularValueReconciliation' => [
                'code' => '<?php
                    $s = rand(0, 1) ? "a" : "b";
                    if (rand(0, 1)) {
                        $s = "c";
                    }

                    if ($s === "a" || $s === "b") {
                        if ($s === "a") {}
                    }',
            ],
            'moreValueReconciliation' => [
                'code' => '<?php
                    $a = rand(0, 1) ? "a" : "b";
                    $b = rand(0, 1) ? "a" : "b";

                    $s = rand(0, 1) ? $a : $b;
                    if (rand(0, 1)) $s = "c";

                    if ($s === $a) {
                    } elseif ($s === $b) {}',
            ],
            'negativeInts' => [
                'code' => '<?php
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
                'code' => '<?php
                    $s = rand(0, 1) ? 200 : null;
                    if (!$s) {}',
            ],
            'redefinedIntInIfAndPossibleComparison' => [
                'code' => '<?php
                    $s = rand(0, 1) ? 0 : 1;

                    if ($s && rand(0, 1)) {
                        if (rand(0, 1)) {
                            $s = 2;
                        }
                    }

                    if ($s == 2) {}',
            ],
            'noEmpties' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    function takesInt(int $i) : void {}

                    $f = ["a", "b", "c"];
                    $f[rand(0, 2)] = 5;

                    $i = rand(0, 2);
                    if (isset($f[$i]) && !is_string($f[$i])) {
                        takesInt($f[$i]);
                    }',
            ],
            'removeLiteralIntForNotIsInt' => [
                'code' => '<?php
                    function takesString(string $i) : void {}

                    $f = [0, 1, 2];
                    $f[rand(0, 2)] = "hello";

                    $i = rand(0, 2);
                    if (isset($f[$i]) && !is_int($f[$i])) {
                        takesString($f[$i]);
                    }',
            ],
            'removeLiteralFloatForNotIsFloat' => [
                'code' => '<?php
                    function takesString(string $i) : void {}

                    $f = [1.1, 1.2, 1.3];
                    $f[rand(0, 2)] = "hello";

                    $i = rand(0, 2);
                    if (isset($f[$i]) && !is_float($f[$i])) {
                        takesString($f[$i]);
                    }',
            ],
            'coerceFromMixed' => [
                'code' => '<?php
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
                'code' => '<?php
                    /** @param "a"|"b" $b */
                    function type(string $b): void {}

                    function foo(string $a) : void {
                        if ($a === "a" || $a === "b") {
                            type($a);
                        }
                    }',
            ],
            'coercePossibleOffset' => [
                'code' => '<?php
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
                'code' => '<?php
                    function foo($a) : void {
                        if ($a == "a") {
                        } else {
                            if ($a == "b" && rand(0, 1)) {}
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MissingParamType', 'MixedAssignment'],
            ],
            'numericToStringComparison' => [
                'code' => '<?php
                    /** @psalm-suppress MissingParamType */
                    function foo($s) : void {
                        if (is_numeric($s)) {
                            if ($s === 1) {}
                        }
                    }',
            ],
            'newlineIssue' => [
                'code' => '<?php
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
                'code' => '<?php
                    $x = 0;
                    $y = rand(0, 1);
                    $x++;
                    if ($x !== $y) {
                        chr($x);
                    }',
            ],
            'don’tChangeTypeInElse' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    class A {
                        /**
                         * @var string
                         * @psalm-var "easy"
                         */
                        private $type = "easy";
                    }',
            ],
            'supportMultipleValues' => [
                'code' => '<?php
                    class A {
                        /**
                         * @var 0|-1|1
                         */
                        private $type = -1;
                    }',
            ],
            'typecastTrueToInt' => [
                'code' => '<?php
                /**
                * @param 0|1 $int
                */
                function foo(int $int) : void {
                    echo (string) $int;
                }

                foo((int) true);',
            ],
            'typecastFalseToInt' => [
                'code' => '<?php
                /**
                * @param 0|1 $int
                */
                function foo(int $int) : void {
                    echo (string) $int;
                }

                foo((int) false);',
            ],
            'typecastedBoolToInt' => [
                'code' => '<?php
                /**
                * @param 0|1 $int
                */
                function foo(int $int) : void {
                    echo (string) $int;
                }

                foo((int) ((bool) 2));',
            ],
            'notEqualToEachOther' => [
                'code' => '<?php
                    function example(object $a, object $b): bool {
                        if ($a !== $b && \get_class($a) === \get_class($b)) {
                            return true;
                        }

                        return false;
                    }',
            ],
            'numericStringValue' => [
                'code' => '<?php
                    /** @psalm-return numeric-string */
                    function makeNumeric() : string {
                      return "12.34";
                    }

                    /** @psalm-param numeric-string $string */
                    function consumeNumeric(string $string) : void {
                      \error_log($string);
                    }

                    consumeNumeric("12.34");',
            ],
            'resolveScalarClassConstant' => [
                'code' => '<?php
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
                    }',
            ],
            'removeNullAfterLessThanZero' => [
                'code' => '<?php
                    function fcn(?int $val): int {
                        if ($val < 0) {
                            return $val;
                        }

                        return 5;
                    }',
            ],
            'numericStringCastFromInt' => [
                'code' => '<?php
                    /**
                     * @return numeric-string
                     */
                    function makeNumStringFromInt(int $v) {
                        return (string) $v;
                    }',
            ],
            'numericStringCastFromFloat' => [
                'code' => '<?php
                    /**
                     * @return numeric-string
                     */
                    function makeNumStringFromFloat(float $v) {
                        return (string) $v;
                    }',
            ],
            'compareNegatedValue' => [
                'code' => '<?php
                    $i = rand(-1, 5);

                    if (!($i > 0)) {
                        echo $i;
                    }',
            ],
            'refinePositiveInt' => [
                'code' => '<?php
                    $f = rand(0, 1) ? -1 : 1;
                    if ($f > 0) {}',
            ],
            'assignOpThenCheck' => [
                'code' => '<?php
                    $data = ["e" => 0];
                    if (rand(0, 1)) {
                        $data["e"]++;
                    }
                    if ($data["e"] > 0) {}',
            ],
            'compareToNullImplicitly' => [
                'code' => '<?php
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

                    echo strlen($data);',
            ],
            'negateValueInUnion' => [
                'code' => '<?php
                    function f(): int {
                        $ret = 0;
                        for ($i = 20; $i >= 0; $i--) {
                            $ret = ($ret === 10) ? 1 : $ret + 1;
                        }
                        return $ret;
                    }',
            ],
            'inArrayPreserveNull' => [
                'code' => '<?php
                    function x(?string $foo): void {
                        if (!in_array($foo, ["foo", "bar", null], true)) {
                            throw new Exception();
                        }

                        if ($foo) {}
                    }',
            ],
            'allowCheckOnPositiveNumericInverse' => [
                'code' => '<?php
                    function foo(int $a): void {
                        if (false === ($a > 1)){}
                    }',
            ],
            'returnFromUnionLiteral' => [
                'code' => '<?php
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
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'returnFromUnionLiteralNegated' => [
                'code' => '<?php
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
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'inArrayInsideLoop' => [
                'code' => '<?php
                    class A {
                        const ACTION_ONE = "one";
                        const ACTION_TWO = "two";
                        const ACTION_THREE = "two";
                    }

                    while (rand(0, 1)) {
                        /** @var list<A::ACTION_*> */
                        $case_actions = [];

                        if (!in_array(A::ACTION_ONE, $case_actions, true)) {}
                    }',
            ],
            'checkIdenticalArray' => [
                'code' => '<?php
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
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'zeroIsNonEmptyString' => [
                'code' => '<?php
                    /**
                     * @param non-empty-string $s
                     */
                    function foo(string $s) : void {}

                    foo("0");',
            ],
            'notLiteralEmptyCanBeNotEmptyString' => [
                'code' => '<?php
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
                'code' => '<?php
                    /**
                     * @param non-empty-string $s
                     */
                    function foo(string $s) : void {
                        if ($s === "0") {}
                        if (empty($s)) {}
                    }',
            ],
            'falseDateInterval' => [
                'code' => '<?php
                    $interval = \DateInterval::createFromDateString("30 дней");
                    if ($interval === false) {}',
            ],
            'literalInt' => [
                'code' => '<?php
                    $a = (int)"5";
                ',
                'assertions' => [
                    '$a===' => '5',
                ],
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'neverEqualsType' => [
                'code' => '<?php
                    $a = 4;
                    if ($a === 5) {
                        // do something
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'alwaysIdenticalType' => [
                'code' => '<?php
                    $a = 4;
                    if ($a === 4) {
                        // do something
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'alwaysNotIdenticalType' => [
                'code' => '<?php
                    $a = 4;
                    if ($a !== 5) {
                        // do something
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'neverNotIdenticalType' => [
                'code' => '<?php
                    $a = 4;
                    if ($a !== 4) {
                        // do something
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'neverEqualsFloatType' => [
                'code' => '<?php
                    $a = 4.0;
                    if ($a === 4.1) {
                        // do something
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'alwaysIdenticalFloatType' => [
                'code' => '<?php
                    $a = 4.1;
                    if ($a === 4.1) {
                        // do something
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'alwaysNotIdenticalFloatType' => [
                'code' => '<?php
                    $a = 4.0;
                    if ($a !== 4.1) {
                        // do something
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'inArrayRemoveNull' => [
                'code' => '<?php
                    function x(?string $foo, string $bar): void {
                        if (!in_array($foo, [$bar], true)) {
                            throw new Exception();
                        }

                        if (is_string($foo)) {}
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'neverNotIdenticalFloatType' => [
                'code' => '<?php
                    $a = 4.1;
                    if ($a !== 4.1) {
                        // do something
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'ifImpossibleString' => [
                'code' => '<?php
                    $foo = rand(0, 1) ? "bar" : "bat";

                    if ($foo === "baz") {}',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'arrayOffsetImpossibleValue' => [
                'code' => '<?php
                    $foo = [
                        "a" => 1,
                        "b" => 2,
                    ];

                    if ($foo["a"] === 2) {}',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'impossibleKeyInForeach' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    $i = 0;

                    $a = function() use ($i) : void {
                        $i++;
                    };

                    $a();

                    if ($i === 0) {}',
                'error_message' => 'RedundantCondition',
            ],
            'redefinedIntInIfAndImpossbleComparison' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    if ("C" === "c") {}',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'compareValueTwice' => [
                'code' => '<?php
                    $i = rand(-1, 5);

                    if ($i > 0 && $i > 0) {}',
                'error_message' => 'RedundantCondition',
            ],
            'numericStringCoerceToLiteral' => [
                'code' => '<?php
                    /** @param "0"|"1" $s */
                    function foo(string $s) : void {}

                    function bar(string $s) : void {
                        if (is_numeric($s)) {
                            foo($s);
                        }
                    }',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'stringCoercedToNonEmptyString' => [
                'code' => '<?php
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
