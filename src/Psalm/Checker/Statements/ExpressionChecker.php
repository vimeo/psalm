<?php
namespace Psalm\Checker\Statements;

use PhpParser;
use Psalm\Checker\ClassChecker;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\ClosureChecker;
use Psalm\Checker\CommentChecker;
use Psalm\Checker\MethodChecker;
use Psalm\Checker\Statements\Expression\AssertionFinder;
use Psalm\Checker\Statements\Expression\AssignmentChecker;
use Psalm\Checker\Statements\Expression\CallChecker;
use Psalm\Checker\Statements\Expression\FetchChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\TypeChecker;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Issue\ForbiddenCode;
use Psalm\Issue\InvalidClone;
use Psalm\Issue\InvalidOperand;
use Psalm\Issue\InvalidScope;
use Psalm\Issue\InvalidStaticVariable;
use Psalm\Issue\MixedOperand;
use Psalm\Issue\NullOperand;
use Psalm\Issue\PossiblyUndefinedVariable;
use Psalm\Issue\UndefinedVariable;
use Psalm\Issue\UnrecognizedExpression;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\Type\Atomic\Generic;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TVoid;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNumericString;

class ExpressionChecker
{
    /**
     * @var array<string,array<int,string>>
     */
    protected static $reflection_functions = [];

    /**
     * @param   StatementsChecker   $statements_checker
     * @param   PhpParser\Node\Expr $stmt
     * @param   Context             $context
     * @param   bool                $array_assignment
     * @param   Type\Union|null     $assignment_key_type
     * @param   Type\Union|null     $assignment_value_type
     * @param   string|null         $assignment_key_value
     * @return  false|null
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr $stmt,
        Context $context,
        $array_assignment = false,
        Type\Union $assignment_key_type = null,
        Type\Union $assignment_value_type = null,
        $assignment_key_value = null
    ) {
        if ($stmt instanceof PhpParser\Node\Expr\Variable) {
            if (self::analyzeVariable($statements_checker, $stmt, $context, false, null, $array_assignment) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Assign) {
            $assignment_type = AssignmentChecker::analyze(
                $statements_checker,
                $stmt->var,
                $stmt->expr,
                null,
                $context,
                (string)$stmt->getDocComment()
            );

            if ($assignment_type === false) {
                return false;
            }

            $stmt->inferredType = $assignment_type;
        } elseif ($stmt instanceof PhpParser\Node\Expr\AssignOp) {
            if (AssignmentChecker::analyzeAssignmentOperation($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\MethodCall) {
            if (CallChecker::analyzeMethodCall($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\StaticCall) {
            if (CallChecker::analyzeStaticCall($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\ConstFetch) {
            if (FetchChecker::analyzeConstFetch($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Scalar\String_) {
            $stmt->inferredType = Type::getString();
        } elseif ($stmt instanceof PhpParser\Node\Scalar\EncapsedStringPart) {
            // do nothing
        } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst) {
            // do nothing
        } elseif ($stmt instanceof PhpParser\Node\Scalar\LNumber) {
            $stmt->inferredType = Type::getInt();
        } elseif ($stmt instanceof PhpParser\Node\Scalar\DNumber) {
            $stmt->inferredType = Type::getFloat();
        } elseif ($stmt instanceof PhpParser\Node\Expr\UnaryMinus) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }
            $stmt->inferredType = isset($stmt->expr->inferredType) ? $stmt->expr->inferredType : null;
        } elseif ($stmt instanceof PhpParser\Node\Expr\UnaryPlus) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

            $stmt->inferredType = isset($stmt->expr->inferredType) ? $stmt->expr->inferredType : null;
        } elseif ($stmt instanceof PhpParser\Node\Expr\Isset_) {
            self::analyzeIsset($statements_checker, $stmt, $context);
            $stmt->inferredType = Type::getBool();
        } elseif ($stmt instanceof PhpParser\Node\Expr\ClassConstFetch) {
            if (FetchChecker::analyzeClassConstFetch($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\PropertyFetch) {
            if (FetchChecker::analyzePropertyFetch($statements_checker, $stmt, $context, $array_assignment) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\StaticPropertyFetch) {
            if (FetchChecker::analyzeStaticPropertyFetch($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\BitwiseNot) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            if (self::analyzeBinaryOp($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\PostInc ||
            $stmt instanceof PhpParser\Node\Expr\PostDec ||
            $stmt instanceof PhpParser\Node\Expr\PreInc ||
            $stmt instanceof PhpParser\Node\Expr\PreDec
        ) {
            if (self::analyze($statements_checker, $stmt->var, $context) === false) {
                return false;
            }

            if (isset($stmt->var->inferredType)) {
                $stmt->inferredType = clone $stmt->var->inferredType;
            } else {
                $stmt->inferredType = Type::getMixed();
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\New_) {
            if (CallChecker::analyzeNew($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Array_) {
            if (self::analyzeArray($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Scalar\Encapsed) {
            if (self::analyzeEncapsulatedString($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\FuncCall) {
            if (CallChecker::analyzeFunctionCall($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Ternary) {
            if (self::analyzeTernary($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\BooleanNot) {
            if (self::analyzeBooleanNot($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Empty_) {
            if (self::analyzeEmpty($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Closure) {
            $closure_checker = new ClosureChecker($stmt, $statements_checker->getSource());

            if (self::analyzeClosureUses($statements_checker, $stmt, $context) === false) {
                return false;
            }

            $use_context = new Context($context->self);

            if (!$statements_checker->isStatic()) {
                if ($context->collect_mutations &&
                    $context->self &&
                    ClassChecker::classExtends(
                        $context->self,
                        (string)$statements_checker->getFQCLN()
                    )
                ) {
                    $use_context->vars_in_scope['$this'] = clone $context->vars_in_scope['$this'];
                } elseif ($context->self) {
                    $use_context->vars_in_scope['$this'] = new Type\Union([new TNamedObject($context->self)]);
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
                // insert the ref into the current context if passed by ref, as whatever we're passing
                // the closure to could execute it straight away.
                if (!isset($context->vars_in_scope['$' . $use->var]) && $use->byRef) {
                    $context->vars_in_scope['$' . $use->var] = Type::getMixed();
                }

                $use_context->vars_in_scope['$' . $use->var] = isset($context->vars_in_scope['$' . $use->var])
                    ? clone $context->vars_in_scope['$' . $use->var]
                    : Type::getMixed();

                $use_context->vars_possibly_in_scope['$' . $use->var] = true;
            }

            $closure_checker->analyze($use_context);

            if (!isset($stmt->inferredType)) {
                $stmt->inferredType = Type::getClosure();
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            if (FetchChecker::analyzeArrayAccess(
                $statements_checker,
                $stmt,
                $context,
                $array_assignment,
                $assignment_key_type,
                $assignment_value_type,
                $assignment_key_value
            ) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Int_) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

            $stmt->inferredType = Type::getInt();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Double) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

            $stmt->inferredType = Type::getFloat();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Bool_) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

            $stmt->inferredType = Type::getBool();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\String_) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

            $stmt->inferredType = Type::getString();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Object_) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

            $stmt->inferredType = Type::getObject();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Array_) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

            $stmt->inferredType = Type::getArray();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Unset_) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

            $stmt->inferredType = Type::getNull();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Clone_) {
            self::analyzeClone($statements_checker, $stmt, $context);
        } elseif ($stmt instanceof PhpParser\Node\Expr\Instanceof_) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

            if ($stmt->class instanceof PhpParser\Node\Name &&
                !in_array($stmt->class->parts[0], ['self', 'static', 'parent'])
            ) {
                if ($context->check_classes) {
                    $fq_class_name = ClassLikeChecker::getFQCLNFromNameObject(
                        $stmt->class,
                        $statements_checker
                    );

                    if (ClassLikeChecker::checkFullyQualifiedClassLikeName(
                        $fq_class_name,
                        $statements_checker->getFileChecker(),
                        new CodeLocation($statements_checker->getSource(), $stmt->class),
                        $statements_checker->getSuppressedIssues()
                    ) === false) {
                        return false;
                    }
                }
            }

            $stmt->inferredType = Type::getBool();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Exit_) {
            // do nothing
        } elseif ($stmt instanceof PhpParser\Node\Expr\Include_) {
            $statements_checker->analyzeInclude($stmt, $context);
        } elseif ($stmt instanceof PhpParser\Node\Expr\Eval_) {
            $context->check_classes = false;
            $context->check_variables = false;

            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\AssignRef) {
            if ($stmt->var instanceof PhpParser\Node\Expr\Variable) {
                if (is_string($stmt->var->name)) {
                    $context->vars_in_scope['$' . $stmt->var->name] = Type::getMixed();
                    $context->vars_possibly_in_scope['$' . $stmt->var->name] = true;
                    $statements_checker->registerVariable('$' . $stmt->var->name, $stmt->var->getLine());
                } else {
                    if (self::analyze($statements_checker, $stmt->var->name, $context) === false) {
                        return false;
                    }
                }
            } else {
                if (self::analyze($statements_checker, $stmt->var, $context) === false) {
                    return false;
                }
            }

            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\ErrorSuppress) {
            // do nothing
        } elseif ($stmt instanceof PhpParser\Node\Expr\ShellExec) {
            if (IssueBuffer::accepts(
                new ForbiddenCode(
                    'Use of shell_exec',
                    new CodeLocation($statements_checker->getSource(), $stmt)
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Print_) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Yield_) {
            self::analyzeYield($statements_checker, $stmt, $context);
        } elseif ($stmt instanceof PhpParser\Node\Expr\YieldFrom) {
            self::analyzeYieldFrom($statements_checker, $stmt, $context);
        } else {
            if (IssueBuffer::accepts(
                new UnrecognizedExpression(
                    'Psalm does not understand ' . get_class($stmt),
                    new CodeLocation($statements_checker->getSource(), $stmt)
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }
        }

        $plugins = Config::getInstance()->getPlugins();

        if ($plugins) {
            $code_location = new CodeLocation($statements_checker->getSource(), $stmt);

            foreach ($plugins as $plugin) {
                if ($plugin->checkExpression(
                    $statements_checker,
                    $stmt,
                    $context,
                    $code_location,
                    $statements_checker->getSuppressedIssues()
                ) === false) {
                    return false;
                }
            }
        }

        return null;
    }

    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Expr\Variable    $stmt
     * @param   Context                         $context
     * @param   bool                            $passed_by_reference
     * @param   Type\Union|null                 $by_ref_type
     * @param   bool                            $array_assignment
     * @return  false|null
     */
    public static function analyzeVariable(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\Variable $stmt,
        Context $context,
        $passed_by_reference = false,
        Type\Union $by_ref_type = null,
        $array_assignment = false
    ) {
        if ($stmt->name === 'this') {
            if ($statements_checker->isStatic()) {
                if (IssueBuffer::accepts(
                    new InvalidStaticVariable(
                        'Invalid reference to $this in a static context',
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }

                return null;
            } elseif (!isset($context->vars_in_scope['$this'])) {
                if (IssueBuffer::accepts(
                    new InvalidScope(
                        'Invalid reference to $this in a non-class context',
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }

                return null;
            }

            $stmt->inferredType = clone $context->vars_in_scope['$this'];

            return null;
        }

        if (!$context->check_variables) {
            $stmt->inferredType = Type::getMixed();

            if (is_string($stmt->name) && !isset($context->vars_in_scope['$' . $stmt->name])) {
                $context->vars_in_scope['$' . $stmt->name] = Type::getMixed();
                $context->vars_possibly_in_scope['$' . $stmt->name] = true;
            }

            return null;
        }

        if (in_array(
            $stmt->name,
            [
                '_SERVER',
                '_GET',
                '_POST',
                '_COOKIE',
                '_REQUEST',
                '_FILES',
                '_ENV',
                '_SESSION',
                'GLOBALS',
                'argv',
                'argc',
            ]
        )
        ) {
            return null;
        }

        if (!is_string($stmt->name)) {
            return self::analyze($statements_checker, $stmt->name, $context);
        }

        if ($passed_by_reference && $by_ref_type) {
            self::assignByRefParam($statements_checker, $stmt, $by_ref_type, $context);
            return null;
        }

        $var_name = '$' . $stmt->name;

        if (!isset($context->vars_in_scope[$var_name])) {
            if (!isset($context->vars_possibly_in_scope[$var_name]) ||
                !$statements_checker->getFirstAppearance($var_name)
            ) {
                if ($array_assignment) {
                    // if we're in an array assignment, let's assign the variable
                    // because PHP allows it

                    $context->vars_in_scope[$var_name] = Type::getArray();
                    $context->vars_possibly_in_scope[$var_name] = true;
                    $statements_checker->registerVariable($var_name, $stmt->getLine());
                } else {
                    IssueBuffer::add(
                        new UndefinedVariable(
                            'Cannot find referenced variable ' . $var_name,
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        )
                    );

                    return false;
                }
            }

            if ($statements_checker->getFirstAppearance($var_name)) {
                if (IssueBuffer::accepts(
                    new PossiblyUndefinedVariable(
                        'Possibly undefined variable ' . $var_name .', first seen on line ' .
                            $statements_checker->getFirstAppearance($var_name),
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            }
        } else {
            $stmt->inferredType = $context->vars_in_scope[$var_name];
        }

        return null;
    }

    /**
     * @param  StatementsChecker    $statements_checker
     * @param  PhpParser\Node\Expr  $stmt
     * @param  Type\Union           $by_ref_type
     * @param  Context              $context
     * @return void
     */
    public static function assignByRefParam(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr $stmt,
        Type\Union $by_ref_type,
        Context $context
    ) {
        $var_id = self::getVarId(
            $stmt,
            $statements_checker->getFQCLN(),
            $statements_checker
        );

        if ($var_id) {
            if (!isset($context->vars_in_scope[$var_id])) {
                $context->vars_possibly_in_scope[$var_id] = true;
                $statements_checker->registerVariable($var_id, $stmt->getLine());
            } else {
                $existing_type = $context->vars_in_scope[$var_id];
                if (TypeChecker::isContainedBy(
                    $existing_type,
                    $by_ref_type,
                    $statements_checker->getFileChecker()
                ) &&
                    (string)$existing_type !== 'array<empty, empty>'
                ) {
                    $stmt->inferredType = $context->vars_in_scope[$var_id];
                    return;
                }
            }
        }

        $stmt->inferredType = $by_ref_type;
        $context->vars_in_scope[$var_id] = $by_ref_type;
    }

    /**
     * @param   StatementsChecker           $statements_checker
     * @param   PhpParser\Node\Expr\Array_  $stmt
     * @param   Context                     $context
     * @return  false|null
     */
    protected static function analyzeArray(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\Array_ $stmt,
        Context $context
    ) {
        // if the array is empty, this special type allows us to match any other array type against it
        if (empty($stmt->items)) {
            $stmt->inferredType = Type::getEmptyArray();
            return null;
        }

        /** @var Type\Union|null */
        $item_key_type = null;

        /** @var Type\Union|null */
        $item_value_type = null;

        /** @var array<string,Type\Union> */
        $property_types = [];

        foreach ($stmt->items as $item) {
            if ($item->key) {
                if (self::analyze($statements_checker, $item->key, $context) === false) {
                    return false;
                }

                if (isset($item->key->inferredType)) {
                    if ($item_key_type) {
                        /** @var Type\Union */
                        $item_key_type = Type::combineUnionTypes($item->key->inferredType, $item_key_type);
                    } else {
                        /** @var Type\Union */
                        $item_key_type = $item->key->inferredType;
                    }
                }
            } else {
                $item_key_type = Type::getInt();
            }

            if (self::analyze($statements_checker, $item->value, $context) === false) {
                return false;
            }

            if (isset($item->value->inferredType)) {
                if ($item->key instanceof PhpParser\Node\Scalar\String_) {
                    $property_types[$item->key->value] = $item->value->inferredType;
                }

                if ($item_value_type) {
                    $item_value_type = Type::combineUnionTypes($item->value->inferredType, $item_value_type);
                } else {
                    $item_value_type = $item->value->inferredType;
                }
            }
        }

        // if this array looks like an object-like array, let's return that instead
        if ($item_value_type && $item_key_type && $item_key_type->hasString() && !$item_key_type->hasInt()) {
            $stmt->inferredType = new Type\Union([new Type\Atomic\ObjectLike($property_types)]);
            return null;
        }

        $stmt->inferredType = new Type\Union([
            new Type\Atomic\TArray([
                $item_key_type ?: new Type\Union([new TInt, new TString]),
                $item_value_type ?: Type::getMixed()
            ])
        ]);

        return null;
    }

    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Expr\BinaryOp    $stmt
     * @param   Context                         $context
     * @param   int                             $nesting
     * @return  false|null
     */
    protected static function analyzeBinaryOp(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\BinaryOp $stmt,
        Context $context,
        $nesting = 0
    ) {
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat && $nesting > 20) {
            // ignore deeply-nested string concatenation
        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd) {
            $if_clauses = TypeChecker::getFormula(
                $stmt->left,
                $statements_checker->getFQCLN(),
                $statements_checker
            );

            $simplified_clauses = TypeChecker::simplifyCNF(array_merge($context->clauses, $if_clauses));

            $left_type_assertions = TypeChecker::getTruthsFromFormula($simplified_clauses);

            if (self::analyze($statements_checker, $stmt->left, $context) === false) {
                return false;
            }

            $changed_vars = [];

            // while in an and, we allow scope to boil over to support
            // statements of the form if ($x && $x->foo())
            $op_vars_in_scope = TypeChecker::reconcileKeyedTypes(
                $left_type_assertions,
                $context->vars_in_scope,
                $changed_vars,
                $statements_checker->getFileChecker(),
                new CodeLocation($statements_checker->getSource(), $stmt),
                $statements_checker->getSuppressedIssues()
            );

            if ($op_vars_in_scope === false) {
                return false;
            }

            $op_context = clone $context;
            $op_context->vars_in_scope = $op_vars_in_scope;

            if (self::analyze($statements_checker, $stmt->right, $op_context) === false) {
                return false;
            }

            foreach ($op_context->vars_in_scope as $var => $type) {
                if (!isset($context->vars_in_scope[$var])) {
                    $context->vars_in_scope[$var] = $type;
                    continue;
                }
            }

            $context->updateChecks($op_context);

            $context->vars_possibly_in_scope = array_merge(
                $op_context->vars_possibly_in_scope,
                $context->vars_possibly_in_scope
            );
        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr) {
            $if_clauses = TypeChecker::getFormula(
                $stmt->left,
                $statements_checker->getFQCLN(),
                $statements_checker
            );

            $rhs_clauses = TypeChecker::simplifyCNF(
                array_merge(
                    $context->clauses,
                    TypeChecker::negateFormula($if_clauses)
                )
            );

            $negated_type_assertions = TypeChecker::getTruthsFromFormula($rhs_clauses);

            if (self::analyze($statements_checker, $stmt->left, $context) === false) {
                return false;
            }

            $changed_vars = [];

            // while in an or, we allow scope to boil over to support
            // statements of the form if ($x === null || $x->foo())
            $op_vars_in_scope = TypeChecker::reconcileKeyedTypes(
                $negated_type_assertions,
                $context->vars_in_scope,
                $changed_vars,
                $statements_checker->getFileChecker(),
                new CodeLocation($statements_checker->getSource(), $stmt),
                $statements_checker->getSuppressedIssues()
            );

            if ($op_vars_in_scope === false) {
                return false;
            }

            $op_context = clone $context;
            $op_context->clauses = $rhs_clauses;
            $op_context->vars_in_scope = $op_vars_in_scope;

            if (self::analyze($statements_checker, $stmt->right, $op_context) === false) {
                return false;
            }

            $context->updateChecks($op_context);

            $context->vars_possibly_in_scope = array_merge(
                $op_context->vars_possibly_in_scope,
                $context->vars_possibly_in_scope
            );
        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
            $stmt->inferredType = Type::getString();

            if (self::analyze($statements_checker, $stmt->left, $context) === false) {
                return false;
            }

            if (self::analyze($statements_checker, $stmt->right, $context) === false) {
                return false;
            }
        } else {
            if ($stmt->left instanceof PhpParser\Node\Expr\BinaryOp) {
                if (self::analyzeBinaryOp($statements_checker, $stmt->left, $context, ++$nesting) === false) {
                    return false;
                }
            } else {
                if (self::analyze($statements_checker, $stmt->left, $context) === false) {
                    return false;
                }
            }

            if ($stmt->right instanceof PhpParser\Node\Expr\BinaryOp) {
                if (self::analyzeBinaryOp($statements_checker, $stmt->right, $context, ++$nesting) === false) {
                    return false;
                }
            } else {
                if (self::analyze($statements_checker, $stmt->right, $context) === false) {
                    return false;
                }
            }
        }

        // let's do some fun type assignment
        if (isset($stmt->left->inferredType) && isset($stmt->right->inferredType)) {
            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Plus ||
                $stmt instanceof PhpParser\Node\Expr\BinaryOp\Minus ||
                $stmt instanceof PhpParser\Node\Expr\BinaryOp\Mod ||
                $stmt instanceof PhpParser\Node\Expr\BinaryOp\Mul ||
                $stmt instanceof PhpParser\Node\Expr\BinaryOp\Pow
            ) {
                self::analyzeNonDivArithmenticOp(
                    $statements_checker,
                    $stmt->left,
                    $stmt->right,
                    $stmt,
                    $result_type
                );

                if ($result_type) {
                    $stmt->inferredType = $result_type;
                }
            } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Div
                && ($stmt->left->inferredType->hasInt() || $stmt->left->inferredType->hasFloat())
                && ($stmt->right->inferredType->hasInt() || $stmt->right->inferredType->hasFloat())
            ) {
                $stmt->inferredType = Type::combineUnionTypes(Type::getFloat(), Type::getInt());
            } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
                self::analyzeConcatOp(
                    $statements_checker,
                    $stmt->left,
                    $stmt->right,
                    $stmt,
                    $result_type
                );

                if ($result_type) {
                    $stmt->inferredType = $result_type;
                }
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

        return null;
    }

    /**
     * @param  StatementsChecker     $statements_checker
     * @param  PhpParser\Node\Expr   $left
     * @param  PhpParser\Node\Expr   $right
     * @param  PhpParser\Node        $parent
     * @param  Type\Union|null   &$result_type
     * @return void
     */
    public static function analyzeNonDivArithmenticOp(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr $left,
        PhpParser\Node\Expr $right,
        PhpParser\Node $parent,
        Type\Union &$result_type = null
    ) {
        $left_type = isset($left->inferredType) ? $left->inferredType : null;
        $right_type = isset($right->inferredType) ? $right->inferredType : null;
        $config = Config::getInstance();

        if ($left_type && $right_type) {
            foreach ($left_type->types as $left_type_part) {
                foreach ($right_type->types as $right_type_part) {
                    if ($left_type_part instanceof TMixed || $right_type_part instanceof TMixed) {
                        if ($left_type_part instanceof TMixed) {
                            if (IssueBuffer::accepts(
                                new MixedOperand(
                                    'Left operand cannot be mixed',
                                    new CodeLocation($statements_checker->getSource(), $left)
                                ),
                                $statements_checker->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        } else {
                            if (IssueBuffer::accepts(
                                new MixedOperand(
                                    'Right operand cannot be mixed',
                                    new CodeLocation($statements_checker->getSource(), $right)
                                ),
                                $statements_checker->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        }

                        $result_type = Type::getMixed();
                        return;
                    }

                    if ($left_type_part instanceof TArray || $right_type_part instanceof TArray) {
                        if (!$right_type_part instanceof TArray || !$left_type_part instanceof TArray) {
                            if (!$left_type_part instanceof TArray) {
                                if (IssueBuffer::accepts(
                                    new InvalidOperand(
                                        'Cannot add an array to a non-array',
                                        new CodeLocation($statements_checker->getSource(), $left)
                                    ),
                                    $statements_checker->getSuppressedIssues()
                                )) {
                                    // fall through
                                }
                            } elseif (!$right_type_part instanceof TArray) {
                                if (IssueBuffer::accepts(
                                    new InvalidOperand(
                                        'Cannot add an array to a non-array',
                                        new CodeLocation($statements_checker->getSource(), $right)
                                    ),
                                    $statements_checker->getSuppressedIssues()
                                )) {
                                    // fall through
                                }
                            }

                            $result_type = Type::getArray();
                            return;
                        }

                        $result_type_member = Type::combineTypes([$left_type_part, $right_type_part]);

                        if (!$result_type) {
                            $result_type = $result_type_member;
                        } else {
                            $result_type = Type::combineUnionTypes($result_type_member, $result_type);
                        }

                        continue;
                    }

                    if ($left_type_part->isNumericType() || $right_type_part->isNumericType()) {
                        if ($left_type_part instanceof TInt && $right_type_part instanceof TInt) {
                            if (!$result_type) {
                                $result_type = Type::getInt();
                            } else {
                                $result_type = Type::combineUnionTypes(Type::getInt(), $result_type);
                            }

                            continue;
                        }

                        if ($left_type_part instanceof TFloat && $right_type_part instanceof TFloat) {
                            if (!$result_type) {
                                $result_type = Type::getFloat();
                            } else {
                                $result_type = Type::combineUnionTypes(Type::getFloat(), $result_type);
                            }

                            continue;
                        }

                        if (($left_type_part instanceof TFloat && $right_type_part instanceof TInt) ||
                            ($left_type_part instanceof TInt && $right_type_part instanceof TFloat)
                        ) {
                            if ($config->strict_binary_operands) {
                                if (IssueBuffer::accepts(
                                    new InvalidOperand(
                                        'Cannot add ints to floats',
                                        new CodeLocation($statements_checker->getSource(), $parent)
                                    ),
                                    $statements_checker->getSuppressedIssues()
                                )) {
                                    // fall through
                                }
                            }

                            if (!$result_type) {
                                $result_type = Type::getFloat();
                            } else {
                                $result_type = Type::combineUnionTypes(Type::getFloat(), $result_type);
                            }

                            continue;
                        }

                        if ($left_type_part->isNumericType() && $right_type_part->isNumericType()) {
                            if ($config->strict_binary_operands) {
                                if (IssueBuffer::accepts(
                                    new InvalidOperand(
                                        'Cannot add numeric types together, please cast explicitly',
                                        new CodeLocation($statements_checker->getSource(), $parent)
                                    ),
                                    $statements_checker->getSuppressedIssues()
                                )) {
                                    // fall through
                                }
                            }

                            if (!$result_type) {
                                $result_type = Type::getFloat();
                            } else {
                                $result_type = Type::combineUnionTypes(Type::getFloat(), $result_type);
                            }

                            continue;
                        }

                        $non_numeric_type = $left_type_part->isNumericType() ? $right_type_part : $left_type_part;

                        if (IssueBuffer::accepts(
                            new InvalidOperand(
                                'Cannot add a numeric type to a non-numeric type ' . $non_numeric_type,
                                new CodeLocation($statements_checker->getSource(), $parent)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
                }
            }
        }
    }

    /**
     * @param  StatementsChecker     $statements_checker
     * @param  PhpParser\Node\Expr   $left
     * @param  PhpParser\Node\Expr   $right
     * @param  PhpParser\Node        $parent
     * @param  Type\Union|null   &$result_type
     * @return void
     */
    public static function analyzeConcatOp(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr $left,
        PhpParser\Node\Expr $right,
        PhpParser\Node $parent,
        Type\Union &$result_type = null
    ) {
        $left_type = isset($left->inferredType) ? $left->inferredType : null;
        $right_type = isset($right->inferredType) ? $right->inferredType : null;
        $config = Config::getInstance();

        if ($left_type && $right_type) {
            $result_type = Type::getString();

            if ($left_type->isMixed() || $right_type->isMixed()) {
                if ($left_type->isMixed()) {
                    if (IssueBuffer::accepts(
                        new MixedOperand(
                            'Left operand cannot be mixed',
                            new CodeLocation($statements_checker->getSource(), $left)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new MixedOperand(
                            'Right operand cannot be mixed',
                            new CodeLocation($statements_checker->getSource(), $right)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                return;
            }

            if ($left_type->isNullable()) {
                if (IssueBuffer::accepts(
                    new NullOperand(
                        'Cannot concatenate with a ' . $left_type,
                        new CodeLocation($statements_checker->getSource(), $left)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($right_type->isNullable()) {
                if (IssueBuffer::accepts(
                    new NullOperand(
                        'Cannot concatenate with a ' . $right_type,
                        new CodeLocation($statements_checker->getSource(), $right)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            $left_type_match = TypeChecker::isContainedBy(
                $left_type,
                Type::getString(),
                $statements_checker->getFileChecker(),
                true,
                $left_has_scalar_match
            );

            $right_type_match = TypeChecker::isContainedBy(
                $right_type,
                Type::getString(),
                $statements_checker->getFileChecker(),
                true,
                $right_has_scalar_match
            );

            if (!$left_type_match && (!$left_has_scalar_match || $config->strict_binary_operands)) {
                if (IssueBuffer::accepts(
                    new InvalidOperand(
                        'Cannot concatenate with a ' . $left_type,
                        new CodeLocation($statements_checker->getSource(), $left)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if (!$right_type_match && (!$right_has_scalar_match || $config->strict_binary_operands)) {
                if (IssueBuffer::accepts(
                    new InvalidOperand(
                        'Cannot concatenate with a ' . $right_type,
                        new CodeLocation($statements_checker->getSource(), $right)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        }
    }

    /**
     * @param  PhpParser\Node\Expr      $stmt
     * @param  string|null              $this_class_name
     * @param  StatementsSource         $source
     * @param  int|null                 &$nesting
     * @return string|null
     */
    public static function getVarId(
        PhpParser\Node\Expr $stmt,
        $this_class_name,
        StatementsSource $source,
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
                if (!$this_class_name) {
                    throw new \UnexpectedValueException('$this_class_name should not be null');
                }

                $fq_class_name = $this_class_name;
            } else {
                $fq_class_name = ClassLikeChecker::getFQCLNFromNameObject(
                    $stmt->class,
                    $source
                );
            }

            return $fq_class_name . '::$' . $stmt->name;
        }

        if ($stmt instanceof PhpParser\Node\Expr\PropertyFetch && is_string($stmt->name)) {
            $object_id = self::getVarId($stmt->var, $this_class_name, $source);

            if (!$object_id) {
                return null;
            }

            return $object_id . '->' . $stmt->name;
        }

        if ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch && $nesting !== null) {
            $nesting++;
            return self::getVarId($stmt->var, $this_class_name, $source, $nesting);
        }

        return null;
    }

    /**
     * @param  PhpParser\Node\Expr      $stmt
     * @param  string|null              $this_class_name
     * @param  StatementsSource         $source
     * @return string|null
     */
    public static function getArrayVarId(
        PhpParser\Node\Expr $stmt,
        $this_class_name,
        StatementsSource $source
    ) {
        if ($stmt instanceof PhpParser\Node\Expr\Assign) {
            return self::getArrayVarId($stmt->var, $this_class_name, $source);
        }

        if ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch &&
            ($stmt->dim instanceof PhpParser\Node\Scalar\String_ ||
                $stmt->dim instanceof PhpParser\Node\Scalar\LNumber)
        ) {
            $root_var_id = self::getArrayVarId($stmt->var, $this_class_name, $source);
            $offset = $stmt->dim instanceof PhpParser\Node\Scalar\String_
                ? '\'' . $stmt->dim->value . '\''
                : $stmt->dim->value;

            return $root_var_id ? $root_var_id . '[' . $offset . ']' : null;
        }

        return self::getVarId($stmt, $this_class_name, $source);
    }

    /**
     * @param  Type\Union                       $return_type
     * @param  array<int, PhpParser\Node\Arg>   $args
     * @param  string|null                      $calling_class
     * @param  string|null                      $method_id
     * @return Type\Union
     */
    public static function fleshOutTypes(Type\Union $return_type, array $args, $calling_class = null, $method_id = null)
    {
        $return_type = clone $return_type;

        $new_return_type_parts = [];

        foreach ($return_type->types as $key => $return_type_part) {
            $new_return_type_parts[] = self::fleshOutAtomicType($return_type_part, $args, $calling_class, $method_id);
        }

        $fleshed_out_type = new Type\Union($new_return_type_parts);

        $fleshed_out_type->from_docblock = $return_type->from_docblock;

        return $fleshed_out_type;
    }

    /**
     * @param  Type\Atomic                      &$return_type
     * @param  array<int, PhpParser\Node\Arg>   $args
     * @param  string|null                      $calling_class
     * @param  string|null                      $method_id
     * @return Type\Atomic
     */
    protected static function fleshOutAtomicType(Type\Atomic $return_type, array $args, $calling_class, $method_id)
    {
        if ($return_type instanceof TNamedObject) {
            if ($return_type->value === '$this' ||
                $return_type->value === 'static' ||
                $return_type->value === 'self'
            ) {
                if (!$calling_class) {
                    throw new \InvalidArgumentException(
                        'Cannot handle ' . $return_type->value . ' when $calling_class is empty'
                    );
                }

                if ($return_type->value === 'static' || !$method_id) {
                    $return_type->value = $calling_class;
                } else {
                    list(, $method_name) = explode('::', $method_id);

                    $appearing_method_id = MethodChecker::getAppearingMethodId($calling_class . '::' . $method_name);

                    $return_type->value = explode('::', (string)$appearing_method_id)[0];
                }
            } elseif ($return_type->value[0] === '$' && $method_id) {
                $method_params = MethodChecker::getMethodParams($method_id);

                if (!$method_params) {
                    throw new \InvalidArgumentException(
                        'Cannot get method params of ' . $method_id
                    );
                }

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
                    $return_type = new TMixed;
                }
            }
        }

        if ($return_type instanceof Type\Atomic\TArray || $return_type instanceof Type\Atomic\TGenericObject) {
            foreach ($return_type->type_params as &$type_param) {
                $type_param = self::fleshOutTypes($type_param, $args, $calling_class, $method_id);
            }
        }

        return $return_type;
    }

    /**
     * @param   StatementsChecker           $statements_checker
     * @param   PhpParser\Node\Expr\Closure $stmt
     * @param   Context                     $context
     * @return  false|null
     */
    protected static function analyzeClosureUses(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\Closure $stmt,
        Context $context
    ) {
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
                            new UndefinedVariable(
                                'Cannot find referenced variable $' . $use->var,
                                new CodeLocation($statements_checker->getSource(), $use)
                            )
                        );

                        return false;
                    }
                }

                if ($statements_checker->getFirstAppearance('$' . $use->var)) {
                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedVariable(
                            'Possibly undefined variable $' . $use->var . ', first seen on line ' .
                                $statements_checker->getFirstAppearance('$' . $use->var),
                            new CodeLocation($statements_checker->getSource(), $use)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    return null;
                }

                if ($context->check_variables) {
                    IssueBuffer::add(
                        new UndefinedVariable(
                            'Cannot find referenced variable $' . $use->var,
                            new CodeLocation($statements_checker->getSource(), $use)
                        )
                    );

                    return false;
                }
            }
        }

        return null;
    }

    /**
     * @param   StatementsChecker           $statements_checker
     * @param   PhpParser\Node\Expr\Yield_  $stmt
     * @param   Context                     $context
     * @return  false|null
     */
    protected static function analyzeYield(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\Yield_ $stmt,
        Context $context
    ) {
        $type_in_comments = CommentChecker::getTypeFromComment(
            (string) $stmt->getDocComment(),
            $context,
            $statements_checker->getSource()
        );

        if ($stmt->key) {
            if (self::analyze($statements_checker, $stmt->key, $context) === false) {
                return false;
            }
        }

        if ($stmt->value) {
            if (self::analyze($statements_checker, $stmt->value, $context) === false) {
                return false;
            }

            if ($type_in_comments) {
                $stmt->inferredType = $type_in_comments;
            } elseif (isset($stmt->value->inferredType)) {
                $stmt->inferredType = $stmt->value->inferredType;
            } else {
                $stmt->inferredType = Type::getMixed();
            }
        } else {
            $stmt->inferredType = Type::getNull();
        }

        return null;
    }

    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Expr\YieldFrom   $stmt
     * @param   Context                         $context
     * @return  false|null
     */
    protected static function analyzeYieldFrom(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\YieldFrom $stmt,
        Context $context
    ) {
        if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
            return false;
        }

        if (isset($stmt->expr->inferredType)) {
            $stmt->inferredType = $stmt->expr->inferredType;
        }

        return null;
    }

    /**
     * @param   StatementsChecker           $statements_checker
     * @param   PhpParser\Node\Expr\Ternary $stmt
     * @param   Context                     $context
     * @return  false|null
     */
    protected static function analyzeTernary(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\Ternary $stmt,
        Context $context
    ) {
        if (self::analyze($statements_checker, $stmt->cond, $context) === false) {
            return false;
        }

        $t_if_context = clone $context;

        $if_clauses = TypeChecker::getFormula(
            $stmt->cond,
            $statements_checker->getFQCLN(),
            $statements_checker
        );

        $ternary_clauses = TypeChecker::simplifyCNF(array_merge($context->clauses, $if_clauses));

        $negated_clauses = TypeChecker::negateFormula($if_clauses);

        $negated_if_types = TypeChecker::getTruthsFromFormula($negated_clauses);

        $reconcilable_if_types = TypeChecker::getTruthsFromFormula($ternary_clauses);

        $if_return_type = null;

        $changed_vars = [];

        $t_if_vars_in_scope_reconciled = TypeChecker::reconcileKeyedTypes(
            $reconcilable_if_types,
            $t_if_context->vars_in_scope,
            $changed_vars,
            $statements_checker->getFileChecker(),
            new CodeLocation($statements_checker->getSource(), $stmt->cond),
            $statements_checker->getSuppressedIssues()
        );

        if ($t_if_vars_in_scope_reconciled === false) {
            return false;
        }

        $t_if_context->vars_in_scope = $t_if_vars_in_scope_reconciled;

        if ($stmt->if) {
            if (self::analyze($statements_checker, $stmt->if, $t_if_context) === false) {
                return false;
            }
        }

        $t_else_context = clone $context;

        if ($negated_if_types) {
            $t_else_vars_in_scope_reconciled = TypeChecker::reconcileKeyedTypes(
                $negated_if_types,
                $t_else_context->vars_in_scope,
                $changed_vars,
                $statements_checker->getFileChecker(),
                new CodeLocation($statements_checker->getSource(), $stmt->else),
                $statements_checker->getSuppressedIssues()
            );

            if ($t_else_vars_in_scope_reconciled === false) {
                return false;
            }

            $t_else_context->vars_in_scope = $t_else_vars_in_scope_reconciled;
        }

        if (self::analyze($statements_checker, $stmt->else, $t_else_context) === false) {
            return false;
        }

        $lhs_type = null;

        if ($stmt->if) {
            if (isset($stmt->if->inferredType)) {
                $lhs_type = $stmt->if->inferredType;
            }
        } elseif ($stmt->cond) {
            if (isset($stmt->cond->inferredType)) {
                $if_return_type_reconciled = TypeChecker::reconcileTypes(
                    '!empty',
                    $stmt->cond->inferredType,
                    '',
                    $statements_checker->getFileChecker(),
                    new CodeLocation($statements_checker->getSource(), $stmt),
                    $statements_checker->getSuppressedIssues()
                );

                if ($if_return_type_reconciled === false) {
                    return false;
                }

                $lhs_type = $if_return_type_reconciled;
            }
        }

        if (!$lhs_type || !isset($stmt->else->inferredType)) {
            $stmt->inferredType = Type::getMixed();
        } else {
            $stmt->inferredType = Type::combineUnionTypes($lhs_type, $stmt->else->inferredType);
        }

        return null;
    }

    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Expr\BooleanNot  $stmt
     * @param   Context                         $context
     * @return  false|null
     */
    protected static function analyzeBooleanNot(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\BooleanNot $stmt,
        Context $context
    ) {
        $stmt->inferredType = Type::getBool();

        if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
            return false;
        }
    }

    /**
     * @param   StatementsChecker           $statements_checker
     * @param   PhpParser\Node\Expr\Empty_  $stmt
     * @param   Context                     $context
     * @return  false|null
     */
    protected static function analyzeEmpty(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\Empty_ $stmt,
        Context $context
    ) {
        return self::analyze($statements_checker, $stmt->expr, $context);
    }

    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Scalar\Encapsed  $stmt
     * @param   Context                         $context
     * @return  false|null
     */
    protected static function analyzeEncapsulatedString(
        StatementsChecker $statements_checker,
        PhpParser\Node\Scalar\Encapsed $stmt,
        Context $context
    ) {
        /** @var PhpParser\Node\Expr $part */
        foreach ($stmt->parts as $part) {
            if (self::analyze($statements_checker, $part, $context) === false) {
                return false;
            }
        }

        $stmt->inferredType = Type::getString();
        return null;
    }

    /**
     * @param  StatementsChecker          $statements_checker
     * @param  PhpParser\Node\Expr\Isset_ $stmt
     * @param  Context                    $context
     * @return void
     */
    protected static function analyzeIsset(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\Isset_ $stmt,
        Context $context
    ) {
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

            self::analyzeIssetVar($statements_checker, $isset_var, $context);
        }
    }

    /**
     * @param  StatementsChecker   $statements_checker
     * @param  PhpParser\Node\Expr $stmt
     * @param  Context             $context
     * @return void
     */
    protected static function analyzeIssetVar(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr $stmt,
        Context $context
    ) {
        if ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            self::analyzeIssetVar($statements_checker, $stmt->var, $context);

            if (isset($stmt->dim)) {
                self::analyze($statements_checker, $stmt->dim, $context);
            }
        }
    }

    /**
     * @param  StatementsChecker            $statements_checker
     * @param  PhpParser\Node\Expr\Clone_   $stmt
     * @param  Context                      $context
     * @return false|null
     */
    protected static function analyzeClone(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\Clone_ $stmt,
        Context $context
    ) {
        if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
            return false;
        }

        if (isset($stmt->expr->inferredType)) {
            foreach ($stmt->expr->inferredType->types as $clone_type_part) {
                if (!$clone_type_part instanceof TNamedObject && !$clone_type_part instanceof TObject) {
                    if (IssueBuffer::accepts(
                        new InvalidClone(
                            'Cannot clone ' . $clone_type_part,
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    return;
                }
            }

            $stmt->inferredType = $stmt->expr->inferredType;
        }
    }

    /**
     * @param  string  $fq_class_name
     * @return boolean
     */
    public static function isMock($fq_class_name)
    {
        return in_array($fq_class_name, Config::getInstance()->getMockClasses());
    }

    /**
     * @return void
     */
    public static function clearCache()
    {
        self::$reflection_functions = [];
    }
}
