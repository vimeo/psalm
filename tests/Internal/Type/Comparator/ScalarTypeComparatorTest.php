<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Type\Comparator;

use PHPUnit\Framework\TestCase;
use Psalm\Codebase;
use Psalm\Internal\Type\Comparator\ScalarTypeComparator;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TEmptyNumeric;
use Psalm\Type\Atomic\TEmptyScalar;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TKeyOf;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNonEmptyScalar;
use Psalm\Type\Atomic\TNonspecificLiteralInt;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Union;

use function get_class;
use function var_export;

class ScalarTypeComparatorTest extends TestCase
{
    /**
     * @covers \Psalm\Internal\Type\Comparator\ScalarTypeComparator::isContainedBy
     * @dataProvider provideForIsContainedBy
     */
    public function testIsContainedBy(
        Scalar $input_type,
        Scalar $container_type,
        bool $expected_result,
        ?TypeComparisonResult $expected_type_comparison_result,
        bool $allow_float_int_equality = false
    ): void {
        $result = ScalarTypeComparator::isContainedBy(
            $this->createMock(Codebase::class),
            $input_type,
            $container_type,
            false,
            $allow_float_int_equality,
            $type_comparison_result = new TypeComparisonResult(),
        );
        $failure_message = var_export(
            ['input' => get_class($input_type), 'container' => get_class($container_type)],
            true,
        );
        $this->assertSame($expected_result, $result, $failure_message);
        $this->assertSame(
            var_export($expected_type_comparison_result ?? new TypeComparisonResult(), true),
            var_export($type_comparison_result, true),
            $failure_message,
        );
    }

    /**
     * @psalm-return iterable<array-key, list{0: Scalar, 1: Scalar, 2: bool, 3: TypeComparisonResult|null, 4?: bool}>
     */
    public static function provideForIsContainedBy(): iterable
    {
        yield from self::provideTArrayKeyComparisons();
        yield from self::provideTKeyOfComparisons();
        yield from self::provideTNumericComparisons();
        yield from self::provideTEmptyNumericComparisons();
        yield from self::provideTScalarComparisons();
        yield from self::provideTEmptyScalarComparisons();
        yield from self::provideTNonEmptyScalarComparisons();
        yield from self::provideTBoolComparisons();
        yield from self::provideTFalseComparisons();
        yield from self::provideTTrueComparisons();
        yield from self::provideTFloatComparisons();
        yield from self::provideTLiteralFloatComparisons();
        yield from self::provideTIntComparisons();
    }

    /**
     * @psalm-return iterable<array-key, list{Scalar, TArrayKey, bool, TypeComparisonResult|null}>
     */
    private static function provideTArrayKeyComparisons(): iterable
    {
        $contained_inputs = [
            new TArrayKey(),
            new TKeyOf(new Union([new TLiteralString('hewo')])),
            new TString(),
            new TInt(),
            new TNumeric(),
        ];
        foreach ($contained_inputs as $contained_input) {
            yield [$contained_input, new TArrayKey(), true, null];
        }

        $not_contained_inputs = [
            [new TScalar(), new TypeComparisonResult(
                true,
                true,
                null,
                null,
                null,
                true,
            )],
            [new TBool(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TFloat(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
        ];
        foreach ($not_contained_inputs as $not_contained_input) {
            yield [$not_contained_input[0], new TArrayKey(), false, $not_contained_input[1]];
        }
    }

    /**
     * @psalm-return iterable<array-key, list{Scalar, TKeyOf, bool, TypeComparisonResult|null}>
     */
    private static function provideTKeyOfComparisons(): iterable
    {
        $contained_inputs = [
            new TLiteralInt(42),
            new TLiteralString('yo'),
        ];
        foreach ($contained_inputs as $contained_input) {
            $container_type = new TKeyOf(new Union([new TKeyedArray([
                42 => new Union([new TScalar()]),
                'yo' => new Union([new TScalar()]),
            ])]));
            yield [$contained_input, $container_type, true, null];
        }

        $not_contained_inputs = [
            [new TScalar(), new TypeComparisonResult(
                true,
                true,
                null,
                null,
                null,
                true,
            )],
            [new TBool(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TFloat(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TInt(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TNumeric(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TString(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TArrayKey(), new TypeComparisonResult(
                true,
                true,
                null,
                null,
                null,
                false,
            )],
        ];
        foreach ($not_contained_inputs as $not_contained_input) {
            $container_type = new TKeyOf(new Union([new TKeyedArray([
                42 => new Union([new TScalar()]),
                'yo' => new Union([new TScalar()]),
            ])]));
            yield [$not_contained_input[0], $container_type, false, $not_contained_input[1]];
        }
    }

    /**
     * @psalm-return iterable<array-key, list{Scalar, TNumeric, bool, TypeComparisonResult|null}>
     */
    private static function provideTNumericComparisons(): iterable
    {
        $contained_inputs = [
            new TNumeric(),
            new TInt(),
            new TFloat(),
            new TNumericString(),
            new TLiteralString('42'),
        ];
        foreach ($contained_inputs as $contained_input) {
            yield [$contained_input, new TNumeric(), true, null];
        }

        $not_contained_inputs = [
            [new TScalar(), new TypeComparisonResult(
                true,
                true,
                null,
                null,
                null,
                true,
            )],
            [new TBool(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TArrayKey(), new TypeComparisonResult(
                true,
                true,
                null,
                null,
                null,
                false,
            )],
            [new TString(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
        ];
        foreach ($not_contained_inputs as $not_contained_input) {
            yield [$not_contained_input[0], new TNumeric(), false, $not_contained_input[1]];
        }
    }

    /**
     * @psalm-return iterable<array-key, list{Scalar, TEmptyNumeric, bool, TypeComparisonResult|null}>
     */
    private static function provideTEmptyNumericComparisons(): iterable
    {
        $contained_inputs = [
            new TEmptyNumeric(),
            new TLiteralFloat(0.0),
            new TLiteralInt(0),
            new TLiteralString('0'),
        ];
        foreach ($contained_inputs as $contained_input) {
            yield [$contained_input, new TEmptyNumeric(), true, null];
        }

        $not_contained_inputs = [
            [new TScalar(), new TypeComparisonResult(
                true,
                true,
                null,
                null,
                null,
                true,
            )],
            [new TBool(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TArrayKey(), new TypeComparisonResult(
                true,
                true,
                null,
                null,
                null,
                false,
            )],
            [new TString(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TNumeric(), new TypeComparisonResult(
                true,
                true,
                null,
                null,
                null,
                false,
            )],
            [new TInt(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TFloat(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
        ];
        foreach ($not_contained_inputs as $not_contained_input) {
            yield [$not_contained_input[0], new TEmptyNumeric(), false, $not_contained_input[1]];
        }
    }

    /**
     * @psalm-return iterable<array-key, list{Scalar, TScalar, bool, TypeComparisonResult|null}>
     */
    private static function provideTScalarComparisons(): iterable
    {
        $contained_inputs = [
            new TScalar(),
            new TBool(),
            new TArrayKey(),
            new TInt(),
            new TFloat(),
            new TNumeric(),
            new TString(),
        ];
        foreach ($contained_inputs as $contained_input) {
            yield [$contained_input, new TScalar(), true, null];
        }
    }

    /**
     * @psalm-return iterable<array-key, list{Scalar, TEmptyScalar, bool, TypeComparisonResult|null}>
     */
    private static function provideTEmptyScalarComparisons(): iterable
    {
        $contained_inputs = [
            new TEmptyScalar(),
            new TEmptyNumeric(),
            new TFalse(),
            new TLiteralFloat(0.0),
            new TLiteralInt(0),
            new TLiteralString('0'),
        ];
        foreach ($contained_inputs as $contained_input) {
            yield [$contained_input, new TEmptyScalar(), true, null];
        }

        $not_contained_inputs = [
            [new TScalar(), new TypeComparisonResult(
                true,
                true,
                null,
                null,
                null,
                true,
            )],
            [new TBool(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TArrayKey(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TString(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TNumeric(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TInt(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TFloat(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
        ];
        foreach ($not_contained_inputs as $not_contained_input) {
            yield [$not_contained_input[0], new TEmptyScalar(), false, $not_contained_input[1]];
        }
    }

    /**
     * @psalm-return iterable<array-key, list{Scalar, TNonEmptyScalar, bool, TypeComparisonResult|null}>
     */
    private static function provideTNonEmptyScalarComparisons(): iterable
    {
        $contained_inputs = [
            new TNonEmptyScalar(),
            new TTrue(),
            new TLiteralFloat(3.14),
            new TLiteralInt(42),
            new TLiteralString('hello'),
        ];
        foreach ($contained_inputs as $contained_input) {
            yield [$contained_input, new TNonEmptyScalar(), true, null];
        }

        $not_contained_inputs = [
            [new TScalar(), new TypeComparisonResult(
                true,
                true,
                null,
                null,
                null,
                true,
            )],
            [new TBool(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TArrayKey(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TString(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TNumeric(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TInt(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TFloat(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
        ];
        foreach ($not_contained_inputs as $not_contained_input) {
            yield [$not_contained_input[0], new TNonEmptyScalar(), false, $not_contained_input[1]];
        }
    }

    /**
     * @psalm-return iterable<array-key, list{Scalar, TBool, bool, TypeComparisonResult|null}>
     */
    private static function provideTBoolComparisons(): iterable
    {
        $contained_inputs = [
            new TBool(),
            new TTrue(),
            new TFalse(),
        ];
        foreach ($contained_inputs as $contained_input) {
            yield [$contained_input, new TBool(), true, null];
        }

        $not_contained_inputs = [
            [new TScalar(), new TypeComparisonResult(
                true,
                true,
                null,
                null,
                null,
                true,
            )],
            [new TArrayKey(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TString(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TNumeric(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TInt(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TFloat(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
        ];
        foreach ($not_contained_inputs as $not_contained_input) {
            yield [$not_contained_input[0], new TBool(), false, $not_contained_input[1]];
        }
    }

    /**
     * @psalm-return iterable<array-key, list{Scalar, TFalse, bool, TypeComparisonResult|null}>
     */
    private static function provideTFalseComparisons(): iterable
    {
        $contained_inputs = [
            new TFalse(),
        ];
        foreach ($contained_inputs as $contained_input) {
            yield [$contained_input, new TFalse(), true, null];
        }

        $not_contained_inputs = [
            [new TScalar(), new TypeComparisonResult(
                true,
                true,
                null,
                null,
                null,
                true,
            )],
            [new TArrayKey(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TString(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TNumeric(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TInt(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TFloat(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TBool(), new TypeComparisonResult(
                true,
                true,
                null,
                null,
                null,
                false,
            )],
            [new TTrue(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
        ];
        foreach ($not_contained_inputs as $not_contained_input) {
            yield [$not_contained_input[0], new TFalse(), false, $not_contained_input[1]];
        }
    }

    /**
     * @psalm-return iterable<array-key, list{Scalar, TTrue, bool, TypeComparisonResult|null}>
     */
    private static function provideTTrueComparisons(): iterable
    {
        $contained_inputs = [
            new TTrue(),
        ];
        foreach ($contained_inputs as $contained_input) {
            yield [$contained_input, new TTrue(), true, null];
        }

        $not_contained_inputs = [
            [new TScalar(), new TypeComparisonResult(
                true,
                true,
                null,
                null,
                null,
                true,
            )],
            [new TArrayKey(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TString(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TNumeric(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TInt(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TFloat(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TBool(), new TypeComparisonResult(
                true,
                true,
                null,
                null,
                null,
                false,
            )],
            [new TFalse(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
        ];
        foreach ($not_contained_inputs as $not_contained_input) {
            yield [$not_contained_input[0], new TTrue(), false, $not_contained_input[1]];
        }
    }

    /**
     * @psalm-return iterable<array-key, list{Scalar, TFloat, bool, TypeComparisonResult|null, bool}>
     */
    private static function provideTFloatComparisons(): iterable
    {
        $contained_inputs = [
            new TFloat(),
            new TLiteralFloat(3.14),
        ];
        foreach ($contained_inputs as $contained_input) {
            yield [$contained_input, new TFloat(), true, null, false];
        }
        yield [new TInt(), new TFloat(), true, null, true];

        $not_contained_inputs = [
            [new TScalar(), new TypeComparisonResult(
                true,
                true,
                null,
                null,
                null,
                true,
            )],
            [new TArrayKey(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TString(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TNumeric(), new TypeComparisonResult(
                true,
                true,
                null,
                null,
                null,
                false,
            )],
            [new TInt(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TBool(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
        ];
        foreach ($not_contained_inputs as $not_contained_input) {
            yield [$not_contained_input[0], new TFloat(), false, $not_contained_input[1], false];
        }
    }

    /**
     * @psalm-return iterable<array-key, list{Scalar, TLiteralFloat, bool, TypeComparisonResult|null, bool}>
     */
    private static function provideTLiteralFloatComparisons(): iterable
    {
        $contained_inputs = [
            new TLiteralFloat(3.14),
        ];
        foreach ($contained_inputs as $contained_input) {
            yield [$contained_input, new TLiteralFloat(3.14), true, null, false];
        }
        yield [new TLiteralInt(3), new TLiteralFloat(3.0), true, null, true];

        $not_contained_inputs = [
            [new TScalar(), new TypeComparisonResult(
                true,
                true,
                null,
                null,
                null,
                true,
            )],
            [new TArrayKey(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TString(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TNumeric(), new TypeComparisonResult(
                true,
                true,
                null,
                null,
                null,
                false,
            )],
            [new TInt(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TBool(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TFloat(), new TypeComparisonResult(
                true,
                true,
                null,
                null,
                null,
                false,
            )],
            [new TLiteralFloat(1.41), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
        ];
        foreach ($not_contained_inputs as $not_contained_input) {
            yield [$not_contained_input[0], new TLiteralFloat(3.14), false, $not_contained_input[1], false];
        }
    }

    /**
     * @psalm-return iterable<array-key, list{Scalar, TInt, bool, TypeComparisonResult|null}>
     */
    private static function provideTIntComparisons(): iterable
    {
        $contained_inputs = [
            new TInt(),
            //new TDependentListKey(),
            new TLiteralInt(42),
            new TIntRange(3, 9),
            new TNonspecificLiteralInt(),
            //new TIntMask(),
            //new TIntMaskOf(),
        ];
        foreach ($contained_inputs as $contained_input) {
            yield [$contained_input, new TInt(), true, null];
        }

        $not_contained_inputs = [
            [new TScalar(), new TypeComparisonResult(
                true,
                true,
                null,
                null,
                null,
                true,
            )],
            [new TArrayKey(), new TypeComparisonResult(
                true,
                true,
                true,
                null,
                null,
                null,
            )],
            [new TString(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TNumeric(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TBool(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
            [new TFloat(), new TypeComparisonResult(
                true,
                false,
                null,
                null,
                null,
                false,
            )],
        ];
        foreach ($not_contained_inputs as $not_contained_input) {
            yield [$not_contained_input[0], new TInt(), false, $not_contained_input[1]];
        }
    }
}
