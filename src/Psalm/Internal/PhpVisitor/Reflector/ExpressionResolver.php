<?php
namespace Psalm\Internal\PhpVisitor\Reflector;

use function class_exists;
use function function_exists;
use function implode;
use function interface_exists;
use PhpParser;
use PhpParser\ConstExprEvaluationException;
use PhpParser\ConstExprEvaluator;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ConstFetch;
use Psalm\Aliases;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Scanner\UnresolvedConstant;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use function assert;
use function strtolower;

class ExpressionResolver
{
    public static function getUnresolvedClassConstExpr(
        PhpParser\Node\Expr $stmt,
        Aliases $aliases,
        ?string $fq_classlike_name,
        ?string $parent_fq_class_name = null
    ) : ?UnresolvedConstantComponent {
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            $left = self::getUnresolvedClassConstExpr(
                $stmt->left,
                $aliases,
                $fq_classlike_name,
                $parent_fq_class_name
            );

            $right = self::getUnresolvedClassConstExpr(
                $stmt->right,
                $aliases,
                $fq_classlike_name,
                $parent_fq_class_name
            );

            if (!$left || !$right) {
                return null;
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Plus) {
                return new UnresolvedConstant\UnresolvedAdditionOp($left, $right);
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Minus) {
                return new UnresolvedConstant\UnresolvedSubtractionOp($left, $right);
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Mul) {
                return new UnresolvedConstant\UnresolvedMultiplicationOp($left, $right);
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Div) {
                return new UnresolvedConstant\UnresolvedDivisionOp($left, $right);
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
                return new UnresolvedConstant\UnresolvedConcatOp($left, $right);
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseOr) {
                return new UnresolvedConstant\UnresolvedBitwiseOr($left, $right);
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseXor) {
                return new UnresolvedConstant\UnresolvedBitwiseXor($left, $right);
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseAnd) {
                return new UnresolvedConstant\UnresolvedBitwiseAnd($left, $right);
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\Ternary) {
            $cond = self::getUnresolvedClassConstExpr(
                $stmt->cond,
                $aliases,
                $fq_classlike_name,
                $parent_fq_class_name
            );

            $if = null;

            if ($stmt->if) {
                $if = self::getUnresolvedClassConstExpr(
                    $stmt->if,
                    $aliases,
                    $fq_classlike_name,
                    $parent_fq_class_name
                );

                if ($if === null) {
                    $if = false;
                }
            }

            $else = self::getUnresolvedClassConstExpr(
                $stmt->else,
                $aliases,
                $fq_classlike_name,
                $parent_fq_class_name
            );

            if ($cond && $else && $if !== false) {
                return new UnresolvedConstant\UnresolvedTernary($cond, $if, $else);
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\ConstFetch) {
            if (strtolower($stmt->name->parts[0]) === 'false') {
                return new UnresolvedConstant\ScalarValue(false);
            } elseif (strtolower($stmt->name->parts[0]) === 'true') {
                return new UnresolvedConstant\ScalarValue(true);
            } elseif (strtolower($stmt->name->parts[0]) === 'null') {
                return new UnresolvedConstant\ScalarValue(null);
            } elseif ($stmt->name->parts[0] === '__NAMESPACE__') {
                return new UnresolvedConstant\ScalarValue($aliases->namespace);
            }

            return new UnresolvedConstant\Constant(
                implode('\\', $stmt->name->parts),
                $stmt->name instanceof PhpParser\Node\Name\FullyQualified
            );
        }

        if ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Namespace_) {
            return new UnresolvedConstant\ScalarValue($aliases->namespace);
        }

        if ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch && $stmt->dim) {
            $left = self::getUnresolvedClassConstExpr(
                $stmt->var,
                $aliases,
                $fq_classlike_name,
                $parent_fq_class_name
            );

            $right = self::getUnresolvedClassConstExpr(
                $stmt->dim,
                $aliases,
                $fq_classlike_name,
                $parent_fq_class_name
            );

            if ($left && $right) {
                return new UnresolvedConstant\ArrayOffsetFetch($left, $right);
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\ClassConstFetch) {
            if ($stmt->class instanceof PhpParser\Node\Name
                && $stmt->name instanceof PhpParser\Node\Identifier
                && $fq_classlike_name
                && $stmt->class->parts !== ['static']
                && ($stmt->class->parts !== ['parent'] || $parent_fq_class_name !== null)
            ) {
                if ($stmt->class->parts === ['self']) {
                    $const_fq_class_name = $fq_classlike_name;
                } else {
                    if ($stmt->class->parts === ['parent']) {
                        assert($parent_fq_class_name !== null);
                        $const_fq_class_name = $parent_fq_class_name;
                    } else {
                        $const_fq_class_name = ClassLikeAnalyzer::getFQCLNFromNameObject(
                            $stmt->class,
                            $aliases
                        );
                    }
                }

                return new UnresolvedConstant\ClassConstant($const_fq_class_name, $stmt->name->name);
            }

            return null;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\String_
            || $stmt instanceof PhpParser\Node\Scalar\LNumber
            || $stmt instanceof PhpParser\Node\Scalar\DNumber
        ) {
            return new UnresolvedConstant\ScalarValue($stmt->value);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Array_) {
            $items = [];

            foreach ($stmt->items as $item) {
                if ($item === null) {
                    return null;
                }

                if ($item->key) {
                    $item_key_type = self::getUnresolvedClassConstExpr(
                        $item->key,
                        $aliases,
                        $fq_classlike_name,
                        $parent_fq_class_name
                    );

                    if (!$item_key_type) {
                        return null;
                    }
                } else {
                    $item_key_type = null;
                }

                $item_value_type = self::getUnresolvedClassConstExpr(
                    $item->value,
                    $aliases,
                    $fq_classlike_name,
                    $parent_fq_class_name
                );

                if (!$item_value_type) {
                    return null;
                }

                if ($item->unpack) {
                    $items[] = new UnresolvedConstant\ArraySpread($item_value_type);
                } else {
                    $items[] = new UnresolvedConstant\KeyValuePair($item_key_type, $item_value_type);
                }
            }

            return new UnresolvedConstant\ArrayValue($items);
        }

        return null;
    }

    public static function enterConditional(
        Codebase $codebase,
        string $file_path,
        PhpParser\Node\Expr $expr
    ) : ?bool {
        if ($expr instanceof PhpParser\Node\Expr\BooleanNot) {
            $enter_negated = self::enterConditional($codebase, $file_path, $expr->expr);

            return $enter_negated === null ? null : !$enter_negated;
        }

        if ($expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd) {
            $enter_conditional_left = self::enterConditional($codebase, $file_path, $expr->left);
            $enter_conditional_right = self::enterConditional($codebase, $file_path, $expr->right);

            return $enter_conditional_left !== false && $enter_conditional_right !== false;
        }

        if ($expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr) {
            $enter_conditional_left = self::enterConditional($codebase, $file_path, $expr->left);
            $enter_conditional_right = self::enterConditional($codebase, $file_path, $expr->right);

            return $enter_conditional_left !== false || $enter_conditional_right !== false;
        }

        if ($codebase->register_autoload_files) {
            if ((
                    $expr instanceof PhpParser\Node\Expr\BinaryOp\GreaterOrEqual
                    || $expr instanceof PhpParser\Node\Expr\BinaryOp\Greater
                    || $expr instanceof PhpParser\Node\Expr\BinaryOp\SmallerOrEqual
                    || $expr instanceof PhpParser\Node\Expr\BinaryOp\Smaller
                ) && (
                    (
                        $expr->left instanceof PhpParser\Node\Expr\ConstFetch
                        && $expr->left->name->parts === ['PHP_VERSION_ID']
                        && $expr->right instanceof PhpParser\Node\Scalar\LNumber
                    ) || (
                        $expr->right instanceof PhpParser\Node\Expr\ConstFetch
                        && $expr->right->name->parts === ['PHP_VERSION_ID']
                        && $expr->left instanceof PhpParser\Node\Scalar\LNumber
                    )
                )
            ) {
                $php_version_id = $codebase->php_major_version * 10000 + $codebase->php_minor_version * 100;
                $evaluator = new ConstExprEvaluator(function (Expr $expr) use ($php_version_id) {
                    if ($expr instanceof ConstFetch && $expr->name->parts === ['PHP_VERSION_ID']) {
                        return $php_version_id;
                    }
                    throw new ConstExprEvaluationException('unexpected');
                });
                try {
                    return (bool) $evaluator->evaluateSilently($expr);
                } catch (ConstExprEvaluationException $e) {
                    return null;
                }
            }
        }

        if (!$expr instanceof PhpParser\Node\Expr\FuncCall) {
            return null;
        }

        return self::functionEvaluatesToTrue($codebase, $file_path, $expr);
    }

    private static function functionEvaluatesToTrue(
        Codebase $codebase,
        string $file_path,
        PhpParser\Node\Expr\FuncCall $function
    ) : ?bool {
        if (!$function->name instanceof PhpParser\Node\Name) {
            return null;
        }

        if ($function->name->parts === ['function_exists']
            && isset($function->args[0])
            && $function->args[0]->value instanceof PhpParser\Node\Scalar\String_
            && function_exists($function->args[0]->value->value)
        ) {
            $reflection_function = new \ReflectionFunction($function->args[0]->value->value);

            if ($reflection_function->isInternal()) {
                return true;
            }

            return false;
        }

        if ($function->name->parts === ['class_exists']
            && isset($function->args[0])
        ) {
            $string_value = null;

            if ($function->args[0]->value instanceof PhpParser\Node\Scalar\String_) {
                $string_value = $function->args[0]->value->value;
            } elseif ($function->args[0]->value instanceof PhpParser\Node\Expr\ClassConstFetch
                && $function->args[0]->value->class instanceof PhpParser\Node\Name
                && $function->args[0]->value->name instanceof PhpParser\Node\Identifier
                && strtolower($function->args[0]->value->name->name) === 'class'
            ) {
                $string_value = (string) $function->args[0]->value->class->getAttribute('resolvedName');
            }

            if ($string_value && class_exists($string_value)) {
                $reflection_class = new \ReflectionClass($string_value);

                if ($reflection_class->getFileName() !== $file_path) {
                    $codebase->scanner->queueClassLikeForScanning(
                        $string_value
                    );

                    return true;
                }
            }

            return false;
        }

        if ($function->name->parts === ['interface_exists']
            && isset($function->args[0])
        ) {
            $string_value = null;

            if ($function->args[0]->value instanceof PhpParser\Node\Scalar\String_) {
                $string_value = $function->args[0]->value->value;
            } elseif ($function->args[0]->value instanceof PhpParser\Node\Expr\ClassConstFetch
                && $function->args[0]->value->class instanceof PhpParser\Node\Name
                && $function->args[0]->value->name instanceof PhpParser\Node\Identifier
                && strtolower($function->args[0]->value->name->name) === 'class'
            ) {
                $string_value = (string) $function->args[0]->value->class->getAttribute('resolvedName');
            }

            if ($string_value && interface_exists($string_value)) {
                $reflection_class = new \ReflectionClass($string_value);

                if ($reflection_class->getFileName() !== $file_path) {
                    $codebase->scanner->queueClassLikeForScanning(
                        $string_value
                    );

                    return true;
                }
            }

            return false;
        }

        return null;
    }
}
