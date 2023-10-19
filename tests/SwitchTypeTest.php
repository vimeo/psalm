<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class SwitchTypeTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'getClassConstArg' => [
                'code' => '<?php
                    class A {
                        /**
                         * @return void
                         */
                        public function fooFoo() {

                        }
                    }

                    class B {
                        /**
                         * @return void
                         */
                        public function barBar() {

                        }
                    }

                    $a = rand(0, 10) ? new A() : new B();

                    switch (get_class($a)) {
                        case A::class:
                            $a->fooFoo();
                            break;

                        case B::class:
                            $a->barBar();
                            break;
                    }',
            ],
            'getClassExteriorArgClassConsts' => [
                'code' => '<?php
                    /** @return void */
                    function foo(Exception $e) {
                        switch (get_class($e)) {
                            case InvalidArgumentException::class:
                                $e->getMessage();
                                break;

                            case LogicException::class:
                                $e->getMessage();
                                break;
                        }
                    }

                    ',
            ],
            'switchGetClassVar' => [
                'code' => '<?php
                    class A {}
                    class B extends A {
                        public function foo(): void {}
                    }

                    function takesA(A $a): void {
                        $class = get_class($a);
                        switch ($class) {
                            case B::class:
                                $a->foo();
                                break;
                        }
                    }',
            ],
            'getTypeArg' => [
                'code' => '<?php
                    function testInt(int $var): void {

                    }

                    function testString(string $var): void {

                    }

                    $a = rand(0, 10) ? 1 : "two";

                    switch (gettype($a)) {
                        case "string":
                            testString($a);
                            break;

                        case "integer":
                            testInt($a);
                            break;
                    }',
            ],
            'switchTruthy' => [
                'code' => '<?php
                    class A {
                       /**
                        * @var ?string
                        */
                       public $a = null;
                       /**
                        * @var ?string
                        */
                       public $b = null;
                    }
                    function f(A $obj): string {
                      switch (true) {
                        case $obj->a !== null:
                          return $obj->a; // definitely not null
                        case !is_null($obj->b):
                          return $obj->b; // definitely not null
                        default:
                          throw new \InvalidArgumentException("$obj->a or $obj->b must be set");
                      }
                    }',
            ],
            'switchMoTruthy' => [
                'code' => '<?php
                    class A {
                       /**
                        * @var ?string
                        */
                       public $a = null;
                       /**
                        * @var ?string
                        */
                       public $b = null;
                    }
                    function f(A $obj): string {
                      switch (true) {
                        case $obj->a:
                          return $obj->a; // definitely not null
                        case $obj->b:
                          return $obj->b; // definitely not null
                        default:
                          throw new \InvalidArgumentException("$obj->a or $obj->b must be set");
                      }
                    }',
            ],
            'switchWithBadBreak' => [
                'code' => '<?php
                    class A {}

                    function foo(): A {
                        switch (rand(0,1)) {
                            case true:
                                return new A;
                                break;
                            default:
                                return new A;
                        }
                    }',
            ],
            'switchCaseExpression' => [
                'code' => '<?php
                    switch (true) {
                        case preg_match("/(d)ata/", "some data in subject string", $matches):
                            return $matches[1];
                        default:
                            throw new RuntimeException("none found");
                    }',
            ],
            'switchBools' => [
                'code' => '<?php
                    $x = false;
                    $y = false;

                    foreach ([1, 2, 3] as $v)  {
                        switch($v) {
                            case 3:
                                $y = true;
                                break;
                            case 2:
                                $x = true;
                                break;
                            default:
                                break;
                        }
                    }',
                'assertions' => [
                    '$x' => 'bool',
                    '$y' => 'bool',
                ],
            ],
            'continueIsBreak' => [
                'code' => '<?php
                    switch(2) {
                        case 2:
                            echo "two\n";
                            continue;
                    }',
            ],
            'defaultAboveCase' => [
                'code' => '<?php
                    function foo(string $a) : string {
                      switch ($a) {
                        case "a":
                          return "hello";

                        default:
                        case "b":
                          return "goodbye";
                      }
                    }',
            ],
            'dontResolveTypesBadly' => [
                'code' => '<?php
                    $a = new A;

                    switch (rand(0,1)) {
                        case 0:
                        case 1:
                            $dt = $a->maybeReturnsDT();
                            if (!is_null($dt)) {
                                $dt = $dt->format(\DateTime::ISO8601);
                            }
                            break;
                    }

                    class A {
                        public function maybeReturnsDT(): ?\DateTimeInterface {
                            return rand(0,1) ? new \DateTime("now") : null;
                        }
                    }',
            ],
            'issetInFallthrough' => [
                'code' => '<?php
                    function foo() : void {
                        switch(rand() % 4) {
                            case 0:
                                echo "here";
                                break;
                            case 1:
                                $x = rand() % 4;
                            case 2:
                                if (isset($x) && $x > 2) {
                                    echo "$x is large";
                                }
                                break;
                        }
                    }',
            ],
            'switchManyGetClass' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}
                    class C extends A {}
                    class D extends A {}

                    function foo(A $a) : void {
                        switch(get_class($a)) {
                            case B::class:
                            case C::class:
                            case D::class:
                                echo "goodbye";
                        }
                    }',
            ],
            'switchManyStrings' => [
                'code' => '<?php
                    function foo(string $s) : void {
                        switch($s) {
                            case "a":
                            case "b":
                            case "c":
                                echo "goodbye";
                        }
                    }',
            ],
            'allSwitchesMet' => [
                'code' => '<?php
                    $a = rand(0, 1) ? "a" : "b";

                    switch ($a) {
                        case "a":
                            $foo = "hello";
                            break;

                        case "b":
                            $foo = "goodbye";
                            break;
                    }

                    echo $foo;',
            ],
            'impossibleCaseDefaultWithThrow' => [
                'code' => '<?php
                    $a = rand(0, 1) ? "a" : "b";

                    switch ($a) {
                        case "a":
                            break;

                        case "b":
                            break;

                        default:
                            throw new \Exception("should never happen");
                    }',
            ],
            'switchOnUnknownInts' => [
                'code' => '<?php
                    function foo(int $a, int $b, int $c) : void {
                        switch ($a) {
                            case $b:
                                break;
                            case $c:
                                break;
                        }
                    }',
            ],
            'switchNullable1' => [
                'code' => '<?php
                    function foo(?string $s) : void {
                        switch ($s) {
                            case "hello":
                            case "goodbye":
                                echo "cool";
                                break;
                            case "hello again":
                                echo "cool";
                                break;
                        }
                    }',
            ],
            'switchNullable2' => [
                'code' => '<?php
                    function foo(?string $s) : void {
                        switch ($s) {
                            case "hello":
                                echo "cool";
                            case "goodbye":
                                echo "cooler";
                                break;
                            case "hello again":
                                echo "cool";
                                break;
                        }
                    }',
            ],
            'switchNullable3' => [
                'code' => '<?php
                    function foo(?string $s) : void {
                        switch ($s) {
                            case "hello":
                                echo "cool";
                                break;
                            case "goodbye":
                                echo "cool";
                                break;
                            case "hello again":
                                echo "cool";
                                break;
                        }
                    }',
            ],
            'switchNullable4' => [
                'code' => '<?php
                    function foo(?string $s, string $a, string $b) : void {
                        switch ($s) {
                            case $a:
                            case $b:
                                break;
                        }
                    }',
            ],
            'removeChangedVarsFromReasonableClauses' => [
                'code' => '<?php
                    function r() : bool {
                        return (bool)rand(0, 1);
                    }

                    function foo(string $s) : void {
                        if (($s === "a" || $s === "b")
                            && ($s === "a" || r())
                            && ($s === "b" || r())
                            && (r() || r())
                        ) {
                            // do something
                        } else {
                            return;
                        }

                        switch ($s) {
                            case "a":
                                break;
                            case "b":
                                break;
                        }
                    }',
            ],
            'preventBadClausesFromBleeding' => [
                'code' => '<?php
                    function foo (string $s) : void {
                        if ($s === "a" && rand(0, 1)) {

                        } elseif ($s === "b" && rand(0, 1)) {

                        } else {
                            return;
                        }

                        switch ($s) {
                            case "a":
                                echo "hello";
                                break;
                            case "b":
                                echo "goodbye";
                                break;
                        }
                    }',
            ],
            'alwaysReturns' => [
                'code' => '<?php
                    /**
                     * @param "a"|"b" $s
                     */
                    function foo(string $s) : string {
                        switch ($s) {
                            case "a":
                                return "hello";

                            case "b":
                            return "goodbye";
                        }
                    }',
            ],
            'switchVarConditionalAssignment' => [
                'code' => '<?php
                    switch (rand(0, 4)) {
                        case 0:
                            $b = 2;
                            if (rand(0, 1)) {
                                $a = false;
                                break;
                            }

                        default:
                            $a = true;
                            $b = 1;
                    }',
                'assertions' => [
                    '$a' => 'bool',
                    '$b' => 'int',
                ],
            ],
            'switchVarConditionalReAssignment' => [
                'code' => '<?php
                    $a = false;
                    switch (rand(0, 4)) {
                        case 0:
                            $b = 1;
                            if (rand(0, 1)) {
                                $a = false;
                                break;
                            }

                        default:
                            $a = true;
                    }',
                'assertions' => [
                    '$a' => 'bool',
                ],
            ],
            'moreThan30Cases' => [
                'code' => '<?php
                    function f(string $a) : void {
                        switch ($a) {
                            case "a":
                            case "b":
                            case "c":
                            case "d":
                            case "e":
                            case "f":
                            case "g":
                            case "h":
                            case "i":
                            case "j":
                            case "k":
                            case "l":
                            case "m":
                            case "n":
                            case "o":
                            case "p":
                            case "q":
                            case "r":
                            case "s":
                            case "t":
                            case "u":
                            case "v":
                            case "w":
                            case "x":
                            case "y":
                            case "z":
                            case "A":
                            case "B":
                            case "C":
                            case "D":
                            case "E":
                                return;
                        }
                    }',
            ],
            'anotherLongSwitch' => [
                'code' => '<?php
                    /**
                     * @param  ?string  $fq_const_name
                     * @param  string   $const_name
                     * @param array<string, bool> $predefined_constants
                     *
                     * @return string|null
                     */
                    function getGlobalConstType(
                        ?string $fq_const_name,
                        string $const_name,
                        array $predefined_constants
                    ) {
                        if ($const_name === "STDERR"
                            || $const_name === "STDOUT"
                            || $const_name === "STDIN"
                        ) {
                            return "hello";
                        }

                        if (isset($predefined_constants[$fq_const_name])
                            || isset($predefined_constants[$const_name])
                        ) {
                            switch ($const_name) {
                                case "PHP_MAJOR_VERSION":
                                case "PHP_ZTS":
                                    return "int";

                                case "PHP_FLOAT_EPSILON":
                                case "PHP_FLOAT_MAX":
                                case "PHP_FLOAT_MIN":
                                    return "float";
                            }

                            if ($fq_const_name && isset($predefined_constants[$fq_const_name])) {
                                return "mixed";
                            }

                            return "hello";
                        }

                        return null;
                    }',
            ],
            'evenLongerSwitch' => [
                'code' => '<?php
                    function foo(string $f) : string {
                        switch ($f) {
                            case "str1";
                                return "foo1";
                            case "str2";
                                return "foo2";
                            case "str3";
                                return "foo3";
                            case "str4";
                                return "foo4";
                            case "str5";
                                return "foo5";
                            case "str6";
                                return "foo6";
                            case "str7";
                                return "foo7";
                            case "str8";
                                return "foo8";
                            case "str9";
                                return "foo9";
                            case "str10";
                                return "foo10";
                            case "str11";
                                return "foo11";
                            case "str12";
                                return "foo12";
                            case "str13";
                                return "foo13";
                            case "str14";
                                return "foo14";
                            case "str15";
                                return "foo15";
                            case "str16";
                                return "foo16";
                            case "str17";
                                return "foo17";
                            case "str18";
                                return "foo18";
                            case "str19";
                                return "foo19";
                            case "str20";
                                return "foo20";
                            case "str21";
                                return "foo21";
                            case "str22";
                                return "foo22";
                            case "str23";
                                return "foo23";
                            case "str24";
                                return "foo24";
                            case "str25";
                                return "foo25";
                            case "str26";
                                return "foo26";
                            case "str27";
                                return "foo27";
                            case "str28";
                                return "foo28";
                            case "str29";
                                return "foo29";
                            case "str30";
                                return "foo30";
                            case "str31";
                                return "foo31";
                            case "str32";
                                return "foo32";
                            case "str33";
                                return "foo33";
                            case "str34";
                                return "foo34";
                            case "str35";
                                return "foo35";
                            case "str36";
                                return "foo36";
                            case "str37";
                                return "foo37";
                            case "str38";
                                return "foo38";
                            case "str39";
                                return "foo39";
                            case "str40";
                                return "foo40";
                            case "str41";
                                return "foo41";
                            case "str42";
                                return "foo42";
                            case "str43";
                                return "foo43";
                            case "str44";
                                return "foo44";
                            case "str45";
                                return "foo45";
                            case "str46";
                                return "foo46";
                            case "str47";
                                return "foo47";
                            case "str48";
                                return "foo48";
                            case "str49";
                                return "foo49";
                            case "str50";
                                return "foo50";
                            case "str51";
                                return "foo51";
                            case "str52";
                                return "foo52";
                            case "str53";
                                return "foo53";
                            case "str54";
                                return "foo54";
                            case "str55";
                                return "foo55";
                            case "str56";
                                return "foo56";
                            case "str57";
                                return "foo57";
                            case "str58";
                                return "foo58";
                            case "str59";
                                return "foo59";
                            case "str60";
                                return "foo60";
                            case "str61";
                                return "foo61";
                            case "str62";
                                return "foo62";
                            case "str63";
                                return "foo63";
                            case "str64";
                                return "foo64";
                            case "str65";
                                return "foo65";
                            case "str66";
                                return "foo66";
                            case "str67";
                                return "foo67";
                            case "str68";
                                return "foo68";
                            case "str70";
                                return "foo70";
                            case "str71";
                                return "foo71";
                            case "str72";
                                return "foo72";
                            case "str73";
                                return "foo73";
                            case "str74";
                                return "foo74";
                            case "str75";
                                return "foo75";
                            case "str76";
                                return "foo76";
                            case "str77";
                                return "foo77";
                            case "str78";
                                return "foo78";
                            case "str79";
                                return "foo79";
                            case "str80";
                                return "foo80";
                            case "str81";
                                return "foo81";
                            case "str82";
                                return "foo82";
                            case "str83";
                                return "foo83";
                            case "str84";
                                return "foo84";
                            case "str85";
                                return "foo85";
                            case "str86";
                                return "foo86";
                            case "str87";
                                return "foo87";
                            case "str88";
                                return "foo88";
                            case "str89";
                                return "foo89";
                            case "str90";
                                return "foo90";
                            case "str91";
                                return "foo91";
                            case "str92";
                                return "foo92";
                            case "str93";
                                return "foo93";
                            case "str94";
                                return "foo94";
                            case "str95";
                                return "foo95";
                            case "str96";
                                return "foo96";
                            case "str97";
                                return "foo97";
                            case "str98";
                                return "foo98";
                            case "str99";
                                return "foo99";
                            case "str100";
                                return "foo100";
                            case "str101";
                                return "foo101";
                            case "str102";
                                return "foo102";
                            case "str103";
                                return "foo103";
                            case "str104";
                                return "foo104";
                            case "str105";
                                return "foo105";
                            case "str106";
                                return "foo106";
                            case "str107";
                                return "foo107";
                            case "str108";
                                return "foo108";
                            case "str109";
                                return "foo109";
                            case "str110";
                                return "foo110";
                            case "str111";
                                return "foo111";
                            case "str112";
                                return "foo112";
                            case "str113";
                                return "foo113";
                            case "str114";
                                return "foo114";
                            case "str115";
                                return "foo115";
                            case "str116";
                                return "foo116";
                            case "str117";
                                return "foo117";
                            case "str118";
                                return "foo118";
                            case "str119";
                                return "foo119";
                            case "str120";
                                return "foo120";
                            case "str121";
                                return "foo121";
                            case "str122";
                                return "foo122";
                            case "str123";
                                return "foo123";
                            case "str124";
                                return "foo124";
                            case "str125";
                                return "foo125";
                            case "str126";
                                return "foo126";
                            case "str127";
                                return "foo127";
                            case "str128";
                                return "foo128";
                            case "str129";
                                return "foo129";
                            case "str130";
                                return "foo130";
                            case "str131";
                                return "foo131";
                            case "str132";
                                return "foo132";
                            case "str133";
                                return "foo133";
                            case "str134";
                                return "foo134";
                            case "str135";
                                return "foo135";
                            case "str136";
                                return "foo136";
                            case "str137";
                                return "foo137";
                            case "str138";
                                return "foo138";
                            case "str139";
                                return "foo139";
                            case "str140";
                                return "foo140";
                            case "str141";
                                return "foo141";
                            case "str142";
                                return "foo142";
                            case "str143";
                                return "foo143";
                            case "str144";
                                return "foo144";
                            case "str145";
                                return "foo145";
                            case "str146";
                                return "foo146";
                            case "str147";
                                return "foo147";
                            case "str148";
                                return "foo148";
                            case "str149";
                                return "foo149";
                            default;
                                return "foo69";
                        }
                    }',
            ],
            'switchTruthyWithBoolean' => [
                'code' => '<?php
                    $a = rand(0,1) ? new \DateTime() : null;

                    switch(true) {
                        case $a !== null && $a->format("Y") === "2020":
                            $a->format("d-m-Y");
                    }',
            ],
            'evenWorseSwitch' => [
                'code' => '<?php
                    function foo(string $locale) : int {
                        switch ($locale) {
                            case "af":
                            case "af_ZA":
                            case "bn":
                            case "bn_BD":
                            case "bn_IN":
                            case "bg":
                            case "bg_BG":
                            case "ca":
                            case "ca_AD":
                            case "ca_ES":
                            case "ca_FR":
                            case "ca_IT":
                            case "da":
                            case "da_DK":
                            case "de":
                            case "de_AT":
                            case "de_BE":
                            case "de_CH":
                            case "de_DE":
                            case "de_LI":
                            case "de_LU":
                            case "el":
                            case "el_CY":
                            case "el_GR":
                            case "en":
                            case "en_AG":
                            case "en_AU":
                            case "en_BW":
                            case "en_CA":
                            case "en_DK":
                            case "en_GB":
                            case "en_HK":
                            case "en_IE":
                            case "en_IN":
                            case "en_NG":
                            case "en_NZ":
                            case "en_PH":
                            case "en_SG":
                            case "en_US":
                            case "en_ZA":
                            case "en_ZM":
                            case "en_ZW":
                            case "es_VE":
                                return 3;
                        }

                        return 4;
                    }',
            ],
            'suppressParadox' => [
                'code' => '<?php
                    /** @psalm-var 1|2|3 $i */
                    $i = rand(1, 3);

                    /** @psalm-suppress ParadoxicalCondition */
                    switch($i) {
                        case 1: break;
                        case 2: break;
                        case 3: break;
                        default:
                            echo "bar";
                    }',
            ],
            'switchGetClassProperty' => [
                'code' => '<?php
                    class A {}
                    class B {}

                    class C {
                        /** @var mixed */
                        public $a;

                        function foo() : void {
                            if (rand(0, 1)) {
                                $this->a = new A();
                            }

                            /** @psalm-suppress MixedArgument */
                            switch (get_class($this->a)) {
                                case B::class:
                                    echo "here";
                            }
                        }
                    }',
            ],
            'noCrashOnComplicatedCases' => [
                'code' => '<?php
                    class A {}
                    class B {}
                    class C {}

                    /**
                     * @psalm-suppress MixedAssignment
                     * @psalm-suppress MixedArrayAccess
                     */
                    function foo(array $columns) : bool
                    {
                        foreach ($columns as $c) {
                            switch (true) {
                                case isset($c["a"]) || $c["b"] || $c["c"]:
                                case $c["t"] instanceof A && rand(0, 1):
                                case $c["t"] instanceof B && rand(0, 1):
                                case $c["t"] instanceof C && rand(0, 1):
                                    return false;
                            }
                        }

                        return true;
                    }',
            ],
            'alwaysReturnsWithConditionalReturnFirst' => [
                'code' => '<?php
                    function getRows(string $s) : int {
                        if (rand(0, 1)) {
                            return 1;
                        }

                        switch ($s) {
                            case "a":
                                return 2;
                            default:
                                return 1;
                        }
                    }',
            ],
            'loopWithSwitchAlwaysReturns' => [
                'code' => '<?php
                    function b(): int {
                        foreach([1,2] as $i) {
                            continue;
                        }

                        switch (random_int(1, 10)) {
                            case 1:
                                return 1;
                            default:
                                return 2;
                        }
                    }',
            ],
            'noCrashWithComplexMethodCallSwitches' => [
                'code' => '<?php
                    function fromFoo(): int {
                       switch (true) {
                           case (rand(0, 1) && rand(0, 2)):
                           case (rand(0, 3) && rand(0, 4)):
                           case (rand(0, 5) && rand(0, 6)):
                           case (rand(0, 7) && rand(0, 8)):
                           case (rand(0, 7) && rand(0, 8)):
                           case (rand(0, 7) && rand(0, 8)):
                           case (rand(0, 7) && rand(0, 8)):
                               return 1;
                           default:
                               return 0;
                       }
                   }',
            ],
            'terminatesAfterContinueInsideWhile' => [
                'code' => '<?php
                    function foo(): int {
                        switch (true) {
                            default:
                                while (rand(0, 1)) {
                                    if (rand(0, 1)) {
                                        continue;
                                    }
                                    return 1;
                                }
                                return 2;
                        }
                    }',
            ],
            'switchDoesNotReturnNever' => [
                'code' => '<?php
                    function a(int $i): ?bool {
                        switch($i) {
                            case 1:
                                return false;
                            default:
                                return null;
                        }
                    }',
            ],
            'nonTotalSwitchStillSometimesExits' => [
                'code' => '<?php
                    function takesAnInt(string $str): ?int{
                        switch ($str) {
                            case "a":
                                return 5;

                            case "b":
                                return null;
                        }

                        throw new Exception();
                    }',
            ],
            'switchWithLeftoverFunctionCallUsesTheFunction' => [
                'code' => '<?php

                    function bar (string $name): int {
                        switch ($name) {
                                case "a":
                                case ucfirst("a"):
                                    return 1;
                        }
                        return -1;
                    }',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'switchReturnTypeWithFallthroughAndBreak' => [
                'code' => '<?php
                    class A {
                        /** @return bool */
                        public function fooFoo() {
                            switch (rand(0,10)) {
                                case 1:
                                    break;
                                default:
                                    return true;
                            }
                        }
                    }',
                'error_message' => 'InvalidNullableReturnType',
            ],
            'switchReturnTypeWithFallthroughAndConditionalBreak' => [
                'code' => '<?php
                    class A {
                        /** @return bool */
                        public function fooFoo() {
                            switch (rand(0,10)) {
                                case 1:
                                    if (rand(0,10) === 5) {
                                        break;
                                    }
                                default:
                                    return true;
                            }
                        }
                    }',
                'error_message' => 'InvalidNullableReturnType',
            ],
            'switchReturnTypeWithNoDefault' => [
                'code' => '<?php
                    class A {
                        /** @return bool */
                        public function fooFoo() {
                            switch (rand(0,10)) {
                                case 1:
                                case 2:
                                    return true;
                            }
                        }
                    }',
                'error_message' => 'InvalidNullableReturnType',
            ],
            'getClassArgWrongClass' => [
                'code' => '<?php
                    class A {
                        /** @return void */
                        public function fooFoo() {

                        }
                    }

                    class B {
                        /** @return void */
                        public function barBar() {

                        }
                    }

                    $a = rand(0, 10) ? new A() : new B();

                    switch (get_class($a)) {
                        case A::class:
                            $a->barBar();
                            break;
                    }',
                'error_message' => 'UndefinedMethod',
            ],
            'getClassMissingClass' => [
                'code' => '<?php
                    class A {}
                    class B {}

                    $a = rand(0, 10) ? new A() : new B();

                    switch (get_class($a)) {
                        case C::class:
                            break;
                    }',
                'error_message' => 'UndefinedClass',
            ],
            'getTypeNotAType' => [
                'code' => '<?php
                    $a = rand(0, 10) ? 1 : "two";

                    switch (gettype($a)) {
                        case "int":
                            break;
                    }',
                'error_message' => 'UnevaluatedCode',
            ],
            'getTypeArgWrongArgs' => [
                'code' => '<?php
                    function testInt(int $var): void {

                    }

                    function testString(string $var): void {

                    }

                    $a = rand(0, 10) ? 1 : "two";

                    switch (gettype($a)) {
                        case "string":
                            testInt($a);

                        case "integer":
                            testString($a);
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
            'switchBadMethodCallInCase' => [
                'code' => '<?php
                    function f(string $p): void { }

                    switch (true) {
                        case $q = (bool) rand(0,1):
                            f($q); // this type problem is not detected
                            break;
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
            'continueIsNotBreak' => [
                'code' => '<?php
                    switch(2) {
                        case 2:
                            echo "two\n";
                            continue 2;
                    }',
                'error_message' => 'ContinueOutsideLoop',
            ],
            'defaultAboveCaseThatBreaks' => [
                'code' => '<?php
                    function foo(string $a) : string {
                      switch ($a) {
                        case "a":
                          return "hello";

                        default:
                        case "b":
                          break;

                        case "c":
                          return "goodbye";
                      }
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'SKIPPED-switchManyGetClassWithRepetitionWithProperLineNumber' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}
                    class C extends A {}
                    class D extends A {}

                    function foo(A $a) : void {
                        switch(get_class($a)) {
                            case B::class:
                            case C::class:
                            case B::class:
                            case C::class:
                            case D::class:
                                echo "goodbye";
                        }
                    }',
                'error_message' => 'RedundantCondition - src' . DIRECTORY_SEPARATOR . 'somefile.php:10',
                'ignored_issues' => ['ParadoxicalCondition'],
            ],
            'repeatedCaseValue' => [
                'code' => '<?php
                    $a = rand(0, 1);
                    switch ($a) {
                        case 0:
                            break;

                        case 0:
                            echo "I never get here";
                    }',
                'error_message' => 'ParadoxicalCondition - src' . DIRECTORY_SEPARATOR . 'somefile.php:7',
            ],
            'impossibleCaseValue' => [
                'code' => '<?php
                    $a = rand(0, 1) ? "a" : "b";

                    switch ($a) {
                        case "a":
                            break;

                        case "b":
                            break;

                        case "c":
                            echo "impossible";
                    }',
                'error_message' => 'TypeDoesNotContainType - src' . DIRECTORY_SEPARATOR . 'somefile.php:11',
            ],
            'impossibleCaseDefault' => [
                'code' => '<?php
                    $a = rand(0, 1) ? "a" : "b";

                    switch ($a) {
                        case "a":
                            break;

                        case "b":
                            break;

                        default:
                            echo "impossible";
                    }',
                'error_message' => 'ParadoxicalCondition - src' . DIRECTORY_SEPARATOR . 'somefile.php:11',
            ],
            'breakWithoutSettingVar' => [
                'code' => '<?php
                    function foo(int $i) : void {
                        switch ($i) {
                            case 0:
                                if (rand(0, 1)) {
                                    break;
                                }

                            default:
                                $a = true;
                        }

                        if ($a) {}
                    }',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
            'getClassExteriorArgStringType' => [
                'code' => '<?php
                    /** @return void */
                    function foo(Exception $e) {
                        switch (get_class($e)) {
                            case "InvalidArgumentException":
                                break;
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'paradoxInFunctionCall' => [
                'code' => '<?php
                    /** @psalm-return 1|2|3 */
                    function foo() {
                        /** @psalm-var 1|2|3 $bar */
                        $bar = rand(1, 3);
                        return $bar;
                    }

                    switch(foo()) {
                        case 1: break;
                        case 2: break;
                        case 3: break;
                        default:
                            echo "bar";
                    }',
                'error_message' => 'ParadoxicalCondition',
            ],
            'loopWithSwitchDoesntReturnFirstCase' => [
                'code' => '<?php
                    function b(): int {
                        switch (random_int(1, 10)) {
                            case 1:
                                foreach([1,2] as $i) {
                                    continue;
                                }
                                break;

                            default:
                                return 2;
                        }
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'clearDependentTypeWhenAssigning' => [
                'code' => '<?php
                    class A {}

                    class AChild extends A {
                        public function bar() : void {}
                    }

                    class B {}

                    function foo(A $a) : void {
                        $a_class = get_class($a);

                        $a = new B();

                        switch ($a_class) {
                            case AChild::class:
                                $a->bar();
                        }
                    }',
                'error_message' => 'UndefinedMethod',
            ],
        ];
    }
}
