<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class InternalAnnotationTest extends TestCase
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
            'psalmInternalMethodCalledFromFunctionInSameNamespace' => [
                'code' => '<?php
                    namespace A\B {
                        class Foo {
                            /** @psalm-internal A\B */
                            public function __construct() {}

                            /** @psalm-internal A\B */
                            public static function barBar(): void {}
                        }

                        function f(): void {
                            Foo::barBar();
                            new Foo();
                        }
                    }',
            ],
            'internalMethodWithCall' => [
                'code' => '<?php
                    namespace A {
                        class Foo {
                            /**
                             * @internal
                             */
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace A\B {
                        class Bat {
                            public function batBat() : void {
                                \A\Foo::barBar();
                            }
                        }
                    }',
            ],
            'internalClassWithStaticCall' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @internal
                         */
                        class Foo {
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace A\B {
                        class Bat {
                            public function batBat() : void {
                                \A\Foo::barBar();
                            }
                        }
                    }',
            ],
            'internalClassWithInstanceCall' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @internal
                         */
                        class Foo {
                            public function barBar(): void {
                            }
                        }

                        function getFoo(): Foo {
                            return new Foo();
                        }
                    }

                    namespace A\B {
                        class Bat {
                            public function batBat(): void {
                                \A\getFoo()->barBar();
                            }
                        }
                    }',
            ],
            'internalClassWithPropertyFetch' => [
                'code' => '<?php
                    namespace A\B {
                        /**
                         * @internal
                         */
                        class Foo {
                            public int $barBar = 0;
                        }

                        function getFoo(): Foo {
                            return new Foo();
                        }
                    }

                    namespace A\C {
                        class Bat {
                            public function batBat(\A\B\Foo $instance): void {
                                \A\B\getFoo()->barBar;
                            }
                        }
                    }',
            ],
            'internalClassExtendingNamespaceWithStaticCall' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @internal
                         */
                        class Foo extends \B\Foo {
                            public function __construct() {
                                parent::__construct();
                            }
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace B {
                        class Foo {
                            public function __construct() {
                                static::barBar();
                            }

                            public static function barBar(): void {
                            }
                        }
                    }',
            ],
            'internalClassWithNew' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @internal
                         */
                        class Foo { }
                    }

                    namespace A\B {
                        class Bat {
                            public function batBat() : void {
                                $a = new \A\Foo();
                            }
                        }
                    }',
            ],
            'internalClassWithExtends' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @internal
                         */
                        class Foo { }
                    }

                    namespace A\B {
                        class Bar extends \A\Foo {}
                    }',
            ],
            'internalPropertyGet' => [
                'code' => '<?php
                    namespace A {
                        class Foo {
                            /**
                             * @internal
                             * @var ?int
                             */
                            public $foo;
                        }
                    }

                    namespace A\B {
                        class Bat {
                            public function batBat() : void {
                                echo (new \A\Foo)->foo;
                            }
                        }
                    }',
            ],
            'internalPropertySet' => [
                'code' => '<?php
                    namespace A {
                        class Foo {
                            /**
                             * @internal
                             * @var ?int
                             */
                            public $foo;
                        }
                    }
                    namespace A\B {
                        class Bat {
                            public function batBat() : void {
                                $a = new \A\Foo;
                                $a->foo = 5;
                            }
                        }
                    }',
            ],
            'internalMethodInTraitWithCall' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @internal
                         */
                        trait T {
                            public static function barBar(): void {
                            }
                        }

                        class Foo {
                            use T;

                        }
                    }

                    namespace B {
                        class Bat {
                            public function batBat() : void {
                                \A\Foo::barBar();
                            }
                        }
                    }',
            ],
            'magicPropertyGetInternalImplicit' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @property int $foo
                         */
                        class Foo {
                            /**
                             * @internal
                             */
                            public function __get(string $s): string {
                              return "hello";
                            }
                        }
                    }
                    namespace B {
                        class Bat {
                            public function batBat() : void {
                                echo (new \A\Foo)->foo;
                            }
                        }
                    }',
            ],
            'constInternalClass' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @internal
                         */
                        class Foo {
                            const AA = "a";
                        }

                        class Bat {
                            public function batBat() : void {
                                echo \A\Foo::AA;
                            }
                        }
                    }',
            ],
            'psalmInternalMethodWithCall' => [
                'code' => '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @psalm-internal A\B
                             */
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace A\B\C {
                        class Bat {
                            public function batBat() : void {
                                \A\B\Foo::barBar();
                            }
                        }
                    }',
            ],
            'internalMethodWithCallWithCaseMisMatched' => [
                'code' => '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @psalm-internal A\B
                             */
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace a\b\c {
                        class Bat {
                            public function batBat() : void {
                                \A\B\Foo::barBar();
                            }
                        }
                    }',
            ],
            'psalmInternalMethodWithTrailingWhitespace' => [
                'code' => '<?php
                    namespace A\B {
                        class Foo {
                            /** @psalm-internal A\B */
                            public static function barBar(): void {
                                self::barBar();
                            }
                        }
                    }',
            ],
            'internalToClassMethodWithCallSameNamespace' => [
                'code' => '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @psalm-internal A\B
                             */
                            public static function barBar(): void {
                            }

                            public static function foo(): void {
                                self::barBar();
                            }
                        }
                    }',
            ],
            'psalmInternalClassWithStaticCall' => [
                'code' => '<?php
                    namespace A\B {
                        /**
                         * @psalm-internal A
                         */
                        class Foo {
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace A\B\C {
                        class Bat {
                            public function batBat() : void {
                                \A\B\Foo::barBar();
                            }
                        }
                    }',
            ],
            'psalmInternalClassWithInstanceCall' => [
                'code' => '<?php
                    namespace A\B {
                        /**
                         * @psalm-internal A\B
                         */
                        class Foo {
                            public function barBar(): void {
                            }
                        }

                        function getFoo(): Foo {
                            return new Foo();
                        }
                    }

                    namespace A\B\C {
                        class Bat {
                            public function batBat(\A\B\Foo $instance): void {
                                \A\B\getFoo()->barBar();
                            }
                        }
                    }',
            ],
            'psalmInternalClassWithPropertyFetch' => [
                'code' => '<?php
                    namespace A\B {
                        /**
                         * @psalm-internal A\B
                         */
                        class Foo {
                            public int $barBar = 0;
                        }

                        function getFoo(): Foo {
                            return new Foo();
                        }
                    }

                    namespace A\B\C {
                        class Bat {
                            public function batBat(\A\B\Foo $instance): void {
                                \A\B\getFoo()->barBar;
                            }
                        }
                    }',
            ],
            'psalmInternalClassExtendingNamespaceWithStaticCall' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @psalm-internal A
                         */
                        class Foo extends \B\Foo {
                            public function __construct() {
                                parent::__construct();
                            }
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace B {
                        class Foo {
                            public function __construct() {
                                static::barBar();
                            }

                            public static function barBar(): void {
                            }
                        }
                    }',
            ],
            'psalmInternalClassWithNew' => [
                'code' => '<?php
                    namespace A\B {
                        /**
                         * @psalm-internal A\B
                         */
                        class Foo { }
                    }

                    namespace A\B\C {
                        class Bat {
                            public function batBat() : void {
                                $a = new \A\B\Foo();
                            }
                        }
                    }',
            ],
            'psalmInternalClassWithInstanceOf' => [
                'code' => '<?php
                    namespace A\B {
                        interface Bar {};

                        /**
                         * @psalm-internal A\B
                         */
                        class Foo { }
                    }

                    namespace A\B\C {
                        class Bat {
                            public function batBat(\A\B\Bar $bar) : void {
                                $bar instanceOf \A\B\Foo;
                            }
                        }
                    }',
            ],
            'psalmInternalClassWithExtends' => [
                'code' => '<?php
                    namespace A\B {
                        /**
                         * @psalm-internal A\B
                         */
                        class Foo { }
                    }

                    namespace A\B\C {
                        class Bar extends \A\B\Foo {}
                    }',
            ],
            'psalmInternalClassWithTrailingWhitespace' => [
                'code' => '<?php
                    namespace A\B {
                        /** @psalm-internal A\B */
                        class Foo {}
                        class Bar extends Foo {}
                    }',
            ],
            'psalmInternalPropertyGet' => [
                'code' => '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @psalm-internal A\B
                             * @var ?int
                             */
                            public $foo;
                        }
                    }

                    namespace A\B\C {
                        class Bat {
                            public function batBat() : void {
                                echo (new \A\B\Foo)->foo;
                            }
                        }
                    }',
            ],
            'psalmInternalPropertySet' => [
                'code' => '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @psalm-internal A\B
                             * @var ?int
                             */
                            public $foo;
                        }
                    }
                    namespace A\B\C {
                        class Bat {
                            public function batBat() : void {
                                $a = new \A\B\Foo;
                                $a->foo = 5;
                            }
                        }
                    }',
            ],
            'psalmInternalPropertyWithTrailingWhitespace' => [
                'code' => '<?php
                    namespace A\B {
                        class Foo {
                            /** @psalm-internal A\B */
                            public int $foo = 0;

                            public function barBar() : void {
                                $this->foo = 42;
                            }
                        }
                    }',
            ],
            'psalmInternalMethodInTraitWithCall' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @psalm-internal A
                         */
                        trait T {
                            public static function barBar(): void {
                            }
                        }

                        class Foo {
                            use T;

                        }
                    }

                    namespace B {
                        class Bat {
                            public function batBat() : void {
                                \A\Foo::barBar();
                            }
                        }
                    }',
            ],
            'psalmInternalMultipleNamespaces' => [
                'code' => '<?php
                    namespace A
                    {
                        class Foo
                        {
                            /**
                             * @psalm-internal \B
                             * @psalm-internal \C
                             */
                            public static function foobar(): void {}
                        }
                    }

                    namespace B
                    {
                        \A\Foo::foobar();
                    }

                    namespace C
                    {
                        \A\Foo::foobar();
                    }
                ',
            ],
            'psalmInternalToClass' => [
                'code' => '<?php
                    namespace A
                    {
                        class Foo
                        {
                            /** @psalm-internal B\Bar */
                            public static function foo(): void {}

                            /** @psalm-internal B\Bar */
                            public function bar(): void {}
                        }
                    }

                    namespace B
                    {
                        class Bar
                        {
                            public function baz(): void
                            {
                                \A\Foo::foo();
                                $foo = new \A\Foo();
                                $foo->bar();
                            }
                        }
                    }
                ',
            ],
            'psalmInternalClassWithCallClass' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @psalm-internal B\Bat
                         */
                        class Foo {
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace B {
                        class Bat {
                            public function batBat() : void {
                                \A\Foo::barBar();
                            }
                        }
                    }',
            ],
            'psalmInternalMethodWithCallClass' => [
                'code' => '<?php
                    namespace A {
                        class Foo {
                            /**
                             * @psalm-internal B\Bat
                             */
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace B {
                        class Bat {
                            public function batBat() : void {
                                \A\Foo::barBar();
                            }
                        }
                    }',
            ],
            'psalmInternalMethodWithMethod' => [
                'code' => '<?php
                    namespace X {
                        class Foo {
                            /**
                             * @psalm-internal Y\Bat::batBat
                             */
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace Y {
                        class Bat {
                            public function batBat() : void {
                                \X\Foo::barBar();
                            }
                        }
                    }',
            ],
            'callToInternalMethodFromAnonymousClass' => [
                'code' => <<<'PHP'
                <?php
                namespace X;

                /**
                 * @internal
                 * @psalm-internal X
                 */
                class A
                {
                    public function a(): void {}
                }

                new class (new A)
                {
                    public function __construct(
                        private A $a
                    ) {
                        $a->a();
                    }
                };
                PHP,
            ],
            'internalSymbolsUsedFromFunctionAndTopLevelCodeInSameNamespace' => [
                'code' => '<?php
                    namespace A\B {
                        /** @internal */
                        final class Inner {
                            public const C = 1;
                            public static function make(): self { return new self(); }
                        }
                        final class Box {
                            /** @internal */
                            public int $p = 1;
                        }
                    }

                    namespace A\C {
                        function f(\A\B\Box $b, \A\B\Inner $i): int {
                            new \A\B\Inner();
                            \A\B\Inner::make();
                            $b->p = 2;
                            return \A\B\Inner::C + $i::C + $b->p;
                        }

                        new \A\B\Inner();
                        $box = new \A\B\Box();
                        $box->p = 2;
                        echo \A\B\Inner::C + $box->p;
                    }',
            ],
            'psalmInternalSymbolsUsedFromFunctionAndTopLevelCodeInMatchingNamespace' => [
                'code' => '<?php
                    namespace A\B {
                        /** @psalm-internal A\B */
                        final class Inner {
                            public const C = 1;
                            public static function make(): self { return new self(); }
                        }
                        final class Box {
                            /** @psalm-internal A\B */
                            public int $p = 1;
                        }
                    }

                    namespace A\B\C {
                        function f(\A\B\Box $b): int {
                            new \A\B\Inner();
                            \A\B\Inner::make();
                            $b->p = 2;
                            return \A\B\Inner::C + $b->p;
                        }

                        new \A\B\Inner();
                        $box = new \A\B\Box();
                        $box->p = 2;
                        echo \A\B\Inner::C + $box->p;
                    }',
            ],
            'psalmInternalToClassUsedInItsClassLevelCode' => [
                'code' => '<?php
                    namespace A {
                        /** @psalm-internal A\Owner */
                        final class OwnerOnly {
                            public const C = 1;
                        }

                        final class Owner {
                            public const K = OwnerOnly::C;
                            public int $d = OwnerOnly::C;
                        }
                    }',
            ],
            'psalmInternalToClassUsedInClosureOfItsMethod' => [
                'code' => '<?php
                    namespace A {
                        final class Helper {
                            /** @psalm-internal A\Owner */
                            public static function make(): int { return 1; }
                        }

                        final class Owner {
                            public function run(): int {
                                $f = function (): int { return Helper::make(); };
                                $g = fn(): int => Helper::make();
                                return $f() + $g();
                            }
                        }
                    }',
            ],
            'psalmInternalToMethodClassUsedFromThatMethod' => [
                'code' => '<?php
                    namespace X {
                        /** @psalm-internal Y\Bat::batBat */
                        final class Foo {
                            public const C = 1;
                        }
                    }

                    namespace Y {
                        final class Bat {
                            public function batBat(): int {
                                new \X\Foo();
                                return \X\Foo::C;
                            }
                        }
                    }',
            ],
            'psalmInternalToClassCloneFromItsMethod' => [
                'code' => '<?php
                    namespace A {
                        final class Foo {
                            /** @psalm-internal A\Foo */
                            public function __clone() {}

                            public function copy(): self {
                                return clone $this;
                            }
                        }
                    }',
            ],
            'internalSymbolsUsedInTraitOfOwningNamespace' => [
                'code' => '<?php
                    namespace A {
                        /** @internal */
                        final class Inner {
                            public const C = 1;
                        }

                        trait T {
                            public function t(): int {
                                new Inner();
                                return Inner::C;
                            }
                        }
                    }

                    namespace Other {
                        final class User {
                            use \A\T;
                        }
                    }',
            ],
            'internalSymbolsUsedFromTopLevelCodeWithScopeThis' => [
                'code' => '<?php
                    namespace A {
                        /** @internal */
                        final class Inner {
                            public const C = 1;
                        }

                        final class View {}
                    }

                    namespace Other {
                        /** @psalm-scope-this \A\View */
                        echo \A\Inner::C;
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
            'internalConstructorCalledFromNamespacedFunction' => [
                'code' => '<?php
                    namespace A\B {
                        final class Inner {
                            /** @internal */
                            public function __construct() {}
                        }
                    }

                    namespace Other {
                        function f(): void {
                            new \A\B\Inner();
                        }
                    }',
                'error_message' => 'Constructor A\B\Inner::__construct is internal to A but called from Other\f',
            ],
            'internalStaticMethodCalledFromNamespacedFunction' => [
                'code' => '<?php
                    namespace A {
                        class Foo {
                            /** @internal */
                            public static function barBar(): void {}
                        }
                    }

                    namespace B {
                        function f(): void {
                            \A\Foo::barBar();
                        }
                    }',
                'error_message' => 'The method A\Foo::barBar is internal to A but called from B\f',
            ],
            'internalInstanceMethodCalledFromNamespacedFunction' => [
                'code' => '<?php
                    namespace A {
                        class Foo {
                            /** @internal */
                            public function barBar(): void {}
                        }
                    }

                    namespace B {
                        function f(\A\Foo $foo): void {
                            $foo->barBar();
                        }
                    }',
                'error_message' => 'The method A\Foo::barBar is internal to A but called from B\f',
            ],
            'psalmInternalMethodCalledFromFunctionInParentNamespace' => [
                'code' => '<?php
                    namespace A\A {
                        class Foo {
                            /** @psalm-internal A\A */
                            public static function barBar(): void {}
                        }
                    }

                    namespace A {
                        function f(): void {
                            \A\A\Foo::barBar();
                        }
                    }',
                'error_message' => 'The method A\A\Foo::barBar is internal to A\A but called from A\f',
            ],
            'internalMethodWithCall' => [
                'code' => '<?php
                    namespace A {
                        class Foo {
                            /**
                             * @internal
                             */
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace B {
                        class Bat {
                            public function batBat(): void {
                                \A\Foo::barBar();
                            }
                        }
                    }',
                'error_message' => 'The method A\Foo::barBar is internal to A but called from B\Bat',
            ],
            'internalCloneMethodWithCall' => [
                'code' => '<?php
                    namespace A {
                        class Foo {
                            /**
                             * @internal
                             */
                            public function __clone() {
                            }
                        }
                    }

                    namespace B {
                        class Bat {
                            public function batBat(): void {
                                $a = new \A\Foo;
                                $aa = clone $a;
                            }
                        }
                    }',
                'error_message' => 'The method A\Foo::__clone is internal to A but called from B\Bat::batBat',
            ],
            'internalMethodWithCallFromRootNamespace' => [
                'code' => '<?php
                    namespace A {
                        class Foo {
                            /**
                             * @internal
                             */
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace {
                        \A\Foo::barBar();
                    }',
                'error_message' => 'The method A\Foo::barBar is internal to A but called from root namespace',
            ],
            'internalClassWithStaticCall' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @internal
                         */
                        class Foo {
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace B {
                        class Bat {
                            public function batBat() {
                                \A\Foo::barBar();
                            }
                        }
                    }',
                'error_message' => 'A\Foo is internal to A but called from B\Bat::batBat',
            ],
            'internalClassWithInstanceCall' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @internal
                         */
                        class Foo {
                            public function barBar(): void {
                            }
                        }

                        function getFoo(): Foo {
                            return new Foo();
                        }
                    }

                    namespace B {
                        class Bat {
                            public function batBat(): void {
                                \A\getFoo()->barBar();
                            }
                        }
                    }',
                'error_message' => 'InternalMethod',
            ],
            'internalClassWithPropertyFetch' => [
                'code' => '<?php
                    namespace A\B {
                        /**
                         * @internal
                         */
                        class Foo {
                            public int $barBar = 0;
                        }

                        function getFoo(): Foo {
                            return new Foo();
                        }
                    }

                    namespace C {
                        class Bat {
                            public function batBat(): void {
                                \A\B\getFoo()->barBar;
                            }
                        }
                    }',
                'error_message' => 'A\B\Foo::$barBar is internal',
            ],
            'internalClassWithNew' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @internal
                         */
                        class Foo { }
                    }

                    namespace B {
                        class Bat {
                            public function batBat() {
                                $a = new \A\Foo();
                            }
                        }
                    }',
                'error_message' => 'A\Foo is internal to A but called from B\Bat::batBat',
            ],
            'internalClassWithExtends' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @internal
                         */
                        class Foo { }
                    }

                    namespace B {
                        class Bar extends \A\Foo {}
                    }',
                'error_message' => 'InternalClass',
            ],
            'internalPropertyGet' => [
                'code' => '<?php
                    namespace A {
                        class Foo {
                            /**
                             * @internal
                             * @var ?int
                             */
                            public $foo;
                        }
                    }

                    namespace B {
                        class Bat {
                            public function batBat() : void {
                                echo (new \A\Foo)->foo;
                            }
                        }
                    }',
                'error_message' => 'A\Foo::$foo is internal to A but called from B\Bat::batBat',
            ],
            'internalPropertySet' => [
                'code' => '<?php
                    namespace A {
                        class Foo {
                            /**
                             * @internal
                             * @var ?int
                             */
                            public $foo;
                        }
                    }
                    namespace B {
                        class Bat {
                            public function batBat() : void {
                                $a = new \A\Foo;
                                $a->foo = 5;
                            }
                        }
                    }',
                'error_message' => 'A\Foo::$foo is internal to A but called from B\Bat::batBat',
            ],
            'magicPropertyGetInternalExplicit' => [
                'code' => '<?php
                    namespace A {
                        class Foo {
                            /**
                             * @internal
                             */
                            public function __get(string $s): string {
                              return "hello";
                            }
                        }
                    }
                    namespace B {
                        class Bat {
                            public function batBat() : void {
                                echo (new \A\Foo)->__get("foo");
                            }
                        }
                    }',
                'error_message' => 'InternalMethod',
            ],
            'constInternalClass' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @internal
                         */
                        class Foo {
                            const AA = "a";
                        }
                    }
                    namespace B {
                        class Bat {
                            public function batBat() : void {
                                echo \A\Foo::AA;
                            }
                        }
                    }',
                'error_message' => 'A\Foo is internal to A but called from B\Bat::batBat',
            ],
            'psalmInternalMethodWithCall' => [
                'code' => '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @psalm-internal A\B
                             */
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace A\C {
                        class Bat {
                            public function batBat(): void {
                                \A\B\Foo::barBar();
                            }
                        }
                    }',
                'error_message' => 'The method A\B\Foo::barBar is internal to A\B',
            ],
            'psalmInternalToClassMethodWithCall' => [
                'code' => '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @psalm-internal A\B\Foo
                             */
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace A\C {
                        class Bat {
                            public function batBat(): void {
                                \A\B\Foo::barBar();
                            }
                        }
                    }',
                'error_message' => 'The method A\B\Foo::barBar is internal to A\B\Foo',
            ],
            'psalmInternalClassWithStaticCall' => [
                'code' => '<?php
                    namespace A\B {
                        /**
                         * @psalm-internal A\B
                         */
                        class Foo {
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace A\C {
                        class Bat {
                            public function batBat(): void {
                                \A\B\Foo::barBar();
                            }
                        }
                    }',
                'error_message' => 'InternalClass',
            ],
            'psalmInternalClassWithPropertyFetch' => [
                'code' => '<?php
                    namespace A\B {
                        /**
                         * @psalm-internal A\B
                         */
                        class Foo {
                            public int $barBar = 0;
                        }

                        function getFoo(): Foo {
                            return new Foo();
                        }
                    }

                    namespace A\C {
                        class Bat {
                            public function batBat(): void {
                                \A\B\getFoo()->barBar;
                            }
                        }
                    }',
                'error_message' => 'A\B\Foo::$barBar is internal to A\B',
            ],
            'psalmInternalClassWithInstanceCall' => [
                'code' => '<?php
                    namespace A\B {
                        /**
                         * @psalm-internal A\B
                         */
                        class Foo {
                            public function barBar(): void {
                            }
                        }

                        function getFoo(): Foo {
                            return new Foo();
                        }
                    }

                    namespace A\C {
                        class Bat {
                            public function batBat(): void {
                                \A\B\getFoo()->barBar();
                            }
                        }
                    }',
                'error_message' => 'The method A\B\Foo::barBar is internal to A\B',
            ],
            'psalmInternalClassWithNew' => [
                'code' => '<?php
                    namespace A\B {
                        /**
                         * @psalm-internal A\B
                         */
                        class Foo { }
                    }

                    namespace A\C {
                        class Bat {
                            public function batBat(): void {
                                $a = new \A\B\Foo();
                            }
                        }
                    }',
                'error_message' => 'InternalClass',
            ],
            'psalmInternalClassWithExtends' => [
                'code' => '<?php
                    namespace A\B {
                        /**
                         * @psalm-internal A\B
                         */
                        class Foo { }
                    }

                    namespace A\C {
                        class Bar extends \A\B\Foo {}
                    }',
                'error_message' => 'A\B\Foo is internal to A\B',
            ],
            'psalmInternalPropertyGet' => [
                'code' => '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @psalm-internal A\B
                             * @var ?int
                             */
                            public $foo;
                        }
                    }

                    namespace A\C {
                        class Bat {
                            public function batBat() : void {
                                echo (new \A\B\Foo)->foo;
                            }
                        }
                    }',
                'error_message' => 'A\B\Foo::$foo is internal to A\B',
            ],
            'psalmInternalPropertySet' => [
                'code' => '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @psalm-internal A\B
                             * @var ?int
                             */
                            public $foo;
                        }
                    }
                    namespace A\C {
                        class Bat {
                            public function batBat() : void {
                                $a = new \A\B\Foo;
                                $a->foo = 5;
                            }
                        }
                    }',
                'error_message' => 'A\B\Foo::$foo is internal to A\B',
            ],
            'psalmInternalClassMissingNamespace' => [
                    'code' => '<?php

                    /**
                      * @internal
                      * @psalm-internal
                      */
                    class Foo {}

                    ',
                    'error_message' => 'psalm-internal annotation used without specifying namespace',
            ],
            'psalmInternalPropertyMissingNamespace' => [
                'code' => '<?php
                    class Foo {
                        /**
                          * @var int
                          * @internal
                          * @psalm-internal
                          */
                        var $bar;
                    }
                    ',
                'error_message' => 'psalm-internal annotation used without specifying namespace',
            ],
            'psalmInternalMethodMissingNamespace' => [
                'code' => '<?php
                    class Foo {
                        /**
                         * @psalm-internal
                         */
                        function Bar(): void {}
                    }

                    ',
                'error_message' => 'psalm-internal annotation used without specifying namespace',
            ],
            'internalConstructor' => [
                'code' => '<?php
                    namespace A {
                        class C {
                            /** @internal */
                            public function __construct() {}
                        }
                    }
                    namespace B {
                        use A\C;
                        new C;
                    }
                ',
                'error_message' => 'InternalMethod',
            ],
            'psalmInternalClassWithCallClass' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @psalm-internal B\Bar
                         */
                        class Foo {
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace B {
                        class Bat {
                            public function batBat() : void {
                                \A\Foo::barBar();
                            }
                        }
                    }',
                'error_message' => 'InternalClass',
            ],
            'psalmInternalMethodWithCallClass' => [
                'code' => '<?php
                    namespace A {
                        class Foo {
                            /**
                             * @psalm-internal B\Bar
                             */
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace B {
                        class Bat {
                            public function batBat() : void {
                                \A\Foo::barBar();
                            }
                        }
                    }',
                'error_message' => 'InternalMethod',
            ],
            'psalmInternalMethodWithMethod' => [
                'code' => '<?php
                    namespace X {
                        class Foo {
                            /**
                             * @psalm-internal Y\Bat::batBat
                             */
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace Y {
                        class Bat {
                            public function fooFoo(): void {
                                \X\Foo::barBar();
                            }
                        }
                    }',
                'error_message' => 'InternalMethod',
            ],
            'internalClassWithNewFromFunction' => [
                'code' => '<?php
                    namespace A {
                        /** @internal */
                        final class Inner {}
                    }

                    namespace Other {
                        function f(): void {
                            new \A\Inner();
                        }
                    }',
                'error_message' => 'A\Inner is internal to A but called from Other\f',
            ],
            'internalClassWithStaticCallFromFunction' => [
                'code' => '<?php
                    namespace A {
                        /** @internal */
                        final class Inner {
                            public static function make(): void {}
                        }
                    }

                    namespace Other {
                        function f(): void {
                            \A\Inner::make();
                        }
                    }',
                'error_message' => 'A\Inner is internal to A but called from Other\f',
            ],
            'internalClassConstFromFunction' => [
                'code' => '<?php
                    namespace A {
                        /** @internal */
                        final class Inner {
                            public const C = 1;
                        }
                    }

                    namespace Other {
                        function f(): int {
                            return \A\Inner::C;
                        }
                    }',
                'error_message' => 'A\Inner is internal to A but called from Other\f',
            ],
            'internalClassConstOnInstanceFromFunction' => [
                'code' => '<?php
                    namespace A {
                        /** @internal */
                        final class Inner {
                            public const C = 1;
                        }
                    }

                    namespace Other {
                        function f(\A\Inner $i): int {
                            return $i::C;
                        }
                    }',
                'error_message' => 'A\Inner is internal to A but called from Other\f',
            ],
            'internalPropertyGetFromFunction' => [
                'code' => '<?php
                    namespace A {
                        final class Box {
                            /** @internal */
                            public int $p = 1;
                        }
                    }

                    namespace Other {
                        function f(\A\Box $b): int {
                            return $b->p;
                        }
                    }',
                'error_message' => 'A\Box::$p is internal to A but called from Other\f',
            ],
            'internalPropertySetFromFunction' => [
                'code' => '<?php
                    namespace A {
                        final class Box {
                            /** @internal */
                            public int $p = 1;
                        }
                    }

                    namespace Other {
                        function f(\A\Box $b): void {
                            $b->p = 2;
                        }
                    }',
                'error_message' => 'A\Box::$p is internal to A but called from Other\f',
            ],
            'internalClassWithNewFromClosureInFunction' => [
                'code' => '<?php
                    namespace A {
                        /** @internal */
                        final class Inner {}
                    }

                    namespace Other {
                        function f(): void {
                            $c = function (): void {
                                new \A\Inner();
                            };
                            $c();
                        }
                    }',
                'error_message' => 'A\Inner is internal to A but called from Other\f',
            ],
            'internalClassWithNewFromTopLevelCode' => [
                'code' => '<?php
                    namespace A {
                        /** @internal */
                        final class Inner {}
                    }

                    namespace Other {
                        new \A\Inner();
                    }',
                'error_message' => 'A\Inner is internal to A but called from Other',
            ],
            'internalClassWithStaticCallFromTopLevelCode' => [
                'code' => '<?php
                    namespace A {
                        /** @internal */
                        final class Inner {
                            public static function make(): void {}
                        }
                    }

                    namespace Other {
                        \A\Inner::make();
                    }',
                'error_message' => 'A\Inner is internal to A but called from Other',
            ],
            'internalClassConstFromTopLevelCode' => [
                'code' => '<?php
                    namespace A {
                        /** @internal */
                        final class Inner {
                            public const C = 1;
                        }
                    }

                    namespace Other {
                        echo \A\Inner::C;
                    }',
                'error_message' => 'A\Inner is internal to A but called from Other',
            ],
            'internalClassConstOnInstanceFromTopLevelCode' => [
                'code' => '<?php
                    namespace A {
                        /** @internal */
                        final class Inner {
                            public const C = 1;
                        }

                        function getInner(): Inner {
                            return new Inner();
                        }
                    }

                    namespace Other {
                        $i = \A\getInner();
                        echo $i::C;
                    }',
                'error_message' => 'A\Inner is internal to A but called from Other',
            ],
            'internalPropertyGetFromTopLevelCode' => [
                'code' => '<?php
                    namespace A {
                        final class Box {
                            /** @internal */
                            public int $p = 1;
                        }
                    }

                    namespace Other {
                        echo (new \A\Box())->p;
                    }',
                'error_message' => 'A\Box::$p is internal to A but called from Other',
            ],
            'internalPropertySetFromTopLevelCode' => [
                'code' => '<?php
                    namespace A {
                        final class Box {
                            /** @internal */
                            public int $p = 1;
                        }
                    }

                    namespace Other {
                        $b = new \A\Box();
                        $b->p = 2;
                    }',
                'error_message' => 'A\Box::$p is internal to A but called from Other',
            ],
            'internalClassWithNewFromRootNamespace' => [
                'code' => '<?php
                    namespace A {
                        /** @internal */
                        final class Inner {}
                    }

                    namespace {
                        new \A\Inner();
                    }',
                'error_message' => 'A\Inner is internal to A but called from root namespace',
            ],
            'internalClassConstFromClassLevelCode' => [
                'code' => '<?php
                    namespace A {
                        /** @internal */
                        final class Inner {
                            public const C = 1;
                        }
                    }

                    namespace Other {
                        final class Consumer {
                            public const K = \A\Inner::C;
                        }
                    }',
                'error_message' => 'A\Inner is internal to A but called from Other\Consumer',
            ],
            'internalAttributeConstructorOnClass' => [
                'code' => '<?php
                    namespace A {
                        #[\Attribute]
                        final class Attr {
                            /** @internal */
                            public function __construct() {}
                        }
                    }

                    namespace Other {
                        #[\A\Attr]
                        final class Consumer {}
                    }',
                'error_message' => 'Constructor A\Attr::__construct is internal to A but called from Other\Consumer',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'internalSymbolUsedInTraitOutsideOwningNamespace' => [
                'code' => '<?php
                    namespace A {
                        /** @internal */
                        final class Inner {}

                        final class User {
                            use \Other\T;
                        }
                    }

                    namespace Other {
                        trait T {
                            public function t(): void {
                                new \A\Inner();
                            }
                        }
                    }',
                'error_message' => 'A\Inner is internal to A but called from Other\T::t',
            ],
        ];
    }
}
