<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Config;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class OverrideTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    #[Override]
    protected function makeConfig(): Config
    {
        $config = parent::makeConfig();
        $config->ensure_override_attribute = true;
        return $config;
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'overrideOfAPropertyFromATraitAndOfAPromotedPropertyWithAParentProperty' => [
                'code' => '<?php
                    class Base {
                        public int $x = 1;
                        public int $y = 1;
                    }

                    trait T {
                        #[Override]
                        public int $x = 2;
                    }

                    final class FromTrait extends Base {
                        use T;
                    }

                    final class Promoted extends Base {
                        public function __construct(#[Override] public int $y = 3) {}
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'overrideOfAPropertyOfAParentClassOrAnInterface' => [
                'code' => '<?php
                    interface HasName {
                        public string $name { get; }
                    }

                    class Base {
                        public int $a = 1;
                        protected int $b = 1;
                    }

                    final class Child extends Base implements HasName {
                        #[Override]
                        public int $a = 2;

                        #[Override]
                        protected int $b = 2;

                        #[Override]
                        public string $name = "x";
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'constructor' => [
                'code' => '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    class C {
                        public function __construct() {}
                    }

                    class C2 extends C {
                        public function __construct() {}
                    }
                ',
            ],
            'overrideClass' => [
                'code' => '<?php
                    class C {
                        public function f(): void {}
                    }

                    class C2 extends C {
                        #[\Override]
                        public function f(): void {}
                    }
                ',
            ],
            'overrideInterface' => [
                'code' => '<?php
                    interface I {
                        public function f(): void;
                    }

                    interface I2 extends I {
                        #[Override]
                        public function f(): void;
                    }
                ',
            ],
            'traitNotMissingAttribute1' => [
                'code' => '<?php
                    trait B {
                        public function f(): void {}
                    }

                    class C {
                        use B;
                    }

                    class C2 extends C {
                        use B;
                    }
                ',
            ],
            'traitNotMissingAttribute2' => [
                'code' => '<?php
                    trait B {
                        public function f(): void {}
                    }

                    class C {
                        public function f(): void {}
                    }

                    class C2 extends C {
                        use B;
                    }
                ',
            ],
            'canBeUsedOnPureMethods' => [
                'code' => <<<'PHP'
                    <?php
                    class A {
                        /** @psalm-pure */
                        public function f(int $a): int {
                            return $a;
                        }
                    }
                    class B extends A {
                        /** @psalm-pure */
                        #[Override]
                        public function f(int $a): int {
                            return $a + 1;
                        }
                    }
                    PHP,
            ],
            'ignoreImplicitStringable' => [
                'code' => '
                    <?php
                    class A {
                        public function __toString(): string {
                            return "";
                        }
                    }
                ',
            ],
            'traitPublicMethodDemotedToPrivateIsNotAnOverride' => [
                'code' => '<?php
                    trait T {
                        public function f(): void {}
                    }

                    class A {
                        use T { f as private; }
                    }

                    class B extends A {
                        public function f(): void {}
                    }
                ',
            ],
            'traitPrivateMethodRedeclaredByChildIsNotAnOverride' => [
                'code' => '<?php
                    trait T {
                        private function f(): void {}
                    }

                    class A {
                        use T;

                        public function bar(): void {
                            $this->f();
                        }
                    }

                    class B extends A {
                        use T;

                        public function baz(): void {
                            $this->f();
                        }

                        private function f(): void {}
                    }
                ',
            ],
            'constantOverridesParentConstant' => [
                'code' => '<?php
                    class P {
                        public const int A = 1;
                        protected const int B = 1;
                    }

                    class C extends P {
                        #[\Override]
                        public const int A = 1;

                        #[\Override]
                        protected const int B = 1;
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'constantOverridesInterfaceConstant' => [
                'code' => '<?php
                    interface I {
                        public const int A = 1;
                    }

                    interface J extends I {
                        #[\Override]
                        public const int A = 1;
                    }

                    class C implements I {
                        #[\Override]
                        public const int A = 1;
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'constantOverridesGrandparentConstant' => [
                'code' => '<?php
                    class G {
                        public const int A = 1;
                    }

                    class P extends G {}

                    class C extends P {
                        #[\Override]
                        public const int A = 1;
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'inheritedOverrideConstantIsNotRechecked' => [
                'code' => '<?php
                    class G {
                        public const int A = 1;
                    }

                    class P extends G {
                        #[\Override]
                        public const int A = 1;
                    }

                    class C extends P {}
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'constantOverrideInTraitIsIgnored' => [
                'code' => '<?php
                    trait T {
                        #[\Override]
                        public const int A = 1;
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'traitConstantOverridesParentConstant' => [
                'code' => '<?php
                    trait T {
                        #[\Override]
                        public const int A = 1;
                    }

                    class P {
                        public const int A = 1;
                    }

                    class C extends P {
                        use T;
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'constantOfClassUsingTraitConstantOverridesParentConstant' => [
                'code' => '<?php
                    trait T {
                        public const int K = 1;
                    }

                    class P {
                        public const int K = 1;
                    }

                    class C extends P {
                        use T;

                        #[\Override]
                        public const int K = 1;
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'enumCaseOverridesInterfaceConstant' => [
                'code' => '<?php
                    interface I {
                        public const int A = 1;
                    }

                    enum E: int implements I {
                        #[\Override]
                        case A = 1;
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'enumConstantOverridesInterfaceConstant' => [
                'code' => '<?php
                    interface I {
                        public const int A = 1;
                    }

                    enum E implements I {
                        case X;

                        #[\Override]
                        public const int A = 1;
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'propertyWithTheOverrideAttributeNeedsNoMissingOverrideAttribute' => [
                'code' => '<?php
                    class P {
                        public string $label = "p";
                    }

                    class C extends P {
                        #[\\Override]
                        public string $label = "c";
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'promotedPropertyWithTheOverrideAttributeNeedsNoMissingOverrideAttribute' => [
                'code' => '<?php
                    class P {
                        public function __construct(public string $label = "p") {}
                    }

                    class C extends P {
                        public function __construct(#[\\Override] public string $label = "c") {
                            parent::__construct($label);
                        }
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'propertyOverridingAPrivatePropertyOrNothingNeedsNoOverrideAttribute' => [
                'code' => '<?php
                    class P {
                        private string $secret = "p";
                    }

                    class C extends P {
                        private string $secret = "c";
                        public int $own = 1;
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'propertyOfATraitNeedsNoOverrideAttribute' => [
                'code' => '<?php
                    class P {
                        public string $label = "p";
                    }

                    trait T {
                        public string $label = "t";
                    }

                    class C extends P {
                        use T;
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'propertyOverridingAPropertyNeedsNoOverrideAttributeBeforePhp85' => [
                'code' => '<?php
                    class P {
                        public string $label = "p";
                    }

                    class C extends P {
                        public string $label = "c";
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'constantWithTheOverrideAttributeNeedsNoMissingOverrideAttribute' => [
                'code' => '<?php
                    class P {
                        public const int K = 1;
                    }

                    class C extends P {
                        #[\\Override]
                        public const int K = 1;
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'constantOverridingAPrivateConstantOrNothingNeedsNoOverrideAttribute' => [
                'code' => '<?php
                    class P {
                        private const int HIDDEN = 1;
                    }

                    class C extends P {
                        private const int HIDDEN = 1;
                        public const int OWN = 3;
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'constantOfATraitNeedsNoOverrideAttribute' => [
                'code' => '<?php
                    class P {
                        public const int K = 1;
                    }

                    trait T {
                        public const int K = 1;
                    }

                    class C extends P {
                        use T;
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'inheritedConstantNeedsNoOverrideAttribute' => [
                'code' => '<?php
                    class P {
                        public const int K = 1;
                    }

                    class C extends P {}
                    class D extends C {}
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'constantOverridingAConstantNeedsNoOverrideAttributeBeforePhp86' => [
                'code' => '<?php
                    class P {
                        public const int K = 1;
                    }

                    class C extends P {
                        public const int K = 1;
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
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
            'overrideOfAPropertyOfATraitWithoutAParentProperty' => [
                'code' => '<?php
                    trait T {
                        #[Override]
                        public int $x = 1;
                    }

                    final class A {
                        use T;
                    }',
                'error_message' => 'InvalidOverride - src' . DIRECTORY_SEPARATOR
                    . 'somefile.php:3:27 - Property $x does not match any parent property',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'overrideOfAPropertyOfATraitWhoseParentPropertyIsPrivate' => [
                'code' => '<?php
                    class Base {
                        private int $x = 1;
                    }

                    trait T {
                        #[Override]
                        public int $x = 2;
                    }

                    final class A extends Base {
                        use T;
                    }',
                'error_message' => 'InvalidOverride - src' . DIRECTORY_SEPARATOR
                    . 'somefile.php:7:27 - Property $x does not match any parent property',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'overrideOfAPromotedPropertyWithoutAParentProperty' => [
                'code' => '<?php
                    final class C {
                        public function __construct(#[Override] public int $x) {}
                    }',
                'error_message' => 'InvalidOverride - src' . DIRECTORY_SEPARATOR
                    . 'somefile.php:3:55 - Property $x does not match any parent property',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'overrideOfAPropertyWithoutAParentProperty' => [
                'code' => '<?php
                    class Base {
                        public int $a = 1;
                        private int $secret = 1;
                    }

                    final class Child extends Base {
                        #[Override]
                        public int $b = 2;
                    }',
                'error_message' => 'InvalidOverride - src' . DIRECTORY_SEPARATOR
                    . 'somefile.php:8:27 - Property $b does not match any parent property',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'overrideOfAPropertyWhoseParentPropertyIsPrivate' => [
                'code' => '<?php
                    class Base {
                        private int $secret = 1;
                    }

                    final class Child extends Base {
                        #[Override]
                        public int $secret = 2;
                    }',
                'error_message' => 'InvalidOverride',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'noParent' => [
                'code' => '<?php
                    class C {
                        #[Override]
                        public function f(): void {}
                    }
                ',
                'error_message' => 'InvalidOverride',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'traitPrivateMethodPromotedToPublicIsStillAnOverride' => [
                'code' => '<?php
                    trait T {
                        private function f(): void {}
                    }

                    class A {
                        use T { f as public; }
                    }

                    class B extends A {
                        public function f(): void {}
                    }
                ',
                'error_message' => 'MissingOverrideAttribute',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'traitPrivateMethodPromotedToProtectedIsStillAnOverride' => [
                'code' => '<?php
                    trait T {
                        private function f(): void {}
                    }

                    class A {
                        use T { f as protected; }
                    }

                    class B extends A {
                        protected function f(): void {}
                    }
                ',
                'error_message' => 'MissingOverrideAttribute',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'classMissingAttribute' => [
                'code' => '<?php
                    class C {
                        public function f(): void {}
                    }

                    class C2 extends C {
                        public function f(): void {}
                    }
                ',
                'error_message' => 'MissingOverrideAttribute',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'classUsingTrait' => [
                'code' => '<?php
                    trait T {
                        abstract public function f(): void;
                    }

                    class C {
                        use T;

                        public function f(): void {}
                    }
                ',
                'error_message' => 'MissingOverrideAttribute',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'constructor' => [
                'code' => '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    class C {
                        public function __construct() {}
                    }

                    class C2 extends C {
                        #[Override]
                        public function __construct() {}
                    }
                ',
                'error_message' => 'InvalidOverride',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'interfaceMissingAttribute' => [
                'code' => '<?php
                    interface I {
                        public function f(): void;
                    }

                    interface I2 extends I {
                        public function f(): void;
                    }
                ',
                'error_message' => 'MissingOverrideAttribute',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'privateMethod' => [
                'code' => '<?php
                    class C {
                        private function f(): void {}
                    }

                    class C2 extends C {
                        #[Override]
                        private function f(): void {}
                    }
                ',
                'error_message' => 'InvalidOverride',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'interfaceWithNoParent' => [
                'code' => '<?php
                    interface I {
                        #[Override]
                        public function f(): void;
                    }
                ',
                'error_message' => 'InvalidOverride',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'explicitStringable' => [
                'code' => '
                    <?php
                    class A implements Stringable {
                        public function __toString(): string {
                            return "";
                        }
                    }
                ',
                'error_message' => 'MissingOverrideAttribute',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'constantOverridesNothing' => [
                'code' => '<?php
                    class C {
                        #[\Override]
                        public const int K = 1;
                    }
                ',
                'error_message' => 'InvalidOverride - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:27 - Class constant C::K does not match any parent constant',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'constantOverridesPrivateParentConstant' => [
                'code' => '<?php
                    class P {
                        private const int K = 1;
                    }

                    class C extends P {
                        #[\Override]
                        public const int K = 1;
                    }
                ',
                'error_message' => 'InvalidOverride',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'interfaceConstantOverridesNothing' => [
                'code' => '<?php
                    interface I {
                        #[\Override]
                        public const int K = 1;
                    }
                ',
                'error_message' => 'InvalidOverride',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'traitConstantOverridesNothingInUsingClass' => [
                'code' => '<?php
                    trait T {
                        #[\Override]
                        public const int K = 1;
                    }

                    class C {
                        use T;
                    }
                ',
                'error_message' => 'InvalidOverride',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'constantOfClassUsingTraitConstantOverridesNothing' => [
                'code' => '<?php
                    trait T {
                        public const int K = 1;
                    }

                    class C {
                        use T;

                        #[\Override]
                        public const int K = 1;
                    }
                ',
                'error_message' => 'InvalidOverride - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:27 - Class constant C::K does not match any parent constant',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'enumCaseOverridesNothing' => [
                'code' => '<?php
                    enum E {
                        #[\Override]
                        case A;
                    }
                ',
                'error_message' => 'InvalidOverride - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:27 - Enum case E::A does not match any parent constant',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'enumConstantOverridesNothing' => [
                'code' => '<?php
                    enum E {
                        case A;

                        #[\Override]
                        public const int K = 1;
                    }
                ',
                'error_message' => 'InvalidOverride',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'constantOverrideAttributeBeforePhp86' => [
                'code' => '<?php
                    class P {
                        public const int K = 1;
                    }

                    class C extends P {
                        #[\Override]
                        public const int K = 1;
                    }
                ',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:27 - Attribute Override cannot be used on a class constant',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'constantOverridingAParentConstantNeedsTheOverrideAttribute' => [
                'code' => '<?php
                    class P {
                        public const int K = 1;
                    }

                    class C extends P {
                        public const int K = 1;
                    }
                ',
                'error_message' => 'MissingOverrideAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:42 - Class constant C::K should have the "Override" attribute',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'constantImplementingAnInterfaceConstantNeedsTheOverrideAttribute' => [
                'code' => '<?php
                    interface HasSides {
                        public const int SIDES = 0;
                    }

                    enum Kind: int implements HasSides {
                        case A = 1;
                        public const int SIDES = 0;
                    }
                ',
                'error_message' => 'MissingOverrideAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:42 - Class constant Kind::SIDES should have the "Override" attribute',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'staticPropertyOverridingAParentPropertyNeedsTheOverrideAttribute' => [
                'code' => '<?php
                    class P {
                        public static int $count = 0;
                    }

                    class C extends P {
                        public static int $count = 1;
                    }
                ',
                'error_message' => 'MissingOverrideAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:43 - Property C::$count should have the "Override" attribute',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'enumCaseImplementingAnInterfaceConstantNeedsTheOverrideAttribute' => [
                'code' => '<?php
                    interface HasA {
                        public const int A = 1;
                    }

                    enum E: int implements HasA {
                        case A = 1;
                    }
                ',
                'error_message' => 'MissingOverrideAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:25 - Enum case E::A should have the "Override" attribute',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'propertyOverridingAParentPropertyNeedsTheOverrideAttribute' => [
                'code' => '<?php
                    class P {
                        public string $label = "p";
                    }

                    class C extends P {
                        public string $label = "c";
                    }
                ',
                'error_message' => 'MissingOverrideAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:39 - Property C::$label should have the "Override" attribute',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'protectedPropertyOverridingAParentPropertyNeedsTheOverrideAttribute' => [
                'code' => '<?php
                    class P {
                        protected int $size = 1;
                    }

                    class C extends P {
                        protected int $size = 2;
                    }
                ',
                'error_message' => 'MissingOverrideAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:39 - Property C::$size should have the "Override" attribute',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'promotedPropertyOverridingAParentPropertyNeedsTheOverrideAttribute' => [
                'code' => '<?php
                    class P {
                        public string $label = "p";
                    }

                    class C extends P {
                        public function __construct(public string $label = "c") {}
                    }
                ',
                'error_message' => 'MissingOverrideAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:67 - Property C::$label should have the "Override" attribute',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'propertyImplementingAnInterfacePropertyNeedsTheOverrideAttribute' => [
                'code' => '<?php
                    interface HasName {
                        public string $name { get; }
                    }

                    class Named implements HasName {
                        public string $name = "n";
                    }
                ',
                'error_message' => 'MissingOverrideAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:39 - Property Named::$name should have the "Override" attribute',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
        ];
    }
}
