<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class Php82Test extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'iterableListConditional' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @param iterable<mixed, mixed> $i
                     * @return ($i is list ? 'list' : 'iter')
                     */
                    function describe(iterable $i) {
                        return is_array($i) && array_is_list($i) ? 'list' : 'iter';
                    }

                    /**
                     * @return iterable<mixed, mixed>
                     */
                    function getIterable(): iterable {
                       return new ArrayObject(['x' => 23]);
                    }

                    $value = describe(getIterable()); // Should be 'list' | 'iter'

                    echo $value === 'iter'
                        ? 'Iterable!'
                        : 'List!';
                PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'anyIterableConvert' => [
                'code' => '<?php
                    function castToArray(iterable $arr): array {
                        return iterator_to_array($arr, false);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'iterator_to_arrayMixedKey' => [
                'code' => '<?php
                    /**
                     * @template TKey
                     * @template TValue
                     * @param iterable<TKey, TValue> $iterable
                     * @return array<TKey, TValue>
                     */
                    function toArray(iterable $iterable): array
                    {
                        return iterator_to_array($iterable);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
        ];
    }
}
