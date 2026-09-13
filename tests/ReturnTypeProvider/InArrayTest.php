<?php

declare(strict_types=1);

namespace Psalm\Tests\ReturnTypeProvider;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class InArrayTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function providerValidCodeParse(): iterable
    {
        yield 'inArrayNonStrictCallReturnsBoolWhenTypesAreCompatible' => [
            'code' => '<?php
                /**
                 * @return string[]
                 */
                function f(): array {
                    return ["1"];
                }
                $ret = in_array("1", f());
            ',
            'assertions' => ['$ret' => 'bool'],
        ];

        yield 'inArrayNonStrictCallReturnsBoolWhenTypesAreIncompatible' => [
            'code' => '<?php
                /**
                 * @return string[]
                 */
                function f(): array {
                    return ["1"];
                }
                $ret = in_array(1, f());
            ',
            'assertions' => ['$ret' => 'bool'],
        ];

        yield 'inArrayStrictCallReturnsFalseWhenTypesAreIncompatible' => [
            'code' => '<?php
                /**
                 * @return string[]
                 */
                function f(): array {
                    return ["1"];
                }
                $ret = in_array(1, f(), true);
            ',
            'assertions' => ['$ret' => 'false'],
        ];

        yield 'inArrayStrictCallReturnsBoolWhenTypesAreCompatible' => [
            'code' => '<?php
                /**
                 * @return string[]
                 */
                function f(): array {
                    return ["1"];
                }
                $ret = in_array("1", f(), true);
            ',
            'assertions' => ['$ret' => 'bool'],
        ];
    }
}
