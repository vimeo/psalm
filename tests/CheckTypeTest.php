<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class CheckTypeTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{code:string,assertions?:array<string,string>,ignored_issues?:list<string>,php_version?:string}>
     */
    public function providerValidCodeParse(): iterable
    {
        yield 'allowSubtype' => [
            'code' => '<?php
                /** @psalm-check-type $foo = int */
                $foo = 1;
            ',
        ];
    }

    /**
     * @return iterable<string,array{code:string,error_message:string,ignored_issues?:list<string>,php_version?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        yield 'checkType' => [
            'code' => '<?php
                $foo = 1;
                /** @psalm-check-type $foo = 2 */;
            ',
            'error_message' => 'CheckType',
        ];
        yield 'checkTypeExact' => [
            'code' => '<?php
                /** @psalm-check-type-exact $foo = int */
                $foo = 1;
            ',
            'error_message' => 'CheckType',
        ];
        yield 'checkMultipleTypesFirstCorrect' => [
            'code' => '<?php
                $foo = 1;
                $bar = 2;
                /**
                 * @psalm-check-type $foo = 1
                 * @psalm-check-type $bar = 3
                 */;
            ',
            'error_message' => 'CheckType',
        ];
        yield 'possiblyUnset' => [
            'code' => '<?php
                try {
                    $foo = 1;
                } catch (Exception $_) {
                }
                /** @psalm-check-type $foo = 1 */;
            ',
            'error_message' => 'Checked variable $foo = 1 does not match $foo? = 1',
        ];
        yield 'notPossiblyUnset' => [
            'code' => '<?php
                $foo = 1;
                /** @psalm-check-type $foo? = 1 */;
            ',
            'error_message' => 'Checked variable $foo? = 1 does not match $foo = 1',
        ];
    }
}
