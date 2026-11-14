<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\BinaryOp;

use PhpParser;
use Psalm\Codebase;

use function array_filter;
use function array_intersect_key;
use function in_array;
use function strtolower;

use const ARRAY_FILTER_USE_BOTH;

/**
 * @internal
 */
final class GuardedClasses
{
    private const FUNCTIONS = ['class_exists', 'interface_exists', 'enum_exists'];

    /**
     * Those of the classes that exist when the expression evaluates to the given result that Psalm doesn't know,
     * which are the ones that are not to be reported where they are used
     *
     * @return array<lowercase-string, true>
     */
    public static function unknownWhenResult(PhpParser\Node\Expr $expr, bool $result, Codebase $codebase): array
    {
        return array_filter(
            self::whenResult($expr, $result),
            static fn(bool $_, string $class_name): bool => !$codebase->classOrInterfaceOrEnumExists($class_name),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * The classes of the checks that whenResult() reads in the expression, whatever its result
     *
     * @return array<lowercase-string, true>
     */
    public static function checkedIn(PhpParser\Node\Expr $expr): array
    {
        if ($expr instanceof PhpParser\Node\Expr\BooleanNot) {
            return self::checkedIn($expr->expr);
        }

        if ($expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
            || $expr instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
            || $expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
            || $expr instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
        ) {
            return [...self::checkedIn($expr->left), ...self::checkedIn($expr->right)];
        }

        return [...self::whenResult($expr, true), ...self::whenResult($expr, false)];
    }

    /**
     * The classes that exist when the expression evaluates to the given result, because of the
     * class_exists(), interface_exists() and enum_exists() checks it is made of
     *
     * @return array<lowercase-string, true>
     */
    public static function whenResult(PhpParser\Node\Expr $expr, bool $result): array
    {
        if ($expr instanceof PhpParser\Node\Expr\BooleanNot) {
            return self::whenResult($expr->expr, !$result);
        }

        if ($expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
            || $expr instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
            || $expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
            || $expr instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
        ) {
            $is_and = $expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                || $expr instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd;
            $left = self::whenResult($expr->left, $result);
            $right = self::whenResult($expr->right, $result);

            // `a && b` is true when both are, false when either is; `a || b` is the other way round
            return $is_and === $result ? [...$left, ...$right] : array_intersect_key($left, $right);
        }

        // `class_exists(A::class) === true`, `false !== class_exists(A::class)`: the functions return a bool
        if ($expr instanceof PhpParser\Node\Expr\BinaryOp\Identical
            || $expr instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical
            || $expr instanceof PhpParser\Node\Expr\BinaryOp\Equal
            || $expr instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
        ) {
            $is_equal = $expr instanceof PhpParser\Node\Expr\BinaryOp\Identical
                || $expr instanceof PhpParser\Node\Expr\BinaryOp\Equal;

            foreach ([[$expr->left, $expr->right], [$expr->right, $expr->left]] as [$operand, $other]) {
                if ($other instanceof PhpParser\Node\Expr\ConstFetch
                    && in_array($other->name->toLowerString(), ['true', 'false'], true)
                ) {
                    $compared_with_true = $other->name->toLowerString() === 'true';

                    return self::whenResult($operand, $result === ($is_equal === $compared_with_true));
                }
            }

            return [];
        }

        if ($result
            && $expr instanceof PhpParser\Node\Expr\FuncCall
            && $expr->name instanceof PhpParser\Node\Name
            && in_array(strtolower($expr->name->getFirst()), self::FUNCTIONS, true)
            && isset($expr->getArgs()[0])
        ) {
            $arg = $expr->getArgs()[0]->value;

            if ($arg instanceof PhpParser\Node\Scalar\String_) {
                return [strtolower($arg->value) => true];
            }

            if ($arg instanceof PhpParser\Node\Expr\ClassConstFetch
                && $arg->class instanceof PhpParser\Node\Name
                && $arg->name instanceof PhpParser\Node\Identifier
                && $arg->name->name === 'class'
            ) {
                return [strtolower((string) $arg->class->getAttribute('resolvedName')) => true];
            }
        }

        return [];
    }
}
