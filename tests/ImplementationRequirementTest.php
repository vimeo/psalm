<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ImplementationRequirementTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->addFile(
            'base.php',
            '<?php
                namespace ImplementationRequirements\Base;

                interface A { }
                interface B { }
            ',
        );

        $this->addFile(
            'trait.php',
            '<?php
                namespace ImplementationRequirements\Trait;

                use ImplementationRequirements\Base\A as MyAliasedInterfaceA;
                use ImplementationRequirements\Base\B as MyAliasedInterfaceB;

                /**
                 * @psalm-require-implements MyAliasedInterfaceA
                 * @psalm-require-implements MyAliasedInterfaceB
                 */
                trait ImposesImplementationRequirements { }
            ',
        );
    }

    public function providerValidCodeParse(): iterable
    {
        return [
            'implementsAllRequirements' => [
                'code' => '<?php
                    use ImplementationRequirements\Base\A;
                    use ImplementationRequirements\Base\B;
                    use ImplementationRequirements\Trait\ImposesImplementationRequirements;

                    class Valid implements A, B {
                        use ImposesImplementationRequirements;
                    }
                ',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'doesNotImplementAnything' => [
                'code' => '<?php
                    use ImplementationRequirements\Trait\ImposesImplementationRequirements;

                    class Invalid {
                        use ImposesImplementationRequirements;
                    }
                ',
                'error_message' => 'requires using class to implement',
            ],
            'onlyImplementsOneRequirement' => [
                'code' => '<?php
                    use ImplementationRequirements\Trait\ImposesImplementationRequirements;
                    use ImplementationRequirements\Base\A;

                    class Invalid implements A {
                        use ImposesImplementationRequirements;
                    }
                ',
                'error_message' => 'requires using class to implement',
            ],
        ];
    }
}
