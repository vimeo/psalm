<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class CoreStubsTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        yield 'RecursiveArrayIterator::CHILD_ARRAYS_ONLY (#6464)' => [
            '<?php

            new RecursiveArrayIterator([], RecursiveArrayIterator::CHILD_ARRAYS_ONLY);'
        ];
        yield 'proc_open() named arguments' => [
            '<?php

            proc_open(
                command: "ls",
                descriptor_spec: [],
                pipes: $pipes,
                cwd: null,
                env_vars: null,
                options: null
            );',
            'assertions' => [],
            'error_levels' => [],
            'php_version' => '8.0',
        ];
    }
}
