<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call\Method;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeNameOptions;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ArgumentsAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ByRefArgumentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ClassTemplateParamCollector;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Codebase\MixinLookup;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Issue\ImpureMethodCall;
use Psalm\Issue\MixedMethodCall;
use Psalm\IssueBuffer;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TEmptyMixed;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNonEmptyMixed;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function array_merge;
use function array_shift;
use function array_values;
use function count;
use function reset;
use function strtolower;

/**
 * This is a bunch of complex logic to handle the potential for missing methods,
 * use of intersection types and/or mixins, together with handling for fallback magic
 * methods.
 *
 * The happy path (i.e 99% of method calls) is handled in ExistingAtomicMethodCallAnalyzer
 *
 * @internal
 */
final class AtomicMethodCallAnalyzer extends CallAnalyzer
{
    /**
     * @param  TNamedObject|TTemplateParam|null $static_type
     * @psalm-suppress ComplexMethod it's really complex, but unavoidably so
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\MethodCall $stmt,
        Codebase $codebase,
        Context $context,
        Union $lhs_type,
        Atomic $lhs_type_part,
        ?Atomic $static_type,
        bool $is_intersection,
        ?string $lhs_var_id,
        AtomicMethodCallAnalysisResult $result,
        ?TemplateResult $inferred_template_result = null,
    ): void {
        if ($lhs_type_part instanceof TTemplateParam
            && !$lhs_type_part->as->isMixed()
        ) {
            // every type of a union bound has to have the method
            if (!$lhs_type_part->as->isSingle()) {
                foreach ($lhs_type_part->as->getAtomicTypes() as $bound_atomic_type) {
                    self::analyze(
                        $statements_analyzer,
                        $stmt,
                        $codebase,
                        $context,
                        $lhs_type,
                        $lhs_type_part->replaceAs(new Union([$bound_atomic_type])),
                        $static_type,
                        $is_intersection,
                        $lhs_var_id,
                        $result,
                        $inferred_template_result,
                    );
                }

                return;
            }

            $extra_types = $lhs_type_part->extra_types;

            $lhs_type_part = array_values(
                $lhs_type_part->as->getAtomicTypes(),
            )[0];

            // a template bounded by another template is bounded by that one's bound, and by what the outer one was
            // narrowed to
            if ($lhs_type_part instanceof TTemplateParam) {
                self::analyze(
                    $statements_analyzer,
                    $stmt,
                    $codebase,
                    $context,
                    $lhs_type,
                    $extra_types
                        ? $lhs_type_part->setIntersectionTypes([...$lhs_type_part->extra_types, ...$extra_types])
                        : $lhs_type_part,
                    $static_type,
                    $is_intersection,
                    $lhs_var_id,
                    $result,
                    $inferred_template_result,
                );

                return;
            }

            if ($lhs_type_part instanceof TNamedObject) {
                $lhs_type_part = $lhs_type_part
                    ->setIntersectionTypes([...$lhs_type_part->getIntersectionTypes(), ...$extra_types])
                    ->setFromDocblock(true);
            } elseif ($lhs_type_part instanceof TObject && $extra_types) {
                $lhs_type_part = array_shift($extra_types)->setFromDocblock(true);
                if ($extra_types) {
                    $lhs_type_part = $lhs_type_part->setIntersectionTypes($extra_types);
                }
            } else {
                $lhs_type_part = $lhs_type_part->setFromDocblock(true);
            }

            $result->has_mixed_method_call = true;
        }

        $source = $statements_analyzer->getSource();

        if ($lhs_type_part instanceof TCallableObject) {
            self::handleCallableObject(
                $statements_analyzer,
                $stmt,
                $context,
                $lhs_type_part->callable,
                $result,
                $inferred_template_result,
            );
            return;
        }

        if (!$lhs_type_part instanceof TNamedObject) {
            self::handleInvalidClass(
                $statements_analyzer,
                $codebase,
                $stmt,
                $lhs_type,
                $lhs_type_part,
                $lhs_var_id,
                $context,
                $is_intersection,
                $result,
            );

            return;
        }

        if (!$context->collect_initializations
            && !$context->collect_mutations
            && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
            && (!(($parent_source = $statements_analyzer->getSource())
                    instanceof FunctionLikeAnalyzer)
                || !$parent_source->getSource() instanceof TraitAnalyzer)
        ) {
            $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());
        }

        $result->has_valid_method_call_type = true;

        $fq_class_name = $lhs_type_part->value;

        $is_mock = ExpressionAnalyzer::isMock($fq_class_name);

        $result->has_mock = $result->has_mock || $is_mock;

        if ($fq_class_name === 'static') {
            $fq_class_name = (string) $context->self;
        }

        if ($is_mock ||
            $context->isPhantomClass($fq_class_name)
        ) {
            $result->return_type = Type::getMixed();

            ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->getArgs(),
                null,
                null,
                true,
                $context,
            );

            return;
        }

        if ($lhs_var_id === '$this') {
            $does_class_exist = true;
        } else {
            $does_class_exist = ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                $statements_analyzer,
                $fq_class_name,
                new CodeLocation($source, $stmt->var),
                $context,
                $statements_analyzer->getSuppressedIssues(),
                new ClassLikeNameOptions(true, false, true, true, $lhs_type_part->from_docblock, context: $context),
                $context->check_classes,
            );
        }

        if (!$does_class_exist) {
            return;
        }

        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

        $result->check_visibility = $result->check_visibility && !$class_storage->override_method_visibility;

        $intersection_types = $lhs_type_part->getIntersectionTypes();

        if (!$stmt->name instanceof PhpParser\Node\Identifier) {
            if (!$context->ignore_variable_method) {
                $codebase->analyzer->addMixedMemberName(
                    strtolower($fq_class_name) . '::',
                    $context->calling_method_id ?: $statements_analyzer->getFileName(),
                );
            }

            if ($stmt->isFirstClassCallable()) {
                $return_type_candidate = null;
                $method_name_type = $statements_analyzer->node_data->getType($stmt->name);
                if ($method_name_type && $method_name_type->isSingleStringLiteral()) {
                    $method_identifier = new MethodIdentifier(
                        $fq_class_name,
                        strtolower($method_name_type->getSingleStringLiteral()->value),
                    );
                    //the call to methodExists will register that the method was called from somewhere
                    if ($codebase->methodExists(
                        $method_identifier,
                        $context->calling_method_id,
                        null,
                        $statements_analyzer,
                        $statements_analyzer->getFilePath(),
                        true,
                        $context->insideUse(),
                    )) {
                        $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_identifier)
                            ?? $method_identifier;
                        $declaring_class_storage = $codebase->classlike_storage_provider->get(
                            $declaring_method_id->fq_class_name,
                        );
                        [$closure, $method_template_types] = MethodCallReturnTypeFetcher::getInheritedClosure(
                            $codebase,
                            $declaring_method_id,
                            $codebase->methods->getStorage($declaring_method_id),
                            $statements_analyzer,
                        );

                        $return_type_candidate = MethodCallReturnTypeFetcher::getFirstClassCallableType(
                            $codebase,
                            $stmt,
                            $closure,
                            new TemplateResult([], ClassTemplateParamCollector::collect(
                                $codebase,
                                $declaring_class_storage,
                                $class_storage,
                                $method_identifier->method_name,
                                $lhs_type_part,
                                $lhs_var_id === '$this',
                            ) ?? []),
                            $method_template_types,
                            $fq_class_name,
                            $static_type,
                            $declaring_class_storage->parent_class,
                            $static_type instanceof TNamedObject
                            && $codebase->classlike_storage_provider->get($static_type->value)->final,
                            true,
                        );
                    }
                }

                $statements_analyzer->node_data->setType($stmt, $return_type_candidate ?? Type::getClosure());


                return;
            }

            ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->getArgs(),
                null,
                null,
                true,
                $context,
            );

            $unknown_method_capabilities = MethodCallPurityAnalyzer::getUnknownMethodCapabilities(
                $statements_analyzer,
                $stmt->var,
                $class_storage,
                $intersection_types,
            );

            $statements_analyzer->signalMutation(
                ByRefArgumentAnalyzer::adjustUnknownParamsCapabilities(
                    $statements_analyzer,
                    $context,
                    $unknown_method_capabilities,
                    $stmt->getArgs(),
                ),
                $context,
                'method ' . ($intersection_types ? '(' . $lhs_type_part . ')' : $class_storage->name) . '::{unknown}',
                ImpureMethodCall::class,
                $stmt,
            );

            $result->return_type = Type::getMixed();
            return;
        }

        $method_name_lc = strtolower($stmt->name->name);

        $method_id = new MethodIdentifier($fq_class_name, $method_name_lc);

        $args = $stmt->isFirstClassCallable() ? [] : $stmt->getArgs();

        $naive_method_id = $method_id;

        // this tells us whether or not we can stay on the happy path
        $naive_method_exists = $codebase->methodExists(
            $method_id,
            $context->calling_method_id,
            $codebase->collect_locations
                ? new CodeLocation($source, $stmt->name)
                : null,
            !$context->collect_initializations
                && !$context->collect_mutations
                ? $statements_analyzer
                : null,
            $statements_analyzer->getFilePath(),
            false,
            $context->insideUse(),
        );

        $fake_method_exists = false;
        // the class whose @mixin the method is found through, as the call goes through its __call()
        $mixin_user_class = null;
        $mixin_method_id = null;
        // the mixin whose `@method` is called
        $pseudo_method_mixin = null;

        if (!$naive_method_exists) {
            // if the method doesn't exist we check for any method existence providers
            if ($codebase->methods->existence_provider->has($fq_class_name)) {
                $method_exists = $codebase->methods->existence_provider->doesMethodExist(
                    $fq_class_name,
                    $method_id->method_name,
                    $source,
                    null,
                );

                if ($method_exists) {
                    $fake_method_exists = true;
                }
            }

            // a `@method` of the class, of its parent classes or of its interfaces, or a method an existence provider
            // adds to the class, wins over its mixins
            if (!$fake_method_exists
                && MissingMethodCallHandler::findPseudoMethodAndClassStorages(
                    $codebase,
                    $class_storage,
                    $method_name_lc,
                ) === null
            ) {
                [$mixin, $mixin_method_exists] = self::findMixinWithMethod(
                    $statements_analyzer,
                    $stmt,
                    $codebase,
                    $context,
                    $class_storage,
                    $lhs_type_part,
                    $method_name_lc,
                    $lhs_var_id === '$this',
                );

                if ($mixin !== null) {
                    if ($mixin_method_exists || $class_storage->is_interface) {
                        $mixin_user_class = $fq_class_name;
                        ['type' => $lhs_type_part, 'storage' => $class_storage] = $mixin;
                        $fq_class_name = $class_storage->name;
                        $method_id = new MethodIdentifier($fq_class_name, $method_name_lc);
                        $mixin_method_id = $method_id;
                        $naive_method_exists = $mixin_method_exists;

                        // an interface has no __call(): a `@method` of its mixin goes through the mixin's
                        if (!$mixin_method_exists) {
                            $naive_method_id = $method_id;
                        }
                    } else {
                        // the call goes through the __call() of the class, with `$this` the class
                        $pseudo_method_mixin = $mixin;
                    }
                }
            }
        }

        $all_intersection_return_type = null;
        $all_intersection_existent_method_ids = [];

        // intersection types are also fun, they also complicate matters
        if ($intersection_types) {
            [$all_intersection_return_type, $all_intersection_existent_method_ids]
                = self::getIntersectionReturnType(
                    $statements_analyzer,
                    $stmt,
                    $codebase,
                    $context,
                    $lhs_type,
                    $lhs_type_part,
                    $lhs_var_id,
                    $result,
                    $intersection_types,
                );
        }

        if (($fake_method_exists
                && $codebase->methodExists(new MethodIdentifier($fq_class_name, '__call')))
            || !$naive_method_exists
            || !MethodAnalyzer::isMethodVisible(
                $method_id,
                $context,
                $statements_analyzer->getSource(),
            )
        ) {
            $interface_has_method = false;

            if ($class_storage->abstract && $class_storage->class_implements) {
                foreach ($class_storage->class_implements as $interface_fqcln_lc => $_) {
                    $interface_storage = $codebase->classlike_storage_provider->get($interface_fqcln_lc);

                    if (isset($interface_storage->methods[$method_name_lc])) {
                        $interface_has_method = true;
                        $fq_class_name = $interface_storage->name;
                        $method_id = new MethodIdentifier(
                            $fq_class_name,
                            $method_name_lc,
                        );
                        break;
                    }
                }
            }

            if (!$interface_has_method
                && !$all_intersection_existent_method_ids
                && $codebase->methodExists(
                    new MethodIdentifier($fq_class_name, '__call'),
                    $context->calling_method_id,
                    $codebase->collect_locations
                        ? new CodeLocation($source, $stmt->name)
                        : null,
                    !$context->collect_initializations
                        && !$context->collect_mutations
                        ? $statements_analyzer
                        : null,
                    $statements_analyzer->getFilePath(),
                    true,
                    $context->insideUse(),
                )
            ) {
                $new_call_context = MissingMethodCallHandler::handleMagicMethod(
                    $statements_analyzer,
                    $codebase,
                    $stmt,
                    $method_id,
                    $class_storage,
                    $context,
                    $codebase->config,
                    $all_intersection_return_type,
                    $result,
                    $lhs_type_part,
                    $lhs_var_id,
                    $pseudo_method_mixin,
                );

                if ($new_call_context) {
                    if ($method_id === $new_call_context->method_id) {
                        return;
                    }

                    $method_id = $new_call_context->method_id;
                    $args = $new_call_context->args;
                } else {
                    return;
                }
            }
        }

        $intersection_method_id = $intersection_types
            ? '(' . $lhs_type_part->getId() . ')'  . '::' . $stmt->name->name
            : null;
        $cased_method_id = $fq_class_name . '::' . $stmt->name->name;

        // in the body of an inherited method analyzed for a class inheriting it, `$this` is an object of that class
        if ($lhs_var_id === '$this'
            && $context->self
            && $fq_class_name !== $context->self
            && DataFlowNode::$body_suffix === null
            && $codebase->methodExists(
                new MethodIdentifier($context->self, $method_name_lc),
            )
        ) {
            $method_id = new MethodIdentifier($context->self, $method_name_lc);
            $cased_method_id = $context->self . '::' . $stmt->name->name;
            $fq_class_name = $context->self;
        }

        $source_method_id = $source instanceof FunctionLikeAnalyzer
            ? $source->getId()
            : null;

        $corrected_method_exists = ($naive_method_exists && $method_id === $naive_method_id)
            || ($method_id !== $naive_method_id
                && $codebase->methodExists(
                    $method_id,
                    $context->calling_method_id,
                    $codebase->collect_locations && $method_id !== $source_method_id
                        ? new CodeLocation($source, $stmt->name)
                        : null,
                ));

        if (!$corrected_method_exists
            || ($codebase->config->use_phpdoc_method_without_magic_or_parent
                && isset($class_storage->pseudo_methods[$method_name_lc]))
        ) {
            MissingMethodCallHandler::handleMissingOrMagicMethod(
                $statements_analyzer,
                $codebase,
                $stmt,
                $method_id,
                $codebase->interfaceExists($fq_class_name, null, $context),
                $context,
                $codebase->config,
                $all_intersection_return_type,
                $all_intersection_existent_method_ids,
                $intersection_method_id,
                $cased_method_id,
                $result,
                $lhs_type_part,
            );

            return;
        }

        MethodAnalyzer::checkMethodAvailability(
            $codebase,
            $method_id,
            new CodeLocation($source, $stmt->name),
            $statements_analyzer->getSuppressedIssues(),
            false,
            $context,
        );

        $old_node_data = $statements_analyzer->node_data;

        $return_type_candidate = ExistingAtomicMethodCallAnalyzer::analyze(
            $statements_analyzer,
            $stmt,
            $stmt->name,
            $args,
            $codebase,
            $context,
            $lhs_type_part,
            $static_type,
            $lhs_var_id,
            $method_id,
            $result,
            $inferred_template_result,
            $method_id === $mixin_method_id ? $mixin_user_class : null,
        );

        $statements_analyzer->node_data = $old_node_data;

        $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

        $in_call_map = InternalCallMapHandler::inCallMap((string) ($declaring_method_id ?? $method_id));

        if (!$in_call_map) {
            if ($result->check_visibility) {
                $name_code_location = new CodeLocation($statements_analyzer, $stmt->name);

                MethodVisibilityAnalyzer::analyze(
                    $method_id,
                    $context,
                    $statements_analyzer->getSource(),
                    $name_code_location,
                    $statements_analyzer->getSuppressedIssues(),
                );
            }
        }

        self::updateResultReturnType(
            $result,
            $return_type_candidate,
            $all_intersection_return_type,
            $codebase,
        );
    }

    /**
     * @param  TNamedObject|TTemplateParam $lhs_type_part
     * @param   array<string, Atomic> $intersection_types
     * @return  array{?Union, array<string, bool>}
     */
    private static function getIntersectionReturnType(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\MethodCall $stmt,
        Codebase $codebase,
        Context $context,
        Union $lhs_type,
        Atomic $lhs_type_part,
        ?string $lhs_var_id,
        AtomicMethodCallAnalysisResult $result,
        array $intersection_types,
    ): array {
        $all_intersection_return_type = null;
        $all_intersection_existent_method_ids = [];

        foreach ($intersection_types as $intersection_type) {
            $intersection_result = clone $result;

            /** @var ?Union $intersection_result->return_type */
            $intersection_result->return_type = null;
            $intersection_result->existent_method_ids = [];

            self::analyze(
                $statements_analyzer,
                $stmt,
                $codebase,
                $context,
                $lhs_type,
                $intersection_type,
                $lhs_type_part,
                true,
                $lhs_var_id,
                $intersection_result,
            );

            $result->returns_by_ref = $intersection_result->returns_by_ref;
            $result->has_mock = $intersection_result->has_mock;
            $result->has_valid_method_call_type = $intersection_result->has_valid_method_call_type;
            $result->has_mixed_method_call = $intersection_result->has_mixed_method_call;
            $result->invalid_method_call_types = $intersection_result->invalid_method_call_types;
            $result->check_visibility = $intersection_result->check_visibility;
            $result->too_many_arguments = $intersection_result->too_many_arguments;

            $all_intersection_existent_method_ids = array_merge(
                $all_intersection_existent_method_ids,
                $intersection_result->existent_method_ids,
            );

            if ($intersection_result->return_type) {
                if (!$all_intersection_return_type || $all_intersection_return_type->isMixed()) {
                    $all_intersection_return_type = $intersection_result->return_type;
                } else {
                    $all_intersection_return_type = Type::intersectUnionTypes(
                        $all_intersection_return_type,
                        $intersection_result->return_type,
                        $codebase,
                    ) ?? Type::getMixed();
                }
            }
        }

        return [$all_intersection_return_type, $all_intersection_existent_method_ids];
    }

    private static function updateResultReturnType(
        AtomicMethodCallAnalysisResult $result,
        Union $return_type_candidate,
        ?Union $all_intersection_return_type,
        Codebase $codebase,
    ): void {
        if ($all_intersection_return_type) {
            $return_type_candidate = Type::intersectUnionTypes(
                $all_intersection_return_type,
                $return_type_candidate,
                $codebase,
            ) ?? Type::getMixed();
        }

        $result->return_type = Type::combineUnionTypes($return_type_candidate, $result->return_type);
    }

    private static function handleInvalidClass(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Expr\MethodCall $stmt,
        Union $lhs_type,
        Atomic $lhs_type_part,
        ?string $lhs_var_id,
        Context $context,
        bool $is_intersection,
        AtomicMethodCallAnalysisResult $result,
    ): void {
        switch ($lhs_type_part::class) {
            case TNull::class:
            case TFalse::class:
                // handled above
                return;

            case TTemplateParam::class:
            case TEmptyMixed::class:
            case TNever::class:
            case TMixed::class:
            case TNonEmptyMixed::class:
            case TObject::class:
            case TObjectWithProperties::class:
                if (!$context->collect_initializations
                    && !$context->collect_mutations
                    && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                    && (!(($parent_source = $statements_analyzer->getSource())
                            instanceof FunctionLikeAnalyzer)
                        || !$parent_source->getSource() instanceof TraitAnalyzer)
                ) {
                    $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
                }

                $result->has_mixed_method_call = true;

                if ($lhs_type_part instanceof TObjectWithProperties
                    && $stmt->name instanceof PhpParser\Node\Identifier
                    && isset($lhs_type_part->methods[strtolower($stmt->name->name)])
                ) {
                    $method_id = $lhs_type_part->methods[strtolower($stmt->name->name)];
                    $result->existent_method_ids[$method_id] = true;
                } elseif (!$is_intersection) {
                    if ($stmt->name instanceof PhpParser\Node\Identifier) {
                        $codebase->analyzer->addMixedMemberName(
                            strtolower($stmt->name->name),
                            $context->calling_method_id ?: $statements_analyzer->getFileName(),
                        );
                    }

                    if ($context->check_methods) {
                        $message = 'Cannot determine the type of the object'
                            . ' on the left hand side of this expression';

                        if ($lhs_var_id) {
                            $message = 'Cannot determine the type of ' . $lhs_var_id;

                            if ($stmt->name instanceof PhpParser\Node\Identifier) {
                                $message .= ' when calling method ' . $stmt->name->name;
                            }
                        }

                        $origin_locations = [];

                        if ($statements_analyzer->variable_use_graph) {
                            foreach ($lhs_type->parent_nodes as $parent_node) {
                                $origin_locations = [
                                    ...$origin_locations,
                                    ...$statements_analyzer->variable_use_graph->getOriginLocations($parent_node),
                                ];
                            }
                        }

                        $origin_location = count($origin_locations) === 1 ? reset($origin_locations) : null;

                        $name_code_location = new CodeLocation($statements_analyzer, $stmt->name);

                        if ($origin_location && $origin_location->getHash() === $name_code_location->getHash()) {
                            $origin_location = null;
                        }

                        IssueBuffer::maybeAdd(
                            new MixedMethodCall(
                                $message,
                                $name_code_location,
                                $origin_location,
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                    }
                }

                if ($stmt->isFirstClassCallable()) {
                    $result->return_type = Type::getClosure();
                } else {
                    if (ArgumentsAnalyzer::analyze(
                        $statements_analyzer,
                        $stmt->getArgs(),
                        null,
                        null,
                        true,
                        $context,
                    ) === false) {
                        return;
                    }

                    $result->return_type = Type::getMixed();
                }
                return;

            default:
                $result->invalid_method_call_types[] = (string)$lhs_type_part;
                return;
        }
    }

    /**
     * The mixin with the method, bound for the receiver, and whether the method is a real one rather than a
     * `@method`, which is only reached through the `__call()` of the class. Methods existence providers add to a
     * mixin are not looked for: a provider forwarding the call back to the class would recurse.
     *
     * @param lowercase-string $method_name_lc
     * @return array{
     *     array{type: TNamedObject, storage: ClassLikeStorage, declared: TNamedObject|TTemplateParam}|null,
     *     bool,
     * }
     */
    private static function findMixinWithMethod(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\MethodCall $stmt,
        Codebase $codebase,
        Context $context,
        ClassLikeStorage $class_storage,
        TNamedObject $lhs_type_part,
        string $method_name_lc,
        bool $self_call,
    ): array {
        $mixin_method_exists = false;
        // an interface has no __call() of its own: the class implementing it forwards the call
        $has_magic_call = $class_storage->is_interface || isset($class_storage->declaring_method_ids['__call']);

        $mixin = MixinLookup::findMember(
            $codebase,
            $class_storage,
            $lhs_type_part,
            static function (ClassLikeStorage $mixin_storage) use (
                $statements_analyzer,
                $stmt,
                $codebase,
                $context,
                $method_name_lc,
                $has_magic_call,
                &$mixin_method_exists,
            ): bool {
                $mixin_method_exists = $codebase->methodExists(
                    new MethodIdentifier($mixin_storage->name, $method_name_lc),
                    $context->calling_method_id,
                    $codebase->collect_locations
                        ? new CodeLocation($statements_analyzer->getSource(), $stmt->name)
                        : null,
                    !$context->collect_initializations
                    && !$context->collect_mutations
                        ? $statements_analyzer
                        : null,
                    $statements_analyzer->getFilePath(),
                    false,
                    $context->insideUse(),
                );

                return $mixin_method_exists
                    || ($has_magic_call && isset($mixin_storage->pseudo_methods[$method_name_lc]));
            },
            $self_call,
        );

        return [$mixin, $mixin_method_exists];
    }

    private static function handleCallableObject(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\MethodCall $stmt,
        Context $context,
        ?TCallable $lhs_type_part_callable,
        AtomicMethodCallAnalysisResult $result,
        ?TemplateResult $inferred_template_result = null,
    ): void {
        $method_id = 'object::__invoke';
        $result->existent_method_ids[$method_id] = true;
        $result->has_valid_method_call_type = true;

        if ($lhs_type_part_callable !== null && !$stmt->isFirstClassCallable()) {
            $result->return_type = $lhs_type_part_callable->return_type ?? Type::getMixed();
            $callableArgumentCount = count($lhs_type_part_callable->params ?? []);
            $providedArgumentsCount = count($stmt->getArgs());

            if ($callableArgumentCount > $providedArgumentsCount) {
                $result->too_few_arguments = true;
                $result->too_few_arguments_method_ids[] = new MethodIdentifier('callable-object', '__invoke');
            } elseif ($providedArgumentsCount > $callableArgumentCount) {
                $result->too_many_arguments = true;
                $result->too_many_arguments_method_ids[] = new MethodIdentifier('callable-object', '__invoke');
            }

            $template_result = $inferred_template_result ?? new TemplateResult([], []);

            ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->getArgs(),
                $lhs_type_part_callable->params,
                $method_id,
                false,
                $context,
                $template_result,
            );

            ArgumentsAnalyzer::checkArgumentsMatch(
                $statements_analyzer,
                $stmt->getArgs(),
                $method_id,
                $lhs_type_part_callable->params ?? [],
                null,
                null,
                $template_result,
                new CodeLocation($statements_analyzer->getSource(), $stmt),
                $context,
            );
        }
    }
}
