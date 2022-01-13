<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ExtendsFinalClassTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{code:string,assertions?:array<string,string>,ignored_issues?:array<string>}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'suppressingIssueWhenUsedWithKeyword' => [
                'code' => '<?php

                final class A {}

                /**
                * @psalm-suppress InvalidExtendClass
                */
                class B extends A {}'
            ],
            'suppressingIssueWhenUsedWithAnnotation' => [
                'code' => '<?php

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
     * @return iterable<string,array{code:string,error_message:string,ignored_issues?:array<string>,strict_mode?:bool,php_version?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'invalidExtendsFinalClass' => [
                'code' => '<?php

                final class A {}

                class B extends A {}',

                'error_message' => 'InvalidExtendClass',
            ],

            'invalidExtendsAnnotatedFinalClass' => [
                'code' => '<?php

                /**
                * @final
                */
                class DoctrineA {}

                class DoctrineB extends DoctrineA {}',

                'error_message' => 'InvalidExtendClass',
            ],

            'invalidExtendsFinalClassAndOtherAnnotation' => [
                'code' => '<?php

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
