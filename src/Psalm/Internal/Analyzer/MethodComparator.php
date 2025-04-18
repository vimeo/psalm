<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer;

use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeTraverser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\PhpVisitor\ParamReplacementVisitor;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Issue\ConstructorSignatureMismatch;
use Psalm\Issue\ImplementedParamTypeMismatch;
use Psalm\Issue\ImplementedReturnTypeMismatch;
use Psalm\Issue\LessSpecificImplementedReturnType;
use Psalm\Issue\MethodSignatureMismatch;
use Psalm\Issue\MethodSignatureMustProvideReturnType;
use Psalm\Issue\MismatchingDocblockParamType;
use Psalm\Issue\MismatchingDocblockReturnType;
use Psalm\Issue\MissingImmutableAnnotation;
use Psalm\Issue\MoreSpecificImplementedParamType;
use Psalm\Issue\OverriddenMethodAccess;
use Psalm\Issue\ParamNameMismatch;
use Psalm\Issue\TraitMethodSignatureMismatch;
use Psalm\IssueBuffer;
use Psalm\Storage\AttributeStorage;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\MethodStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function array_filter;
use function in_array;
use function str_starts_with;
use function strtolower;

/**
 * @internal
 */
final class MethodComparator
{
    /**
     * @param  string[]         $suppressed_issues
     * @return false|null
     * @psalm-suppress PossiblyUnusedReturnValue unused but seems important
     */
    public static function compare(
        Codebase $codebase,
        ?ClassMethod $stmt,
        ClassLikeStorage $implementer_classlike_storage,
        ClassLikeStorage $guide_classlike_storage,
        MethodStorage $implementer_method_storage,
        MethodStorage $guide_method_storage,
        string $implementer_called_class_name,
        int $implementer_visibility,
        CodeLocation $code_location,
        array $suppressed_issues,
        bool $prevent_abstract_override = true,
        bool $prevent_method_signature_mismatch = true,
    ): ?bool {
        $implementer_method_id = new MethodIdentifier(
            $implementer_classlike_storage->name,
            strtolower($guide_method_storage->cased_name ?: ''),
        );

        $implementer_declaring_method_id = $codebase->methods->getDeclaringMethodId(
            $implementer_method_id,
        );

        $cased_implementer_method_id = $implementer_classlike_storage->name . '::'
            . $implementer_method_storage->cased_name;

        $cased_guide_method_id = $guide_classlike_storage->name . '::' . $guide_method_storage->cased_name;

        $codebase->methods->file_reference_provider->addMethodDependencyToClassMember(
            strtolower((string)($implementer_declaring_method_id ?? $implementer_method_id)),
            strtolower($guide_classlike_storage->name . '::' . $guide_method_storage->cased_name),
        );

        self::checkForObviousMethodMismatches(
            $guide_classlike_storage,
            $implementer_classlike_storage,
            $guide_method_storage,
            $implementer_method_storage,
            $guide_method_storage->visibility,
            $implementer_visibility,
            $cased_guide_method_id,
            $cased_implementer_method_id,
            $prevent_method_signature_mismatch,
            $prevent_abstract_override,
            $codebase->analysis_php_version_id >= 8_00_00,
            $code_location,
            $suppressed_issues,
        );

        if ($guide_method_storage->signature_return_type && $prevent_method_signature_mismatch) {
            self::compareMethodSignatureReturnTypes(
                $codebase,
                $guide_classlike_storage,
                $implementer_classlike_storage,
                $guide_method_storage,
                $implementer_method_storage,
                $guide_method_storage->signature_return_type,
                $cased_guide_method_id,
                $implementer_called_class_name,
                $cased_implementer_method_id,
                $code_location,
                $suppressed_issues,
            );
        }

        // CallMapHandler needed due to https://github.com/vimeo/psalm/issues/10378
        if (!$guide_classlike_storage->user_defined
            && $implementer_classlike_storage->user_defined
            && $codebase->analysis_php_version_id >= 8_01_00
            && (($guide_method_storage->return_type && InternalCallMapHandler::inCallMap($cased_guide_method_id))
                || $guide_method_storage->signature_return_type
            ) && !$implementer_method_storage->signature_return_type
            && !array_filter(
                $implementer_method_storage->attributes,
                static fn(AttributeStorage $s): bool => $s->fq_class_name === 'ReturnTypeWillChange',
            )
        ) {
            IssueBuffer::maybeAdd(
                new MethodSignatureMustProvideReturnType(
                    'Method ' . $cased_implementer_method_id . ' must have a return type signature',
                    $implementer_method_storage->location ?: $code_location,
                ),
                $suppressed_issues + $implementer_classlike_storage->suppressed_issues,
            );
        }

        if ($guide_method_storage->return_type
            && $implementer_method_storage->return_type
            && !$implementer_method_storage->inherited_return_type
            && ($guide_method_storage->signature_return_type !== $guide_method_storage->return_type
                || $implementer_method_storage->signature_return_type !== $implementer_method_storage->return_type)
            && $implementer_classlike_storage->user_defined
            && (!$guide_classlike_storage->stubbed || $guide_classlike_storage->template_types)
        ) {
            self::compareMethodDocblockReturnTypes(
                $codebase,
                $guide_classlike_storage,
                $implementer_classlike_storage,
                $implementer_method_storage,
                $guide_method_storage->return_type,
                $implementer_method_storage->return_type,
                $cased_guide_method_id,
                $implementer_called_class_name,
                $implementer_declaring_method_id,
                $code_location,
                $suppressed_issues,
            );
        }

        foreach ($guide_method_storage->params as $i => $guide_param) {
            if (!isset($implementer_method_storage->params[$i])) {
                if (!$prevent_abstract_override && $i >= $guide_method_storage->required_param_count) {
                    continue;
                }

                if (IssueBuffer::accepts(
                    new MethodSignatureMismatch(
                        'Method ' . $cased_implementer_method_id . ' has fewer parameters than parent method ' .
                            $cased_guide_method_id,
                        $code_location,
                    ),
                    $suppressed_issues + $implementer_classlike_storage->suppressed_issues,
                )) {
                    return false;
                }

                return null;
            }

            self::compareMethodParams(
                $codebase,
                $stmt,
                $implementer_classlike_storage,
                $guide_classlike_storage,
                $implementer_called_class_name,
                $guide_method_storage,
                $implementer_method_storage,
                $guide_param,
                $implementer_method_storage->params[$i],
                $i,
                $cased_guide_method_id,
                $cased_implementer_method_id,
                $prevent_method_signature_mismatch,
                $code_location,
                $suppressed_issues,
            );
        }

        if (($guide_classlike_storage->is_interface
             || $guide_classlike_storage->preserve_constructor_signature
             || $implementer_method_storage->cased_name !== '__construct')
            && $implementer_method_storage->required_param_count > $guide_method_storage->required_param_count
        ) {
            if ($implementer_method_storage->cased_name !== '__construct') {
                if (IssueBuffer::accepts(
                    new MethodSignatureMismatch(
                        'Method ' . $cased_implementer_method_id . ' has more required parameters than parent method ' .
                            $cased_guide_method_id,
                        $code_location,
                    ),
                    $suppressed_issues + $implementer_classlike_storage->suppressed_issues,
                )) {
                    return false;
                }
            } else {
                if (IssueBuffer::accepts(
                    new ConstructorSignatureMismatch(
                        'Method ' . $cased_implementer_method_id . ' has more required parameters than parent method ' .
                            $cased_guide_method_id,
                        $code_location,
                    ),
                    $suppressed_issues + $implementer_classlike_storage->suppressed_issues,
                )) {
                    return false;
                }
            }


            return null;
        }

        return null;
    }

    /**
     * @param array<lowercase-string, MethodStorage> $pseudo_methods
     */
    public static function comparePseudoMethods(
        array $pseudo_methods,
        string $fq_class_name,
        Codebase $codebase,
        ClassLikeStorage $class_storage,
    ): void {
        foreach ($pseudo_methods as $pseudo_method_name => $pseudo_method_storage) {
            $pseudo_method_id = new MethodIdentifier(
                $fq_class_name,
                $pseudo_method_name,
            );

            $overridden_method_ids = $codebase->methods->getOverriddenMethodIds($pseudo_method_id);
            if (isset($class_storage->methods[$pseudo_method_id->method_name])) {
                $overridden_method_ids[$class_storage->name] = $pseudo_method_id;
            }

            if ($overridden_method_ids
                && $pseudo_method_name !== '__construct'
                && $pseudo_method_storage->location
            ) {
                foreach ($overridden_method_ids as $overridden_method_id) {
                    $parent_method_storage = $codebase->methods->getStorage($overridden_method_id);

                    $overridden_fq_class_name = $overridden_method_id->fq_class_name;

                    $parent_storage = $codebase->classlike_storage_provider->get($overridden_fq_class_name);

                    self::compare(
                        $codebase,
                        null,
                        $class_storage,
                        $parent_storage,
                        $pseudo_method_storage,
                        $parent_method_storage,
                        $fq_class_name,
                        $pseudo_method_storage->visibility ?: 0,
                        $class_storage->location ?: $pseudo_method_storage->location,
                        $class_storage->suppressed_issues,
                        true,
                        false,
                    );
                }
            }
        }
    }

    /**
     * @param  string[]         $suppressed_issues
     */
    private static function checkForObviousMethodMismatches(
        ClassLikeStorage $guide_classlike_storage,
        ClassLikeStorage $implementer_classlike_storage,
        MethodStorage $guide_method_storage,
        MethodStorage $implementer_method_storage,
        int $guide_visibility,
        int $implementer_visibility,
        string $cased_guide_method_id,
        string $cased_implementer_method_id,
        bool $prevent_method_signature_mismatch,
        bool $prevent_abstract_override,
        bool $trait_mismatches_are_fatal,
        CodeLocation $code_location,
        array $suppressed_issues,
    ): void {
        if ($implementer_visibility > $guide_visibility) {
            if ($trait_mismatches_are_fatal
                || $guide_classlike_storage->is_trait === $implementer_classlike_storage->is_trait
                || !in_array($guide_classlike_storage->name, $implementer_classlike_storage->used_traits)
                || $implementer_method_storage->defining_fqcln !== $implementer_classlike_storage->name
                || (!$implementer_method_storage->abstract
                    && !$guide_method_storage->abstract)
            ) {
                IssueBuffer::maybeAdd(
                    new OverriddenMethodAccess(
                        'Method ' . $cased_implementer_method_id . ' has different access level than '
                            . $cased_guide_method_id,
                        $code_location,
                    ),
                    $suppressed_issues + $implementer_classlike_storage->suppressed_issues,
                );
            } else {
                IssueBuffer::maybeAdd(
                    new TraitMethodSignatureMismatch(
                        'Method ' . $cased_implementer_method_id . ' has different access level than '
                        . $cased_guide_method_id,
                        $code_location,
                    ),
                    $suppressed_issues + $implementer_classlike_storage->suppressed_issues,
                );
            }
        }

        if ($guide_method_storage->final
            && $prevent_method_signature_mismatch
            && $prevent_abstract_override
        ) {
            IssueBuffer::maybeAdd(
                new MethodSignatureMismatch(
                    'Method ' . $cased_guide_method_id . ' is declared final and cannot be overridden',
                    $code_location,
                ),
                $guide_method_storage->final_from_docblock ?
                    $suppressed_issues + $implementer_classlike_storage->suppressed_issues :
                    [],
            );
        }

        if ($prevent_abstract_override
            && !$guide_method_storage->abstract
            && $implementer_method_storage->abstract
            && !$guide_classlike_storage->abstract
            && !$guide_classlike_storage->is_interface
        ) {
            IssueBuffer::maybeAdd(
                new MethodSignatureMismatch(
                    'Method ' . $cased_implementer_method_id . ' cannot be abstract when inherited method '
                        . $cased_guide_method_id . ' is non-abstract',
                    $code_location,
                ),
                $suppressed_issues + $implementer_classlike_storage->suppressed_issues,
            );
        }

        if ($guide_method_storage->returns_by_ref && !$implementer_method_storage->returns_by_ref) {
            IssueBuffer::maybeAdd(
                new MethodSignatureMismatch(
                    'Method ' . $cased_implementer_method_id . ' must return by-reference',
                    $code_location,
                ),
                $suppressed_issues + $implementer_classlike_storage->suppressed_issues,
            );
        }

        if ($guide_method_storage->external_mutation_free
            && !$implementer_method_storage->external_mutation_free
            && !$guide_method_storage->mutation_free_inferred
            && $prevent_method_signature_mismatch
        ) {
            IssueBuffer::maybeAdd(
                new MissingImmutableAnnotation(
                    $cased_guide_method_id . ' is marked @psalm-external-mutation-free, but '
                        . $implementer_classlike_storage->name . '::'
                        . ($guide_method_storage->cased_name ?: '')
                        . ' is not marked @psalm-external-mutation-free',
                    $code_location,
                ),
                $suppressed_issues + $implementer_classlike_storage->suppressed_issues,
            );
        }
    }

    /**
     * @param  string[]         $suppressed_issues
     */
    private static function compareMethodParams(
        Codebase $codebase,
        ?ClassMethod $stmt,
        ClassLikeStorage $implementer_classlike_storage,
        ClassLikeStorage $guide_classlike_storage,
        string $implementer_called_class_name,
        MethodStorage $guide_method_storage,
        MethodStorage $implementer_method_storage,
        FunctionLikeParameter $guide_param,
        FunctionLikeParameter $implementer_param,
        int $i,
        string $cased_guide_method_id,
        string $cased_implementer_method_id,
        bool $prevent_method_signature_mismatch,
        CodeLocation $code_location,
        array $suppressed_issues,
    ): void {
        // ignore errors from stubbed/out of project files
        $config = Config::getInstance();
        if (!$implementer_classlike_storage->user_defined
            && (!$implementer_param->location
                || !$config->isInProjectDirs(
                    $implementer_param->location->file_path,
                )
            )) {
            return;
        }

        if ($prevent_method_signature_mismatch) {
            if (!$guide_classlike_storage->user_defined
                && $guide_param->type) {
                $implementer_param_type = $implementer_param->signature_type;

                $guide_param_signature_type = $guide_param->type;

                $or_null_guide_param_signature_type = $guide_param->signature_type
                    ? $guide_param->signature_type->getBuilder()
                    : null;

                if ($or_null_guide_param_signature_type) {
                    $or_null_guide_param_signature_type->addType(new TNull);
                }

                if ($cased_guide_method_id === 'Serializable::unserialize') {
                    $guide_param_signature_type = null;
                    $or_null_guide_param_signature_type = null;
                }

                if (!$guide_param->type->hasMixed()
                    && !$guide_param->type->from_docblock
                    && ($implementer_param_type || $guide_param_signature_type)
                ) {
                    if ($implementer_param_type
                        && (!$guide_param_signature_type
                            || strtolower($implementer_param_type->getId())
                                !== strtolower($guide_param_signature_type->getId()))
                        && (!$or_null_guide_param_signature_type
                            || strtolower($implementer_param_type->getId())
                                !== strtolower($or_null_guide_param_signature_type->getId()))
                    ) {
                        if ($implementer_method_storage->cased_name === '__construct') {
                            IssueBuffer::maybeAdd(
                                new ConstructorSignatureMismatch(
                                    'Argument ' . ($i + 1) . ' of '
                                        . $cased_implementer_method_id . ' has wrong type \''
                                        . $implementer_param_type . '\', expecting \''
                                        . $guide_param_signature_type . '\' as defined by '
                                        . $cased_guide_method_id,
                                    $implementer_param->location
                                        && $config->isInProjectDirs(
                                            $implementer_param->location->file_path,
                                        )
                                        ? $implementer_param->location
                                        : $code_location,
                                ),
                                $suppressed_issues + $implementer_classlike_storage->suppressed_issues,
                            );
                        } else {
                            IssueBuffer::maybeAdd(
                                new MethodSignatureMismatch(
                                    'Argument ' . ($i + 1) . ' of '
                                        . $cased_implementer_method_id . ' has wrong type \''
                                        . $implementer_param_type . '\', expecting \''
                                        . $guide_param_signature_type . '\' as defined by '
                                        . $cased_guide_method_id,
                                    $implementer_param->location
                                        && $config->isInProjectDirs(
                                            $implementer_param->location->file_path,
                                        )
                                        ? $implementer_param->location
                                        : $code_location,
                                ),
                                $suppressed_issues + $implementer_classlike_storage->suppressed_issues,
                            );
                        }


                        return;
                    }
                }
            }

            if ($guide_param->name !== $implementer_param->name
                && $guide_method_storage->allow_named_arg_calls
                && $implementer_classlike_storage->user_defined
                && $implementer_param->location
                && $guide_method_storage->cased_name
                && (!str_starts_with($guide_method_storage->cased_name, '__')
                    || ($guide_classlike_storage->preserve_constructor_signature
                        && $guide_method_storage->cased_name === '__construct'))
                && $config->isInProjectDirs(
                    $implementer_param->location->file_path,
                )
            ) {
                // even if it's just a single arg, it needs to be renamed in case it's called with a single named arg
                if ($config->allow_named_arg_calls
                    || ($guide_classlike_storage->location
                        && !$config->isInProjectDirs($guide_classlike_storage->location->file_path)
                    )
                ) {
                    if ($codebase->alter_code) {
                        $project_analyzer = ProjectAnalyzer::getInstance();

                        if ($stmt && isset($project_analyzer->getIssuesToFix()['ParamNameMismatch'])) {
                            $param_replacer = new ParamReplacementVisitor(
                                $implementer_param->name,
                                $guide_param->name,
                            );

                            $traverser = new NodeTraverser();
                            $traverser->addVisitor($param_replacer);
                            $traverser->traverse([$stmt]);

                            if ($replacements = $param_replacer->getReplacements()) {
                                FileManipulationBuffer::add(
                                    $implementer_param->location->file_path,
                                    $replacements,
                                );
                            }
                        }
                    } else {
                        IssueBuffer::maybeAdd(
                            new ParamNameMismatch(
                                'Argument ' . ($i + 1) . ' of ' . $cased_implementer_method_id . ' has wrong name $'
                                    . $implementer_param->name . ', expecting $'
                                    . $guide_param->name . ' as defined by '
                                    . $cased_guide_method_id,
                                $implementer_param->location,
                            ),
                            $suppressed_issues + $implementer_classlike_storage->suppressed_issues,
                        );
                    }
                }
            }

            if ($guide_classlike_storage->user_defined
                && $implementer_param->signature_type
                && $guide_param->signature_type
            ) {
                self::compareMethodSignatureParams(
                    $codebase,
                    $i,
                    $guide_classlike_storage,
                    $implementer_classlike_storage,
                    $guide_method_storage,
                    $implementer_method_storage,
                    $guide_param,
                    $implementer_param->signature_type,
                    $cased_guide_method_id,
                    $cased_implementer_method_id,
                    $code_location,
                    $suppressed_issues,
                );
            }
        }

        if ($implementer_param->type
            && $guide_param->type
            && $implementer_param->type->getId() !== $guide_param->type->getId()
        ) {
            self::compareMethodDocblockParams(
                $codebase,
                $i,
                $guide_classlike_storage,
                $implementer_classlike_storage,
                $implementer_called_class_name,
                $guide_method_storage,
                $implementer_method_storage,
                $cased_guide_method_id,
                $cased_implementer_method_id,
                $guide_param->type,
                $implementer_param->type,
                $code_location,
                $suppressed_issues,
            );
        }

        if ($implementer_param->by_ref !== $guide_param->by_ref) {
            IssueBuffer::maybeAdd(
                new MethodSignatureMismatch(
                    'Argument ' . ($i + 1) . ' of ' . $cased_implementer_method_id . ' is' .
                        ($implementer_param->by_ref ? '' : ' not') . ' passed by reference, but argument ' .
                        ($i + 1) . ' of ' . $cased_guide_method_id . ' is' . ($guide_param->by_ref ? '' : ' not'),
                    $implementer_param->location
                        && $config->isInProjectDirs(
                            $implementer_param->location->file_path,
                        )
                        ? $implementer_param->location
                        : $code_location,
                ),
                $suppressed_issues + $implementer_classlike_storage->suppressed_issues,
            );
        }
    }

    /**
     * @param  string[]         $suppressed_issues
     */
    private static function compareMethodSignatureParams(
        Codebase $codebase,
        int $i,
        ClassLikeStorage $guide_classlike_storage,
        ClassLikeStorage $implementer_classlike_storage,
        MethodStorage $guide_method_storage,
        MethodStorage $implementer_method_storage,
        FunctionLikeParameter $guide_param,
        Union $implementer_param_signature_type,
        string $cased_guide_method_id,
        string $cased_implementer_method_id,
        CodeLocation $code_location,
        array $suppressed_issues,
    ): void {
        $guide_param_signature_type = $guide_param->signature_type
            ? TypeExpander::expandUnion(
                $codebase,
                $guide_param->signature_type,
                $guide_classlike_storage->is_trait && $guide_method_storage->abstract
                    ? $implementer_classlike_storage->name
                    : $guide_classlike_storage->name,
                $guide_classlike_storage->is_trait && $guide_method_storage->abstract
                    ? $implementer_classlike_storage->name
                    : $guide_classlike_storage->name,
                $guide_classlike_storage->is_trait && $guide_method_storage->abstract
                    ? $implementer_classlike_storage->parent_class
                    : $guide_classlike_storage->parent_class,
            )
            : null;

        // CallMapHandler needed due to https://github.com/vimeo/psalm/issues/10378
        if (!$guide_param->signature_type
            && $guide_param->type
            && InternalCallMapHandler::inCallMap($cased_guide_method_id)) {
            $guide_method_storage_param_type = TypeExpander::expandUnion(
                $codebase,
                $guide_param->type,
                $guide_classlike_storage->is_trait && $guide_method_storage->abstract
                    ? $implementer_classlike_storage->name
                    : $guide_classlike_storage->name,
                $guide_classlike_storage->is_trait && $guide_method_storage->abstract
                    ? $implementer_classlike_storage->name
                    : $guide_classlike_storage->name,
                $guide_classlike_storage->is_trait && $guide_method_storage->abstract
                    ? $implementer_classlike_storage->parent_class
                    : $guide_classlike_storage->parent_class,
            );

            $builder = $guide_method_storage_param_type->getBuilder();
            foreach ($builder->getAtomicTypes() as $k => $t) {
                if ($t instanceof TTemplateParam) {
                    $builder->removeType($k);

                    foreach ($t->as->getAtomicTypes() as $as_t) {
                        $builder->addType($as_t);
                    }
                }
            }

            if ($builder->hasMixed()) {
                foreach ($builder->getAtomicTypes() as $k => $_) {
                    if ($k !== 'mixed') {
                        $builder->removeType($k);
                    }
                }
            }
            $guide_method_storage_param_type = $builder->freeze();
            unset($builder);

            if (!$guide_method_storage_param_type->hasMixed() || $codebase->analysis_php_version_id >= 8_00_00) {
                $guide_param_signature_type = $guide_method_storage_param_type;
            }
        }

        $implementer_param_signature_type = TypeExpander::expandUnion(
            $codebase,
            $implementer_param_signature_type,
            $implementer_classlike_storage->name,
            $implementer_classlike_storage->name,
            $implementer_classlike_storage->parent_class,
        );

        $is_contained_by = $codebase->analysis_php_version_id >= 7_04_00
            && $guide_param_signature_type
            ? UnionTypeComparator::isContainedBy(
                $codebase,
                $guide_param_signature_type,
                $implementer_param_signature_type,
            )
            : UnionTypeComparator::isContainedByInPhp(
                $guide_param_signature_type,
                $implementer_param_signature_type,
            );
        if (!$is_contained_by) {
            $config = Config::getInstance();

            if ($codebase->analysis_php_version_id >= 8_00_00
                || $guide_classlike_storage->is_trait === $implementer_classlike_storage->is_trait
                || !in_array($guide_classlike_storage->name, $implementer_classlike_storage->used_traits)
                || $implementer_method_storage->defining_fqcln !== $implementer_classlike_storage->name
                || (!$implementer_method_storage->abstract
                    && !$guide_method_storage->abstract)
            ) {
                if ($implementer_method_storage->cased_name === '__construct') {
                    IssueBuffer::maybeAdd(
                        new ConstructorSignatureMismatch(
                            'Argument ' . ($i + 1) . ' of '
                                . $cased_implementer_method_id
                                . ' has wrong type \''
                                . $implementer_param_signature_type . '\', expecting \''
                                . $guide_param_signature_type . '\' as defined by '
                                . $cased_guide_method_id,
                            $implementer_method_storage->params[$i]->location
                                && $config->isInProjectDirs(
                                    $implementer_method_storage->params[$i]->location->file_path,
                                )
                                ? $implementer_method_storage->params[$i]->location
                                : $code_location,
                        ),
                        $suppressed_issues + $implementer_classlike_storage->suppressed_issues,
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new MethodSignatureMismatch(
                            'Argument ' . ($i + 1) . ' of '
                                . $cased_implementer_method_id
                                . ' has wrong type \''
                                . $implementer_param_signature_type . '\', expecting \''
                                . $guide_param_signature_type . '\' as defined by '
                                . $cased_guide_method_id,
                            $implementer_method_storage->params[$i]->location
                                && $config->isInProjectDirs(
                                    $implementer_method_storage->params[$i]->location->file_path,
                                )
                                ? $implementer_method_storage->params[$i]->location
                                : $code_location,
                        ),
                        $suppressed_issues + $implementer_classlike_storage->suppressed_issues,
                    );
                }
            } else {
                IssueBuffer::maybeAdd(
                    new TraitMethodSignatureMismatch(
                        'Argument ' . ($i + 1) . ' of ' . $cased_implementer_method_id . ' has wrong type \'' .
                            $implementer_param_signature_type . '\', expecting \'' .
                            $guide_param_signature_type . '\' as defined by ' .
                            $cased_guide_method_id,
                        $implementer_method_storage->params[$i]->location
                            && $config->isInProjectDirs(
                                $implementer_method_storage->params[$i]->location->file_path,
                            )
                            ? $implementer_method_storage->params[$i]->location
                            : $code_location,
                    ),
                    $suppressed_issues + $implementer_classlike_storage->suppressed_issues,
                );
            }
        }
    }

    /**
     * @param  string[]         $suppressed_issues
     */
    private static function compareMethodDocblockParams(
        Codebase $codebase,
        int $i,
        ClassLikeStorage $guide_classlike_storage,
        ClassLikeStorage $implementer_classlike_storage,
        string $implementer_called_class_name,
        MethodStorage $guide_method_storage,
        MethodStorage $implementer_method_storage,
        string $cased_guide_method_id,
        string $cased_implementer_method_id,
        Union $guide_param_type,
        Union $implementer_param_type,
        CodeLocation $code_location,
        array $suppressed_issues,
    ): void {
        $implementer_method_storage_param_type = TypeExpander::expandUnion(
            $codebase,
            $implementer_param_type,
            $implementer_classlike_storage->name,
            $implementer_called_class_name,
            $implementer_classlike_storage->parent_class,
        );

        $guide_method_storage_param_type = TypeExpander::expandUnion(
            $codebase,
            $guide_param_type,
            $guide_classlike_storage->is_trait && $guide_method_storage->abstract
                ? $implementer_classlike_storage->name
                : $guide_classlike_storage->name,
            $guide_classlike_storage->is_trait && $guide_method_storage->abstract
                ? $implementer_classlike_storage->name
                : $guide_classlike_storage->name,
            $guide_classlike_storage->is_trait && $guide_method_storage->abstract
                ? $implementer_classlike_storage->parent_class
                : $guide_classlike_storage->parent_class,
        );

        $guide_class_name = $guide_classlike_storage->name;

        if ($implementer_classlike_storage->is_trait) {
            $implementer_called_class_storage = $codebase->classlike_storage_provider->get(
                $implementer_called_class_name,
            );

            if (isset(
                $implementer_called_class_storage->template_extended_params[$implementer_classlike_storage->name],
            )) {
                self::transformTemplates(
                    $implementer_called_class_storage->template_extended_params,
                    $implementer_classlike_storage->name,
                    $implementer_method_storage_param_type,
                    $codebase,
                );

                self::transformTemplates(
                    $implementer_called_class_storage->template_extended_params,
                    $guide_class_name,
                    $guide_method_storage_param_type,
                    $codebase,
                );
            }
        }

        $builder = $implementer_method_storage_param_type->getBuilder();
        foreach ($builder->getAtomicTypes() as $k => $t) {
            if ($t instanceof TTemplateParam
                && str_starts_with($t->defining_class, 'fn-')
            ) {
                $builder->removeType($k);

                foreach ($t->as->getAtomicTypes() as $as_t) {
                    $builder->addType($as_t);
                }
            }
        }
        $implementer_method_storage_param_type = $builder->freeze();

        $builder = $guide_method_storage_param_type->getBuilder();
        foreach ($builder->getAtomicTypes() as $k => $t) {
            if ($t instanceof TTemplateParam
                && str_starts_with($t->defining_class, 'fn-')
            ) {
                $builder->removeType($k);

                foreach ($t->as->getAtomicTypes() as $as_t) {
                    $builder->addType($as_t);
                }
            }
        }
        $guide_method_storage_param_type = $builder->freeze();
        unset($builder);

        if ($implementer_classlike_storage->template_extended_params) {
            self::transformTemplates(
                $implementer_classlike_storage->template_extended_params,
                $guide_class_name,
                $guide_method_storage_param_type,
                $codebase,
            );
        }

        $union_comparison_results = new TypeComparisonResult();

        if (!UnionTypeComparator::isContainedBy(
            $codebase,
            $guide_method_storage_param_type,
            $implementer_method_storage_param_type,
            !$guide_classlike_storage->user_defined,
            !$guide_classlike_storage->user_defined,
            $union_comparison_results,
        )) {
            // is the declared return type more specific than the inferred one?
            if ($union_comparison_results->type_coerced) {
                if ($guide_classlike_storage->user_defined) {
                    IssueBuffer::maybeAdd(
                        new MoreSpecificImplementedParamType(
                            'Argument ' . ($i + 1) . ' of ' . $cased_implementer_method_id
                                . ' has the more specific type \'' .
                                $implementer_method_storage_param_type->getId() . '\', expecting \'' .
                                $guide_method_storage_param_type->getId() . '\' as defined by ' .
                                $cased_guide_method_id,
                            $implementer_method_storage->params[$i]->location
                                ?: $code_location,
                        ),
                        $suppressed_issues + $implementer_classlike_storage->suppressed_issues,
                    );
                }
            } else {
                if (UnionTypeComparator::isContainedBy(
                    $codebase,
                    $implementer_method_storage_param_type,
                    $guide_method_storage_param_type,
                    !$guide_classlike_storage->user_defined,
                    !$guide_classlike_storage->user_defined,
                )) {
                    IssueBuffer::maybeAdd(
                        new MoreSpecificImplementedParamType(
                            'Argument ' . ($i + 1) . ' of ' . $cased_implementer_method_id
                            . ' has the more specific type \'' .
                            $implementer_method_storage_param_type->getId() . '\', expecting \'' .
                            $guide_method_storage_param_type->getId() . '\' as defined by ' .
                            $cased_guide_method_id,
                            $implementer_method_storage->params[$i]->location
                                ?: $code_location,
                        ),
                        $suppressed_issues + $implementer_classlike_storage->suppressed_issues,
                    );
                } elseif ($guide_class_name == $implementer_called_class_name) {
                    IssueBuffer::maybeAdd(
                        new MismatchingDocblockParamType(
                            'Argument ' . ($i + 1) . ' of ' . $cased_implementer_method_id
                            . ' has wrong type \'' .
                            $implementer_method_storage_param_type->getId() . '\' in @method annotation, expecting \'' .
                            $guide_method_storage_param_type->getId() . '\'',
                            $implementer_method_storage->params[$i]->location
                                ?: $code_location,
                        ),
                        $suppressed_issues + $implementer_classlike_storage->suppressed_issues,
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new ImplementedParamTypeMismatch(
                            'Argument ' . ($i + 1) . ' of ' . $cased_implementer_method_id
                            . ' has wrong type \'' .
                            $implementer_method_storage_param_type->getId() . '\', expecting \'' .
                            $guide_method_storage_param_type->getId() . '\' as defined by ' .
                            $cased_guide_method_id,
                            $implementer_method_storage->params[$i]->location
                                ?: $code_location,
                        ),
                        $suppressed_issues + $implementer_classlike_storage->suppressed_issues,
                    );
                }
            }
        }
    }

    /**
     * @param  string[]         $suppressed_issues
     */
    private static function compareMethodSignatureReturnTypes(
        Codebase $codebase,
        ClassLikeStorage $guide_classlike_storage,
        ClassLikeStorage $implementer_classlike_storage,
        MethodStorage $guide_method_storage,
        MethodStorage $implementer_method_storage,
        Union $guide_signature_return_type,
        string $cased_guide_method_id,
        string $implementer_called_class_name,
        string $cased_implementer_method_id,
        CodeLocation $code_location,
        array $suppressed_issues,
    ): void {
        $guide_signature_return_type = TypeExpander::expandUnion(
            $codebase,
            $guide_signature_return_type,
            $guide_classlike_storage->is_trait && $guide_method_storage->abstract
                ? $implementer_classlike_storage->name
                : $guide_classlike_storage->name,
            ($guide_classlike_storage->is_trait && $guide_method_storage->abstract)
                || $guide_classlike_storage->final
                ? $implementer_classlike_storage->name
                : $guide_classlike_storage->name,
            $guide_classlike_storage->is_trait && $guide_method_storage->abstract
                ? $implementer_classlike_storage->parent_class
                : $guide_classlike_storage->parent_class,
            true,
            true,
            $implementer_method_storage->final,
        );

        $implementer_signature_return_type = $implementer_method_storage->signature_return_type
            ? TypeExpander::expandUnion(
                $codebase,
                $implementer_method_storage->signature_return_type,
                $implementer_classlike_storage->is_trait
                    ? $implementer_called_class_name
                    : $implementer_classlike_storage->name,
                $implementer_classlike_storage->is_trait
                    ? $implementer_called_class_name
                    : $implementer_classlike_storage->name,
                $implementer_classlike_storage->parent_class,
            ) : null;

        $is_contained_by = $codebase->analysis_php_version_id >= 7_04_00
            && $implementer_signature_return_type
            ? UnionTypeComparator::isContainedBy(
                $codebase,
                $implementer_signature_return_type,
                $guide_signature_return_type,
            )
            : UnionTypeComparator::isContainedByInPhp($implementer_signature_return_type, $guide_signature_return_type);

        if (!$is_contained_by) {
            if ($implementer_signature_return_type === null
                && array_filter(
                    $implementer_method_storage->attributes,
                    static fn(AttributeStorage $s): bool => $s->fq_class_name === 'ReturnTypeWillChange',
                )) {
                // no error if return type will change and no signature set at all
            } elseif ($codebase->analysis_php_version_id >= 8_00_00
                      || $guide_classlike_storage->is_trait === $implementer_classlike_storage->is_trait
                      || !in_array($guide_classlike_storage->name, $implementer_classlike_storage->used_traits)
                      || $implementer_method_storage->defining_fqcln !== $implementer_classlike_storage->name
                      || (!$implementer_method_storage->abstract
                          && !$guide_method_storage->abstract)
            ) {
                IssueBuffer::maybeAdd(
                    new MethodSignatureMismatch(
                        'Method ' . $cased_implementer_method_id . ' with return type \''
                            . $implementer_signature_return_type . '\' is different to return type \''
                            . $guide_signature_return_type . '\' of inherited method ' . $cased_guide_method_id,
                        $code_location,
                    ),
                    $suppressed_issues + $implementer_classlike_storage->suppressed_issues,
                );
            } else {
                IssueBuffer::maybeAdd(
                    new TraitMethodSignatureMismatch(
                        'Method ' . $cased_implementer_method_id . ' with return type \''
                            . $implementer_signature_return_type . '\' is different to return type \''
                            . $guide_signature_return_type . '\' of inherited method ' . $cased_guide_method_id,
                        $code_location,
                    ),
                    $suppressed_issues + $implementer_classlike_storage->suppressed_issues,
                );
            }
        }
    }

    /**
     * @param  string[]         $suppressed_issues
     */
    private static function compareMethodDocblockReturnTypes(
        Codebase $codebase,
        ClassLikeStorage $guide_classlike_storage,
        ClassLikeStorage $implementer_classlike_storage,
        MethodStorage $implementer_method_storage,
        Union $guide_return_type,
        Union $implementer_return_type,
        string $cased_guide_method_id,
        string $implementer_called_class_name,
        ?MethodIdentifier $implementer_declaring_method_id,
        CodeLocation $code_location,
        array $suppressed_issues,
    ): void {
        $implementer_method_storage_return_type = TypeExpander::expandUnion(
            $codebase,
            $implementer_return_type,
            $implementer_classlike_storage->is_trait
                ? $implementer_called_class_name
                : $implementer_classlike_storage->name,
            $implementer_called_class_name,
            $implementer_classlike_storage->parent_class,
        );

        $guide_method_storage_return_type = TypeExpander::expandUnion(
            $codebase,
            $guide_return_type,
            $guide_classlike_storage->is_trait
                ? $implementer_classlike_storage->name
                : $guide_classlike_storage->name,
            $guide_classlike_storage->is_trait
                || $implementer_method_storage->final
                ? $implementer_called_class_name
                : $guide_classlike_storage->name,
            $guide_classlike_storage->parent_class,
            true,
            true,
            $implementer_method_storage->final,
        );

        $guide_class_name = $guide_classlike_storage->name;

        if ($implementer_classlike_storage->template_extended_params) {
            self::transformTemplates(
                $implementer_classlike_storage->template_extended_params,
                $guide_class_name,
                $guide_method_storage_return_type,
                $codebase,
            );

            if ($implementer_method_storage->defining_fqcln) {
                self::transformTemplates(
                    $implementer_classlike_storage->template_extended_params,
                    $implementer_method_storage->defining_fqcln,
                    $implementer_method_storage_return_type,
                    $codebase,
                );
            }
        }

        if ($implementer_classlike_storage->is_trait) {
            $implementer_called_class_storage = $codebase->classlike_storage_provider->get(
                $implementer_called_class_name,
            );

            if ($implementer_called_class_storage->template_extended_params) {
                self::transformTemplates(
                    $implementer_called_class_storage->template_extended_params,
                    $implementer_classlike_storage->name,
                    $implementer_method_storage_return_type,
                    $codebase,
                );

                self::transformTemplates(
                    $implementer_called_class_storage->template_extended_params,
                    $guide_class_name,
                    $guide_method_storage_return_type,
                    $codebase,
                );
            }
        }

        // treat void as null when comparing against docblock implementer
        if ($implementer_method_storage_return_type->isVoid()) {
            $implementer_method_storage_return_type = Type::getNull();
        }

        if ($guide_method_storage_return_type->isVoid()) {
            $guide_method_storage_return_type = Type::getNull();
        }

        $union_comparison_results = new TypeComparisonResult();

        if (!UnionTypeComparator::isContainedBy(
            $codebase,
            $implementer_method_storage_return_type,
            $guide_method_storage_return_type,
            false,
            false,
            $union_comparison_results,
        )) {
            // is the declared return type more specific than the inferred one?
            if ($union_comparison_results->type_coerced) {
                IssueBuffer::maybeAdd(
                    new LessSpecificImplementedReturnType(
                        'The inherited return type \'' . $guide_method_storage_return_type->getId()
                            . '\' for ' . $cased_guide_method_id . ' is more specific than the implemented '
                            . 'return type for ' . $implementer_declaring_method_id . ' \''
                            . $implementer_method_storage_return_type->getId() . '\'',
                        $implementer_method_storage->return_type_location
                            ?: $code_location,
                    ),
                    $suppressed_issues + $implementer_classlike_storage->suppressed_issues,
                );
            } elseif ($guide_class_name == $implementer_called_class_name) {
                IssueBuffer::maybeAdd(
                    new MismatchingDocblockReturnType(
                        'The inherited return type \'' . $guide_method_storage_return_type->getId()
                        . '\' for ' . $cased_guide_method_id . ' is different to the corresponding '
                        . '@method annotation \'' . $implementer_method_storage_return_type->getId() . '\'',
                        $implementer_method_storage->return_type_location
                            ?: $code_location,
                    ),
                    $suppressed_issues + $implementer_classlike_storage->suppressed_issues,
                );
            } else {
                IssueBuffer::maybeAdd(
                    new ImplementedReturnTypeMismatch(
                        'The inherited return type \'' . $guide_method_storage_return_type->getId()
                            . '\' for ' . $cased_guide_method_id . ' is different to the implemented '
                            . 'return type for ' . $implementer_declaring_method_id . ' \''
                            . $implementer_method_storage_return_type->getId() . '\'',
                        $implementer_method_storage->return_type_location
                            ?: $code_location,
                    ),
                    $suppressed_issues + $implementer_classlike_storage->suppressed_issues,
                );
            }
        }
    }

    /**
     * @param  array<string, array<string, Union>>  $template_extended_params
     */
    private static function transformTemplates(
        array $template_extended_params,
        string $base_class_name,
        Union &$templated_type,
        Codebase $codebase,
    ): void {
        if (isset($template_extended_params[$base_class_name])) {
            $map = $template_extended_params[$base_class_name];

            $template_types = [];

            foreach ($map as $key => $mapped_type) {
                $new_bases = [];

                foreach ($mapped_type->getTemplateTypes() as $mapped_atomic_type) {
                    if ($mapped_atomic_type->defining_class === $base_class_name) {
                        continue;
                    }

                    $new_bases[] = $mapped_atomic_type->defining_class;
                }

                if ($new_bases) {
                    foreach ($new_bases as $new_base_class_name) {
                        self::transformTemplates(
                            $template_extended_params,
                            $new_base_class_name,
                            $mapped_type,
                            $codebase,
                        );
                    }
                }

                $template_types[$key][$base_class_name] = $mapped_type;
            }

            $template_result = new TemplateResult([], $template_types);

            $templated_type = TemplateInferredTypeReplacer::replace(
                $templated_type,
                $template_result,
                $codebase,
            );
        }
    }
}
