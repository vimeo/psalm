<?php
namespace Psalm\Tests;

use const DIRECTORY_SEPARATOR;

class ScopeTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'newVarInIf' => [
                '<?php
                    if (rand(0,100) === 10) {
                        $badge = "hello";
                    }
                    else {
                        $badge = "goodbye";
                    }

                    echo $badge;',
            ],
            'newVarInIfWithElseReturn' => [
                '<?php
                    if (rand(0,100) === 10) {
                        $badge = "hello";
                    }
                    else {
                        throw new \Exception();
                    }

                    echo $badge;',
            ],
            'assignmentInIf' => [
                '<?php
                    if ($row = (rand(0, 10) ? [5] : null)) {
                        echo $row[0];
                    }',
            ],
            'negatedAssignmentInIf' => [
                '<?php
                    if (!($row = (rand(0, 10) ? [5] : null))) {
                        // do nothing
                    }
                    else {
                        echo $row[0];
                    }',
            ],
            'assignInElseIf' => [
                '<?php
                    if (rand(0, 10) > 5) {
                        echo "hello";
                    } elseif ($row = (rand(0, 10) ? [5] : null)) {
                        echo $row[0];
                    }',
            ],
            'ifNotEqualsFalse' => [
                '<?php
                    if (($row = rand(0,10) ? [1] : false) !== false) {
                       echo $row[0];
                    }',
            ],
            'ifNotEqualsNull' => [
                '<?php
                    if (($row = rand(0,10) ? [1] : null) !== null) {
                       echo $row[0];
                    }',
            ],
            'ifNullNotEquals' => [
                '<?php
                    if (null !== ($row = rand(0,10) ? [1] : null)) {
                       echo $row[0];
                    }',
            ],
            'ifNullEquals' => [
                '<?php
                    if (null === ($row = rand(0,10) ? [1] : null)) {

                    } else {
                        echo $row[0];
                    }',
            ],
            'passedByRefInIf' => [
                '<?php
                    if (preg_match("/bad/", "badger", $matches)) {
                        echo (string)$matches[0];
                    }',
            ],
            'passByRefInIfCheckAfter' => [
                '<?php
                    if (!preg_match("/bad/", "badger", $matches)) {
                        exit();
                    }
                    echo (string)$matches[0];',
            ],
            'passByRefInIfWithBoolean' => [
                '<?php
                    $a = (bool)rand(0, 1);
                    if ($a && preg_match("/bad/", "badger", $matches)) {
                        echo (string)$matches[0];
                    }',
            ],
            'passByRefInVarWithBoolean' => [
                '<?php
                    $a = preg_match("/bad/", "badger", $matches) > 0;
                    if ($a) {
                        echo (string)$matches[1];
                    }',
            ],
            'functionExists' => [
                '<?php
                    if (true && function_exists("flabble")) {
                        flabble();
                    }',
            ],
            'nestedPropertyFetchInElseif' => [
                '<?php
                    class A {
                        /** @var A|null */
                        public $foo;

                        public function __toString(): string {
                            return "boop";
                        }
                    }

                    $a = rand(0, 10) === 5 ? new A(): null;

                    if (false) {

                    }
                    elseif ($a && $a->foo) {
                        echo $a;
                    }',
            ],
            'globalReturn' => [
                '<?php
                    $foo = "foo";

                    function a(): string {
                        global $foo;

                        return $foo;
                    }',
            ],
            'globalReturnWithAnnotation' => [
                '<?php
                    /**
                     * @global string $foo
                     */
                    function a(): string {
                        global $foo;

                        return $foo;
                    }',
            ],
            'negateAssertionAndOther' => [
                '<?php
                    $a = rand(0, 10) ? "hello" : null;

                    if (rand(0, 10) > 1 && is_string($a)) {
                        throw new \Exception("bad");
                    }',
                'assertions' => [
                    '$a' => 'string|null',
                ],
            ],
            'repeatAssertionWithOther' => [
                '<?php
                    $a = rand(0, 10) ? "hello" : null;

                    if (rand(0, 10) > 1 || is_string($a)) {
                        if (is_string($a)) {
                            echo strpos("e", $a);
                        }
                    }',
                'assertions' => [
                    '$a' => 'string|null',
                ],
                'error_levels' => ['PossiblyFalseArgument'],
            ],
            'refineOredType' => [
                '<?php
                    class A {
                        public function doThing(): void
                        {
                            if ($this instanceof B || $this instanceof C) {
                                if ($this instanceof B) {

                                }
                            }
                        }
                    }
                    class B extends A {}
                    class C extends A {}',
            ],
            'instanceOfSubtraction' => [
                '<?php
                    class Foo {}
                    class FooBar extends Foo{}
                    class FooBarBat extends FooBar{}
                    class FooMoo extends Foo{}

                    $a = new Foo();

                    if ($a instanceof FooBar && !$a instanceof FooBarBat) {

                    } elseif ($a instanceof FooMoo) {

                    }',
            ],
            'staticNullRef' => [
                '<?php
                    /** @return void */
                    function foo() {
                        static $bar = null;

                        if ($bar !== null) {
                            // do something
                        }

                        $bar = 5;
                    }',
            ],
            'suppressInvalidThis' => [
                '<?php
                    /** @psalm-suppress InvalidScope */
                    if (!isset($this->value)) {
                        $this->value = ["x", "y"];
                        echo count($this->value) - 2;
                    }',
                'assertions' => [],
                'error_levels' => ['MixedPropertyAssignment', 'MixedArgument'],
            ],
            'typedStatic' => [
                '<?php
                    function a(): ?int {
                        /** @var ?int */
                        static $foo = 5;

                        if (rand(0, 1)) {
                            return $foo;
                        }

                        $foo = null;

                        return $foo;
                    }',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
    {
        return [
            'possiblyUndefinedVarInIf' => [
                '<?php
                    if (rand(0,100) === 10) {
                        $b = "s";
                    }

                    echo $b;',
                'error_message' => 'PossiblyUndefinedGlobalVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:26 - Possibly undefined global '
                    . 'variable $b, first seen on line 3',
            ],
            'possiblyUndefinedArrayInIf' => [
                '<?php
                    if (rand(0,100) === 10) {
                        $array[] = "hello";
                    }

                    echo $array;',
                'error_message' => 'PossiblyUndefinedGlobalVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:25 - Possibly undefined global '
                    . 'variable $array, first seen on line 3',
            ],
            'invalidGlobal' => [
                '<?php
                    $a = "heli";

                    global $a;',
                'error_message' => 'InvalidGlobal',
            ],
            'thisInStatic' => [
                '<?php
                    class A {
                        public static function fooFoo() {
                            echo $this;
                        }
                    }',
                'error_message' => 'InvalidScope',
            ],
            'static' => [
                '<?php
                    function a(): string {
                        static $foo = "foo";

                        return $foo;
                    }',
                'error_message' => 'MixedReturnStatement',
            ],
            'staticNullRef' => [
                '<?php
                    /** @return void */
                    function foo() {
                        /** @var int */
                        static $bar = 5;

                        if ($bar === null) {
                            // do something
                        }

                        $bar = 4;
                    }',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'typedStaticCannotHaveNullDefault' => [
                '<?php
                    function a(): void {
                        /** @var string */
                        static $foo = null;
                    }',
                'error_message' => 'ReferenceConstraintViolation',
            ],
            'typedStaticCannotBeAssignedInt' => [
                '<?php
                    function a(): void {
                        /** @var string */
                        static $foo = "foo";

                        $foo = 5;
                    }',
                'error_message' => 'ReferenceConstraintViolation',
            ],
            'typedStaticCannotBeAssignedNull' => [
                '<?php
                    function a(): void {
                        /** @var string */
                        static $foo = "foo";

                        $foo = null;
                    }',
                'error_message' => 'ReferenceConstraintViolation',
            ],
        ];
    }
}
