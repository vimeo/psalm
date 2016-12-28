<?php
namespace Psalm\Checker\Statements\Expression;

use PhpParser;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\TypeChecker;
use Psalm\Clause;
use Psalm\CodeLocation;
use Psalm\Issue\FailedTypeResolution;
use Psalm\Issue\TypeDoesNotContainType;
use Psalm\IssueBuffer;
use Psalm\Type;

class AssertionChecker
{
    const ASSIGNMENT_TO_RIGHT = 1;
    const ASSIGNMENT_TO_LEFT = -1;

    /**
     * Gets all the type assertions in a conditional
     *
     * @param  PhpParser\Node\Expr      $conditional
     * @param  string                   $this_class_name
     * @param  string                   $namespace
     * @param  array<string, string>    $aliased_classes
     * @param  bool                 $allow_non_negatable Allow type assertions that should not be negated
     * @return array<string, string>
     */
    public static function getAssertions(
        PhpParser\Node\Expr $conditional,
        $this_class_name,
        $namespace,
        array $aliased_classes,
        $allow_non_negatable = false
    ) {
        $if_types = [];

        if ($conditional instanceof PhpParser\Node\Expr\Instanceof_) {
            $instanceof_type = self::getInstanceOfTypes($conditional, $this_class_name, $namespace, $aliased_classes);

            if ($instanceof_type) {
                $var_name = ExpressionChecker::getArrayVarId(
                    $conditional->expr,
                    $this_class_name,
                    $namespace,
                    $aliased_classes
                );

                if ($var_name) {
                    $if_types[$var_name] = $instanceof_type;
                }
            }

            return $if_types;
        }

        if ($var_name = ExpressionChecker::getArrayVarId(
            $conditional,
            $this_class_name,
            $namespace,
            $aliased_classes
        )) {
            $if_types[$var_name] = '!empty';

            return $if_types;
        }

        if ($conditional instanceof PhpParser\Node\Expr\Assign) {
            $var_name = ExpressionChecker::getArrayVarId(
                $conditional->var,
                $this_class_name,
                $namespace,
                $aliased_classes
            );

            if ($var_name) {
                $if_types[$var_name] = '!empty';
            }

            return $if_types;
        }

        if ($conditional instanceof PhpParser\Node\Expr\BooleanNot) {
            $if_types_to_negate = self::getAssertions(
                $conditional->expr,
                $this_class_name,
                $namespace,
                $aliased_classes,
                false
            );

            return TypeChecker::negateTypes($if_types_to_negate);
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical ||
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
        ) {
            $null_position = self::hasNullVariable($conditional);
            $false_position = self::hasFalseVariable($conditional);
            $gettype_position = self::hasGetTypeCheck($conditional);
            $scalar_value_position = $allow_non_negatable ? self::hasScalarValueComparison($conditional) : false;

            $var_name = null;

            if ($null_position !== null) {
                if ($null_position === self::ASSIGNMENT_TO_RIGHT) {
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->left,
                        $this_class_name,
                        $namespace,
                        $aliased_classes
                    );
                } elseif ($null_position === self::ASSIGNMENT_TO_LEFT) {
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->right,
                        $this_class_name,
                        $namespace,
                        $aliased_classes
                    );
                } else {
                    throw new \InvalidArgumentException('Bad null variable position');
                }

                if ($var_name) {
                    if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                        $if_types[$var_name] = 'null';
                    } else {
                        $if_types[$var_name] = 'empty';
                    }
                }

                return $if_types;
            }

            if ($false_position) {
                if ($false_position === self::ASSIGNMENT_TO_RIGHT) {
                    if ($conditional->left instanceof PhpParser\Node\Expr\FuncCall) {
                        self::processFunctionCall(
                            $conditional->left,
                            $if_types,
                            $this_class_name,
                            $namespace,
                            $aliased_classes,
                            true
                        );
                    } else {
                        $var_name = ExpressionChecker::getArrayVarId(
                            $conditional->left,
                            $this_class_name,
                            $namespace,
                            $aliased_classes
                        );
                    }
                } elseif ($false_position === self::ASSIGNMENT_TO_LEFT) {
                    if ($conditional->right instanceof PhpParser\Node\Expr\FuncCall) {
                        self::processFunctionCall(
                            $conditional->right,
                            $if_types,
                            $this_class_name,
                            $namespace,
                            $aliased_classes,
                            true
                        );
                    } else {
                        $var_name = ExpressionChecker::getArrayVarId(
                            $conditional->right,
                            $this_class_name,
                            $namespace,
                            $aliased_classes
                        );
                    }
                } else {
                    throw new \InvalidArgumentException('Bad null variable position');
                }

                if ($var_name) {
                    if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                        $if_types[$var_name] = 'false';
                    } else {
                        $if_types[$var_name] = 'empty';
                    }
                }

                return $if_types;
            }

            if ($gettype_position) {
                $var_type = null;

                if ($gettype_position === self::ASSIGNMENT_TO_RIGHT) {
                    /** @var PhpParser\Node\Expr\FuncCall $conditional->right */
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->right->args[0]->value,
                        $this_class_name,
                        $namespace,
                        $aliased_classes
                    );

                    /** @var PhpParser\Node\Scalar\String_ $conditional->left */
                    $var_type = $conditional->left->value;
                } elseif ($gettype_position === self::ASSIGNMENT_TO_LEFT) {
                    /** @var PhpParser\Node\Expr\FuncCall $conditional->left */
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->left->args[0]->value,
                        $this_class_name,
                        $namespace,
                        $aliased_classes
                    );

                    /** @var PhpParser\Node\Scalar\String_ $conditional->right */
                    $var_type = $conditional->right->value;
                }

                if ($var_name && $var_type) {
                    $if_types[$var_name] = $var_type;
                }

                return $if_types;
            }

            if ($scalar_value_position) {
                $var_type = null;

                if ($scalar_value_position === self::ASSIGNMENT_TO_RIGHT) {
                    /** @var PhpParser\Node\Expr $conditional->right */
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->left,
                        $this_class_name,
                        $namespace,
                        $aliased_classes
                    );

                    if ($conditional->right instanceof PhpParser\Node\Scalar\String_) {
                        $var_type = 'string';
                    } elseif ($conditional->right instanceof PhpParser\Node\Scalar\LNumber) {
                        $var_type = 'int';
                    } elseif ($conditional->right instanceof PhpParser\Node\Scalar\DNumber) {
                        $var_type = 'float';
                    }
                } elseif ($scalar_value_position === self::ASSIGNMENT_TO_LEFT) {
                    /** @var PhpParser\Node\Expr $conditional->left */
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->right,
                        $this_class_name,
                        $namespace,
                        $aliased_classes
                    );

                    if ($conditional->left instanceof PhpParser\Node\Scalar\String_) {
                        $var_type = 'string';
                    } elseif ($conditional->left instanceof PhpParser\Node\Scalar\LNumber) {
                        $var_type = 'int';
                    } elseif ($conditional->left instanceof PhpParser\Node\Scalar\DNumber) {
                        $var_type = 'float';
                    }
                }

                if ($var_name && $var_type) {
                    $if_types[$var_name] = $var_type;
                }

                return $if_types;
            }

            return [];
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical ||
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
        ) {
            $null_position = self::hasNullVariable($conditional);
            $false_position = self::hasFalseVariable($conditional);
            $true_position = self::hasTrueVariable($conditional);

            if ($null_position !== null) {
                if ($null_position === self::ASSIGNMENT_TO_RIGHT) {
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->left,
                        $this_class_name,
                        $namespace,
                        $aliased_classes
                    );
                } elseif ($null_position === self::ASSIGNMENT_TO_LEFT) {
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->right,
                        $this_class_name,
                        $namespace,
                        $aliased_classes
                    );
                } else {
                    throw new \InvalidArgumentException('Bad null variable position');
                }

                if ($var_name) {
                    if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical) {
                        $if_types[$var_name] = '!null';
                    } else {
                        $if_types[$var_name] = '!empty';
                    }
                }

                return $if_types;
            }

            if ($false_position) {
                if ($false_position === self::ASSIGNMENT_TO_RIGHT) {
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->left,
                        $this_class_name,
                        $namespace,
                        $aliased_classes
                    );
                } elseif ($false_position === self::ASSIGNMENT_TO_LEFT) {
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->right,
                        $this_class_name,
                        $namespace,
                        $aliased_classes
                    );
                } else {
                    throw new \InvalidArgumentException('Bad null variable position');
                }

                if ($var_name) {
                    if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical) {
                        $if_types[$var_name] = '!false';
                    } else {
                        $if_types[$var_name] = '!empty';
                    }
                }

                return $if_types;
            }

            if ($true_position) {
                if ($true_position === self::ASSIGNMENT_TO_RIGHT) {
                    if ($conditional->left instanceof PhpParser\Node\Expr\FuncCall) {
                        self::processFunctionCall(
                            $conditional->left,
                            $if_types,
                            $this_class_name,
                            $namespace,
                            $aliased_classes,
                            true
                        );
                    }
                } elseif ($true_position === self::ASSIGNMENT_TO_LEFT) {
                    if ($conditional->right instanceof PhpParser\Node\Expr\FuncCall) {
                        self::processFunctionCall(
                            $conditional->right,
                            $if_types,
                            $this_class_name,
                            $namespace,
                            $aliased_classes,
                            true
                        );
                    }
                } else {
                    throw new \InvalidArgumentException('Bad null variable position');
                }

                return $if_types;
            }

            return [];
        }

        if ($conditional instanceof PhpParser\Node\Expr\FuncCall) {
            self::processFunctionCall($conditional, $if_types, $this_class_name, $namespace, $aliased_classes, false);

            return $if_types;
        }

        if ($conditional instanceof PhpParser\Node\Expr\Empty_) {
            $var_name = ExpressionChecker::getArrayVarId(
                $conditional->expr,
                $this_class_name,
                $namespace,
                $aliased_classes
            );

            if ($var_name) {
                $if_types[$var_name] = 'empty';
            }

            return $if_types;
        }

        if ($conditional instanceof PhpParser\Node\Expr\Isset_) {
            foreach ($conditional->vars as $isset_var) {
                $var_name = ExpressionChecker::getArrayVarId(
                    $isset_var,
                    $this_class_name,
                    $namespace,
                    $aliased_classes
                );

                if ($var_name) {
                    $if_types[$var_name] = '!isset';
                }
            }

            return $if_types;
        }

        return [];
    }

    /**
     * @param  PhpParser\Node\Expr\FuncCall $expr
     * @param  array<string>                &$if_types
     * @param  string                       $this_class_name
     * @param  string                       $namespace
     * @param  array<string, string>        $aliased_classes
     * @param  boolean                      $negate
     * @return void
     */
    protected static function processFunctionCall(
        PhpParser\Node\Expr\FuncCall $expr,
        array &$if_types,
        $this_class_name,
        $namespace,
        array $aliased_classes,
        $negate = false
    ) {
        $prefix = $negate ? '!' : '';

        $first_var_name = isset($expr->args[0]->value)
            ? ExpressionChecker::getArrayVarId(
                $expr->args[0]->value,
                $this_class_name,
                $namespace,
                $aliased_classes
            )
            : null;

        if (self::hasNullCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'null';
            }
        } elseif (self::hasIsACheck($expr)) {
            if ($first_var_name) {
                /** @var PhpParser\Node\Scalar\String_ */
                $is_a_type = $expr->args[1]->value;
                $if_types[$first_var_name] = $prefix . $is_a_type->value;
            }
        } elseif (self::hasArrayCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'array';
            }
        } elseif (self::hasBoolCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'bool';
            }
        } elseif (self::hasStringCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'string';
            }
        } elseif (self::hasObjectCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'object';
            }
        } elseif (self::hasNumericCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'numeric';
            }
        } elseif (self::hasIntCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'int';
            }
        } elseif (self::hasFloatCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'float';
            }
        } elseif (self::hasResourceCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'resource';
            }
        } elseif (self::hasScalarCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'scalar';
            }
        } elseif (self::hasCallableCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'callable';
            }
        }
    }

    /**
     * @param  PhpParser\Node\Expr\Instanceof_ $stmt
     * @param  string                          $this_class_name
     * @param  string                          $namespace
     * @param  array<string, string>           $aliased_classes
     * @return string|null
     */
    protected static function getInstanceOfTypes(
        PhpParser\Node\Expr\Instanceof_ $stmt,
        $this_class_name,
        $namespace,
        array $aliased_classes
    ) {
        if ($stmt->class instanceof PhpParser\Node\Name) {
            if (!in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
                $instanceof_class = ClassLikeChecker::getFQCLNFromNameObject(
                    $stmt->class,
                    $namespace,
                    $aliased_classes
                );

                return $instanceof_class;
            } elseif ($stmt->class->parts === ['self']) {
                return $this_class_name;
            }
        }

        return null;
    }

    /**
     * @param   PhpParser\Node\Expr\BinaryOp    $conditional
     * @return  int|null
     */
    protected static function hasNullVariable(PhpParser\Node\Expr\BinaryOp $conditional)
    {
        if ($conditional->right instanceof PhpParser\Node\Expr\ConstFetch &&
            $conditional->right->name instanceof PhpParser\Node\Name &&
            strtolower($conditional->right->name->parts[0]) === 'null') {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\ConstFetch &&
            $conditional->left->name instanceof PhpParser\Node\Name &&
            strtolower($conditional->left->name->parts[0]) === 'null') {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return null;
    }

    /**
     * @param   PhpParser\Node\Expr\BinaryOp    $conditional
     * @return  int|null
     */
    protected static function hasFalseVariable(PhpParser\Node\Expr\BinaryOp $conditional)
    {
        if ($conditional->right instanceof PhpParser\Node\Expr\ConstFetch &&
            $conditional->right->name instanceof PhpParser\Node\Name &&
            strtolower($conditional->right->name->parts[0]) === 'false') {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\ConstFetch &&
            $conditional->left->name instanceof PhpParser\Node\Name &&
            strtolower($conditional->left->name->parts[0]) === 'false') {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return null;
    }

    /**
     * @param   PhpParser\Node\Expr\BinaryOp    $conditional
     * @return  int|null
     */
    protected static function hasTrueVariable(PhpParser\Node\Expr\BinaryOp $conditional)
    {
        if ($conditional->right instanceof PhpParser\Node\Expr\ConstFetch &&
            $conditional->right->name instanceof PhpParser\Node\Name &&
            strtolower($conditional->right->name->parts[0]) === 'true') {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\ConstFetch &&
            $conditional->left->name instanceof PhpParser\Node\Name &&
            strtolower($conditional->left->name->parts[0]) === 'true') {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return null;
    }

    /**
     * @param   PhpParser\Node\Expr\BinaryOp    $conditional
     * @return  false|int
     */
    protected static function hasGetTypeCheck(PhpParser\Node\Expr\BinaryOp $conditional)
    {
        if ($conditional->right instanceof PhpParser\Node\Expr\FuncCall &&
            $conditional->right->name instanceof PhpParser\Node\Name &&
            strtolower($conditional->right->name->parts[0]) === 'gettype' &&
            $conditional->left instanceof PhpParser\Node\Scalar\String_) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\FuncCall &&
            $conditional->left->name instanceof PhpParser\Node\Name &&
            strtolower($conditional->left->name->parts[0]) === 'gettype' &&
            $conditional->right instanceof PhpParser\Node\Scalar\String_) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\BinaryOp    $conditional
     * @return  false|int
     */
    protected static function hasScalarValueComparison(PhpParser\Node\Expr\BinaryOp $conditional)
    {
        if (!$conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
            return false;
        }

        if ($conditional->right instanceof PhpParser\Node\Scalar) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Scalar) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @return  bool
     */
    protected static function hasNullCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'is_null') {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @return  bool
     */
    protected static function hasIsACheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'is_a' &&
            $stmt->args[1]->value instanceof PhpParser\Node\Scalar\String_) {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @return  bool
     */
    protected static function hasArrayCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'is_array') {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @return  bool
     */
    protected static function hasStringCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'is_string') {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @return  bool
     */
    protected static function hasBoolCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'is_bool') {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @return  bool
     */
    protected static function hasObjectCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_object']) {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @return  bool
     */
    protected static function hasNumericCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_numeric']) {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @return  bool
     */
    protected static function hasIntCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name &&
            ($stmt->name->parts === ['is_int'] ||
                $stmt->name->parts === ['is_integer']||
                $stmt->name->parts === ['is_long'])
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @return  bool
     */
    protected static function hasFloatCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name &&
            ($stmt->name->parts === ['is_float'] ||
                $stmt->name->parts === ['is_real'] ||
                $stmt->name->parts === ['is_double'])
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @return  bool
     */
    protected static function hasResourceCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_resource']) {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @return  bool
     */
    protected static function hasScalarCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_scalar']) {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @return  bool
     */
    protected static function hasCallableCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_callable']) {
            return true;
        }

        return false;
    }
}
