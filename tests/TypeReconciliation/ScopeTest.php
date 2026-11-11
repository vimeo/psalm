<?php

declare(strict_types=1);

namespace Psalm\Tests\TypeReconciliation;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class ScopeTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
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
            'globalNonEmptyMixedKeepsUnboundPathTypeAfterCall' => [
                'code' => '<?php
                    function bind_x(): void { global $x; }
                    /** @global non-empty-mixed $x */
                    function run(bool $c): int {
                        global $x;
                        if ($c) {
                            unset($x);
                            $x = 0;
                        }
                        bind_x();
                        if (!$x) {
                            return 1;
                        }
                        return 2;
                    }',
            ],
            'globalDeclaredTypeAfterMethodCall' => [
                'code' => '<?php
                    class Db { public function prepare(string $q): string { return $q; } }
                    class Q {
                        /** @global Db $db */
                        public function run(): string {
                            global $db;
                            $this->parse();
                            return $db->prepare("x");
                        }
                        /** @global Db $db */
                        private function parse(): void {
                            global $db;
                            $db->prepare("y");
                        }
                    }',
            ],
            'globalDeclaredTypeAfterFunctionCall' => [
                'code' => '<?php
                    class Db { public function prepare(string $q): string { return $q; } }
                    function bind_db(): void { global $db; }
                    /** @global Db $db */
                    function run(): string {
                        global $db;
                        bind_db();
                        return $db->prepare("x");
                    }',
            ],
            'globalDeclaredTypeAfterStaticMethodCall' => [
                'code' => '<?php
                    class Db { public function prepare(string $q): string { return $q; } }
                    class Binder { public static function bind(): void { global $db; } }
                    /** @global Db $db */
                    function run(): string {
                        global $db;
                        Binder::bind();
                        return $db->prepare("x");
                    }',
            ],
            'globalDeclaredTypeAfterTransitiveCall' => [
                'code' => '<?php
                    class Db { public function prepare(string $q): string { return $q; } }
                    function bind_db(): void { global $db; }
                    function outer(): void { bind_db(); }
                    /** @global Db $db */
                    function run(): string {
                        global $db;
                        outer();
                        return $db->prepare("x");
                    }',
            ],
            'globalInlineVarTypeAfterCall' => [
                'code' => '<?php
                    class Db { public function prepare(string $q): string { return $q; } }
                    function bind_db(): void { global $db; }
                    function run(): string {
                        /** @var Db $db */
                        global $db;
                        bind_db();
                        return $db->prepare("x");
                    }',
            ],
            'localVariableKeepsTypeAfterCallBindingSameNameGlobal' => [
                'code' => '<?php
                    class Db { public function prepare(string $q): string { return $q; } }
                    function bind_db(): void { global $db; }
                    function run(): string {
                        $db = new Db();
                        bind_db();
                        return $db->prepare("x");
                    }',
            ],
            'byRefParamKeepsTypeAfterCallBindingSameNameGlobal' => [
                'code' => '<?php
                    function bind_state(): void { global $install_state; }
                    /** @param array{a: int} $install_state */
                    function run(array &$install_state): int {
                        bind_state();
                        return $install_state["a"];
                    }',
            ],
            'globalDeclaredTypeWinsOverWriteInCallee' => [
                'code' => '<?php
                    class Db { public function prepare(string $q): string { return $q; } }
                    function write_db(): void { global $db; $db = 5; }
                    /** @global Db $db */
                    function run(): string {
                        global $db;
                        write_db();
                        return $db->prepare("x");
                    }',
            ],
            'globalUnsetThenReassignedKeepsTypeAfterCall' => [
                'code' => '<?php
                    class Db {}
                    class Other { public function name(): string { return "o"; } }
                    function bind_db(): void { global $db; }
                    /** @global Db $db */
                    function run(): string {
                        global $db;
                        unset($db);
                        $db = new Other();
                        bind_db();
                        return $db->name();
                    }',
            ],
            'globalRebindToStaticKeepsTypeAfterCall' => [
                'code' => '<?php
                    class Db {}
                    class Other { public function name(): string { return "o"; } }
                    function bind_db(): void { global $db; }
                    /** @global Db $db */
                    function run(): string {
                        global $db;
                        static $db;
                        $db = new Other();
                        bind_db();
                        return $db->name();
                    }',
            ],
            'globalRebindByReferenceKeepsTypeAfterCall' => [
                'code' => '<?php
                    class Db {}
                    class Other { public function name(): string { return "o"; } }
                    function bind_db(): void { global $db; }
                    /** @global Db $db */
                    function run(Other $other): string {
                        global $db;
                        $db = &$other;
                        bind_db();
                        return $db->name();
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
            'globalStatementInOtherFunctionKeepsLocalType' => [
                'code' => '<?php
                    function function1(): void {
                        global $a;
                    }

                    /** @param "a" $s */
                    function takesA(string $s): void {}

                    function method1(): void {
                        $a = "a";
                        function1();
                        takesA($a);
                    }',
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
            'globalUndeclaredNarrowedStaysMixedAfterCall' => [
                'code' => '<?php
                    class Db { public function prepare(string $q): string { return $q; } }
                    function bind_db(): void { global $db; }
                    function run(): string {
                        global $db;
                        if (!$db instanceof Db) { return ""; }
                        bind_db();
                        return $db->prepare("x");
                    }',
                'error_message' => 'MixedMethodCall',
            ],
            'globalForeachByRefUnbindsAfterCall' => [
                'code' => '<?php
                    class Db {}
                    class Other { public function name(): string { return "o"; } }
                    function bind_db(): void { global $db; }
                    /**
                     * @global Db $db
                     * @param list<Other> $others
                     */
                    function run(array $others): string {
                        global $db;
                        foreach ($others as &$db) {}
                        bind_db();
                        return $db->name();
                    }',
                'error_message' => 'PossiblyUndefinedMethod',
            ],
            'globalUnsetInBranchKeepsOtherPathTypeAfterCall' => [
                'code' => '<?php
                    class Db {}
                    class Other { public function name(): string { return "o"; } }
                    function bind_db(): void { global $db; }
                    /** @global Db $db */
                    function run(bool $c): string {
                        global $db;
                        if ($c) {
                            unset($db);
                            $db = new Other();
                        }
                        bind_db();
                        return $db->name();
                    }',
                'error_message' => 'PossiblyUndefinedMethod',
            ],
            'globalNarrowedStaysNullableAfterCallThatMayWriteNull' => [
                'code' => '<?php
                    class Db { public function prepare(string $q): string { return $q; } }
                    function write_db_null(): void { global $db; $db = null; }
                    /** @global Db|null $db */
                    function run(): string {
                        global $db;
                        if (!$db) { return ""; }
                        write_db_null();
                        return $db->prepare("x");
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'globalNullableForeachByRefMayNotRunKeepsBothTypesAfterCall' => [
                'code' => '<?php
                    class Db { public function prepare(string $q): string { return $q; } }
                    function bind_db(): void { global $db; }
                    /**
                     * @global Db|null $db
                     * @param list<Db> $others
                     */
                    function run(array $others): string {
                        global $db;
                        if (!$db) { return ""; }
                        foreach ($others as &$db) {}
                        bind_db();
                        return $db->prepare("x");
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'globalBoundThroughClosureUseByRefKeepsDeclaredTypeAfterCall' => [
                'code' => '<?php
                    class Db { public function prepare(string $q): string { return $q; } }
                    function write_db_null(): void { global $db; $db = null; }
                    /** @global Db|null $db */
                    function run(): Closure {
                        global $db;
                        return function () use (&$db): string {
                            if (!$db) { return ""; }
                            write_db_null();
                            return $db->prepare("x");
                        };
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'globalBoundByReferenceToGlobalsElementIsReadAfterCall' => [
                'code' => '<?php
                    class Db { public function prepare(string $q): string { return $q; } }
                    function write_db_null(): void { global $db; $db = null; }
                    function run(): string {
                        $db = &$GLOBALS["db"];
                        if (!$db instanceof Db) { return ""; }
                        write_db_null();
                        return $db->prepare("x");
                    }',
                'error_message' => 'MixedMethodCall',
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
