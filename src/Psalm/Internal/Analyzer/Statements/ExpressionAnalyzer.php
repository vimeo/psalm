<?php
namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ClassAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ClosureAnalyzer;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ArrayAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\AssertionFinder;
use Psalm\Internal\Analyzer\Statements\Expression\AssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\BinaryOpAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\FunctionCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\MethodCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\NewAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\StaticCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ArrayFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ClassConstFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ConstFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\PropertyFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\VariableFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\IncludeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\TernaryAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\DocblockParseException;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\FileSource;
use Psalm\Issue\DuplicateParam;
use Psalm\Issue\ForbiddenCode;
use Psalm\Issue\ImpurePropertyAssignment;
use Psalm\Issue\InvalidCast;
use Psalm\Issue\InvalidClone;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\InvalidOperand;
use Psalm\Issue\PossiblyInvalidCast;
use Psalm\Issue\PossiblyInvalidOperand;
use Psalm\Issue\PossiblyUndefinedVariable;
use Psalm\Issue\UndefinedConstant;
use Psalm\Issue\UndefinedVariable;
use Psalm\Issue\UnnecessaryVarAnnotation;
use Psalm\Issue\UnrecognizedExpression;
use Psalm\IssueBuffer;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TString;
use Psalm\Internal\Type\TypeCombination;
use function strpos;
use function is_string;
use function in_array;
use function strtolower;
use function get_class;
use function count;
use function implode;
use function is_array;
use function array_merge;
use function array_values;
use function array_map;
use function current;

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
        Context $global_context = null,
        bool $from_stmt = false
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

            if (!$from_stmt) {
                $statements_analyzer->node_data->setType($stmt, $assignment_type);
            }
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
            $statements_analyzer->node_data->setType($stmt, Type::getString($stmt->value));
        } elseif ($stmt instanceof PhpParser\Node\Scalar\EncapsedStringPart) {
            // do nothing
        } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst) {
            if ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Line) {
                $statements_analyzer->node_data->setType($stmt, Type::getInt());
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

                    $statements_analyzer->node_data->setType($stmt, Type::getClassString());
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

                    $statements_analyzer->node_data->setType($stmt, Type::getLiteralClassString($context->self));
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

                $statements_analyzer->node_data->setType($stmt, Type::getString($namespace));
            } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Method
                || $stmt instanceof PhpParser\Node\Scalar\MagicConst\Function_
            ) {
                $source = $statements_analyzer->getSource();
                if ($source instanceof FunctionLikeAnalyzer) {
                    $statements_analyzer->node_data->setType($stmt, Type::getString($source->getId()));
                } else {
                    $statements_analyzer->node_data->setType($stmt, new Type\Union([new Type\Atomic\TCallableString]));
                }
            } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst\File
                || $stmt instanceof PhpParser\Node\Scalar\MagicConst\Dir
                || $stmt instanceof PhpParser\Node\Scalar\MagicConst\Trait_
            ) {
                $statements_analyzer->node_data->setType($stmt, Type::getString());
            }
        } elseif ($stmt instanceof PhpParser\Node\Scalar\LNumber) {
            $statements_analyzer->node_data->setType($stmt, Type::getInt(false, $stmt->value));
        } elseif ($stmt instanceof PhpParser\Node\Scalar\DNumber) {
            $statements_analyzer->node_data->setType($stmt, Type::getFloat($stmt->value));
        } elseif ($stmt instanceof PhpParser\Node\Expr\UnaryMinus ||
            $stmt instanceof PhpParser\Node\Expr\UnaryPlus
        ) {
            if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            if (!($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr))) {
                $statements_analyzer->node_data->setType($stmt, new Type\Union([new TInt, new TFloat]));
            } elseif ($stmt_expr_type->isMixed()) {
                $statements_analyzer->node_data->setType($stmt, Type::getMixed());
            } else {
                $acceptable_types = [];

                foreach ($stmt_expr_type->getAtomicTypes() as $type_part) {
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

                $statements_analyzer->node_data->setType($stmt, new Type\Union($acceptable_types));
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Isset_) {
            self::analyzeIsset($statements_analyzer, $stmt, $context);
            $statements_analyzer->node_data->setType($stmt, Type::getBool());
        } elseif ($stmt instanceof PhpParser\Node\Expr\ClassConstFetch) {
            if (ClassConstFetchAnalyzer::analyze($statements_analyzer, $stmt, $context) === false) {
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

            if (!($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr))) {
                $statements_analyzer->node_data->setType($stmt, new Type\Union([new TInt(), new TString()]));
            } elseif ($stmt_expr_type->isMixed()) {
                $statements_analyzer->node_data->setType($stmt, Type::getMixed());
            } else {
                $acceptable_types = [];
                $unacceptable_type = null;
                $has_valid_operand = false;

                foreach ($stmt_expr_type->getAtomicTypes() as $type_string => $type_part) {
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

                        $stmt_expr_type->removeType($type_string);
                        $stmt_expr_type->addType($type_part);

                        $acceptable_types[] = $type_part;
                        $has_valid_operand = true;
                    } elseif (!$unacceptable_type) {
                        $unacceptable_type = $type_part;
                    }
                }

                if ($unacceptable_type || !$acceptable_types) {
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

                    $statements_analyzer->node_data->setType($stmt, Type::getMixed());
                } else {
                    $statements_analyzer->node_data->setType($stmt, new Type\Union($acceptable_types));
                }
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            if (BinaryOpAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context,
                0,
                $from_stmt
            ) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\PostInc ||
            $stmt instanceof PhpParser\Node\Expr\PostDec ||
            $stmt instanceof PhpParser\Node\Expr\PreInc ||
            $stmt instanceof PhpParser\Node\Expr\PreDec
        ) {
            $was_inside_assignment = $context->inside_assignment;
            $context->inside_assignment = true;

            if (self::analyze($statements_analyzer, $stmt->var, $context) === false) {
                if (!$was_inside_assignment) {
                    $context->inside_assignment = false;
                }
                return false;
            }

            if (!$was_inside_assignment) {
                $context->inside_assignment = false;
            }

            if ($stmt_var_type = $statements_analyzer->node_data->getType($stmt->var)) {
                $return_type = null;

                $fake_right_expr = new PhpParser\Node\Scalar\LNumber(1, $stmt->getAttributes());
                $statements_analyzer->node_data->setType($fake_right_expr, Type::getInt());

                BinaryOpAnalyzer::analyzeNonDivArithmeticOp(
                    $statements_analyzer,
                    $statements_analyzer->node_data,
                    $stmt->var,
                    $fake_right_expr,
                    $stmt,
                    $return_type,
                    $context
                );

                $stmt_type = clone $stmt_var_type;

                $statements_analyzer->node_data->setType($stmt, $stmt_type);
                $stmt_type->from_calculation = true;

                foreach ($stmt_type->getAtomicTypes() as $atomic_type) {
                    if ($atomic_type instanceof Type\Atomic\TLiteralInt) {
                        $stmt_type->addType(new Type\Atomic\TInt);
                    } elseif ($atomic_type instanceof Type\Atomic\TLiteralFloat) {
                        $stmt_type->addType(new Type\Atomic\TFloat);
                    }
                }

                $var_id = self::getArrayVarId($stmt->var, null);

                if ($var_id && $context->mutation_free && strpos($var_id, '->')) {
                    if (IssueBuffer::accepts(
                        new ImpurePropertyAssignment(
                            'Cannot assign to a property from a mutation-free context',
                            new CodeLocation($statements_analyzer, $stmt->var)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if ($var_id && isset($context->vars_in_scope[$var_id])) {
                    $context->vars_in_scope[$var_id] = $stmt_type;

                    if ($codebase->find_unused_variables && $stmt->var instanceof PhpParser\Node\Expr\Variable) {
                        $location = new CodeLocation($statements_analyzer, $stmt->var);
                        $context->assigned_var_ids[$var_id] = true;
                        $context->possibly_assigned_var_ids[$var_id] = true;
                        $statements_analyzer->registerVariableAssignment(
                            $var_id,
                            $location
                        );
                        $context->unreferenced_vars[$var_id] = [$location->getHash() => $location];
                    }

                    // removes dependent vars from $context
                    $context->removeDescendents(
                        $var_id,
                        $context->vars_in_scope[$var_id],
                        $return_type,
                        $statements_analyzer
                    );
                }
            } else {
                $statements_analyzer->node_data->setType($stmt, Type::getMixed());
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
        } elseif ($stmt instanceof PhpParser\Node\Expr\Closure
            || $stmt instanceof PhpParser\Node\Expr\ArrowFunction
        ) {
            $closure_analyzer = new ClosureAnalyzer($stmt, $statements_analyzer);

            if ($stmt instanceof PhpParser\Node\Expr\Closure
                && self::analyzeClosureUses($statements_analyzer, $stmt, $context) === false
            ) {
                return false;
            }

            $use_context = new Context($context->self);
            $use_context->mutation_free = $context->mutation_free;
            $use_context->external_mutation_free = $context->external_mutation_free;
            $use_context->pure = $context->pure;

            if (!$statements_analyzer->isStatic()) {
                if ($context->collect_mutations &&
                    $context->self &&
                    $codebase->classExtends(
                        $context->self,
                        (string)$statements_analyzer->getFQCLN()
                    )
                ) {
                    /** @psalm-suppress PossiblyUndefinedStringArrayOffset */
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

            if ($context->self) {
                $self_class_storage = $codebase->classlike_storage_provider->get($context->self);

                ClassAnalyzer::addContextProperties(
                    $statements_analyzer,
                    $self_class_storage,
                    $use_context,
                    $context->self,
                    $statements_analyzer->getParentFQCLN()
                );
            }

            foreach ($context->vars_possibly_in_scope as $var => $_) {
                if (strpos($var, '$this->') === 0) {
                    $use_context->vars_possibly_in_scope[$var] = true;
                }
            }

            $byref_uses = [];

            if ($stmt instanceof PhpParser\Node\Expr\Closure) {
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
            } else {
                $traverser = new PhpParser\NodeTraverser;

                $short_closure_visitor = new \Psalm\Internal\PhpVisitor\ShortClosureVisitor();

                $traverser->addVisitor($short_closure_visitor);
                $traverser->traverse($stmt->getStmts());

                foreach ($short_closure_visitor->getUsedVariables() as $use_var_id => $_) {
                    $use_context->vars_in_scope[$use_var_id] =
                        $context->hasVariable($use_var_id, $statements_analyzer)
                        ? clone $context->vars_in_scope[$use_var_id]
                        : Type::getMixed();

                    $use_context->vars_possibly_in_scope[$use_var_id] = true;
                }
            }

            $use_context->calling_method_id = $context->calling_method_id;

            $closure_analyzer->analyze($use_context, $statements_analyzer->node_data, $context, false, $byref_uses);

            if (!$statements_analyzer->node_data->getType($stmt)) {
                $statements_analyzer->node_data->setType($stmt, Type::getClosure());
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

            $as_int = true;
            $maybe_type = $statements_analyzer->node_data->getType($stmt->expr);

            if (null !== $maybe_type) {
                $maybe = $maybe_type->getAtomicTypes();

                if (1 === count($maybe) && current($maybe) instanceof Type\Atomic\TBool) {
                    $as_int = false;
                    $statements_analyzer->node_data->setType($stmt, new Type\Union([
                        new Type\Atomic\TLiteralInt(0),
                        new Type\Atomic\TLiteralInt(1),
                    ]));
                }
            }

            if ($as_int) {
                $statements_analyzer->node_data->setType($stmt, Type::getInt());
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Double) {
            if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $statements_analyzer->node_data->setType($stmt, Type::getFloat());
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Bool_) {
            if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $statements_analyzer->node_data->setType($stmt, Type::getBool());
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\String_) {
            if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            if ($statements_analyzer->node_data->getType($stmt->expr)) {
                $stmt_type = self::castStringAttempt($statements_analyzer, $stmt->expr, true);
            } else {
                $stmt_type = Type::getString();
            }

            $statements_analyzer->node_data->setType($stmt, $stmt_type);

            if (($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr))
                && $stmt_expr_type->tainted
            ) {
                $stmt_type->tainted = $stmt_expr_type->tainted;
                $stmt_type->sources = $stmt_expr_type->sources;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Object_) {
            if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $statements_analyzer->node_data->setType($stmt, new Type\Union([new TNamedObject('stdClass')]));
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Array_) {
            if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $permissible_atomic_types = [];
            $all_permissible = false;

            if ($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr)) {
                $all_permissible = true;

                foreach ($stmt_expr_type->getAtomicTypes() as $type) {
                    if ($type instanceof Scalar) {
                        $permissible_atomic_types[] = new ObjectLike([new Type\Union([$type])]);
                    } elseif ($type instanceof TNull) {
                        $permissible_atomic_types[] = new TArray([Type::getEmpty(), Type::getEmpty()]);
                    } elseif ($type instanceof TArray
                        || $type instanceof TList
                        || $type instanceof ObjectLike
                    ) {
                        $permissible_atomic_types[] = clone $type;
                    } else {
                        $all_permissible = false;
                        break;
                    }
                }
            }

            if ($permissible_atomic_types && $all_permissible) {
                $statements_analyzer->node_data->setType(
                    $stmt,
                    TypeCombination::combineTypes($permissible_atomic_types)
                );
            } else {
                $statements_analyzer->node_data->setType($stmt, Type::getArray());
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Unset_) {
            if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $statements_analyzer->node_data->setType($stmt, Type::getNull());
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

                    if ($codebase->store_node_types
                        && $fq_class_name
                        && !$context->collect_initializations
                        && !$context->collect_mutations
                    ) {
                        $codebase->analyzer->addNodeReference(
                            $statements_analyzer->getFilePath(),
                            $stmt->class,
                            $codebase->classlikes->classOrInterfaceExists($fq_class_name)
                                ? $fq_class_name
                                : '*' . implode('\\', $stmt->class->parts)
                        );
                    }

                    if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                        $statements_analyzer,
                        $fq_class_name,
                        new CodeLocation($statements_analyzer->getSource(), $stmt->class),
                        $context->self,
                        $context->calling_method_id,
                        $statements_analyzer->getSuppressedIssues(),
                        false
                    ) === false) {
                        return false;
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

            $statements_analyzer->node_data->setType($stmt, Type::getBool());
        } elseif ($stmt instanceof PhpParser\Node\Expr\Exit_) {
            if ($stmt->expr) {
                $context->inside_call = true;
                if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                    return false;
                }
                $context->inside_call = false;
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
            $context->error_suppressing = true;
            if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }
            $context->error_suppressing = false;

            $expr_type = $statements_analyzer->node_data->getType($stmt->expr);

            if ($expr_type) {
                $statements_analyzer->node_data->setType($stmt, $expr_type);
            }
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
            $was_inside_call = $context->inside_call;
            $context->inside_call = true;
            if (self::analyzePrint($statements_analyzer, $stmt, $context) === false) {
                return false;
            }
            $context->inside_call = $was_inside_call;
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
            $assertions = $statements_analyzer->node_data->getAssertions($stmt);

            if ($assertions === null) {
                AssertionFinder::scrapeAssertions(
                    $stmt,
                    $context->self,
                    $statements_analyzer,
                    $codebase
                );
            }
        }

        $plugin_classes = $codebase->config->after_expression_checks;

        if ($plugin_classes) {
            $file_manipulations = [];

            foreach ($plugin_classes as $plugin_fq_class_name) {
                if ($plugin_fq_class_name::afterExpressionAnalysis(
                    $stmt,
                    $context,
                    $statements_analyzer,
                    $codebase,
                    $file_manipulations
                ) === false) {
                    return false;
                }
            }

            if ($file_manipulations) {
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
        if ($stmt instanceof PhpParser\Node\Expr\PropertyFetch && $stmt->name instanceof PhpParser\Node\Identifier) {
            $prop_name = $stmt->name->name;

            Expression\Assignment\PropertyAssignmentAnalyzer::analyzeInstance(
                $statements_analyzer,
                $stmt,
                $prop_name,
                null,
                $by_ref_out_type,
                $context
            );

            return;
        }

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

                    $codebase = $statements_analyzer->getCodebase();

                    if ($codebase->find_unused_variables) {
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

                    if (!($stmt_type = $statements_analyzer->node_data->getType($stmt))
                        || $stmt_type->isEmpty()
                    ) {
                        $statements_analyzer->node_data->setType($stmt, clone $by_ref_type);
                    }

                    return;
                }
            }

            $context->assigned_var_ids[$var_id] = true;

            $context->vars_in_scope[$var_id] = $by_ref_out_type;

            if (!($stmt_type = $statements_analyzer->node_data->getType($stmt)) || $stmt_type->isEmpty()) {
                $statements_analyzer->node_data->setType($stmt, clone $by_ref_type);
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
                } elseif ($stmt->dim instanceof PhpParser\Node\Expr\PropertyFetch) {
                    $object_id = self::getArrayVarId($stmt->dim->var, $this_class_name, $source);

                    if ($object_id && $stmt->dim->name instanceof PhpParser\Node\Identifier) {
                        $offset = $object_id . '->' . $stmt->dim->name;
                    }
                } elseif ($stmt->dim instanceof PhpParser\Node\Expr\ClassConstFetch
                    && $stmt->dim->name instanceof PhpParser\Node\Identifier
                    && $stmt->dim->class instanceof PhpParser\Node\Name
                    && $stmt->dim->class->parts[0] === 'static'
                ) {
                    $offset = 'static::' . $stmt->dim->name;
                } elseif ($stmt->dim
                    && $source instanceof StatementsAnalyzer
                    && ($stmt_dim_type = $source->node_data->getType($stmt->dim))
                    && (!$stmt->dim instanceof PhpParser\Node\Expr\ClassConstFetch
                        || !$stmt->dim->name instanceof PhpParser\Node\Identifier
                        || $stmt->dim->name->name !== 'class'
                    )
                ) {
                    if ($stmt_dim_type->isSingleStringLiteral()) {
                        $offset = '\'' . $stmt_dim_type->getSingleStringLiteral()->value . '\'';
                    } elseif ($stmt_dim_type->isSingleIntLiteral()) {
                        $offset = $stmt_dim_type->getSingleIntLiteral()->value;
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

                return $offset !== null ? $root_var_id . '[' . $offset . ']' : null;
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\PropertyFetch) {
            $object_id = self::getArrayVarId($stmt->var, $this_class_name, $source);

            if (!$object_id) {
                return null;
            }

            if ($stmt->name instanceof PhpParser\Node\Identifier) {
                return $object_id . '->' . $stmt->name;
            } elseif ($source instanceof StatementsAnalyzer
                && ($stmt_name_type = $source->node_data->getType($stmt->name))
                && $stmt_name_type->isSingleStringLiteral()
            ) {
                return $object_id . '->' . $stmt_name_type->getSingleStringLiteral()->value;
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

            if ($config->memoize_method_calls || isset($stmt->pure)) {
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
                $context->remove($use_var_id);

                $context->vars_in_scope[$use_var_id] = Type::getMixed();
            }
        }

        return null;
    }

    /**
     * @param   StatementsAnalyzer           $statements_analyzer
     * @param   PhpParser\Node\Expr\Print_  $stmt
     * @param   Context                     $context
     *
     * @return  false|null
     */
    protected static function analyzePrint(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Print_ $stmt,
        Context $context
    ) {
        $codebase = $statements_analyzer->getCodebase();

        if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            return false;
        }

        if ($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr)) {
            if (CallAnalyzer::checkFunctionArgumentType(
                $statements_analyzer,
                $stmt_expr_type,
                Type::getString(),
                null,
                'print',
                0,
                new CodeLocation($statements_analyzer->getSource(), $stmt->expr),
                $stmt->expr,
                $context,
                new FunctionLikeParameter('var', false),
                false,
                null,
                false,
                true,
                new CodeLocation($statements_analyzer->getSource(), $stmt)
            ) === false) {
                return false;
            }
        }

        if (isset($codebase->config->forbidden_functions['print'])) {
            if (IssueBuffer::accepts(
                new ForbiddenCode(
                    'You have forbidden the use of print',
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // continue
            }
        }

        $statements_analyzer->node_data->setType($stmt, Type::getInt(false, 1));

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
                if (!$var_comment->type) {
                    continue;
                }

                $comment_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                    $codebase,
                    $var_comment->type,
                    $context->self,
                    $context->self ? new Type\Atomic\TNamedObject($context->self) : null,
                    $statements_analyzer->getParentFQCLN()
                );

                $type_location = null;

                if ($var_comment->type_start
                    && $var_comment->type_end
                    && $var_comment->line_number
                ) {
                    $type_location = new CodeLocation\DocblockTypeLocation(
                        $statements_analyzer,
                        $var_comment->type_start,
                        $var_comment->type_end,
                        $var_comment->line_number
                    );
                }

                if (!$var_comment->var_id) {
                    $var_comment_type = $comment_type;
                    continue;
                }

                if ($codebase->find_unused_variables
                    && $type_location
                    && isset($context->vars_in_scope[$var_comment->var_id])
                    && $context->vars_in_scope[$var_comment->var_id]->getId() === $comment_type->getId()
                ) {
                    $project_analyzer = $statements_analyzer->getProjectAnalyzer();

                    if ($codebase->alter_code
                        && isset($project_analyzer->getIssuesToFix()['UnnecessaryVarAnnotation'])
                    ) {
                        FileManipulationBuffer::addVarAnnotationToRemove($type_location);
                    } elseif (IssueBuffer::accepts(
                        new UnnecessaryVarAnnotation(
                            'The @var annotation for ' . $var_comment->var_id . ' is unnecessary',
                            $type_location
                        ),
                        [],
                        true
                    )) {
                        // fall through
                    }
                }

                $context->vars_in_scope[$var_comment->var_id] = $comment_type;
            }
        }

        if ($stmt->key) {
            $context->inside_call = true;
            if (self::analyze($statements_analyzer, $stmt->key, $context) === false) {
                return false;
            }
            $context->inside_call = false;
        }

        if ($stmt->value) {
            $context->inside_call = true;
            if (self::analyze($statements_analyzer, $stmt->value, $context) === false) {
                return false;
            }
            $context->inside_call = false;

            if ($var_comment_type) {
                $expression_type = clone $var_comment_type;
            } elseif ($stmt_var_type = $statements_analyzer->node_data->getType($stmt->value)) {
                $expression_type = clone $stmt_var_type;
            } else {
                $expression_type = Type::getMixed();
            }
        } else {
            $expression_type = Type::getEmpty();
        }

        foreach ($expression_type->getAtomicTypes() as $expression_atomic_type) {
            if ($expression_atomic_type instanceof Type\Atomic\TNamedObject) {
                $classlike_storage = $codebase->classlike_storage_provider->get($expression_atomic_type->value);

                if ($classlike_storage->yield) {
                    if ($expression_atomic_type instanceof Type\Atomic\TGenericObject) {
                        $yield_type = PropertyFetchAnalyzer::localizePropertyType(
                            $codebase,
                            clone $classlike_storage->yield,
                            $expression_atomic_type,
                            $classlike_storage,
                            $classlike_storage
                        );
                    } else {
                        $yield_type = Type::getMixed();
                    }

                    $expression_type->substitute($expression_type, $yield_type);
                }
            }
        }

        $statements_analyzer->node_data->setType($stmt, $expression_type);

        $source = $statements_analyzer->getSource();

        if ($source instanceof FunctionLikeAnalyzer
            && !($source->getSource() instanceof TraitAnalyzer)
        ) {
            $source->examineParamTypes($statements_analyzer, $context, $codebase, $stmt);

            $storage = $source->getFunctionLikeStorage($statements_analyzer);

            if ($storage->return_type) {
                foreach ($storage->return_type->getAtomicTypes() as $atomic_return_type) {
                    if ($atomic_return_type instanceof Type\Atomic\TNamedObject
                        && $atomic_return_type->value === 'Generator'
                    ) {
                        if ($atomic_return_type instanceof Type\Atomic\TGenericObject) {
                            if (!$atomic_return_type->type_params[2]->isVoid()) {
                                $statements_analyzer->node_data->setType(
                                    $stmt,
                                    Type::combineUnionTypes(
                                        clone $atomic_return_type->type_params[2],
                                        $expression_type,
                                        $codebase
                                    )
                                );
                            }
                        } else {
                            $statements_analyzer->node_data->setType(
                                $stmt,
                                Type::combineUnionTypes(
                                    Type::getMixed(),
                                    $expression_type
                                )
                            );
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
        $was_inside_call = $context->inside_call;

        $context->inside_call = true;

        if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            $context->inside_call = $was_inside_call;

            return false;
        }

        if ($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr)) {
            $yield_from_type = null;

            foreach ($stmt_expr_type->getAtomicTypes() as $atomic_type) {
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
            $statements_analyzer->node_data->setType($stmt, $yield_from_type ?: Type::getMixed());
        }

        $context->inside_call = $was_inside_call;

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
        $statements_analyzer->node_data->setType($stmt, Type::getBool());

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

        if (($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr))
            && $stmt_expr_type->hasBool()
            && $stmt_expr_type->isSingle()
            && !$stmt_expr_type->from_docblock
        ) {
            if (IssueBuffer::accepts(
                new \Psalm\Issue\InvalidArgument(
                    'Calling empty on a boolean value is almost certainly unintended',
                    new CodeLocation($statements_analyzer->getSource(), $stmt->expr),
                    'empty'
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        $statements_analyzer->node_data->setType($stmt, Type::getBool());
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
        foreach ($stmt->parts as $part) {
            if (self::analyze($statements_analyzer, $part, $context) === false) {
                return false;
            }

            if ($statements_analyzer->node_data->getType($part)) {
                self::castStringAttempt($statements_analyzer, $part);
            }
        }

        $statements_analyzer->node_data->setType($stmt, Type::getString());

        return null;
    }

    private static function castStringAttempt(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        bool $explicit_cast = false
    ) : Type\Union {
        $codebase = $statements_analyzer->getCodebase();

        if (!($stmt_type = $statements_analyzer->node_data->getType($stmt))) {
            return Type::getString();
        }

        $invalid_casts = [];
        $valid_strings = [];
        $castable_types = [];

        $atomic_types = $stmt_type->getAtomicTypes();

        while ($atomic_types) {
            $atomic_type = \array_pop($atomic_types);

            if ($atomic_type instanceof TString) {
                $valid_strings[] = $atomic_type;
                continue;
            }

            if ($atomic_type instanceof TMixed
                || $atomic_type instanceof Type\Atomic\TResource
                || $atomic_type instanceof Type\Atomic\TNull
                || $atomic_type instanceof Type\Atomic\Scalar
            ) {
                $castable_types[] = new TString();
                continue;
            }

            if ($atomic_type instanceof TNamedObject
                || $atomic_type instanceof Type\Atomic\TObjectWithProperties
            ) {
                $intersection_types = [$atomic_type];

                if ($atomic_type->extra_types) {
                    $intersection_types = array_merge($intersection_types, $atomic_type->extra_types);
                }

                foreach ($intersection_types as $intersection_type) {
                    if ($intersection_type instanceof TNamedObject
                        && $codebase->methods->methodExists(
                            new \Psalm\Internal\MethodIdentifier(
                                $intersection_type->value,
                                '__tostring'
                            )
                        )
                    ) {
                        $return_type = $codebase->methods->getMethodReturnType(
                            new \Psalm\Internal\MethodIdentifier(
                                $intersection_type->value,
                                '__tostring'
                            ),
                            $self_class
                        );

                        if ($return_type) {
                            $castable_types = array_merge(
                                $castable_types,
                                array_values($return_type->getAtomicTypes())
                            );
                        } else {
                            $castable_types[] = new TString();
                        }

                        continue 2;
                    }

                    if ($intersection_type instanceof Type\Atomic\TObjectWithProperties
                        && isset($intersection_type->methods['__toString'])
                    ) {
                        $castable_types[] = new TString();

                        continue 2;
                    }
                }
            }

            if ($atomic_type instanceof Type\Atomic\TTemplateParam) {
                $atomic_types = array_merge($atomic_types, $atomic_type->as->getAtomicTypes());

                continue;
            }

            $invalid_casts[] = $atomic_type->getId();
        }

        if ($invalid_casts) {
            if ($valid_strings || $castable_types) {
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
        } elseif ($explicit_cast && !$castable_types) {
            // todo: emit error here
        }

        $valid_types = array_merge($valid_strings, $castable_types);

        if (!$valid_types) {
            return Type::getString();
        }

        return \Psalm\Internal\Type\TypeCombination::combineTypes(
            $valid_types,
            $codebase
        );
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

        $statements_analyzer->node_data->setType($stmt, Type::getBool());
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

        $stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr);

        if ($stmt_expr_type) {
            $clone_type = $stmt_expr_type;

            $immutable_cloned = false;

            foreach ($clone_type->getAtomicTypes() as $clone_type_part) {
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

                if ($clone_type_part instanceof TNamedObject) {
                    $immutable_cloned = true;
                }
            }

            $statements_analyzer->node_data->setType($stmt, $stmt_expr_type);

            if ($immutable_cloned) {
                $stmt_expr_type = clone $stmt_expr_type;
                $statements_analyzer->node_data->setType($stmt, $stmt_expr_type);
                $stmt_expr_type->reference_free = true;
                $stmt_expr_type->allow_mutations = true;
            }
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
