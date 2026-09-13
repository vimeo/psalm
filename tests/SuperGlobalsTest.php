<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class SuperGlobalsTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function providerValidCodeParse(): iterable
    {
        yield 'http_response_headerIsList' => [
            'code' => '<?php
                /** @return non-empty-list<non-falsy-string> */
                function returnsList(): array {
                    if (!isset($http_response_header)) {
                        throw new \RuntimeException();
                    }
                    return $http_response_header;
                }
            ',
            'assertions' => [],
        ];

        yield 'ENV has scalar entries only' => [
            'code' => '<?php
                /** @return array<array-key, scalar> */
                function f(): array {
                    return $_ENV;
                }
            ',
        ];
    }

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        yield 'undefined http_response_header' => [
            'code' => '<?php
                /** @return non-empty-list<non-falsy-string> */
                function returnsList(): array {
                    return $http_response_header;
                }
            ',
            'error_message' => 'InvalidReturnStatement',
        ];
    }
}
