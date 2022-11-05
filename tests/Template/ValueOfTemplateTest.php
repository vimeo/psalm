<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ValueOfTemplateTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     *
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
            'valueOfUnreplacedTemplateParam' => [
                'code' => '<?php
                    /**
                     * @template T as array<bool>
                     */
                    abstract class Foo {
                        /**
                         * @return value-of<T>
                         */
                        abstract public function getRandomValue(): bool;
                    }
                ',
            ],
            'valueOfNestedTemplates' => [
                'code' => '<?php
                    /**
                     * @template TValue
                     * @template TArray of array<TValue>
                     * @param TArray $array
                     * @return list<TValue>
                     */
                    function toList(array $array): array {
                        return array_values($array);
                    }'
            ],
        ];
    }

    /**
     *
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
            'valueOfUnresolvedTemplateParamIsStillChecked' => [
                'code' => '<?php
                    /**
                     * @template T as array<bool>
                     */
                    abstract class Foo {
                        /**
                         * @return value-of<T>
                         */
                        abstract public function getRandomValue(): string;
                    }
                ',
                'error_message' => 'MismatchingDocblockReturnType'
            ],
        ];
    }
}
