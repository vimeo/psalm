<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class ExtensionRequirementTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->addFile(
            'base.php',
            '<?php
                namespace ExtensionRequirements\Base;

                class MyBaseClass { }
            ',
        );

        $this->addFile(
            'trait.php',
            '<?php
                namespace ExtensionRequirements\Trait;

                use ExtensionRequirements\Base\MyBaseClass as MyAliasedBaseClass;

                /** @psalm-require-extends MyAliasedBaseClass */
                trait ImposesExtensionRequirements { }
            ',
        );

        $this->addFile(
            'phpstan_trait.php',
            '<?php
                namespace ExtensionRequirements\Trait;

                use ExtensionRequirements\Base\MyBaseClass as MyAliasedBaseClass;

                /** @phpstan-require-extends MyAliasedBaseClass */
                trait ImposesPhpstanExtensionRequirements { }
            ',
        );

        $this->addFile(
            'generic_wider_trait.php',
            '<?php
                namespace ExtensionRequirements\Generic;

                /** @psalm-require-extends GenericBase<int|string> */
                trait ImposesWiderGenericExtensionRequirement { }
            ',
        );

        $this->addFile(
            'generic_covariant_trait.php',
            '<?php
                namespace ExtensionRequirements\Generic;

                /** @template-covariant T */
                class CovariantBase { }

                /** @psalm-require-extends CovariantBase<int|string> */
                trait ImposesCovariantExtensionRequirement { }
            ',
        );

        $this->addFile(
            'generic_trait.php',
            '<?php
                namespace ExtensionRequirements\Generic;

                /** @template T */
                class GenericBase { }

                /**
                 * @template T
                 * @psalm-require-extends GenericBase<T>
                 */
                trait ImposesGenericExtensionRequirement { }
            ',
        );
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'extendsBaseClass' => [
                'code' => '<?php
                    use ExtensionRequirements\Base\MyBaseClass;
                    use ExtensionRequirements\Trait\ImposesExtensionRequirements;

                    class Valid extends MyBaseClass {
                        use ImposesExtensionRequirements;
                    }
                ',
            ],
            'extendsBaseClassWithPhpstanTag' => [
                'code' => '<?php
                    use ExtensionRequirements\Base\MyBaseClass;
                    use ExtensionRequirements\Trait\ImposesPhpstanExtensionRequirements;

                    class Valid extends MyBaseClass {
                        use ImposesPhpstanExtensionRequirements;
                    }
                ',
            ],
            'genericRequirementWithTheSameTypeArgument' => [
                'code' => '<?php
                    use ExtensionRequirements\Generic\GenericBase;
                    use ExtensionRequirements\Generic\ImposesGenericExtensionRequirement;

                    /** @extends GenericBase<string> */
                    class Valid extends GenericBase {
                        /** @use ImposesGenericExtensionRequirement<string> */
                        use ImposesGenericExtensionRequirement;
                    }
                ',
            ],
            'genericRequirementKeepsTheTypeOfTheTemplateOfTheTrait' => [
                'code' => '<?php
                    /** @template T */
                    class A {
                        /** @param T $a */
                        public function __construct(protected readonly mixed $a) {}
                    }

                    /**
                     * @template T
                     * @psalm-require-extends A<T>
                     */
                    trait C {
                        /** @return T */
                        public function a() {
                            return $this->a;
                        }
                    }

                    /**
                     * @template T
                     * @extends A<T>
                     */
                    class B extends A {
                        /** @use C<T> */
                        use C;
                    }

                    /** @var B<string> $b */
                    $b = new B("x");
                    $result = $b->a();
                ',
                'assertions' => [
                    '$result' => 'string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'genericRequirementOfACovariantTemplateWithAWiderTypeArgument' => [
                'code' => '<?php
                    use ExtensionRequirements\Generic\CovariantBase;
                    use ExtensionRequirements\Generic\ImposesCovariantExtensionRequirement;

                    /** @extends CovariantBase<int> */
                    class Valid extends CovariantBase {
                        use ImposesCovariantExtensionRequirement;
                    }
                ',
            ],
            'extendsGenericBaseClass' => [
                'code' => '<?php
                    use ExtensionRequirements\Generic\GenericBase;
                    use ExtensionRequirements\Generic\ImposesGenericExtensionRequirement;

                    /**
                     * @template T
                     * @extends GenericBase<T>
                     */
                    class Valid extends GenericBase {
                        /** @use ImposesGenericExtensionRequirement<T> */
                        use ImposesGenericExtensionRequirement;
                    }
                ',
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
            'extendsBaseClass' => [
                'code' => '<?php
                    use ExtensionRequirements\Trait\ImposesExtensionRequirements;

                    class Invalid {
                        use ImposesExtensionRequirements;
                    }
                ',
                'error_message' => 'requires using class to extend',
            ],
            'extendsBaseClassWithPhpstanTag' => [
                'code' => '<?php
                    use ExtensionRequirements\Trait\ImposesPhpstanExtensionRequirements;

                    class Invalid {
                        use ImposesPhpstanExtensionRequirements;
                    }
                ',
                'error_message' => 'requires using class to extend',
            ],
            'genericRequirementWithAnotherTypeArgument' => [
                'code' => '<?php
                    use ExtensionRequirements\Generic\GenericBase;
                    use ExtensionRequirements\Generic\ImposesGenericExtensionRequirement;

                    /** @extends GenericBase<string> */
                    class Invalid extends GenericBase {
                        /** @use ImposesGenericExtensionRequirement<int> */
                        use ImposesGenericExtensionRequirement;
                    }
                ',
                'error_message' => 'requires using class to extend ExtensionRequirements\Generic\GenericBase',
            ],
            'genericRequirementOfAnInvariantTemplateWithAWiderTypeArgument' => [
                'code' => '<?php
                    use ExtensionRequirements\Generic\GenericBase;
                    use ExtensionRequirements\Generic\ImposesWiderGenericExtensionRequirement;

                    /** @extends GenericBase<int> */
                    class Invalid extends GenericBase {
                        use ImposesWiderGenericExtensionRequirement;
                    }
                ',
                'error_message' => 'requires using class to extend ExtensionRequirements\Generic\GenericBase',
            ],
            'doesNotExtendGenericBaseClass' => [
                'code' => '<?php
                    use ExtensionRequirements\Generic\ImposesGenericExtensionRequirement;

                    /** @template T */
                    class Invalid {
                        /** @use ImposesGenericExtensionRequirement<T> */
                        use ImposesGenericExtensionRequirement;
                    }
                ',
                'error_message' => 'requires using class to extend ExtensionRequirements\Generic\GenericBase',
            ],
            'unparseableExtensionRequirementIsAnIssue' => [
                'code' => '<?php
                    /** @psalm-require-extends SomeBase< */
                    trait T {}',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:27 -'
                    . ' No generic params provided for type in docblock for T',
            ],
        ];
    }
}
