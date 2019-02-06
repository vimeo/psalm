<?php
namespace Psalm\Tests;

class SwitchTypeTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return array
     */
    public function providerValidCodeParse()
    {
        return [
            'getClassConstArg' => [
                '<?php
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

                    $a = rand(0, 10) ? new A(): new B();

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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    switch (true) {
                        case preg_match("/(d)ata/", "some data in subject string", $matches):
                            return $matches[1];
                        default:
                            throw new RuntimeException("none found");
                    }',
            ],
            'switchBools' => [
                '<?php
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
                '<?php
                    switch(2) {
                        case 2:
                            echo "two\n";
                            continue;
                    }',
            ],
            'defaultAboveCase' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    function foo(?string $s, string $a, string $b) : void {
                        switch ($s) {
                            case $a:
                            case $b:
                                break;
                        }
                    }',
            ],
            'removeChangedVarsFromReasonableClauses' => [
                '<?php
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
                    }'
            ],
            'preventBadClausesFromBleeding' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
        ];
    }

    /**
     * @return array
     */
    public function providerInvalidCodeParse()
    {
        return [
            'switchReturnTypeWithFallthroughAndBreak' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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

                    $a = rand(0, 10) ? new A(): new B();

                    switch (get_class($a)) {
                        case A::class:
                            $a->barBar();
                            break;
                    }',
                'error_message' => 'UndefinedMethod',
            ],
            'getClassMissingClass' => [
                '<?php
                    class A {}
                    class B {}

                    $a = rand(0, 10) ? new A(): new B();

                    switch (get_class($a)) {
                        case C::class:
                            break;
                    }',
                'error_message' => 'UndefinedClass',
            ],
            'getTypeNotAType' => [
                '<?php
                    $a = rand(0, 10) ? 1 : "two";

                    switch (gettype($a)) {
                        case "int":
                            break;
                    }',
                'error_message' => 'UnevaluatedCode',
            ],
            'getTypeArgWrongArgs' => [
                '<?php
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
                '<?php
                    function f(string $p): void { }

                    switch (true) {
                        case $q = (bool) rand(0,1):
                            f($q); // this type problem is not detected
                            break;
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
            'continueIsNotBreak' => [
                '<?php
                    switch(2) {
                        case 2:
                            echo "two\n";
                            continue 2;
                    }',
                'error_message' => 'ContinueOutsideLoop',
            ],
            'defaultAboveCaseThatBreaks' => [
                '<?php
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
                '<?php
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
                'error_message' => 'RedundantCondition - src/somefile.php:10',
                'error_levels' => ['ParadoxicalCondition'],
            ],
            'repeatedCaseValue' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                'error_message' => 'PossiblyUndefinedVariable'
            ],
            'getClassExteriorArgStringType' => [
                '<?php
                    /** @return void */
                    function foo(Exception $e) {
                        switch (get_class($e)) {
                            case "InvalidArgumentException":
                                $e->getMessage();
                                break;
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType - src' . DIRECTORY_SEPARATOR . 'somefile.php:5 - string(InvalidArgumentException) cannot be identical to class-string',
            ],
        ];
    }
}
