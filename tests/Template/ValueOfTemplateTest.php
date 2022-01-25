<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ValueOfTemplateTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{code:string,assertions?:array<string,string>,ignored_issues?:list<string>,php_version?:string}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'acceptsArrayValuesFn' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     * @return value-of<T>[]
                     */
                    function getValues($array) {
                        return array_values($array);
                    }
                '
            ],
            'SKIPPED-acceptsIfInArrayFn' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     * @return value-of<T>|null
                     */
                    function getValue(string $value, $array) {
                        if (in_array($value, $array)) {
                            return $value;
                        }
                        return null;
                    }
                '
            ],
        ];
    }

    /**
     * @return iterable<string,array{code:string,error_message:string,ignored_issues?:list<string>,php_version?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'valueOfTemplateNotIncludesString' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     * @return value-of<T>
                     */
                    function getValue($array) {
                        return "foo";
                    }
                ',
                'error_message' => 'InvalidReturnStatement'
            ],
            'valueOfTemplateNotIncludesInt' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     * @return value-of<T>
                     */
                    function getValue($array) {
                        return 0;
                    }
                ',
                'error_message' => 'InvalidReturnStatement'
            ],
        ];
    }
}
