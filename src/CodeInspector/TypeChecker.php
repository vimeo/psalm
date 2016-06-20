<?php

namespace CodeInspector;

use CodeInspector\Issue\InvalidArgument;
use CodeInspector\Issue\FailedTypeResolution;
use CodeInspector\ExceptionHandler;
use PhpParser;

class TypeChecker
{
    protected $_absolute_class;
    protected $_namespace;
    protected $_checker;
    protected $_check_nulls;

    const ASSIGNMENT_TO_RIGHT = 1;
    const ASSIGNMENT_TO_LEFT = -1;

    public function __construct(StatementsSource $source, StatementsChecker $statements_checker)
    {
        $this->_absolute_class = $source->getAbsoluteClass();
        $this->_namespace = $source->getNamespace();
        $this->_checker = $statements_checker;
    }

    /**
     * Gets all the type assertions in a conditional
     *
     * @param  PhpParser\Node\Expr $stmt
     * @return array
     */
    public function getTypeAssertions(PhpParser\Node\Expr $conditional, $check_boolean_and = false)
    {
        $if_types = [];

        if ($conditional instanceof PhpParser\Node\Expr\Instanceof_) {
            $instanceof_type = $this->_getInstanceOfTypes($conditional);

            if ($instanceof_type) {
                $var_name = StatementsChecker::getVarId($conditional->expr);
                if ($var_name) {
                    $if_types[$var_name] = $instanceof_type;
                }
            }
        }
        else if ($var_name = StatementsChecker::getVarId($conditional)) {
            $if_types[$var_name] = '!empty';
        }
        else if ($conditional instanceof PhpParser\Node\Expr\Assign) {
            $var_name = StatementsChecker::getVarId($conditional->var);
            $if_types[$var_name] = '!empty';
        }
        else if ($conditional instanceof PhpParser\Node\Expr\BooleanNot) {
            if ($conditional->expr instanceof PhpParser\Node\Expr\Instanceof_) {
                $instanceof_type = $this->_getInstanceOfTypes($conditional->expr);

                if ($instanceof_type) {
                    $var_name = StatementsChecker::getVarId($conditional->expr->expr);
                    if ($var_name) {
                        $if_types[$var_name] = '!' . $instanceof_type;
                    }
                }
            }
            else if ($var_name = StatementsChecker::getVarId($conditional->expr)) {
                $if_types[$var_name] = 'empty';
            }
            else if ($conditional->expr instanceof PhpParser\Node\Expr\Assign) {
                $var_name = StatementsChecker::getVarId($conditional->expr->var);
                $if_types[$var_name] = 'empty';
            }
            else if ($conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\Identical || $conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\Equal) {
                $null_position = self::_hasNullVariable($conditional->expr);
                $false_position = self::_hasNullVariable($conditional->expr);

                if ($null_position !== null) {
                    if ($null_position === self::ASSIGNMENT_TO_RIGHT) {
                        $var_name = StatementsChecker::getVarId($conditional->expr->left);
                    }
                    else if ($null_position === self::ASSIGNMENT_TO_LEFT) {
                        $var_name = StatementsChecker::getVarId($conditional->epxr->right);
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
                        $var_name = StatementsChecker::getVarId($conditional->expr->left);
                    }
                    else if ($false_position === self::ASSIGNMENT_TO_LEFT) {
                        $var_name = StatementsChecker::getVarId($conditional->epxr->right);
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
                $null_position = self::_hasNullVariable($conditional->expr);
                $false_position = self::_hasNullVariable($conditional->expr);

                if ($null_position !== null) {
                    if ($null_position === self::ASSIGNMENT_TO_RIGHT) {
                        $var_name = StatementsChecker::getVarId($conditional->expr->left);
                    }
                    else if ($null_position === self::ASSIGNMENT_TO_LEFT) {
                        $var_name = StatementsChecker::getVarId($conditional->epxr->right);
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
                        $var_name = StatementsChecker::getVarId($conditional->expr->left);
                    }
                    else if ($false_position === self::ASSIGNMENT_TO_LEFT) {
                        $var_name = StatementsChecker::getVarId($conditional->epxr->right);
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
                $var_name = StatementsChecker::getVarId($conditional->expr->expr);

                if ($var_name) {
                    $if_types[$var_name] = '!empty';
                }
            }
            else if (self::_hasNullCheck($conditional->expr)) {
                $var_name = StatementsChecker::getVarId($conditional->expr->args[0]->value);
                $if_types[$var_name] = '!null';
            }
            else if (self::_hasIsACheck($conditional->expr)) {
                $var_name = StatementsChecker::getVarId($conditional->expr->args[0]->value);
                $if_types[$var_name] = '!' . $conditional->expr->args[1]->value->value;
            }
            else if (self::_hasArrayCheck($conditional->expr)) {
                $var_name = StatementsChecker::getVarId($conditional->expr->args[0]->value);
                $if_types[$var_name] = '!array';
            }
            else if ($conditional->expr instanceof PhpParser\Node\Expr\Isset_) {
                foreach ($conditional->expr->vars as $isset_var) {
                    $var_name = StatementsChecker::getVarId($isset_var);
                    if ($var_name) {
                        $if_types[$var_name] = 'null';
                    }
                }
            }
        }
        else if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal) {
            $null_position = self::_hasNullVariable($conditional);
            $false_position = self::_hasFalseVariable($conditional);

            if ($null_position !== null) {
                if ($null_position === self::ASSIGNMENT_TO_RIGHT) {
                    $var_name = StatementsChecker::getVarId($conditional->left);
                }
                else if ($null_position === self::ASSIGNMENT_TO_LEFT) {
                    $var_name = StatementsChecker::getVarId($conditional->right);
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
                    $var_name = StatementsChecker::getVarId($conditional->left);
                }
                else if ($false_position === self::ASSIGNMENT_TO_LEFT) {
                    $var_name = StatementsChecker::getVarId($conditional->right);
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
        }
        else if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical || $conditional instanceof PhpParser\Node\Expr\BinaryOp\NotEqual) {
            $null_position = self::_hasNullVariable($conditional);
            $false_position = self::_hasFalseVariable($conditional);

            if ($null_position !== null) {
                if ($null_position === self::ASSIGNMENT_TO_RIGHT) {
                    $var_name = StatementsChecker::getVarId($conditional->left);
                }
                else if ($null_position === self::ASSIGNMENT_TO_LEFT) {
                    $var_name = StatementsChecker::getVarId($conditional->right);
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
                    $var_name = StatementsChecker::getVarId($conditional->left);
                }
                else if ($false_position === self::ASSIGNMENT_TO_LEFT) {
                    $var_name = StatementsChecker::getVarId($conditional->right);
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
        }
        else if (self::_hasNullCheck($conditional)) {
            $var_name = StatementsChecker::getVarId($conditional->args[0]->value);
            $if_types[$var_name] = 'null';
        }
        else if (self::_hasIsACheck($conditional)) {
            $var_name = StatementsChecker::getVarId($conditional->args[0]->value);
            $if_types[$var_name] = $conditional->args[1]->value->value;
        }
        else if (self::_hasArrayCheck($conditional)) {
            $var_name = StatementsChecker::getVarId($conditional->args[0]->value);
            $if_types[$var_name] = 'array';
        }
        else if ($conditional instanceof PhpParser\Node\Expr\Empty_) {
            $var_name = StatementsChecker::getVarId($conditional->expr);
            if ($var_name) {
                $if_types[$var_name] = 'empty';
            }
        }
        else if ($conditional instanceof PhpParser\Node\Expr\Isset_) {
            foreach ($conditional->vars as $isset_var) {
                $var_name = StatementsChecker::getVarId($isset_var);
                if ($var_name) {
                    $if_types[$var_name] = '!null';
                }
            }
        }
        else if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr) {
            $left_assertions = $this->getTypeAssertions($conditional->left, false);
            $right_assertions = $this->getTypeAssertions($conditional->right, false);

            $keys = array_merge(array_keys($left_assertions), array_keys($right_assertions));
            $keys = array_unique($keys);

            foreach ($keys as $key) {
                if (isset($left_assertions[$key]) && isset($right_assertions[$key])) {
                    $type_assertions = array_merge(explode('|', $left_assertions[$key]), explode('|', $right_assertions[$key]));
                    $if_types[$key] = implode('|', array_unique($type_assertions));
                }
                else if (isset($left_assertions[$key])) {
                    $if_types[$key] = $left_assertions[$key];
                }
                else {
                    $if_types[$key] = $right_assertions[$key];
                }
            }
        }
        else if ($check_boolean_and && $conditional instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd) {
            $left_assertions = $this->getTypeAssertions($conditional->left, $check_boolean_and);
            $right_assertions = $this->getTypeAssertions($conditional->right, $check_boolean_and);

            $keys = array_merge(array_keys($left_assertions), array_keys($right_assertions));
            $keys = array_unique($keys);

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
        }

        return $if_types;
    }

    protected function _getInstanceOfTypes(PhpParser\Node\Expr\Instanceof_ $stmt)
    {
        if ($stmt->class instanceof PhpParser\Node\Name) {
            if (!in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
                $instanceof_class = ClassChecker::getAbsoluteClassFromName($stmt->class, $this->_namespace, $this->_checker->getAliasedClasses());
                return $instanceof_class;

            } elseif ($stmt->class->parts === ['self']) {
                return $this->_absolute_class;
            }
        }

        return null;
    }

    protected static function _hasNullVariable(PhpParser\Node\Expr $conditional)
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

    protected static function _hasFalseVariable(PhpParser\Node\Expr $conditional)
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

    /**
     * @return bool
     */
    protected static function _hasNullCheck(PhpParser\Node\Expr $stmt)
    {
        if ($stmt instanceof PhpParser\Node\Expr\FuncCall && $stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_null']) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected static function _hasIsACheck(PhpParser\Node\Expr $stmt)
    {
        if ($stmt instanceof PhpParser\Node\Expr\FuncCall &&
            $stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_a'] &&
            $stmt->args[1]->value instanceof PhpParser\Node\Scalar\String_) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected static function _hasArrayCheck(PhpParser\Node\Expr $stmt)
    {
        if ($stmt instanceof PhpParser\Node\Expr\FuncCall && $stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_array']) {
            return true;
        }

        return false;
    }

    /**
     * Takes two arrays and consolidates them, removing null values from existing types where applicable
     *
     * @param  array  $new_types
     * @param  array  $existing_types
     * @return array|false
     */
    public static function reconcileKeyedTypes(array $new_types, array $existing_types, $file_name, $line_number)
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

            $result_type = self::reconcileTypes(
                (string) $new_types[$key],
                isset($existing_types[$key]) ? clone $existing_types[$key] : null,
                $key,
                $file_name,
                $line_number
            );

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
     * @param  Type\Union   $existing_var_types
     * @param  string       $key
     * @param  string       $file_name
     * @param  int          $line_number
     * @return Type\Union|false
     */
    public static function reconcileTypes($new_var_type, Type\Union $existing_var_type = null, $key = null, $file_name = null, $line_number = null)
    {
        $result_var_types = null;

        if (!$existing_var_type) {
            return Type::getMixed();
        }

        if ($new_var_type === 'mixed' && $existing_var_type->isMixed()) {
            return $existing_var_type;
        }

        if ($new_var_type === 'null') {
            return Type::getNull();
        }

        if ($new_var_type[0] === '!') {
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
                    if (ExceptionHandler::accepts(
                        new FailedTypeResolution('Cannot resolve types for ' . $key, $file_name, $line_number)
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
                $existing_var_type->types['false'] = Type::getFalse(false);
            }

            $existing_var_type->removeObjects();

            if (empty($existing_var_type->types)) {
                return Type::getNull();
            }

            return $existing_var_type;
        }

        return Type::parseString($new_var_type);
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
     * @param  array<UnionType>  $new_types
     * @param  array<UnionType>  $existing_types
     * @return array
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

    public static function reduceTypes(array $all_types)
    {
        if (in_array('mixed', $all_types)) {
            return ['mixed'];
        }

        $array_types = array_filter($all_types, function($type) {
            return preg_match('/^array(\<|$)/', $type);
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

    public static function negateTypes(array $types)
    {
        return array_map(function ($type) {
            if ($type === 'mixed') {
                return $type;
            }

            return $type[0] === '!' ? substr($type, 1) : '!' . $type;
        }, $types);
    }

    /**
     * @return array<string>
     */
    public static function tokenize($return_type)
    {
        $return_type_tokens = [''];
        $was_char = false;

        foreach (str_split($return_type) as $char) {
            if ($was_char) {
                $return_type_tokens[] = '';
            }

            if ($char === '<' || $char === '>' || $char === '|') {
                if ($return_type_tokens[count($return_type_tokens) - 1] === '') {
                    $return_type_tokens[count($return_type_tokens) - 1] = $char;
                }
                else {
                    $return_type_tokens[] = $char;
                }

                $was_char = true;
            }
            else {
                $return_type_tokens[count($return_type_tokens) - 1] .= $char;
                $was_char = false;
            }
        }

        return $return_type_tokens;
    }

    public static function convertSquareBrackets($type)
    {
        return preg_replace_callback(
            '/([a-zA-Z\<\>]+)((\[\])+)/',
            function ($matches) {
                $inner_type = $matches[1];

                $dimensionality = strlen($matches[2]) / 2;

                for ($i = 0; $i < $dimensionality; $i++) {
                    $inner_type = 'array<' . $inner_type . '>';
                }

                return $inner_type;
            },
            $type
        );
    }
}
