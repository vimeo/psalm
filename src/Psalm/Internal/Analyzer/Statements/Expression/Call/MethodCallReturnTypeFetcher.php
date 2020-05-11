<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\CallMap;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Type;
use Psalm\Type\Atomic\TGenericObject;
use function strtolower;
use Psalm\Internal\Taint\Source;

class MethodCallReturnTypeFetcher
{
    /**
     * @param  Type\Atomic\TNamedObject|Type\Atomic\TTemplateParam  $static_type
     * @param array<int, PhpParser\Node\Arg> $args
     */
    public static function fetch(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Expr\MethodCall $stmt,
        Context $context,
        MethodIdentifier $method_id,
        ?MethodIdentifier $declaring_method_id,
        string $cased_method_id,
        Type\Atomic $lhs_type_part,
        ?Type\Atomic $static_type,
        array $args,
        AtomicMethodCallAnalysisResult $result,
        TemplateResult $template_result
    ) : ?Type\Union {
        $call_map_id = $declaring_method_id ?: $method_id;

        $fq_class_name = $method_id->fq_class_name;
        $method_name = $method_id->method_name;

        if ($codebase->methods->return_type_provider->has($fq_class_name)) {
            $return_type_candidate = $codebase->methods->return_type_provider->getReturnType(
                $statements_analyzer,
                $fq_class_name,
                $method_name,
                $stmt->args,
                $context,
                new CodeLocation($statements_analyzer->getSource(), $stmt->name),
                $lhs_type_part instanceof TGenericObject ? $lhs_type_part->type_params : null
            );

            if ($return_type_candidate) {
                return $return_type_candidate;
            }
        }

        if ($declaring_method_id && $declaring_method_id !== $method_id) {
            $declaring_fq_class_name = $declaring_method_id->fq_class_name;
            $declaring_method_name = $declaring_method_id->method_name;

            if ($codebase->methods->return_type_provider->has($declaring_fq_class_name)) {
                $return_type_candidate = $codebase->methods->return_type_provider->getReturnType(
                    $statements_analyzer,
                    $declaring_fq_class_name,
                    $declaring_method_name,
                    $stmt->args,
                    $context,
                    new CodeLocation($statements_analyzer->getSource(), $stmt->name),
                    $lhs_type_part instanceof TGenericObject ? $lhs_type_part->type_params : null,
                    $fq_class_name,
                    $method_name
                );

                if ($return_type_candidate) {
                    return $return_type_candidate;
                }
            }
        }

        $class_storage = $codebase->methods->getClassLikeStorageForMethod($method_id);

        if (CallMap::inCallMap((string) $call_map_id)) {
            if (($template_result->upper_bounds || $class_storage->stubbed)
                && isset($class_storage->methods[$method_id->method_name])
                && ($method_storage = $class_storage->methods[$method_id->method_name])
                && $method_storage->return_type
            ) {
                $return_type_candidate = clone $method_storage->return_type;

                if ($template_result->upper_bounds) {
                    $return_type_candidate->replaceTemplateTypesWithArgTypes(
                        $template_result,
                        $codebase
                    );
                }
            } else {
                $callmap_callables = CallMap::getCallablesFromCallMap((string) $call_map_id);

                if (!$callmap_callables || $callmap_callables[0]->return_type === null) {
                    throw new \UnexpectedValueException('Shouldn’t get here');
                }

                $return_type_candidate = $callmap_callables[0]->return_type;
            }

            if ($return_type_candidate->isFalsable()) {
                $return_type_candidate->ignore_falsable_issues = true;
            }

            $return_type_candidate = \Psalm\Internal\Type\TypeExpander::expandUnion(
                $codebase,
                $return_type_candidate,
                $fq_class_name,
                $static_type,
                $class_storage->parent_class
            );
        } else {
            $self_fq_class_name = $fq_class_name;

            $return_type_candidate = $codebase->methods->getMethodReturnType(
                $method_id,
                $self_fq_class_name,
                $statements_analyzer,
                $args
            );

            if ($return_type_candidate) {
                $return_type_candidate = clone $return_type_candidate;

                if ($template_result->template_types) {
                    $bindable_template_types = $return_type_candidate->getTemplateTypes();

                    foreach ($bindable_template_types as $template_type) {
                        if ($template_type->defining_class !== $fq_class_name
                            && !isset(
                                $template_result->upper_bounds
                                    [$template_type->param_name]
                                    [$template_type->defining_class]
                            )
                        ) {
                            if ($template_type->param_name === 'TFunctionArgCount') {
                                $template_result->upper_bounds[$template_type->param_name] = [
                                    'fn-' . $method_id => [Type::getInt(false, \count($stmt->args)), 0]
                                ];
                            } else {
                                $template_result->upper_bounds[$template_type->param_name] = [
                                    ($template_type->defining_class) => [Type::getEmpty(), 0]
                                ];
                            }
                        }
                    }
                }

                if ($template_result->upper_bounds) {
                    $return_type_candidate = \Psalm\Internal\Type\TypeExpander::expandUnion(
                        $codebase,
                        $return_type_candidate,
                        null,
                        null,
                        null
                    );

                    $return_type_candidate->replaceTemplateTypesWithArgTypes(
                        $template_result,
                        $codebase
                    );
                }

                $return_type_candidate = \Psalm\Internal\Type\TypeExpander::expandUnion(
                    $codebase,
                    $return_type_candidate,
                    $self_fq_class_name,
                    $static_type,
                    $class_storage->parent_class,
                    true,
                    false,
                    $static_type instanceof Type\Atomic\TNamedObject
                        && $codebase->classlike_storage_provider->get($static_type->value)->final
                );

                if ($codebase->taint) {
                    $return_type_candidate->sources = [
                        new Source(
                            strtolower((string) $method_id),
                            $cased_method_id,
                            new CodeLocation($statements_analyzer, $stmt->name)
                        )
                    ];
                }

                $return_type_location = $codebase->methods->getMethodReturnTypeLocation(
                    $method_id,
                    $secondary_return_type_location
                );

                if ($secondary_return_type_location) {
                    $return_type_location = $secondary_return_type_location;
                }

                $config = \Psalm\Config::getInstance();

                // only check the type locally if it's defined externally
                if ($return_type_location && !$config->isInProjectDirs($return_type_location->file_path)) {
                    $return_type_candidate->check(
                        $statements_analyzer,
                        new CodeLocation($statements_analyzer, $stmt),
                        $statements_analyzer->getSuppressedIssues(),
                        $context->phantom_classes,
                        true,
                        false,
                        false,
                        $context->calling_method_id
                    );
                }
            } else {
                $result->returns_by_ref =
                    $result->returns_by_ref
                        || $codebase->methods->getMethodReturnsByRef($method_id);
            }
        }

        return $return_type_candidate;
    }
}
