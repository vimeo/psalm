<?php

declare(strict_types=1);

namespace Psalm\Tests\TypeReconciliation;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class ScopeTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'newVarInIf' => [
                'code' => '<?php
                    if (rand(0,100) === 10) {
                        $badge = "hello";
                    }
                    else {
                        $badge = "goodbye";
                    }

                    echo $badge;',
            ],
            'newVarInIfWithElseReturn' => [
                'code' => '<?php
                    if (rand(0,100) === 10) {
                        $badge = "hello";
                    }
                    else {
                        throw new \Exception();
                    }

                    echo $badge;',
            ],
            'passByRefInVarWithBoolean' => [
                'code' => '<?php
                    $a = preg_match("/bad/", "badger", $matches) > 0;
                    if ($a) {
                        echo $matches[1];
                    }',
            ],
            'functionExists' => [
                'code' => '<?php
                    if (rand(0,1) && function_exists("flabble")) {
                        flabble();
                    }',
            ],
            'nestedPropertyFetchInElseif' => [
                'code' => '<?php
                    class A {
                        /** @var A|null */
                        public $foo;

                        public function __toString(): string {
                            return "boop";
                        }
                    }

                    $a = rand(0, 10) === 5 ? new A() : null;

                    if (rand(0, 1)) {

                    } elseif ($a && $a->foo) {
                        echo $a;
                    }',
            ],
            'globalReturn' => [
                'code' => '<?php
                    $foo = "foo";

                    function a(): string {
                        global $foo;

                        return $foo;
                    }',
            ],
            'globalReturnWithAnnotation' => [
                'code' => '<?php
                    /**
                     * @global string $foo
                     */
                    function a(): string {
                        global $foo;

                        return $foo;
                    }',
            ],
            'negateAssertionAndOther' => [
                'code' => '<?php
                    $a = rand(0, 10) ? "hello" : null;

                    if (rand(0, 10) > 1 && is_string($a)) {
                        throw new \Exception("bad");
                    }',
                'assertions' => [
                    '$a' => 'null|string',
                ],
            ],
            'repeatAssertionWithOther' => [
                'code' => '<?php
                    function getString() : string {
                        return "hello";
                    }
                    $a = rand(0, 10) ? getString() : null;

                    if (rand(0, 10) > 1 || is_string($a)) {
                        if (is_string($a)) {
                            echo strpos($a, "e");
                        }
                    }',
                'assertions' => [
                    '$a' => 'null|string',
                ],
                'ignored_issues' => ['PossiblyFalseArgument'],
            ],
            'refineOredType' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    /** @psalm-suppress InvalidScope */
                    if (!isset($this->value)) {
                        $this->value = ["x", "y"];
                        echo count($this->value) - 2;
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedPropertyAssignment', 'MixedArgument'],
            ],
            'typedStatic' => [
                'code' => '<?php
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
            'psalmScopeThisInTemplate' => [
                'code' => '<?php
                    $e = new Exception(); // necessary to trick Psalm’s scanner for test
                    /** @psalm-scope-this Exception */
                ?>
                <h1><?= $this->getMessage() ?></h1>',
            ],
            'psalmScopeThisInTemplateFQCN' => [
                'code' => '<?php
                    $e = new Exception(); // necessary to trick Psalm’s scanner for test
                    /** @psalm-scope-this \Exception */
                ?>
                <h1><?= $this->getMessage() ?></h1>',
            ],
            'psalmVarThisInTemplate' => [
                'code' => '<?php
                    $e = new Exception(); // necessary to trick Psalm’s scanner for test
                    /** @var Exception $this */
                ?>
                <h1><?= $this->getMessage() ?></h1>',
            ],
            'psalmVarThisAbsoluteClassInTemplate' => [
                'code' => '<?php
                    $e = new Exception(); // necessary to trick Psalm’s scanner for test
                    /** @var \Exception $this */
                ?>
                <h1><?= $this->getMessage() ?></h1>',
            ],
            'psalmScopeWithNamespace' => [
                'code' => <<<'PHP'
                    <?php
                    namespace A {
                        class C { public function f(): void {} }
                    }
                    namespace B {
                        use A\C;
                        /** @psalm-scope-this C */
                        ?>
                        <h1><?php $this->f(); ?></h1>
                        <?php
                    }
                    PHP,
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'possiblyUndefinedVarInIf' => [
                'code' => '<?php
                    if (rand(0,100) === 10) {
                        $b = "s";
                    }

                    echo $b;',
                'error_message' => 'PossiblyUndefinedGlobalVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:26 - Possibly undefined global '
                    . 'variable $b, first seen on line 3',
            ],
            'possiblyUndefinedArrayInIf' => [
                'code' => '<?php
                    if (rand(0,100) === 10) {
                        $array[] = "hello";
                    }

                    echo $array;',
                'error_message' => 'PossiblyUndefinedGlobalVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:25 - Possibly undefined global '
                    . 'variable $array, first seen on line 3',
            ],
            'invalidGlobal' => [
                'code' => '<?php
                    $a = "heli";

                    global $a;',
                'error_message' => 'InvalidGlobal',
            ],
            'thisInStatic' => [
                'code' => '<?php
                    class A {
                        public static function fooFoo() {
                            echo $this;
                        }
                    }',
                'error_message' => 'InvalidScope',
            ],
            'static' => [
                'code' => '<?php
                    function a(): string {
                        static $foo = "foo";

                        return $foo;
                    }',
                'error_message' => 'MixedReturnStatement',
            ],
            'staticNullRef' => [
                'code' => '<?php
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
                'code' => '<?php
                    function a(): void {
                        /** @var string */
                        static $foo = null;
                    }',
                'error_message' => 'ReferenceConstraintViolation',
            ],
            'typedStaticCannotBeAssignedInt' => [
                'code' => '<?php
                    function a(): void {
                        /** @var string */
                        static $foo = "foo";

                        $foo = 5;
                    }',
                'error_message' => 'ReferenceConstraintViolation',
            ],
            'typedStaticCannotBeAssignedNull' => [
                'code' => '<?php
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
