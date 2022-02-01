<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\Statements\Expression\Call\Method\MethodCallReturnTypeFetcher;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\VariableUseGraph;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Issue\InvalidCast;
use Psalm\Issue\PossiblyInvalidCast;
use Psalm\Issue\RedundantCast;
use Psalm\Issue\RedundantCastGivenDocblockType;
use Psalm\Issue\UnrecognizedExpression;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonspecificLiteralInt;
use Psalm\Type\Atomic\TNonspecificLiteralString;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function array_merge;
use function array_pop;
use function array_values;
use function count;
use function get_class;

class CastAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Cast $stmt,
        Context $context
    ): bool {
        if ($stmt instanceof PhpParser\Node\Expr\Cast\Int_) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $as_int = true;
            $valid_int_type = null;
            $maybe_type = $statements_analyzer->node_data->getType($stmt->expr);

            if ($maybe_type) {
                if ($maybe_type->isInt()) {
                    $valid_int_type = $maybe_type;
                    if (!$maybe_type->from_calculation) {
                        self::handleRedundantCast($maybe_type, $statements_analyzer, $stmt);
                    }
                }

                if (count($maybe_type->getAtomicTypes()) === 1
                    && $maybe_type->getSingleAtomic() instanceof TBool) {
                    $as_int = false;
                    $type = new Union([
                        new TLiteralInt(0),
                        new TLiteralInt(1),
                    ]);

                    if ($statements_analyzer->data_flow_graph instanceof VariableUseGraph
                    ) {
                        $type->parent_nodes = $maybe_type->parent_nodes;
                    }

                    $statements_analyzer->node_data->setType($stmt, $type);
                }
            }

            if ($as_int) {
                $type = $valid_int_type ?? Type::getInt();

                if ($statements_analyzer->data_flow_graph instanceof VariableUseGraph
                ) {
                    $type->parent_nodes = $maybe_type->parent_nodes ?? [];
                }

                $statements_analyzer->node_data->setType($stmt, $type);
            }

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Double) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $maybe_type = $statements_analyzer->node_data->getType($stmt->expr);

            if ($maybe_type) {
                if ($maybe_type->isFloat()) {
                    self::handleRedundantCast($maybe_type, $statements_analyzer, $stmt);
                }
            }

            $type = Type::getFloat();

            if ($statements_analyzer->data_flow_graph instanceof VariableUseGraph
            ) {
                $type->parent_nodes = $maybe_type->parent_nodes ?? [];
            }

            $statements_analyzer->node_data->setType($stmt, $type);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Bool_) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $maybe_type = $statements_analyzer->node_data->getType($stmt->expr);

            if ($maybe_type) {
                if ($maybe_type->isBool()) {
                    self::handleRedundantCast($maybe_type, $statements_analyzer, $stmt);
                }
            }

            $type = Type::getBool();

            if ($statements_analyzer->data_flow_graph instanceof VariableUseGraph
            ) {
                $type->parent_nodes = $maybe_type->parent_nodes ?? [];
            }

            $statements_analyzer->node_data->setType($stmt, $type);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\String_) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr);

            if ($stmt_expr_type) {
                if ($stmt_expr_type->isString()) {
                    self::handleRedundantCast($stmt_expr_type, $statements_analyzer, $stmt);
                }

                $stmt_type = self::castStringAttempt(
                    $statements_analyzer,
                    $context,
                    $stmt_expr_type,
                    $stmt->expr,
                    true
                );
            } else {
                $stmt_type = Type::getString();
            }

            $statements_analyzer->node_data->setType($stmt, $stmt_type);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Object_) {
            $was_inside_general_use = $context->inside_general_use;
            $context->inside_general_use = true;
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                $context->inside_general_use = $was_inside_general_use;

                return false;
            }
            $context->inside_general_use = $was_inside_general_use;

            $type = new Union([new TNamedObject('stdClass')]);

            $maybe_type = $statements_analyzer->node_data->getType($stmt->expr);

            if ($statements_analyzer->data_flow_graph instanceof VariableUseGraph
            ) {
                $type->parent_nodes = $maybe_type->parent_nodes ?? [];
            }

            $statements_analyzer->node_data->setType($stmt, $type);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Array_) {
            $was_inside_general_use = $context->inside_general_use;
            $context->inside_general_use = true;
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                $context->inside_general_use = $was_inside_general_use;

                return false;
            }
            $context->inside_general_use = $was_inside_general_use;

            $permissible_atomic_types = [];
            $all_permissible = false;

            if ($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr)) {
                if ($stmt_expr_type->isArray()) {
                    self::handleRedundantCast($stmt_expr_type, $statements_analyzer, $stmt);
                }

                $all_permissible = true;

                foreach ($stmt_expr_type->getAtomicTypes() as $type) {
                    if ($type instanceof Scalar) {
                        $keyed_array = new TKeyedArray([new Union([$type])]);
                        $keyed_array->is_list = true;
                        $keyed_array->sealed = true;
                        $permissible_atomic_types[] = $keyed_array;
                    } elseif ($type instanceof TNull) {
                        $permissible_atomic_types[] = new TArray([Type::getEmpty(), Type::getEmpty()]);
                    } elseif ($type instanceof TArray
                        || $type instanceof TList
                        || $type instanceof TKeyedArray
                    ) {
                        $permissible_atomic_types[] = clone $type;
                    } else {
                        $all_permissible = false;
                        break;
                    }
                }
            }

            if ($permissible_atomic_types && $all_permissible) {
                $type = TypeCombiner::combine($permissible_atomic_types);
            } else {
                $type = Type::getArray();
            }

            if ($statements_analyzer->data_flow_graph) {
                $type->parent_nodes = $stmt_expr_type->parent_nodes ?? [];
            }

            $statements_analyzer->node_data->setType($stmt, $type);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Unset_
            && $statements_analyzer->getCodebase()->php_major_version < 8
        ) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $statements_analyzer->node_data->setType($stmt, Type::getNull());

            return true;
        }

        IssueBuffer::maybeAdd(
            new UnrecognizedExpression(
                'Psalm does not understand the cast ' . get_class($stmt),
                new CodeLocation($statements_analyzer->getSource(), $stmt)
            ),
            $statements_analyzer->getSuppressedIssues()
        );

        return false;
    }

    public static function castStringAttempt(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        Union $stmt_type,
        PhpParser\Node\Expr $stmt,
        bool $explicit_cast = false
    ): Union {
        $codebase = $statements_analyzer->getCodebase();

        $invalid_casts = [];
        $valid_strings = [];
        $castable_types = [];

        $atomic_types = $stmt_type->getAtomicTypes();

        $parent_nodes = [];

        if ($statements_analyzer->data_flow_graph) {
            $parent_nodes = $stmt_type->parent_nodes;
        }

        while ($atomic_types) {
            $atomic_type = array_pop($atomic_types);

            if ($atomic_type instanceof TFloat
                || $atomic_type instanceof TInt
                || $atomic_type instanceof TNumeric
            ) {
                if ($atomic_type instanceof TLiteralInt) {
                    $castable_types[] = new TLiteralString((string) $atomic_type->value);
                } elseif ($atomic_type instanceof TNonspecificLiteralInt) {
                    $castable_types[] = new TNonspecificLiteralString();
                } else {
                    $castable_types[] = new TNumericString();
                }

                continue;
            }

            if ($atomic_type instanceof TString) {
                $valid_strings[] = $atomic_type;

                continue;
            }

            if ($atomic_type instanceof TNull
                || $atomic_type instanceof TFalse
            ) {
                $valid_strings[] = new TLiteralString('');
                continue;
            }

            if ($atomic_type instanceof TMixed
                || $atomic_type instanceof TResource
                || $atomic_type instanceof Scalar
            ) {
                $castable_types[] = new TString();

                continue;
            }

            if ($atomic_type instanceof TNamedObject
                || $atomic_type instanceof TObjectWithProperties
            ) {
                $intersection_types = [$atomic_type];

                if ($atomic_type->extra_types) {
                    $intersection_types = array_merge($intersection_types, $atomic_type->extra_types);
                }

                foreach ($intersection_types as $intersection_type) {
                    if ($intersection_type instanceof TNamedObject) {
                        $intersection_method_id = new MethodIdentifier(
                            $intersection_type->value,
                            '__tostring'
                        );

                        if ($codebase->methods->methodExists(
                            $intersection_method_id,
                            $context->calling_method_id,
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        )) {
                            $return_type = $codebase->methods->getMethodReturnType(
                                $intersection_method_id,
                                $self_class
                            ) ?? Type::getString();

                            $declaring_method_id = $codebase->methods->getDeclaringMethodId($intersection_method_id);

                            MethodCallReturnTypeFetcher::taintMethodCallResult(
                                $statements_analyzer,
                                $return_type,
                                $stmt,
                                $stmt,
                                [],
                                $intersection_method_id,
                                $declaring_method_id,
                                $intersection_type->value . '::__toString',
                                $context
                            );

                            if ($statements_analyzer->data_flow_graph) {
                                $parent_nodes = array_merge($return_type->parent_nodes, $parent_nodes);
                            }

                            $castable_types = array_merge(
                                $castable_types,
                                array_values($return_type->getAtomicTypes())
                            );

                            continue 2;
                        }
                    }

                    if ($intersection_type instanceof TObjectWithProperties
                        && isset($intersection_type->methods['__toString'])
                    ) {
                        $castable_types[] = new TString();

                        continue 2;
                    }
                }
            }

            if ($atomic_type instanceof TTemplateParam) {
                $atomic_types = array_merge($atomic_types, $atomic_type->as->getAtomicTypes());

                continue;
            }

            $invalid_casts[] = $atomic_type->getId();
        }

        if ($invalid_casts) {
            if ($valid_strings || $castable_types) {
                IssueBuffer::maybeAdd(
                    new PossiblyInvalidCast(
                        $invalid_casts[0] . ' cannot be cast to string',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            } else {
                IssueBuffer::maybeAdd(
                    new InvalidCast(
                        $invalid_casts[0] . ' cannot be cast to string',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            }
        } elseif ($explicit_cast && !$castable_types) {
            // todo: emit error here
        }

        $valid_types = array_merge($valid_strings, $castable_types);

        if (!$valid_types) {
            $str_type = Type::getString();
        } else {
            $str_type = TypeCombiner::combine(
                $valid_types,
                $codebase
            );
        }

        if ($statements_analyzer->data_flow_graph) {
            $str_type->parent_nodes = $parent_nodes;
        }

        return $str_type;
    }

    private static function handleRedundantCast(
        Union $maybe_type,
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Cast $stmt
    ): void {
        $codebase = $statements_analyzer->getCodebase();
        $project_analyzer = $statements_analyzer->getProjectAnalyzer();

        $file_manipulation = null;
        if ($maybe_type->from_docblock) {
            $issue = new RedundantCastGivenDocblockType(
                'Redundant cast to ' . $maybe_type->getKey() . ' given docblock-provided type',
                new CodeLocation($statements_analyzer->getSource(), $stmt)
            );

            if ($codebase->alter_code
                && isset($project_analyzer->getIssuesToFix()['RedundantCastGivenDocblockType'])
            ) {
                $file_manipulation = new FileManipulation(
                    (int) $stmt->getAttribute('startFilePos'),
                    (int) $stmt->expr->getAttribute('startFilePos'),
                    ''
                );
            }
        } else {
            $issue = new RedundantCast(
                'Redundant cast to ' . $maybe_type->getKey(),
                new CodeLocation($statements_analyzer->getSource(), $stmt)
            );

            if ($codebase->alter_code
                && isset($project_analyzer->getIssuesToFix()['RedundantCast'])
            ) {
                $file_manipulation = new FileManipulation(
                    (int) $stmt->getAttribute('startFilePos'),
                    (int) $stmt->expr->getAttribute('startFilePos'),
                    ''
                );
            }
        }

        if ($file_manipulation) {
            FileManipulationBuffer::add($statements_analyzer->getFilePath(), [$file_manipulation]);
        }


        if (IssueBuffer::accepts($issue, $statements_analyzer->getSuppressedIssues())) {
            // fall through
        }
    }
}
