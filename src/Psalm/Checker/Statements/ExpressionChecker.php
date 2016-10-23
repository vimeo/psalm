<?php
namespace Psalm\Checker\Statements;

use PhpParser;
use Psalm\Checker\ClassChecker;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\ClosureChecker;
use Psalm\Checker\CommentChecker;
use Psalm\Checker\FunctionChecker;
use Psalm\Checker\FunctionLikeChecker;
use Psalm\Checker\InterfaceChecker;
use Psalm\Checker\MethodChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\TraitChecker;
use Psalm\Checker\TypeChecker;
use Psalm\Config;
use Psalm\Context;
use Psalm\IssueBuffer;
use Psalm\Issue\FailedTypeResolution;
use Psalm\Issue\ForbiddenCode;
use Psalm\Issue\InvalidArrayAccess;
use Psalm\Issue\InvalidArrayAssignment;
use Psalm\Issue\InvalidArgument;
use Psalm\Issue\InvalidPropertyAssignment;
use Psalm\Issue\InvalidScalarArgument;
use Psalm\Issue\InvalidScope;
use Psalm\Issue\InvalidStaticVariable;
use Psalm\Issue\InvalidPropertyFetch;
use Psalm\Issue\InvisibleProperty;
use Psalm\Issue\MixedArgument;
use Psalm\Issue\MixedArrayOffset;
use Psalm\Issue\MixedMethodCall;
use Psalm\Issue\MixedPropertyAssignment;
use Psalm\Issue\MixedPropertyFetch;
use Psalm\Issue\MixedStringOffsetAssignment;
use Psalm\Issue\MissingPropertyDeclaration;
use Psalm\Issue\NoInterfaceProperties;
use Psalm\Issue\NullPropertyAssignment;
use Psalm\Issue\NullPropertyFetch;
use Psalm\Issue\NullReference;
use Psalm\Issue\ParentNotFound;
use Psalm\Issue\PossiblyUndefinedVariable;
use Psalm\Issue\TooFewArguments;
use Psalm\Issue\TooManyArguments;
use Psalm\Issue\TypeCoercion;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedConstant;
use Psalm\Issue\UndefinedFunction;
use Psalm\Issue\UndefinedPropertyAssignment;
use Psalm\Issue\UndefinedPropertyFetch;
use Psalm\Issue\UndefinedThisPropertyAssignment;
use Psalm\Issue\UndefinedThisPropertyFetch;
use Psalm\Issue\UndefinedVariable;
use Psalm\Type;

class ExpressionChecker
{
    /** @var array<string,array<int,string>> */
    protected static $reflection_functions = [];

    /**
     * @return false|null
     */
    public static function check(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr $stmt,
        Context $context,
        $array_assignment = false,
        Type\Union $assignment_key_type = null,
        Type\Union $assignment_value_type = null,
        $assignment_key_value = null
    ) {
        if ($stmt instanceof PhpParser\Node\Expr\Variable) {
            if (self::checkVariable($statements_checker, $stmt, $context, null, null, $array_assignment) === false) {
                return false;
            }
        }
        elseif ($stmt instanceof PhpParser\Node\Expr\Assign) {
            if (self::checkAssignment($statements_checker, $stmt, $context) === false) {
                return false;
            }
        }
        elseif ($stmt instanceof PhpParser\Node\Expr\AssignOp) {
            if (self::checkAssignmentOperation($statements_checker, $stmt, $context) === false) {
                return false;
            }
        }
        elseif ($stmt instanceof PhpParser\Node\Expr\MethodCall) {
            if (self::checkMethodCall($statements_checker, $stmt, $context) === false) {
                return false;
            }
        }
        elseif ($stmt instanceof PhpParser\Node\Expr\StaticCall) {
            if (self::checkStaticCall($statements_checker, $stmt, $context) === false) {
                return false;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\ConstFetch) {
            if (self::checkConstFetch($statements_checker, $stmt, $context) === false) {
                return false;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Scalar\String_) {
            $stmt->inferredType = Type::getString();

        }
        elseif ($stmt instanceof PhpParser\Node\Scalar\EncapsedStringPart) {
            // do nothing

        }
        elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst) {
            // do nothing

        }
        elseif ($stmt instanceof PhpParser\Node\Scalar\LNumber) {
            $stmt->inferredType = Type::getInt();

        }
        elseif ($stmt instanceof PhpParser\Node\Scalar\DNumber) {
            $stmt->inferredType = Type::getFloat();

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\UnaryMinus) {
            if (self::check($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\UnaryPlus) {
            if (self::check($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\Isset_) {
            foreach ($stmt->vars as $isset_var) {
                if ($isset_var instanceof PhpParser\Node\Expr\PropertyFetch &&
                    $isset_var->var instanceof PhpParser\Node\Expr\Variable &&
                    $isset_var->var->name === 'this' &&
                    is_string($isset_var->name)
                ) {
                    $var_id = '$this->' . $isset_var->name;
                    $context->vars_in_scope[$var_id] = Type::getMixed();
                    $context->vars_possibly_in_scope[$var_id] = true;
                }
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\ClassConstFetch) {
            if (self::checkClassConstFetch($statements_checker, $stmt, $context) === false) {
                return false;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\PropertyFetch) {
            if (self::checkPropertyFetch($statements_checker, $stmt, $context, $array_assignment) === false) {
                return false;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\StaticPropertyFetch) {
            if (self::checkStaticPropertyFetch($statements_checker, $stmt, $context) === false) {
                return false;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\BitwiseNot) {
            if (self::check($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            if (self::checkBinaryOp($statements_checker, $stmt, $context) === false) {
                return false;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\PostInc) {
            if (self::check($statements_checker, $stmt->var, $context) === false) {
                return false;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\PostDec) {
            if (self::check($statements_checker, $stmt->var, $context) === false) {
                return false;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\PreInc) {
            if (self::check($statements_checker, $stmt->var, $context) === false) {
                return false;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\PreDec) {
            if (self::check($statements_checker, $stmt->var, $context) === false) {
                return false;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\New_) {
            if (self::checkNew($statements_checker, $stmt, $context) === false) {
                return false;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\Array_) {
            if (self::checkArray($statements_checker, $stmt, $context) === false) {
                return false;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Scalar\Encapsed) {
            if (self::checkEncapsulatedString($statements_checker, $stmt, $context) === false) {
                return false;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\FuncCall) {
            if (self::checkFunctionCall($statements_checker, $stmt, $context) === false) {
                return false;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\Ternary) {
            if (self::checkTernary($statements_checker, $stmt, $context) === false) {
                return false;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\BooleanNot) {
            if (self::checkBooleanNot($statements_checker, $stmt, $context) === false) {
                return false;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\Empty_) {
            if (self::checkEmpty($statements_checker, $stmt, $context) === false) {
                return false;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\Closure) {
            $closure_checker = new ClosureChecker($stmt, $statements_checker->getSource());

            if (self::checkClosureUses($statements_checker, $stmt, $context) === false) {
                return false;
            }

            $use_context = new Context($statements_checker->getFileName(), $context->self);

            if (!$statements_checker->isStatic()) {
                $this_class = ClassLikeChecker::getThisClass();
                $this_class = $this_class && ClassChecker::classExtends($this_class, $statements_checker->getAbsoluteClass())
                    ? $this_class
                    : $context->self;

                if ($this_class) {
                    $use_context->vars_in_scope['$this'] = new Type\Union([new Type\Atomic($this_class)]);
                }
            }

            foreach ($context->vars_in_scope as $var => $type) {
                if (strpos($var, '$this->') === 0) {
                    $use_context->vars_in_scope[$var] = clone $type;
                }
            }

            foreach ($context->vars_possibly_in_scope as $var => $type) {
                if (strpos($var, '$this->') === 0) {
                    $use_context->vars_possibly_in_scope[$var] = true;
                }
            }

            foreach ($stmt->uses as $use) {
                $use_context->vars_in_scope['$' . $use->var] = isset($context->vars_in_scope['$' . $use->var]) ? clone $context->vars_in_scope['$' . $use->var] : Type::getMixed();
                $use_context->vars_possibly_in_scope['$' . $use->var] = true;
            }

            $closure_checker->check($use_context);

            $stmt->inferredType = Type::getClosure();

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            if (self::checkArrayAccess($statements_checker, $stmt, $context, $array_assignment, $assignment_key_type, $assignment_value_type, $assignment_key_value) === false) {
                return false;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Int_) {
            if (self::check($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }
            $stmt->inferredType = Type::getInt();

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Double) {
            if (self::check($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }
            $stmt->inferredType = Type::getFloat();

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Bool_) {
            if (self::check($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }
            $stmt->inferredType = Type::getBool();

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\Cast\String_) {
            if (self::check($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }
            $stmt->inferredType = Type::getString();

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Object_) {
            if (self::check($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }
            $stmt->inferredType = Type::getObject();

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Array_) {
            if (self::check($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }
            $stmt->inferredType = Type::getArray();

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Unset_) {
            if (self::check($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }
            $stmt->inferredType = Type::getNull();

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\Clone_) {
            if (self::check($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

            if (property_exists($stmt->expr, 'inferredType')) {
                $stmt->inferredType = $stmt->expr->inferredType;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\Instanceof_) {
            if (self::check($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

            if ($stmt->class instanceof PhpParser\Node\Name && !in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
                if ($context->check_classes) {
                    $absolute_class = ClassLikeChecker::getAbsoluteClassFromName(
                        $stmt->class,
                        $statements_checker->getNamespace(),
                        $statements_checker->getAliasedClasses()
                    );

                    if (ClassLikeChecker::checkAbsoluteClassOrInterface($absolute_class, $statements_checker->getCheckedFileName(), $stmt->getLine(), $statements_checker->getSuppressedIssues()) === false) {
                        return false;
                    }
                }
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\Exit_) {
            // do nothing

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\Include_) {
            $statements_checker->checkInclude($stmt, $context);

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\Eval_) {
            $context->check_classes = false;
            $context->check_variables = false;

            if (self::check($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\AssignRef) {
            if ($stmt->var instanceof PhpParser\Node\Expr\Variable) {
                if (is_string($stmt->var->name)) {
                    $context->vars_in_scope['$' . $stmt->var->name] = Type::getMixed();
                    $context->vars_possibly_in_scope['$' . $stmt->var->name] = true;
                    $statements_checker->registerVariable('$' . $stmt->var->name, $stmt->var->getLine());
                }
                else {
                    if (self::check($statements_checker, $stmt->var->name, $context) === false) {
                        return false;
                    }
                }
            } else {
                if (self::check($statements_checker, $stmt->var, $context) === false) {
                    return false;
                }
            }

            if (self::check($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\ErrorSuppress) {
            // do nothing

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\ShellExec) {
            if (IssueBuffer::accepts(
                new ForbiddenCode('Use of shell_exec', $statements_checker->getCheckedFileName(), $stmt->getLine()),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\Print_) {
            if (self::check($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

        }
        elseif ($stmt instanceof PhpParser\Node\Expr\Yield_) {
            self::checkYield($statements_checker, $stmt, $context);
        }
        elseif ($stmt instanceof PhpParser\Node\Expr\YieldFrom) {
            self::checkYieldFrom($statements_checker, $stmt, $context);
        }
        else {
            var_dump('Unrecognised expression in ' . $statements_checker->getCheckedFileName());
            var_dump($stmt);
        }

        foreach (Config::getInstance()->getPlugins() as $plugin) {
            if ($plugin->checkExpression($stmt, $context, $statements_checker->getCheckedFileName()) === false) {
                return false;
            }
        }
    }

    /**
     * @return false|null
     */
    protected static function checkVariable(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\Variable $stmt,
        Context $context,
        $passed_by_reference = false,
        Type\Union $by_ref_type = null,
        $array_assignment = false
    ) {
        if ($statements_checker->isStatic() && $stmt->name === 'this') {
            if (IssueBuffer::accepts(
                new InvalidStaticVariable('Invalid reference to $this in a static context', $statements_checker->getCheckedFileName(), $stmt->getLine()),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }
        }

        if (!$context->check_variables) {
            $stmt->inferredType = Type::getMixed();

            if (is_string($stmt->name) && !isset($context->vars_in_scope['$' . $stmt->name])) {
                $context->vars_in_scope['$' . $stmt->name] = Type::getMixed();
                $context->vars_possibly_in_scope['$' . $stmt->name] = true;
            }

            return;
        }

        if (in_array($stmt->name, ['_SERVER', '_GET', '_POST', '_COOKIE', '_REQUEST', '_FILES', '_ENV', 'GLOBALS', 'argv'])) {
            return;
        }

        if (!is_string($stmt->name)) {
            return self::check($statements_checker, $stmt->name, $context);
        }

        if ($stmt->name === 'this') {
            return;
        }

        if ($passed_by_reference && $by_ref_type) {
            self::assignByRefParam($statements_checker, $stmt, $by_ref_type, $context);
            return;
        }

        $var_name = '$' . $stmt->name;

        if (!isset($context->vars_in_scope[$var_name])) {
            if (!isset($context->vars_possibly_in_scope[$var_name]) || !$statements_checker->getFirstAppearance($var_name)) {
                if ($array_assignment) {
                    // if we're in an array assignment, let's assign the variable
                    // because PHP allows it

                    $context->vars_in_scope[$var_name] = Type::getArray();
                    $context->vars_possibly_in_scope[$var_name] = true;
                    $statements_checker->registerVariable($var_name, $stmt->getLine());
                }
                else {
                    IssueBuffer::add(
                        new UndefinedVariable('Cannot find referenced variable ' . $var_name, $statements_checker->getCheckedFileName(), $stmt->getLine())
                    );

                    return false;
                }
            }

            if ($statements_checker->getFirstAppearance($var_name)) {
                if (IssueBuffer::accepts(
                    new PossiblyUndefinedVariable(
                        'Possibly undefined variable ' . $var_name .', first seen on line ' . $statements_checker->getFirstAppearance($var_name),
                        $statements_checker->getCheckedFileName(),
                        $stmt->getLine()
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            }

        } else {
            $stmt->inferredType = $context->vars_in_scope[$var_name];
        }
    }

    /**
     * @param  PhpParser\Node\Expr\Variable|PhpParser\Node\Expr\PropertyFetch $stmt
     * @param  Type\Union $by_ref_type
     * @param  Context $context
     * @return void
     */
    protected static function assignByRefParam(StatementsChecker $statements_checker, PhpParser\Node\Expr $stmt, Type\Union $by_ref_type, Context $context)
    {
        $var_id = self::getVarId($stmt, $statements_checker->getAbsoluteClass(), $statements_checker->getNamespace(), $statements_checker->getAliasedClasses());

        if ($var_id && !isset($context->vars_in_scope[$var_id])) {
            $context->vars_possibly_in_scope[$var_id] = true;
            $statements_checker->registerVariable($var_id, $stmt->getLine());
        }

        $stmt->inferredType = $by_ref_type;

        $context->vars_in_scope[$var_id] = $by_ref_type;
    }

    /**
     * @return false|null
     */
    protected static function checkPropertyFetch(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\PropertyFetch $stmt,
        Context $context,
        $array_assignment = false
    ) {
        if (!is_string($stmt->name)) {
            if (self::check($statements_checker, $stmt->name, $context) === false) {
                return false;
            }
        }

        $var_id = null;

        if (!($stmt->var instanceof PhpParser\Node\Expr\Variable)) {
            if (self::check($statements_checker, $stmt->var, $context) === false) {
                return false;
            }
        }
        else{
            if (self::checkVariable($statements_checker, $stmt->var, $context) === false) {
                return false;
            }
        }

        $stmt_var_id = self::getVarId($stmt->var, $statements_checker->getAbsoluteClass(), $statements_checker->getNamespace(), $statements_checker->getAliasedClasses());
        $var_id = self::getVarId($stmt, $statements_checker->getAbsoluteClass(), $statements_checker->getNamespace(), $statements_checker->getAliasedClasses());

        $var_name = is_string($stmt->name) ? $stmt->name : null;

        $stmt_var_type = null;

        if ($var_id && isset($context->vars_in_scope[$var_id])) {
            // we don't need to check anything
            $stmt->inferredType = $context->vars_in_scope[$var_id];
            return;
        }

        if ($stmt_var_id && isset($context->vars_in_scope[$stmt_var_id])) {
            $stmt_var_type = $context->vars_in_scope[$stmt_var_id];
        }
        elseif (isset($stmt->var->inferredType)) {
            /** @var Type\Union */
            $stmt_var_type = $stmt->var->inferredType;
        }

        if (!$stmt_var_type) {
            return;
        }

        if ($stmt_var_type->isNull()) {
            if (IssueBuffer::accepts(
                new NullReference(
                    'Cannot get property on null variable ' . $stmt_var_id,
                    $statements_checker->getCheckedFileName(),
                    $stmt->getLine()
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }

            return;
        }

        if ($stmt_var_type->isEmpty()) {
            if (IssueBuffer::accepts(
                new NullReference(
                    'Cannot fetch property on empty var ' . $stmt_var_id,
                    $statements_checker->getCheckedFileName(),
                    $stmt->getLine()
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }

            return;
        }

        if ($stmt_var_type->isMixed()) {
            if (IssueBuffer::accepts(
                new MixedPropertyFetch(
                    'Cannot fetch property on mixed var ' . $stmt_var_id,
                    $statements_checker->getCheckedFileName(),
                    $stmt->getLine()
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }

            return;
        }

        if ($stmt_var_type->isNullable()) {
            if (IssueBuffer::accepts(
                new NullPropertyFetch(
                    'Cannot get property on possibly null variable ' . $stmt_var_id,
                    $statements_checker->getCheckedFileName(),
                    $stmt->getLine()
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }

            $stmt->inferredType = Type::getNull();
        }

        if (!is_string($stmt->name)) {
            return;
        }

        foreach ($stmt_var_type->types as $lhs_type_part) {
            if ($lhs_type_part->isNull()) {
                continue;
            }

            if (!$lhs_type_part->isObjectType()) {
                $stmt_var_id = self::getVarId($stmt->var, $statements_checker->getAbsoluteClass(), $statements_checker->getNamespace(), $statements_checker->getAliasedClasses());

                if (IssueBuffer::accepts(
                    new InvalidPropertyFetch(
                        'Cannot fetch property on non-object ' . $stmt_var_id . ' of type ' . $lhs_type_part,
                        $statements_checker->getCheckedFileName(),
                        $stmt->getLine()
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }

                continue;
            }

            // stdClass and SimpleXMLElement are special cases where we cannot infer the return types
            // but we don't want to throw an error
            // Hack has a similar issue: https://github.com/facebook/hhvm/issues/5164
            if ($lhs_type_part->isObject() || in_array(strtolower($lhs_type_part->value), ['stdclass', 'simplexmlelement', 'dateinterval', 'domdocument', 'domnode'])) {
                $stmt->inferredType = Type::getMixed();
                continue;
            }

            if (method_exists((string) $lhs_type_part, '__get')) {
                $stmt->inferredType = Type::getMixed();
                continue;
            }

            if (!ClassChecker::classExists($lhs_type_part->value)) {
                if (InterfaceChecker::interfaceExists($lhs_type_part->value)) {
                    if (IssueBuffer::accepts(
                        new NoInterfaceProperties(
                            'Interfaces cannot have properties',
                            $statements_checker->getCheckedFileName(),
                            $stmt->getLine()
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    continue;
                }

                if (IssueBuffer::accepts(
                    new UndefinedClass(
                        'Cannot get properties of undefined class ' . $lhs_type_part->value,
                        $statements_checker->getCheckedFileName(),
                        $stmt->getLine()
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }

                continue;
            }

            if ($var_name === 'this'
                || $lhs_type_part->value === $context->self
                || ($statements_checker->getSource()->getSource() instanceof TraitChecker && $lhs_type_part->value === $statements_checker->getSource()->getAbsoluteClass())
            ) {
                $class_visibility = \ReflectionProperty::IS_PRIVATE;
            }
            elseif (ClassChecker::classExtends($lhs_type_part->value, $context->self)) {
                $class_visibility = \ReflectionProperty::IS_PROTECTED;
            }
            else {
                $class_visibility = \ReflectionProperty::IS_PUBLIC;
            }

            $class_properties = ClassLikeChecker::getInstancePropertiesForClass(
                $lhs_type_part->value,
                $class_visibility
            );

            if (!$class_properties || !isset($class_properties[$stmt->name])) {
                $stmt->inferredType = Type::getMixed();

                if ($var_id) {
                    $context->vars_in_scope[$var_id] = Type::getMixed();
                }

                if ($stmt_var_id === '$this') {
                    if (IssueBuffer::accepts(
                        new UndefinedThisPropertyFetch(
                            'Instance property ' . $lhs_type_part->value .'::$' . $stmt->name . ' is not defined',
                            $statements_checker->getCheckedFileName(),
                            $stmt->getLine()
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }
                else {
                    if (IssueBuffer::accepts(
                        new UndefinedPropertyFetch(
                            'Instance property ' . $lhs_type_part->value .'::$' . $stmt->name . ' is not defined',
                            $statements_checker->getCheckedFileName(),
                            $stmt->getLine()
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }

                return;
            }

            if (isset($stmt->inferredType)) {
                $stmt->inferredType = Type::combineUnionTypes(clone $class_properties[$stmt->name], $stmt->inferredType);
            }
            else {
                $stmt->inferredType = $class_properties[$stmt->name];
            }
        }

        if ($var_id) {
            $context->vars_in_scope[$var_id] = isset($stmt->inferredType) ? $stmt->inferredType : Type::getMixed();
        }
    }

    /**
     * @param  PhpParser\Node\Expr\PropertyFetch|PhpParser\Node\Stmt\PropertyProperty    $stmt
     * @param  string     $prop_name
     * @param  Type\Union $assignment_type
     * @param  Context    $context
     * @return false|null
     */
    public static function checkPropertyAssignment(
        StatementsChecker $statements_checker,
        $stmt,
        $prop_name,
        Type\Union $assignment_type,
        Context $context
    ) {
        $class_property_types = [];

        if ($stmt instanceof PhpParser\Node\Stmt\PropertyProperty) {
            if (!$context->self) {
                return;
            }

            $class_properties = ClassLikeChecker::getInstancePropertiesForClass($context->self, \ReflectionProperty::IS_PRIVATE);

            $class_property_types[] = clone $class_properties[$prop_name];

            $var_id = '$this->' . $prop_name;
        }
        elseif ($stmt->var instanceof PhpParser\Node\Expr\Variable) {
            if (!isset($context->vars_in_scope['$' . $stmt->var->name])) {
                if (self::checkVariable($statements_checker, $stmt->var, $context) === false) {
                    return false;
                }

                return;
            }

            $stmt->var->inferredType = $context->vars_in_scope['$' . $stmt->var->name];

            $lhs_type = $context->vars_in_scope['$' . $stmt->var->name];

            if ($stmt->var->name === 'this' && !$statements_checker->getSource()->getClassLikeChecker()) {
                if (IssueBuffer::accepts(
                    new InvalidScope('Cannot use $this when not inside class', $statements_checker->getCheckedFileName(), $stmt->getLine()),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            }

            $var_id = self::getVarId($stmt, $statements_checker->getAbsoluteClass(), $statements_checker->getNamespace(), $statements_checker->getAliasedClasses());

            if ($lhs_type->isMixed()) {
                if (IssueBuffer::accepts(
                    new MixedPropertyAssignment(
                        $var_id . ' with mixed type cannot be assigned to',
                        $statements_checker->getCheckedFileName(),
                        $stmt->getLine()
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }

                return;
            }

            if ($lhs_type->isNull()) {
                if (IssueBuffer::accepts(
                    new NullPropertyAssignment(
                        $var_id . ' with null type cannot be assigned to',
                        $statements_checker->getCheckedFileName(),
                        $stmt->getLine()
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }

                return;
            }

            if ($lhs_type->isNullable()) {
                if (IssueBuffer::accepts(
                    new NullPropertyAssignment(
                        $var_id . ' with possibly null type \'' . $lhs_type . '\' cannot be assigned to',
                        $statements_checker->getCheckedFileName(),
                        $stmt->getLine()
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            }

            $has_regular_setter = false;

            foreach ($lhs_type->types as $lhs_type_part) {
                if ($lhs_type_part->isNull()) {
                    continue;
                }

                if (method_exists((string) $lhs_type_part, '__set')) {
                    $context->vars_in_scope[$var_id] = Type::getMixed();
                    continue;
                }

                $has_regular_setter = true;

                if (!$lhs_type_part->isObjectType()) {
                    if (IssueBuffer::accepts(
                        new InvalidPropertyAssignment(
                            $var_id . ' with possible non-object type \'' . $lhs_type_part . '\' cannot be assigned to',
                            $statements_checker->getCheckedFileName(),
                            $stmt->getLine()
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    continue;
                }

                if ($lhs_type_part->isObject()) {
                    continue;
                }

                // stdClass and SimpleXMLElement are special cases where we cannot infer the return types
                // but we don't want to throw an error
                // Hack has a similar issue: https://github.com/facebook/hhvm/issues/5164
                if ($lhs_type_part->isObject() || in_array(strtolower($lhs_type_part->value), ['stdclass', 'simplexmlelement', 'dateinterval', 'domdocument', 'domnode'])) {
                    $context->vars_in_scope[$var_id] = Type::getMixed();
                    return;
                }

                if (self::isMock($lhs_type_part->value)) {
                    $context->vars_in_scope[$var_id] = Type::getMixed();
                    return;
                }

                if ($stmt->var->name === 'this' || $lhs_type_part->value === $context->self) {
                    $class_visibility = \ReflectionProperty::IS_PRIVATE;
                }
                elseif (ClassChecker::classExtends($lhs_type_part->value, $context->self)) {
                    $class_visibility = \ReflectionProperty::IS_PROTECTED;
                }
                else {
                    $class_visibility = \ReflectionProperty::IS_PUBLIC;
                }

                if (!ClassChecker::classExists($lhs_type_part->value)) {
                    if (InterfaceChecker::interfaceExists($lhs_type_part->value)) {
                        if (IssueBuffer::accepts(
                            new NoInterfaceProperties(
                                'Interfaces cannot have properties',
                                $statements_checker->getCheckedFileName(),
                                $stmt->getLine()
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        return;
                    }

                    if (IssueBuffer::accepts(
                        new UndefinedClass(
                            'Cannot set properties of undefined class ' . $lhs_type_part->value,
                            $statements_checker->getCheckedFileName(),
                            $stmt->getLine()
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    return;
                }

                $class_properties = ClassLikeChecker::getInstancePropertiesForClass(
                    $lhs_type_part->value,
                    $class_visibility
                );

                if (!isset($class_properties[$prop_name])) {
                    if ($stmt->var->name === 'this') {
                        if (IssueBuffer::accepts(
                            new UndefinedThisPropertyAssignment(
                                'Instance property ' . $lhs_type_part->value . '::$' . $prop_name . ' is not defined',
                                $statements_checker->getCheckedFileName(),
                                $stmt->getLine()
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }
                    }
                    else {
                        if (IssueBuffer::accepts(
                            new UndefinedPropertyAssignment(
                                'Instance property ' . $lhs_type_part->value . '::$' . $prop_name . ' is not defined',
                                $statements_checker->getCheckedFileName(),
                                $stmt->getLine()
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }
                    }

                    continue;
                }

                $class_property_types[] = clone $class_properties[$prop_name];
            }

            if (!$has_regular_setter) {
                return;
            }

            // because we don't want to be assigning for property declarations
            $context->vars_in_scope[$var_id] = $assignment_type;
        }
        else {
            return;
        }

        if ($var_id && count($class_property_types) === 1 && isset($class_property_types[0]->types['stdClass'])) {
            $context->vars_in_scope[$var_id] = Type::getMixed();
            return;
        }

        if (!$class_property_types) {
            if (IssueBuffer::accepts(
                new MissingPropertyDeclaration(
                    'Missing property declaration for ' . $var_id,
                    $statements_checker->getCheckedFileName(),
                    $stmt->getLine()
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }

            return;
        }

        if ($assignment_type->isMixed()) {
            return;
        }

        foreach ($class_property_types as $class_property_type) {
            if ($class_property_type->isMixed()) {
                continue;
            }

            if (!$assignment_type->isIn($class_property_type)) {
                if (IssueBuffer::accepts(
                    new InvalidPropertyAssignment(
                        $var_id . ' with declared type \'' . $class_property_type . '\' cannot be assigned type \'' . $assignment_type . '\'',
                        $statements_checker->getCheckedFileName(),
                        $stmt->getLine()
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            }
        }
    }

    /**
     * @param  PhpParser\Node\Expr\StaticPropertyFetch    $stmt
     * @param  Type\Union $assignment_type
     * @param  Context    $context
     * @return false|null
     */
    protected static function checkStaticPropertyAssignment(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\StaticPropertyFetch $stmt,
        Type\Union $assignment_type,
        Context $context
    ) {
        $class_property_types = [];

        if (self::checkStaticPropertyFetch($statements_checker, $stmt, $context) === false) {
            return false;
        }

        $var_id = self::getVarId($stmt, $statements_checker->getAbsoluteClass(), $statements_checker->getNamespace(), $statements_checker->getAliasedClasses());

        $absolute_class = (string)$stmt->inferredType;

        if (($stmt->class instanceof PhpParser\Node\Name && $stmt->class->parts[0] === 'this') || $absolute_class === $context->self) {
            $class_visibility = \ReflectionProperty::IS_PRIVATE;
        }
        elseif ($context->self && ClassChecker::classExtends($absolute_class, $context->self)) {
            $class_visibility = \ReflectionProperty::IS_PROTECTED;
        }
        else {
            $class_visibility = \ReflectionProperty::IS_PUBLIC;
        }

        $class_properties = ClassLikeChecker::getStaticPropertiesForClass(
            $absolute_class,
            $class_visibility
        );

        $prop_name = $stmt->name;

        if (!isset($class_properties[$prop_name])) {
            if ($stmt->class instanceof PhpParser\Node\Name && $stmt->class->parts[0] === 'this') {
                if (IssueBuffer::accepts(
                    new UndefinedThisPropertyAssignment(
                        'Static property ' . $var_id . ' is not defined',
                        $statements_checker->getCheckedFileName(),
                        $stmt->getLine()
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            }
            else {
                if (IssueBuffer::accepts(
                    new UndefinedPropertyAssignment(
                        'Static property ' . $var_id . ' is not defined',
                        $statements_checker->getCheckedFileName(),
                        $stmt->getLine()
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            }

            return;
        }

        $context->vars_in_scope[$var_id] = $assignment_type;

        $class_property_type = clone $class_properties[$prop_name];

        if ($assignment_type->isMixed()) {
            return;
        }

        if ($class_property_type->isMixed()) {
            return;
        }

        if (!$assignment_type->isIn($class_property_type)) {
            if (IssueBuffer::accepts(
                new InvalidPropertyAssignment(
                    $var_id . ' with declared type \'' . $class_property_type . '\' cannot be assigned type \'' . $assignment_type . '\'',
                    $statements_checker->getCheckedFileName(),
                    $stmt->getLine()
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }
        }

        $context->vars_in_scope[$var_id] = $assignment_type;
    }

    /**
     * @return false|null
     */
    protected static function checkNew(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\New_ $stmt,
        Context $context
    ) {
        $absolute_class = null;

        if ($stmt->class instanceof PhpParser\Node\Name) {
            if (!in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
                if ($context->check_classes) {
                    $absolute_class = ClassLikeChecker::getAbsoluteClassFromName($stmt->class, $statements_checker->getNamespace(), $statements_checker->getAliasedClasses());

                    if ($context->isPhantomClass($absolute_class)) {
                        return;
                    }

                    if (ClassLikeChecker::checkAbsoluteClassOrInterface($absolute_class, $statements_checker->getCheckedFileName(), $stmt->getLine(), $statements_checker->getSuppressedIssues()) === false) {
                        return false;
                    }
                }
            }
            else {
                switch ($stmt->class->parts[0]) {
                    case 'self':
                        $absolute_class = $context->self;
                        break;

                    case 'parent':
                        $absolute_class = $context->parent;
                        break;

                    case 'static':
                        // @todo maybe we can do better here
                        $absolute_class = $context->self;
                        break;
                }
            }
        }
        elseif ($stmt->class instanceof PhpParser\Node\Stmt\Class_) {
            $statements_checker->check([$stmt->class], $context);
            $absolute_class = $stmt->class->name;
        }
        else {
            self::check($statements_checker, $stmt->class, $context);
        }

        if ($absolute_class) {
            $stmt->inferredType = new Type\Union([new Type\Atomic($absolute_class)]);

            if (method_exists($absolute_class, '__construct')) {
                $method_id = $absolute_class . '::__construct';

                if (self::checkFunctionArguments($statements_checker, $stmt->args, $method_id, $context, $stmt->getLine()) === false) {
                    return false;
                }

                if ($absolute_class === 'ArrayIterator' && isset($stmt->args[0]->value->inferredType)) {
                    /** @var Type\Union */
                    $first_arg_type = $stmt->args[0]->value->inferredType;

                    if ($first_arg_type->hasGeneric()) {
                        $key_type = null;
                        $value_type = null;

                        foreach ($first_arg_type->types as $type) {
                            if ($type instanceof Type\Generic) {
                                $first_type_param = count($type->type_params) ? $type->type_params[0] : null;
                                $last_type_param = $type->type_params[count($type->type_params) - 1];

                                if ($value_type === null) {
                                    $value_type = clone $last_type_param;
                                }
                                else {
                                    $value_type = Type::combineUnionTypes($value_type, $last_type_param);
                                }

                                if (!$key_type || !$first_type_param) {
                                    $key_type = $first_type_param ? clone $first_type_param : Type::getMixed();
                                }
                                else {
                                    $key_type = Type::combineUnionTypes($key_type, $first_type_param);
                                }
                            }
                        }

                        $stmt->inferredType = new Type\Union([
                            new Type\Generic(
                                $absolute_class,
                                [
                                    $key_type,
                                    $value_type
                                ]
                            )
                        ]);

                    }
                }
            }
        }
    }

    /**
     * @return false|null
     */
    protected static function checkArray(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\Array_ $stmt,
        Context $context
    ) {
        // if the array is empty, this special type allows us to match any other array type against it
        if (empty($stmt->items)) {
            $stmt->inferredType = Type::getEmptyArray();
            return;
        }

        /** @var Type\Union|null */
        $item_key_type = null;

        /** @var Type\Union|null */
        $item_value_type = null;

        /** @var array<string,Type\Union> */
        $property_types = [];

        foreach ($stmt->items as $item) {
            if ($item->key) {
                if (self::check($statements_checker, $item->key, $context) === false) {
                    return false;
                }

                if (isset($item->key->inferredType)) {
                    if ($item_key_type) {
                        /** @var Type\Union */
                        $item_key_type = Type::combineUnionTypes($item->key->inferredType, $item_key_type);
                    }
                    else {
                        /** @var Type\Union */
                        $item_key_type = $item->key->inferredType;
                    }
                }
            }
            else {
                $item_key_type = Type::getInt();
            }

            if (self::check($statements_checker, $item->value, $context) === false) {
                return false;
            }

            if (isset($item->value->inferredType)) {
                if ($item->key instanceof PhpParser\Node\Scalar\String_) {
                    $property_types[$item->key->value] = $item->value->inferredType;
                }

                if ($item_value_type) {
                    $item_value_type = Type::combineUnionTypes($item->value->inferredType, $item_value_type);
                }
                else {
                    $item_value_type = $item->value->inferredType;
                }
            }
        }

        // if this array looks like an object-like array, let's return that instead
        if ($item_value_type && $item_key_type && $item_key_type->hasString() && !$item_key_type->hasInt()) {
            $stmt->inferredType = new Type\Union([new Type\ObjectLike('object-like', $property_types)]);
            return;
        }

        $stmt->inferredType = new Type\Union([
            new Type\Generic(
                'array',
                [
                    $item_key_type ?: new Type\Union([new Type\Atomic('int'), new Type\Atomic('string')]),
                    $item_value_type ?: Type::getMixed()
                ]
            )
        ]);
    }

    /**
     * @return false|null
     */
    protected static function checkBinaryOp(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\BinaryOp $stmt,
        Context $context,
        $nesting = 0
    ) {
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat && $nesting > 20) {
            // ignore deeply-nested string concatenation
        }
        else if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd) {
            $left_type_assertions = TypeChecker::getReconcilableTypeAssertions(
                $stmt->left,
                $statements_checker->getAbsoluteClass(),
                $statements_checker->getNamespace(),
                $statements_checker->getAliasedClasses()
            );

            if (self::check($statements_checker, $stmt->left, $context) === false) {
                return false;
            }

            // while in an and, we allow scope to boil over to support
            // statements of the form if ($x && $x->foo())
            $op_vars_in_scope = TypeChecker::reconcileKeyedTypes(
                $left_type_assertions,
                $context->vars_in_scope,
                $statements_checker->getCheckedFileName(),
                $stmt->getLine(),
                $statements_checker->getSuppressedIssues()
            );

            if ($op_vars_in_scope === false) {
                return false;
            }

            $op_context = clone $context;
            $op_context->vars_in_scope = $op_vars_in_scope;

            if (self::check($statements_checker, $stmt->right, $op_context) === false) {
                return false;
            }

            foreach ($op_context->vars_in_scope as $var => $type) {
                if (!isset($context->vars_in_scope[$var])) {
                    $context->vars_in_scope[$var] = $type;
                    continue;
                }
            }

            $context->updateChecks($op_context);

            $context->vars_possibly_in_scope = array_merge($op_context->vars_possibly_in_scope, $context->vars_possibly_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr) {
            $left_type_assertions = TypeChecker::getNegatableTypeAssertions(
                $stmt->left,
                $statements_checker->getAbsoluteClass(),
                $statements_checker->getNamespace(),
                $statements_checker->getAliasedClasses()
            );

            $negated_type_assertions = TypeChecker::negateTypes($left_type_assertions);

            if (self::check($statements_checker, $stmt->left, $context) === false) {
                return false;
            }

            // while in an or, we allow scope to boil over to support
            // statements of the form if ($x === null || $x->foo())
            $op_vars_in_scope = TypeChecker::reconcileKeyedTypes(
                $negated_type_assertions,
                $context->vars_in_scope,
                $statements_checker->getCheckedFileName(),
                $stmt->getLine(),
                $statements_checker->getSuppressedIssues()
            );

            if ($op_vars_in_scope === false) {
                return false;
            }

            $op_context = clone $context;
            $op_context->vars_in_scope = $op_vars_in_scope;

            if (self::check($statements_checker, $stmt->right, $op_context) === false) {
                return false;
            }

            $context->updateChecks($op_context);

            $context->vars_possibly_in_scope = array_merge($op_context->vars_possibly_in_scope, $context->vars_possibly_in_scope);
        }
        else {
            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
                $stmt->inferredType = Type::getString();
            }

            if ($stmt->left instanceof PhpParser\Node\Expr\BinaryOp) {
                if (self::checkBinaryOp($statements_checker, $stmt->left, $context, ++$nesting) === false) {
                    return false;
                }
            }
            else {
                if (self::check($statements_checker, $stmt->left, $context) === false) {
                    return false;
                }
            }

            if ($stmt->right instanceof PhpParser\Node\Expr\BinaryOp) {
                if (self::checkBinaryOp($statements_checker, $stmt->right, $context, ++$nesting) === false) {
                    return false;
                }
            }
            else {
                if (self::check($statements_checker, $stmt->right, $context) === false) {
                    return false;
                }
            }
        }

        // let's do some fun type assignment
        if (isset($stmt->left->inferredType) && isset($stmt->right->inferredType)) {
            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Mul
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Minus
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Plus
            ) {
                if ($stmt->left->inferredType->isInt() && $stmt->right->inferredType->isInt()) {
                    $stmt->inferredType = Type::getInt();
                }
                elseif ($stmt->left->inferredType->hasNumericType() && $stmt->right->inferredType->hasNumericType()) {
                    $stmt->inferredType = Type::getFloat();
                }
            }
            elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Div
                && $stmt->left->inferredType->hasNumericType()
                && $stmt->right->inferredType->hasNumericType()
            ) {
                $stmt->inferredType = Type::getFloat();
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Equal
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Identical
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Greater
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\GreaterOrEqual
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Smaller
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\SmallerOrEqual
        ) {
            $stmt->inferredType = Type::getBool();
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Spaceship) {
            $stmt->inferredType = Type::getInt();
        }
    }

    /**
     * @return false|null
     */
    protected static function checkAssignment(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\Assign $stmt,
        Context $context
    ) {
        $var_id = self::getVarId($stmt->var, $statements_checker->getAbsoluteClass(), $statements_checker->getNamespace(), $statements_checker->getAliasedClasses());

        $array_var_id = self::getArrayVarId($stmt->var, $statements_checker->getAbsoluteClass(), $statements_checker->getNamespace(), $statements_checker->getAliasedClasses());

        if ($array_var_id) {
            // removes dependennt vars from $context
            $context->removeDescendents($array_var_id);
        }

        $type_in_comments = CommentChecker::getTypeFromComment((string) $stmt->getDocComment(), $context, $statements_checker->getSource(), $var_id);

        if (self::check($statements_checker, $stmt->expr, $context) === false) {
            // if we're not exiting immediately, make everything mixed
            $context->vars_in_scope[$var_id] = $type_in_comments ?: Type::getMixed();
            $stmt->inferredType = $type_in_comments ?: Type::getMixed();

            return false;
        }

        if ($type_in_comments) {
            $return_type = $type_in_comments;
        }
        elseif (isset($stmt->expr->inferredType)) {
            /** @var Type\Union */
            $return_type = $stmt->expr->inferredType;
        }
        else {
            $return_type = Type::getMixed();
        }

        $stmt->inferredType = $return_type;

        if ($stmt->var instanceof PhpParser\Node\Expr\Variable && is_string($stmt->var->name) && $var_id) {
            $context->vars_in_scope[$var_id] = $return_type;
            $context->vars_possibly_in_scope[$var_id] = true;
            $statements_checker->registerVariable($var_id, $stmt->var->getLine());

        }
        elseif ($stmt->var instanceof PhpParser\Node\Expr\List_) {
            foreach ($stmt->var->vars as $var) {
                if ($var && $var instanceof PhpParser\Node\Expr\Variable) {
                    $context->vars_in_scope['$' . $var->name] = Type::getMixed();
                    $context->vars_possibly_in_scope['$' . $var->name] = true;
                    $statements_checker->registerVariable('$' . $var->name, $var->getLine());
                }
            }

        }
        else if ($stmt->var instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            if (self::checkArrayAssignment($statements_checker, $stmt->var, $context, $return_type) === false) {
                return false;
            }

        }
        else if ($stmt->var instanceof PhpParser\Node\Expr\PropertyFetch &&
                    $stmt->var->var instanceof PhpParser\Node\Expr\Variable &&
                    is_string($stmt->var->name)) {

            self::checkPropertyAssignment($statements_checker, $stmt->var, $stmt->var->name, $return_type, $context);

            $context->vars_possibly_in_scope[$var_id] = true;
        }
        else if ($stmt->var instanceof PhpParser\Node\Expr\StaticPropertyFetch &&
                    $stmt->var->class instanceof PhpParser\Node\Name &&
                    is_string($stmt->var->name)) {

            self::checkStaticPropertyAssignment($statements_checker, $stmt->var, $return_type, $context);

            $context->vars_possibly_in_scope[$var_id] = true;
        }

        if ($var_id && isset($context->vars_in_scope[$var_id]) && $context->vars_in_scope[$var_id]->isVoid()) {
            if (IssueBuffer::accepts(
                new FailedTypeResolution('Cannot assign ' . $var_id . ' to type void', $statements_checker->getCheckedFileName(), $stmt->getLine()),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }
        }
    }

    /**
     * @param  PhpParser\Node\Expr $stmt
     * @param  string              $this_class_name
     * @param  string              $namespace
     * @param  array               $aliased_classes
     * @param  int|null            &$nesting
     * @return string|null
     */
    public static function getVarId(
        PhpParser\Node\Expr $stmt,
        $this_class_name,
        $namespace,
        array $aliased_classes,
        &$nesting = null
    ) {
        if ($stmt instanceof PhpParser\Node\Expr\Variable && is_string($stmt->name)) {
            return '$' . $stmt->name;
        }

        if ($stmt instanceof PhpParser\Node\Expr\StaticPropertyFetch
            && is_string($stmt->name)
            && $stmt->class instanceof PhpParser\Node\Name
        ) {
            if (count($stmt->class->parts) === 1 && in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
                $absolute_class = $this_class_name;
            }
            else {
                $absolute_class = ClassLikeChecker::getAbsoluteClassFromName($stmt->class, $namespace, $aliased_classes);
            }

            return $absolute_class . '::$' . $stmt->name;

        }

        if ($stmt instanceof PhpParser\Node\Expr\PropertyFetch && is_string($stmt->name)) {
            $object_id = self::getVarId($stmt->var, $this_class_name, $namespace, $aliased_classes);

            if (!$object_id) {
                return null;
            }

            return $object_id . '->' . $stmt->name;
        }

        if ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch && $nesting !== null) {
            $nesting++;
            return self::getVarId($stmt->var, $this_class_name, $namespace, $aliased_classes, $nesting);
        }

        return null;
    }

    /**
     * @param  PhpParser\Node\Expr $stmt
     * @param  string              $this_class_name
     * @param  string              $namespace
     * @param  array               $aliased_classes
     * @return string|null
     */
    public static function getArrayVarId(PhpParser\Node\Expr $stmt, $this_class_name, $namespace, array $aliased_classes)
    {
        if ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch && $stmt->dim instanceof PhpParser\Node\Scalar\String_) {
            $root_var_id = self::getArrayVarId($stmt->var, $this_class_name, $namespace, $aliased_classes);
            return $root_var_id ? $root_var_id . '[\'' . $stmt->dim->value . '\']' : null;
        }

        return self::getVarId($stmt, $this_class_name, $namespace, $aliased_classes);
    }

    /**
     * @return false|null
     * @psalm-suppress MixedMethodCall - some funky logic here
     */
    protected static function checkArrayAssignment(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        Context $context,
        Type\Union $assignment_value_type
    ) {
        if ($stmt->dim && self::check($statements_checker, $stmt->dim, $context, false) === false) {
            return false;
        }

        $assignment_key_type = null;
        $assignment_key_value = null;

        if ($stmt->dim) {
            if (isset($stmt->dim->inferredType)) {
                /** @var Type\Union */
                $assignment_key_type = $stmt->dim->inferredType;

                if ($stmt->dim instanceof PhpParser\Node\Scalar\String_) {
                    $assignment_key_value = $stmt->dim->value;
                }
            }
            else {
                $assignment_key_type = Type::getMixed();
            }
        }
        else {
            $assignment_key_type = Type::getInt();
        }

        $nesting = 0;
        $var_id = self::getVarId($stmt->var, $statements_checker->getAbsoluteClass(), $statements_checker->getNamespace(), $statements_checker->getAliasedClasses(), $nesting);
        $is_object = $var_id && isset($context->vars_in_scope[$var_id]) && $context->vars_in_scope[$var_id]->hasObjectType();
        $is_string = $var_id && isset($context->vars_in_scope[$var_id]) && $context->vars_in_scope[$var_id]->hasString();

        if (self::check($statements_checker, $stmt->var, $context, !$is_object, $assignment_key_type, $assignment_value_type, $assignment_key_value) === false) {
            return false;
        }

        $array_var_id = self::getArrayVarId($stmt->var, $statements_checker->getAbsoluteClass(), $statements_checker->getNamespace(), $statements_checker->getAliasedClasses());
        $keyed_array_var_id = $array_var_id && $stmt->dim instanceof PhpParser\Node\Scalar\String_
                                ? $array_var_id . '[\'' . $stmt->dim->value . '\']'
                                : null;

        if (isset($stmt->var->inferredType)) {
            $return_type = $stmt->var->inferredType;

            if ($is_object) {
                // do nothing
            }
            elseif ($is_string) {
                foreach ($assignment_value_type->types as $value_type) {
                    if (!$value_type->isString()) {
                        if ($value_type->isMixed()) {
                            if (IssueBuffer::accepts(
                                new MixedStringOffsetAssignment(
                                    'Cannot assign a mixed variable to a string offset for ' . $var_id,
                                    $statements_checker->getCheckedFileName(),
                                    $stmt->getLine()
                                ),
                                $statements_checker->getSuppressedIssues()
                            )) {
                                return false;
                            }

                            continue;
                        }

                        if (IssueBuffer::accepts(
                            new InvalidArrayAssignment(
                                'Cannot assign string offset for  ' . $var_id . ' of type ' . $value_type,
                                $statements_checker->getCheckedFileName(),
                                $stmt->getLine()
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        break;
                    }
                }
            }
            else {
                // we want to support multiple array types:
                // - Dictionaries (which have the type array<string,T>)
                // - pseudo-objects (which have the type array<string,mixed>)
                // - typed arrays (which have the type array<int,T>)
                // and completely freeform arrays
                //
                // When making assignments, we generally only know the shape of the array
                // as it is being created.
                if ($keyed_array_var_id) {
                    // when we have a pattern like
                    // $a = [];
                    // $a['b']['c']['d'] = 1;
                    // $a['c'] = 2;
                    // we need to create each type in turn
                    // so we get
                    // typeof $a['b']['c']['d'] => int
                    // typeof $a['b']['c'] => object-like{d:int}
                    // typeof $a['b'] => object-like{c:object-like{d:int}}
                    // typeof $a['c'] => int
                    // typeof $a => object-like{b:object-like{c:object-like{d:int}},c:int}

                    $context->vars_in_scope[$keyed_array_var_id] = $assignment_value_type;

                    $stmt->inferredType = $assignment_value_type;
                }

                if (!$nesting) {
                    /** @var Type\Generic|null */
                    $array_type = isset($context->vars_in_scope[$var_id]->types['array']) ? $context->vars_in_scope[$var_id]->types['array'] : null;

                    if ($assignment_key_type->hasString()
                        && $assignment_key_value
                        && !isset($context->vars_in_scope[$var_id])
                            || $context->vars_in_scope[$var_id]->hasObjectLike()
                            || ($array_type && $array_type->type_params[0]->isEmpty())
                    ) {
                        $assignment_type = new Type\Union([
                            new Type\ObjectLike(
                                'object-like',
                                [
                                    $assignment_key_value => $assignment_value_type
                                ]
                            )
                        ]);
                    }
                    else {
                        $assignment_type = new Type\Union([
                            new Type\Generic(
                                'array',
                                [
                                    $assignment_key_type,
                                    $assignment_value_type
                                ]
                            )
                        ]);
                    }

                    if (isset($context->vars_in_scope[$var_id])) {
                        $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                            $context->vars_in_scope[$var_id],
                            $assignment_type
                        );
                    }
                    else {
                        $context->vars_in_scope[$var_id] = $assignment_type;
                    }
                }
            }

        }
        else {
            $context->vars_in_scope[$var_id] = Type::getMixed();
        }
    }

    /**
     * @param  Type\Atomic $type
     * @param  string|null $var_id
     * @param  int         $line_number
     * @return Type\Atomic|null|false
     */
    protected static function refineArrayType(
        StatementsChecker $statements_checker,
        Type\Atomic $type,
        Type\Union $assignment_key_type,
        Type\Union $assignment_value_type,
        $var_id,
        $line_number
    ) {
        if ($type->value === 'null') {
            if (IssueBuffer::accepts(
                new NullReference(
                    'Cannot assign value on possibly null array ' . $var_id,
                    $statements_checker->getCheckedFileName(),
                    $line_number
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }

            return $type;
        }

        if ($type->value === 'string' && $assignment_value_type->hasString() && !$assignment_key_type->hasString()) {
            return;
        }

        if (!$type->isArray() && !$type->isObjectLike() && !ClassChecker::classImplements($type->value, 'ArrayAccess')) {
            if (IssueBuffer::accepts(
                new InvalidArrayAssignment(
                    'Cannot assign value on variable ' . $var_id . ' of type ' . $type->value . ' that does not implement ArrayAccess',
                    $statements_checker->getCheckedFileName(),
                    $line_number
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }

            return $type;
        }

        if ($type instanceof Type\Generic) {
            if ($type->isArray()) {
                if ($type->type_params[1]->isEmpty()) {
                    $type->type_params[0] = $assignment_key_type;
                    $type->type_params[1] = $assignment_value_type;
                    return $type;
                }

                if ((string) $type->type_params[0] !== (string) $assignment_key_type) {
                    $type->type_params[0] = Type::combineUnionTypes($type->type_params[0], $assignment_key_type);
                }

                if ((string) $type->type_params[1] !== (string) $assignment_value_type) {
                    $type->type_params[1] = Type::combineUnionTypes($type->type_params[1], $assignment_value_type);
                }
            }
        }

        return $type;
    }

    /**
     * @return false|null
     */
    protected static function checkAssignmentOperation(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\AssignOp $stmt,
        Context $context
    ) {
        if (self::check($statements_checker, $stmt->var, $context) === false) {
            return false;
        }

        return self::check($statements_checker, $stmt->expr, $context);
    }

    /**
     * @return false|null
     */
    protected static function checkMethodCall(StatementsChecker $statements_checker, PhpParser\Node\Expr\MethodCall $stmt, Context $context)
    {
        if (self::check($statements_checker, $stmt->var, $context) === false) {
            return false;
        }

        $class_type = null;
        $method_id = null;

        if ($stmt->var instanceof PhpParser\Node\Expr\Variable) {
            if (is_string($stmt->var->name) && $stmt->var->name === 'this' && !$statements_checker->getClassName()) {
                if (IssueBuffer::accepts(
                    new InvalidScope('Use of $this in non-class context', $statements_checker->getCheckedFileName(), $stmt->getLine()),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            }
        }

        $var_id = self::getVarId($stmt->var, $statements_checker->getAbsoluteClass(), $statements_checker->getNamespace(), $statements_checker->getAliasedClasses());

        $class_type = isset($context->vars_in_scope[$var_id]) ? $context->vars_in_scope[$var_id] : null;

        if (isset($stmt->var->inferredType)) {
            /** @var Type\Union */
            $class_type = $stmt->var->inferredType;
        }
        elseif (!$class_type) {
            $stmt->inferredType = Type::getMixed();
        }

        $source = $statements_checker->getSource();

        if ($stmt->var instanceof PhpParser\Node\Expr\Variable
            && $stmt->var->name === 'this'
            && is_string($stmt->name)
            && $source instanceof FunctionLikeChecker
        ) {
            $this_method_id = $source->getMethodId();

            if (($this_class = ClassLikeChecker::getThisClass()) &&
                (
                    $this_class === $statements_checker->getAbsoluteClass() ||
                    ClassChecker::classExtends($this_class, $statements_checker->getAbsoluteClass()) ||
                    trait_exists($statements_checker->getAbsoluteClass())
                )) {

                $method_id = $statements_checker->getAbsoluteClass() . '::' . strtolower($stmt->name);

                if ($statements_checker->checkInsideMethod($method_id, $context) === false) {
                    return false;
                }
            }
        }

        if (!$context->check_methods || !$context->check_classes) {
            return;
        }

        $has_mock = false;

        if ($class_type && is_string($stmt->name)) {
            $return_type = null;

            foreach ($class_type->types as $type) {
                $absolute_class = $type->value;

                $is_mock = self::isMock($absolute_class);

                $has_mock = $has_mock || $is_mock;

                switch ($absolute_class) {
                    case 'null':
                        if (IssueBuffer::accepts(
                            new NullReference(
                                'Cannot call method ' . $stmt->name . ' on possibly null variable ' . $var_id,
                                $statements_checker->getCheckedFileName(),
                                $stmt->getLine()
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }
                        break;

                    case 'int':
                    case 'bool':
                    case 'false':
                    case 'array':
                    case 'string':
                        if (IssueBuffer::accepts(
                            new InvalidArgument(
                                'Cannot call method ' . $stmt->name . ' on ' . $class_type . ' variable ' . $var_id,
                                $statements_checker->getCheckedFileName(),
                                $stmt->getLine()
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }
                        break;

                    case 'mixed':
                    case 'object':
                        if (IssueBuffer::accepts(
                            new MixedMethodCall(
                                'Cannot call method ' . $stmt->name . ' on a mixed variable ' . $var_id,
                                $statements_checker->getCheckedFileName(),
                                $stmt->getLine()
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }
                        break;

                    case 'static':
                        $absolute_class = (string) $context->self;
                        // fall through to default

                    default:
                        if (method_exists($absolute_class, '__call') || $is_mock || $context->isPhantomClass($absolute_class)) {
                            $return_type = Type::getMixed();
                            continue;
                        }

                        $does_class_exist = ClassLikeChecker::checkAbsoluteClassOrInterface(
                            $absolute_class,
                            $statements_checker->getCheckedFileName(),
                            $stmt->getLine(),
                            $statements_checker->getSuppressedIssues()
                        );

                        if (!$does_class_exist) {
                            return $does_class_exist;
                        }

                        $method_id = $absolute_class . '::' . strtolower($stmt->name);
                        $cased_method_id = $absolute_class . '::' . $stmt->name;

                        $does_method_exist = MethodChecker::checkMethodExists($cased_method_id, $statements_checker->getCheckedFileName(), $stmt->getLine(), $statements_checker->getSuppressedIssues());

                        if (!$does_method_exist) {
                            return $does_method_exist;
                        }

                        if (FunctionChecker::inCallMap($cased_method_id)) {
                            $return_type_candidate = FunctionChecker::getReturnTypeFromCallMap($method_id);
                        }
                        else {
                            if (MethodChecker::checkMethodVisibility($method_id, $context->self, $statements_checker->getSource(), $stmt->getLine(), $statements_checker->getSuppressedIssues()) === false) {
                                return false;
                            }

                            if (MethodChecker::checkMethodNotDeprecated($method_id, $statements_checker->getCheckedFileName(), $stmt->getLine(), $statements_checker->getSuppressedIssues()) === false) {
                                return false;
                            }

                            $return_type_candidate = MethodChecker::getMethodReturnTypes($method_id);
                        }

                        if ($return_type_candidate) {
                            $return_type_candidate = self::fleshOutTypes($return_type_candidate, $stmt->args, $absolute_class, $method_id);

                            if (!$return_type) {
                                $return_type = $return_type_candidate;
                            }
                            else {
                                $return_type = Type::combineUnionTypes($return_type_candidate, $return_type);
                            }
                        }
                        else {
                            $return_type = Type::getMixed();
                        }
                }
            }

            $stmt->inferredType = $return_type;
        }

        if (self::checkFunctionArguments($statements_checker, $stmt->args, $method_id, $context, $stmt->getLine(), $has_mock) === false) {
            return false;
        }
    }

    /**
     * @param  Type\Union                   $return_type
     * @param  array<PhpParser\Node\Arg>    $args
     * @param  string|null                  $calling_class
     * @param  string|null                  $method_id
     * @return Type\Union
     */
    public static function fleshOutTypes(Type\Union $return_type, array $args, $calling_class, $method_id)
    {
        $return_type = clone $return_type;

        $new_return_type_parts = [];

        foreach ($return_type->types as $key => $return_type_part) {
            $new_return_type_parts[] = self::fleshOutAtomicType($return_type_part, $args, $calling_class, $method_id);
        }

        return new Type\Union($new_return_type_parts);
    }

    /**
     * @param  Type\Atomic                  &$return_type
     * @param  array<PhpParser\Node\Arg>    $args
     * @param  string|null                  $calling_class
     * @param  string|null                  $method_id
     * @return Type\Atomic
     */
    protected static function fleshOutAtomicType(Type\Atomic $return_type, array $args, $calling_class, $method_id)
    {
        if ($return_type->value === '$this' || $return_type->value === 'static' || $return_type->value === 'self') {
            if (!$calling_class) {
                throw new \InvalidArgumentException('Cannot handle ' . $return_type->value . ' when $calling_class is empty', null);
            }

            $return_type->value = $calling_class;
        }
        else if ($return_type->value[0] === '$' && $method_id) {
            $method_params = MethodChecker::getMethodParams($method_id);

            foreach ($args as $i => $arg) {
                $method_param = $method_params[$i];

                if ($return_type->value === '$' . $method_param->name) {
                    $arg_value = $arg->value;
                    if ($arg_value instanceof PhpParser\Node\Scalar\String_) {
                        $return_type->value = preg_replace('/^\\\/', '', $arg_value->value);
                    }
                }
            }

            if ($return_type->value[0] === '$') {
                $return_type = new Type\Atomic('mixed');
            }
        }

        if ($return_type instanceof Type\Generic) {
            foreach ($return_type->type_params as &$type_param) {
                $type_param = self::fleshOutTypes($type_param, $args, $calling_class, $method_id);
            }
        }

        return $return_type;
    }

    /**
     * @return false|null
     */
    protected static function checkClosureUses(StatementsChecker $statements_checker, PhpParser\Node\Expr\Closure $stmt, Context $context)
    {
        foreach ($stmt->uses as $use) {
            if (!isset($context->vars_in_scope['$' . $use->var])) {
                if ($use->byRef) {
                    $context->vars_in_scope['$' . $use->var] = Type::getMixed();
                    $context->vars_possibly_in_scope['$' . $use->var] = true;
                    $statements_checker->registerVariable('$' . $use->var, $use->getLine());
                    return;
                }

                if (!isset($context->vars_possibly_in_scope['$' . $use->var])) {
                    if ($context->check_variables) {
                        IssueBuffer::add(
                            new UndefinedVariable('Cannot find referenced variable $' . $use->var, $statements_checker->getCheckedFileName(), $use->getLine())
                        );

                        return false;
                    }
                }

                if ($statements_checker->getFirstAppearance('$' . $use->var)) {
                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedVariable(
                            'Possibly undefined variable $' . $use->var . ', first seen on line ' . $statements_checker->getFirstAppearance('$' . $use->var),
                            $statements_checker->getCheckedFileName(),
                            $use->getLine()
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    return;
                }

                if ($context->check_variables) {
                    IssueBuffer::add(
                        new UndefinedVariable('Cannot find referenced variable $' . $use->var, $statements_checker->getCheckedFileName(), $use->getLine())
                    );

                    return false;
                }
            }
        }
    }

    /**
     * @return false|null
     */
    protected static function checkStaticCall(StatementsChecker $statements_checker, PhpParser\Node\Expr\StaticCall $stmt, Context $context)
    {
        if ($stmt->class instanceof PhpParser\Node\Expr\Variable || $stmt->class instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            // this is when calling $some_class::staticMethod() - which is a shitty way of doing things
            // because it can't be statically type-checked
            return;
        }

        $method_id = null;
        $absolute_class = null;

        $lhs_type = null;

        if ($stmt->class instanceof PhpParser\Node\Name) {
            $absolute_class = null;

            if (count($stmt->class->parts) === 1 && in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
                if ($stmt->class->parts[0] === 'parent') {
                    if ($statements_checker->getParentClass() === null) {
                        if (IssueBuffer::accepts(
                            new ParentNotFound('Cannot call method on parent as this class does not extend another', $statements_checker->getCheckedFileName(), $stmt->getLine()),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }
                    }

                    $absolute_class = $statements_checker->getParentClass();
                } else {
                    $absolute_class = ($statements_checker->getNamespace() ? $statements_checker->getNamespace() . '\\' : '') . $statements_checker->getClassName();
                }

                if ($context->isPhantomClass($absolute_class)) {
                    return;
                }
            }
            elseif ($context->check_classes) {

                $absolute_class = ClassLikeChecker::getAbsoluteClassFromName($stmt->class, $statements_checker->getNamespace(), $statements_checker->getAliasedClasses());

                if ($context->isPhantomClass($absolute_class)) {
                    return;
                }

                $does_class_exist = ClassLikeChecker::checkAbsoluteClassOrInterface(
                    $absolute_class,
                    $statements_checker->getCheckedFileName(),
                    $stmt->getLine(),
                    $statements_checker->getSuppressedIssues()
                );

                if (!$does_class_exist) {
                    return $does_class_exist;
                }
            }

            if ($stmt->class->parts === ['parent'] && is_string($stmt->name)) {
                if (ClassLikeChecker::getThisClass()) {
                    $method_id = $absolute_class . '::' . strtolower($stmt->name);

                    if ($statements_checker->checkInsideMethod($method_id, $context) === false) {
                        return false;
                    }
                }
            }

            if ($absolute_class) {
                $lhs_type = new Type\Union([new Type\Atomic($absolute_class)]);
            }
        }
        else {
            self::check($statements_checker, $stmt->class, $context);

            /** @var Type\Union */
            $lhs_type = $stmt->class->inferredType;
        }

        if (!$context->check_methods || !$lhs_type) {
            return;
        }

        $has_mock = false;

        foreach ($lhs_type->types as $lhs_type_part) {
            $absolute_class = $lhs_type_part->value;

            $is_mock = self::isMock($absolute_class);

            $has_mock = $has_mock || $is_mock;

            if (is_string($stmt->name) && !method_exists($absolute_class, '__callStatic') && !$is_mock) {
                $method_id = $absolute_class . '::' . strtolower($stmt->name);
                $cased_method_id = $absolute_class . '::' . $stmt->name;

                $does_method_exist = MethodChecker::checkMethodExists($cased_method_id, $statements_checker->getCheckedFileName(), $stmt->getLine(), $statements_checker->getSuppressedIssues());

                if (!$does_method_exist) {
                    return $does_method_exist;
                }

                if (MethodChecker::checkMethodVisibility($method_id, $context->self, $statements_checker->getSource(), $stmt->getLine(), $statements_checker->getSuppressedIssues()) === false) {
                    return false;
                }

                if ($statements_checker->isStatic()) {
                    if (MethodChecker::checkMethodStatic($method_id, $statements_checker->getCheckedFileName(), $stmt->getLine(), $statements_checker->getSuppressedIssues()) === false) {
                        return false;
                    }
                }
                else {
                    if ($stmt->class instanceof PhpParser\Node\Name && $stmt->class->parts[0] === 'self' && $stmt->name !== '__construct') {
                        if (MethodChecker::checkMethodStatic($method_id, $statements_checker->getCheckedFileName(), $stmt->getLine(), $statements_checker->getSuppressedIssues()) === false) {
                            return false;
                        }
                    }
                }

                if (MethodChecker::checkMethodNotDeprecated($method_id, $statements_checker->getCheckedFileName(), $stmt->getLine(), $statements_checker->getSuppressedIssues()) === false) {
                    return false;
                }

                $return_types = MethodChecker::getMethodReturnTypes($method_id);

                if ($return_types) {
                    $return_types = self::fleshOutTypes($return_types, $stmt->args, $stmt->class->parts === ['parent'] ? $statements_checker->getAbsoluteClass() : $absolute_class, $method_id);

                    if (isset($stmt->inferredType)) {
                        $stmt->inferredType = Type::combineUnionTypes($stmt->inferredType, $return_types);
                    }
                    else {
                        $stmt->inferredType = $return_types;
                    }
                }
            }

            if (self::checkFunctionArguments($statements_checker, $stmt->args, $method_id, $context, $stmt->getLine(), $has_mock) === false) {
                return false;
            }
        }

        return;
    }

    /**
     * @param  PhpParser\Node\Arg[]   $args
     * @param  string|null            $method_id
     * @param  Context                $context
     * @param  int                    $line_number
     * @param  boolean                $is_mock
     * @return false|null
     */
    protected static function checkFunctionArguments(StatementsChecker $statements_checker, array $args, $method_id, Context $context, $line_number, $is_mock = false)
    {
        $function_params = null;

        $is_variadic = false;

        $absolute_class = null;

        if ($method_id) {
            $function_params = FunctionLikeChecker::getParamsById($method_id, $args, $statements_checker->getFileName());

            if (strpos($method_id, '::')) {
                $absolute_class = explode('::', $method_id)[0];
                $is_variadic = $is_mock || MethodChecker::isVariadic($method_id);
            }
            else {
                $is_variadic = FunctionChecker::isVariadic(strtolower($method_id), $statements_checker->getFileName());
            }
        }

        foreach ($args as $argument_offset => $arg) {
            if ($arg->value instanceof PhpParser\Node\Expr\PropertyFetch) {
                if ($method_id) {
                    $by_ref = false;
                    $by_ref_type = null;

                    if ($function_params) {
                        $by_ref = $argument_offset < count($function_params) && $function_params[$argument_offset]->by_ref;
                        $by_ref_type = $by_ref && $argument_offset < count($function_params) ? clone $function_params[$argument_offset]->type : null;
                    }

                    if ($by_ref && $by_ref_type) {
                        self::assignByRefParam($statements_checker, $arg->value, $by_ref_type, $context);
                    }
                    else {
                        if (self::checkPropertyFetch($statements_checker, $arg->value, $context) === false) {
                            return false;
                        }
                    }
                }
                else {
                    $var_id = self::getVarId($arg->value, $statements_checker->getAbsoluteClass(), $statements_checker->getNamespace(), $statements_checker->getAliasedClasses());

                    if ($var_id && (!isset($context->vars_in_scope[$var_id]) || $context->vars_in_scope[$var_id]->isNull())) {
                        // we don't know if it exists, assume it's passed by reference
                        $context->vars_in_scope[$var_id] = Type::getMixed();
                        $context->vars_possibly_in_scope[$var_id] = true;
                        $statements_checker->registerVariable('$' . $var_id, $arg->value->getLine());
                    }
                }
            }
            elseif ($arg->value instanceof PhpParser\Node\Expr\Variable) {
                if ($method_id) {
                    $by_ref = false;
                    $by_ref_type = null;

                    if ($function_params) {
                        $by_ref = $argument_offset < count($function_params) && $function_params[$argument_offset]->by_ref;
                        $by_ref_type = $by_ref && $argument_offset < count($function_params) ? clone $function_params[$argument_offset]->type : null;
                    }

                    if (self::checkVariable($statements_checker, $arg->value, $context, $by_ref, $by_ref_type) === false) {
                        return false;
                    }

                } elseif (is_string($arg->value->name)) {
                    if (false || !isset($context->vars_in_scope['$' . $arg->value->name]) || $context->vars_in_scope['$' . $arg->value->name]->isNull()) {
                        // we don't know if it exists, assume it's passed by reference
                        $context->vars_in_scope['$' . $arg->value->name] = Type::getMixed();
                        $context->vars_possibly_in_scope['$' . $arg->value->name] = true;
                        $statements_checker->registerVariable('$' . $arg->value->name, $arg->value->getLine());
                    }
                }
            }
            else {
                if (self::check($statements_checker, $arg->value, $context) === false) {
                    return false;
                }
            }
        }

        // we need to do this calculation after the above vars have already processed
        $function_params = $method_id ? FunctionLikeChecker::getParamsById($method_id, $args, $statements_checker->getFileName()) : [];

        $cased_method_id = $method_id;

        if ($method_id && strpos($method_id, '::')) {
            $cased_method_id = MethodChecker::getCasedMethodId($method_id);
        }

        if ($function_params) {
            foreach ($function_params as $function_param) {
                $is_variadic = $is_variadic || $function_param->is_variadic;
            }
        }


        $has_packed_var = false;

        foreach ($args as $arg) {
            $has_packed_var = $has_packed_var || $arg->unpack;
        }

        foreach ($args as $argument_offset => $arg) {
            if ($method_id && $cased_method_id && isset($arg->value->inferredType)) {
                if (count($function_params) > $argument_offset) {
                    $param_type = $function_params[$argument_offset]->type;

                    // for now stop when we encounter a variadic param pr a packed argument
                    if ($function_params[$argument_offset]->is_variadic || $arg->unpack) {
                        break;
                    }

                    if (self::checkFunctionArgumentType($statements_checker,
                        $arg->value->inferredType,
                        self::fleshOutTypes(
                            clone $param_type,
                            [],
                            $absolute_class,
                            $method_id
                        ),
                        $cased_method_id,
                        $argument_offset,
                        $arg->value->getLine()
                    ) === false
                    ) {
                        return false;
                    }
                }
            }
        }

        if ($method_id) {
            if (!$is_variadic
                && count($args) > count($function_params)
                && (!count($function_params) || $function_params[count($function_params) - 1]->name !== '...=')
            ) {
                if (IssueBuffer::accepts(
                    new TooManyArguments('Too many arguments for method ' . ($cased_method_id ?: $method_id), $statements_checker->getCheckedFileName(), $line_number),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }

                return;
            }

            if (!$has_packed_var && count($args) < count($function_params)) {
                for ($i = count($args); $i < count($function_params); $i++) {
                    $param = $function_params[$i];

                    if (!$param->is_optional && !$param->is_variadic) {
                        if (IssueBuffer::accepts(
                            new TooFewArguments('Too few arguments for method ' . $cased_method_id, $statements_checker->getCheckedFileName(), $line_number),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        break;
                    }
                }
            }
        }
    }

    /**
     * @return null|false
     */
    protected static function checkConstFetch(StatementsChecker $statements_checker, PhpParser\Node\Expr\ConstFetch $stmt, Context $context)
    {
        $const_name = implode('', $stmt->name->parts);
        switch (strtolower($const_name)) {
            case 'null':
                $stmt->inferredType = Type::getNull();
                break;

            case 'false':
                // false is a subtype of bool
                $stmt->inferredType = Type::getFalse();
                break;

            case 'true':
                $stmt->inferredType = Type::getBool();
                break;

            default:
                if ($const_type = $statements_checker->getConstType($const_name)) {
                    $stmt->inferredType = clone $const_type;
                }
                elseif ($context->check_consts && !defined($const_name)) {
                    if (IssueBuffer::accepts(
                        new UndefinedConstant('Const ' . $const_name . ' is not defined', $statements_checker->getCheckedFileName(), $stmt->getLine()),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }
        }
    }

    /**
     * @return null|false
     */
    protected static function checkClassConstFetch(StatementsChecker $statements_checker, PhpParser\Node\Expr\ClassConstFetch $stmt, Context $context)
    {
        if ($context->check_consts && $stmt->class instanceof PhpParser\Node\Name && $stmt->class->parts !== ['static']) {
            if ($stmt->class->parts === ['self']) {
                $absolute_class = (string)$context->self;
            }
            else {
                $absolute_class = ClassLikeChecker::getAbsoluteClassFromName($stmt->class, $statements_checker->getNamespace(), $statements_checker->getAliasedClasses());
                if (ClassLikeChecker::checkAbsoluteClassOrInterface($absolute_class, $statements_checker->getCheckedFileName(), $stmt->getLine(), $statements_checker->getSuppressedIssues()) === false) {
                    return false;
                }
            }

            $const_id = $absolute_class . '::' . $stmt->name;

            if ($stmt->name === 'class') {
                $stmt->inferredType = Type::getString();
                return;
            }

            $class_constants = ClassLikeChecker::getConstantsForClass($absolute_class, \ReflectionProperty::IS_PUBLIC);

            if (!isset($class_constants[$stmt->name])) {
                if (IssueBuffer::accepts(
                    new UndefinedConstant('Const ' . $const_id . ' is not defined', $statements_checker->getCheckedFileName(), $stmt->getLine()),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            }
            else {
                $stmt->inferredType = $class_constants[$stmt->name];
            }

            return;
        }

        if ($stmt->class instanceof PhpParser\Node\Expr) {
            if (self::check($statements_checker, $stmt->class, $context) === false) {
                return false;
            }
        }
    }

    /**
     * @return null|false
     */
    protected static function checkStaticPropertyFetch(StatementsChecker $statements_checker, PhpParser\Node\Expr\StaticPropertyFetch $stmt, Context $context)
    {
        if ($stmt->class instanceof PhpParser\Node\Expr\Variable || $stmt->class instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            // @todo check this
            return;
        }

        $method_id = null;
        $absolute_class = null;

        if ($stmt->class instanceof PhpParser\Node\Name) {
            if (count($stmt->class->parts) === 1 && in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
                if ($stmt->class->parts[0] === 'parent') {
                    $absolute_class = $statements_checker->getParentClass();
                } else {
                    $absolute_class = ($statements_checker->getNamespace() ? $statements_checker->getNamespace() . '\\' : '') . $statements_checker->getClassName();
                }

                if ($context->isPhantomClass($absolute_class)) {
                    return null;
                }
            }
            elseif ($context->check_classes) {
                $absolute_class = ClassLikeChecker::getAbsoluteClassFromName($stmt->class, $statements_checker->getNamespace(), $statements_checker->getAliasedClasses());

                if ($context->isPhantomClass($absolute_class)) {
                    return;
                }

                if (ClassLikeChecker::checkAbsoluteClassOrInterface($absolute_class, $statements_checker->getCheckedFileName(), $stmt->getLine(), $statements_checker->getSuppressedIssues()) === false) {
                    return false;
                }
            }

            $stmt->class->inferredType = $absolute_class ? new Type\Union([new Type\Atomic($absolute_class)]) : null;
        }

        if ($absolute_class && $context->check_variables && is_string($stmt->name) && !self::isMock($absolute_class)) {
            $var_id = self::getVarId($stmt, $statements_checker->getAbsoluteClass(), $statements_checker->getNamespace(), $statements_checker->getAliasedClasses());

            if ($var_id && isset($context->vars_in_scope[$var_id])) {
                // we don't need to check anything
                $stmt->inferredType = $context->vars_in_scope[$var_id];
                return;
            }

            if ($absolute_class === $context->self
                || ($statements_checker->getSource()->getSource() instanceof TraitChecker && $absolute_class === $statements_checker->getSource()->getAbsoluteClass())
            ) {
                $class_visibility = \ReflectionProperty::IS_PRIVATE;
            }
            elseif ($context->self && ClassChecker::classExtends($context->self, $absolute_class)) {
                $class_visibility = \ReflectionProperty::IS_PROTECTED;
            }
            else {
                $class_visibility = \ReflectionProperty::IS_PUBLIC;
            }

            $visible_class_properties = ClassLikeChecker::getStaticPropertiesForClass(
                $absolute_class,
                $class_visibility
            );

            if (!isset($visible_class_properties[$stmt->name])) {
                $all_class_properties = [];

                if ($absolute_class !== $context->self) {
                    $all_class_properties = ClassLikeChecker::getStaticPropertiesForClass(
                        $absolute_class,
                        \ReflectionProperty::IS_PRIVATE
                    );
                }

                if ($all_class_properties && isset($all_class_properties[$stmt->name])) {
                    IssueBuffer::add(
                        new InvisibleProperty('Static property ' . $var_id . ' is not visible in this context', $statements_checker->getCheckedFileName(), $stmt->getLine())
                    );
                }
                else {
                    IssueBuffer::add(
                        new UndefinedPropertyFetch('Static property ' . $var_id . ' does not exist', $statements_checker->getCheckedFileName(), $stmt->getLine())
                    );
                }

                return false;
            }

            $context->vars_in_scope[$var_id] = clone $visible_class_properties[$stmt->name];
            $stmt->inferredType = clone  $visible_class_properties[$stmt->name];
        }
    }

    /**
     * @param  PhpParser\Node\Expr\Yield_  $stmt
     * @param  Context                     $context
     * @return false|null
     */
    protected static function checkYield(StatementsChecker $statements_checker, PhpParser\Node\Expr\Yield_ $stmt, Context $context)
    {
        $type_in_comments = CommentChecker::getTypeFromComment((string) $stmt->getDocComment(), $context, $statements_checker->getSource());

        if ($stmt->key) {
            if (self::check($statements_checker, $stmt->key, $context) === false) {
                return false;
            }
        }

        if ($stmt->value) {
            if (self::check($statements_checker, $stmt->value, $context) === false) {
                return false;
            }

            if ($type_in_comments) {
                $stmt->inferredType = $type_in_comments;
            }
            elseif (isset($stmt->value->inferredType)) {
                $stmt->inferredType = $stmt->value->inferredType;
            }
            else {
                $stmt->inferredType = Type::getMixed();
            }
        }
        else {
            $stmt->inferredType = Type::getNull();
        }
    }

    /**
     * @param  PhpParser\Node\Expr\YieldFrom    $stmt
     * @param  Context                          $context
     * @return false|null
     */
    protected static function checkYieldFrom(StatementsChecker $statements_checker, PhpParser\Node\Expr\YieldFrom $stmt, Context $context)
    {
        if (self::check($statements_checker, $stmt->expr, $context) === false) {
            return false;
        }

        if (isset($stmt->expr->inferredType)) {
            $stmt->inferredType = $stmt->expr->inferredType;
        }
    }

    protected static function checkTernary(StatementsChecker $statements_checker, PhpParser\Node\Expr\Ternary $stmt, Context $context)
    {
        if (self::check($statements_checker, $stmt->cond, $context) === false) {
            return false;
        }

        $t_if_context = clone $context;

        if ($stmt->cond instanceof PhpParser\Node\Expr\BinaryOp) {
            $reconcilable_if_types = TypeChecker::getReconcilableTypeAssertions(
                $stmt->cond,
                $statements_checker->getAbsoluteClass(),
                $statements_checker->getNamespace(),
                $statements_checker->getAliasedClasses()
            );
            $negatable_if_types = TypeChecker::getNegatableTypeAssertions(
                $stmt->cond,
                $statements_checker->getAbsoluteClass(),
                $statements_checker->getNamespace(),
                $statements_checker->getAliasedClasses()
            );
        }
        else {
            $reconcilable_if_types = $negatable_if_types = TypeChecker::getTypeAssertions(
                $stmt->cond,
                $statements_checker->getAbsoluteClass(),
                $statements_checker->getNamespace(),
                $statements_checker->getAliasedClasses());
        }

        $if_return_type = null;

        $t_if_vars_in_scope_reconciled =
            TypeChecker::reconcileKeyedTypes(
                $reconcilable_if_types,
                $t_if_context->vars_in_scope,
                $statements_checker->getCheckedFileName(),
                $stmt->getLine(),
                $statements_checker->getSuppressedIssues()
            );

        if ($t_if_vars_in_scope_reconciled === false) {
            return false;
        }

        $t_if_context->vars_in_scope = $t_if_vars_in_scope_reconciled;

        if ($stmt->if) {
            if (self::check($statements_checker, $stmt->if, $t_if_context) === false) {
                return false;
            }
        }

        $t_else_context = clone $context;

        if ($negatable_if_types) {
            $negated_if_types = TypeChecker::negateTypes($negatable_if_types);

            $t_else_vars_in_scope_reconciled = TypeChecker::reconcileKeyedTypes(
                $negated_if_types,
                $t_else_context->vars_in_scope,
                $statements_checker->getCheckedFileName(),
                $stmt->getLine(),
                $statements_checker->getSuppressedIssues()
            );

            if ($t_else_vars_in_scope_reconciled === false) {
                return false;
            }
            $t_else_context->vars_in_scope = $t_else_vars_in_scope_reconciled;
        }

        if (self::check($statements_checker, $stmt->else, $t_else_context) === false) {
            return false;
        }

        $lhs_type = null;

        if ($stmt->if) {
            if (isset($stmt->if->inferredType)) {
                $lhs_type = $stmt->if->inferredType;
            }
        }
        elseif ($stmt->cond) {
            if (isset($stmt->cond->inferredType)) {
                $if_return_type_reconciled = TypeChecker::reconcileTypes('!empty', $stmt->cond->inferredType, '', $statements_checker->getCheckedFileName(), $stmt->getLine(), $statements_checker->getSuppressedIssues());

                if ($if_return_type_reconciled === false) {
                    return false;
                }

                $lhs_type = $if_return_type_reconciled;
            }
        }

        if (!$lhs_type || !isset($stmt->else->inferredType)) {
            $stmt->inferredType = Type::getMixed();
        }
        else {
            $stmt->inferredType = Type::combineUnionTypes($lhs_type, $stmt->else->inferredType);
        }
    }

    protected static function checkBooleanNot(StatementsChecker $statements_checker, PhpParser\Node\Expr\BooleanNot $stmt, Context $context)
    {
        return self::check($statements_checker, $stmt->expr, $context);
    }

    protected static function checkEmpty(StatementsChecker $statements_checker, PhpParser\Node\Expr\Empty_ $stmt, Context $context)
    {
        return self::check($statements_checker, $stmt->expr, $context);
    }

    /**
     * @param  Type\Union $input_type
     * @param  Type\Union $param_type
     * @param  string     $cased_method_id
     * @param  int        $argument_offset
     * @param  int        $line_number
     * @return null|false
     */
    protected static function checkFunctionArgumentType(
        StatementsChecker $statements_checker,
        Type\Union $input_type,
        Type\Union $param_type,
        $cased_method_id,
        $argument_offset,
        $line_number
    ) {
        if ($param_type->isMixed()) {
            return;
        }

        if ($input_type->isMixed()) {
            if (IssueBuffer::accepts(
                new MixedArgument(
                    'Argument ' . ($argument_offset + 1) . ' of ' . $cased_method_id . ' cannot be mixed, expecting ' . $param_type,
                    $statements_checker->getCheckedFileName(),
                    $line_number
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }

            return;
        }

        if ($input_type->isNullable() && !$param_type->isNullable()) {
            if (IssueBuffer::accepts(
                new NullReference(
                    'Argument ' . ($argument_offset + 1) . ' of ' . $cased_method_id . ' cannot be null, possibly null value provided',
                    $statements_checker->getCheckedFileName(),
                    $line_number
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }
        }

        $type_match_found = FunctionLikeChecker::doesParamMatch($input_type, $param_type, $scalar_type_match_found, $coerced_type);

        if ($coerced_type) {
            if (IssueBuffer::accepts(
                new TypeCoercion(
                    'Argument ' . ($argument_offset + 1) . ' of ' . $cased_method_id . ' expects ' . $param_type . ', parent type ' . $input_type . ' provided',
                    $statements_checker->getCheckedFileName(),
                    $line_number
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }
        }

        if (!$type_match_found) {
            if ($scalar_type_match_found) {
                if (IssueBuffer::accepts(
                    new InvalidScalarArgument(
                        'Argument ' . ($argument_offset + 1) . ' of ' . $cased_method_id . ' expects ' . $param_type . ', ' . $input_type . ' provided',
                        $statements_checker->getCheckedFileName(),
                        $line_number
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            }
            else if (IssueBuffer::accepts(
                new InvalidArgument(
                    'Argument ' . ($argument_offset + 1) . ' of ' . $cased_method_id . ' expects ' . $param_type . ', ' . $input_type . ' provided',
                    $statements_checker->getCheckedFileName(),
                    $line_number
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }
        }
    }

    /**
     * @param  PhpParser\Node\Expr\FuncCall $stmt
     * @param  Context                      $context
     * @return false|null
     */
    protected static function checkFunctionCall(StatementsChecker $statements_checker, PhpParser\Node\Expr\FuncCall $stmt, Context $context)
    {
        $method = $stmt->name;

        if ($method instanceof PhpParser\Node\Name) {
            $first_arg = $stmt->args[0];

            if ($method->parts === ['method_exists']) {
                $context->check_methods = false;

            }
            elseif ($method->parts === ['class_exists']) {
                if ($first_arg->value instanceof PhpParser\Node\Scalar\String_) {
                    $context->addPhantomClass($first_arg->value->value);
                }
                else {
                    $context->check_classes = false;
                }

            }
            elseif ($method->parts === ['function_exists']) {
                $context->check_functions = false;

            }
            elseif ($method->parts === ['is_callable']) {
                $context->check_methods = false;
                $context->check_functions = false;
            }
            elseif ($method->parts === ['defined']) {
                $context->check_consts = false;

            }
            elseif ($method->parts === ['extract']) {
                $context->check_variables = false;

            }
            elseif ($method->parts === ['var_dump'] || $method->parts === ['die'] || $method->parts === ['exit']) {
                if (IssueBuffer::accepts(
                    new ForbiddenCode('Unsafe ' . implode('', $method->parts), $statements_checker->getCheckedFileName(), $stmt->getLine()),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            }
            elseif ($method->parts === ['define']) {
                if ($first_arg->value instanceof PhpParser\Node\Scalar\String_) {
                    $second_arg = $stmt->args[1];
                    self::check($statements_checker, $second_arg->value, $context);
                    $const_name = $first_arg->value->value;

                    $statements_checker->setConstType(
                        $const_name,
                        isset($second_arg->value->inferredType) ? $second_arg->value->inferredType : Type::getMixed()
                    );
                }
                else {
                    $context->check_consts = false;
                }
            }
        }

        $method_id = null;

        if ($context->check_functions) {
            if (!($stmt->name instanceof PhpParser\Node\Name)) {
                return;
            }

            $method_id = implode('', $stmt->name->parts);

            if ($context->self) {
                //$method_id = $statements_checker->getAbsoluteClass() . '::' . $method_id;
            }

            $in_call_map = FunctionChecker::inCallMap($method_id);

            if (!$in_call_map && self::checkFunctionExists($statements_checker, $method_id, $context, $stmt->getLine()) === false) {
                return false;
            }

            if (self::checkFunctionArguments($statements_checker, $stmt->args, $method_id, $context, $stmt->getLine()) === false) {
                return false;
            }

            if ($in_call_map) {
                $stmt->inferredType = FunctionChecker::getReturnTypeFromCallMap($method_id, $stmt->args, $statements_checker->getCheckedFileName(), $stmt->getLine(), $statements_checker->getSuppressedIssues());
            }
            else {
                try {
                    $stmt->inferredType = FunctionChecker::getFunctionReturnTypes($method_id, $statements_checker->getCheckedFileName());
                }
                catch (\InvalidArgumentException $e) {
                    // this can happen when the function was defined in the Config startup script
                    $stmt->inferredType = Type::getMixed();
                }
            }
        }

        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['get_class'] && $stmt->args) {
            $var = $stmt->args[0]->value;

            if ($var instanceof PhpParser\Node\Expr\Variable && is_string($var->name)) {
                $stmt->inferredType = new Type\Union([new Type\T('$' . $var->name)]);
            }
        }
    }

    /**
     * @param  PhpParser\Node\Expr\ArrayDimFetch $stmt
     * @param  array                             &$context->vars_in_scope
     * @param  array                             &$context->vars_possibly_in_scope
     * @return false|null
     */
    protected static function checkArrayAccess(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        Context $context,
        $array_assignment = false,
        Type\Union $assignment_key_type = null,
        Type\Union $assignment_value_type = null,
        $assignment_key_value = null
    ) {
        $var_type = null;
        $key_type = null;
        $key_value = null;

        $nesting = 0;
        $var_id = self::getVarId(
            $stmt->var,
            $statements_checker->getAbsoluteClass(),
            $statements_checker->getNamespace(),
            $statements_checker->getAliasedClasses(),
            $nesting
        );

        $is_object = $var_id && isset($context->vars_in_scope[$var_id]) && $context->vars_in_scope[$var_id]->hasObjectType();
        $array_var_id = self::getArrayVarId($stmt->var, $statements_checker->getAbsoluteClass(), $statements_checker->getNamespace(), $statements_checker->getAliasedClasses());
        $keyed_array_var_id = $array_var_id && $stmt->dim instanceof PhpParser\Node\Scalar\String_
                                ? $array_var_id . '[\'' . $stmt->dim->value . '\']'
                                : null;

        if ($stmt->dim && self::check($statements_checker, $stmt->dim, $context) === false) {
            return false;
        }

        if ($stmt->dim) {
            if (isset($stmt->dim->inferredType)) {
                /** @var Type\Union */
                $key_type = $stmt->dim->inferredType;

                if ($stmt->dim instanceof PhpParser\Node\Scalar\String_) {
                    $key_value = $stmt->dim->value;
                }
            }
            else {
                $key_type = Type::getMixed();
            }
        }
        else {
            $key_type = Type::getInt();
        }

        $keyed_assignment_type = null;

        if ($array_assignment && $assignment_key_type && $assignment_value_type) {
            $keyed_assignment_type =
                $keyed_array_var_id && isset($context->vars_in_scope[$keyed_array_var_id])
                    ? $context->vars_in_scope[$keyed_array_var_id]
                    : null;

            if (!$keyed_assignment_type || $keyed_assignment_type->isEmpty()) {
                if (!$assignment_key_type->isMixed() && !$assignment_key_type->hasInt() && $assignment_key_value) {
                    $keyed_assignment_type = new Type\Union([
                        new Type\ObjectLike(
                            'object-like',
                            [
                                $assignment_key_value => $assignment_value_type
                            ]
                        )
                    ]);
                }
                else {
                    $keyed_assignment_type = Type::getEmptyArray();
                    /** @var Type\Generic */
                    $keyed_assignment_type_array = $keyed_assignment_type->types['array'];
                    $keyed_assignment_type_array->type_params[0] = $assignment_key_type;
                    $keyed_assignment_type_array->type_params[1] = $assignment_value_type;
                }
            }
            else {
                foreach ($keyed_assignment_type->types as &$type) {
                    if ($type->isScalarType() && !$type->isString()) {
                        if (IssueBuffer::accepts(
                            new InvalidArrayAssignment(
                                'Cannot assign value on variable ' . $var_id . ' of scalar type ' . $type->value,
                                $statements_checker->getCheckedFileName(),
                                $stmt->getLine()
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        continue;
                    }

                    if ($type instanceof Type\Generic) {
                        $refined_type = self::refineArrayType(
                            $statements_checker,
                            $type,
                            $assignment_key_type,
                            $assignment_value_type,
                            $var_id,
                            $stmt->getLine()
                        );

                        if ($refined_type === false) {
                            return false;
                        }

                        if ($refined_type === null) {
                            continue;
                        }

                        $type = $refined_type;
                    }
                    elseif ($type instanceof Type\ObjectLike && $assignment_key_value) {
                        if (isset($type->properties[$assignment_key_value])) {
                            $type->properties[$assignment_key_value] = Type::combineUnionTypes(
                                $type->properties[$assignment_key_value],
                                $assignment_value_type
                            );
                        }
                        else {
                            $type->properties[$assignment_key_value] = $assignment_value_type;
                        }
                    }
                }
            }
        }

        if (self::check($statements_checker, $stmt->var, $context, $array_assignment, $key_type, $keyed_assignment_type, $key_value) === false) {
            return false;
        }

        if (isset($stmt->var->inferredType)) {
            /** @var Type\Union */
            $var_type = $stmt->var->inferredType;

            foreach ($var_type->types as &$type) {
                if ($type instanceof Type\Generic || $type instanceof Type\ObjectLike) {
                    $value_index = null;

                    if ($type instanceof Type\Generic) {
                        // create a union type to pass back to the statement
                        $value_index = count($type->type_params) - 1;

                        if ($value_index) {
                            // if we're assigning to an empty array with a key offset, refashion that array
                            if ($array_assignment && $type->type_params[0]->isEmpty()) {
                                if ($key_type) {
                                    $type->type_params[0] = $key_type;
                                }
                            }
                            else {
                                if ($key_type) {
                                    $key_type = Type::combineUnionTypes($key_type, $type->type_params[0]);
                                }
                                else {
                                    $key_type = $type->type_params[0];
                                }
                            }
                        }
                    }

                    if ($array_assignment && !$is_object) {
                        // if we're in an array assignment then we need to create some variables
                        // e.g.
                        // $a = [];
                        // $a['b']['c']['d'] = 3;
                        //
                        // means we need add $a['b'], $a['b']['c'] to the current context
                        // (but not $a['b']['c']['d'], which is handled in checkArrayAssignment)
                        if ($keyed_array_var_id && $keyed_assignment_type) {
                            if (isset($context->vars_in_scope[$keyed_array_var_id])) {
                                $context->vars_in_scope[$keyed_array_var_id] = Type::combineUnionTypes(
                                    $keyed_assignment_type,
                                    $context->vars_in_scope[$keyed_array_var_id]
                                );
                            }
                            else {
                                $context->vars_in_scope[$keyed_array_var_id] = $keyed_assignment_type;
                            }

                            $stmt->inferredType = $keyed_assignment_type;
                        }

                        if ($array_var_id === $var_id) {
                            if ($type instanceof Type\ObjectLike || ($type->isArray() && !$key_type->hasInt() && $type->type_params[1]->isEmpty())) {
                                $properties = $key_value ? [$key_value => $keyed_assignment_type] : [];

                                $assignment_type = new Type\Union([
                                    new Type\ObjectLike(
                                        'object-like',
                                        $properties
                                    )
                                ]);
                            }
                            else {
                                $assignment_type = new Type\Union([
                                    new Type\Generic(
                                        'array',
                                        [
                                            $key_type,
                                            $keyed_assignment_type
                                        ]
                                    )
                                ]);
                            }

                            if (isset($context->vars_in_scope[$var_id])) {
                                $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                                    $context->vars_in_scope[$var_id],
                                    $assignment_type
                                );
                            }
                            else {
                                $context->vars_in_scope[$var_id] = $assignment_type;
                            }
                        }

                        if ($type instanceof Type\Generic && $type->type_params[$value_index]->isEmpty()) {
                            $empty_type = Type::getEmptyArray();

                            if (!isset($stmt->inferredType)) {
                                // if in array assignment and the referenced variable does not have
                                // an array at this level, create one
                                $stmt->inferredType = $empty_type;
                            }

                            $context_type = clone $context->vars_in_scope[$var_id];

                            $array_type = $context_type;

                            for ($i = 0; $i < $nesting + 1; $i++) {
                                if ($array_type->hasArray()) {
                                    /** @var Type\Generic */
                                    $atomic_array = $array_type->types['array'];

                                    if ($i < $nesting) {
                                        if ($atomic_array->type_params[1]->isEmpty()) {
                                            $new_empty = clone $empty_type;
                                            /** @var Type\Generic */
                                            $new_atomic_empty = $new_empty->types['array'];
                                            $new_atomic_empty->type_params[0] = $key_type;

                                            $atomic_array->type_params[1] = $new_empty;
                                            continue;
                                        }

                                        $array_type = $atomic_array->type_params[1];
                                    }
                                    else {
                                        $atomic_array->type_params[0] = $key_type;
                                    }
                                }
                            }

                            $context->vars_in_scope[$var_id] = $context_type;
                        }
                    }
                    elseif ($type instanceof Type\Generic && $value_index !== null) {
                        $stmt->inferredType = $type->type_params[$value_index];
                    }
                    elseif ($type instanceof Type\ObjectLike) {
                        if ($key_value && isset($type->properties[$key_value])) {
                            $stmt->inferredType = clone $type->properties[$key_value];
                        }
                        elseif ($key_type->hasInt()) {
                            $object_like_keys = array_keys($type->properties);
                            if ($object_like_keys) {
                                if (count($object_like_keys) === 1) {
                                    $expected_keys_string = '\'' . $object_like_keys[0] . '\'';
                                }
                                else {
                                    $last_key = array_pop($object_like_keys);
                                    $expected_keys_string = '\'' . implode('\', \'', $object_like_keys) . '\' or \'' . $last_key . '\'';
                                }
                            }
                            else {
                                $expected_keys_string = 'string';
                            }

                            if (IssueBuffer::accepts(
                                new InvalidArrayAccess(
                                    'Cannot access value on object-like variable ' . $var_id . ' using int offset - expecting ' . $expected_keys_string,
                                    $statements_checker->getCheckedFileName(),
                                    $stmt->getLine()
                                ),
                                $statements_checker->getSuppressedIssues()
                            )) {
                                return false;
                            }
                        }
                    }
                }
                elseif ($type->isString()) {
                    if ($key_type) {
                        $key_type = Type::combineUnionTypes($key_type, Type::getInt());
                    }
                    else {
                        $key_type = Type::getInt();
                    }

                    $stmt->inferredType = Type::getString();
                }
            }
        }

        if ($keyed_array_var_id && isset($context->vars_in_scope[$keyed_array_var_id])) {
            $stmt->inferredType = $context->vars_in_scope[$keyed_array_var_id];
        }

        if (!isset($stmt->inferredType)) {
            $stmt->inferredType = Type::getMixed();
        }

        if (!$key_type) {
            $key_type = new Type\Union([
                new Type\Atomic('int'),
                new Type\Atomic('string')
            ]);
        }

        if ($stmt->dim) {
            if (isset($stmt->dim->inferredType) && $key_type && !$key_type->isEmpty()) {
                foreach ($stmt->dim->inferredType->types as $at) {
                    if (($at->isMixed() || $at->isEmpty()) && !$key_type->isMixed()) {
                        if (IssueBuffer::accepts(
                            new MixedArrayOffset(
                                'Cannot access value on variable ' . $var_id . ' using mixed offset - expecting ' . $key_type,
                                $statements_checker->getCheckedFileName(),
                                $stmt->getLine()
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }
                    }
                    elseif (!$at->isIn($key_type)) {
                        if (IssueBuffer::accepts(
                            new InvalidArrayAccess(
                                'Cannot access value on variable ' . $var_id . ' using ' . $at . ' offset - expecting ' . $key_type,
                                $statements_checker->getCheckedFileName(),
                                $stmt->getLine()
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }
                    }
                }
            }
        }
    }

    /**
     * @param  PhpParser\Node\Scalar\Encapsed $stmt
     * @param  Context                        $context
     * @return false|null
     */
    protected static function checkEncapsulatedString(StatementsChecker $statements_checker, PhpParser\Node\Scalar\Encapsed $stmt, Context $context)
    {
        /** @var PhpParser\Node\Expr $part */
        foreach ($stmt->parts as $part) {
            if (self::check($statements_checker, $part, $context) === false) {
                return false;
            }
        }

        $stmt->inferredType = Type::getString();
    }

    /**
     * @param  string  $function_id
     * @param  Context $context
     * @return bool
     */
    protected static function checkFunctionExists(StatementsChecker $statements_checker, $function_id, Context $context, $line_number)
    {
        $cased_function_id = $function_id;
        $function_id = strtolower($function_id);

        if (!FunctionChecker::functionExists($function_id, $context->file_name)) {
            if (IssueBuffer::accepts(
                new UndefinedFunction('Function ' . $cased_function_id . ' does not exist', $statements_checker->getCheckedFileName(), $line_number),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  string  $absolute_class
     * @return boolean
     */
    public static function isMock($absolute_class)
    {
        return in_array($absolute_class, Config::getInstance()->getMockClasses());
    }

    public static function clearCache()
    {
        self::$reflection_functions = [];
    }
}
