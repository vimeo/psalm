<?php

namespace Psalm\Tests\ReturnTypeProvider;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class InArrayTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        yield 'inArrayNonStrictCallReturnsBoolWhenTypesAreCompatible' => [
            '<?php
                /**
                 * @return string[]
                 */
                function f(): array {
                    return ["1"];
                }
                $ret = in_array("1", f());
            ',
            ['$ret' => 'bool'],
        ];

        yield 'inArrayNonStrictCallReturnsBoolWhenTypesAreIncompatible' => [
            '<?php
                /**
                 * @return string[]
                 */
                function f(): array {
                    return ["1"];
                }
                $ret = in_array(1, f());
            ',
            ['$ret' => 'bool'],
        ];

        yield 'inArrayStrictCallReturnsFalseWhenTypesAreIncompatible' => [
            '<?php
                /**
                 * @return string[]
                 */
                function f(): array {
                    return ["1"];
                }
                $ret = in_array(1, f(), true);
            ',
            ['$ret' => 'false'],
        ];

        yield 'inArrayStrictCallReturnsBoolWhenTypesAreCompatible' => [
            '<?php
                /**
                 * @return string[]
                 */
                function f(): array {
                    return ["1"];
                }
                $ret = in_array("1", f(), true);
            ',
            ['$ret' => 'bool'],
        ];
    }
}
