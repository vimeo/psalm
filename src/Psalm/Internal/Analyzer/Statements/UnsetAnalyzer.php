<?php

namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyMixed;
use Psalm\Type\Union;

use function count;

/**
 * @internal
 */
class UnsetAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Unset_ $stmt,
        Context $context
    ): void {
        $context->inside_unset = true;

        foreach ($stmt->vars as $var) {
            $was_inside_general_use = $context->inside_general_use;
            $context->inside_general_use = true;

            ExpressionAnalyzer::analyze($statements_analyzer, $var, $context);

            $context->inside_general_use = $was_inside_general_use;

            $var_id = ExpressionIdentifier::getExtendedVarId(
                $var,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer
            );

            if ($var_id) {
                $context->remove($var_id);
                unset($context->references_possibly_from_confusing_scope[$var_id]);
            }

            if ($var instanceof PhpParser\Node\Expr\ArrayDimFetch && $var->dim) {
                $root_var_id = ExpressionIdentifier::getExtendedVarId(
                    $var->var,
                    $statements_analyzer->getFQCLN(),
                    $statements_analyzer
                );

                $key_type = $statements_analyzer->node_data->getType($var->dim);
                if ($root_var_id && isset($context->vars_in_scope[$root_var_id]) && $key_type) {
                    $root_types = [];

                    foreach ($context->vars_in_scope[$root_var_id]->getAtomicTypes() as $atomic_root_type) {
                        if ($atomic_root_type instanceof TKeyedArray) {
                            $key_value = null;
                            if ($key_type->isSingleIntLiteral()) {
                                $key_value = $key_type->getSingleIntLiteral()->value;
                            } elseif ($key_type->isSingleStringLiteral()) {
                                $key_value = $key_type->getSingleStringLiteral()->value;
                            }
                            if ($key_value !== null) {
                                $properties = $atomic_root_type->properties;
                                $is_list = $atomic_root_type->is_list;
                                if (isset($properties[$key_value])) {
                                    if ($is_list
                                        && $key_value !== count($properties)-1
                                    ) {
                                        $is_list = false;
                                    }
                                    unset($properties[$key_value]);
                                }

                                if (!$properties) {
                                    if ($atomic_root_type->previous_value_type) {
                                        $root_types [] =
                                            new TArray([
                                                $atomic_root_type->previous_key_type
                                                    ? clone $atomic_root_type->previous_key_type
                                                    : new Union([new TArrayKey]),
                                                clone $atomic_root_type->previous_value_type,
                                            ])
                                        ;
                                    } else {
                                        $root_types [] =
                                            new TArray([
                                                new Union([new TNever]),
                                                new Union([new TNever]),
                                            ])
                                        ;
                                    }
                                } else {
                                    $root_types []= new TKeyedArray(
                                        $properties,
                                        null,
                                        $atomic_root_type->sealed,
                                        $atomic_root_type->previous_key_type,
                                        $atomic_root_type->previous_value_type,
                                        $is_list
                                    );
                                }
                            } else {
                                $properties = [];
                                foreach ($atomic_root_type->properties as $key => $type) {
                                    $properties[$key] = clone $type;
                                    /** @psalm-suppress InaccessibleProperty We just cloned this type */
                                    $properties[$key]->possibly_undefined = true;
                                }
                                $root_types []= new TKeyedArray(
                                    $properties,
                                    null,
                                    false,
                                    $atomic_root_type->previous_key_type,
                                    $atomic_root_type->previous_value_type,
                                    false,
                                );
                            }
                        } elseif ($atomic_root_type instanceof TNonEmptyArray) {
                            $root_types []= new TArray($atomic_root_type->type_params);
                        } elseif ($atomic_root_type instanceof TNonEmptyMixed) {
                            $root_types []= new TMixed();
                        } elseif ($atomic_root_type instanceof TList) {
                            $root_types []=
                                new TArray([
                                    Type::getInt(),
                                    $atomic_root_type->type_param
                                ])
                            ;
                        }
                    }

                    $context->vars_in_scope[$root_var_id] = new Union($root_types);

                    $context->removeVarFromConflictingClauses(
                        $root_var_id,
                        $context->vars_in_scope[$root_var_id],
                        $statements_analyzer
                    );
                }
            }
        }

        $context->inside_unset = false;
    }
}
