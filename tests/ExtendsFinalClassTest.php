<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ExtendsFinalClassTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'suppressingIssueWhenUsedWithKeyword' => [
                '<?php

                final class A {}

                /**
                * @psalm-suppress InvalidExtendClass
                */
                class B extends A {}'
            ],
            'suppressingIssueWhenUsedWithAnnotation' => [
                '<?php

                /**
                * @final
                */
                class A {}

                /**
                * @psalm-suppress InvalidExtendClass
                */
                class B extends A {}'
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'invalidExtendsFinalClass' => [
                '<?php

                final class A {}

                class B extends A {}',

                'error_message' => 'InvalidExtendClass',
            ],

            'invalidExtendsAnnotatedFinalClass' => [
                '<?php

                /**
                * @final
                */
                class DoctrineA {}

                class DoctrineB extends DoctrineA {}',

                'error_message' => 'InvalidExtendClass',
            ],

            'invalidExtendsFinalClassAndOtherAnnotation' => [
                '<?php

                /**
                * @something-else-no-final annotation
                */
                final class DoctrineA {}

                class DoctrineB extends DoctrineA {}',

                'error_message' => 'InvalidExtendClass',
            ],
        ];
    }
}
