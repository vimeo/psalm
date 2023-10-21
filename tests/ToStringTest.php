<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ToStringTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'validToString' => [
                'code' => '<?php
                    class A {
                        function __toString() {
                            return "hello";
                        }
                    }
                    echo (new A);',
            ],
            'inheritedToString' => [
                'code' => '<?php
                    class A {
                        function __toString() {
                            return "hello";
                        }
                    }
                    class B {
                        function __toString() {
                            return "goodbye";
                        }
                    }
                    class C extends B {}

                    $c = new C();
                    echo (string) $c;',
            ],
            'goodCast' => [
                'code' => '<?php
                    class A {
                        public function __toString(): string
                        {
                            return "hello";
                        }
                    }

                    /** @param string|A $b */
                    function fooFoo($b): void {}

                    /** @param A|string $b */
                    function barBar($b): void {}

                    fooFoo(new A());
                    barBar(new A());',
            ],
            'resourceToString' => [
                'code' => '<?php
                    $a = fopen("php://memory", "r");
                    if ($a === false) exit;
                    $b = (string) $a;',
            ],
            'canBeObject' => [
                'code' => '<?php
                    class A {
                        public function __toString() {
                            return "A";
                        }
                    }

                    /** @param string|object $s */
                    function foo($s) : void {}

                    foo(new A);',
            ],
            'castArrayKey' => [
                'code' => '<?php
                    /**
                     * @param string[] $arr
                     */
                    function foo(array $arr) : void {
                        if (!$arr) {
                            return;
                        }

                        foreach ($arr as $i => $_) {}

                        echo (string) $i;
                    }',
            ],
            'allowToStringAfterMethodExistsCheck' => [
                'code' => '<?php
                    function getString(object $value) : ?string {
                        if (method_exists($value, "__toString")) {
                            return (string) $value;
                        }

                        return null;
                    }',
            ],
            'refineToStringType' => [
                'code' => '<?php
                    /** @psalm-return non-empty-string */
                    function doesCast() : string {
                        return (string) (new A());
                    }

                    /** @psalm-return non-empty-string */
                    function callsToString() : string {
                        return (new A())->__toString();
                    }

                    class A {
                        /** @psalm-return non-empty-string */
                        function __toString(): string {
                            return "ha";
                        }
                    }',
            ],
            'intersectionCanBeString' => [
                'code' => '<?php
                    interface EmptyInterface {}

                    class StringCastable implements EmptyInterface
                    {
                        public function __toString()
                        {
                            return \'I am castable\';
                        }
                    }

                    function factory(): EmptyInterface
                    {
                        return new StringCastable();
                    }

                    $object = factory();
                    if (method_exists($object, \'__toString\')) {
                        $a = (string) $object;
                        echo $a;
                    }

                    if (is_callable([$object, \'__toString\'])) {
                        $a = (string) $object;
                        echo $a;
                    }',
            ],
            'PHP80-stringableInterface' => [
                'code' => '<?php
                    interface Foo extends Stringable {}

                    function takesString(string $s) : void {}

                    function takesFoo(Foo $foo) : void {
                        /** @psalm-suppress ImplicitToStringCast */
                        takesString($foo);
                    }

                    class FooImplementer implements Foo {
                        public function __toString() : string {
                            return "hello";
                        }
                    }

                    takesFoo(new FooImplementer());',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'implicitStringable' => [
                'code' => '<?php
                    function foo(Stringable $s): void {}

                    class Bar {
                        public function __toString() {
                            return "foo";
                        }
                    }

                    foo(new Bar());',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'toStringNever' => [
                'code' => '<?php
                    class B{
                        public function __toString() {
                            throw new BadMethodCallException("bad");
                        }
                    }
                ',
            ],
            'toStringToImplode' => [
                'code' => '<?php
                    class Bar {
                        public function __toString() {
                            return "foo";
                        }
                    }

                    echo implode(":", [new Bar()]);',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'echoClass' => [
                'code' => '<?php
                    class A {}
                    echo (new A);',
                'error_message' => 'InvalidArgument',
            ],
            'echoCastClass' => [
                'code' => '<?php
                    class A {}
                    echo (string)(new A);',
                'error_message' => 'InvalidCast',
            ],
            'invalidToStringReturnType' => [
                'code' => '<?php
                    class A {
                        function __toString(): void { }
                    }',
                'error_message' => 'InvalidToString',
            ],
            'invalidInferredToStringReturnType' => [
                'code' => '<?php
                    class A {
                        function __toString() { }
                    }',
                'error_message' => 'InvalidToString',
            ],
            'invalidInferredToStringReturnTypeWithTruePhp8' => [
                'code' => '<?php
                    class A {
                        function __toString() {
                            /** @psalm-suppress InvalidReturnStatement */
                            return true;
                        }
                    }',
                'error_message' => 'InvalidToString',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'implicitCastWithStrictTypes' => [
                'code' => '<?php declare(strict_types=1);
                    class A {
                        public function __toString(): string
                        {
                            return "hello";
                        }
                    }

                    function fooFoo(string $b): void {}
                    fooFoo(new A());',
                'error_message' => 'InvalidArgument',
            ],
            'implicitCast' => [
                'code' => '<?php
                    class A {
                        public function __toString(): string
                        {
                            return "hello";
                        }
                    }

                    function fooFoo(string $b): void {}
                    fooFoo(new A());',
                'error_message' => 'ImplicitToStringCast',
            ],
            'implicitCastToUnion' => [
                'code' => '<?php
                    class A {
                        public function __toString(): string
                        {
                            return "hello";
                        }
                    }

                    /** @param string|int $b */
                    function fooFoo($b): void {}
                    fooFoo(new A());',
                'error_message' => 'ImplicitToStringCast',
            ],
            'implicitCastFromInterface' => [
                'code' => '<?php
                    interface I {
                        public function __toString();
                    }

                    function takesString(string $str): void { }

                    function takesI(I $i): void
                    {
                        takesString($i);
                    }',
                'error_message' => 'ImplicitToStringCast',
            ],
            'resourceCannotBeCoercedToString' => [
                'code' => '<?php
                    function takesString(string $s) : void {}
                    $a = fopen("php://memory", "r");
                    takesString($a);',
                'error_message' => 'InvalidArgument',
            ],
            'resourceOrFalseToString' => [
                'code' => '<?php
                    $a = fopen("php://memory", "r");
                    if (rand(0, 1)) {
                        $a = [];
                    }
                    $b = (string) $a;',
                'error_message' => 'PossiblyInvalidCast',
            ],
            'cannotCastInsideString' => [
                'code' => '<?php
                    class NotStringCastable {}
                    $object = new NotStringCastable();
                    echo "$object";',
                'error_message' => 'InvalidCast',
            ],
            'warnAboutNullableCast' => [
                'code' => '<?php
                    class ClassWithToString {
                        public function __toString(): string {
                            return "";
                        }
                    }

                    function maybeShow(?string $message): void {
                        if ($message !== null) {
                            echo $message;
                        }
                    }

                    maybeShow(new ClassWithToString());',
                'error_message' => 'ImplicitToStringCast',
            ],
            'possiblyInvalidCastOnIsSubclassOf' => [
                'code' => '<?php
                    class Foo {}

                    /**
                     * @param mixed $a
                     */
                    function bar($a) : ?string {
                        /**
                         * @psalm-suppress MixedArgument
                         */
                        if (is_subclass_of($a, Foo::class)) {
                            return "hello" . $a;
                        }

                        return null;
                    }',
                'error_message' => 'PossiblyInvalidOperand',
            ],
            'allowToStringAfterMethodExistsCheckWithTypo' => [
                'code' => '<?php
                    function getString(object $value) : ?string {
                        if (method_exists($value, "__toStrong")) {
                            return (string) $value;
                        }

                        return null;
                    }',
                'error_message' => 'InvalidCast',
            ],
            'alwaysEvaluateToStringVar' => [
                'code' => '<?php
                    /** @psalm-suppress UndefinedFunction */
                    fora((string) $address);',
                'error_message' => 'UndefinedGlobalVariable',
            ],
            'implicitStringableDisallowed' => [
                'code' => '<?php
                    interface Stringable {
                        function __toString() {}
                    }
                    function foo(Stringable $s): void {}

                    class Bar {
                        public function __toString() {
                            return "foo";
                        }
                    }

                    foo(new Bar());',
                'error_message' => 'InvalidArgument',
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'implicitCastInArray' => [
                'code' => '<?php
                    interface S {
                        public function __toString(): string;
                    }
                    /** @return array<array-key, string> */
                    function f(S $s): array {
                        return [$s];
                    }
                ',
                'error_message' => 'ImplicitToStringCast',
            ],
            'implicitCastInList' => [
                'code' => '<?php
                    interface S {
                        public function __toString(): string;
                    }
                    /** @return list<string> */
                    function f(S $s): array {
                        return [$s];
                    }
                ',
                'error_message' => 'ImplicitToStringCast',
            ],
            'implicitCastInTuple' => [
                'code' => '<?php
                    interface S {
                        public function __toString(): string;
                    }
                    /** @return array{string} */
                    function f(S $s): array {
                        return [$s];
                    }
                ',
                'error_message' => 'ImplicitToStringCast',
            ],
            'implicitCastInShape' => [
                'code' => '<?php
                    interface S {
                        public function __toString(): string;
                    }
                    /** @return array{0:string} */
                    function f(S $s): array {
                        return [$s];
                    }
                ',
                'error_message' => 'ImplicitToStringCast',
            ],
            'implicitCastInIterable' => [
                'code' => '<?php
                    interface S {
                        public function __toString(): string;
                    }
                    /** @return iterable<int, string> */
                    function f(S $s) {
                        return [$s];
                    }
                ',
                'error_message' => 'ImplicitToStringCast',
            ],
            'implicitCastInToString' => [
                'code' => '<?php
                    declare(strict_types=1);

                    final class A
                    {
                        public function __toString(): string
                        {
                            return new SplFileInfo("a");
                        }
                    }
                ',
                'error_message' => 'ImplicitToStringCast',
            ],
            'toStringTypecastNonString' => [
                'code' => '<?php
                    class A {
                        function __toString(): string {
                            return "ha";
                        }
                    }

                    $foo = new A();
                    echo (int) $foo;',
                'error_message' => 'InvalidCast',
            ],
            'riskyArrayToIntCast' => [
                'code' => '<?php
                    echo (int) array();',
                'error_message' => 'RiskyCast',
            ],
            'riskyArrayToFloatCast' => [
                'code' => '<?php
                    echo (float) array(\'hello\');',
                'error_message' => 'RiskyCast',
            ],
        ];
    }
}
