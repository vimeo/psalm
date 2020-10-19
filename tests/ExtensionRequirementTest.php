<?php

namespace Psalm\Tests;

class ExtensionRequirementTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;
    use Traits\InvalidCodeAnalysisTestTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->addFile(
            'base.php',
            '<?php
                namespace ExtensionRequirements\Base;

                class MyBaseClass { }
            '
        );

        $this->addFile(
            'trait.php',
            '<?php
                namespace ExtensionRequirements\Trait;

                use ExtensionRequirements\Base\MyBaseClass as MyAliasedBaseClass;

                /** @psalm-require-extends MyAliasedBaseClass */
                trait ImposesExtensionRequirements { }
            '
        );
    }

    public function providerValidCodeParse(): iterable
    {
        return [
            'extendsBaseClass' => [
                '<?php
                    use ExtensionRequirements\Base\MyBaseClass;
                    use ExtensionRequirements\Trait\ImposesExtensionRequirements;

                    class Valid extends MyBaseClass {
                        use ImposesExtensionRequirements;
                    }
                '
            ]
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'extendsBaseClass' => [
                '<?php
                    use ExtensionRequirements\Trait\ImposesExtensionRequirements;

                    class Invalid {
                        use ImposesExtensionRequirements;
                    }
                ',
                'error_message' => 'requires using class to extend'
            ]
        ];
    }
}
