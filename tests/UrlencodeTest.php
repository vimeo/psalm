<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class UrlencodeTest. extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'urlencodeEmptyStringReturnsEmptyString' => [
                'code' => '<?php
                    $string = urlencode("");
                ',
                'assertions' => [
                    '$string' => '\'\'',
                ],
            ],
            'urlencodeNonEmptyStringReturnsNonEmptyString' => [
                'code' => '<?php
                    /** 
                     * @param non-empty-string $input 
                     * @return non-empty-string 
                     */
                    function f(string $input): string {
                        return urlencode($input);
                    }

                    $string = f("test");
                ',
                'assertions' => [
                    '$string' => 'non-empty-string',
                ],
            ],
            'urlencodeAnyStringReturnsAnyString' => [
                'code' => '<?php
                    function f(string $input): string {
                        return urlencode($input);
                    }

                    $string = f("test");
                ',
                'assertions' => [
                    '$string' => 'string',
                ],
            ],
        ];
    }

    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'urlencodeEmptyStringReturnsEmptyString' => [
                'code' => '<?php
                    $string = urlencode("");
                    assert($string !== "");
                ',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'urlencodeNonEmptyStringReturnsNonEmptyString' => [
                'code' => '<?php
                    $string = urlencode("hello");
                    assert($string === "");
                ',
                'error_message' => 'TypeDoesNotContainType',
            ],
        ];
    }
}
