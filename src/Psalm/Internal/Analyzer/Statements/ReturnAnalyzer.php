<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\CodeLocation\DocblockTypeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Exception\DocblockParseException;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeNameOptions;
use Psalm\Internal\Analyzer\ClosureAnalyzer;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Analyzer\ConstructorExits;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ClassTemplateParamCollector;
use Psalm\Internal\Analyzer\Statements\Expression\Call\Method\MethodCallPurityAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Issue\FalsableReturnStatement;
use Psalm\Issue\HiddenGeneratorReturn;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\InvalidReturnStatement;
use Psalm\Issue\LessSpecificReturnStatement;
use Psalm\Issue\MixedReturnStatement;
use Psalm\Issue\MixedReturnTypeCoercion;
use Psalm\Issue\NoValue;
use Psalm\Issue\NonVariableReferenceReturn;
use Psalm\Issue\NullableReturnStatement;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function array_values;
use function count;
use function explode;
use function implode;
use function reset;
use function strtolower;

/**
 * @internal
 */
final class ReturnAnalyzer
{
    /**
     * Inside `if ($a === 15)` the variable `$a` of type `T` is `T as 15`: returning the literal `15`
     * returns the value of `$a`, which is a `T`. The bound of `T` must be `int`: `==` narrows the same way as `===`,
     * and a string or a float can be loosely equal to the literal without being identical to it.
     */
    private static function getTemplateParamEqualToLiteral(
        Union $literal_type,
        Context $context,
        StatementsAnalyzer $statements_analyzer,
    ): ?Union {
        $source = $statements_analyzer->getSource();

        if (!$source instanceof FunctionLikeAnalyzer) {
            return null;
        }

        $declared_return_type = $source->getFunctionLikeStorage($statements_analyzer)->return_type;

        if ($declared_return_type === null) {
            return null;
        }

        $template_type_map = $statements_analyzer->getTemplateTypeMap() ?? [];

        if (!$literal_type->isSingle()) {
            return null;
        }

        $literal = $literal_type->getSingleAtomic();

        if (!$literal instanceof TLiteralInt) {
            return null;
        }

        foreach ($context->vars_in_scope as $var_type) {
            if ($var_type->isSingle()
                && !$var_type->possibly_undefined
                && ($template_param = $var_type->getSingleAtomic()) instanceof TTemplateParam
                && $template_param->as->getId() === $literal_type->getId()
                && ($bound = $template_type_map[$template_param->param_name][$template_param->defining_class] ?? null)
                && $bound->isInt()
            ) {
                foreach ($declared_return_type->getAtomicTypes() as $declared_atomic) {
                    if ($declared_atomic instanceof TTemplateParam
                        && $declared_atomic->param_name === $template_param->param_name
                        && $declared_atomic->defining_class === $template_param->defining_class
                    ) {
                        return $var_type;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param bool $substitute_template_param whether a literal equal to a template variable is that variable
     */
    private static function getReturnedType(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $expr,
        Context $context,
        bool $substitute_template_param,
    ): ?Union {
        $expr_type = $statements_analyzer->node_data->getType($expr);

        if ($expr_type === null || !$substitute_template_param) {
            return $expr_type;
        }

        $template_type = self::getTemplateParamEqualToLiteral($expr_type, $context, $statements_analyzer);

        if ($template_type === null) {
            return $expr_type;
        }

        // the value is the literal's, so is where it comes from: only its type is the variable's
        return $template_type->setParentNodes($expr_type->parent_nodes);
    }

    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Return_ $stmt,
        Context $context,
    ): void {
        self::analyzeReturn($statements_analyzer, $stmt, $context);

        // what a closure wrote to a variable it captures by reference stays written when it returns
        $closure = $statements_analyzer->getSource();

        if ($closure instanceof ClosureAnalyzer) {
            foreach ($context->captured_by_ref as $var_id => $_) {
                if (isset($context->vars_in_scope[$var_id])) {
                    $closure->captured_by_ref_types_at_return[$var_id][] = $context->vars_in_scope[$var_id];
                }
            }
        }

        ConstructorExits::record($statements_analyzer, $context);
    }

    private static function analyzeReturn(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Return_ $stmt,
        Context $context,
    ): void {
        $doc_comment = $stmt->getDocComment();

        $var_comments = [];
        $var_comment_type = null;

        $source = $statements_analyzer->getSource();

        $codebase = $statements_analyzer->getCodebase();

        if ($doc_comment && ($parsed_docblock = $statements_analyzer->getParsedDocblock())) {
            $file_storage_provider = $codebase->file_storage_provider;

            $file_storage = $file_storage_provider->get($statements_analyzer->getFilePath());

            try {
                $var_comments = $codebase->config->disable_var_parsing
                    ? []
                    : CommentAnalyzer::arrayToDocblocks(
                        $statements_analyzer->getCodebase(),
                        $doc_comment,
                        $parsed_docblock,
                        $statements_analyzer->getSource(),
                        $statements_analyzer->getAliases(),
                        $statements_analyzer->getTemplateTypeMap(),
                        $file_storage->type_aliases,
                    );
            } catch (DocblockParseException $e) {
                IssueBuffer::maybeAdd(
                    new InvalidDocblock(
                        $e->getMessage(),
                        new CodeLocation($source, $stmt),
                    ),
                );
            }

            foreach ($var_comments as $var_comment) {
                if (!$var_comment->type) {
                    continue;
                }

                $comment_type = TypeExpander::expandUnion(
                    $codebase,
                    $var_comment->type,
                    $context->self,
                    $context->self,
                    $statements_analyzer->getParentFQCLN(),
                );

                if ($codebase->alter_code
                    && $var_comment->type_start
                    && $var_comment->type_end
                    && $var_comment->line_number
                ) {
                    $type_location = new DocblockTypeLocation(
                        $statements_analyzer,
                        $var_comment->type_start,
                        $var_comment->type_end,
                        $var_comment->line_number,
                    );

                    $codebase->classlikes->handleDocblockTypeInMigration(
                        $codebase,
                        $statements_analyzer,
                        $comment_type,
                        $type_location,
                        $context->calling_method_id,
                    );
                }

                if (!$var_comment->var_id) {
                    $var_comment_type = $comment_type;
                    continue;
                }

                if (isset($context->vars_in_scope[$var_comment->var_id])) {
                    $comment_type = $comment_type->setParentNodes(
                        $context->vars_in_scope[$var_comment->var_id]->parent_nodes,
                    );
                }

                $context->vars_in_scope[$var_comment->var_id] = $comment_type;
            }
        }

        if ($stmt->expr) {
            $context->inside_return = true;

            if ($stmt->expr instanceof PhpParser\Node\Expr\Closure
                || $stmt->expr instanceof PhpParser\Node\Expr\ArrowFunction
            ) {
                self::potentiallyInferTypesOnClosureFromParentReturnType(
                    $statements_analyzer,
                    $stmt->expr,
                    $context,
                );
            }

            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                $context->inside_return = false;
                $context->has_returned = true;

                return;
            }

            $stmt_expr_type = self::getReturnedType($statements_analyzer, $stmt->expr, $context, !$var_comment_type);

            if ($var_comment_type) {
                $stmt_type = $var_comment_type;

                if ($stmt_expr_type && $stmt_expr_type->parent_nodes) {
                    $stmt_type = $stmt_type->setParentNodes($stmt_expr_type->parent_nodes);
                }

                $statements_analyzer->node_data->setType($stmt, $var_comment_type);
            } elseif ($stmt_expr_type) {
                $stmt_type = $stmt_expr_type;

                if ($stmt_type->isNever()) {
                    IssueBuffer::maybeAdd(
                        new NoValue(
                            'All possible types for this return were invalidated - This may be dead code',
                            new CodeLocation($source, $stmt),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );

                    $stmt_type = Type::getNever();
                }

                if ($stmt_type->isVoid()) {
                    $stmt_type = Type::getNull();
                }
            } else {
                $stmt_type = Type::getMixed();
            }

            $context->inside_return = false;
        } else {
            $stmt_type = Type::getVoid();
        }

        $statements_analyzer->node_data->setType($stmt, $stmt_type);

        if ($context->finally_scope) {
            foreach ($context->vars_in_scope as $var_id => $type) {
                if (isset($context->finally_scope->vars_in_scope[$var_id])) {
                    $context->finally_scope->vars_in_scope[$var_id] = Type::combineUnionTypes(
                        $context->finally_scope->vars_in_scope[$var_id],
                        $type,
                        $statements_analyzer->getCodebase(),
                    );
                } else {
                    $context->finally_scope->vars_in_scope[$var_id] = $type->setPossiblyUndefined(true, true);
                }
            }
        }

        $context->has_returned = true;

        if ($source instanceof FunctionLikeAnalyzer
            && $source->getSource() instanceof TraitAnalyzer
        ) {
            self::analyzeReturnInTraitMethod($statements_analyzer, $stmt, $stmt_type, $context);
        } elseif ($source instanceof FunctionLikeAnalyzer) {
            $source->addReturnTypes($context);

            $source->examineParamTypes($statements_analyzer, $context, $codebase, $stmt);

            $storage = $source->getFunctionLikeStorage($statements_analyzer);

            if ($storage->signature_return_type
                && $storage->signature_return_type->by_ref
                && $stmt->expr !== null
                && !($stmt->expr instanceof PhpParser\Node\Expr\Variable
                    || $stmt->expr instanceof PhpParser\Node\Expr\PropertyFetch
                    || $stmt->expr instanceof PhpParser\Node\Expr\StaticPropertyFetch
                )
            ) {
                IssueBuffer::maybeAdd(
                    new NonVariableReferenceReturn(
                        'Only variable references should be returned by reference',
                        new CodeLocation($source, $stmt->expr),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }

            $cased_method_id = $source->getCorrectlyCasedMethodId();

            FunctionLikeAnalyzer::taintByRefParamsOut($codebase, $context);

            if ($stmt->expr && $storage->location) {
                $inferred_type = TypeExpander::expandUnion(
                    $codebase,
                    $stmt_type,
                    $source->getFQCLN(),
                    $source->getFQCLN(),
                    $source->getParentFQCLN(),
                );

                self::handleTaints(
                    $statements_analyzer,
                    $stmt,
                    $cased_method_id,
                    $inferred_type,
                    $storage,
                    $context,
                );

                if ($storage instanceof MethodStorage
                    // the purity of the calls, telling which of them return `$this`, is only analyzed then
                    && !$context->collect_initializations
                    && !$context->collect_mutations
                    && !MethodCallPurityAnalyzer::isReceiverThis($stmt->expr)
                    && self::mustReturnThis($codebase, $storage, MethodIdentifier::wrap($cased_method_id))
                ) {
                    IssueBuffer::maybeAdd(
                        new InvalidReturnStatement(
                            'The declared return type \'$this\' for ' . $cased_method_id
                                . ' requires returning $this, but this may return another object',
                            new CodeLocation($source, $stmt->expr),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }

                if ($storage instanceof MethodStorage && $context->self) {
                    $self_class = $context->self;

                    $declared_return_type = $codebase->methods->getMethodReturnType(
                        $codebase,
                        MethodIdentifier::wrap($cased_method_id),
                        $self_class,
                        $statements_analyzer,
                        null,
                    );

                    [, $method_name] = explode('::', $cased_method_id);
                    if ($method_name === '__construct') {
                        IssueBuffer::maybeAdd(
                            new InvalidReturnStatement(
                                'No return values are expected for ' . $cased_method_id,
                                new CodeLocation($source, $stmt->expr),
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                        return;
                    }
                } else {
                    $declared_return_type = $storage->return_type;
                }

                if ($declared_return_type && !$declared_return_type->hasMixed()) {
                    $local_return_type = $source->getLocalReturnType(
                        $declared_return_type,
                        $storage instanceof MethodStorage && $storage->final,
                    );

                    if ($storage instanceof MethodStorage) {
                        [$fq_class_name, $method_name] = explode('::', $cased_method_id);

                        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

                        $found_generic_params = ClassTemplateParamCollector::collect(
                            $codebase,
                            $class_storage,
                            $class_storage,
                            strtolower($method_name),
                            null,
                            true,
                        );

                        if ($found_generic_params) {
                            foreach ($found_generic_params as $template_name => $_) {
                                unset($found_generic_params[$template_name][$fq_class_name]);
                            }

                            $local_return_type = TemplateInferredTypeReplacer::replace(
                                $local_return_type,
                                new TemplateResult([], $found_generic_params),
                                $codebase,
                            );
                        }
                    }

                    if ($storage->has_yield) {
                        $local_return_type = self::getGeneratorReturnTypeToCheck(
                            $statements_analyzer,
                            $stmt,
                            $local_return_type,
                            $cased_method_id,
                        );

                        if ($local_return_type === null) {
                            return;
                        }
                    }

                    if ($stmt_type->hasMixed()) {
                        if ($local_return_type->isVoid() || $local_return_type->isNever()) {
                            if (IssueBuffer::accepts(
                                new InvalidReturnStatement(
                                    'No return values are expected for ' . $cased_method_id,
                                    new CodeLocation($source, $stmt->expr),
                                ),
                                $statements_analyzer->getSuppressedIssues(),
                            )) {
                                return;
                            }
                        }

                        if (!$context->collect_initializations
                            && !$context->collect_mutations
                            && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                            && !($source->getSource() instanceof TraitAnalyzer)
                        ) {
                            $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
                        }

                        if ($stmt_type->isMixed()) {
                            $origin_locations = [];

                            if ($statements_analyzer->variable_use_graph) {
                                foreach ($stmt_type->parent_nodes as $parent_node) {
                                    $origin_locations = [
                                        ...$origin_locations,
                                        ...$statements_analyzer->variable_use_graph->getOriginLocations($parent_node),
                                    ];
                                }
                            }

                            $origin_location = count($origin_locations) === 1 ? reset($origin_locations) : null;

                            $return_location = new CodeLocation($source, $stmt->expr);

                            if ($origin_location && $origin_location->getHash() === $return_location->getHash()) {
                                $origin_location = null;
                            }

                            IssueBuffer::maybeAdd(
                                new MixedReturnStatement(
                                    'Could not infer a return type',
                                    $return_location,
                                    $origin_location,
                                ),
                                $statements_analyzer->getSuppressedIssues(),
                            );

                            return;
                        }

                        IssueBuffer::maybeAdd(
                            new MixedReturnStatement(
                                'Possibly-mixed return value',
                                new CodeLocation($source, $stmt->expr),
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                    }

                    if ($local_return_type->isMixed()) {
                        return;
                    }

                    if (!$context->collect_initializations
                        && !$context->collect_mutations
                        && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                        && !($source->getSource() instanceof TraitAnalyzer)
                    ) {
                        $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());
                    }

                    if ($local_return_type->isVoid()) {
                        IssueBuffer::maybeAdd(
                            new InvalidReturnStatement(
                                'No return values are expected for ' . $cased_method_id,
                                new CodeLocation($source, $stmt->expr),
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                        return;
                    }

                    $union_comparison_results = new TypeComparisonResult();

                    $is_contained_by = UnionTypeComparator::isContainedBy(
                        $codebase,
                        $inferred_type,
                        $local_return_type,
                        true,
                        true,
                        $union_comparison_results,
                    );

                    if ($is_contained_by
                        && ($union_comparison_results->type_variable_lower_bounds
                            || $union_comparison_results->type_variable_upper_bounds)
                    ) {
                        // transfer recorded type-variable bounds, upgrading
                        // plain upper bounds to equality bounds: the declared
                        // return type names the type parameter exactly, so a
                        // conflicting use elsewhere must fail reconciliation.
                        //
                        // A bound merged from several arms of a union return
                        // type (`Foo<int>|Foo<string>`) stays a plain upper
                        // bound (`int|string`): the arms are alternatives the
                        // value need only satisfy one of, as Hack localizes
                        // that union to `Foo<(int | string)>`.
                        foreach ($union_comparison_results->type_variable_upper_bounds as [$_, $upper_bound]) {
                            if ($upper_bound->equality_bound_classlike === null
                                && !$upper_bound->from_union_alternatives
                            ) {
                                $upper_bound->equality_bound_classlike = '';
                            }

                            // a value that only reaches this bound through mixed
                            // is reported as MixedReturnTypeCoercion at reconciliation
                            $upper_bound->from_return_requirement = true;
                        }

                        $statements_analyzer->type_variable_tracker->addBounds(
                            $union_comparison_results->type_variable_lower_bounds,
                            $union_comparison_results->type_variable_upper_bounds,
                            new CodeLocation($source, $stmt->expr),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                    }

                    if (!$is_contained_by) {
                        // is the declared return type more specific than the inferred one?
                        if ($union_comparison_results->type_coerced) {
                            if ($union_comparison_results->type_coerced_from_mixed) {
                                if (!$union_comparison_results->type_coerced_from_as_mixed) {
                                    if ($inferred_type->hasMixed()) {
                                        IssueBuffer::maybeAdd(
                                            new MixedReturnStatement(
                                                'Could not infer a return type',
                                                new CodeLocation($source, $stmt->expr),
                                            ),
                                            $statements_analyzer->getSuppressedIssues(),
                                        );
                                    } else {
                                        IssueBuffer::maybeAdd(
                                            new MixedReturnTypeCoercion(
                                                'The type \'' . $stmt_type->getId() . '\' is more general than the'
                                                    . ' declared return type \'' . $local_return_type->getId() . '\''
                                                    . ' for ' . $cased_method_id,
                                                new CodeLocation($source, $stmt->expr),
                                            ),
                                            $statements_analyzer->getSuppressedIssues(),
                                        );
                                    }
                                }
                            } else {
                                IssueBuffer::maybeAdd(
                                    new LessSpecificReturnStatement(
                                        'The type \'' . $stmt_type->getId() . '\' is more general than the'
                                            . ' declared return type \'' . $local_return_type->getId() . '\''
                                            . ' for ' . $cased_method_id,
                                        new CodeLocation($source, $stmt->expr),
                                    ),
                                    $statements_analyzer->getSuppressedIssues(),
                                );
                            }

                            foreach ($local_return_type->getAtomicTypes() as $local_type_part) {
                                if ($local_type_part instanceof TClassString
                                    && $stmt->expr instanceof PhpParser\Node\Scalar\String_
                                ) {
                                    if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                                        $statements_analyzer,
                                        $stmt->expr->value,
                                        new CodeLocation($source, $stmt->expr),
                                        $context,
                                        $statements_analyzer->getSuppressedIssues(),
                                        new ClassLikeNameOptions(true),
                                    ) === false
                                    ) {
                                        return;
                                    }
                                } elseif ($local_type_part instanceof TArray
                                    && $stmt->expr instanceof PhpParser\Node\Expr\Array_
                                ) {
                                    $value_param = $local_type_part->type_params[1];

                                    foreach ($value_param->getAtomicTypes() as $local_array_type_part) {
                                        if ($local_array_type_part instanceof TClassString) {
                                            foreach ($stmt->expr->items as $item) {
                                                if ($item->value instanceof PhpParser\Node\Scalar\String_) {
                                                    if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                                                        $statements_analyzer,
                                                        $item->value->value,
                                                        new CodeLocation($source, $item->value),
                                                        $context,
                                                        $statements_analyzer->getSuppressedIssues(),
                                                        new ClassLikeNameOptions(true),
                                                    ) === false
                                                    ) {
                                                        return;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            IssueBuffer::maybeAdd(
                                new InvalidReturnStatement(
                                    'The inferred type \'' . $inferred_type->getId()
                                        . '\' does not match the declared return '
                                        . 'type \'' . $local_return_type->getId() . '\' for ' . $cased_method_id
                                        . ($union_comparison_results->missing_shape_fields
                                            ? ' due to additional array shape fields ('
                                                . implode(', ', $union_comparison_results->missing_shape_fields)
                                                . ')'
                                            : ''),
                                    new CodeLocation($source, $stmt->expr),
                                ),
                                $statements_analyzer->getSuppressedIssues(),
                            );
                        }
                    }

                    if (!$stmt_type->ignore_nullable_issues
                        && $inferred_type->isNullable()
                        && !$local_return_type->isNullable()
                        && !$local_return_type->hasTemplate()
                    ) {
                        IssueBuffer::maybeAdd(
                            new NullableReturnStatement(
                                'The declared return type \'' . $local_return_type->getId() . '\' for '
                                    . $cased_method_id . ' is not nullable, but the function returns \''
                                        . $inferred_type->getId() . '\'',
                                new CodeLocation($source, $stmt->expr),
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                    }

                    if (!$stmt_type->ignore_falsable_issues
                        && $inferred_type->isFalsable()
                        && !$local_return_type->isFalsable()
                        && (!$local_return_type->hasBool() || $local_return_type->isTrue())
                        && !$local_return_type->hasScalar()
                    ) {
                        IssueBuffer::maybeAdd(
                            new FalsableReturnStatement(
                                'The declared return type \'' . $local_return_type . '\' for '
                                    . $cased_method_id . ' does not allow false, but the function returns \''
                                        . $inferred_type . '\'',
                                new CodeLocation($source, $stmt->expr),
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                    }
                }
            } else {
                if ($storage->signature_return_type
                    && !$storage->signature_return_type->isVoid()
                    && !$storage->has_yield
                ) {
                    IssueBuffer::maybeAdd(
                        new InvalidReturnStatement(
                            'Empty return statement is not expected in ' . $cased_method_id,
                            new CodeLocation($source, $stmt),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }
            }
        }
    }

    /**
     * The body of a method of a trait is analyzed as one of each class using it: what it returns is returned by
     * the calls of the method on that class (see FunctionLikeAnalyzer::getBodyMethodId()). Its value is not
     * checked against the return type, except for a value returned by a generator through a declared type
     * without Generator.
     */
    private static function analyzeReturnInTraitMethod(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Return_ $stmt,
        Union $stmt_type,
        Context $context,
    ): void {
        $source = $statements_analyzer->getSource();

        if (!$source instanceof FunctionLikeAnalyzer) {
            return;
        }

        $codebase = $statements_analyzer->getCodebase();

        FunctionLikeAnalyzer::taintByRefParamsOut($codebase, $context);

        $storage = $source->getFunctionLikeStorage($statements_analyzer);
        $cased_method_id = $source->getCorrectlyCasedMethodId($context->self);

        if ($stmt->expr && $storage->location) {
            self::handleTaints(
                $statements_analyzer,
                $stmt,
                $cased_method_id,
                TypeExpander::expandUnion(
                    $codebase,
                    $stmt_type,
                    $context->self,
                    $context->self,
                    $context->parent,
                ),
                $storage,
                $context,
            );
        }

        if ($storage->has_yield && $storage->return_type && !$storage->return_type->hasMixed()) {
            $declared_type = TypeExpander::expandUnion(
                $codebase,
                $storage->return_type,
                $context->self,
                $context->self,
                $context->parent,
            );

            $trait_name = $source->getFQCLN();

            if ($trait_name !== null && $context->self !== null) {
                // what the class using the trait gave to the templates of the trait
                $found_generic_params = ClassTemplateParamCollector::collect(
                    $codebase,
                    $codebase->classlike_storage_provider->get($trait_name),
                    $codebase->classlike_storage_provider->get($context->self),
                    $storage->cased_name === null ? null : strtolower($storage->cased_name),
                    null,
                    true,
                );

                if ($found_generic_params !== null) {
                    $declared_type = TemplateInferredTypeReplacer::replace(
                        $declared_type,
                        new TemplateResult([], $found_generic_params),
                        $codebase,
                    );
                }
            }

            self::getGeneratorReturnTypeToCheck($statements_analyzer, $stmt, $declared_type, $cased_method_id);
        }
    }

    /**
     * What the value returned by a function containing `yield` is checked against: the TReturn of the declared
     * Generator, or null when there is nothing to check
     */
    private static function getGeneratorReturnTypeToCheck(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Return_ $stmt,
        Union $declared_type,
        string $cased_method_id,
    ): ?Union {
        $expr = $stmt->expr;
        $source = $statements_analyzer->getSource();

        // an arrow function implicitly returns its expression, which says nothing about the generator's return value
        if ($expr === null || ($source instanceof FunctionLikeAnalyzer && $source->isArrowFunction())) {
            return null;
        }

        $codebase = $statements_analyzer->getCodebase();
        $return_types = [];

        if (!self::collectGeneratorReturnTypes($codebase, $declared_type, $return_types) || !$return_types) {
            if (!$statements_analyzer->node_data->getType($stmt)?->isNull()) {
                IssueBuffer::maybeAdd(
                    new HiddenGeneratorReturn(
                        'The value returned by the generator ' . $cased_method_id
                            . ' can\'t be read through its declared return type \''
                            . $declared_type->getId() . '\', which has no Generator',
                        new CodeLocation($statements_analyzer->getSource(), $expr),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }

            return null;
        }

        $return_type = TypeCombiner::combine($return_types, $codebase);

        return $return_type->hasMixed() ? null : $return_type;
    }

    /**
     * Collects what a `return` of a function containing `yield` may return. Every alternative of the declared
     * type (with its intersected types and template bound) contributes the TReturn of its Generator, or mixed
     * if it has no Generator but could be one.
     *
     * @param list<Atomic> $return_types
     * @return bool whether the declared type has a Generator
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private static function collectGeneratorReturnTypes(
        Codebase $codebase,
        Union $declared_type,
        array &$return_types,
    ): bool {
        $has_generator = false;

        foreach ($declared_type->getAtomicTypes() as $atomic) {
            $parts = [$atomic];

            if ($atomic instanceof TNamedObject
                || $atomic instanceof TTemplateParam
                || $atomic instanceof TIterable
            ) {
                $parts = [...$parts, ...array_values($atomic->getIntersectionTypes())];
            }

            $generator_types = [];
            $other_types = [];

            foreach ($parts as $part) {
                if ($part instanceof TTemplateParam) {
                    $bound_types = [];

                    if (self::collectGeneratorReturnTypes($codebase, $part->as, $bound_types)) {
                        $generator_types = [...$generator_types, ...$bound_types];
                    } else {
                        $other_types = [...$other_types, ...$bound_types];
                    }
                } elseif ($part instanceof TNamedObject && strtolower($part->value) === 'generator') {
                    $generator_return_type = $part instanceof TGenericObject ? ($part->type_params[3] ?? null) : null;

                    foreach ($generator_return_type?->getAtomicTypes() ?? [new TMixed()] as $return_atomic) {
                        $generator_types[] = $return_atomic;
                    }
                } elseif ($part instanceof TMixed
                    || $part instanceof TIterable
                    || $part instanceof TArray
                    || $part instanceof TKeyedArray
                    || $part instanceof TObject
                    || ($part instanceof TNamedObject
                        && $codebase->classExtendsOrImplements('Generator', $part->value))
                ) {
                    $other_types[] = new TMixed();
                }
            }

            if ($generator_types) {
                $has_generator = true;
                $return_types = [...$return_types, ...$generator_types];
            } else {
                $return_types = [...$return_types, ...$other_types];
            }
        }

        return $has_generator;
    }

    /**
     * Callers treat what a method returning `$this` gives back as their receiver, so the method,
     * and every override of it, must return nothing else.
     *
     * @psalm-mutation-free
     */
    public static function mustReturnThis(
        Codebase $codebase,
        MethodStorage $storage,
        MethodIdentifier $method_id,
    ): bool {
        if ($storage->returns_this) {
            return true;
        }

        $class_storage = $codebase->classlike_storage_provider->get($method_id->fq_class_name);

        foreach ($class_storage->overridden_method_ids[$method_id->method_name] ?? [] as $overridden_method_id) {
            if ($codebase->methods->getStorage($overridden_method_id)->returns_this) {
                return true;
            }
        }

        return false;
    }

    private static function handleTaints(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Return_ $stmt,
        string $cased_method_id,
        Union $inferred_type,
        FunctionLikeStorage $storage,
        Context $context,
    ): void {
        if (!$statements_analyzer->taint_flow_graph
            || !$stmt->expr
            || !$storage->location
        ) {
            return;
        }

        $method_node = DataFlowNode::getForMethodReturn(
            $cased_method_id,
            $storage,
        );

        $statements_analyzer->taint_flow_graph->addNode($method_node);

        $codebase = $statements_analyzer->getCodebase();

        $event = new AddRemoveTaintsEvent($stmt->expr, $context, $statements_analyzer, $codebase);

        // what is removed from this returned expression only applies to this return, not to the function's other
        // returns or to its calls
        $expr_removed_taints = $codebase->config->eventDispatcher->dispatchRemoveTaints($event);

        // what is added to it makes the calls of the function a source (see taintUsingStorage())
        $storage->added_taints |= $codebase->config->eventDispatcher->dispatchAddTaints($event)
            & ~$expr_removed_taints;

        if ($inferred_type->parent_nodes) {
            // what the returned value cannot hold, given its type or the native return type PHP enforces
            $removed_taints = $storage->removed_taints
                | $expr_removed_taints
                | $inferred_type->getTaintsToRemove($codebase->getInputTaints())
                | ($storage->signature_return_type?->getTaintsToRemove($codebase->getInputTaints()) ?? 0);

            foreach ($inferred_type->parent_nodes as $parent_node) {
                $statements_analyzer->taint_flow_graph->addPath(
                    $parent_node,
                    $method_node,
                    'return',
                    $storage->added_taints,
                    $removed_taints,
                );
            }
        }
    }

    /**
     * If a function returns a closure, we try to infer the param/return types of
     * the inner closure.
     *
     * @see \Psalm\Tests\ReturnTypeTest:756
     * @param PhpParser\Node\Expr\Closure|PhpParser\Node\Expr\ArrowFunction $expr
     */
    private static function potentiallyInferTypesOnClosureFromParentReturnType(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\FunctionLike $expr,
        Context $context,
    ): void {
        // if not returning from inside of a function, return
        if (!$context->calling_method_id && !$context->calling_function_id) {
            return;
        }

        $closure_id = (new ClosureAnalyzer($expr, $statements_analyzer))->getClosureId();
        $closure_storage = $statements_analyzer
            ->getCodebase()
            ->getFunctionLikeStorage($statements_analyzer, $closure_id);

        // a closure returned from a closure takes its types from the closure it is returned from
        $source = $statements_analyzer->getSource();

        $parent_fn_storage = $source instanceof ClosureAnalyzer
            ? $source->getFunctionLikeStorage($statements_analyzer)
            : $statements_analyzer
                ->getCodebase()
                ->getFunctionLikeStorage(
                    $statements_analyzer,
                    $context->calling_function_id ?: $context->calling_method_id,
                );

        if ($parent_fn_storage->return_type === null) {
            return;
        }

        // can't infer returned closure if the parent doesn't have a callable return type
        if (!$parent_fn_storage->return_type->hasCallableType()) {
            return;
        }

        // cannot infer if we have union/intersection types
        if (!$parent_fn_storage->return_type->isSingle()) {
            return;
        }

        /** @var TClosure|TCallable $parent_callable_return_type */
        $parent_callable_return_type = $parent_fn_storage->return_type->getSingleAtomic();

        if ($parent_callable_return_type->params === null && $parent_callable_return_type->return_type === null) {
            return;
        }

        foreach ($closure_storage->params as $key => $param) {
            $parent_param = $parent_callable_return_type->params[$key] ?? null;
            $param->type = self::inferInnerClosureTypeFromParent(
                $statements_analyzer->getCodebase(),
                $param->type,
                $parent_param->type ?? null,
            );
        }

        $closure_storage->return_type = self::inferInnerClosureTypeFromParent(
            $statements_analyzer->getCodebase(),
            $closure_storage->return_type,
            $parent_callable_return_type->return_type,
        );
    }

    /**
     * - If non parent type, do nothing
     * - If no return type, infer from parent
     * - If parent return type is more specific, infer from parent
     * - else, do nothing
     */
    private static function inferInnerClosureTypeFromParent(
        Codebase $codebase,
        ?Union $return_type,
        ?Union $parent_return_type,
    ): ?Union {
        if (!$parent_return_type) {
            return $return_type;
        }
        if (!$return_type || UnionTypeComparator::isContainedBy($codebase, $parent_return_type, $return_type)) {
            return $parent_return_type;
        }
        return $return_type;
    }
}
