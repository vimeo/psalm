<?php
namespace Psalm\Checker\Statements\Expression;

use PhpParser;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\TypeChecker;
use Psalm\CodeLocation;
use Psalm\Issue\TypeDoesNotContainNull;
use Psalm\Issue\TypeDoesNotContainType;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Type;

class AssertionFinder
{
    const ASSIGNMENT_TO_RIGHT = 1;
    const ASSIGNMENT_TO_LEFT = -1;

    /**
     * Gets all the type assertions in a conditional
     *
     * @param  PhpParser\Node\Expr      $conditional
     * @param  string|null              $this_class_name
     * @param  StatementsSource         $source
     *
     * @return array<string, string>
     * @psalm-suppress MoreSpecificReturnType
     */
    public static function getAssertions(
        PhpParser\Node\Expr $conditional,
        $this_class_name,
        StatementsSource $source
    ) {
        $if_types = [];

        if ($conditional instanceof PhpParser\Node\Expr\Instanceof_) {
            $instanceof_type = self::getInstanceOfTypes($conditional, $this_class_name, $source);

            if ($instanceof_type) {
                $var_name = ExpressionChecker::getArrayVarId(
                    $conditional->expr,
                    $this_class_name,
                    $source
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
            $source
        )) {
            $if_types[$var_name] = '!empty';

            return $if_types;
        }

        if ($conditional instanceof PhpParser\Node\Expr\Assign) {
            $var_name = ExpressionChecker::getArrayVarId(
                $conditional->var,
                $this_class_name,
                $source
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
                $source
            );

            return TypeChecker::negateTypes($if_types_to_negate);
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical ||
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
        ) {
            $null_position = self::hasNullVariable($conditional);
            $false_position = self::hasFalseVariable($conditional);
            $gettype_position = self::hasGetTypeCheck($conditional);
            $typed_value_position = self::hasTypedValueComparison($conditional);

            $var_name = null;
            $var_type = null;

            if ($null_position !== null) {
                if ($null_position === self::ASSIGNMENT_TO_RIGHT) {
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->left,
                        $this_class_name,
                        $source
                    );

                    $var_type = isset($conditional->left->inferredType) ? $conditional->left->inferredType : null;
                } elseif ($null_position === self::ASSIGNMENT_TO_LEFT) {
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->right,
                        $this_class_name,
                        $source
                    );

                    $var_type = isset($conditional->right->inferredType) ? $conditional->right->inferredType : null;
                }

                if ($var_name) {
                    if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                        $if_types[$var_name] = 'null';
                    } else {
                        $if_types[$var_name] = 'empty';
                    }
                } elseif ($var_type && $conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                    $null_type = Type::getNull();

                    if (!TypeChecker::isContainedBy(
                        $var_type,
                        $null_type,
                        $source->getFileChecker()
                    ) && !TypeChecker::isContainedBy(
                        $null_type,
                        $var_type,
                        $source->getFileChecker()
                    )) {
                        if (IssueBuffer::accepts(
                            new TypeDoesNotContainNull(
                                $var_type . ' does not contain ' . $null_type,
                                new CodeLocation($source, $conditional)
                            ),
                            $source->getSuppressedIssues()
                        )) {
                            // fall through
                        }
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
                            $source,
                            true
                        );
                    } else {
                        $var_name = ExpressionChecker::getArrayVarId(
                            $conditional->left,
                            $this_class_name,
                            $source
                        );

                        $var_type = isset($conditional->left->inferredType) ? $conditional->left->inferredType : null;
                    }
                } elseif ($false_position === self::ASSIGNMENT_TO_LEFT) {
                    if ($conditional->right instanceof PhpParser\Node\Expr\FuncCall) {
                        self::processFunctionCall(
                            $conditional->right,
                            $if_types,
                            $this_class_name,
                            $source,
                            true
                        );
                    } else {
                        $var_name = ExpressionChecker::getArrayVarId(
                            $conditional->right,
                            $this_class_name,
                            $source
                        );

                        $var_type = isset($conditional->right->inferredType) ? $conditional->right->inferredType : null;
                    }
                }

                if ($var_name) {
                    if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                        $if_types[$var_name] = 'false';
                    } else {
                        $if_types[$var_name] = 'empty';
                    }
                } elseif ($var_type && $conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                    $false_type = Type::getFalse();

                    if (!TypeChecker::isContainedBy(
                        $var_type,
                        $false_type,
                        $source->getFileChecker()
                    ) && !TypeChecker::isContainedBy(
                        $false_type,
                        $var_type,
                        $source->getFileChecker()
                    )) {
                        if (IssueBuffer::accepts(
                            new TypeDoesNotContainType(
                                $var_type . ' does not contain ' . $false_type,
                                new CodeLocation($source, $conditional)
                            ),
                            $source->getSuppressedIssues()
                        )) {
                            // fall through
                        }
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
                        $source
                    );

                    /** @var PhpParser\Node\Scalar\String_ $conditional->left */
                    $var_type = $conditional->left->value;
                } elseif ($gettype_position === self::ASSIGNMENT_TO_LEFT) {
                    /** @var PhpParser\Node\Expr\FuncCall $conditional->left */
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->left->args[0]->value,
                        $this_class_name,
                        $source
                    );

                    /** @var PhpParser\Node\Scalar\String_ $conditional->right */
                    $var_type = $conditional->right->value;
                }

                if ($var_name && $var_type) {
                    $if_types[$var_name] = $var_type;
                }

                return $if_types;
            }

            if ($typed_value_position) {
                $var_type = null;
                $other_type = null;

                if ($typed_value_position === self::ASSIGNMENT_TO_RIGHT) {
                    /** @var PhpParser\Node\Expr $conditional->right */
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->left,
                        $this_class_name,
                        $source
                    );

                    $other_type = isset($conditional->left->inferredType) ? $conditional->left->inferredType : null;
                    $var_type = isset($conditional->right->inferredType) ? $conditional->right->inferredType : null;
                } elseif ($typed_value_position === self::ASSIGNMENT_TO_LEFT) {
                    /** @var PhpParser\Node\Expr $conditional->left */
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->right,
                        $this_class_name,
                        $source
                    );

                    $var_type = isset($conditional->left->inferredType) ? $conditional->left->inferredType : null;
                    $other_type = isset($conditional->right->inferredType) ? $conditional->right->inferredType : null;
                }

                if ($var_type) {
                    if ($var_name) {
                        $if_types[$var_name] = '^' . $var_type;
                    } elseif ($other_type && $conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                        if (!TypeChecker::isContainedBy(
                            $var_type,
                            $other_type,
                            $source->getFileChecker(),
                            true
                        ) && !TypeChecker::isContainedBy(
                            $other_type,
                            $var_type,
                            $source->getFileChecker(),
                            true
                        )) {
                            if (IssueBuffer::accepts(
                                new TypeDoesNotContainType(
                                    $var_type . ' does not contain ' . $other_type,
                                    new CodeLocation($source, $conditional)
                                ),
                                $source->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        }
                    }
                }

                return $if_types;
            }

            $var_type = isset($conditional->left->inferredType) ? $conditional->left->inferredType : null;
            $other_type = isset($conditional->right->inferredType) ? $conditional->right->inferredType : null;

            if ($var_type && $other_type && $conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                if (!TypeChecker::canBeIdenticalTo($var_type, $other_type, $source->getFileChecker())) {
                    if (IssueBuffer::accepts(
                        new TypeDoesNotContainType(
                            $var_type . ' does not contain ' . $other_type,
                            new CodeLocation($source, $conditional)
                        ),
                        $source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
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
                        $source
                    );
                } elseif ($null_position === self::ASSIGNMENT_TO_LEFT) {
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->right,
                        $this_class_name,
                        $source
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
                        $source
                    );
                } elseif ($false_position === self::ASSIGNMENT_TO_LEFT) {
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->right,
                        $this_class_name,
                        $source
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
                            $source,
                            true
                        );
                    }
                } elseif ($true_position === self::ASSIGNMENT_TO_LEFT) {
                    if ($conditional->right instanceof PhpParser\Node\Expr\FuncCall) {
                        self::processFunctionCall(
                            $conditional->right,
                            $if_types,
                            $this_class_name,
                            $source,
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
            self::processFunctionCall($conditional, $if_types, $this_class_name, $source, false);

            return $if_types;
        }

        if ($conditional instanceof PhpParser\Node\Expr\Empty_) {
            $var_name = ExpressionChecker::getArrayVarId(
                $conditional->expr,
                $this_class_name,
                $source
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
                    $source
                );

                if ($var_name) {
                    $if_types[$var_name] = 'isset';
                } else {
                    // look for any variables we *can* use for an isset assertion
                    $array_root = $isset_var;

                    while ($array_root instanceof PhpParser\Node\Expr\ArrayDimFetch && !$var_name) {
                        $array_root = $array_root->var;

                        $var_name = ExpressionChecker::getArrayVarId(
                            $array_root,
                            $this_class_name,
                            $source
                        );
                    }

                    if ($var_name) {
                        $if_types[$var_name] = '^isset';
                    }
                }
            }

            return $if_types;
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Coalesce) {
            $var_name = ExpressionChecker::getArrayVarId(
                $conditional->left,
                $this_class_name,
                $source
            );

            if ($var_name) {
                $if_types[$var_name] = 'isset';
            } else {
                // look for any variables we *can* use for an isset assertion
                $array_root = $conditional->left;

                while ($array_root instanceof PhpParser\Node\Expr\ArrayDimFetch && !$var_name) {
                    $array_root = $array_root->var;

                    $var_name = ExpressionChecker::getArrayVarId(
                        $array_root,
                        $this_class_name,
                        $source
                    );
                }

                if ($var_name) {
                    $if_types[$var_name] = '^isset';
                }
            }

            return $if_types;
        }

        return [];
    }

    /**
     * @param  PhpParser\Node\Expr\FuncCall $expr
     * @param  array<string>                &$if_types
     * @param  string|null                  $this_class_name
     * @param  StatementsSource             $source
     * @param  bool                      $negate
     *
     * @return void
     */
    protected static function processFunctionCall(
        PhpParser\Node\Expr\FuncCall $expr,
        array &$if_types,
        $this_class_name,
        StatementsSource $source,
        $negate = false
    ) {
        $prefix = $negate ? '!' : '';

        $first_var_name = isset($expr->args[0]->value)
            ? ExpressionChecker::getArrayVarId(
                $expr->args[0]->value,
                $this_class_name,
                $source
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
     * @param  string|null                     $this_class_name
     * @param  StatementsSource                $source
     *
     * @return string|null
     */
    protected static function getInstanceOfTypes(
        PhpParser\Node\Expr\Instanceof_ $stmt,
        $this_class_name,
        StatementsSource $source
    ) {
        if ($stmt->class instanceof PhpParser\Node\Name) {
            if (!in_array($stmt->class->parts[0], ['self', 'static', 'parent'], true)) {
                $instanceof_class = ClassLikeChecker::getFQCLNFromNameObject(
                    $stmt->class,
                    $source
                );

                return $instanceof_class;
            } elseif ($stmt->class->parts === ['self'] && $this_class_name) {
                return $this_class_name;
            }
        }

        return null;
    }

    /**
     * @param   PhpParser\Node\Expr\BinaryOp    $conditional
     *
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
     *
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
     *
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
     *
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
     *
     * @return  false|int
     */
    protected static function hasTypedValueComparison(PhpParser\Node\Expr\BinaryOp $conditional)
    {
        if (!$conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
            return false;
        }

        if (isset($conditional->right->inferredType) && count($conditional->right->inferredType->types) === 1) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if (isset($conditional->left->inferredType) && count($conditional->left->inferredType->types) === 1) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
     * @return  bool
     */
    protected static function hasIntCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name &&
            ($stmt->name->parts === ['is_int'] ||
                $stmt->name->parts === ['is_integer'] ||
                $stmt->name->parts === ['is_long'])
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     *
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
     *
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
     *
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
     *
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
