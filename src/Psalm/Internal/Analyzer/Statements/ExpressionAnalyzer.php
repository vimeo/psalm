<?php
namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ClosureAnalyzer;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ArrayAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\AssertionFinder;
use Psalm\Internal\Analyzer\Statements\Expression\AssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\BinaryOpAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\FunctionCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\MethodCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\NewAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\StaticCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ArrayFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ConstFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\PropertyFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\VariableFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\IncludeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\TernaryAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\DocblockParseException;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\FileSource;
use Psalm\Issue\DuplicateParam;
use Psalm\Issue\ForbiddenCode;
use Psalm\Issue\InvalidCast;
use Psalm\Issue\InvalidClone;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\InvalidOperand;
use Psalm\Issue\PossiblyInvalidCast;
use Psalm\Issue\PossiblyInvalidOperand;
use Psalm\Issue\PossiblyUndefinedVariable;
use Psalm\Issue\UndefinedConstant;
use Psalm\Issue\UndefinedVariable;
use Psalm\Issue\UnrecognizedExpression;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TString;
use Psalm\Internal\Type\TypeCombination;

/**
 * @internal
 */
class ExpressionAnalyzer
{
    /**
     * @param   StatementsAnalyzer   $statements_analyzer
     * @param   PhpParser\Node\Expr $stmt
     * @param   Context             $context
     * @param   bool                $array_assignment
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        Context $context,
        $array_assignment = false,
        Context $global_context = null
    ) {
        $codebase = $statements_analyzer->getCodebase();

        if ($stmt instanceof PhpParser\Node\Expr\Variable) {
            if (VariableFetchAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context,
                false,
                null,
                $array_assignment
            ) === false
            ) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Assign) {
            $assignment_type = AssignmentAnalyzer::analyze(
                $statements_analyzer,
                $stmt->var,
                $stmt->expr,
                null,
                $context,
                $stmt->getDocComment()
            );

            if ($assignment_type === false) {
                return false;
            }

            $stmt->inferredType = $assignment_type;
        } elseif ($stmt instanceof PhpParser\Node\Expr\AssignOp) {
            if (AssignmentAnalyzer::analyzeAssignmentOperation($statements_analyzer, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\MethodCall) {
            if (MethodCallAnalyzer::analyze($statements_analyzer, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\StaticCall) {
            if (StaticCallAnalyzer::analyze($statements_analyzer, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\ConstFetch) {
            ConstFetchAnalyzer::analyze($statements_analyzer, $stmt, $context);
        } elseif ($stmt instanceof PhpParser\Node\Scalar\String_) {
            $stmt->inferredType = Type::getString($stmt->value);
        } elseif ($stmt instanceof PhpParser\Node\Scalar\EncapsedStringPart) {
            // do nothing
        } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst) {
            if ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Line) {
                $stmt->inferredType = Type::getInt();
            } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Class_) {
                if (!$context->self) {
                    if (IssueBuffer::accepts(
                        new UndefinedConstant(
                            'Cannot get __class__ outside a class',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    $stmt->inferredType = Type::getClassString();
                } else {
                    if ($codebase->alter_code) {
                        $codebase->classlikes->handleClassLikeReferenceInMigration(
                            $codebase,
                            $statements_analyzer,
                            $stmt,
                            $context->self,
                            $context->calling_method_id
                        );
                    }

                    $stmt->inferredType = Type::getLiteralClassString($context->self);
                }
            } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Namespace_) {
                $namespace = $statements_analyzer->getNamespace();
                if ($namespace === null
                    && IssueBuffer::accepts(
                        new UndefinedConstant(
                            'Cannot get __namespace__ outside a namespace',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )
                ) {
                    // fall through
                }

                $stmt->inferredType = Type::getString($namespace);
            } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst\File
                || $stmt instanceof PhpParser\Node\Scalar\MagicConst\Dir
                || $stmt instanceof PhpParser\Node\Scalar\MagicConst\Function_
                || $stmt instanceof PhpParser\Node\Scalar\MagicConst\Trait_
                || $stmt instanceof PhpParser\Node\Scalar\MagicConst\Method
            ) {
                $stmt->inferredType = Type::getString();
            }
        } elseif ($stmt instanceof PhpParser\Node\Scalar\LNumber) {
            $stmt->inferredType = Type::getInt(false, $stmt->value);
        } elseif ($stmt instanceof PhpParser\Node\Scalar\DNumber) {
            $stmt->inferredType = Type::getFloat($stmt->value);
        } elseif ($stmt instanceof PhpParser\Node\Expr\UnaryMinus ||
            $stmt instanceof PhpParser\Node\Expr\UnaryPlus
        ) {
            if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            if (!isset($stmt->expr->inferredType)) {
                $stmt->inferredType = new Type\Union([new TInt, new TFloat]);
            } elseif ($stmt->expr->inferredType->isMixed()) {
                $stmt->inferredType = Type::getMixed();
            } else {
                $acceptable_types = [];

                foreach ($stmt->expr->inferredType->getTypes() as $type_part) {
                    if ($type_part instanceof TInt || $type_part instanceof TFloat) {
                        if ($type_part instanceof Type\Atomic\TLiteralInt
                            && $stmt instanceof PhpParser\Node\Expr\UnaryMinus
                        ) {
                            $type_part->value = -$type_part->value;
                        } elseif ($type_part instanceof Type\Atomic\TLiteralFloat
                            && $stmt instanceof PhpParser\Node\Expr\UnaryMinus
                        ) {
                            $type_part->value = -$type_part->value;
                        }

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
            self::analyzeIsset($statements_analyzer, $stmt, $context);
            $stmt->inferredType = Type::getBool();
        } elseif ($stmt instanceof PhpParser\Node\Expr\ClassConstFetch) {
            if (ConstFetchAnalyzer::analyzeClassConst($statements_analyzer, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\PropertyFetch) {
            if (PropertyFetchAnalyzer::analyzeInstance($statements_analyzer, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\StaticPropertyFetch) {
            if (PropertyFetchAnalyzer::analyzeStatic($statements_analyzer, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\BitwiseNot) {
            if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            if (!isset($stmt->expr->inferredType)) {
                $stmt->inferredType = new Type\Union([new TInt(), new TString()]);
            } elseif ($stmt->expr->inferredType->isMixed()) {
                $stmt->inferredType = Type::getMixed();
            } else {
                $acceptable_types = [];
                $unacceptable_type = null;
                $has_valid_operand = false;

                foreach ($stmt->expr->inferredType->getTypes() as $type_string => $type_part) {
                    if ($type_part instanceof TInt || $type_part instanceof TString) {
                        if ($type_part instanceof Type\Atomic\TLiteralInt) {
                            $type_part->value = ~$type_part->value;
                        } elseif ($type_part instanceof Type\Atomic\TLiteralString) {
                            $type_part->value = ~$type_part->value;
                        }

                        $acceptable_types[] = $type_part;
                        $has_valid_operand = true;
                    } elseif ($type_part instanceof TFloat) {
                        $type_part = ($type_part instanceof Type\Atomic\TLiteralFloat) ?
                            new Type\Atomic\TLiteralInt(~$type_part->value) :
                            new TInt;

                        $stmt->expr->inferredType->removeType($type_string);
                        $stmt->expr->inferredType->addType($type_part);

                        $acceptable_types[] = $type_part;
                        $has_valid_operand = true;
                    } elseif (!$unacceptable_type) {
                        $unacceptable_type = $type_part;
                    }
                }

                if ($unacceptable_type) {
                    $message = 'Cannot negate a non-numeric non-string type ' . $unacceptable_type;
                    if ($has_valid_operand) {
                        if (IssueBuffer::accepts(
                            new PossiblyInvalidOperand(
                                $message,
                                new CodeLocation($statements_analyzer, $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    } else {
                        if (IssueBuffer::accepts(
                            new InvalidOperand(
                                $message,
                                new CodeLocation($statements_analyzer, $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }

                    $stmt->inferredType = Type::getMixed();
                } else {
                    $stmt->inferredType = new Type\Union($acceptable_types);
                }
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            if (BinaryOpAnalyzer::analyze(
                $statements_analyzer,
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
            if (self::analyze($statements_analyzer, $stmt->var, $context) === false) {
                return false;
            }

            if (isset($stmt->var->inferredType)) {
                $return_type = null;

                $fake_right_expr = new PhpParser\Node\Scalar\LNumber(1, $stmt->getAttributes());
                $fake_right_expr->inferredType = Type::getInt();

                BinaryOpAnalyzer::analyzeNonDivArithmeticOp(
                    $statements_analyzer,
                    $stmt->var,
                    $fake_right_expr,
                    $stmt,
                    $return_type,
                    $context
                );

                $stmt->inferredType = clone $stmt->var->inferredType;
                $stmt->inferredType->from_calculation = true;

                foreach ($stmt->inferredType->getTypes() as $atomic_type) {
                    if ($atomic_type instanceof Type\Atomic\TLiteralInt) {
                        $stmt->inferredType->addType(new Type\Atomic\TInt);
                    } elseif ($atomic_type instanceof Type\Atomic\TLiteralFloat) {
                        $stmt->inferredType->addType(new Type\Atomic\TFloat);
                    }
                }

                $var_id = self::getArrayVarId($stmt->var, null);

                if ($var_id && isset($context->vars_in_scope[$var_id])) {
                    $context->vars_in_scope[$var_id] = $stmt->inferredType;

                    if ($context->collect_references && $stmt->var instanceof PhpParser\Node\Expr\Variable) {
                        $location = new CodeLocation($statements_analyzer, $stmt->var);
                        $context->assigned_var_ids[$var_id] = true;
                        $context->possibly_assigned_var_ids[$var_id] = true;
                        $statements_analyzer->registerVariableAssignment(
                            $var_id,
                            $location
                        );
                        $context->unreferenced_vars[$var_id] = [$location->getHash() => $location];
                    }
                }
            } else {
                $stmt->inferredType = Type::getMixed();
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\New_) {
            if (NewAnalyzer::analyze($statements_analyzer, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Array_) {
            if (ArrayAnalyzer::analyze($statements_analyzer, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Scalar\Encapsed) {
            if (self::analyzeEncapsulatedString($statements_analyzer, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\FuncCall) {
            if (FunctionCallAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context
            ) === false
            ) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Ternary) {
            if (TernaryAnalyzer::analyze($statements_analyzer, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\BooleanNot) {
            if (self::analyzeBooleanNot($statements_analyzer, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Empty_) {
            self::analyzeEmpty($statements_analyzer, $stmt, $context);
        } elseif ($stmt instanceof PhpParser\Node\Expr\Closure) {
            $closure_analyzer = new ClosureAnalyzer($stmt, $statements_analyzer);

            if (self::analyzeClosureUses($statements_analyzer, $stmt, $context) === false) {
                return false;
            }

            $use_context = new Context($context->self);
            $use_context->collect_references = $codebase->collect_references;

            if (!$statements_analyzer->isStatic()) {
                if ($context->collect_mutations &&
                    $context->self &&
                    $codebase->classExtends(
                        $context->self,
                        (string)$statements_analyzer->getFQCLN()
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

            foreach ($context->vars_possibly_in_scope as $var => $_) {
                if (strpos($var, '$this->') === 0) {
                    $use_context->vars_possibly_in_scope[$var] = true;
                }
            }

            $byref_uses = [];

            foreach ($stmt->uses as $use) {
                if (!is_string($use->var->name)) {
                    continue;
                }

                $use_var_id = '$' . $use->var->name;

                if ($use->byRef) {
                    $byref_uses[$use_var_id] = true;
                }

                // insert the ref into the current context if passed by ref, as whatever we're passing
                // the closure to could execute it straight away.
                if (!$context->hasVariable($use_var_id, $statements_analyzer) && $use->byRef) {
                    $context->vars_in_scope[$use_var_id] = Type::getMixed();
                }

                $use_context->vars_in_scope[$use_var_id] =
                    $context->hasVariable($use_var_id, $statements_analyzer) && !$use->byRef
                    ? clone $context->vars_in_scope[$use_var_id]
                    : Type::getMixed();

                $use_context->vars_possibly_in_scope[$use_var_id] = true;
            }

            $use_context->calling_method_id = $context->calling_method_id;

            $closure_analyzer->analyze($use_context, $context, false, $byref_uses);

            if (!isset($stmt->inferredType)) {
                $stmt->inferredType = Type::getClosure();
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            if (ArrayFetchAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context
            ) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Int_) {
            if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $stmt->inferredType = Type::getInt();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Double) {
            if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $stmt->inferredType = Type::getFloat();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Bool_) {
            if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $stmt->inferredType = Type::getBool();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\String_) {
            if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            if (isset($stmt->expr->inferredType)) {
                self::castStringAttempt($statements_analyzer, $stmt->expr);
            }

            $stmt->inferredType = Type::getString();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Object_) {
            if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $stmt->inferredType = new Type\Union([new TNamedObject('stdClass')]);
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Array_) {
            if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $permissible_atomic_types = [];
            $all_permissible = false;

            if (isset($stmt->expr->inferredType)) {
                $all_permissible = true;

                foreach ($stmt->expr->inferredType->getTypes() as $type) {
                    if ($type instanceof Scalar) {
                        $permissible_atomic_types[] = new ObjectLike([new Type\Union([$type])]);
                    } elseif ($type instanceof TNull) {
                        $permissible_atomic_types[] = new TArray([Type::getEmpty(), Type::getEmpty()]);
                    } elseif ($type instanceof TArray) {
                        $permissible_atomic_types[] = clone $type;
                    } elseif ($type instanceof ObjectLike) {
                        $permissible_atomic_types[] = clone $type;
                    } else {
                        $all_permissible = false;
                        break;
                    }
                }
            }

            if ($all_permissible) {
                $stmt->inferredType = TypeCombination::combineTypes($permissible_atomic_types);
            } else {
                $stmt->inferredType = Type::getArray();
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Unset_) {
            if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $stmt->inferredType = Type::getNull();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Clone_) {
            self::analyzeClone($statements_analyzer, $stmt, $context);
        } elseif ($stmt instanceof PhpParser\Node\Expr\Instanceof_) {
            if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            if ($stmt->class instanceof PhpParser\Node\Expr) {
                if (self::analyze($statements_analyzer, $stmt->class, $context) === false) {
                    return false;
                }
            } elseif (!in_array(strtolower($stmt->class->parts[0]), ['self', 'static', 'parent'], true)
            ) {
                if ($context->check_classes) {
                    $fq_class_name = ClassLikeAnalyzer::getFQCLNFromNameObject(
                        $stmt->class,
                        $statements_analyzer->getAliases()
                    );

                    if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                        $statements_analyzer,
                        $fq_class_name,
                        new CodeLocation($statements_analyzer->getSource(), $stmt->class),
                        $statements_analyzer->getSuppressedIssues(),
                        false
                    ) === false) {
                        return false;
                    }

                    if ($codebase->store_node_types
                        && $fq_class_name
                        && $codebase->classOrInterfaceExists($fq_class_name)
                    ) {
                        $codebase->analyzer->addNodeReference(
                            $statements_analyzer->getFilePath(),
                            $stmt->class,
                            $fq_class_name
                        );
                    }

                    if ($codebase->alter_code) {
                        $codebase->classlikes->handleClassLikeReferenceInMigration(
                            $codebase,
                            $statements_analyzer,
                            $stmt->class,
                            $fq_class_name,
                            $context->calling_method_id
                        );
                    }
                }
            }

            $stmt->inferredType = Type::getBool();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Exit_) {
            if ($stmt->expr) {
                if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                    return false;
                }
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Include_) {
            IncludeAnalyzer::analyze($statements_analyzer, $stmt, $context, $global_context);
        } elseif ($stmt instanceof PhpParser\Node\Expr\Eval_) {
            $context->check_classes = false;
            $context->check_variables = false;

            if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\AssignRef) {
            if (AssignmentAnalyzer::analyzeAssignmentRef($statements_analyzer, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\ErrorSuppress) {
            if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }
            $stmt->inferredType = isset($stmt->expr->inferredType) ? $stmt->expr->inferredType : null;
        } elseif ($stmt instanceof PhpParser\Node\Expr\ShellExec) {
            if (IssueBuffer::accepts(
                new ForbiddenCode(
                    'Use of shell_exec',
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // continue
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Print_) {
            if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Yield_) {
            self::analyzeYield($statements_analyzer, $stmt, $context);
        } elseif ($stmt instanceof PhpParser\Node\Expr\YieldFrom) {
            self::analyzeYieldFrom($statements_analyzer, $stmt, $context);
        } elseif ($stmt instanceof PhpParser\Node\Expr\Error) {
            // do nothing
        } else {
            if (IssueBuffer::accepts(
                new UnrecognizedExpression(
                    'Psalm does not understand ' . get_class($stmt),
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                return false;
            }
        }

        if (!$context->inside_conditional
            && ($stmt instanceof PhpParser\Node\Expr\BinaryOp
                || $stmt instanceof PhpParser\Node\Expr\Instanceof_
                || $stmt instanceof PhpParser\Node\Expr\Assign
                || $stmt instanceof PhpParser\Node\Expr\BooleanNot
                || $stmt instanceof PhpParser\Node\Expr\Empty_
                || $stmt instanceof PhpParser\Node\Expr\Isset_
                || $stmt instanceof PhpParser\Node\Expr\FuncCall)
        ) {
            AssertionFinder::scrapeAssertions(
                $stmt,
                $context->self,
                $statements_analyzer,
                $codebase
            );
        }

        $plugin_classes = $codebase->config->after_expression_checks;

        if ($plugin_classes) {
            $file_manipulations = [];

            foreach ($plugin_classes as $plugin_fq_class_name) {
                if ($plugin_fq_class_name::afterExpressionAnalysis(
                    $stmt,
                    $context,
                    $statements_analyzer->getSource(),
                    $codebase,
                    $file_manipulations
                ) === false) {
                    return false;
                }
            }

            if ($file_manipulations) {
                /** @psalm-suppress MixedTypeCoercion */
                FileManipulationBuffer::add($statements_analyzer->getFilePath(), $file_manipulations);
            }
        }

        return null;
    }

    /**
     * @param  StatementsAnalyzer    $statements_analyzer
     * @param  PhpParser\Node\Expr  $stmt
     * @param  Type\Union           $by_ref_type
     * @param  Context              $context
     * @param  bool                 $constrain_type
     *
     * @return void
     */
    public static function assignByRefParam(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        Type\Union $by_ref_type,
        Type\Union $by_ref_out_type,
        Context $context,
        bool $constrain_type = true,
        bool $prevent_null = false
    ) {
        $var_id = self::getVarId(
            $stmt,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        if ($var_id) {
            if (!$by_ref_type->hasMixed() && $constrain_type) {
                $context->byref_constraints[$var_id] = new \Psalm\Internal\ReferenceConstraint($by_ref_type);
            }

            if (!$context->hasVariable($var_id, $statements_analyzer)) {
                $context->vars_possibly_in_scope[$var_id] = true;

                if (!$statements_analyzer->hasVariable($var_id)) {
                    $location = new CodeLocation($statements_analyzer, $stmt);
                    $statements_analyzer->registerVariable($var_id, $location, null);

                    if ($constrain_type
                        && $prevent_null
                        && !$by_ref_type->isMixed()
                        && !$by_ref_type->isNullable()
                        && !strpos($var_id, '->')
                        && !strpos($var_id, '::')
                    ) {
                        if (IssueBuffer::accepts(
                            new \Psalm\Issue\NullReference(
                                'Not expecting null argument passed by reference',
                                new CodeLocation($statements_analyzer->getSource(), $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }

                    if ($context->collect_references) {
                        $context->unreferenced_vars[$var_id] = [$location->getHash() => $location];
                    }

                    $context->hasVariable($var_id, $statements_analyzer);
                }
            } elseif ($var_id === '$this') {
                // don't allow changing $this
                return;
            } else {
                $existing_type = $context->vars_in_scope[$var_id];

                // removes dependent vars from $context
                $context->removeDescendents(
                    $var_id,
                    $existing_type,
                    $by_ref_type,
                    $statements_analyzer
                );

                if ($existing_type->getId() !== 'array<empty, empty>') {
                    $context->vars_in_scope[$var_id] = clone $by_ref_out_type;

                    if (!isset($stmt->inferredType) || $stmt->inferredType->isEmpty()) {
                        $stmt->inferredType = clone $by_ref_type;
                    }

                    return;
                }
            }

            $context->vars_in_scope[$var_id] = $by_ref_out_type;

            if (!isset($stmt->inferredType) || $stmt->inferredType->isEmpty()) {
                $stmt->inferredType = clone $by_ref_type;
            }
        }
    }

    /**
     * @param  PhpParser\Node\Expr      $stmt
     * @param  string|null              $this_class_name
     * @param  FileSource|null    $source
     * @param  int|null                 &$nesting
     *
     * @return string|null
     */
    public static function getVarId(
        PhpParser\Node\Expr $stmt,
        $this_class_name,
        FileSource $source = null,
        &$nesting = null
    ) {
        if ($stmt instanceof PhpParser\Node\Expr\Variable && is_string($stmt->name)) {
            return '$' . $stmt->name;
        }

        if ($stmt instanceof PhpParser\Node\Expr\StaticPropertyFetch
            && $stmt->name instanceof PhpParser\Node\Identifier
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
                    ? ClassLikeAnalyzer::getFQCLNFromNameObject(
                        $stmt->class,
                        $source->getAliases()
                    )
                    : implode('\\', $stmt->class->parts);
            }

            return $fq_class_name . '::$' . $stmt->name->name;
        }

        if ($stmt instanceof PhpParser\Node\Expr\PropertyFetch && $stmt->name instanceof PhpParser\Node\Identifier) {
            $object_id = self::getVarId($stmt->var, $this_class_name, $source);

            if (!$object_id) {
                return null;
            }

            return $object_id . '->' . $stmt->name->name;
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
     * @param  FileSource|null    $source
     *
     * @return string|null
     */
    public static function getRootVarId(
        PhpParser\Node\Expr $stmt,
        $this_class_name,
        FileSource $source = null
    ) {
        if ($stmt instanceof PhpParser\Node\Expr\Variable
            || $stmt instanceof PhpParser\Node\Expr\StaticPropertyFetch
        ) {
            return self::getVarId($stmt, $this_class_name, $source);
        }

        if ($stmt instanceof PhpParser\Node\Expr\PropertyFetch && $stmt->name instanceof PhpParser\Node\Identifier) {
            $property_root = self::getRootVarId($stmt->var, $this_class_name, $source);

            if ($property_root) {
                return $property_root . '->' . $stmt->name->name;
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
     * @param  FileSource|null    $source
     *
     * @return string|null
     */
    public static function getArrayVarId(
        PhpParser\Node\Expr $stmt,
        $this_class_name,
        FileSource $source = null
    ) {
        if ($stmt instanceof PhpParser\Node\Expr\Assign) {
            return self::getArrayVarId($stmt->var, $this_class_name, $source);
        }

        if ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            $root_var_id = self::getArrayVarId($stmt->var, $this_class_name, $source);

            $offset = null;

            if ($root_var_id) {
                if ($stmt->dim instanceof PhpParser\Node\Scalar\String_
                    || $stmt->dim instanceof PhpParser\Node\Scalar\LNumber
                ) {
                    $offset = $stmt->dim instanceof PhpParser\Node\Scalar\String_
                        ? '\'' . $stmt->dim->value . '\''
                        : $stmt->dim->value;
                } elseif ($stmt->dim instanceof PhpParser\Node\Expr\Variable
                    && is_string($stmt->dim->name)
                ) {
                    $offset = '$' . $stmt->dim->name;
                } elseif ($stmt->dim instanceof PhpParser\Node\Expr\ConstFetch) {
                    $offset = implode('\\', $stmt->dim->name->parts);
                } elseif (isset($stmt->dim->inferredType)) {
                    if ($stmt->dim->inferredType->isSingleStringLiteral()) {
                        $offset = '\'' . $stmt->dim->inferredType->getSingleStringLiteral()->value . '\'';
                    } elseif ($stmt->dim->inferredType->isSingleIntLiteral()) {
                        $offset = $stmt->dim->inferredType->getSingleIntLiteral()->value;
                    }
                } elseif ($stmt->dim instanceof PhpParser\Node\Expr\ClassConstFetch
                    && $stmt->dim->name instanceof PhpParser\Node\Identifier
                ) {
                    /** @var string|null */
                    $resolved_name = $stmt->dim->class->getAttribute('resolvedName');

                    if ($resolved_name) {
                        $offset = $resolved_name . '::' . $stmt->dim->name;
                    }
                }

                return $root_var_id && $offset !== null ? $root_var_id . '[' . $offset . ']' : null;
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\PropertyFetch) {
            $object_id = self::getArrayVarId($stmt->var, $this_class_name, $source);

            if (!$object_id) {
                return null;
            }

            if ($stmt->name instanceof PhpParser\Node\Identifier) {
                return $object_id . '->' . $stmt->name;
            } elseif (isset($stmt->name->inferredType) && $stmt->name->inferredType->isSingleStringLiteral()) {
                return $object_id . '->' . $stmt->name->inferredType->getSingleStringLiteral()->value;
            } else {
                return null;
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\ClassConstFetch
            && $stmt->name instanceof PhpParser\Node\Identifier
        ) {
            /** @var string|null */
            $resolved_name = $stmt->class->getAttribute('resolvedName');

            if ($resolved_name) {
                if (($resolved_name === 'self' || $resolved_name === 'static') && $this_class_name) {
                    $resolved_name = $this_class_name;
                }

                return $resolved_name . '::' . $stmt->name;
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\MethodCall
            && $stmt->name instanceof PhpParser\Node\Identifier
            && !$stmt->args
        ) {
            $config = \Psalm\Config::getInstance();

            if ($config->memoize_method_calls) {
                $lhs_var_name = self::getArrayVarId(
                    $stmt->var,
                    $this_class_name,
                    $source
                );

                if (!$lhs_var_name) {
                    return null;
                }

                return $lhs_var_name . '->' . strtolower($stmt->name->name) . '()';
            }
        }

        return self::getVarId($stmt, $this_class_name, $source);
    }

    /**
     * @param  Type\Union   $return_type
     * @param  string|null  $self_class
     * @param  string|Type\Atomic\TNamedObject|null $static_class_type
     *
     * @return Type\Union
     */
    public static function fleshOutType(
        Codebase $codebase,
        Type\Union $return_type,
        ?string $self_class,
        $static_class_type,
        ?string $parent_class,
        bool $evaluate = true
    ) {
        $return_type = clone $return_type;

        $new_return_type_parts = [];

        foreach ($return_type->getTypes() as $return_type_part) {
            $parts = self::fleshOutAtomicType(
                $codebase,
                $return_type_part,
                $self_class,
                $static_class_type,
                $parent_class,
                $evaluate
            );

            if (is_array($parts)) {
                $new_return_type_parts = array_merge($new_return_type_parts, $parts);
            } else {
                $new_return_type_parts[] = $parts;
            }
        }

        $fleshed_out_type = new Type\Union($new_return_type_parts);

        $fleshed_out_type->from_docblock = $return_type->from_docblock;
        $fleshed_out_type->ignore_nullable_issues = $return_type->ignore_nullable_issues;
        $fleshed_out_type->ignore_falsable_issues = $return_type->ignore_falsable_issues;
        $fleshed_out_type->possibly_undefined = $return_type->possibly_undefined;
        $fleshed_out_type->by_ref = $return_type->by_ref;
        $fleshed_out_type->initialized = $return_type->initialized;
        $fleshed_out_type->had_template = $return_type->had_template;

        return $fleshed_out_type;
    }

    /**
     * @param  Type\Atomic  &$return_type
     * @param  string|null  $self_class
     * @param  string|Type\Atomic\TNamedObject|null $static_class_type
     *
     * @return Type\Atomic|array<int, Type\Atomic>
     */
    private static function fleshOutAtomicType(
        Codebase $codebase,
        Type\Atomic &$return_type,
        ?string $self_class,
        $static_class_type,
        ?string $parent_class,
        bool $evaluate = true
    ) {
        if ($return_type instanceof TNamedObject
            || $return_type instanceof TTemplateParam
        ) {
            if ($return_type->extra_types) {
                $new_intersection_types = [];

                foreach ($return_type->extra_types as &$extra_type) {
                    self::fleshOutAtomicType(
                        $codebase,
                        $extra_type,
                        $self_class,
                        $static_class_type,
                        $parent_class,
                        $evaluate
                    );

                    if ($extra_type instanceof TNamedObject && $extra_type->extra_types) {
                        $new_intersection_types = array_merge(
                            $new_intersection_types,
                            $extra_type->extra_types
                        );
                        $extra_type->extra_types = [];
                    }
                }

                if ($new_intersection_types) {
                    $return_type->extra_types = array_merge($return_type->extra_types, $new_intersection_types);
                }
            }

            if ($return_type instanceof TNamedObject) {
                $return_type_lc = strtolower($return_type->value);

                if ($return_type_lc === 'static' || $return_type_lc === '$this') {
                    if (!$static_class_type) {
                        throw new \UnexpectedValueException(
                            'Cannot handle ' . $return_type->value . ' when $static_class is empty'
                        );
                    }

                    if (is_string($static_class_type)) {
                        $return_type->value = $static_class_type;
                    } else {
                        $return_type = clone $static_class_type;
                    }
                } elseif ($return_type_lc === 'self') {
                    if (!$self_class) {
                        throw new \UnexpectedValueException(
                            'Cannot handle ' . $return_type->value . ' when $self_class is empty'
                        );
                    }

                    $return_type->value = $self_class;
                } elseif ($return_type_lc === 'parent') {
                    if (!$parent_class) {
                        throw new \UnexpectedValueException(
                            'Cannot handle ' . $return_type->value . ' when $parent_class is empty'
                        );
                    }

                    $return_type->value = $parent_class;
                } else {
                    $return_type->value = $codebase->classlikes->getUnAliasedName($return_type->value);
                }
            }
        }

        if ($return_type instanceof Type\Atomic\TScalarClassConstant) {
            if ($return_type->fq_classlike_name === 'self' && $self_class) {
                $return_type->fq_classlike_name = $self_class;
            }

            if ($evaluate && $codebase->classOrInterfaceExists($return_type->fq_classlike_name)) {
                if (strtolower($return_type->const_name) === 'class') {
                    return new Type\Atomic\TLiteralClassString($return_type->fq_classlike_name);
                }

                $class_constants = $codebase->classlikes->getConstantsForClass(
                    $return_type->fq_classlike_name,
                    \ReflectionProperty::IS_PRIVATE
                );

                if (isset($class_constants[$return_type->const_name])) {
                    $const_type = $class_constants[$return_type->const_name];

                    if ($const_type->isSingle()) {
                        $const_type = clone $const_type;

                        return array_values($const_type->getTypes())[0];
                    }
                }
            }

            return $return_type;
        }

        if ($return_type instanceof Type\Atomic\TKeyOfClassConstant
            || $return_type instanceof Type\Atomic\TValueOfClassConstant
        ) {
            if ($return_type->fq_classlike_name === 'self' && $self_class) {
                $return_type->fq_classlike_name = $self_class;
            }

            if ($evaluate && $codebase->classOrInterfaceExists($return_type->fq_classlike_name)) {
                $class_constants = $codebase->classlikes->getConstantsForClass(
                    $return_type->fq_classlike_name,
                    \ReflectionProperty::IS_PRIVATE
                );

                if (isset($class_constants[$return_type->const_name])) {
                    $const_type = $class_constants[$return_type->const_name];

                    foreach ($const_type->getTypes() as $const_type_atomic) {
                        if ($const_type_atomic instanceof Type\Atomic\ObjectLike
                            || $const_type_atomic instanceof Type\Atomic\TArray
                        ) {
                            if ($const_type_atomic instanceof Type\Atomic\ObjectLike) {
                                $const_type_atomic = $const_type_atomic->getGenericArrayType();
                            }

                            if ($return_type instanceof Type\Atomic\TKeyOfClassConstant) {
                                return array_values($const_type_atomic->type_params[0]->getTypes());
                            }

                            return array_values($const_type_atomic->type_params[1]->getTypes());
                        }
                    }
                }
            }

            return $return_type;
        }

        if ($return_type instanceof Type\Atomic\TArray || $return_type instanceof Type\Atomic\TGenericObject) {
            foreach ($return_type->type_params as &$type_param) {
                $type_param = self::fleshOutType(
                    $codebase,
                    $type_param,
                    $self_class,
                    $static_class_type,
                    $parent_class
                );
            }
        } elseif ($return_type instanceof Type\Atomic\ObjectLike) {
            foreach ($return_type->properties as &$property_type) {
                $property_type = self::fleshOutType(
                    $codebase,
                    $property_type,
                    $self_class,
                    $static_class_type,
                    $parent_class
                );
            }
        }

        if ($return_type instanceof Type\Atomic\TCallable) {
            if ($return_type->params) {
                foreach ($return_type->params as $param) {
                    if ($param->type) {
                        $param->type = self::fleshOutType(
                            $codebase,
                            $param->type,
                            $self_class,
                            $static_class_type,
                            $parent_class
                        );
                    }
                }
            }
            if ($return_type->return_type) {
                $return_type->return_type = self::fleshOutType(
                    $codebase,
                    $return_type->return_type,
                    $self_class,
                    $static_class_type,
                    $parent_class
                );
            }
        }

        return $return_type;
    }

    /**
     * @param   StatementsAnalyzer           $statements_analyzer
     * @param   PhpParser\Node\Expr\Closure $stmt
     * @param   Context                     $context
     *
     * @return  false|null
     */
    protected static function analyzeClosureUses(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Closure $stmt,
        Context $context
    ) {
        $param_names = array_map(
            function (PhpParser\Node\Param $p) : string {
                if (!$p->var instanceof PhpParser\Node\Expr\Variable
                    || !is_string($p->var->name)
                ) {
                    return '';
                }
                return $p->var->name;
            },
            $stmt->params
        );

        foreach ($stmt->uses as $use) {
            if (!is_string($use->var->name)) {
                continue;
            }

            $use_var_id = '$' . $use->var->name;

            if (in_array($use->var->name, $param_names)) {
                if (IssueBuffer::accepts(
                    new DuplicateParam(
                        'Closure use duplicates param name ' . $use_var_id,
                        new CodeLocation($statements_analyzer->getSource(), $use->var)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return false;
                }
            }

            if (!$context->hasVariable($use_var_id, $statements_analyzer)) {
                if ($use_var_id === '$argv' || $use_var_id === '$argc') {
                    continue;
                }

                if ($use->byRef) {
                    $context->vars_in_scope[$use_var_id] = Type::getMixed();
                    $context->vars_possibly_in_scope[$use_var_id] = true;

                    if (!$statements_analyzer->hasVariable($use_var_id)) {
                        $statements_analyzer->registerVariable(
                            $use_var_id,
                            new CodeLocation($statements_analyzer, $use->var),
                            null
                        );
                    }

                    return;
                }

                if (!isset($context->vars_possibly_in_scope[$use_var_id])) {
                    if ($context->check_variables) {
                        if (IssueBuffer::accepts(
                            new UndefinedVariable(
                                'Cannot find referenced variable ' . $use_var_id,
                                new CodeLocation($statements_analyzer->getSource(), $use->var)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        return null;
                    }
                }

                $first_appearance = $statements_analyzer->getFirstAppearance($use_var_id);

                if ($first_appearance) {
                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedVariable(
                            'Possibly undefined variable ' . $use_var_id . ', first seen on line ' .
                                $first_appearance->getLineNumber(),
                            new CodeLocation($statements_analyzer->getSource(), $use->var)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    continue;
                }

                if ($context->check_variables) {
                    if (IssueBuffer::accepts(
                        new UndefinedVariable(
                            'Cannot find referenced variable ' . $use_var_id,
                            new CodeLocation($statements_analyzer->getSource(), $use->var)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    continue;
                }
            } elseif ($use->byRef) {
                foreach ($context->vars_in_scope[$use_var_id]->getTypes() as $atomic_type) {
                    if ($atomic_type instanceof Type\Atomic\TLiteralInt) {
                        $context->vars_in_scope[$use_var_id]->addType(new Type\Atomic\TInt);
                    } elseif ($atomic_type instanceof Type\Atomic\TLiteralFloat) {
                        $context->vars_in_scope[$use_var_id]->addType(new Type\Atomic\TFloat);
                    } elseif ($atomic_type instanceof Type\Atomic\TLiteralString) {
                        $context->vars_in_scope[$use_var_id]->addType(new Type\Atomic\TString);
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param   StatementsAnalyzer           $statements_analyzer
     * @param   PhpParser\Node\Expr\Yield_  $stmt
     * @param   Context                     $context
     *
     * @return  false|null
     */
    protected static function analyzeYield(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Yield_ $stmt,
        Context $context
    ) {
        $doc_comment = $stmt->getDocComment();

        $var_comments = [];
        $var_comment_type = null;

        $codebase = $statements_analyzer->getCodebase();

        if ($doc_comment) {
            try {
                $var_comments = CommentAnalyzer::getTypeFromComment(
                    $doc_comment,
                    $statements_analyzer,
                    $statements_analyzer->getAliases()
                );
            } catch (DocblockParseException $e) {
                if (IssueBuffer::accepts(
                    new InvalidDocblock(
                        (string)$e->getMessage(),
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    )
                )) {
                    // fall through
                }
            }

            foreach ($var_comments as $var_comment) {
                $comment_type = ExpressionAnalyzer::fleshOutType(
                    $codebase,
                    $var_comment->type,
                    $context->self,
                    $context->self ? new Type\Atomic\TNamedObject($context->self) : null,
                    $statements_analyzer->getParentFQCLN()
                );

                if (!$var_comment->var_id) {
                    $var_comment_type = $comment_type;
                    continue;
                }

                $context->vars_in_scope[$var_comment->var_id] = $comment_type;
            }
        }

        if ($stmt->key) {
            if (self::analyze($statements_analyzer, $stmt->key, $context) === false) {
                return false;
            }
        }

        if ($stmt->value) {
            if (self::analyze($statements_analyzer, $stmt->value, $context) === false) {
                return false;
            }

            if ($var_comment_type) {
                $stmt->inferredType = $var_comment_type;
            } elseif (isset($stmt->value->inferredType)) {
                $stmt->inferredType = $stmt->value->inferredType;
            } else {
                $stmt->inferredType = Type::getMixed();
            }
        } else {
            $stmt->inferredType = Type::getNull();
        }

        $source = $statements_analyzer->getSource();

        if ($source instanceof FunctionLikeAnalyzer
            && !($source->getSource() instanceof TraitAnalyzer)
        ) {
            $source->examineParamTypes($statements_analyzer, $context, $codebase, $stmt);

            $storage = $source->getFunctionLikeStorage($statements_analyzer);

            if ($storage->return_type) {
                foreach ($storage->return_type->getTypes() as $atomic_return_type) {
                    if ($atomic_return_type instanceof Type\Atomic\TGenericObject
                        && $atomic_return_type->value === 'Generator'
                    ) {
                        if (!$atomic_return_type->type_params[2]->isMixed()
                            && !$atomic_return_type->type_params[2]->isVoid()
                        ) {
                            $stmt->inferredType = clone $atomic_return_type->type_params[2];
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param   StatementsAnalyzer               $statements_analyzer
     * @param   PhpParser\Node\Expr\YieldFrom   $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    protected static function analyzeYieldFrom(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\YieldFrom $stmt,
        Context $context
    ) {
        if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            return false;
        }

        if (isset($stmt->expr->inferredType)) {
            $yield_from_type = null;

            foreach ($stmt->expr->inferredType->getTypes() as $atomic_type) {
                if ($yield_from_type === null) {
                    if ($atomic_type instanceof Type\Atomic\TGenericObject
                        && strtolower($atomic_type->value) === 'generator'
                        && isset($atomic_type->type_params[3])
                    ) {
                        $yield_from_type = clone $atomic_type->type_params[3];
                    } elseif ($atomic_type instanceof Type\Atomic\TArray) {
                        $yield_from_type = clone $atomic_type->type_params[1];
                    } elseif ($atomic_type instanceof Type\Atomic\ObjectLike) {
                        $yield_from_type = $atomic_type->getGenericValueType();
                    }
                } else {
                    $yield_from_type = Type::getMixed();
                }
            }

            // this should be whatever the generator above returns, but *not* the return type
            $stmt->inferredType = $yield_from_type ?: Type::getMixed();
        }

        return null;
    }

    /**
     * @param   StatementsAnalyzer               $statements_analyzer
     * @param   PhpParser\Node\Expr\BooleanNot  $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    protected static function analyzeBooleanNot(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\BooleanNot $stmt,
        Context $context
    ) {
        $stmt->inferredType = Type::getBool();

        $inside_negation = $context->inside_negation;

        $context->inside_negation = !$inside_negation;

        if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            return false;
        }

        $context->inside_negation = $inside_negation;
    }

    /**
     * @param   StatementsAnalyzer           $statements_analyzer
     * @param   PhpParser\Node\Expr\Empty_  $stmt
     * @param   Context                     $context
     *
     * @return  void
     */
    protected static function analyzeEmpty(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Empty_ $stmt,
        Context $context
    ) {
        self::analyzeIssetVar($statements_analyzer, $stmt->expr, $context);
        $stmt->inferredType = Type::getBool();
    }

    /**
     * @param   StatementsAnalyzer               $statements_analyzer
     * @param   PhpParser\Node\Scalar\Encapsed  $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    protected static function analyzeEncapsulatedString(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Scalar\Encapsed $stmt,
        Context $context
    ) {
        /** @var PhpParser\Node\Expr $part */
        foreach ($stmt->parts as $part) {
            if (self::analyze($statements_analyzer, $part, $context) === false) {
                return false;
            }

            if (isset($part->inferredType)) {
                self::castStringAttempt($statements_analyzer, $part);
            }
        }

        $stmt->inferredType = Type::getString();

        return null;
    }

    /**
     * @return  void
     */
    private static function castStringAttempt(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt
    ) {
        if (!isset($stmt->inferredType)) {
            return;
        }

        $has_valid_cast = false;
        $invalid_casts = [];

        foreach ($stmt->inferredType->getTypes() as $atomic_type) {
            if (!$atomic_type instanceof TMixed
                && !$atomic_type instanceof Type\Atomic\TResource
                && !$atomic_type instanceof TNull
                && !TypeAnalyzer::isAtomicContainedBy(
                    $statements_analyzer->getCodebase(),
                    $atomic_type,
                    new TString(),
                    false,
                    true,
                    $has_scalar_match
                )
                && !$has_scalar_match
            ) {
                $invalid_casts[] = $atomic_type->getId();
            } else {
                $has_valid_cast = true;
            }
        }

        if ($invalid_casts) {
            if ($has_valid_cast) {
                if (IssueBuffer::accepts(
                    new PossiblyInvalidCast(
                        $invalid_casts[0] . ' cannot be cast to string',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            } else {
                if (IssueBuffer::accepts(
                    new InvalidCast(
                        $invalid_casts[0] . ' cannot be cast to string',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        }
    }

    /**
     * @param  StatementsAnalyzer          $statements_analyzer
     * @param  PhpParser\Node\Expr\Isset_ $stmt
     * @param  Context                    $context
     *
     * @return void
     */
    protected static function analyzeIsset(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Isset_ $stmt,
        Context $context
    ) {
        foreach ($stmt->vars as $isset_var) {
            if ($isset_var instanceof PhpParser\Node\Expr\PropertyFetch
                && $isset_var->var instanceof PhpParser\Node\Expr\Variable
                && $isset_var->var->name === 'this'
                && $isset_var->name instanceof PhpParser\Node\Identifier
            ) {
                $var_id = '$this->' . $isset_var->name->name;

                if (!isset($context->vars_in_scope[$var_id])) {
                    $context->vars_in_scope[$var_id] = Type::getMixed();
                    $context->vars_possibly_in_scope[$var_id] = true;
                }
            }

            self::analyzeIssetVar($statements_analyzer, $isset_var, $context);
        }

        $stmt->inferredType = Type::getBool();
    }

    /**
     * @param  StatementsAnalyzer   $statements_analyzer
     * @param  PhpParser\Node\Expr $stmt
     * @param  Context             $context
     *
     * @return false|null
     */
    protected static function analyzeIssetVar(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        Context $context
    ) {

        $context->inside_isset = true;

        if (self::analyze($statements_analyzer, $stmt, $context) === false) {
            return false;
        }

        $context->inside_isset = false;
    }

    /**
     * @param  StatementsAnalyzer            $statements_analyzer
     * @param  PhpParser\Node\Expr\Clone_   $stmt
     * @param  Context                      $context
     *
     * @return false|null
     */
    protected static function analyzeClone(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Clone_ $stmt,
        Context $context
    ) {
        if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            return false;
        }

        if (isset($stmt->expr->inferredType)) {
            $clone_type = $stmt->expr->inferredType;

            foreach ($clone_type->getTypes() as $clone_type_part) {
                if (!$clone_type_part instanceof TNamedObject
                    && !$clone_type_part instanceof TObject
                    && !$clone_type_part instanceof TMixed
                    && !$clone_type_part instanceof TTemplateParam
                ) {
                    if ($clone_type_part instanceof Type\Atomic\TFalse
                        && $clone_type->ignore_falsable_issues
                    ) {
                        continue;
                    }

                    if ($clone_type_part instanceof Type\Atomic\TNull
                        && $clone_type->ignore_nullable_issues
                    ) {
                        continue;
                    }

                    if (IssueBuffer::accepts(
                        new InvalidClone(
                            'Cannot clone ' . $clone_type_part,
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
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
        return in_array(strtolower($fq_class_name), Config::getInstance()->getMockClasses(), true);
    }
}
