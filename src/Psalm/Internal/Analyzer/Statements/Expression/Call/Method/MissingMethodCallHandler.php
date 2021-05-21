<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Call\Method;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ArgumentsAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\MethodIdentifier;
use Psalm\Node\Expr\VirtualArray;
use Psalm\Node\Expr\VirtualArrayItem;
use Psalm\Node\Scalar\VirtualString;
use Psalm\Node\VirtualArg;
use Psalm\Type;
use function array_map;
use function array_merge;

class MissingMethodCallHandler
{
    public static function handleMagicMethod(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Expr\MethodCall $stmt,
        MethodIdentifier $method_id,
        \Psalm\Storage\ClassLikeStorage $class_storage,
        Context $context,
        \Psalm\Config $config,
        ?Type\Union $all_intersection_return_type,
        AtomicMethodCallAnalysisResult $result
    ) : ?AtomicCallContext {
        $fq_class_name = $method_id->fq_class_name;
        $method_name_lc = $method_id->method_name;

        if ($codebase->methods->return_type_provider->has($fq_class_name)) {
            $return_type_candidate = $codebase->methods->return_type_provider->getReturnType(
                $statements_analyzer,
                $method_id->fq_class_name,
                $method_id->method_name,
                $stmt->args,
                $context,
                new CodeLocation($statements_analyzer->getSource(), $stmt->name)
            );

            if ($return_type_candidate) {
                if ($all_intersection_return_type) {
                    $return_type_candidate = Type::intersectUnionTypes(
                        $all_intersection_return_type,
                        $return_type_candidate,
                        $codebase
                    ) ?: Type::getMixed();
                }

                if (!$result->return_type) {
                    $result->return_type = $return_type_candidate;
                } else {
                    $result->return_type = Type::combineUnionTypes(
                        $return_type_candidate,
                        $result->return_type,
                        $codebase
                    );
                }

                CallAnalyzer::checkMethodArgs(
                    $method_id,
                    $stmt->args,
                    null,
                    $context,
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                    $statements_analyzer
                );

                return null;
            }
        }

        if (isset($class_storage->pseudo_methods[$method_name_lc])) {
            $result->has_valid_method_call_type = true;
            $result->existent_method_ids[] = $method_id->__toString();

            $pseudo_method_storage = $class_storage->pseudo_methods[$method_name_lc];

            ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->args,
                $pseudo_method_storage->params,
                (string) $method_id,
                true,
                $context
            );

            ArgumentsAnalyzer::checkArgumentsMatch(
                $statements_analyzer,
                $stmt->args,
                null,
                $pseudo_method_storage->params,
                $pseudo_method_storage,
                null,
                null,
                new CodeLocation($statements_analyzer, $stmt),
                $context
            );

            if ($pseudo_method_storage->return_type) {
                $return_type_candidate = clone $pseudo_method_storage->return_type;

                $return_type_candidate = \Psalm\Internal\Type\TypeExpander::expandUnion(
                    $codebase,
                    $return_type_candidate,
                    $fq_class_name,
                    $fq_class_name,
                    $class_storage->parent_class
                );

                if ($all_intersection_return_type) {
                    $return_type_candidate = Type::intersectUnionTypes(
                        $all_intersection_return_type,
                        $return_type_candidate,
                        $codebase
                    ) ?: Type::getMixed();
                }

                if (!$result->return_type) {
                    $result->return_type = $return_type_candidate;
                } else {
                    $result->return_type = Type::combineUnionTypes(
                        $return_type_candidate,
                        $result->return_type,
                        $codebase
                    );
                }

                return null;
            }
        } elseif ($all_intersection_return_type == null) {
            ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->args,
                null,
                null,
                true,
                $context
            );

            if ($class_storage->sealed_methods || $config->seal_all_methods) {
                $result->non_existent_magic_method_ids[] = $method_id->__toString();

                return null;
            }
        }

        $result->has_valid_method_call_type = true;
        $result->existent_method_ids[] = $method_id->__toString();

        $array_values = array_map(
            /**
             * @return PhpParser\Node\Expr\ArrayItem
             */
            function (PhpParser\Node\Arg $arg): PhpParser\Node\Expr\ArrayItem {
                return new VirtualArrayItem(
                    $arg->value,
                    null,
                    false,
                    $arg->getAttributes()
                );
            },
            $stmt->args
        );

        $statements_analyzer->node_data = clone $statements_analyzer->node_data;

        return new AtomicCallContext(
            new MethodIdentifier($fq_class_name, '__call'),
            [
                new VirtualArg(
                    new VirtualString($method_name_lc),
                    false,
                    false,
                    $stmt->getAttributes()
                ),
                new VirtualArg(
                    new VirtualArray(
                        $array_values,
                        $stmt->getAttributes()
                    ),
                    false,
                    false,
                    $stmt->getAttributes()
                ),
            ]
        );
    }

    /**
     * @param array<string> $all_intersection_existent_method_ids
     */
    public static function handleMissingOrMagicMethod(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Expr\MethodCall $stmt,
        MethodIdentifier $method_id,
        bool $is_interface,
        Context $context,
        \Psalm\Config $config,
        ?Type\Union $all_intersection_return_type,
        array $all_intersection_existent_method_ids,
        ?string $intersection_method_id,
        string $cased_method_id,
        AtomicMethodCallAnalysisResult $result
    ) : void {
        $fq_class_name = $method_id->fq_class_name;
        $method_name_lc = $method_id->method_name;

        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

        if (($is_interface || $config->use_phpdoc_method_without_magic_or_parent)
            && isset($class_storage->pseudo_methods[$method_name_lc])
        ) {
            $result->has_valid_method_call_type = true;
            $result->existent_method_ids[] = $method_id->__toString();

            $pseudo_method_storage = $class_storage->pseudo_methods[$method_name_lc];

            if (ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->args,
                $pseudo_method_storage->params,
                (string) $method_id,
                true,
                $context
            ) === false) {
                return;
            }

            if (ArgumentsAnalyzer::checkArgumentsMatch(
                $statements_analyzer,
                $stmt->args,
                null,
                $pseudo_method_storage->params,
                $pseudo_method_storage,
                null,
                null,
                new CodeLocation($statements_analyzer, $stmt->name),
                $context
            ) === false) {
                return;
            }

            if ($pseudo_method_storage->return_type) {
                $return_type_candidate = clone $pseudo_method_storage->return_type;

                if ($all_intersection_return_type) {
                    $return_type_candidate = Type::intersectUnionTypes(
                        $all_intersection_return_type,
                        $return_type_candidate,
                        $codebase
                    ) ?: Type::getMixed();
                }

                $return_type_candidate = \Psalm\Internal\Type\TypeExpander::expandUnion(
                    $codebase,
                    $return_type_candidate,
                    $fq_class_name,
                    $fq_class_name,
                    $class_storage->parent_class,
                    true,
                    false,
                    $class_storage->final
                );

                if (!$result->return_type) {
                    $result->return_type = $return_type_candidate;
                } else {
                    $result->return_type = Type::combineUnionTypes($return_type_candidate, $result->return_type);
                }

                return;
            }

            $result->return_type = Type::getMixed();

            return;
        }

        if (ArgumentsAnalyzer::analyze(
            $statements_analyzer,
            $stmt->args,
            null,
            null,
            true,
            $context
        ) === false) {
            return;
        }

        if ($all_intersection_return_type && $all_intersection_existent_method_ids) {
            $result->existent_method_ids = array_merge(
                $result->existent_method_ids,
                $all_intersection_existent_method_ids
            );

            if (!$result->return_type) {
                $result->return_type = $all_intersection_return_type;
            } else {
                $result->return_type = Type::combineUnionTypes($all_intersection_return_type, $result->return_type);
            }

            return;
        }

        if ((!$is_interface && !$config->use_phpdoc_method_without_magic_or_parent)
            || !isset($class_storage->pseudo_methods[$method_name_lc])
        ) {
            if ($is_interface) {
                $result->non_existent_interface_method_ids[] = $intersection_method_id ?: $cased_method_id;
            } else {
                $result->non_existent_class_method_ids[] = $intersection_method_id ?: $cased_method_id;
            }
        }
    }
}
