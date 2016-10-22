<?php

namespace Psalm\Checker;

use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Issue\InvalidArgument;
use Psalm\Issue\FailedTypeResolution;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\StatementsSource;
use Psalm\Config;
use PhpParser;

class TypeChecker
{
    protected $absolute_class;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var StatementsChecker
     */
    protected $checker;
    protected $check_nulls;

    const ASSIGNMENT_TO_RIGHT = 1;
    const ASSIGNMENT_TO_LEFT = -1;

    /**
     * Gets all the type assertions in a conditional that are && together
     *
     * @param  PhpParser\Node\Expr  $conditional
     * @param  string               $this_class_name
     * @param  string               $namespace
     * @param  array<string>        $aliased_classes
     * @return array<string,string>
     */
    public static function getReconcilableTypeAssertions(PhpParser\Node\Expr $conditional, $this_class_name, $namespace, array $aliased_classes)
    {
        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr) {
            $left_assertions = self::getReconcilableTypeAssertions($conditional->left, $this_class_name, $namespace, $aliased_classes);
            $right_assertions = self::getReconcilableTypeAssertions($conditional->right, $this_class_name, $namespace, $aliased_classes);

            $keys = array_intersect(array_keys($left_assertions), array_keys($right_assertions));

            $if_types = [];

            foreach ($keys as $key) {
                if ($left_assertions[$key][0] !== '!' && $right_assertions[$key][0] !== '!') {
                    $if_types[$key] = $left_assertions[$key] . '|' . $right_assertions[$key];
                }
            }

            return $if_types;
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd) {
            $left_assertions = self::getReconcilableTypeAssertions($conditional->left, $this_class_name, $namespace, $aliased_classes);
            $right_assertions = self::getReconcilableTypeAssertions($conditional->right, $this_class_name, $namespace, $aliased_classes);

            return self::combineTypeAssertions($left_assertions, $right_assertions);
        }

        return self::getTypeAssertions($conditional, $this_class_name, $namespace, $aliased_classes);
    }

    /**
     * @param  PhpParser\Node\Expr  $conditional
     * @param  string               $this_class_name
     * @param  string               $namespace
     * @param  array<string>        $aliased_classes
     * @return array<string,string>
     */
    public static function getNegatableTypeAssertions(PhpParser\Node\Expr $conditional, $this_class_name, $namespace, array $aliased_classes)
    {
        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd) {
            return [];
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr) {
            $left_assertions = self::getNegatableTypeAssertions($conditional->left, $this_class_name, $namespace, $aliased_classes);
            $right_assertions = self::getNegatableTypeAssertions($conditional->right, $this_class_name, $namespace, $aliased_classes);

            return self::combineTypeAssertions($left_assertions, $right_assertions);
        }

        return self::getTypeAssertions($conditional, $this_class_name, $namespace, $aliased_classes);
    }

    private static function combineTypeAssertions(array $left_assertions, array $right_assertions)
    {
        $keys = array_merge(array_keys($left_assertions), array_keys($right_assertions));
        $keys = array_unique($keys);

        $if_types = [];

        foreach ($keys as $key) {
            if (isset($left_assertions[$key]) && isset($right_assertions[$key])) {
                if ($left_assertions[$key][0] !== '!' && $right_assertions[$key][0] !== '!') {
                    $if_types[$key] = $left_assertions[$key] . '&' . $right_assertions[$key];
                }
                else {
                    $if_types[$key] = $right_assertions[$key];
                }
            }
            else if (isset($left_assertions[$key])) {
                $if_types[$key] = $left_assertions[$key];
            }
            else {
                $if_types[$key] = $right_assertions[$key];
            }
        }

        return $if_types;
    }

    /**
     * Gets all the type assertions in a conditional
     *
     * @param  PhpParser\Node\Expr  $conditional
     * @param  string               $this_class_name
     * @param  string               $namespace
     * @param  array<string>        $aliased_classes
     * @return array<string,string>
     */
    public static function getTypeAssertions(PhpParser\Node\Expr $conditional, $this_class_name, $namespace, array $aliased_classes)
    {
        $if_types = [];

        if ($conditional instanceof PhpParser\Node\Expr\Instanceof_) {
            $instanceof_type = self::getInstanceOfTypes($conditional, $this_class_name, $namespace, $aliased_classes);

            if ($instanceof_type) {
                $var_name = ExpressionChecker::getArrayVarId($conditional->expr, $this_class_name, $namespace, $aliased_classes);
                if ($var_name) {
                    $if_types[$var_name] = $instanceof_type;
                }
            }
        }
        else if ($var_name = ExpressionChecker::getArrayVarId($conditional, $this_class_name, $namespace, $aliased_classes)) {
            $if_types[$var_name] = '!empty';
        }
        else if ($conditional instanceof PhpParser\Node\Expr\Assign) {
            $var_name = ExpressionChecker::getArrayVarId($conditional->var, $this_class_name, $namespace, $aliased_classes);
            if ($var_name) {
                $if_types[$var_name] = '!empty';
            }
        }
        else if ($conditional instanceof PhpParser\Node\Expr\BooleanNot) {
            if ($conditional->expr instanceof PhpParser\Node\Expr\Instanceof_) {
                $instanceof_type = self::getInstanceOfTypes($conditional->expr, $this_class_name, $namespace, $aliased_classes);

                if ($instanceof_type) {
                    $var_name = ExpressionChecker::getArrayVarId($conditional->expr->expr, $this_class_name, $namespace, $aliased_classes);
                    if ($var_name) {
                        $if_types[$var_name] = '!' . $instanceof_type;
                    }
                }
            }
            else if ($var_name = ExpressionChecker::getArrayVarId($conditional->expr, $this_class_name, $namespace, $aliased_classes)) {
                $if_types[$var_name] = 'empty';
            }
            else if ($conditional->expr instanceof PhpParser\Node\Expr\Assign) {
                $var_name = ExpressionChecker::getArrayVarId($conditional->expr->var, $this_class_name, $namespace, $aliased_classes);
                $if_types[$var_name] = 'empty';
            }
            else if ($conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\Identical || $conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\Equal) {
                $null_position = self::hasNullVariable($conditional->expr);
                $false_position = self::hasFalseVariable($conditional->expr);

                if ($null_position !== null) {
                    if ($null_position === self::ASSIGNMENT_TO_RIGHT) {
                        $var_name = ExpressionChecker::getArrayVarId($conditional->expr->left, $this_class_name, $namespace, $aliased_classes);
                    }
                    else if ($null_position === self::ASSIGNMENT_TO_LEFT) {
                        $var_name = ExpressionChecker::getArrayVarId($conditional->expr->right, $this_class_name, $namespace, $aliased_classes);
                    }
                    else {
                        throw new \InvalidArgumentException('Bad null variable position');
                    }

                    if ($var_name) {
                        if ($conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                            $if_types[$var_name] = '!null';
                        }
                        else {
                            // we do this because == null gives us a weaker idea than === null
                            $if_types[$var_name] = '!empty';
                        }
                    }
                }
                elseif ($false_position !== null) {
                    if ($false_position === self::ASSIGNMENT_TO_RIGHT) {
                        $var_name = ExpressionChecker::getArrayVarId($conditional->expr->left, $this_class_name, $namespace, $aliased_classes);
                    }
                    else if ($false_position === self::ASSIGNMENT_TO_LEFT) {
                        $var_name = ExpressionChecker::getArrayVarId($conditional->expr->right, $this_class_name, $namespace, $aliased_classes);
                    }
                    else {
                        throw new \InvalidArgumentException('Bad null variable position');
                    }

                    if ($var_name) {
                        if ($conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                            $if_types[$var_name] = '!false';
                        }
                        else {
                            // we do this because == null gives us a weaker idea than === null
                            $if_types[$var_name] = '!empty';
                        }
                    }
                }
            }
            else if ($conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical || $conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\NotEqual) {
                $null_position = self::hasNullVariable($conditional->expr);
                $false_position = self::hasFalseVariable($conditional->expr);

                if ($null_position !== null) {
                    if ($null_position === self::ASSIGNMENT_TO_RIGHT) {
                        $var_name = ExpressionChecker::getArrayVarId($conditional->expr->left, $this_class_name, $namespace, $aliased_classes);
                    }
                    else if ($null_position === self::ASSIGNMENT_TO_LEFT) {
                        $var_name = ExpressionChecker::getArrayVarId($conditional->expr->right, $this_class_name, $namespace, $aliased_classes);
                    }
                    else {
                        throw new \InvalidArgumentException('Bad null variable position');
                    }

                    if ($var_name) {
                        if ($conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical) {
                            $if_types[$var_name] = 'null';
                        }
                        else {
                            $if_types[$var_name] = 'empty';
                        }
                    }
                }
                elseif ($false_position !== null) {
                    if ($false_position === self::ASSIGNMENT_TO_RIGHT) {
                        $var_name = ExpressionChecker::getArrayVarId($conditional->expr->left, $this_class_name, $namespace, $aliased_classes);
                    }
                    else if ($false_position === self::ASSIGNMENT_TO_LEFT) {
                        $var_name = ExpressionChecker::getArrayVarId($conditional->expr->right, $this_class_name, $namespace, $aliased_classes);
                    }
                    else {
                        throw new \InvalidArgumentException('Bad null variable position');
                    }

                    if ($var_name) {
                        if ($conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                            $if_types[$var_name] = 'false';
                        }
                        else {
                            // we do this because == null gives us a weaker idea than === null
                            $if_types[$var_name] = 'empty';
                        }
                    }
                }
            }
            else if ($conditional->expr instanceof PhpParser\Node\Expr\Empty_) {
                $var_name = ExpressionChecker::getArrayVarId($conditional->expr->expr, $this_class_name, $namespace, $aliased_classes);

                if ($var_name) {
                    $if_types[$var_name] = '!empty';
                }
            }
            elseif ($conditional->expr instanceof PhpParser\Node\Expr\FuncCall) {
                self::processFunctionCall($conditional->expr, $if_types, true, $this_class_name, $namespace, $aliased_classes);
            }
            else if ($conditional->expr instanceof PhpParser\Node\Expr\Isset_) {
                foreach ($conditional->expr->vars as $isset_var) {
                    $var_name = ExpressionChecker::getArrayVarId($isset_var, $this_class_name, $namespace, $aliased_classes);
                    if ($var_name) {
                        $if_types[$var_name] = 'null';
                    }
                }
            }
        }
        else if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal) {
            $null_position = self::hasNullVariable($conditional);
            $false_position = self::hasFalseVariable($conditional);
            $gettype_position = self::hasGetTypeCheck($conditional);

            $var_name = null;

            if ($null_position !== null) {
                if ($null_position === self::ASSIGNMENT_TO_RIGHT) {
                    $var_name = ExpressionChecker::getArrayVarId($conditional->left, $this_class_name, $namespace, $aliased_classes);
                }
                else if ($null_position === self::ASSIGNMENT_TO_LEFT) {
                    $var_name = ExpressionChecker::getArrayVarId($conditional->right, $this_class_name, $namespace, $aliased_classes);
                }
                else {
                    throw new \InvalidArgumentException('Bad null variable position');
                }

                if ($var_name) {
                    if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                        $if_types[$var_name] = 'null';
                    }
                    else {
                        $if_types[$var_name] = 'empty';
                    }
                }
            }
            elseif ($false_position) {
                if ($false_position === self::ASSIGNMENT_TO_RIGHT) {
                    if ($conditional->left instanceof PhpParser\Node\Expr\FuncCall) {
                        self::processFunctionCall($conditional->left, $if_types, true, $this_class_name, $namespace, $aliased_classes);
                    }
                    else {
                        $var_name = ExpressionChecker::getArrayVarId($conditional->left, $this_class_name, $namespace, $aliased_classes);
                    }
                }
                else if ($false_position === self::ASSIGNMENT_TO_LEFT) {
                    if ($conditional->right instanceof PhpParser\Node\Expr\FuncCall) {
                        self::processFunctionCall($conditional->right, $if_types, true, $this_class_name, $namespace, $aliased_classes);
                    }
                    else {
                        $var_name = ExpressionChecker::getArrayVarId($conditional->right, $this_class_name, $namespace, $aliased_classes);
                    }
                }
                else {
                    throw new \InvalidArgumentException('Bad null variable position');
                }

                if ($var_name) {
                    if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                        $if_types[$var_name] = 'false';
                    }
                    else {
                        $if_types[$var_name] = 'empty';
                    }
                }
            }
            elseif ($gettype_position) {
                $var_type = null;

                if ($gettype_position === self::ASSIGNMENT_TO_RIGHT) {
                    /** @var PhpParser\Node\Expr\FuncCall $conditional->right */
                    $var_name = ExpressionChecker::getArrayVarId($conditional->right->args[0]->value, $this_class_name, $namespace, $aliased_classes);

                    /** @var PhpParser\Node\Scalar\String_ $conditional->left */
                    $var_type = $conditional->left->value;
                }
                else if ($gettype_position === self::ASSIGNMENT_TO_LEFT) {
                    /** @var PhpParser\Node\Expr\FuncCall $conditional->left */
                    $var_name = ExpressionChecker::getArrayVarId($conditional->left->args[0]->value, $this_class_name, $namespace, $aliased_classes);

                    /** @var PhpParser\Node\Scalar\String_ $conditional->right */
                    $var_type = $conditional->right->value;
                }

                if ($var_name) {
                    $if_types[$var_name] = $var_type;
                }
            }
        }
        else if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical || $conditional instanceof PhpParser\Node\Expr\BinaryOp\NotEqual) {
            $null_position = self::hasNullVariable($conditional);
            $false_position = self::hasFalseVariable($conditional);
            $true_position = self::hasTrueVariable($conditional);

            if ($null_position !== null) {
                if ($null_position === self::ASSIGNMENT_TO_RIGHT) {
                    $var_name = ExpressionChecker::getArrayVarId($conditional->left, $this_class_name, $namespace, $aliased_classes);
                }
                else if ($null_position === self::ASSIGNMENT_TO_LEFT) {
                    $var_name = ExpressionChecker::getArrayVarId($conditional->right, $this_class_name, $namespace, $aliased_classes);
                }
                else {
                    throw new \InvalidArgumentException('Bad null variable position');
                }

                if ($var_name) {
                    if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical) {
                        $if_types[$var_name] = '!null';
                    }
                    else {
                        $if_types[$var_name] = '!empty';
                    }
                }
            }
            elseif ($false_position) {
                if ($false_position === self::ASSIGNMENT_TO_RIGHT) {
                    $var_name = ExpressionChecker::getArrayVarId($conditional->left, $this_class_name, $namespace, $aliased_classes);
                }
                else if ($false_position === self::ASSIGNMENT_TO_LEFT) {
                    $var_name = ExpressionChecker::getArrayVarId($conditional->right, $this_class_name, $namespace, $aliased_classes);
                }
                else {
                    throw new \InvalidArgumentException('Bad null variable position');
                }

                if ($var_name) {
                    if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                        $if_types[$var_name] = '!false';
                    }
                    else {
                        $if_types[$var_name] = '!empty';
                    }
                }
            }
            elseif ($true_position) {
                if ($true_position === self::ASSIGNMENT_TO_RIGHT) {
                    if ($conditional->left instanceof PhpParser\Node\Expr\FuncCall) {
                        self::processFunctionCall($conditional->left, $if_types, true, $this_class_name, $namespace, $aliased_classes);
                    }
                }
                else if ($true_position === self::ASSIGNMENT_TO_LEFT) {
                    if ($conditional->right instanceof PhpParser\Node\Expr\FuncCall) {
                        self::processFunctionCall($conditional->right, $if_types, true, $this_class_name, $namespace, $aliased_classes);
                    }
                }
                else {
                    throw new \InvalidArgumentException('Bad null variable position');
                }
            }
        }
        elseif ($conditional instanceof PhpParser\Node\Expr\FuncCall) {
            self::processFunctionCall($conditional, $if_types, false, $this_class_name, $namespace, $aliased_classes);
        }
        else if ($conditional instanceof PhpParser\Node\Expr\Empty_) {
            $var_name = ExpressionChecker::getArrayVarId($conditional->expr, $this_class_name, $namespace, $aliased_classes);
            if ($var_name) {
                $if_types[$var_name] = 'empty';
            }
        }
        else if ($conditional instanceof PhpParser\Node\Expr\Isset_) {
            foreach ($conditional->vars as $isset_var) {
                $var_name = ExpressionChecker::getArrayVarId($isset_var, $this_class_name, $namespace, $aliased_classes);
                if ($var_name) {
                    $if_types[$var_name] = '!null';
                }
            }
        }

        return $if_types;
    }

    /**
     * @param  PhpParser\Node\Expr\FuncCall $expr
     * @param  array<string>                &$if_types
     * @param  boolean                      $negate
     * @param  string                       $this_class_name
     * @param  string                       $namespace
     * @param  array<string>                $aliased_classes]
     * @return void
     */
    protected static function processFunctionCall(
        PhpParser\Node\Expr\FuncCall $expr,
        array &$if_types,
        $negate = false,
        $this_class_name,
        $namespace,
        array $aliased_classes
    ) {
        $prefix = $negate ? '!' : '';

        if (self::hasNullCheck($expr)) {
            $var_name = ExpressionChecker::getArrayVarId($expr->args[0]->value, $this_class_name, $namespace, $aliased_classes);
            if ($var_name) {
                $if_types[$var_name] = $prefix . 'null';
            }
        }
        else if (self::hasIsACheck($expr)) {
            $var_name = ExpressionChecker::getArrayVarId($expr->args[0]->value, $this_class_name, $namespace, $aliased_classes);
            if ($var_name) {
                /** @var PhpParser\Node\Scalar\String_ */
                $is_a_type = $expr->args[1]->value;
                $if_types[$var_name] = $prefix . $is_a_type->value;
            }
        }
        else if (self::hasArrayCheck($expr)) {
            $var_name = ExpressionChecker::getArrayVarId($expr->args[0]->value, $this_class_name, $namespace, $aliased_classes);
            if ($var_name) {
                $if_types[$var_name] = $prefix . 'array';
            }
        }
        else if (self::hasBoolCheck($expr)) {
            $var_name = ExpressionChecker::getArrayVarId($expr->args[0]->value, $this_class_name, $namespace, $aliased_classes);
            if ($var_name) {
                $if_types[$var_name] = $prefix . 'bool';
            }
        }
        else if (self::hasStringCheck($expr)) {
            $var_name = ExpressionChecker::getArrayVarId($expr->args[0]->value, $this_class_name, $namespace, $aliased_classes);
            if ($var_name) {
                $if_types[$var_name] = $prefix . 'string';
            }
        }
        else if (self::hasObjectCheck($expr)) {
            $var_name = ExpressionChecker::getArrayVarId($expr->args[0]->value, $this_class_name, $namespace, $aliased_classes);
            if ($var_name) {
                $if_types[$var_name] = $prefix . 'object';
            }
        }
        else if (self::hasNumericCheck($expr)) {
            $var_name = ExpressionChecker::getArrayVarId($expr->args[0]->value, $this_class_name, $namespace, $aliased_classes);
            if ($var_name) {
                $if_types[$var_name] = $prefix . 'numeric';
            }
        }
        else if (self::hasIntCheck($expr)) {
            $var_name = ExpressionChecker::getArrayVarId($expr->args[0]->value, $this_class_name, $namespace, $aliased_classes);
            if ($var_name) {
                $if_types[$var_name] = $prefix . 'int';
            }
        }
        else if (self::hasFloatCheck($expr)) {
            $var_name = ExpressionChecker::getArrayVarId($expr->args[0]->value, $this_class_name, $namespace, $aliased_classes);
            if ($var_name) {
                $if_types[$var_name] = $prefix . 'float';
            }
        }
        else if (self::hasResourceCheck($expr)) {
            $var_name = ExpressionChecker::getArrayVarId($expr->args[0]->value, $this_class_name, $namespace, $aliased_classes);
            if ($var_name) {
                $if_types[$var_name] = $prefix . 'resource';
            }
        }
        else if (self::hasScalarCheck($expr)) {
            $var_name = ExpressionChecker::getArrayVarId($expr->args[0]->value, $this_class_name, $namespace, $aliased_classes);
            if ($var_name) {
                $if_types[$var_name] = $prefix . 'scalar';
            }
        }
        else if (self::hasCallableCheck($expr)) {
            $var_name = ExpressionChecker::getArrayVarId($expr->args[0]->value, $this_class_name, $namespace, $aliased_classes);
            if ($var_name) {
                $if_types[$var_name] = $prefix . 'callable';
            }
        }
    }

    protected function getInstanceOfTypes(PhpParser\Node\Expr\Instanceof_ $stmt, $this_class_name, $namespace, $aliased_classes)
    {
        if ($stmt->class instanceof PhpParser\Node\Name) {
            if (!in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
                $instanceof_class = ClassLikeChecker::getAbsoluteClassFromName($stmt->class, $namespace, $aliased_classes);
                return $instanceof_class;

            } elseif ($stmt->class->parts === ['self']) {
                return $this_class_name;
            }
        }

        return null;
    }

    protected static function hasNullVariable(PhpParser\Node\Expr\BinaryOp $conditional)
    {
        if ($conditional->right instanceof PhpParser\Node\Expr\ConstFetch &&
            $conditional->right->name instanceof PhpParser\Node\Name &&
            $conditional->right->name->parts === ['null']) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\ConstFetch &&
            $conditional->left->name instanceof PhpParser\Node\Name &&
            $conditional->left->name->parts === ['null']) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return null;
    }

    protected static function hasFalseVariable(PhpParser\Node\Expr\BinaryOp $conditional)
    {
        if ($conditional->right instanceof PhpParser\Node\Expr\ConstFetch &&
            $conditional->right->name instanceof PhpParser\Node\Name &&
            $conditional->right->name->parts === ['false']) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\ConstFetch &&
            $conditional->left->name instanceof PhpParser\Node\Name &&
            $conditional->left->name->parts === ['false']) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return null;
    }

    protected static function hasTrueVariable(PhpParser\Node\Expr\BinaryOp $conditional)
    {
        if ($conditional->right instanceof PhpParser\Node\Expr\ConstFetch &&
            $conditional->right->name instanceof PhpParser\Node\Name &&
            $conditional->right->name->parts === ['true']) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\ConstFetch &&
            $conditional->left->name instanceof PhpParser\Node\Name &&
            $conditional->left->name->parts === ['true']) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return null;
    }

    /**
     * @return bool
     */
    protected static function hasGetTypeCheck(PhpParser\Node\Expr\BinaryOp $conditional)
    {
        if ($conditional->right instanceof PhpParser\Node\Expr\FuncCall &&
            $conditional->right->name instanceof PhpParser\Node\Name &&
            $conditional->right->name->parts === ['gettype'] &&
            $conditional->left instanceof PhpParser\Node\Scalar\String_) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\FuncCall &&
            $conditional->left->name instanceof PhpParser\Node\Name &&
            $conditional->left->name->parts === ['gettype'] &&
            $conditional->right instanceof PhpParser\Node\Scalar\String_) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected static function hasNullCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_null']) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected static function hasIsACheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_a'] &&
            $stmt->args[1]->value instanceof PhpParser\Node\Scalar\String_) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected static function hasArrayCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_array']) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected static function hasStringCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_string']) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected static function hasBoolCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_bool']) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected static function hasObjectCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_object']) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected static function hasNumericCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_numeric']) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected static function hasIntCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && ($stmt->name->parts === ['is_int'] || $stmt->name->parts === ['is_integer']|| $stmt->name->parts === ['is_long'])) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected static function hasFloatCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && ($stmt->name->parts === ['is_float'] || $stmt->name->parts === ['is_real'] || $stmt->name->parts === ['is_double'])) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected static function hasResourceCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_resource']) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected static function hasScalarCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_scalar']) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected static function hasCallableCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_callable']) {
            return true;
        }

        return false;
    }

    /**
     * Takes two arrays and consolidates them, removing null values from existing types where applicable
     *
     * @param  array<string,string>     $new_types
     * @param  array<string,Type\Union> $existing_types
     * @param  string                   $file_name
     * @param  int                      $line_number
     * @param  array<string>            $suppressed_issues
     * @return array|false
     */
    public static function reconcileKeyedTypes(array $new_types, array $existing_types, $file_name, $line_number, array $suppressed_issues = [])
    {
        $keys = array_merge(array_keys($new_types), array_keys($existing_types));
        $keys = array_unique($keys);

        $result_types = [];

        if (empty($new_types)) {
            return $existing_types;
        }

        foreach ($keys as $key) {
            if (!isset($new_types[$key])) {
                $result_types[$key] = $existing_types[$key];
                continue;
            }

            $new_type_parts = explode('&', $new_types[$key]);

            $result_type = isset($existing_types[$key]) ? clone $existing_types[$key] : self::getValueForKey($key, $existing_types);

            foreach ($new_type_parts as $new_type_part) {
                $result_type = self::reconcileTypes(
                    (string) $new_type_part,
                    $result_type,
                    $key,
                    $file_name,
                    $line_number,
                    $suppressed_issues
                );

                // special case if result is just a simple array
                if ((string) $result_type === 'array') {
                    $result_type = Type::getArray();
                }
            }

            if ($result_type === null) {
                continue;
            }

            if ($result_type === false) {
                return false;
            }

            $result_types[$key] = $result_type;
        }

        return $result_types;
    }

    /**
     * Reconciles types
     *
     * think of this as a set of functions e.g. empty(T), notEmpty(T), null(T), notNull(T) etc. where
     * empty(Object) => null,
     * empty(bool) => false,
     * notEmpty(Object|null) => Object,
     * notEmpty(Object|false) => Object
     *
     * @param  string       $new_var_type
     * @param  Type\Union   $existing_var_type
     * @param  string       $key
     * @param  string       $file_name
     * @param  int          $line_number
     * @return Type\Union|null|false
     */
    public static function reconcileTypes($new_var_type, Type\Union $existing_var_type = null, $key = null, $file_name = null, $line_number = null, array $suppressed_issues = [])
    {
        $result_var_types = null;

        if ($existing_var_type === null) {
            if ($new_var_type[0] !== '!') {
                return Type::parseString($new_var_type);
            }

            return $new_var_type === '!empty' ? Type::getMixed() : null;
        }

        if ($new_var_type === 'mixed' && $existing_var_type->isMixed()) {
            return $existing_var_type;
        }

        if ($new_var_type === 'null') {
            return Type::getNull();
        }

        if ($new_var_type[0] === '!') {
            if ($new_var_type === '!object' && !$existing_var_type->isMixed()) {
                $non_object_types = [];

                foreach ($existing_var_type->types as $type) {
                    if (!$type->isObjectType()) {
                        $non_object_types[] = $type;
                    }
                }

                if ($non_object_types) {
                    return new Type\Union($non_object_types);
                }
            }

            if (in_array($new_var_type, ['!empty', '!null'])) {
                $existing_var_type->removeType('null');

                if ($new_var_type === '!empty') {
                    $existing_var_type->removeType('false');
                }

                if (empty($existing_var_type->types)) {
                    // @todo - I think there's a better way to handle this, but for the moment
                    // mixed will have to do.
                    return Type::getMixed();
                }

                return $existing_var_type;
            }

            $negated_type = substr($new_var_type, 1);

            $existing_var_type->removeType($negated_type);

            if (empty($existing_var_type->types)) {
                if ($key) {

                    if (IssueBuffer::accepts(
                        new FailedTypeResolution('Cannot resolve types for ' . $key, $file_name, $line_number),
                        $suppressed_issues
                    )) {
                        return false;
                    }

                    return Type::getMixed();
                }
            }

            return $existing_var_type;
        }

        if ($new_var_type === 'empty') {
            if ($existing_var_type->hasType('bool')) {
                $existing_var_type->removeType('bool');
                $existing_var_type->types['false'] = new Type\Atomic('false');
            }

            $existing_var_type->removeObjects();

            if (empty($existing_var_type->types)) {
                return Type::getNull();
            }

            return $existing_var_type;
        }

        if ($new_var_type === 'object' && !$existing_var_type->isMixed()) {
            $object_types = [];

            foreach ($existing_var_type->types as $type) {
                if ($type->isObjectType()) {
                    $object_types[] = $type;
                }
            }

            if ($object_types) {
                return new Type\Union($object_types);
            }
        }

        return Type::parseString($new_var_type);
    }

    /**
     * Gets the type for a given (non-existent key) based on the passed keys
     * @param  string                    $key
     * @param  array<string,Type\Union>  $existing_keys
     * @return Type\Union|null
     */
    protected static function getValueForKey($key, array &$existing_keys)
    {
        $key_parts = explode('->', $key);

        $base_type = self::getArrayValueForKey($key_parts[0], $existing_keys);

        if (!$base_type) {
            return null;
        }

        $base_key = $key_parts[0];

        // for an expression like $obj->key1->key2
        for ($i = 1; $i < count($key_parts); $i++) {
            $new_base_key = $base_key . '->' . $key_parts[$i];

            if (!isset($existing_keys[$new_base_key])) {
                /** @var Type\Union|null */
                $new_base_type = null;

                foreach ($existing_keys[$base_key]->types as $existing_key_type_part) {
                    $class_properties = ClassLikeChecker::getInstancePropertiesForClass(
                        $existing_key_type_part->value,
                        \ReflectionProperty::IS_PUBLIC
                    );

                    if (!isset($class_properties[$key_parts[$i]])) {
                        return null;
                    }

                    if (!$new_base_type) {
                        $new_base_type = clone $class_properties[$key_parts[$i]];
                    }
                    else {
                        $new_base_type = Type::combineUnionTypes($new_base_type, clone $class_properties[$key_parts[$i]]);
                    }

                    $existing_keys[$new_base_key] = $new_base_type;
                }
            }

            $base_type = $existing_keys[$new_base_key];
            $base_key = $new_base_key;
        }

        return $existing_keys[$base_key];
    }

    /**
     * Gets the type for a given (non-existent key) based on the passed keys
     * @param  string                    $key
     * @param  array<string,Type\Union>  $existing_keys
     * @return Type\Union|null
     */
    protected static function getArrayValueForKey($key, array &$existing_keys)
    {
        $key_parts = preg_split('/(\'\]|\[\')/', $key, -1, PREG_SPLIT_NO_EMPTY);

        if (count($key_parts) === 1) {
            return isset($existing_keys[$key_parts[0]]) ? clone $existing_keys[$key_parts[0]] : null;
        }

        if (!isset($existing_keys[$key_parts[0]])) {
            return null;
        }

        $base_type = $existing_keys[$key_parts[0]];
        $base_key = $key_parts[0];

        // for an expression like $obj->key1->key2
        for ($i = 1; $i < count($key_parts); $i++) {
            $new_base_key = $base_key . '[\'' . $key_parts[$i] . '\']';

            if (!isset($existing_keys[$new_base_key])) {
                /** @var Type\Union|null */
                $new_base_type = null;

                foreach ($existing_keys[$base_key]->types as $existing_key_type_part) {
                    if (!$existing_key_type_part->isObjectLike()) {
                        return null;
                    }

                    /** @var Type\ObjectLike $existing_key_type_part */
                    $array_properties = $existing_key_type_part->properties;

                    if (!isset($array_properties[$key_parts[$i]])) {
                        return null;
                    }

                    if (!$new_base_type) {
                        $new_base_type = clone $array_properties[$key_parts[$i]];
                    }
                    else {
                        $new_base_type = Type::combineUnionTypes($new_base_type, clone $array_properties[$key_parts[$i]]);
                    }

                    $existing_keys[$new_base_key] = $new_base_type;
                }
            }

            $base_type = $existing_keys[$new_base_key];
            $base_key = $new_base_key;
        }

        return $existing_keys[$base_key];
    }

    public static function isNegation($type, $existing_type)
    {
        if ($type === 'mixed' || $existing_type === 'mixed') {
            return false;
        }

        if ($type === '!' . $existing_type || $existing_type === '!' . $type) {
            return true;
        }

        if (in_array($type, ['empty', 'false', 'null']) && !in_array($existing_type, ['empty', 'false', 'null'])) {
            return true;
        }

        if (in_array($existing_type, ['empty', 'false', 'null']) && !in_array($type, ['empty', 'false', 'null'])) {
            return true;
        }

        return false;
    }

    /**
     * Takes two arrays of types and merges them
     *
     * @param  array<Type\Union>  $new_types
     * @param  array<Type\Union>  $existing_types
     * @return array<string,Type\Union>
     */
    public static function combineKeyedTypes(array $new_types, array $existing_types)
    {
        $keys = array_merge(array_keys($new_types), array_keys($existing_types));
        $keys = array_unique($keys);

        $result_types = [];

        if (empty($new_types)) {
            return $existing_types;
        }

        if (empty($existing_types)) {
            return $new_types;
        }

        foreach ($keys as $key) {
            if (!isset($existing_types[$key])) {
                $result_types[$key] = $new_types[$key];
                continue;
            }

            if (!isset($new_types[$key])) {
                $result_types[$key] = $existing_types[$key];
                continue;
            }

            $existing_var_types = $existing_types[$key];
            $new_var_types = $new_types[$key];

            if ((string) $new_var_types === (string) $existing_var_types) {
                $result_types[$key] = $new_var_types;
            }
            else {
                $result_types[$key] = Type::combineUnionTypes($new_var_types, $existing_var_types);
            }
        }

        return $result_types;
    }

    /**
     * @param  array<string,string>  $all_types
     * @return array<string>
     */
    public static function reduceTypes(array $all_types)
    {
        if (in_array('mixed', $all_types)) {
            return ['mixed'];
        }

        $array_types = array_filter($all_types, function($type) {
            return preg_match('/^array(\<|$)/', (string)$type);
        });

        $all_types = array_flip($all_types);

        if (isset($all_types['array<empty>']) && count($array_types) > 1) {
            unset($all_types['array<empty>']);
        }

        if (isset($all_types['array<mixed>'])) {
            unset($all_types['array<mixed>']);

            $all_types['array'] = true;
        }

        return array_keys($all_types);
    }

    /**
     * @param  array<string>  $types
     * @return array<string>
     */
    public static function negateTypes(array $types)
    {
        return array_map(
            function ($type) {
                if ($type === 'mixed') {
                    return $type;
                }

                $type_parts = explode('&', (string)$type);

                foreach ($type_parts as &$type_part) {
                    $type_part = $type_part[0] === '!' ? substr($type_part, 1) : '!' . $type_part;
                }

                return implode('&', $type_parts);
            },
            $types
        );
    }

    /**
     * @param  Type\Union $declared_type
     * @param  Type\Union $inferred_type
     * @param  string     $absolute_class
     * @return boolean
     */
    public static function hasIdenticalTypes(Type\Union $declared_type, Type\Union $inferred_type, $absolute_class)
    {
        if ($declared_type->isMixed() || $inferred_type->isEmpty()) {
            return true;
        }

        if ($declared_type->isNullable() !== $inferred_type->isNullable()) {
            return false;
        }

        $inferred_type = ExpressionChecker::fleshOutTypes($inferred_type, [], $absolute_class, '');

        $simple_declared_types = array_filter(
            array_keys($declared_type->types),
            function ($type_value) {
                return $type_value !== 'null';
            }
        );

        $simple_inferred_types = array_filter(
            array_keys($inferred_type->types),
            function ($type_value) {
                return $type_value !== 'null';
            }
        );

        // gets elements A△B
        $differing_types = array_diff($simple_inferred_types, $simple_declared_types);

        if (count($differing_types)) {
            // check whether the differing types are subclasses of declared return types
            $truly_different = false;

            foreach ($differing_types as $differing_type) {
                $is_match = false;

                if ($differing_type === 'mixed') {
                    continue;
                }

                foreach ($simple_declared_types as $simple_declared_type) {
                    if ($simple_declared_type === 'mixed'
                        || ($simple_declared_type === 'object' && ClassLikeChecker::classOrInterfaceExists($differing_type))
                        || ClassChecker::classExtendsOrImplements($differing_type, $simple_declared_type)
                        || (in_array($differing_type, ['array', 'object-like']) && in_array($simple_declared_type, ['array', 'object-like']))
                        || (in_array($differing_type, ['float', 'int']) && in_array($simple_declared_type, ['float', 'int']))
                    ) {
                        $is_match = true;
                        break;
                    }
                }

                if (!$is_match) {
                    $truly_different = true;
                }
            }

            return !$truly_different;
        }

        foreach ($declared_type->types as $key => $declared_atomic_type) {
            if (!isset($inferred_type->types[$key])) {
                continue;
            }

            $inferred_atomic_type = $inferred_type->types[$key];

            if (!($declared_atomic_type instanceof Type\Generic)) {
                continue;
            }

            if (!($inferred_atomic_type instanceof Type\Generic)) {
                // @todo handle this better
                continue;
            }

            foreach ($declared_atomic_type->type_params as $offset => $type_param) {
                if (!self::hasIdenticalTypes($declared_atomic_type->type_params[$offset], $inferred_atomic_type->type_params[$offset], $absolute_class)) {
                    return false;
                }
            }
        }

        foreach ($declared_type->types as $key => $declared_atomic_type) {
            if (!isset($inferred_type->types[$key])) {
                continue;
            }

            $inferred_atomic_type = $inferred_type->types[$key];

            if (!($declared_atomic_type instanceof Type\ObjectLike)) {
                continue;
            }

            if (!($inferred_atomic_type instanceof Type\ObjectLike)) {
                // @todo handle this better
                continue;
            }

            foreach ($declared_atomic_type->properties as $property_name => $type_param) {
                if (!isset($inferred_atomic_type->properties[$property_name])) {
                    return false;
                }

                if (!self::hasIdenticalTypes($type_param, $inferred_atomic_type->properties[$property_name], $absolute_class)) {
                    return false;
                }
            }

        }

        return true;
    }
}
