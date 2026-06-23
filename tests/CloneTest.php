<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class CloneTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'cloneCorrect' => [
                'code' => '<?php
                    class A {}
                    function foo(A $a) : A {
                        return clone $a;
                    }
                    $a = foo(new A());',
            ],
            'cloneCorrectWithPublicMethod' => [
                'code' => '<?php
                    class A {
                        public function __clone() {}
                    }
                    function foo(A $a) : A {
                        return clone $a;
                    }
                    foo(new A());',
            ],
            'clonePrivateInternally' => [
                'code' => '<?php
                    class A {
                        private function __clone() {}
                        public function foo(): self {
                            return clone $this;
                        }
                    }',
            ],
            'cloneWithPropertiesPreservesType' => [
                'code' => '<?php
                    class Foo {
                        public function __construct(public int $x) {}
                    }
                    $o = new Foo(1);
                    $b = clone($o, ["x" => 2]);',
                'assertions' => ['$b===' => 'Foo'],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'cloneWithReadonlyPropertyAllowed' => [
                'code' => '<?php
                    class Foo {
                        public function __construct(
                            public int $x,
                            public readonly string $y,
                        ) {}
                    }
                    $o = new Foo(1, "a");
                    $b = clone($o, ["y" => "b"]);',
                'assertions' => ['$b===' => 'Foo'],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'cloneWithNamedArguments' => [
                'code' => '<?php
                    class Foo {
                        public function __construct(public int $x) {}
                    }
                    $o = new Foo(1);
                    $b = clone(object: $o, withProperties: ["x" => 2]);',
                'assertions' => ['$b===' => 'Foo'],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'cloneFuncCallWithSingleNamedObjectArg' => [
                'code' => '<?php
                    class Foo {
                        public function __construct(public int $x) {}
                    }
                    $o = new Foo(1);
                    $b = clone(object: $o);',
                'assertions' => ['$b===' => 'Foo'],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'cloneWithDynamicPropertiesArrayNoFalsePositive' => [
                'code' => '<?php
                    class Foo {
                        public function __construct(public int $x) {}
                    }
                    /** @param array<string, mixed> $props */
                    function withProps(Foo $o, array $props): Foo {
                        return clone($o, $props);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'cloneWithMagicSetterUnknownKeyAllowed' => [
                'code' => '<?php
                    class Foo {
                        public function __construct(public int $x) {}
                        public function __set(string $name, mixed $value): void {}
                    }
                    $o = new Foo(1);
                    $b = clone($o, ["dynamic" => "anything"]);',
                'assertions' => ['$b===' => 'Foo'],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'cloneWithPropertyWriteAnnotationAllowed' => [
                'code' => '<?php
                    /** @property-write string $dynamic */
                    class Foo {
                        public function __construct(public int $x) {}
                    }
                    $o = new Foo(1);
                    $b = clone($o, ["dynamic" => "anything"]);',
                'assertions' => ['$b===' => 'Foo'],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'cloneWithMultipleProperties' => [
                'code' => '<?php
                    class Foo {
                        public function __construct(public int $x, public string $y) {}
                    }
                    $o = new Foo(1, "a");
                    $b = clone($o, ["x" => 2, "y" => "b"]);',
                'assertions' => ['$b===' => 'Foo'],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'cloneWithNonLiteralKeyNoFalsePositive' => [
                'code' => '<?php
                    class Foo {
                        public function __construct(public int $x) {}
                    }
                    function withDynamicKey(Foo $o, string $k): Foo {
                        return clone($o, [$k => 2]);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'cloneWithPropertiesOnGenericClass' => [
                'code' => '<?php
                    /** @template T */
                    class Box {
                        /** @param T $value */
                        public function __construct(public mixed $value) {}
                    }
                    /** @param Box<int> $o */
                    function withValue(Box $o): Box {
                        return clone($o, ["value" => 2]);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'cloneWithPropertiesOnGenericClassNestedTemplate' => [
                'code' => '<?php
                    /** @template T */
                    class Box {
                        /** @var list<T> */
                        public array $items = [];
                    }
                    /** @param Box<int> $o */
                    function withItems(Box $o): Box {
                        return clone($o, ["items" => [1, 2, 3]]);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'cloneWithPropertiesOnIntersectionType' => [
                'code' => '<?php
                    class A {
                        public int $a = 0;
                    }
                    class B {
                        public int $b = 0;
                    }
                    /** @param A&B $o */
                    function withMember($o): object {
                        return clone($o, ["b" => 5]);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'cloneFuncCallCaseInsensitive' => [
                'code' => '<?php
                    class Foo {
                        public function __construct(public int $x) {}
                    }
                    $o = new Foo(1);
                    $b = Clone($o, ["x" => 2]);',
                'assertions' => ['$b===' => 'Foo'],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'cloneWithSpreadArgsFallsBackGracefully' => [
                // An unpacked first arg skips the clone-with intercept and falls back to
                // normal call analysis (CallMap). That path is conservative about spreads;
                // the point here is that the intercept is bypassed without a crash and the
                // result type stays object.
                'code' => '<?php
                    class Foo {}
                    /** @param array{0: Foo} $args */
                    function cloneSpread(array $args): object {
                        return clone(...$args);
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArgument', 'TooFewArguments'],
                'php_version' => '8.5',
            ],
        ];
    }

    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'invalidIntClone' => [
                'code' => '<?php
                    $a = 5;
                    clone $a;',
                'error_message' => 'InvalidClone',
            ],
            'possiblyInvalidIntClone' => [
                'code' => '<?php
                    $a = rand(0, 1) ? 5 : new Exception();
                    clone $a;',
                'error_message' => 'PossiblyInvalidClone',
            ],
            'invalidMixedClone' => [
                'code' => '<?php
                    /** @var mixed $a */
                    $a = 5;
                    clone $a;',
                'error_message' => 'MixedClone',
            ],
            'notVisibleCloneMethod' => [
                'code' => '<?php
                    class A {
                        private function __clone() {}
                    }
                    $a = new A();
                    clone $a;',
                'error_message' => 'InvalidClone',
            ],
            'notVisibleCloneMethodSubClass' => [
                'code' => '<?php
                    class a {
                        private function __clone() {}
                    }
                    class b extends a {}

                    clone new b;',
                'error_message' => 'InvalidClone',
            ],
            'notVisibleCloneMethodTrait' => [
                'code' => '<?php
                    trait a {
                        private function __clone() {}
                    }
                    class b {
                        use a;
                    }

                    clone new b;',
                'error_message' => 'InvalidClone',
            ],
            'invalidGenericClone' => [
                'code' => '<?php
                    /**
                     * @template T as int|string
                     * @param T $a
                     */
                    function foo($a): void {
                        clone $a;
                    }',
                'error_message' => 'InvalidClone',
            ],
            'possiblyInvalidGenericClone' => [
                'code' => '<?php
                    /**
                     * @template T as int|Exception
                     * @param T $a
                     */
                    function foo($a): void {
                        clone $a;
                    }',
                'error_message' => 'PossiblyInvalidClone',
            ],
            'mixedGenericClone' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T $a
                     */
                    function foo($a): void {
                        clone $a;
                    }',
                'error_message' => 'MixedClone',
            ],
            'mixedTypeInferredIfErrors' => [
                'code' => '<?php
                    class A {}
                    /**
                     * @param A|string $a
                     */
                    function foo($a): void {
                        /**
                         * @psalm-suppress PossiblyInvalidClone
                         */
                        $cloned = clone $a;
                    }',
                'error_message' => 'MixedAssignment',
            ],
            'missingClass' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress UndefinedDocblockClass
                     * @psalm-suppress InvalidReturnType
                     * @return Editable
                     */
                    function get() {}

                    /** @psalm-suppress UndefinedDocblockClass */
                    clone get();',
                'error_message' => 'InvalidClone',
            ],
            'cloneWithFunctionFormRequiresPhp85' => [
                'code' => '<?php
                    class Foo {
                        public function __construct(public int $x) {}
                    }
                    $o = new Foo(1);
                    $b = clone($o, ["x" => 2]);',
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'cloneWithUnknownProperty' => [
                'code' => '<?php
                    class Foo {
                        public function __construct(public int $x) {}
                    }
                    $o = new Foo(1);
                    clone($o, ["nope" => 1]);',
                'error_message' => 'UndefinedPropertyAssignment',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'cloneWithInvalidValueType' => [
                'code' => '<?php
                    class Foo {
                        public function __construct(public int $x) {}
                    }
                    $o = new Foo(1);
                    clone($o, ["x" => "str"]);',
                'error_message' => 'InvalidPropertyAssignmentValue',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'cloneWithInaccessiblePrivateProperty' => [
                'code' => '<?php
                    class Foo {
                        public function __construct(private int $x) {}
                    }
                    $o = new Foo(1);
                    clone($o, ["x" => 2]);',
                'error_message' => 'InaccessibleProperty',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'cloneWithNonArrayProperties' => [
                'code' => '<?php
                    class Foo {
                        public function __construct(public int $x) {}
                    }
                    $o = new Foo(1);
                    clone($o, 5);',
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'cloneWithPropertiesOnInt' => [
                'code' => '<?php
                    $a = 5;
                    clone($a, ["x" => 1]);',
                'error_message' => 'InvalidClone',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'cloneWithPropertiesOnMixed' => [
                'code' => '<?php
                    /** @var mixed $a */
                    $a = 5;
                    clone($a, ["x" => 1]);',
                'error_message' => 'MixedClone',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'cloneWithPossiblyInvalidValue' => [
                'code' => '<?php
                    class Foo {
                        public function __construct(public int $x) {}
                    }
                    /** @param int|string $v */
                    function make(Foo $o, $v): void {
                        clone($o, ["x" => $v]);
                    }',
                'error_message' => 'PossiblyInvalidPropertyAssignmentValue',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'cloneWithMultiplePropertiesSecondInvalid' => [
                'code' => '<?php
                    class Foo {
                        public function __construct(public int $x, public string $y) {}
                    }
                    $o = new Foo(1, "a");
                    clone($o, ["x" => 2, "y" => 5]);',
                'error_message' => 'InvalidPropertyAssignmentValue',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'cloneWithPropertiesAndInaccessibleCloneMethod' => [
                'code' => '<?php
                    class Foo {
                        public int $x = 0;
                        private function __clone() {}
                    }
                    $o = new Foo();
                    clone($o, ["x" => 2]);',
                'error_message' => 'InvalidClone',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'cloneWithInaccessibleProtectedProperty' => [
                'code' => '<?php
                    class Foo {
                        public function __construct(protected int $x) {}
                    }
                    $o = new Foo(1);
                    clone($o, ["x" => 2]);',
                'error_message' => 'InaccessibleProperty',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
        ];
    }
}
