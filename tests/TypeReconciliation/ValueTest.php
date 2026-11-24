<?php

declare(strict_types=1);

namespace Psalm\Tests\TypeReconciliation;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class ValueTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->project_analyzer->setPhpVersion('7.3', 'tests');
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'notIdenticalToAVariableOfSeveralLiteralsKeepsThemInTheElseif' => [
                'code' => '<?php
                    $a = rand(0, 1) ? "a" : "b";
                    $b = rand(0, 1) ? "a" : "b";

                    $s = rand(0, 1) ? $a : $b;
                    if (rand(0, 1)) {
                        $s = "c";
                    }

                    if ($s === $a) {
                        $result = $s;
                    } elseif ($s === $b) {
                        $result = $s;
                    } else {
                        $result = null;
                    }',
                'assertions' => [
                    '$result===' => '\'a\'|\'b\'|null',
                ],
            ],
            'looseComparisonOfAnObjectOrAStringWithAStringKeepsTheObject' => [
                'code' => <<<'PHP'
                    <?php
                    class Plain {}

                    function anObject(object|string $x): void {
                        if ($x == 'a') {
                            /** @psalm-check-type-exact $x = 'a'|object */
                        }
                    }

                    function withoutToString(Plain|string $x): void {
                        if ($x == 'a') {
                            /** @psalm-check-type-exact $x = 'a' */
                        }
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'looseInequalityWithAStringKeepsTheStringableObjectInTheElseBranch' => [
                'code' => <<<'PHP'
                    <?php
                    class A {
                        public function __toString(): string {
                            return 'a';
                        }
                    }

                    function f(A|string $x): void {
                        if ($x != 'a') {
                            /** @psalm-check-type-exact $x = A|string */
                        } else {
                            /** @psalm-check-type-exact $x = 'a'|A */
                        }
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'looseEqualityWithAStringKeepsAStringableObject' => [
                'code' => '<?php
                    final class A {
                        public function __toString(): string {
                            return "a";
                        }
                    }

                    /** @var A|"a" $foo */
                    $foo = new A();

                    if ($foo == "a") {
                        $result = $foo;
                    } else {
                        $result = null;
                    }',
                'assertions' => [
                    '$result' => 'A|null|string',
                ],
            ],
            'strictEqualityWithAStringDropsAStringableObject' => [
                'code' => '<?php
                    final class A {
                        public function __toString(): string {
                            return "a";
                        }
                    }

                    /** @var A|"a" $foo */
                    $foo = new A();

                    if ($foo === "a") {
                        $result = $foo;
                    } else {
                        $result = null;
                    }',
                'assertions' => [
                    '$result' => 'null|string',
                ],
            ],
            'stringThatIsNeitherEmptyNorZeroIsNonFalsyInEveryOrder' => [
                'code' => <<<'PHP'
                    <?php
                    function emptyFirst(string $s): void {
                        if ($s !== '' && $s !== '0') {
                            /** @psalm-check-type-exact $s = non-falsy-string */
                        }
                    }

                    function zeroFirst(string $s): void {
                        if ($s !== '0' && $s !== '') {
                            /** @psalm-check-type-exact $s = non-falsy-string */
                        }
                    }

                    function earlyReturnsZeroFirst(string $s): void {
                        if ('0' === $s) {
                            return;
                        }

                        if ('' === $s) {
                            return;
                        }

                        /** @psalm-check-type-exact $s = non-falsy-string */
                    }

                    function earlyReturnsEmptyFirst(string $s): void {
                        if ('' === $s) {
                            return;
                        }

                        if ('0' === $s) {
                            return;
                        }

                        /** @psalm-check-type-exact $s = non-falsy-string */
                    }

                    function onlyZeroExcluded(string $s): void {
                        if ($s !== '0') {
                            /** @psalm-check-type-exact $s = string */
                        }
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'nonEmptyStringKnownBeforeTheOtherConditionsIsKeptOnMixed' => [
                'code' => '<?php
                    /** @return non-empty-string */
                    function f(mixed $v, bool $b): string {
                        if (is_string($v) && $v !== "" && $b) {
                            return $v;
                        }

                        return "x";
                    }

                    /** @return non-empty-string */
                    function g(mixed $v): string {
                        if (!is_string($v) || $v === "") {
                            return "x";
                        }

                        return $v;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'nonEmptyStringNotZeroIsNonFalsy' => [
                'code' => '<?php
                    /** @var non-empty-string $s */
                    $s = $_GET["s"];
                    if ($s === "0") { throw new Exception(""); }',
                'assertions' => [
                    '$s===' => 'non-falsy-string',
                ],
            ],
            'nonEmptyStringLooselyNotZeroIsNonFalsy' => [
                'code' => '<?php
                    /** @var non-empty-string $s */
                    $s = $_GET["s"];
                    if ($s == "0") { throw new Exception(""); }',
                'assertions' => [
                    '$s===' => 'non-falsy-string',
                ],
            ],
            'nonEmptyStringNotAnotherLiteralStaysNonEmpty' => [
                'code' => '<?php
                    /** @var non-empty-string $s */
                    $s = $_GET["s"];
                    if ($s === "a") { throw new Exception(""); }',
                'assertions' => [
                    '$s===' => 'non-empty-string',
                ],
            ],
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
                        chr($x);
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
            'notLiteralEmptyKeepsKindOfString' => [
                'code' => '<?php
                    function plain(): string { return "a"; }
                    /** @return lowercase-string */
                    function lowercase(): string { return "a"; }
                    /** @return literal-string */
                    function literal(): string { return "a"; }
                    /** @return non-falsy-string */
                    function nonFalsy(): string { return "a"; }
                    /** @return numeric-string */
                    function numeric(): string { return "1"; }
                    /** @return class-string */
                    function className(): string { return Exception::class; }
                    /** @return lowercase-string|int */
                    function lowercaseOrInt() { return 1; }

                    $string = plain();
                    $lowercase = lowercase();
                    $literal = literal();
                    $non_falsy = nonFalsy();
                    $numeric = numeric();
                    $class_name = className();
                    $lowercase_or_int = lowercaseOrInt();

                    if ($string === ""
                        || $lowercase === ""
                        || $literal === ""
                        || $non_falsy === ""
                        || $numeric === ""
                        || $class_name === ""
                        || $lowercase_or_int === ""
                    ) {
                        throw new RuntimeException();
                    }',
                'assertions' => [
                    '$string===' => 'non-empty-string',
                    '$lowercase===' => 'non-empty-lowercase-string',
                    '$literal===' => 'non-empty-literal-string',
                    '$non_falsy===' => 'non-falsy-string',
                    '$numeric===' => 'numeric-string',
                    '$class_name===' => 'class-string',
                    '$lowercase_or_int===' => 'int|non-empty-lowercase-string',
                ],
                'ignored_issues' => ['DocblockTypeContradiction', 'TypeDoesNotContainType'],
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
            'iterableMayBeAList' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @template T
                     * @param iterable<T|null> $value
                     * @return ($value is list ? list<T> : ($value is array ? array<T> : iterable<T>))
                     */
                    function allNotNull(iterable $value): iterable {
                        throw new RuntimeException();
                    }

                    /** @return iterable<int|null> */
                    function g(): iterable {
                        return [1, 2];
                    }

                    $x = allNotNull(g());
                    PHP,
                'assertions' => [
                    '$x===' => 'iterable<array-key|mixed, int>|list<int>',
                ],
            ],
            'callableIdenticalToStringIsCallableString' => [
                'code' => <<<'PHP'
                    <?php
                    function f(callable $c, string $s): void {
                        if ($c === $s) { // 'strlen'
                            /** @psalm-check-type-exact $c = callable-string */
                            /** @psalm-check-type-exact $s = callable-string */
                        }
                    }
                    PHP,
            ],
            'callableIdenticalToArrayIsCallableArray' => [
                'code' => <<<'PHP'
                    <?php
                    function f(callable $c, array $a): void {
                        if ($c === $a) { // [new ArrayObject(), 'count']
                            /** @psalm-check-type-exact $c = callable-array{0: class-string|object, 1: non-empty-string} */
                        }
                    }
                    PHP,
            ],
            'callableIdenticalToObjectIsCallableObject' => [
                'code' => <<<'PHP'
                    <?php
                    function f(callable $c, object $o): void {
                        if ($c === $o) { // fn() => 1
                            /** @psalm-check-type-exact $c = callable-object */
                            /** @psalm-check-type-exact $o = callable-object */
                        }
                    }
                    PHP,
            ],
            'numericIdenticalToStringIsNumericString' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param numeric $n */
                    function f($n, string $s): void {
                        if ($n === $s) { // '1'
                            /** @psalm-check-type-exact $n = numeric-string */
                            /** @psalm-check-type-exact $s = numeric-string */
                        }
                    }
                    PHP,
            ],
            'iterableIdenticalToObjectIsTraversable' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param iterable<int> $it */
                    function f(iterable $it, object $o): void {
                        if ($it === $o) { // new ArrayIterator([1])
                            /** @psalm-check-type-exact $it = Traversable<array-key, int> */
                            /** @psalm-check-type-exact $o = Traversable<mixed, int> */
                        }
                    }
                    PHP,
            ],
            'iterableIdenticalToCallableIsCallableArray' => [
                'code' => <<<'PHP'
                    <?php
                    function f(iterable $it, callable $c): void {
                        if ($it === $c) { // [new ArrayObject(), 'count']
                            /** @psalm-check-type-exact $c = callable-array{0: class-string|object, 1: non-empty-string} */
                        }
                    }
                    PHP,
            ],
            'iterableAndScalarMayBeCallable' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @param scalar $sc
                     * @param array-key $k
                     */
                    function f(iterable $it, $sc, $k): void {
                        if (is_callable($it)) {} // [new ArrayObject(), 'count']
                        if (is_callable($sc)) { // 'strlen'
                            /** @psalm-check-type-exact $sc = callable-string */
                        }
                        if (is_callable($k)) { // 'strlen'
                            /** @psalm-check-type-exact $k = callable-string */
                        }
                    }
                    PHP,
            ],
            'scalarIdenticalToCallableIsCallableString' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @param scalar $sc
                     * @param array-key $k
                     */
                    function f($sc, $k, callable $c): void {
                        if ($sc === $c) { // 'strlen'
                            /** @psalm-check-type-exact $sc = callable-string */
                        }
                        if ($k === $c) { // 'strlen'
                            /** @psalm-check-type-exact $k = callable-string */
                        }
                    }
                    PHP,
            ],
            'callableInArrayOfStringsIsCallableString' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param list<string> $names */
                    function f(callable $c, array $names): void {
                        if (in_array($c, $names, true)) { // 'strlen'
                            /** @psalm-check-type-exact $c = callable-string */
                        }
                    }
                    PHP,
            ],
            'unionsWithOverlappingCompoundTypesAreIntersected' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @param callable|int $c
                     * @param string|array $s
                     */
                    function f($c, $s): void {
                        if ($c === $s) { // 'strlen' or [new ArrayObject(), 'count']
                            /** @psalm-check-type-exact $c = callable-array{0: class-string|object, 1: non-empty-string}|callable-string */
                        }
                    }
                    PHP,
            ],
            'callableIdenticalToLiteralString' => [
                'code' => <<<'PHP'
                    <?php
                    function f(callable $c): void {
                        if ($c === 'strlen') {
                            /** @psalm-check-type-exact $c = 'strlen' */
                        }
                    }
                    PHP,
            ],
            'numericIdenticalToLiteralNumericString' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param numeric $n */
                    function f($n): void {
                        if ($n === '1') {
                            /** @psalm-check-type-exact $n = '1' */
                        }
                    }
                    PHP,
            ],
            'nonEmptyScalarMayBeIdenticalToTruthyScalars' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @param non-empty-scalar $n
                     * @param scalar $s
                     */
                    function f($n, $s): void {
                        if ($n === 'a') {}
                        if ($n === 1) {}
                        if ($n === true) {}
                        if ($n === $s) {}
                    }
                    PHP,
            ],
            'emptyScalarMayBeIdenticalToFalsyScalars' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @param empty-scalar $e
                     * @param scalar $s
                     */
                    function f($e, $s): void {
                        if ($e === '') {}
                        if ($e === '0') {}
                        if ($e === 0) {}
                        if ($e === false) {}
                        if ($e === $s) {}
                    }
                    PHP,
            ],
            'nonEmptyScalarIdenticalToCallableIsCallableString' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param non-empty-scalar $sc */
                    function f($sc, callable $c): void {
                        if ($sc === $c) { // 'strlen'
                            /** @psalm-check-type-exact $sc = callable-string */
                        }
                    }
                    PHP,
            ],
            'callableMayBeIterable' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return callable */
                    function c() {
                        return 'strlen';
                    }

                    $c = c();

                    if (!is_iterable($c)) { // [new ArrayObject(), 'count']
                        throw new RuntimeException();
                    }
                    PHP,
                'assertions' => [
                    '$c===' => 'callable-array{0: class-string|object, 1: non-empty-string}',
                ],
            ],
            'callableMayBeCountable' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return callable */
                    function c() {
                        return 'strlen';
                    }

                    $c = c();

                    if (!is_countable($c)) { // [new ArrayObject(), 'count']
                        throw new RuntimeException();
                    }

                    $c();
                    echo count($c);
                    PHP,
                'assertions' => [
                    '$c===' => 'callable-array{0: class-string|object, 1: non-empty-string}',
                ],
            ],
            'arrayOrCallableMayBeCountableArray' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return array<int, string>|callable */
                    function c() {
                        return [];
                    }

                    $c = c();

                    if (!is_countable($c)) {
                        throw new RuntimeException();
                    }
                    PHP,
                'assertions' => [
                    '$c===' => 'array<int, object|string>',
                ],
            ],
            'arrayOrCallableMayBeIterableArray' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return array<int, string>|callable */
                    function c() {
                        return [];
                    }

                    $c = c();

                    if (!is_iterable($c)) {
                        throw new RuntimeException();
                    }
                    PHP,
                'assertions' => [
                    '$c===' => 'array<int, object|string>',
                ],
            ],
            'iterableWithoutArrayKeysMayBeCountableEmptyArray' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return iterable<object, int> */
                    function i() {
                        return [];
                    }

                    $i = i();

                    if (!is_countable($i)) {
                        throw new RuntimeException();
                    }
                    PHP,
                'assertions' => [
                    '$i===' => 'Traversable<object, int>&Countable|array<never, never>',
                ],
            ],
            'templatedIterableMayBeCountableArray' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @template TKey
                     * @template TValue
                     * @param iterable<TKey, TValue> $i
                     * @return list<TValue>
                     */
                    function f(iterable $i): array {
                        if (is_countable($i) && is_array($i)) {
                            return array_values($i);
                        }

                        return [];
                    }
                    PHP,
            ],
            'emptyIterableMayBeCountableEmptyArray' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return iterable<never, never> */
                    function i() {
                        return [];
                    }

                    $i = i();

                    if (!is_countable($i) || !is_array($i)) {
                        throw new RuntimeException();
                    }
                    PHP,
                'assertions' => [
                    '$i===' => 'array<never, never>',
                ],
            ],
            'iterableMayBeCountableArrayOrTraversable' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return iterable<string, int> */
                    function i() {
                        return [];
                    }

                    $i = i();

                    if (!is_countable($i)) {
                        throw new RuntimeException();
                    }
                    PHP,
                'assertions' => [
                    '$i===' => 'Traversable<string, int>&Countable|array<string, int>',
                ],
            ],
            'countableIterableIsCountableTraversable' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return iterable<int, string>&Countable */
                    function i() {
                        return new ArrayObject([]);
                    }

                    $i = i();

                    if (!is_countable($i)) {
                        throw new RuntimeException();
                    }
                    PHP,
                'assertions' => [
                    '$i===' => 'Traversable<int, string>&Countable',
                ],
            ],
            'isCountableKeepsOtherTypes' => [
                'code' => <<<'PHP'
                    <?php
                    interface I {}

                    /**
                     * @template T
                     * @param T $t
                     */
                    function f(mixed $m, $t, I $i, object $o, Countable|string $cs, array|string $as): void {
                        if (is_countable($m)) {
                            /** @psalm-check-type-exact $m = Countable|array<array-key, mixed> */
                        }
                        if (is_countable($t)) {
                            /** @psalm-check-type-exact $t = Countable|array<array-key, mixed> */
                        }
                        if (is_countable($i)) {
                            /** @psalm-check-type-exact $i = I&Countable */
                        }
                        if (is_countable($o)) {
                            /** @psalm-check-type-exact $o = Countable */
                        }
                        if (is_countable($cs)) {
                            /** @psalm-check-type-exact $cs = Countable */
                        }
                        if (!is_countable($as)) {
                            /** @psalm-check-type-exact $as = string */
                        }
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'notCountableCallableOrIterable' => [
                'code' => <<<'PHP'
                    <?php
                    function f(callable $c, iterable $i): void {
                        if (!is_countable($c)) { // 'strlen'
                            /** @psalm-check-type-exact $c = callable */
                        }
                        if (!is_countable($i)) { // a Generator
                            /** @psalm-check-type-exact $i = iterable<mixed, mixed> */
                        }
                    }
                    PHP,
            ],
            'arrayOfStringsMayBeCallable' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return array<int, lowercase-string> */
                    function a(): array {
                        return [];
                    }

                    $a = a();

                    if (!is_callable($a)) { // ['myclass', 'method']
                        throw new RuntimeException();
                    }
                    PHP,
                'assertions' => [
                    '$a===' => 'callable-array{0: non-empty-lowercase-string, 1: non-empty-lowercase-string}',
                ],
            ],
            'callableInstanceOfClosureKeepsItsSignature' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param callable(int): void $c */
                    function f(callable $c): void {
                        if ($c instanceof Closure) {
                            /** @psalm-check-type-exact $c = Closure(int):void */
                        }
                    }
                    PHP,
            ],
            'pureIterableInArrayOfObjectsKeepsItsPurity' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return iterable[pure]<int, string> */
                    function a(): iterable {
                        return [];
                    }

                    /** @return list<object> */
                    function objects(): array {
                        return [];
                    }

                    $a = a();

                    if (!in_array($a, objects(), true)) {
                        throw new RuntimeException();
                    }
                    PHP,
                'assertions' => [
                    '$a===' => 'Traversable[pure]<int, string>',
                ],
            ],
            'pureIterableThatIsAnObjectKeepsItsPurity' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return iterable[pure]<int, string> */
                    function a(): iterable {
                        return [];
                    }

                    $a = a();

                    if (!is_object($a)) {
                        throw new RuntimeException();
                    }
                    PHP,
                'assertions' => [
                    '$a===' => 'Traversable[pure]<int, string>',
                ],
            ],
            'callableArrayHasAClassOrObjectAndAMethodName' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return array<int, mixed> */
                    function a(): array {
                        return [];
                    }

                    $a = a();

                    if (!is_callable($a)) { // [new ArrayObject(), 'count']
                        throw new RuntimeException();
                    }
                    PHP,
                'assertions' => [
                    '$a===' => 'callable-array{0: class-string|object, 1: non-empty-string}',
                ],
            ],
            'notIdenticalToStringKeepsWiderTypes' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return array-key */
                    function k() { return 1; }
                    /** @return scalar */
                    function sc() { return 1; }
                    function c(): callable { return "strlen"; }
                    function s(): string { return ""; }

                    $s = s();
                    $k = k();
                    $loose = k();
                    $sc = sc();
                    $c = c();

                    $k = $k !== $s ? $k : null;
                    $loose = $loose != $s ? $loose : null;
                    $sc = $sc !== $s ? $sc : null;
                    $c = $c !== $s ? $c : null;
                    PHP,
                'assertions' => [
                    '$k===' => 'array-key|null',
                    '$loose===' => 'array-key|null',
                    '$sc===' => 'null|scalar',
                    '$c===' => 'callable[impure]|null',
                ],
            ],
            'notIdenticalToIntKeepsWiderTypes' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return array-key */
                    function k() { return 1; }
                    /** @return scalar */
                    function sc() { return 1; }
                    /** @return numeric */
                    function n() { return 1; }
                    function i(): int { return 1; }

                    $i = i();
                    $k = k();
                    $sc = sc();
                    $n = n();

                    $k = $k !== $i ? $k : null;
                    $sc = $sc !== $i ? $sc : null;
                    $n = $n !== $i ? $n : null;
                    PHP,
                'assertions' => [
                    '$k===' => 'array-key|null',
                    '$sc===' => 'null|scalar',
                    '$n===' => 'null|numeric',
                ],
            ],
            'notIdenticalToFloatKeepsWiderTypes' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return scalar */
                    function sc() { return 1; }
                    /** @return numeric */
                    function n() { return 1; }
                    function f(): float { return 1.0; }

                    $f = f();
                    $sc = sc();
                    $n = n();

                    $sc = $sc !== $f ? $sc : null;
                    $n = $n !== $f ? $n : null;
                    PHP,
                'assertions' => [
                    '$sc===' => 'null|scalar',
                    '$n===' => 'null|numeric',
                ],
            ],
            'notIdenticalToNumericKeepsWiderTypes' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return array-key */
                    function k() { return 1; }
                    /** @return scalar */
                    function sc() { return 1; }
                    /** @return numeric */
                    function n() { return 1; }

                    $n = n();
                    $k = k();
                    $sc = sc();

                    $k = $k !== $n ? $k : null;
                    $sc = $sc !== $n ? $sc : null;
                    PHP,
                'assertions' => [
                    '$k===' => 'array-key|null',
                    '$sc===' => 'null|scalar',
                ],
            ],
            'notIdenticalToBoolKeepsScalar' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return scalar */
                    function sc() { return 1; }
                    function b(): bool { return true; }

                    $b = b();
                    $sc = sc();

                    $sc = $sc !== $b ? $sc : null;
                    PHP,
                'assertions' => [
                    '$sc===' => 'null|scalar',
                ],
            ],
            'notIdenticalToBoolKeepsBoolLiterals' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return true|int */
                    function ti() { return rand(0, 1) ? true : 1; }
                    /** @return false|string */
                    function fs() { return rand(0, 1) ? false : ""; }
                    function b(): bool { return true; }

                    $b = b();
                    $ti = ti();
                    $fs = fs();

                    $ti = $ti !== $b ? $ti : null;
                    $fs = $fs !== $b ? $fs : null;
                    PHP,
                'assertions' => [
                    '$ti===' => 'int|null|true',
                    '$fs===' => 'false|null|string',
                ],
            ],
            'notIdenticalToSameWideTypeKeepsIt' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return resource */
                    function r() { return STDIN; }
                    function c(): callable { return "strlen"; }

                    $r1 = r();
                    $r2 = r();
                    $c1 = c();
                    $c2 = c();

                    $r1 = $r1 !== $r2 ? $r1 : null;
                    $c1 = $c1 !== $c2 ? $c1 : null;
                    PHP,
                'assertions' => [
                    '$r1===' => 'null|resource',
                    '$c1===' => 'callable[impure]|null',
                ],
            ],
            'notIdenticalTemplatedAssertionKeepsWiderTypes' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @template T
                     * @param T $expected
                     * @param mixed $actual
                     * @psalm-assert !=T $actual
                     */
                    function assertNotSameValue($expected, $actual): void {}
                    /** @return array-key */
                    function k() { return 1; }
                    function s(): string { return ""; }
                    function c(): callable { return "strlen"; }

                    $s = s();
                    $k = k();
                    $c1 = c();
                    $c2 = c();

                    assertNotSameValue($s, $k);
                    assertNotSameValue($c2, $c1);
                    PHP,
                'assertions' => [
                    '$k===' => 'array-key',
                    '$c1===' => 'callable[impure]',
                ],
            ],
            'offsetOfPropertyIsNotOfIntOrBool' => [
                'code' => <<<'PHP'
                    <?php
                    final class A {
                        /** @var scalar */
                        public $v = 1;
                        /** @var numeric */
                        public $n = 1;
                        /** @var array-key */
                        public $k = 1;
                    }
                    function a(): A { return new A(); }

                    $a = a();
                    $r = isset($a->v[0]) ? $a->v : null;
                    $rn = isset($a->n[0]) ? $a->n : null;
                    $rk = isset($a->k[0]) ? $a->k : null;
                    PHP,
                'assertions' => [
                    '$r===' => 'non-empty-string|null',
                    '$rn===' => 'non-empty-string|null',
                    '$rk===' => 'non-empty-string|null',
                ],
                'ignored_issues' => ['InvalidArrayAccess'],
            ],
            'notIdenticalToObjectKeepsCallableAndIterable' => [
                'code' => <<<'PHP'
                    <?php
                    function c(): callable { return "strlen"; }
                    function it(): iterable { return []; }
                    function o(): object { return new stdClass(); }

                    $o = o();
                    $c = c();
                    $it = it();

                    $c = $c !== $o ? $c : null;
                    $it = $it !== $o ? $it : null;
                    PHP,
                'assertions' => [
                    '$c===' => 'callable[impure]|null',
                    '$it===' => 'iterable<mixed, mixed>|null',
                ],
            ],
            'notIdenticalToArrayKeepsCallableAndIterable' => [
                'code' => <<<'PHP'
                    <?php
                    function c(): callable { return "strlen"; }
                    function it(): iterable { return []; }
                    function a(): array { return []; }

                    $a = a();
                    $c = c();
                    $it = it();

                    $c = $c !== $a ? $c : null;
                    $it = $it !== $a ? $it : null;
                    PHP,
                'assertions' => [
                    '$c===' => 'callable[impure]|null',
                    '$it===' => 'iterable<mixed, mixed>|null',
                ],
            ],
            'notIdenticalToResourceKeepsResource' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return resource */
                    function r() { return STDIN; }
                    /** @return resource|int */
                    function ri() { return 1; }

                    $r = r();
                    $ri = ri();

                    $ri = $ri !== $r ? $ri : null;
                    PHP,
                'assertions' => [
                    '$ri===' => 'int|null|resource',
                ],
            ],
            'notIdenticalToCallableKeepsCallable' => [
                'code' => <<<'PHP'
                    <?php
                    function c(): callable { return "strlen"; }
                    function ci(): callable|int { return 1; }

                    $c = c();
                    $ci = ci();

                    $ci = $ci !== $c ? $ci : null;
                    PHP,
                'assertions' => [
                    '$ci===' => 'callable[impure]|int|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'stringCastFromFloatMayBeInfOrNan' => [
                'code' => '<?php
                    /**
                     * @return numeric-string
                     */
                    function makeNumStringFromFloat(float $v) {
                        // (string) INF is "INF" and (string) NAN is "NAN"
                        return (string) $v;
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
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
            'stringNotRepresentableAsIntIsNeverLooselyEqualToZero' => [
                'code' => '<?php
                    /** @param "1e999"|"5" $s */
                    function foo(string $s): void {
                        if ($s == 0) {}
                    }',
                'error_message' => 'DocblockTypeContradiction',
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
            'callableNeverIdenticalToNumericString' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param numeric-string $s */
                    function f(callable $c, $s): void {
                        if ($c === $s) {}
                    }
                    PHP,
                'error_message' => 'does not contain',
            ],
            'callableStringNeverCountable' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param callable-string $c */
                    function f(string $c): void {
                        if (is_countable($c)) {}
                    }
                    PHP,
                'error_message' => 'DocblockTypeContradiction',
            ],
            'numericNeverIdenticalToCallableString' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @param numeric $n
                     * @param callable-string $s
                     */
                    function f($n, $s): void {
                        if ($n === $s) {}
                    }
                    PHP,
                'error_message' => 'DocblockTypeContradiction',
            ],
            'iterableNeverIdenticalToString' => [
                'code' => <<<'PHP'
                    <?php
                    function f(iterable $it, string $s): void {
                        if ($it === $s) {}
                    }
                    PHP,
                'error_message' => 'TypeDoesNotContainType',
            ],
            'numericNeverIdenticalToLiteralNonNumericString' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param numeric $n */
                    function f($n): void {
                        if ($n === 'abc') {}
                    }
                    PHP,
                'error_message' => 'DocblockTypeContradiction',
            ],
            'numericNeverIdenticalToCallable' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param numeric $n */
                    function f($n, callable $c): void {
                        if ($n === $c) {}
                    }
                    PHP,
                'error_message' => 'DocblockTypeContradiction',
            ],
            'arrayOfIntsIsNeverCallable' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param array<int, int> $a */
                    function f(array $a): void {
                        if (is_callable($a)) {}
                    }
                    PHP,
                'error_message' => 'TypeDoesNotContainType',
            ],
            'arrayOfEmptyStringsIsNeverCallable' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param array<int, ''> $a */
                    function f(array $a): void {
                        if (is_callable($a)) {}
                    }
                    PHP,
                'error_message' => 'TypeDoesNotContainType',
            ],
            'callableNeverIdenticalToFinalNonInvokableObject' => [
                'code' => <<<'PHP'
                    <?php
                    final class F {}

                    function f(callable $c, F $o): void {
                        if ($c === $o) {}
                    }
                    PHP,
                'error_message' => 'TypeDoesNotContainType',
            ],
            'callableNeverInstanceOfFinalNonInvokableClass' => [
                'code' => <<<'PHP'
                    <?php
                    final class F {}

                    function f(callable $c): void {
                        if ($c instanceof F) {}
                    }
                    PHP,
                'error_message' => 'TypeDoesNotContainType',
            ],
            'callableNeverIdenticalToFunctionOfAnotherSignature' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param callable(int): int $c */
                    function f(callable $c): void {
                        if ($c === 'strrev') {}
                    }
                    PHP,
                'error_message' => 'DocblockTypeContradiction',
            ],
            'nonEmptyScalarNeverIdenticalToEmptyString' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param non-empty-scalar $n */
                    function f($n): void {
                        if ($n === '') {}
                    }
                    PHP,
                'error_message' => 'DocblockTypeContradiction',
            ],
            'nonEmptyScalarNeverIdenticalToZero' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param non-empty-scalar $n */
                    function f($n): void {
                        if ($n === 0) {}
                    }
                    PHP,
                'error_message' => 'DocblockTypeContradiction',
            ],
            'nonEmptyScalarNeverIdenticalToFalse' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param non-empty-scalar $n */
                    function f($n): void {
                        if ($n === false) {}
                    }
                    PHP,
                'error_message' => 'DocblockTypeContradiction',
            ],
            'emptyScalarNeverIdenticalToNonEmptyString' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param empty-scalar $e */
                    function f($e): void {
                        if ($e === 'a') {}
                    }
                    PHP,
                'error_message' => 'DocblockTypeContradiction',
            ],
            'emptyScalarNeverIdenticalToTrue' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param empty-scalar $e */
                    function f($e): void {
                        if ($e === true) {}
                    }
                    PHP,
                'error_message' => 'DocblockTypeContradiction',
            ],
            'emptyScalarNeverIdenticalToCallable' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param empty-scalar $e */
                    function f($e, callable $c): void {
                        if ($e === $c) {}
                    }
                    PHP,
                'error_message' => 'DocblockTypeContradiction',
            ],
        ];
    }
}
