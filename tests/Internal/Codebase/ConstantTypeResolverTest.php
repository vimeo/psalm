<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Internal\Codebase\ConstantTypeResolver;
use Psalm\Internal\Scanner\UnresolvedConstant\ScalarValue;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedBitwiseAnd;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedBitwiseOr;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedBitwiseXor;
use Psalm\Tests\TestCase;

use const NAN;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

final class ConstantTypeResolverTest extends TestCase
{
    #[DataProvider('providerBitwiseOperations')]
    public function testBitwiseOperationsOfLiterals(
        string $operator,
        float|int $left,
        float|int $right,
        string $expected,
    ): void {
        $left_node = new ScalarValue($left);
        $right_node = new ScalarValue($right);

        $type = ConstantTypeResolver::resolve(
            $this->project_analyzer->getCodebase()->classlikes,
            match ($operator) {
                '|' => new UnresolvedBitwiseOr($left_node, $right_node),
                '^' => new UnresolvedBitwiseXor($left_node, $right_node),
                default => new UnresolvedBitwiseAnd($left_node, $right_node),
            },
        );

        $this->assertSame($expected, $type->getId());
    }

    /**
     * @return iterable<string, array{string, float|int, float|int, string}>
     * @psalm-pure
     */
    public static function providerBitwiseOperations(): iterable
    {
        yield 'or of ints' => ['|', 6, 3, '7'];
        yield 'xor of ints' => ['^', 6, 3, '5'];
        yield 'and of ints' => ['&', 6, 3, '2'];
        yield 'or of floats that are ints' => ['|', 2.0, 1.0, '3'];
        yield 'xor of an int and a float that is an int' => ['^', 6, 2.0, '4'];
        yield 'and of a float that is an int and an int' => ['&', 6.0, 3, '2'];
        yield 'or of the lowest int' => ['|', (float) PHP_INT_MIN, 1, '-9223372036854775807'];
        yield 'or with a fraction on the left' => ['|', 1.5, 1, 'int'];
        yield 'xor with a fraction on the right' => ['^', 2, 0.5, 'int'];
        yield 'and with a fraction' => ['&', 6, 2.5, 'int'];
        yield 'or with a float beyond the ints' => ['|', -(float) PHP_INT_MIN, 1, 'int'];
        yield 'or with a negative float beyond the ints' => ['|', 1, -(float) PHP_INT_MAX * 1000.0, 'int'];
        yield 'or with NAN' => ['|', NAN, 1, 'int'];
    }
}
