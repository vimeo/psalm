<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class SuperGlobalsTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    /**
     *
     */
    public function providerValidCodeParse(): iterable
    {
        yield 'http_response_headerIsList' => [
            'code' => '<?php
                /** @return list<string> */
                function returnsList(): array {
                    return $http_response_header;
                }
            ',
            'assertions' => []
        ];
    }
}
