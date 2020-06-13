<?php

namespace Psalm\Tests;

class ExtendsFinalClassTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
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
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
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
