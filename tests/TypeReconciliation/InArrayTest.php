<?php
namespace Psalm\Tests\TypeReconciliation;

use const DIRECTORY_SEPARATOR;

class InArrayTest extends \Psalm\Tests\TestCase
{
    use \Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;
    use \Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'nullTypeRemovedAfterNegatedAssertionAgainstArrayOfInt' => [
                '<?php
                    /**
                     * @param int|null $x
                     * @return int
                     */
                    function assertInArray($x) {
                        if (!in_array($x, range(0, 5), true)) {
                            throw new \Exception();
                        }

                        return $x;
                    }',
            ],
            'nullTypeRemovedAfterAssertionAgainstArrayOfInt' => [
                '<?php
                    /**
                     * @param int|null $x
                     * @param non-empty-list<int> $y
                     * @return int|null
                     */
                    function assertInArray($x, $y) {
                        if (in_array($x, $y, true)) {
                            acceptInt($x);
                        }

                        return $x;
                    }
                    /**
                     * @param int $x
                     */
                    function acceptInt($x): void {
                    }',
            ],
            'typeNotChangedAfterAssertionAgainstArrayOfMixed' => [
                '<?php
                    /**
                     * @param int|null $x
                     * @param list<mixed> $y
                     * @return int|null
                     */
                    function assertInArray($x, $y) {
                        if (!in_array($x, $y, true)) {
                            throw new \Exception();
                        }

                        return $x;
                    }',
            ],
            'unionTypeReconciledToUnionTypeOfHaystackValueTypes' => [
                '<?php
                    /**
                     * @param int|string|bool $x
                     * @param non-empty-list<int|string> $y
                     * @return int|string|bool
                     */
                    function assertInArray($x, $y) {
                        if (in_array($x, $y, true)) {
                            acceptIntAndStr($x);
                        }

                        return $x;
                    }
                    /**
                     * @param int|string $x
                     */
                    function acceptIntAndStr($x): void {
                    }',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'typeNotChangedAfterNegatedAssertionAgainstUnsealedArrayOfMixed' => [
                '<?php
                    /**
                     * @param int|null $x
                     * @param non-empty-list<mixed> $y
                     * @return int|null
                     */
                    function assertInArray($x, $y) {
                        if (!in_array($x, $y, true)) {
                            acceptInt($x);
                        }

                        return $x;
                    }
                    /**
                     * @param int $x
                     */
                    function acceptInt($x): void {
                    }',
                'error_message' => 'PossiblyNullArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:39 - Argument 1 of acceptInt cannot be null, possibly null value provided',
            ],
            'typeNotChangedAfterNegatedAssertionAgainstUnsealedArrayOfUnionType' => [
                '<?php
                    /**
                     * @param int|null $x
                     * @param non-empty-list<int|null> $y
                     * @return int|null
                     */
                    function assertInArray($x, $y) {
                        if (!in_array($x, $y, true)) {
                            acceptInt($x);
                        }

                        return $x;
                    }
                    /**
                     * @param int $x
                     */
                    function acceptInt($x): void {
                    }',
                'error_message' => 'PossiblyNullArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:39 - Argument 1 of acceptInt cannot be null, possibly null value provided',
            ],
        ];
    }
}
