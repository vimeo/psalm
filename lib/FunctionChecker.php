<?php

namespace CodeInspector;

ini_set('xdebug.max_nesting_level', 500);

use \PhpParser;

class FunctionChecker
{
    protected $_function;
    protected $_declared_variables = [];
    protected $_aliased_classes = [];
    protected $_namespace;
    protected $_file_name;
    protected $_class;
    protected $_known_types = [];
    protected $_check_classes = true;
    protected $_check_variables = true;
    protected $_check_methods = true;
    protected $_check_consts = true;

    protected static $_method_return_types = [];
    protected static $_existing_methods = [];
    protected static $_reflection_functions = [];
    protected static $_method_comments = [];
    protected static $_method_files = [];
    protected static $_method_params = [];
    protected static $_method_param_types = [];
    protected static $_declaring_classes = [];
    protected static $_existing_static_vars = [];

    public function __construct(PhpParser\Node\FunctionLike $function, $namespace, array $aliased_classes, $file_name, $class_name = null, PhpParser\Node\Name $class_extends = null)
    {
        $this->_function = $function;
        $this->_aliased_classes = $aliased_classes;
        $this->_namespace = $namespace;
        $this->_class_name = $class_name;
        $this->_class_extends = $class_extends;
        $this->_file_name = $file_name;

        $this->_absolute_class = ClassChecker::getAbsoluteClass($this->_class_name, $this->_namespace, []);

        if ($function instanceof PhpParser\Node\Stmt\ClassMethod) {
            self::_registerMethod($function);
        }
    }

    public function check()
    {
        foreach ($this->_function->params as $param) {
            if ($param->type) {
                if (is_object($param->type)) {
                    if (!in_array($param->type->parts[0], ['self', 'parent']) && $this->_check_classes) {
                        ClassChecker::checkClassName($param->type, $this->_namespace, $this->_aliased_classes, $this->_file_name);
                    }
                }
            }

            $this->_declared_variables[$param->name] = 1;

            if ($param->type && is_object($param->type)) {
                $this->_known_types[$param->name] =
                    $param->type->parts === ['self'] ?
                        $this->_absolute_class :
                        ClassChecker::getAbsoluteClassFromName($param->type, $this->_namespace, $this->_aliased_classes);
            }
        }

        $types_in_scope = [];
        if ($this->_function->stmts) {
            $this->_checkStatements($this->_function->stmts, $types_in_scope);
        }
    }

    protected function _checkStatements(array $stmts, array &$types_in_scope)
    {
        $has_returned = false;

        foreach ($stmts as $stmt) {

            if ($has_returned) {
                throw new CodeException('Expressions after return', $this->_file_name, $stmt->getLine());
            }

            if ($stmt instanceof PhpParser\Node\Stmt\If_) {
                $this->_checkIf($stmt, $types_in_scope);
            }
            else if ($stmt instanceof PhpParser\Node\Stmt\TryCatch) {
                $this->_checkTryCatch($stmt, $types_in_scope);
            }
            else if ($stmt instanceof PhpParser\Node\Stmt\For_) {
                $this->_checkFor($stmt, $types_in_scope);
            }
            else if ($stmt instanceof PhpParser\Node\Stmt\Foreach_) {
                $this->_checkForeach($stmt, $types_in_scope);
            }
            else if ($stmt instanceof PhpParser\Node\Stmt\While_) {
                $this->_checkWhile($stmt, $types_in_scope);
            }
            else if ($stmt instanceof PhpParser\Node\Stmt\Do_) {
                $this->_checkDo($stmt, $types_in_scope);
            }
            else if ($stmt instanceof PhpParser\Node\Stmt\Unset_) {
                // do nothing
            }
            else if ($stmt instanceof PhpParser\Node\Stmt\Return_) {
                $has_returned = true;
                $this->_checkReturn($stmt, $types_in_scope);
            }
            else if ($stmt instanceof PhpParser\Node\Stmt\Throw_) {
                $this->_checkThrow($stmt, $types_in_scope);
            }
            else if ($stmt instanceof PhpParser\Node\Stmt\Switch_) {
                $this->_checkSwitch($stmt, $types_in_scope);
            }
            else if ($stmt instanceof PhpParser\Node\Stmt\Break_) {
                // do nothing
            }
            else if ($stmt instanceof PhpParser\Node\Stmt\Continue_) {
                // do nothing
            }
            else if ($stmt instanceof PhpParser\Node\Stmt\Static_) {
                foreach ($stmt->vars as $var) {
                    if ($var instanceof PhpParser\Node\Stmt\StaticVar) {
                        if (is_string($var->name)) {
                            if ($this->_check_variables) {
                                $this->_declared_variables[$var->name] = 1;
                            }
                        }
                        else {
                            $this->_checkExpression($var->name, $types_in_scope);
                        }

                        if ($var->default) {
                            $this->_checkExpression($var->default, $types_in_scope);
                        }
                    }
                    else {
                        $this->_checkExpression($var, $types_in_scope);
                    }
                }
            }
            else if ($stmt instanceof PhpParser\Node\Stmt\Echo_) {
                foreach ($stmt->exprs as $expr) {
                    $this->_checkExpression($expr, $types_in_scope);
                }
            }
            else if ($stmt instanceof PhpParser\Node\Stmt\Function_) {
                $function_checker = new FunctionChecker($stmt, $this->_namespace, $this->_aliased_classes, $this->_file_name, $this->_class_name, $this->_class_extends);
                $function_checker->check();
            }
            else if ($stmt instanceof PhpParser\Node\Expr) {
                $this->_checkExpression($stmt, $types_in_scope);
            }
            else {
                var_dump('Unrecognised statement');
                var_dump($stmt);
            }
        }
    }

    protected function _checkIf(PhpParser\Node\Stmt\If_ $stmt, array &$types_in_scope)
    {
        $this->_checkCondition($stmt->cond, $types_in_scope);

        $instanceof_class = null;

        $if_types = [];

        if ($stmt->cond instanceof PhpParser\Node\Expr\Instanceof_) {
            $if_types = $this->_getInstanceOfTypes($stmt->cond);
        }

        $this->_checkStatements($stmt->stmts, array_merge($types_in_scope, $if_types));

        foreach ($stmt->elseifs as $elseif) {
            $this->_checkElseIf($elseif, $types_in_scope);
        }

        if ($stmt->else) {
            $this->_checkElse($stmt->else, $types_in_scope);
        }
    }

    protected function _checkElseIf(PhpParser\Node\Stmt\ElseIf_ $stmt, array &$types_in_scope)
    {
        $this->_checkCondition($stmt->cond, $types_in_scope);

        $if_types = [];

        if ($stmt->cond instanceof PhpParser\Node\Expr\Instanceof_) {
            $if_types = $this->_getInstanceOfTypes($stmt->cond);
        }

        $this->_checkStatements($stmt->stmts, array_merge($types_in_scope, $if_types));
    }

    protected function _checkElse(PhpParser\Node\Stmt\Else_ $stmt, array &$types_in_scope)
    {
        $this->_checkStatements($stmt->stmts, $types_in_scope);
    }

    protected function _checkCondition(PhpParser\Node\Expr $stmt, array &$types_in_scope)
    {
        $this->_checkExpression($stmt, $types_in_scope);
    }

    protected function _getInstanceOfTypes(PhpParser\Node\Expr $stmt)
    {
        $if_types = [];

        if ($stmt->expr instanceof PhpParser\Node\Expr\Variable && is_string($stmt->expr->name) && $stmt->class instanceof PhpParser\Node\Name) {
            if (!in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
                $instanceof_class = ClassChecker::getAbsoluteClassFromName($stmt->class, $this->_namespace, $this->_aliased_classes);
                $if_types[$stmt->expr->name] = $instanceof_class;
            }
            else if ($stmt->class->parts === ['self']) {
                $if_types[$stmt->expr->name] = $this->_absolute_class;
            }
        }

        return $if_types;
    }

    protected function _checkExpression(PhpParser\Node\Expr $stmt, array &$types_in_scope = [])
    {
        if ($stmt instanceof PhpParser\Node\Expr\Variable) {
            $this->_checkVariable($stmt, $types_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\Assign) {
            $this->_checkAssignment($stmt, $types_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\AssignOp) {
            $this->_checkAssignmentOperation($stmt, $types_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\MethodCall) {
            $this->_checkMethodCall($stmt, $types_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\StaticCall) {
            $this->_checkStaticCall($stmt, $types_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\ConstFetch) {
            $this->_checkConstFetch($stmt);
        }
        else if ($stmt instanceof PhpParser\Node\Scalar\String_) {
            // do nothing
        }
        else if ($stmt instanceof PhpParser\Node\Scalar\EncapsedStringPart) {
            // do nothing
        }
        else if ($stmt instanceof PhpParser\Node\Scalar\MagicConst) {
            // do nothing
        }
        else if ($stmt instanceof PhpParser\Node\Scalar\LNumber) {
            // do nothing
        }
        else if ($stmt instanceof PhpParser\Node\Scalar\DNumber) {
            // do nothing
        }
        else if ($stmt instanceof PhpParser\Node\Expr\UnaryMinus) {
            $this->_checkExpression($stmt->expr);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\Isset_) {
            // do nothing
        }
        else if ($stmt instanceof PhpParser\Node\Expr\ClassConstFetch) {
            $this->_checkClassConstFetch($stmt, $types_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\PropertyFetch) {
            $this->_checkPropertyFetch($stmt, $types_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\StaticPropertyFetch) {
            $this->_checkStaticPropertyFetch($stmt);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\BitwiseNot) {
            $this->_checkExpression($stmt->expr, $types_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            $this->_checkExpression($stmt->left, $types_in_scope);
            $this->_checkExpression($stmt->right, $types_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\PostInc) {
            $this->_checkExpression($stmt->var);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\PostDec) {
            $this->_checkExpression($stmt->var);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\PreInc) {
            $this->_checkExpression($stmt->var);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\PreDec) {
            $this->_checkExpression($stmt->var);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\New_) {
            $this->_checkNew($stmt, $types_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\Array_) {
            $this->_checkArray($stmt, $types_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Scalar\Encapsed) {
            $this->_checkEncapsulatedString($stmt, $types_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\FuncCall) {
            $this->_checkFunctionCall($stmt, $types_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\Ternary) {
            $this->_checkTernary($stmt, $types_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\BooleanNot) {
            $this->_checkBooleanNot($stmt, $types_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\Empty_) {
            $this->_checkEmpty($stmt, $types_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\Closure) {
            $closure_checker = new ClosureChecker($stmt, $this->_namespace, $this->_aliased_classes, $this->_file_name, $this->_class_name, $this->_class_extends);
            $closure_checker->check();
        }
        else if ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            $this->_checkArrayAccess($stmt, $types_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\Cast\Int_) {
            $this->_checkExpression($stmt->expr, $types_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\Cast\Double) {
            $this->_checkExpression($stmt->expr, $types_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\Cast\Bool_) {
            $this->_checkExpression($stmt->expr, $types_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\Cast\String_) {
            $this->_checkExpression($stmt->expr, $types_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\Cast\Object_) {
            $this->_checkExpression($stmt->expr, $types_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\Cast\Array_) {
            $this->_checkExpression($stmt->expr, $types_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\Clone_) {
            $this->_checkExpression($stmt->expr, $types_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\Instanceof_) {
            $this->_checkExpression($stmt->expr, $types_in_scope);

            if ($stmt->class instanceof PhpParser\Node\Name && !in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
                if ($this->_check_classes) {
                    ClassChecker::checkClassName($stmt->class, $this->_namespace, $this->_aliased_classes, $this->_file_name);
                }
            }
        }
        else if ($stmt instanceof PhpParser\Node\Expr\Exit_) {
            // do nothing
        }
        else if ($stmt instanceof PhpParser\Node\Expr\Include_) {
            $this->_checkExpression($stmt->expr);
            $this->_check_classes = false;
            $this->_check_variables = false;
        }
        else if ($stmt instanceof PhpParser\Node\Expr\Eval_) {
            $this->_checkExpression($stmt->expr);
            $this->_check_classes = false;
            $this->_check_variables = false;
        }
        else if ($stmt instanceof PhpParser\Node\Expr\AssignRef) {
            if ($stmt->var instanceof PhpParser\Node\Expr\Variable) {
                $this->_declared_variables[$stmt->var->name] = 1;
            }
            else {
                $this->_checkExpression($stmt->var, $types_in_scope);
            }

            $this->_checkExpression($stmt->expr, $types_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\ErrorSuppress) {
            // do nothing
        }
        else if ($stmt instanceof PhpParser\Node\Expr\ShellExec) {
            throw new CodeException('Use of shell_exec', $this->_file_name, $stmt->getLine());
        }
        else {
            var_dump('Unrecognised expression');
            var_dump($stmt);
        }
    }

    protected function _checkVariable(PhpParser\Node\Expr\Variable $stmt, array &$types_in_scope, $method_id = null, $argument_offset = -1)
    {
        if (!$this->_check_variables) {
            return;
        }

        if (in_array($stmt->name, ['this', '_SERVER', '_GET', '_POST', '_COOKIE', '_REQUEST', '_FILES', '_ENV', 'GLOBALS'])) {
            return;
        }

        if (!is_string($stmt->name)) {
            $this->_checkExpression($stmt->name);
            return;
        }

        if (!array_key_exists($stmt->name, $this->_declared_variables)) {
            if ($method_id) {
                if (strpos($method_id, '::') !== false) {
                    if (self::_isPassedByRef($method_id, $argument_offset)) {
                        $this->_declared_variables[$stmt->name] = 1;
                        return;
                    }
                }
                else {
                    $reflection_parameters = (new \ReflectionFunction($method_id))->getParameters();

                    // if value is passed by reference
                    if ($argument_offset < count($reflection_parameters) && $reflection_parameters[$argument_offset]->isPassedByReference()) {
                        $this->_declared_variables[$stmt->name] = 1;
                        return;
                    }
                }
            }

            throw new CodeException('Cannot find referenced variable ' . $stmt->name, $this->_file_name, $stmt->getLine());
        }
        else {
            if (isset($this->_known_types[$stmt->name])) {
                $stmt->returnType = $this->_known_types[$stmt->name];
            }

            if (isset($types_in_scope[$stmt->name])) {
                $stmt->returnType = $types_in_scope[$stmt->name];
            }
        }
    }

    protected function _checkPropertyFetch(PhpParser\Node\Expr\PropertyFetch $stmt, array &$types_in_scope)
    {
        if ($stmt->var instanceof PhpParser\Node\Expr\Variable) {
            if ($stmt->var->name === 'this') {

            }
            else {
                $this->_checkVariable($stmt->var, $types_in_scope);
            }
        }
        else {
            $this->_checkExpression($stmt->var, $types_in_scope);
        }
    }

    protected function _checkNew(PhpParser\Node\Expr\New_ $stmt, array &$types_in_scope)
    {
        $absolute_class = null;

        if ($stmt->class instanceof PhpParser\Node\Name && !in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
            if ($this->_check_classes) {
                ClassChecker::checkClassName($stmt->class, $this->_namespace, $this->_aliased_classes, $this->_file_name);

                $absolute_class = ClassChecker::getAbsoluteClassFromName($stmt->class, $this->_namespace, $this->_aliased_classes);
                $stmt->returnType = $absolute_class;
            }
        }

        if ($absolute_class) {
            $method_id = $absolute_class . '::__construct';

            $this->_checkMethodParams($stmt->args, $method_id, $types_in_scope);
        }
    }

    protected function _checkArray(PhpParser\Node\Expr\Array_ $stmt, array &$types_in_scope)
    {
        foreach ($stmt->items as $item) {
            if ($item->key) {
                $this->_checkExpression($item->key, $types_in_scope);
            }

            $this->_checkExpression($item->value, $types_in_scope);
        }

        $stmt->returnType = 'array';
    }

    protected function _checkTryCatch(PhpParser\Node\Stmt\TryCatch $stmt, array &$types_in_scope)
    {
        $this->_checkStatements($stmt->stmts, $types_in_scope);

        foreach ($stmt->catches as $catch) {
            $this->_declared_variables[$catch->var] = 1;
            $this->_known_types[$catch->var] = ClassChecker::getAbsoluteClassFromName($catch->type, $this->_namespace, $this->_aliased_classes);

            if ($this->_check_classes) {
                ClassChecker::checkClassName($catch->type, $this->_namespace, $this->_aliased_classes, $this->_file_name);
            }

            $this->_checkStatements($catch->stmts, $types_in_scope);
        }

        if ($stmt->finallyStmts) {
            $this->_checkStatements($stmt->finallyStmts, $types_in_scope);
        }
    }

    protected function _checkFor(PhpParser\Node\Stmt\For_ $stmt, array &$types_in_scope)
    {
        foreach ($stmt->init as $init) {
            $this->_checkExpression($init, $types_in_scope);
        }

        foreach ($stmt->cond as $condition) {
            $this->_checkCondition($init, $types_in_scope);
        }

        foreach ($stmt->loop as $expr) {
            $this->_checkExpression($expr);
        }

        $this->_checkStatements($stmt->stmts, $types_in_scope);
    }

    protected function _checkForeach(PhpParser\Node\Stmt\Foreach_ $stmt, array &$types_in_scope)
    {
        $this->_checkExpression($stmt->expr, $types_in_scope);

        if ($stmt->keyVar) {
            $this->_declared_variables[$stmt->keyVar->name] = 1;
        }

        if ($stmt->valueVar) {
            $this->_declared_variables[$stmt->valueVar->name] = 1;
        }

        $this->_checkStatements($stmt->stmts, $types_in_scope);
    }

    protected function _checkWhile(PhpParser\Node\Stmt\While_ $stmt, array &$types_in_scope)
    {
        $this->_checkCondition($stmt->cond, $types_in_scope);

        $this->_checkStatements($stmt->stmts, $types_in_scope);
    }

    protected function _checkDo(PhpParser\Node\Stmt\Do_ $stmt, array &$types_in_scope)
    {
        $this->_checkStatements($stmt->stmts, $types_in_scope);

        $this->_checkCondition($stmt->cond, $types_in_scope);
    }

    protected function _checkAssignment(PhpParser\Node\Expr\Assign $stmt, array &$types_in_scope)
    {
        if ($stmt->var instanceof PhpParser\Node\Expr\Variable && is_string($stmt->var->name)) {
            $this->_declared_variables[$stmt->var->name] = 1;
        }
        else if ($stmt->var instanceof PhpParser\Node\Expr\List_) {
            foreach ($stmt->var->vars as $var) {
                if ($var) {
                    $this->_declared_variables[$var->name] = 1;
                }
            }
        }
        // if it's an array assignment
        else if ($stmt->var instanceof PhpParser\Node\Expr\ArrayDimFetch && $stmt->var->var instanceof PhpParser\Node\Expr\Variable) {
            $this->_declared_variables[$stmt->var->var->name] = 1;
        }

        $this->_checkExpression($stmt->expr, $types_in_scope);

        if ($stmt->var instanceof PhpParser\Node\Expr\Variable && is_string($stmt->var->name)) {
            $comments = [];
            $doc_comment = $stmt->getDocComment();

            if ($doc_comment) {
                $comments = self::_parseDocComment($doc_comment);
            }

            if ($comments && isset($comments['specials']['var'][0])) {
                $type = explode(' ', $comments['specials']['var'][0])[0];

                if ($type[0] === strtoupper($type[0])) {
                    $this->_known_types[$stmt->var->name] = ClassChecker::getAbsoluteClass($type, $this->_namespace, $this->_aliased_classes);
                }
            }
            else if (isset($stmt->expr->returnType)) {
                $var_name = $stmt->var->name;

                if ($stmt->expr->returnType === 'null') {
                    if (isset($this->_known_types[$var_name])) {
                        $this->_known_types[$var_name] = 'mixed';
                    }
                }
                else if (isset($this->_known_types[$var_name])) {
                    $existing_type = $this->_known_types[$var_name];

                    if ($existing_type !== 'mixed') {
                        if (is_a($existing_type, $stmt->expr->returnType, true)) {
                            // downcast
                            $this->_known_types[$var_name] = $stmt->expr->returnType;
                        }
                        else if (is_a($stmt->expr->returnType, $existing_type, true)) {
                            if (!isset($types_in_scope[$var_name])) {
                                $types_in_scope[$var_name] = $stmt->expr->returnType;
                            }
                        }
                        else {
                            $this->_known_types[$stmt->var->name] = 'mixed';
                        }
                    }
                }
                else {
                    $this->_known_types[$stmt->var->name] = $stmt->expr->returnType;
                }
            }
        }
    }

    protected function _checkAssignmentOperation(PhpParser\Node\Expr\AssignOp $stmt, array &$types_in_scope)
    {
        $this->_checkExpression($stmt->var, $types_in_scope);
        $this->_checkExpression($stmt->expr, $types_in_scope);
    }

    protected function _checkMethodCall(PhpParser\Node\Expr\MethodCall $stmt, array &$types_in_scope)
    {
        $this->_checkExpression($stmt->var, $types_in_scope);

        $absolute_class = null;
        $method_id = null;

        if ($stmt->var instanceof PhpParser\Node\Expr\Variable) {
            if ($stmt->var->name === 'this') {
                if (!$this->_class_name) {
                    throw new CodeException('Use of $this in non-class context', $this->_file_name, $stmt->getLine());
                }

                $absolute_class = $this->_absolute_class;
            }
            else if (!is_string($stmt->var->name)) {
                $this->_checkExpression($stmt->var->name, $types_in_scope);
            }
            else if (isset($this->_known_types[$stmt->var->name])) {
                if (isset($types_in_scope[$stmt->var->name])) {
                    $absolute_class = $types_in_scope[$stmt->var->name];
                }
                else {
                    $absolute_class = $this->_known_types[$stmt->var->name];
                }
            }
        }
        else if ($stmt->var instanceof PhpParser\Node\Expr) {
            $this->_checkExpression($stmt->var, $types_in_scope);
        }

        if (!$absolute_class && isset($stmt->var->returnType)) {
            $absolute_class = $stmt->var->returnType;
        }

        if ($absolute_class && $absolute_class[0] === strtoupper($absolute_class[0]) && $this->_check_methods && is_string($stmt->name) && !method_exists($absolute_class, '__call')) {
            $method_id = $absolute_class . '::' . $stmt->name;

            if (!self::_methodExists($method_id)) {
                throw new CodeException('Method ' . $method_id . ' does not exist', $this->_file_name, $stmt->getLine());
            }

            $return_types = $this->_getMethodReturnTypes($method_id);

            if ($return_types) {
                // @todo should work for multiple types
                $return_type = $return_types[0];

                $stmt->returnType = $return_type;
            }
        }

        $this->_checkMethodParams($stmt->args, $method_id, $types_in_scope);
    }

    protected function _checkStaticCall(PhpParser\Node\Expr\StaticCall $stmt, array &$types_in_scope)
    {
        if ($stmt->class instanceof PhpParser\Node\Expr\Variable || $stmt->class instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            // this is when calling $some_class::staticMethod() - which is a shitty way of doing things
            // because it can't be statically type-checked
            return;
        }

        $method_id = null;
        $absolute_class = null;

        if (count($stmt->class->parts) === 1 && in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
            if ($stmt->class->parts[0] === 'parent') {
                $absolute_class = ClassChecker::getAbsoluteClassFromName($this->_class_extends, $this->_namespace, $this->_aliased_classes);
            }
            else {
                $absolute_class = ($this->_namespace ? '\\' : '') . $this->_namespace . '\\' . $this->_class_name;
            }
        }
        else if ($this->_check_classes) {
            ClassChecker::checkClassName($stmt->class, $this->_namespace, $this->_aliased_classes, $this->_file_name);
            $absolute_class = ClassChecker::getAbsoluteClassFromName($stmt->class, $this->_namespace, $this->_aliased_classes);
        }

        if ($absolute_class && $this->_check_methods && is_string($stmt->name) && !method_exists($absolute_class, '__callStatic')) {
            $method_id = $absolute_class . '::' . $stmt->name;

            if (!self::_methodExists($method_id)) {
                throw new CodeException('Method ' . $method_id . ' does not exist', $this->_file_name, $stmt->getLine());
            }

            $return_types = $this->_getMethodReturnTypes($method_id);

            if ($return_types) {
                // @todo should work for multiple types
                $return_type = $return_types[0];

                $stmt->returnType = $return_type;
            }
        }

        $this->_checkMethodParams($stmt->args, $method_id, $types_in_scope);
    }

    protected function _checkMethodParams(array $args, $method_id, array &$types_in_scope)
    {
        foreach ($args as $i => $arg) {
            if ($arg->value instanceof PhpParser\Node\Expr\Variable) {
                if ($method_id) {
                    $this->_checkVariable($arg->value, $types_in_scope, $method_id, $i);
                }
                else if (is_string($arg->value->name)) {
                    // we don't know if it exists, assume it's passed by reference
                    $this->_declared_variables[$arg->value->name] = 1;
                }
            }
            else {
                $this->_checkExpression($arg->value, $types_in_scope);
            }

            if ($method_id && isset($arg->value->returnType)) {
                if (!self::_isCorrectType($arg->value->returnType, $method_id, $i)) {
                    throw new CodeException('Argument ' . ($i + 1) . ' of ' . $method_id . ' has incorrect type of ' . $arg->value->returnType, $this->_file_name, $arg->value->getLine());
                }
            }
        }
    }

    protected function _checkConstFetch(PhpParser\Node\Expr\ConstFetch $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name) {
            if ($stmt->name->parts === ['null']) {
                $stmt->returnType = 'null';
            }
        }
    }

    protected function _checkClassConstFetch(PhpParser\Node\Expr\ClassConstFetch $stmt, $types_in_scope)
    {
        if ($this->_check_consts && $stmt->class instanceof PhpParser\Node\Name && $stmt->class->parts !== ['static']) {
            if ($stmt->class->parts === ['self']) {
                $absolute_class = $this->_absolute_class;
            }
            else {
                $absolute_class = ClassChecker::getAbsoluteClassFromName($stmt->class, $this->_namespace, $this->_aliased_classes);
            }

            $const_id = $absolute_class . '::' . $stmt->name;

            if (!defined($const_id)) {
                throw new CodeException('Const ' . $const_id . ' is not defined', $this->_file_name, $stmt->getLine());
            }
        }
        else if ($stmt->class instanceof PhpParser\Node\Expr) {
            $this->_checkExpression($stmt->class, $types_in_scope);
        }
    }

    protected function _checkStaticPropertyFetch(PhpParser\Node\Expr\StaticPropertyFetch $stmt)
    {
        if ($stmt->class instanceof PhpParser\Node\Expr\Variable || $stmt->class instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            // this is when calling $some_class::staticMethod() - which is a shitty way of doing things
            // because it can't be statically type-checked
            return;
        }

        $method_id = null;
        $absolute_class = null;

        if (count($stmt->class->parts) === 1 && in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
            if ($stmt->class->parts[0] === 'parent') {
                $absolute_class = ClassChecker::getAbsoluteClassFromName($this->_class_extends, $this->_namespace, $this->_aliased_classes);
            }
            else {
                $absolute_class = ($this->_namespace ? '\\' : '') . $this->_namespace . '\\' . $this->_class_name;
            }
        }
        else if ($this->_check_classes) {
            ClassChecker::checkClassName($stmt->class, $this->_namespace, $this->_aliased_classes, $this->_file_name);
            $absolute_class = ClassChecker::getAbsoluteClassFromName($stmt->class, $this->_namespace, $this->_aliased_classes);
        }

        if ($absolute_class && $this->_check_variables && is_string($stmt->name)) {
            $var_id = $absolute_class . '::$' . $stmt->name;

            if (!self::_staticVarExists($var_id)) {
                throw new CodeException('Static variable ' . $var_id . ' does not exist', $this->_file_name, $stmt->getLine());
            }
        }
    }

    protected function _checkReturn(PhpParser\Node\Stmt\Return_ $stmt, array &$types_in_scope)
    {
        if ($stmt->expr) {
            $this->_checkExpression($stmt->expr, $types_in_scope);
        }
    }

    protected function _checkTernary(PhpParser\Node\Expr\Ternary $stmt, array &$types_in_scope)
    {
        $this->_checkCondition($stmt->cond, $types_in_scope);

        $if_types = [];

        if ($stmt->cond instanceof PhpParser\Node\Expr\Instanceof_) {
            $if_types = $this->_getInstanceOfTypes($stmt->cond);
        }

        if ($stmt->if) {
            $this->_checkExpression($stmt->if, array_merge($types_in_scope, $if_types));
        }

        $this->_checkExpression($stmt->else, $types_in_scope);
    }

    protected function _checkBooleanNot(PhpParser\Node\Expr\BooleanNot $stmt, array &$types_in_scope)
    {
        $this->_checkExpression($stmt->expr, $types_in_scope);
    }

    protected function _checkEmpty(PhpParser\Node\Expr\Empty_ $stmt, array &$types_in_scope)
    {
        $this->_checkExpression($stmt->expr, $types_in_scope);
    }

    protected function _checkThrow(PhpParser\Node\Stmt\Throw_ $stmt, array &$types_in_scope)
    {
        $this->_checkExpression($stmt->expr, $types_in_scope);
    }

    protected function _checkSwitch(PhpParser\Node\Stmt\Switch_ $stmt, array &$types_in_scope)
    {
        $this->_checkCondition($stmt->cond, $types_in_scope);

        foreach ($stmt->cases as $case) {
            if ($case->cond) {
                $this->_checkCondition($case->cond, $types_in_scope);
            }

            $this->_checkStatements($case->stmts, $types_in_scope);
        }
    }

    protected function _checkFunctionCall(PhpParser\Node\Expr\FuncCall $stmt, array &$types_in_scope)
    {
        $method = $stmt->name;

        if ($method instanceof PhpParser\Node\Name) {
            if ($method->parts === ['method_exists']) {
                $this->_check_methods = false;
            }
            else if ($method->parts === ['defined']) {
                $this->_check_consts = false;
            }
        }

        foreach ($stmt->args as $i => $arg) {
            if ($arg->value instanceof PhpParser\Node\Expr\Variable) {
                $this->_checkVariable($arg->value, $types_in_scope, $method, $i);
            }
            else {
                $this->_checkExpression($arg->value, $types_in_scope);
            }
        }
    }

    protected function _checkArrayAccess(PhpParser\Node\Expr\ArrayDimFetch $stmt, array &$types_in_scope)
    {
        $this->_checkExpression($stmt->var, $types_in_scope);
        if ($stmt->dim) {
            $this->_checkExpression($stmt->dim, $types_in_scope);
        }
    }

    protected function _checkEncapsulatedString(PhpParser\Node\Scalar\Encapsed $stmt, array &$types_in_scope)
    {
        foreach ($stmt->parts as $part)
        {
            $this->_checkExpression($part, $types_in_scope);
        }
    }

    protected function _registerMethod(PhpParser\Node\Stmt\ClassMethod $method)
    {
        $method_id = $this->_absolute_class . '::' . $method->name;

        if (!isset(self::$_method_return_types[$method_id])) {
            $comments = self::_parseDocComment($method->getDocComment());

            $return_types = [];

            if (isset($comments['specials']['return'])) {
                $return_blocks = explode(' ', $comments['specials']['return'][0]);
                foreach ($return_blocks as $block) {
                    if ($block) {
                        if ($block && preg_match('/^\\\?[A-Za-z0-9|\\\]+[A-Za-z0-9]$/', $block)) {
                            $return_types = explode('|', $block);
                            break;
                        }
                    }
                }
            }

            $return_types = array_filter($return_types, function($entry) {
                return !empty($entry) && $entry !== '[type]';
            });

            foreach ($return_types as &$return_type) {
                if ($return_type[0] === strtoupper($return_type[0])) {
                    if ($return_type === '$this') {
                        $return_type = $this->_absolute_class;
                    }
                    else {
                        $return_type = ClassChecker::getAbsoluteClass($return_type, $this->_namespace, $this->_aliased_classes);
                    }
                }
            }

            self::$_method_return_types[$method_id] = $return_types;
        }

        if (!isset(self::$_method_params[$method_id])) {
            self::$_method_params[$method_id] = [];

            foreach ($method->params as $param) {
                self::$_method_params[$method_id][] = $param->byRef;
            }
        }
    }

    protected static function _methodExists($method_id)
    {
        if (isset(self::$_existing_methods[$method_id])) {
            return true;
        }

        try {
            new \ReflectionMethod($method_id);
            self::$_existing_methods[$method_id] = 1;
            return true;
        }
        catch (\ReflectionException $e) {
            return false;
        }
    }

    protected static function _staticVarExists($var_id)
    {
        if (isset(self::$_existing_static_vars[$var_id])) {
            return true;
        }

        $absolute_class = explode('::', $var_id)[0];

        $reflection_class = new \ReflectionClass($absolute_class);

        $static_properties = $reflection_class->getStaticProperties();

        foreach ($static_properties as $property => $value) {
            self::$_existing_static_vars[$absolute_class . '::$' . $property] = 1;
        }

        return isset(self::$_existing_static_vars[$var_id]);
    }

    protected function _getMethodReturnTypes($method_id)
    {
        if (isset(self::$_method_return_types[$method_id])) {
            return self::$_method_return_types[$method_id];
        }

        if (!isset(self::$_method_comments[$method_id])) {
            self::_extractReflectionMethodInfo($method_id);
        }

        $comments = self::_parseDocComment(self::$_method_comments[$method_id]);

        $absolute_class = explode('::', $method_id)[0];

        $return_types = [];

        if (isset($comments['specials']['return'])) {
            $return_blocks = explode(' ', $comments['specials']['return'][0]);
            foreach ($return_blocks as $block) {
                if ($block && preg_match('/^\\\?[A-Za-z0-9|\\\]+[A-Za-z0-9]$/', $block)) {
                    $return_types = explode('|', $block);
                    break;
                }
            }
        }

        $return_types = array_filter($return_types, function($entry) {
            return !empty($entry) && $entry !== '[type]';
        });

        if ($return_types) {
            foreach ($return_types as &$return_type) {
                if ($return_type[0] === strtoupper($return_type[0])) {
                    if ($return_type === '$this') {
                        $return_type = $absolute_class;
                    }
                    else if (self::$_declaring_classes[$method_id] === $this->_absolute_class) {
                        $return_type = ClassChecker::getAbsoluteClass($return_type, $this->_namespace, $this->_aliased_classes);
                    }
                    else {
                        $return_type = FileChecker::getAbsoluteClassInFile($return_type, self::$_method_files[$method_id]);
                    }
                }
            }
        }

        self::$_method_return_types[$method_id] = $return_types;

        return $return_types;
    }

    /**
     * Parse a docblock comment into its parts.
     *
     * Taken from advanced api docmaker
     * Which was taken from https://github.com/facebook/libphutil/blob/master/src/parser/docblock/PhutilDocblockParser.php
     *
     * @return array Array of the main comment and specials
     */
    public static function _parseDocComment($docblock)
    {
        // Strip off comments.
        $docblock = trim($docblock);
        $docblock = preg_replace('@^/\*\*@', '', $docblock);
        $docblock = preg_replace('@\*/$@', '', $docblock);
        $docblock = preg_replace('@^\s*\*@m', '', $docblock);

        // Normalize multi-line @specials.
        $lines = explode("\n", $docblock);
        $last = false;
        foreach ($lines as $k => $line) {
            if (preg_match('/^\s?@\w/i', $line)) {
                $last = $k;
            }
            else if (preg_match('/^\s*$/', $line)) {
                $last = false;
            }
            else if ($last !== false) {
                $lines[$last] = rtrim($lines[$last]).' '.trim($line);
                unset($lines[$k]);
            }
        }
        $docblock = implode("\n", $lines);

        $special = array();

        // Parse @specials.
        $matches = null;
        $have_specials = preg_match_all('/^\s?@(\w+)\s*([^\n]*)/m', $docblock, $matches, PREG_SET_ORDER);
        if ($have_specials) {
            $docblock = preg_replace('/^\s?@(\w+)\s*([^\n]*)/m', '', $docblock);
            foreach ($matches as $match) {
                list($_, $type, $data) = $match;

                if (empty($special[$type])) {
                    $special[$type] = array();
                }

                $special[$type][] = $data;
            }
        }

        $docblock = str_replace("\t", '  ', $docblock);

        // Smush the whole docblock to the left edge.
        $min_indent = 80;
        $indent = 0;
        foreach (array_filter(explode("\n", $docblock)) as $line) {
            for ($ii = 0; $ii < strlen($line); $ii++) {
                if ($line[$ii] != ' ') {
                    break;
                }
                $indent++;
            }

            $min_indent = min($indent, $min_indent);
        }

        $docblock = preg_replace('/^' . str_repeat(' ', $min_indent) . '/m', '', $docblock);
        $docblock = rtrim($docblock);

        // Trim any empty lines off the front, but leave the indent level if there
        // is one.
        $docblock = preg_replace('/^\s*\n/', '', $docblock);

        return array('description' => $docblock, 'specials' => $special);
    }

    protected static function _isPassedByRef($method_id, $arg_offset)
    {
        if (!isset(self::$_method_params[$method_id])) {
            self::_extractReflectionMethodInfo($method_id);
        }

        return $arg_offset < count(self::$_method_params[$method_id]) && self::$_method_params[$method_id][$arg_offset];
    }

    protected static function _isCorrectType($return_type, $method_id, $arg_offset)
    {
        if ($return_type === 'mixed' || $return_type === 'null') {
            return true;
        }

        if (!isset(self::$_method_param_types[$method_id])) {
            self::_extractReflectionMethodInfo($method_id);
        }

        if ($arg_offset >= count(self::$_method_param_types[$method_id])) {
            return true;
        }

        $expected_type = self::$_method_param_types[$method_id][$arg_offset];

        if (!$expected_type) {
            return true;
        }

        if ($return_type === $expected_type) {
            return true;
        }

        return is_a($return_type, $expected_type, true) || is_a($expected_type, $return_type, true);
    }

    protected static function _extractReflectionMethodInfo($method_id)
    {
        $method = new \ReflectionMethod($method_id);
        $params = $method->getParameters();

        self::$_method_params[$method_id] = [];
        self::$_method_param_types[$method_id] = [];
        foreach ($params as $param) {
            self::$_method_params[$method_id][] = $param->isPassedByReference();
            self::$_method_param_types[$method_id][] = $param->getClass() ? '\\' . $param->getClass()->getName() : ($param->isArray() ? 'array' : null);
        }

        self::$_method_comments[$method_id] = $method->getDocComment() ?: '';
        self::$_method_files[$method_id] = $method->getFileName();
        self::$_declaring_classes[$method_id] = '\\' . $method->getDeclaringClass()->name;
    }

    /**
     * @param  string $function
     * @return \ReflectionFunction
     */
    protected static function _getReflectionFunction($function)
    {
        if (!isset(self::$_reflection_functions[$function])) {
            self::$_reflection_functions[$function] = new \ReflectionFunction($function);
        }

        return self::$_reflection_functions[$function];
    }
}
