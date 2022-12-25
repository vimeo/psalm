<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class SuperGlobalsTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

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
            'assertions' => []
        ];

        yield 'ENV has scalar entries only' => [
            'code' => '<?php
                /** @return array<array-key, scalar> */
                function f(): array {
                    return $_ENV;
                }
            '
        ];
    }
}
