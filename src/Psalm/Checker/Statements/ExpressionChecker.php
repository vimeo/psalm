<?php
namespace Psalm\Checker\Statements;

use PhpParser;
use Psalm\Checker\AlgebraChecker;
use Psalm\Checker\ClassChecker;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\ClosureChecker;
use Psalm\Checker\CommentChecker;
use Psalm\Checker\FunctionLikeChecker;
use Psalm\Checker\MethodChecker;
use Psalm\Checker\ProjectChecker;
use Psalm\Checker\Statements\Expression\AssignmentChecker;
use Psalm\Checker\Statements\Expression\CallChecker;
use Psalm\Checker\Statements\Expression\FetchChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\TypeChecker;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\DocblockParseException;
use Psalm\FileManipulation\FileManipulationBuffer;
use Psalm\Issue\ForbiddenCode;
use Psalm\Issue\InvalidCast;
use Psalm\Issue\InvalidClone;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\InvalidOperand;
use Psalm\Issue\InvalidScope;
use Psalm\Issue\InvalidStaticVariable;
use Psalm\Issue\MixedOperand;
use Psalm\Issue\NullOperand;
use Psalm\Issue\PossiblyNullOperand;
use Psalm\Issue\PossiblyUndefinedGlobalVariable;
use Psalm\Issue\PossiblyUndefinedVariable;
use Psalm\Issue\UndefinedGlobalVariable;
use Psalm\Issue\UndefinedVariable;
use Psalm\Issue\UnrecognizedExpression;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TString;

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
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr $stmt,
        Context $context,
        $array_assignment = false
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
                (string)$stmt->getDocComment(),
                $stmt->getLine()
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
            switch (strtolower($stmt->getName())) {
                case '__line__':
                    $stmt->inferredType = Type::getInt();
                    break;

                case '__file__':
                case '__dir__':
                case '__function__':
                case '__class__':
                case '__trait__':
                case '__method__':
                case '__namespace__':
                    $stmt->inferredType = Type::getString();
                    break;
            }
        } elseif ($stmt instanceof PhpParser\Node\Scalar\LNumber) {
            $stmt->inferredType = Type::getInt();
        } elseif ($stmt instanceof PhpParser\Node\Scalar\DNumber) {
            $stmt->inferredType = Type::getFloat();
        } elseif ($stmt instanceof PhpParser\Node\Expr\UnaryMinus ||
            $stmt instanceof PhpParser\Node\Expr\UnaryPlus
        ) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

            if (!isset($stmt->expr->inferredType)) {
                $stmt->inferredType = new Type\Union([new TInt, new TFloat]);
            } elseif ($stmt->expr->inferredType->isMixed()) {
                $stmt->inferredType = Type::getMixed();
            } else {
                $acceptable_types = [];

                foreach ($stmt->expr->inferredType->types as $type_part) {
                    if ($type_part instanceof TInt || $type_part instanceof TFloat) {
                        $acceptable_types[] = $type_part;
                    } elseif ($type_part instanceof TString) {
                        $acceptable_types[] = new TInt;
                        $acceptable_types[] = new TFloat;
                    } else {
                        $acceptable_types[] = new TInt;
                    }
                }

                $stmt->inferredType = new Type\Union($acceptable_types);
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Isset_) {
            self::analyzeIsset($statements_checker, $stmt, $context);
            $stmt->inferredType = Type::getBool();
        } elseif ($stmt instanceof PhpParser\Node\Expr\ClassConstFetch) {
            if (FetchChecker::analyzeClassConstFetch($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\PropertyFetch) {
            if (FetchChecker::analyzePropertyFetch($statements_checker, $stmt, $context) === false) {
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
            if (self::analyzeBinaryOp(
                $statements_checker,
                $stmt,
                $context
            ) === false) {
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
            $project_checker = $statements_checker->getFileChecker()->project_checker;
            if (CallChecker::analyzeFunctionCall(
                $project_checker,
                $statements_checker,
                $stmt,
                $context
            ) === false
            ) {
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
            self::analyzeEmpty($statements_checker, $stmt, $context);
        } elseif ($stmt instanceof PhpParser\Node\Expr\Closure) {
            $closure_checker = new ClosureChecker($stmt, $statements_checker->getSource());

            if (self::analyzeClosureUses($statements_checker, $stmt, $context) === false) {
                return false;
            }

            $use_context = new Context($context->self);
            $use_context->collect_references =
                $statements_checker->getFileChecker()->project_checker->collect_references;

            if (!$statements_checker->isStatic()) {
                if ($context->collect_mutations &&
                    $context->self &&
                    ClassChecker::classExtends(
                        $statements_checker->getFileChecker()->project_checker,
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
                if (!$context->hasVariable('$' . $use->var) && $use->byRef) {
                    $context->vars_in_scope['$' . $use->var] = Type::getMixed();
                }

                $use_context->vars_in_scope['$' . $use->var] = $context->hasVariable('$' . $use->var)
                    ? clone $context->vars_in_scope['$' . $use->var]
                    : Type::getMixed();

                $use_context->vars_possibly_in_scope['$' . $use->var] = true;
            }

            $closure_checker->analyze($use_context, $context);

            if (!isset($stmt->inferredType)) {
                $stmt->inferredType = Type::getClosure();
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            if (FetchChecker::analyzeArrayAccess(
                $statements_checker,
                $stmt,
                $context
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

            $container_type = Type::getString();

            if (isset($stmt->expr->inferredType)
                && !$stmt->expr->inferredType->isMixed()
                && !TypeChecker::isContainedBy(
                    $statements_checker->getFileChecker()->project_checker,
                    $stmt->expr->inferredType,
                    $container_type,
                    true,
                    false,
                    $has_scalar_match
                )
                && !$has_scalar_match
            ) {
                if (IssueBuffer::accepts(
                    new InvalidCast(
                        $stmt->expr->inferredType . ' cannot be cast to ' . $container_type,
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            }

            $stmt->inferredType = $container_type;
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Object_) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

            $stmt->inferredType = new Type\Union([new TNamedObject('stdClass')]);
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Array_) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

            $permissible_atomic_types = [];
            $all_permissible = false;

            if (isset($stmt->expr->inferredType)) {
                $all_permissible = true;

                foreach ($stmt->expr->inferredType->types as $type) {
                    if ($type instanceof Scalar) {
                        $permissible_atomic_types[] = new TArray([Type::getInt(), new Type\Union([$type])]);
                    } elseif ($type instanceof TArray) {
                        $permissible_atomic_types[] = $type;
                    } elseif ($type instanceof ObjectLike) {
                        $permissible_atomic_types[] = $type->getGenericArrayType();
                    } else {
                        $all_permissible = false;
                        break;
                    }
                }
            }

            if ($all_permissible) {
                $stmt->inferredType = Type::combineTypes($permissible_atomic_types);
            } else {
                $stmt->inferredType = Type::getArray();
            }
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
                !in_array(strtolower($stmt->class->parts[0]), ['self', 'static', 'parent'], true)
            ) {
                if ($context->check_classes) {
                    $fq_class_name = ClassLikeChecker::getFQCLNFromNameObject(
                        $stmt->class,
                        $statements_checker->getAliases()
                    );

                    if (ClassLikeChecker::checkFullyQualifiedClassLikeName(
                        $statements_checker->getFileChecker()->project_checker,
                        $fq_class_name,
                        new CodeLocation($statements_checker->getSource(), $stmt->class),
                        $statements_checker->getSuppressedIssues()
                    ) === false) {
                        return false;
                    }
                }
            }

            $stmt->inferredType = Type::getBool();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Exit_) {
            if ($stmt->expr) {
                if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                    return false;
                }
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Include_) {
            $statements_checker->analyzeInclude($stmt, $context);
        } elseif ($stmt instanceof PhpParser\Node\Expr\Eval_) {
            $context->check_classes = false;
            $context->check_variables = false;

            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\AssignRef) {
            if (AssignmentChecker::analyzeAssignmentRef($statements_checker, $stmt, $context) === false) {
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
            $file_manipulations = [];
            $code_location = new CodeLocation($statements_checker->getSource(), $stmt);

            foreach ($plugins as $plugin) {
                if ($plugin->afterExpressionCheck(
                    $statements_checker,
                    $stmt,
                    $context,
                    $code_location,
                    $statements_checker->getSuppressedIssues(),
                    $file_manipulations
                ) === false) {
                    return false;
                }
            }

            if ($file_manipulations) {
                FileManipulationBuffer::add($statements_checker->getFilePath(), $file_manipulations);
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
     *
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
            if (is_string($stmt->name)) {
                $var_name = '$' . $stmt->name;

                if (!$context->hasVariable($var_name)) {
                    $context->vars_in_scope[$var_name] = Type::getMixed();
                    $context->vars_possibly_in_scope[$var_name] = true;
                    $stmt->inferredType = Type::getMixed();
                } else {
                    $stmt->inferredType = clone $context->vars_in_scope[$var_name];
                }
            } else {
                $stmt->inferredType = Type::getMixed();
            }

            return null;
        }

        if (in_array(
            $stmt->name,
            [
                'GLOBALS',
                '_SERVER',
                '_GET',
                '_POST',
                '_FILES',
                '_COOKIE',
                '_SESSION',
                '_REQUEST',
                '_ENV',
            ],
            true
        )
        ) {
            $stmt->inferredType = Type::getArray();

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

        if (!$context->hasVariable($var_name)) {
            if (!isset($context->vars_possibly_in_scope[$var_name]) ||
                !$statements_checker->getFirstAppearance($var_name)
            ) {
                if ($array_assignment) {
                    // if we're in an array assignment, let's assign the variable
                    // because PHP allows it

                    $context->vars_in_scope[$var_name] = Type::getArray();
                    $context->vars_possibly_in_scope[$var_name] = true;

                    // it might have been defined first in another if/else branch
                    if (!$statements_checker->hasVariable($var_name)) {
                        $statements_checker->registerVariable(
                            $var_name,
                            new CodeLocation($statements_checker, $stmt)
                        );
                    }
                } elseif ($context->check_variables) {
                    if ($context->is_global) {
                        if (IssueBuffer::accepts(
                            new UndefinedGlobalVariable(
                                'Cannot find referenced variable ' . $var_name . ' in global scope',
                                new CodeLocation($statements_checker->getSource(), $stmt)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        $stmt->inferredType = Type::getMixed();

                        return null;
                    }
                    IssueBuffer::add(
                            new UndefinedVariable(
                                'Cannot find referenced variable ' . $var_name,
                                new CodeLocation($statements_checker->getSource(), $stmt)
                            )
                        );

                    $stmt->inferredType = Type::getMixed();

                    return false;
                }
            }

            $first_appearance = $statements_checker->getFirstAppearance($var_name);

            if ($first_appearance) {
                if ($context->is_global) {
                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedGlobalVariable(
                            'Possibly undefined global variable ' . $var_name . ', first seen on line ' .
                                $first_appearance->getLineNumber(),
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedVariable(
                            'Possibly undefined variable ' . $var_name . ', first seen on line ' .
                                $first_appearance->getLineNumber(),
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }
            }
        } else {
            $stmt->inferredType = clone $context->vars_in_scope[$var_name];
        }

        return null;
    }

    /**
     * @param  StatementsChecker    $statements_checker
     * @param  PhpParser\Node\Expr  $stmt
     * @param  Type\Union           $by_ref_type
     * @param  bool                 $constrain_type
     * @param  Context              $context
     *
     * @return void
     */
    public static function assignByRefParam(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr $stmt,
        Type\Union $by_ref_type,
        Context $context,
        $constrain_type = true
    ) {
        $var_id = self::getVarId(
            $stmt,
            $statements_checker->getFQCLN(),
            $statements_checker
        );

        if ($var_id) {
            if (!$by_ref_type->isMixed() && $constrain_type) {
                $context->byref_constraints[$var_id] = new \Psalm\ReferenceConstraint($by_ref_type);
            }

            if (!$context->hasVariable($var_id)) {
                $context->vars_possibly_in_scope[$var_id] = true;

                if (!$statements_checker->hasVariable($var_id)) {
                    $statements_checker->registerVariable($var_id, new CodeLocation($statements_checker, $stmt));
                }
            } else {
                $existing_type = $context->vars_in_scope[$var_id];

                // removes dependennt vars from $context
                $context->removeDescendents(
                    $var_id,
                    $existing_type,
                    $by_ref_type,
                    $statements_checker
                );

                if ((string)$existing_type !== 'array<empty, empty>') {
                    $context->vars_in_scope[$var_id] = $by_ref_type;
                    $stmt->inferredType = $context->vars_in_scope[$var_id];

                    return;
                }
            }

            $context->vars_in_scope[$var_id] = $by_ref_type;
        }

        $stmt->inferredType = $by_ref_type;
    }

    /**
     * @param   StatementsChecker           $statements_checker
     * @param   PhpParser\Node\Expr\Array_  $stmt
     * @param   Context                     $context
     *
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

        $item_key_type = null;

        $item_value_type = null;

        $property_types = [];

        $can_create_objectlike = true;

        foreach ($stmt->items as $int_offset => $item) {
            if ($item->key) {
                if (self::analyze($statements_checker, $item->key, $context) === false) {
                    return false;
                }

                if (isset($item->key->inferredType)) {
                    if ($item_key_type) {
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

            if ($item_value_type && $item_value_type->isMixed() && !$can_create_objectlike) {
                continue;
            }

            if (isset($item->value->inferredType)) {
                if ($item->key instanceof PhpParser\Node\Scalar\String_
                    || $item->key instanceof PhpParser\Node\Scalar\LNumber
                    || !$item->key
                ) {
                    $property_types[$item->key ? $item->key->value : $int_offset] = $item->value->inferredType;
                } else {
                    $can_create_objectlike = false;
                }

                if ($item_value_type) {
                    $item_value_type = Type::combineUnionTypes($item->value->inferredType, $item_value_type);
                } else {
                    $item_value_type = $item->value->inferredType;
                }
            } else {
                $item_value_type = Type::getMixed();

                if ($item->key instanceof PhpParser\Node\Scalar\String_
                    || $item->key instanceof PhpParser\Node\Scalar\LNumber
                    || !$item->key
                ) {
                    $property_types[$item->key ? $item->key->value : $int_offset] = $item_value_type;
                } else {
                    $can_create_objectlike = false;
                }
            }
        }

        // if this array looks like an object-like array, let's return that instead
        if ($item_value_type
            && $item_key_type
            && ($item_key_type->hasString() || $item_key_type->hasInt())
            && $can_create_objectlike
        ) {
            $stmt->inferredType = new Type\Union([new Type\Atomic\ObjectLike($property_types)]);

            return null;
        }

        $stmt->inferredType = new Type\Union([
            new Type\Atomic\TArray([
                $item_key_type ?: new Type\Union([new TInt, new TString]),
                $item_value_type ?: Type::getMixed(),
            ]),
        ]);

        return null;
    }

    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Expr\BinaryOp    $stmt
     * @param   Context                         $context
     * @param   int                             $nesting
     *
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
        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd ||
            $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
        ) {
            $if_clauses = AlgebraChecker::getFormula(
                $stmt->left,
                $statements_checker->getFQCLN(),
                $statements_checker
            );

            $pre_referenced_var_ids = $context->referenced_var_ids;
            $context->referenced_var_ids = [];

            $pre_assigned_var_ids = $context->assigned_var_ids;

            if (self::analyze($statements_checker, $stmt->left, $context) === false) {
                return false;
            }

            $new_referenced_var_ids = $context->referenced_var_ids;
            $context->referenced_var_ids = array_merge($pre_referenced_var_ids, $new_referenced_var_ids);

            $new_assigned_var_ids = array_diff_key($context->assigned_var_ids, $pre_assigned_var_ids);

            $new_referenced_var_ids = array_diff_key($new_referenced_var_ids, $new_assigned_var_ids);

            $simplified_clauses = AlgebraChecker::simplifyCNF(array_merge($context->clauses, $if_clauses));

            $left_type_assertions = AlgebraChecker::getTruthsFromFormula($simplified_clauses);

            $changed_var_ids = [];

            // while in an and, we allow scope to boil over to support
            // statements of the form if ($x && $x->foo())
            $op_vars_in_scope = TypeChecker::reconcileKeyedTypes(
                $left_type_assertions,
                $context->vars_in_scope,
                $changed_var_ids,
                $new_referenced_var_ids,
                $statements_checker,
                new CodeLocation($statements_checker->getSource(), $stmt),
                $statements_checker->getSuppressedIssues()
            );

            if ($op_vars_in_scope === false) {
                return false;
            }

            $op_context = clone $context;
            $op_context->vars_in_scope = $op_vars_in_scope;

            $op_context->removeReconciledClauses($changed_var_ids);

            if (self::analyze($statements_checker, $stmt->right, $op_context) === false) {
                return false;
            }

            $context->referenced_var_ids = array_merge(
                $op_context->referenced_var_ids,
                $context->referenced_var_ids
            );

            foreach ($op_context->vars_in_scope as $var_id => $type) {
                if (isset($context->vars_in_scope[$var_id])) {
                    $context->vars_in_scope[$var_id] = Type::combineUnionTypes($context->vars_in_scope[$var_id], $type);
                }
            }

            if ($context->inside_conditional) {
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

                $context->assigned_var_ids = array_merge(
                    $context->assigned_var_ids,
                    $op_context->assigned_var_ids
                );
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr ||
            $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
        ) {
            $pre_referenced_var_ids = $context->referenced_var_ids;
            $context->referenced_var_ids = [];

            $pre_assigned_var_ids = $context->assigned_var_ids;

            if (self::analyze($statements_checker, $stmt->left, $context) === false) {
                return false;
            }

            $new_referenced_var_ids = $context->referenced_var_ids;
            $context->referenced_var_ids = array_merge($pre_referenced_var_ids, $new_referenced_var_ids);

            $new_assigned_var_ids = array_diff_key($context->assigned_var_ids, $pre_assigned_var_ids);

            $new_referenced_var_ids = array_diff_key($new_referenced_var_ids, $new_assigned_var_ids);

            $left_clauses = AlgebraChecker::getFormula(
                $stmt->left,
                $statements_checker->getFQCLN(),
                $statements_checker
            );

            $rhs_clauses = AlgebraChecker::simplifyCNF(
                array_merge(
                    $context->clauses,
                    AlgebraChecker::negateFormula($left_clauses)
                )
            );

            $negated_type_assertions = AlgebraChecker::getTruthsFromFormula($rhs_clauses);

            $changed_var_ids = [];

            // while in an or, we allow scope to boil over to support
            // statements of the form if ($x === null || $x->foo())
            $op_vars_in_scope = TypeChecker::reconcileKeyedTypes(
                $negated_type_assertions,
                $context->vars_in_scope,
                $changed_var_ids,
                $new_referenced_var_ids,
                $statements_checker,
                new CodeLocation($statements_checker->getSource(), $stmt),
                $statements_checker->getSuppressedIssues()
            );

            if ($op_vars_in_scope === false) {
                return false;
            }

            $op_context = clone $context;
            $op_context->clauses = $rhs_clauses;
            $op_context->vars_in_scope = $op_vars_in_scope;

            $op_context->removeReconciledClauses($changed_var_ids);

            if (self::analyze($statements_checker, $stmt->right, $op_context) === false) {
                return false;
            }

            if (!($stmt->right instanceof PhpParser\Node\Expr\Exit_)) {
                foreach ($op_context->vars_in_scope as $var_id => $type) {
                    if (isset($context->vars_in_scope[$var_id])) {
                        $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                            $context->vars_in_scope[$var_id],
                            $type
                        );
                    }
                }
            } elseif ($stmt->left instanceof PhpParser\Node\Expr\Assign) {
                $var_id = self::getVarId($stmt->left->var, $context->self);

                if ($var_id && isset($context->vars_in_scope[$var_id])) {
                    $left_inferred_reconciled = TypeChecker::reconcileTypes(
                        '!falsy',
                        $context->vars_in_scope[$var_id],
                        '',
                        $statements_checker,
                        new CodeLocation($statements_checker->getSource(), $stmt->left),
                        $statements_checker->getSuppressedIssues()
                    );

                    if ($left_inferred_reconciled) {
                        $context->vars_in_scope[$var_id] = $left_inferred_reconciled;
                    }
                }
            }

            if ($context->inside_conditional) {
                $context->updateChecks($op_context);
            }

            $context->referenced_var_ids = array_merge(
                $op_context->referenced_var_ids,
                $context->referenced_var_ids
            );

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
        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Coalesce) {
            $t_if_context = clone $context;

            $if_clauses = AlgebraChecker::getFormula(
                $stmt,
                $statements_checker->getFQCLN(),
                $statements_checker
            );

            $ternary_clauses = AlgebraChecker::simplifyCNF(array_merge($context->clauses, $if_clauses));

            $negated_clauses = AlgebraChecker::negateFormula($if_clauses);

            $negated_if_types = AlgebraChecker::getTruthsFromFormula($negated_clauses);

            $reconcilable_if_types = AlgebraChecker::getTruthsFromFormula($ternary_clauses);

            $changed_var_ids = [];

            $t_if_vars_in_scope_reconciled = TypeChecker::reconcileKeyedTypes(
                $reconcilable_if_types,
                $t_if_context->vars_in_scope,
                $changed_var_ids,
                [],
                $statements_checker,
                new CodeLocation($statements_checker->getSource(), $stmt->left),
                $statements_checker->getSuppressedIssues()
            );

            if ($t_if_vars_in_scope_reconciled === false) {
                return false;
            }

            $t_if_context->vars_in_scope = $t_if_vars_in_scope_reconciled;

            if (self::analyze($statements_checker, $stmt->left, $t_if_context) === false) {
                return false;
            }

            foreach ($t_if_context->vars_in_scope as $var_id => $type) {
                if (isset($context->vars_in_scope[$var_id])) {
                    $context->vars_in_scope[$var_id] = Type::combineUnionTypes($context->vars_in_scope[$var_id], $type);
                } else {
                    $context->vars_in_scope[$var_id] = $type;
                }
            }

            $context->referenced_var_ids = array_merge(
                $context->referenced_var_ids,
                $t_if_context->referenced_var_ids
            );

            $t_else_context = clone $context;

            if ($negated_if_types) {
                $t_else_vars_in_scope_reconciled = TypeChecker::reconcileKeyedTypes(
                    $negated_if_types,
                    $t_else_context->vars_in_scope,
                    $changed_var_ids,
                    [],
                    $statements_checker,
                    new CodeLocation($statements_checker->getSource(), $stmt->right),
                    $statements_checker->getSuppressedIssues()
                );

                if ($t_else_vars_in_scope_reconciled === false) {
                    return false;
                }

                $t_else_context->vars_in_scope = $t_else_vars_in_scope_reconciled;
            }

            if (self::analyze($statements_checker, $stmt->right, $t_else_context) === false) {
                return false;
            }

            $context->referenced_var_ids = array_merge(
                $context->referenced_var_ids,
                $t_else_context->referenced_var_ids
            );

            $lhs_type = null;

            if (isset($stmt->left->inferredType)) {
                $if_return_type_reconciled = TypeChecker::reconcileTypes(
                    '!null',
                    $stmt->left->inferredType,
                    '',
                    $statements_checker,
                    new CodeLocation($statements_checker->getSource(), $stmt),
                    $statements_checker->getSuppressedIssues()
                );

                if ($if_return_type_reconciled === false) {
                    return false;
                }

                $lhs_type = $if_return_type_reconciled;
            }

            if (!$lhs_type || !isset($stmt->right->inferredType)) {
                $stmt->inferredType = Type::getMixed();
            } else {
                $stmt->inferredType = Type::combineUnionTypes($lhs_type, $stmt->right->inferredType);
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
                    $result_type,
                    $context
                );

                if ($result_type) {
                    $stmt->inferredType = $result_type;
                }
            } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Div) {
                $project_checker = $statements_checker->getFileChecker()->project_checker;

                if ($project_checker->infer_types_from_usage
                    && isset($stmt->left->inferredType)
                    && isset($stmt->right->inferredType)
                    && ($stmt->left->inferredType->isMixed() || $stmt->right->inferredType->isMixed())
                ) {
                    $source_checker = $statements_checker->getSource();

                    if ($source_checker instanceof FunctionLikeChecker) {
                        $function_storage = $source_checker->getFunctionLikeStorage($statements_checker);

                        $context->inferType($stmt->left, $function_storage, new Type\Union([new TInt, new TFloat]));
                        $context->inferType($stmt->right, $function_storage, new Type\Union([new TInt, new TFloat]));
                    }
                }

                $stmt->inferredType = new Type\Union([new TInt, new TFloat]);
            } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
                self::analyzeConcatOp(
                    $statements_checker,
                    $stmt->left,
                    $stmt->right,
                    $context,
                    $result_type
                );

                if ($result_type) {
                    $stmt->inferredType = $result_type;
                }
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
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
     * @param  StatementsSource     $statements_source
     * @param  PhpParser\Node\Expr   $left
     * @param  PhpParser\Node\Expr   $right
     * @param  PhpParser\Node        $parent
     * @param  Type\Union|null   &$result_type
     *
     * @return void
     */
    public static function analyzeNonDivArithmenticOp(
        StatementsSource $statements_source,
        PhpParser\Node\Expr $left,
        PhpParser\Node\Expr $right,
        PhpParser\Node $parent,
        Type\Union &$result_type = null,
        Context $context = null
    ) {
        $project_checker = $statements_source->getFileChecker()->project_checker;

        $left_type = isset($left->inferredType) ? $left->inferredType : null;
        $right_type = isset($right->inferredType) ? $right->inferredType : null;
        $config = Config::getInstance();

        if ($project_checker->infer_types_from_usage
            && $context
            && $left_type
            && $right_type
            && ($left_type->isMixed() || $right_type->isMixed())
            && ($left_type->hasNumericType() || $right_type->hasNumericType())
        ) {
            $source_checker = $statements_source->getSource();
            if ($source_checker instanceof FunctionLikeChecker
                && $statements_source instanceof StatementsChecker
            ) {
                $function_storage = $source_checker->getFunctionLikeStorage($statements_source);

                $context->inferType($left, $function_storage, new Type\Union([new TInt, new TFloat]));
                $context->inferType($right, $function_storage, new Type\Union([new TInt, new TFloat]));
            }
        }

        if ($left_type && $right_type) {
            if ($left_type->isNullable()) {
                if (IssueBuffer::accepts(
                    new PossiblyNullOperand(
                        'Left operand cannot be nullable, got ' . $left_type,
                        new CodeLocation($statements_source, $left)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            } elseif ($left_type->isNull()) {
                if (IssueBuffer::accepts(
                    new NullOperand(
                        'Left operand cannot be null',
                        new CodeLocation($statements_source, $left)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($right_type->isNullable()) {
                if (IssueBuffer::accepts(
                    new PossiblyNullOperand(
                        'Right operand cannot be nullable, got ' . $right_type,
                        new CodeLocation($statements_source, $right)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            } elseif ($right_type->isNull()) {
                if (IssueBuffer::accepts(
                    new NullOperand(
                        'Right operand cannot be null',
                        new CodeLocation($statements_source, $right)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            foreach ($left_type->types as $left_type_part) {
                foreach ($right_type->types as $right_type_part) {
                    if ($left_type_part instanceof TNull) {
                        // null case is handled above
                        continue;
                    }

                    if ($left_type_part instanceof TMixed || $right_type_part instanceof TMixed) {
                        if ($left_type_part instanceof TMixed) {
                            if (IssueBuffer::accepts(
                                new MixedOperand(
                                    'Left operand cannot be mixed',
                                    new CodeLocation($statements_source, $left)
                                ),
                                $statements_source->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        } else {
                            if (IssueBuffer::accepts(
                                new MixedOperand(
                                    'Right operand cannot be mixed',
                                    new CodeLocation($statements_source, $right)
                                ),
                                $statements_source->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        }

                        $result_type = Type::getMixed();

                        return;
                    }

                    if ($left_type_part instanceof TArray
                        || $right_type_part instanceof TArray
                        || $left_type_part instanceof ObjectLike
                        || $right_type_part instanceof ObjectLike
                    ) {
                        if ((!$right_type_part instanceof TArray && !$right_type_part instanceof ObjectLike)
                            || (!$left_type_part instanceof TArray && !$left_type_part instanceof ObjectLike)
                        ) {
                            if (!$left_type_part instanceof TArray && !$left_type_part instanceof ObjectLike) {
                                if (IssueBuffer::accepts(
                                    new InvalidOperand(
                                        'Cannot add an array to a non-array ' . $left_type_part,
                                        new CodeLocation($statements_source, $left)
                                    ),
                                    $statements_source->getSuppressedIssues()
                                )) {
                                    // fall through
                                }
                            } else {
                                if (IssueBuffer::accepts(
                                    new InvalidOperand(
                                        'Cannot add an array to a non-array ' . $right_type_part,
                                        new CodeLocation($statements_source, $right)
                                    ),
                                    $statements_source->getSuppressedIssues()
                                )) {
                                    // fall through
                                }
                            }

                            $result_type = Type::getArray();

                            return;
                        }

                        if ($left_type_part instanceof ObjectLike && $right_type_part instanceof ObjectLike) {
                            $properties = $left_type_part->properties + $right_type_part->properties;

                            $result_type_member = new Type\Union([new ObjectLike($properties)]);
                        } else {
                            $result_type_member = Type::combineTypes([$left_type_part, $right_type_part]);
                        }

                        if (!$result_type) {
                            $result_type = $result_type_member;
                        } else {
                            $result_type = Type::combineUnionTypes($result_type_member, $result_type);
                        }

                        if ($left instanceof PhpParser\Node\Expr\ArrayDimFetch
                            && $context
                            && $statements_source instanceof StatementsChecker
                        ) {
                            AssignmentChecker::updateArrayType(
                                $statements_source,
                                $left,
                                $result_type,
                                $context
                            );
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
                                        new CodeLocation($statements_source, $parent)
                                    ),
                                    $statements_source->getSuppressedIssues()
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
                                        new CodeLocation($statements_source, $parent)
                                    ),
                                    $statements_source->getSuppressedIssues()
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
                                new CodeLocation($statements_source, $parent)
                            ),
                            $statements_source->getSuppressedIssues()
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
     * @param  Type\Union|null       &$result_type
     *
     * @return void
     */
    public static function analyzeConcatOp(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr $left,
        PhpParser\Node\Expr $right,
        Context $context,
        Type\Union &$result_type = null
    ) {
        $project_checker = $statements_checker->getFileChecker()->project_checker;

        $left_type = isset($left->inferredType) ? $left->inferredType : null;
        $right_type = isset($right->inferredType) ? $right->inferredType : null;
        $config = Config::getInstance();

        if ($project_checker->infer_types_from_usage
            && $left_type
            && $right_type
            && ($left_type->isMixed() || $right_type->isMixed())
        ) {
            $source_checker = $statements_checker->getSource();

            if ($source_checker instanceof FunctionLikeChecker) {
                $function_storage = $source_checker->getFunctionLikeStorage($statements_checker);

                $context->inferType($left, $function_storage, Type::getString());
                $context->inferType($right, $function_storage, Type::getString());
            }
        }

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

            if ($left_type->isNull()) {
                if (IssueBuffer::accepts(
                    new NullOperand(
                        'Cannot concatenate with a ' . $left_type,
                        new CodeLocation($statements_checker->getSource(), $left)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($right_type->isNull()) {
                if (IssueBuffer::accepts(
                    new NullOperand(
                        'Cannot concatenate with a ' . $right_type,
                        new CodeLocation($statements_checker->getSource(), $right)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($left_type->isNullable() && !$left_type->ignore_nullable_issues) {
                if (IssueBuffer::accepts(
                    new PossiblyNullOperand(
                        'Cannot concatenate with a possibly null ' . $left_type,
                        new CodeLocation($statements_checker->getSource(), $left)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($right_type->isNullable() && !$right_type->ignore_nullable_issues) {
                if (IssueBuffer::accepts(
                    new PossiblyNullOperand(
                        'Cannot concatenate with a possibly null ' . $right_type,
                        new CodeLocation($statements_checker->getSource(), $right)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            $project_checker = $statements_checker->getFileChecker()->project_checker;

            $left_type_match = TypeChecker::isContainedBy(
                $project_checker,
                $left_type,
                Type::getString(),
                true,
                false,
                $left_has_scalar_match
            );

            $right_type_match = TypeChecker::isContainedBy(
                $project_checker,
                $right_type,
                Type::getString(),
                true,
                false,
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
     * @param  StatementsSource|null    $source
     * @param  int|null                 &$nesting
     *
     * @return string|null
     */
    public static function getVarId(
        PhpParser\Node\Expr $stmt,
        $this_class_name,
        StatementsSource $source = null,
        &$nesting = null
    ) {
        if ($stmt instanceof PhpParser\Node\Expr\Variable && is_string($stmt->name)) {
            return '$' . $stmt->name;
        }

        if ($stmt instanceof PhpParser\Node\Expr\StaticPropertyFetch
            && is_string($stmt->name)
            && $stmt->class instanceof PhpParser\Node\Name
        ) {
            if (count($stmt->class->parts) === 1
                && in_array(strtolower($stmt->class->parts[0]), ['self', 'static', 'parent'], true)
            ) {
                if (!$this_class_name) {
                    $fq_class_name = $stmt->class->parts[0];
                } else {
                    $fq_class_name = $this_class_name;
                }
            } else {
                $fq_class_name = $source
                    ? ClassLikeChecker::getFQCLNFromNameObject(
                        $stmt->class,
                        $source->getAliases()
                    )
                    : implode('\\', $stmt->class->parts);
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
            ++$nesting;

            return self::getVarId($stmt->var, $this_class_name, $source, $nesting);
        }

        return null;
    }

    /**
     * @param  PhpParser\Node\Expr      $stmt
     * @param  string|null              $this_class_name
     * @param  StatementsSource|null    $source
     *
     * @return string|null
     */
    public static function getRootVarId(
        PhpParser\Node\Expr $stmt,
        $this_class_name,
        StatementsSource $source = null
    ) {
        if ($stmt instanceof PhpParser\Node\Expr\Variable
            || $stmt instanceof PhpParser\Node\Expr\StaticPropertyFetch
        ) {
            return self::getVarId($stmt, $this_class_name, $source);
        }

        if ($stmt instanceof PhpParser\Node\Expr\PropertyFetch && is_string($stmt->name)) {
            $property_root = self::getRootVarId($stmt->var, $this_class_name, $source);

            if ($property_root) {
                return $property_root . '->' . $stmt->name;
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            return self::getRootVarId($stmt->var, $this_class_name, $source);
        }

        return null;
    }

    /**
     * @param  PhpParser\Node\Expr      $stmt
     * @param  string|null              $this_class_name
     * @param  StatementsSource|null    $source
     *
     * @return string|null
     */
    public static function getArrayVarId(
        PhpParser\Node\Expr $stmt,
        $this_class_name,
        StatementsSource $source = null
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
     * @param  Type\Union   $return_type
     * @param  string|null  $calling_class
     * @param  string|null  $method_id
     *
     * @return Type\Union
     */
    public static function fleshOutType(
        ProjectChecker $project_checker,
        Type\Union $return_type,
        $calling_class = null,
        $method_id = null
    ) {
        $return_type = clone $return_type;

        $new_return_type_parts = [];

        foreach ($return_type->types as $return_type_part) {
            $new_return_type_parts[] = self::fleshOutAtomicType(
                $project_checker,
                $return_type_part,
                $calling_class,
                $method_id
            );
        }

        $fleshed_out_type = new Type\Union($new_return_type_parts);

        $fleshed_out_type->from_docblock = $return_type->from_docblock;
        $fleshed_out_type->ignore_nullable_issues = $return_type->ignore_nullable_issues;

        return $fleshed_out_type;
    }

    /**
     * @param  Type\Atomic  &$return_type
     * @param  string|null  $calling_class
     * @param  string|null  $method_id
     *
     * @return Type\Atomic
     */
    protected static function fleshOutAtomicType(
        ProjectChecker $project_checker,
        Type\Atomic $return_type,
        $calling_class,
        $method_id
    ) {
        if ($return_type instanceof TNamedObject) {
            if (in_array(strtolower($return_type->value), ['$this', 'static', 'self'], true)) {
                if (!$calling_class) {
                    throw new \InvalidArgumentException(
                        'Cannot handle ' . $return_type->value . ' when $calling_class is empty'
                    );
                }

                if (strtolower($return_type->value) === 'static' || !$method_id) {
                    $return_type->value = $calling_class;
                } elseif (strpos($method_id, ':-:closure') !== false) {
                    $return_type->value = $calling_class;
                } else {
                    list(, $method_name) = explode('::', $method_id);

                    $appearing_method_id = MethodChecker::getAppearingMethodId(
                        $project_checker,
                        $calling_class . '::' . $method_name
                    );

                    $return_type->value = explode('::', (string)$appearing_method_id)[0];
                }
            }
        }

        if ($return_type instanceof Type\Atomic\TArray || $return_type instanceof Type\Atomic\TGenericObject) {
            foreach ($return_type->type_params as &$type_param) {
                $type_param = self::fleshOutType($project_checker, $type_param, $calling_class, $method_id);
            }
        }

        return $return_type;
    }

    /**
     * @param   StatementsChecker           $statements_checker
     * @param   PhpParser\Node\Expr\Closure $stmt
     * @param   Context                     $context
     *
     * @return  false|null
     */
    protected static function analyzeClosureUses(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\Closure $stmt,
        Context $context
    ) {
        foreach ($stmt->uses as $use) {
            $use_var_id = '$' . $use->var;
            if (!$context->hasVariable($use_var_id)) {
                if ($use->byRef) {
                    $context->vars_in_scope[$use_var_id] = Type::getMixed();
                    $context->vars_possibly_in_scope[$use_var_id] = true;

                    if (!$statements_checker->hasVariable($use_var_id)) {
                        $statements_checker->registerVariable($use_var_id, new CodeLocation($statements_checker, $use));
                    }

                    return;
                }

                if (!isset($context->vars_possibly_in_scope[$use_var_id])) {
                    if ($context->check_variables) {
                        if (IssueBuffer::accepts(
                            new UndefinedVariable(
                                'Cannot find referenced variable ' . $use_var_id,
                                new CodeLocation($statements_checker->getSource(), $use)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        return null;
                    }
                }

                $first_appearance = $statements_checker->getFirstAppearance($use_var_id);

                if ($first_appearance) {
                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedVariable(
                            'Possibly undefined variable ' . $use_var_id . ', first seen on line ' .
                                $first_appearance->getLineNumber(),
                            new CodeLocation($statements_checker->getSource(), $use)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    continue;
                }

                if ($context->check_variables) {
                    if (IssueBuffer::accepts(
                        new UndefinedVariable(
                            'Cannot find referenced variable ' . $use_var_id,
                            new CodeLocation($statements_checker->getSource(), $use)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    continue;
                }
            }
        }

        return null;
    }

    /**
     * @param   StatementsChecker           $statements_checker
     * @param   PhpParser\Node\Expr\Yield_  $stmt
     * @param   Context                     $context
     *
     * @return  false|null
     */
    protected static function analyzeYield(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\Yield_ $stmt,
        Context $context
    ) {
        $doc_comment_text = (string)$stmt->getDocComment();

        $var_comment = null;

        if ($doc_comment_text) {
            try {
                $var_comment = CommentChecker::getTypeFromComment(
                    $doc_comment_text,
                    $statements_checker,
                    $statements_checker->getAliases()
                );
            } catch (DocblockParseException $e) {
                if (IssueBuffer::accepts(
                    new InvalidDocblock(
                        (string)$e->getMessage(),
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    )
                )) {
                    // fall through
                }
            }

            if ($var_comment && $var_comment->var_id) {
                $comment_type = ExpressionChecker::fleshOutType(
                    $statements_checker->getFileChecker()->project_checker,
                    Type::parseString($var_comment->type),
                    $context->self
                );

                $context->vars_in_scope[$var_comment->var_id] = $comment_type;
            }
        }

        if ($stmt->key) {
            if (self::analyze($statements_checker, $stmt->key, $context) === false) {
                return false;
            }
        }

        if ($stmt->value) {
            if (self::analyze($statements_checker, $stmt->value, $context) === false) {
                return false;
            }

            if ($var_comment && !$var_comment->var_id) {
                $stmt->inferredType = Type::parseString($var_comment->type);
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
     *
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
            // this should be whatever the generator above returns, but *not* the return type
            $stmt->inferredType = Type::getMixed();
        }

        return null;
    }

    /**
     * @param   StatementsChecker           $statements_checker
     * @param   PhpParser\Node\Expr\Ternary $stmt
     * @param   Context                     $context
     *
     * @return  false|null
     */
    protected static function analyzeTernary(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\Ternary $stmt,
        Context $context
    ) {
        $pre_referenced_var_ids = $context->referenced_var_ids;
        $context->referenced_var_ids = [];

        $context->inside_conditional = true;
        if (self::analyze($statements_checker, $stmt->cond, $context) === false) {
            return false;
        }

        $new_referenced_var_ids = $context->referenced_var_ids;
        $context->referenced_var_ids = array_merge($pre_referenced_var_ids, $new_referenced_var_ids);

        $context->inside_conditional = false;

        $t_if_context = clone $context;

        $if_clauses = AlgebraChecker::getFormula(
            $stmt->cond,
            $statements_checker->getFQCLN(),
            $statements_checker
        );

        $ternary_clauses = AlgebraChecker::simplifyCNF(array_merge($context->clauses, $if_clauses));

        $negated_clauses = AlgebraChecker::negateFormula($if_clauses);

        $negated_if_types = AlgebraChecker::getTruthsFromFormula($negated_clauses);

        $reconcilable_if_types = AlgebraChecker::getTruthsFromFormula($ternary_clauses);

        $changed_var_ids = [];

        $t_if_vars_in_scope_reconciled = TypeChecker::reconcileKeyedTypes(
            $reconcilable_if_types,
            $t_if_context->vars_in_scope,
            $changed_var_ids,
            $new_referenced_var_ids,
            $statements_checker,
            new CodeLocation($statements_checker->getSource(), $stmt->cond),
            $statements_checker->getSuppressedIssues()
        );

        if ($t_if_vars_in_scope_reconciled === false) {
            return false;
        }

        $t_if_context->vars_in_scope = $t_if_vars_in_scope_reconciled;
        $t_else_context = clone $context;

        if ($stmt->if) {
            if (self::analyze($statements_checker, $stmt->if, $t_if_context) === false) {
                return false;
            }

            foreach ($t_if_context->vars_in_scope as $var_id => $type) {
                if (isset($context->vars_in_scope[$var_id])) {
                    $context->vars_in_scope[$var_id] = Type::combineUnionTypes($context->vars_in_scope[$var_id], $type);
                }
            }

            $context->referenced_var_ids = array_merge(
                $context->referenced_var_ids,
                $t_if_context->referenced_var_ids
            );
        }

        if ($negated_if_types) {
            $t_else_vars_in_scope_reconciled = TypeChecker::reconcileKeyedTypes(
                $negated_if_types,
                $t_else_context->vars_in_scope,
                $changed_var_ids,
                $new_referenced_var_ids,
                $statements_checker,
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

        foreach ($t_else_context->vars_in_scope as $var_id => $type) {
            if (isset($context->vars_in_scope[$var_id])) {
                $context->vars_in_scope[$var_id] = Type::combineUnionTypes($context->vars_in_scope[$var_id], $type);
            }
        }

        $context->referenced_var_ids = array_merge(
            $context->referenced_var_ids,
            $t_else_context->referenced_var_ids
        );

        $lhs_type = null;

        if ($stmt->if) {
            if (isset($stmt->if->inferredType)) {
                $lhs_type = $stmt->if->inferredType;
            }
        } elseif (isset($stmt->cond->inferredType)) {
            $if_return_type_reconciled = TypeChecker::reconcileTypes(
                '!falsy',
                $stmt->cond->inferredType,
                '',
                $statements_checker,
                new CodeLocation($statements_checker->getSource(), $stmt),
                $statements_checker->getSuppressedIssues()
            );

            if ($if_return_type_reconciled === false) {
                return false;
            }

            $lhs_type = $if_return_type_reconciled;
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
     *
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
     *
     * @return  void
     */
    protected static function analyzeEmpty(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\Empty_ $stmt,
        Context $context
    ) {
        self::analyzeIssetVar($statements_checker, $stmt->expr, $context);
    }

    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Scalar\Encapsed  $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    protected static function analyzeEncapsulatedString(
        StatementsChecker $statements_checker,
        PhpParser\Node\Scalar\Encapsed $stmt,
        Context $context
    ) {
        $project_checker = $statements_checker->getFileChecker()->project_checker;

        $function_storage = null;

        if ($project_checker->infer_types_from_usage) {
            $source_checker = $statements_checker->getSource();

            if ($source_checker instanceof FunctionLikeChecker) {
                $function_storage = $source_checker->getFunctionLikeStorage($statements_checker);
            }
        }

        /** @var PhpParser\Node\Expr $part */
        foreach ($stmt->parts as $part) {
            if (self::analyze($statements_checker, $part, $context) === false) {
                return false;
            }

            if ($function_storage) {
                $context->inferType($part, $function_storage, Type::getString());
            }
        }

        $stmt->inferredType = Type::getString();

        return null;
    }

    /**
     * @param  StatementsChecker          $statements_checker
     * @param  PhpParser\Node\Expr\Isset_ $stmt
     * @param  Context                    $context
     *
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

                if (!isset($context->vars_in_scope[$var_id])) {
                    $context->vars_in_scope[$var_id] = Type::getMixed();
                    $context->vars_possibly_in_scope[$var_id] = true;
                }
            }

            self::analyzeIssetVar($statements_checker, $isset_var, $context);
        }
    }

    /**
     * @param  StatementsChecker   $statements_checker
     * @param  PhpParser\Node\Expr $stmt
     * @param  Context             $context
     *
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
        } elseif ($stmt instanceof PhpParser\Node\Expr\PropertyFetch) {
            self::analyzeIssetVar($statements_checker, $stmt->var, $context);
        } elseif ($stmt instanceof PhpParser\Node\Expr\Variable) {
            if (is_string($stmt->name)) {
                $context->hasVariable('$' . $stmt->name);
            }
        }
    }

    /**
     * @param  StatementsChecker            $statements_checker
     * @param  PhpParser\Node\Expr\Clone_   $stmt
     * @param  Context                      $context
     *
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
                if (!$clone_type_part instanceof TNamedObject &&
                    !$clone_type_part instanceof TObject &&
                    !$clone_type_part instanceof TMixed
                ) {
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
     *
     * @return bool
     */
    public static function isMock($fq_class_name)
    {
        return in_array($fq_class_name, Config::getInstance()->getMockClasses(), true);
    }

    /**
     * @return void
     */
    public static function clearCache()
    {
        self::$reflection_functions = [];
    }
}
