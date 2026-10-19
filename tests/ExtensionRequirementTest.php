<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

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
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function providerValidCodeParse(): iterable
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
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function providerInvalidCodeParse(): iterable
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
        ];
    }
}
