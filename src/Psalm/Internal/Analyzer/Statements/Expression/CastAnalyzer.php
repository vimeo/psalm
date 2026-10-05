<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\Statements\Expression\Call\Method\MethodCallReturnTypeFetcher;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\CombinedFlowGraph;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Internal\Type\TypeVariableTracker;
use Psalm\Issue\ImpureMethodCall;
use Psalm\Issue\InvalidCast;
use Psalm\Issue\PossiblyInvalidCast;
use Psalm\Issue\RedundantCast;
use Psalm\Issue\RedundantCastGivenDocblockType;
use Psalm\Issue\RiskyCast;
use Psalm\Issue\UnrecognizedExpression;
use Psalm\IssueBuffer;
use Psalm\Storage\Capabilities;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TClosedResource;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNonspecificLiteralInt;
use Psalm\Type\Atomic\TNonspecificLiteralString;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Atomic\TTypeVariable;
use Psalm\Type\Union;

use function array_merge;
use function array_pop;
use function array_values;
use function range;
use function strtolower;

/**
 * @internal
 */
final class CastAnalyzer
{
    /** @var string[] */
    private const PSEUDO_CASTABLE_CLASSES = [
        'SimpleXMLElement',
        'DOMNode',
        'GMP',
        'Decimal\Decimal',
    ];

    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Cast $stmt,
        Context $context,
    ): bool {
        if ($stmt instanceof PhpParser\Node\Expr\Cast\Int_) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $maybe_type = $statements_analyzer->node_data->getType($stmt->expr);

            if ($maybe_type) {
                if ($maybe_type->isInt()) {
                    if (!$maybe_type->from_calculation) {
                        self::handleRedundantCast($maybe_type, $statements_analyzer, $stmt);
                    }
                }

                $type = self::castIntAttempt(
                    $statements_analyzer,
                    $maybe_type,
                    $stmt->expr,
                    true,
                );
            } else {
                $type = Type::getInt();
            }

            $statements_analyzer->node_data->setType($stmt, $type);

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

                $type = self::castFloatAttempt(
                    $statements_analyzer,
                    $maybe_type,
                    $stmt->expr,
                    true,
                );
            } else {
                $type = Type::getFloat();
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

            $type = new Union([new TBool()]);

            if ($statements_analyzer->data_flow_graph) {
                $type = self::stripCastTaints(
                    $statements_analyzer,
                    $type,
                    $stmt,
                    $maybe_type->parent_nodes ?? [],
                    'bool',
                );
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
                    true,
                );
            } else {
                $stmt_type = Type::getString();
            }

            $statements_analyzer->node_data->setType($stmt, $stmt_type);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Object_) {
            if (!self::checkExprGeneralUse($statements_analyzer, $stmt, $context)) {
                return false;
            }

            $permissible_atomic_types = [];
            $all_permissible = false;

            if ($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr)) {
                if ($stmt_expr_type->isObjectType()) {
                    self::handleRedundantCast($stmt_expr_type, $statements_analyzer, $stmt);
                }

                $all_permissible = true;

                foreach ($stmt_expr_type->getAtomicTypes() as $type) {
                    if ($type instanceof Scalar) {
                        $objWithProps = new TObjectWithProperties(['scalar' => new Union([$type])]);
                        $permissible_atomic_types[] = $objWithProps;
                    } elseif ($type instanceof TKeyedArray) {
                        $permissible_atomic_types[] = new TObjectWithProperties($type->properties);
                    } else {
                        $all_permissible = false;
                        break;
                    }
                }
            }

            if ($permissible_atomic_types && $all_permissible) {
                $type = TypeCombiner::combine($permissible_atomic_types);
            } else {
                $type = Type::getObject();
            }

            if ($statements_analyzer->data_flow_graph) {
                $type = $type->setParentNodes($stmt_expr_type->parent_nodes ?? []);
            }

            $statements_analyzer->node_data->setType($stmt, $type);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Array_) {
            if (!self::checkExprGeneralUse($statements_analyzer, $stmt, $context)) {
                return false;
            }

            $permissible_atomic_types = [];
            $all_permissible = false;

            if ($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr)) {
                if ($stmt_expr_type->isArray()) {
                    self::handleRedundantCast($stmt_expr_type, $statements_analyzer, $stmt);
                }

                $all_permissible = true;

                foreach ($stmt_expr_type->getAtomicTypes() as $type) {
                    if ($type instanceof Scalar) {
                        $keyed_array = TKeyedArray::make([new Union([$type])], null, null, true);
                        $permissible_atomic_types[] = $keyed_array;
                    } elseif ($type instanceof TNull) {
                        $permissible_atomic_types[] = new TArray([Type::getNever(), Type::getNever()]);
                    } elseif ($type instanceof TArray
                        || $type instanceof TKeyedArray
                    ) {
                        $permissible_atomic_types[] = $type;
                    } elseif ($type instanceof TObjectWithProperties) {
                        $array_type = $type->properties === []
                            ? Type::getArrayAtomic()
                            : TKeyedArray::make(
                                $type->properties,
                                null,
                                [Type::getArrayKey(), Type::getMixed()],
                            );
                        $permissible_atomic_types[] = $array_type;
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
                $type = $type->setParentNodes($stmt_expr_type->parent_nodes ?? []);
            }

            $statements_analyzer->node_data->setType($stmt, $type);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Unset_
            && $statements_analyzer->getCodebase()->analysis_php_version_id <= 7_04_00
        ) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $statements_analyzer->node_data->setType($stmt, Type::getNull());

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Void_) {
            // PHP 8.5's (void) cast evaluates its operand and explicitly discards the result.
            // It is only valid as a statement; consuming its result is a compile error in PHP
            // (the parser nikic/php-parser accepts more leniently), so reject it in any
            // value-consuming position. Otherwise it is the documented escape hatch for
            // #[\NoDiscard]: analysing the operand under inside_general_use marks it as used, so
            // no discarded-return-value issue is raised. The parser only produces this node when
            // targeting PHP 8.5+, so no version guard is needed here.
            if ($context->insideUse()) {
                IssueBuffer::maybeAdd(
                    new InvalidCast(
                        'The (void) cast can only be used as a statement, not as an expression',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }

            $expression_result = self::checkExprGeneralUse($statements_analyzer, $stmt, $context);

            $statements_analyzer->node_data->setType($stmt, Type::getVoid());

            return $expression_result;
        }

        IssueBuffer::maybeAdd(
            new UnrecognizedExpression(
                'Psalm does not understand the cast ' . $stmt::class,
                new CodeLocation($statements_analyzer->getSource(), $stmt),
            ),
            $statements_analyzer->getSuppressedIssues(),
        );

        return false;
    }

    public static function castIntAttempt(
        StatementsAnalyzer $statements_analyzer,
        Union $stmt_type,
        PhpParser\Node\Expr $stmt,
        bool $explicit_cast = false,
    ): Union {
        $codebase = $statements_analyzer->getCodebase();

        $risky_cast = [];
        $invalid_casts = [];
        $valid_ints = [];
        $castable_types = [];

        $atomic_types = $stmt_type->getAtomicTypes();

        $parent_nodes = [];

        if ($statements_analyzer->data_flow_graph) {
            $parent_nodes = $stmt_type->parent_nodes;
        }

        while ($atomic_types) {
            $atomic_type = array_pop($atomic_types);

            if ($atomic_type instanceof TInt) {
                $valid_ints[] = $atomic_type;

                continue;
            }

            if ($atomic_type instanceof TFloat) {
                if ($atomic_type instanceof TLiteralFloat) {
                    $valid_ints[] = new TLiteralInt((int) $atomic_type->value);
                } else {
                    $castable_types[] = new TInt();
                }

                continue;
            }

            if ($atomic_type instanceof TString) {
                if ($atomic_type instanceof TLiteralString) {
                    $valid_ints[] = new TLiteralInt((int) $atomic_type->value);
                } elseif ($atomic_type instanceof TNumericString) {
                    $castable_types[] = new TInt();
                } else {
                    // any normal string is technically $valid_int[] = new TLiteralInt(0);
                    // however we cannot be certain that it's not inferred, therefore less strict
                    $castable_types[] = new TInt();
                }

                continue;
            }

            if ($atomic_type instanceof TNull || $atomic_type instanceof TFalse) {
                $valid_ints[] = new TLiteralInt(0);
                continue;
            }

            if ($atomic_type instanceof TTrue) {
                $valid_ints[] = new TLiteralInt(1);
                continue;
            }

            if ($atomic_type instanceof TBool) {
                // do NOT use TIntRange here, as it will cause invalid behavior, e.g. bitwiseAssignment
                $valid_ints[] = new TLiteralInt(0);
                $valid_ints[] = new TLiteralInt(1);
                continue;
            }

            // could be invalid, but allow it, as it is allowed for TString below too
            if ($atomic_type instanceof TMixed
                || $atomic_type instanceof TClosedResource
                || $atomic_type instanceof TResource
                || $atomic_type instanceof Scalar
            ) {
                $castable_types[] = new TInt();

                continue;
            }

            if ($atomic_type instanceof TNamedObject) {
                $intersection_types = [$atomic_type];

                if ($atomic_type->extra_types) {
                    $intersection_types = [...$intersection_types, ...$atomic_type->extra_types];
                }

                foreach ($intersection_types as $intersection_type) {
                    if (!$intersection_type instanceof TNamedObject) {
                        continue;
                    }

                    // prevent "Could not get class storage for mixed"
                    if (!$codebase->classExists($intersection_type->value)) {
                        continue;
                    }

                    foreach (self::PSEUDO_CASTABLE_CLASSES as $pseudo_castable_class) {
                        if (strtolower($intersection_type->value) === strtolower($pseudo_castable_class)
                            || $codebase->classExtends(
                                $intersection_type->value,
                                $pseudo_castable_class,
                            )
                        ) {
                            $castable_types[] = new TInt();
                            continue 3;
                        }
                    }
                }
            }

            if ($atomic_type instanceof TNonEmptyArray
                || ($atomic_type instanceof TKeyedArray && $atomic_type->isNonEmpty())
            ) {
                $risky_cast[] = $atomic_type->getId();

                $valid_ints[] = new TLiteralInt(1);

                continue;
            }

            if ($atomic_type instanceof TArray
                || $atomic_type instanceof TKeyedArray
            ) {
                // if type is not specific, it can be both 0 or 1, depending on whether the array has data or not
                // welcome to off-by-one hell if that happens :-)
                $risky_cast[] = $atomic_type->getId();

                $valid_ints[] = new TLiteralInt(0);
                $valid_ints[] = new TLiteralInt(1);

                continue;
            }

            if ($atomic_type instanceof TTemplateParam) {
                $atomic_types = array_merge($atomic_types, $atomic_type->as->getAtomicTypes());

                continue;
            }

            // always 1 for "error" cases
            $valid_ints[] = new TLiteralInt(1);

            $invalid_casts[] = $atomic_type->getId();
        }

        if ($invalid_casts) {
            IssueBuffer::maybeAdd(
                new InvalidCast(
                    $invalid_casts[0] . ' cannot be cast to int',
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        } elseif ($risky_cast) {
            IssueBuffer::maybeAdd(
                new RiskyCast(
                    'Casting ' . $risky_cast[0] . ' to int has possibly unintended value of 0/1',
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        } elseif ($explicit_cast && !$castable_types) {
            // todo: emit error here
        }

        $valid_types = [...$valid_ints, ...$castable_types];

        if (!$valid_types) {
            $int_type = Type::getInt();
        } else {
            $int_type = TypeCombiner::combine(
                $valid_types,
                $codebase,
            );
        }

        return self::stripCastTaints(
            $statements_analyzer,
            $int_type,
            $stmt,
            $parent_nodes,
            'int',
        );
    }

    public static function castFloatAttempt(
        StatementsAnalyzer $statements_analyzer,
        Union $stmt_type,
        PhpParser\Node\Expr $stmt,
        bool $explicit_cast = false,
    ): Union {
        $codebase = $statements_analyzer->getCodebase();

        $risky_cast = [];
        $invalid_casts = [];
        $valid_floats = [];
        $castable_types = [];

        $atomic_types = $stmt_type->getAtomicTypes();

        $parent_nodes = [];

        if ($statements_analyzer->data_flow_graph) {
            $parent_nodes = $stmt_type->parent_nodes;
        }

        while ($atomic_types) {
            $atomic_type = array_pop($atomic_types);

            if ($atomic_type instanceof TFloat) {
                $valid_floats[] = $atomic_type;

                continue;
            }

            if ($atomic_type instanceof TIntRange
                && $atomic_type->min_bound !== null
                && $atomic_type->max_bound !== null
                && ($atomic_type->max_bound - $atomic_type->min_bound) < 500
            ) {
                foreach (range($atomic_type->min_bound, $atomic_type->max_bound) as $literal_int_value) {
                    $valid_floats[] = new TLiteralFloat((float) $literal_int_value);
                }

                continue;
            }

            if ($atomic_type instanceof TInt) {
                if ($atomic_type instanceof TLiteralInt) {
                    $valid_floats[] = new TLiteralFloat((float) $atomic_type->value);
                } else {
                    $castable_types[] = new TFloat();
                }

                continue;
            }

            if ($atomic_type instanceof TString) {
                if ($atomic_type instanceof TLiteralString) {
                    $valid_floats[] = new TLiteralFloat((float) $atomic_type->value);
                } elseif ($atomic_type instanceof TNumericString) {
                    $castable_types[] = new TFloat();
                } else {
                    // any normal string is technically $valid_floats[] = new TLiteralFloat(0.0);
                    // however we cannot be certain that it's not inferred, therefore less strict
                    $castable_types[] = new TFloat();
                }

                continue;
            }

            if ($atomic_type instanceof TNull || $atomic_type instanceof TFalse) {
                $valid_floats[] = new TLiteralFloat(0.0);
                continue;
            }

            if ($atomic_type instanceof TTrue) {
                $valid_floats[] = new TLiteralFloat(1.0);
                continue;
            }

            if ($atomic_type instanceof TBool) {
                $valid_floats[] = new TLiteralFloat(0.0);
                $valid_floats[] = new TLiteralFloat(1.0);
                continue;
            }

            // could be invalid, but allow it, as it is allowed for TString below too
            if ($atomic_type instanceof TMixed
                || $atomic_type instanceof TClosedResource
                || $atomic_type instanceof TResource
                || $atomic_type instanceof Scalar
            ) {
                $castable_types[] = new TFloat();

                continue;
            }

            if ($atomic_type instanceof TNamedObject) {
                $intersection_types = [$atomic_type];

                if ($atomic_type->extra_types) {
                    $intersection_types = [...$intersection_types, ...$atomic_type->extra_types];
                }

                foreach ($intersection_types as $intersection_type) {
                    if (!$intersection_type instanceof TNamedObject) {
                        continue;
                    }

                    // prevent "Could not get class storage for mixed"
                    if (!$codebase->classExists($intersection_type->value)) {
                        continue;
                    }

                    foreach (self::PSEUDO_CASTABLE_CLASSES as $pseudo_castable_class) {
                        if (strtolower($intersection_type->value) === strtolower($pseudo_castable_class)
                            || $codebase->classExtends(
                                $intersection_type->value,
                                $pseudo_castable_class,
                            )
                        ) {
                            $castable_types[] = new TFloat();
                            continue 3;
                        }
                    }
                }
            }

            if ($atomic_type instanceof TNonEmptyArray
                || ($atomic_type instanceof TKeyedArray && $atomic_type->isNonEmpty())
            ) {
                $risky_cast[] = $atomic_type->getId();

                $valid_floats[] = new TLiteralFloat(1.0);

                continue;
            }

            if ($atomic_type instanceof TArray
                || $atomic_type instanceof TKeyedArray
            ) {
                // if type is not specific, it can be both 0 or 1, depending on whether the array has data or not
                // welcome to off-by-one hell if that happens :-)
                $risky_cast[] = $atomic_type->getId();

                $valid_floats[] = new TLiteralFloat(0.0);
                $valid_floats[] = new TLiteralFloat(1.0);

                continue;
            }

            if ($atomic_type instanceof TTemplateParam) {
                $atomic_types = array_merge($atomic_types, $atomic_type->as->getAtomicTypes());

                continue;
            }

            // always 1.0 for "error" cases
            $valid_floats[] = new TLiteralFloat(1.0);

            $invalid_casts[] = $atomic_type->getId();
        }

        if ($invalid_casts) {
            IssueBuffer::maybeAdd(
                new InvalidCast(
                    $invalid_casts[0] . ' cannot be cast to float',
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        } elseif ($risky_cast) {
            IssueBuffer::maybeAdd(
                new RiskyCast(
                    'Casting ' . $risky_cast[0] . ' to float has possibly unintended value of 0.0/1.0',
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        } elseif ($explicit_cast && !$castable_types) {
            // todo: emit error here
        }

        $valid_types = [...$valid_floats, ...$castable_types];

        if (!$valid_types) {
            $float_type = Type::getFloat();
        } else {
            $float_type = TypeCombiner::combine(
                $valid_types,
                $codebase,
            );
        }

        return self::stripCastTaints(
            $statements_analyzer,
            $float_type,
            $stmt,
            $parent_nodes,
            'float',
        );
    }

    public static function castStringAttempt(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        Union $stmt_type,
        PhpParser\Node\Expr $stmt,
        bool $explicit_cast = false,
    ): Union {
        $codebase = $statements_analyzer->getCodebase();

        $invalid_casts = [];
        $valid_strings = [];
        $castable_types = [];

        $atomic_types = $stmt_type->getAtomicTypes();

        $parent_nodes = $statements_analyzer->data_flow_graph
            ? self::getStringConversionParentNodes($statements_analyzer, $context, $stmt, $stmt_type)
            : [];

        while ($atomic_types) {
            $atomic_type = array_pop($atomic_types);

            if ($atomic_type instanceof TFloat
                || $atomic_type instanceof TInt
                || $atomic_type instanceof TNumeric
            ) {
                if ($atomic_type instanceof TLiteralInt || $atomic_type instanceof TLiteralFloat) {
                    $valid_strings[] = Type::getAtomicStringFromLiteral((string) $atomic_type->value);
                } elseif ($atomic_type instanceof TNonspecificLiteralInt) {
                    $castable_types[] = new TNonspecificLiteralString();
                } elseif ($atomic_type instanceof TIntRange
                    && $atomic_type->min_bound !== null
                    && $atomic_type->max_bound !== null
                    && ($atomic_type->max_bound - $atomic_type->min_bound) < 500
                ) {
                    foreach (range($atomic_type->min_bound, $atomic_type->max_bound) as $literal_int_value) {
                        $valid_strings[] = Type::getAtomicStringFromLiteral((string) $literal_int_value);
                    }
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
                $valid_strings[] = Type::getAtomicStringFromLiteral('');
                continue;
            }

            if ($atomic_type instanceof TTrue
            ) {
                $valid_strings[] = Type::getAtomicStringFromLiteral('1');
                continue;
            }

            if ($atomic_type instanceof TBool
            ) {
                $valid_strings[] = Type::getAtomicStringFromLiteral('1');
                $valid_strings[] = Type::getAtomicStringFromLiteral('');
                continue;
            }

            if ($atomic_type instanceof TClosedResource
               || $atomic_type instanceof TResource
            ) {
                $castable_types[] = new TNonEmptyString();

                continue;
            }

            if ($atomic_type instanceof TMixed
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
                            '__tostring',
                        );

                        if ($codebase->methodExists(
                            $intersection_method_id,
                            $context->calling_method_id,
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                        )) {
                            $return_type = $codebase->getMethodReturnType(
                                $intersection_method_id,
                                $self_class,
                            ) ?? Type::getString();

                            $declaring_method_id = $codebase->methods->getDeclaringMethodId($intersection_method_id);

                            if ($declaring_method_id !== null) {
                                $to_string_storage = $codebase->methods->getStorage($declaring_method_id);
                                $var_id = ExpressionIdentifier::getExtendedVarId(
                                    $stmt,
                                    $statements_analyzer->getFQCLN(),
                                    $statements_analyzer,
                                );

                                $statements_analyzer->signalMutation(
                                    $to_string_storage->capabilities & ~Capabilities::READ_PROPS,
                                    $context,
                                    'possibly-mutating method ' . $intersection_type->value . '::__toString',
                                    ImpureMethodCall::class,
                                    $stmt,
                                    $to_string_storage->capabilities,
                                    false,
                                    $var_id === '$this' ? $to_string_storage : null,
                                );
                            }

                            $castable_types = [...$castable_types, ...array_values($return_type->getAtomicTypes())];

                            continue 2;
                        }
                    }

                    if ($intersection_type instanceof TObjectWithProperties
                        && isset($intersection_type->methods['__tostring'])
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

            if ($atomic_type instanceof TTypeVariable) {
                // A class-template type variable is castable through the bound
                // its construction inferred — as the TTemplateParam branch reads
                // through `as`. Resolve it so `(string) $var` sees that bound
                // instead of rejecting the bare variable as uncastable.
                $resolved = TypeVariableTracker::resolveTypeVariables(new Union([$atomic_type]), $codebase);

                if ($resolved->getId() !== $atomic_type->getId()) {
                    $atomic_types = array_merge($atomic_types, $resolved->getAtomicTypes());

                    continue;
                }
            }

            $invalid_casts[] = $atomic_type->getId();
        }

        if ($invalid_casts) {
            if ($valid_strings || $castable_types) {
                IssueBuffer::maybeAdd(
                    new PossiblyInvalidCast(
                        $invalid_casts[0] . ' cannot be cast to string',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            } else {
                IssueBuffer::maybeAdd(
                    new InvalidCast(
                        $invalid_casts[0] . ' cannot be cast to string',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }
        } elseif ($explicit_cast && !$castable_types) {
            // todo: emit error here
        }

        $valid_types = [...$valid_strings, ...$castable_types];

        if (!$valid_types) {
            $str_type = Type::getString();
        } else {
            $str_type = TypeCombiner::combine(
                $valid_types,
                $codebase,
            );
        }

        return self::stripCastTaints(
            $statements_analyzer,
            $str_type,
            $stmt,
            $parent_nodes,
            'string',
        );
    }

    /**
     * The parent nodes of the string $expr, a value of $type, converts to. An object of a class with __toString
     * converts to what its __toString returns: when the value can only be such objects, and what their __toString
     * returns carries all their taints (see returnsTheTaintsOfTheObject()), their own taints only reach the string
     * through __toString, so what it does to them (e.g. escaping them) applies.
     *
     * @return array<string, DataFlowNode>
     */
    public static function getStringConversionParentNodes(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        PhpParser\Node\Expr $expr,
        Union $type,
    ): array {
        $codebase = $statements_analyzer->getCodebase();

        $to_string_parent_nodes = [];
        $has_to_string = false;
        $converted_atomics = 0;
        $to_string_atomics = 0;

        $atomic_types = $type->getAtomicTypes();

        while ($atomic_types) {
            $atomic_type = array_pop($atomic_types);

            if ($atomic_type instanceof TTemplateParam) {
                $atomic_types = [...$atomic_types, ...array_values($atomic_type->as->getAtomicTypes())];

                continue;
            }

            $converted_atomics++;

            $to_string_id = self::getToStringMethodId($codebase, $atomic_type);

            if ($to_string_id === null) {
                continue;
            }

            $has_to_string = true;

            $return_type = $codebase->getMethodReturnType($to_string_id, $self_class) ?? Type::getString();
            $declaring_method_id = $codebase->methods->getDeclaringMethodId($to_string_id);

            MethodCallReturnTypeFetcher::taintMethodCallResult(
                $statements_analyzer,
                $return_type,
                $expr,
                $expr,
                [],
                $to_string_id,
                $declaring_method_id,
                $to_string_id->fq_class_name . '::__toString',
                $context,
            );

            $to_string_parent_nodes = array_merge($to_string_parent_nodes, $return_type->parent_nodes);

            $var_id = ExpressionIdentifier::getExtendedVarId(
                $expr,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer,
            );

            if (self::returnsTheTaintsOfTheObject($codebase, $context, $declaring_method_id, $var_id)) {
                $to_string_atomics++;
            }
        }

        if (!$has_to_string) {
            return $type->parent_nodes;
        }

        if ($to_string_atomics === $converted_atomics) {
            return self::getToStringConversionParentNodes(
                $statements_analyzer,
                $expr,
                $type->parent_nodes,
                $to_string_parent_nodes,
            );
        }

        return array_merge($to_string_parent_nodes, $type->parent_nodes);
    }

    /**
     * The __toString of an object type (of one of the types of an intersection), if it has one
     */
    private static function getToStringMethodId(Codebase $codebase, Atomic $atomic_type): ?MethodIdentifier
    {
        if (!$atomic_type instanceof TNamedObject) {
            return null;
        }

        foreach ([$atomic_type, ...array_values($atomic_type->extra_types)] as $intersection_type) {
            if (!$intersection_type instanceof TNamedObject) {
                continue;
            }

            $method_id = new MethodIdentifier($intersection_type->value, '__tostring');

            if ($codebase->methods->methodExists($codebase, $method_id)) {
                return $method_id;
            }
        }

        return null;
    }

    /**
     * Whether what the __toString of an object returns carries all the taints of the object: its body must be analyzed
     * (what a method of a file that is only scanned, a dependency or a stub without flows, returns carries none), and
     * the object of a class tracked per instance (@psalm-taint-specialize) must be held in a variable, the receivers
     * whose taints a specialized call gets (see MethodCallReturnTypeFetcher::taintMethodCallResult())
     *
     * @psalm-mutation-free
     */
    private static function returnsTheTaintsOfTheObject(
        Codebase $codebase,
        Context $context,
        ?MethodIdentifier $to_string_id,
        ?string $var_id,
    ): bool {
        if ($to_string_id === null) {
            return false;
        }

        $to_string_storage = $codebase->methods->getStorage($to_string_id);

        if ($to_string_storage->location === null
            || !$codebase->config->isInProjectDirs($to_string_storage->location->file_path)
        ) {
            return false;
        }

        return !$to_string_storage->specialize_call
            || ($var_id !== null && isset($context->vars_in_scope[$var_id]));
    }

    /**
     * The parent nodes of the string conversion of objects whose __toString returns all their taints: in the taint
     * graph only what __toString returns, as the objects' own taints would bypass what __toString does to them; in
     * the variable use graph the objects too, as the conversion uses them
     *
     * @param array<string, DataFlowNode> $object_parent_nodes
     * @param array<string, DataFlowNode> $to_string_parent_nodes
     * @return array<string, DataFlowNode>
     */
    private static function getToStringConversionParentNodes(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $expr,
        array $object_parent_nodes,
        array $to_string_parent_nodes,
    ): array {
        $graph = $statements_analyzer->data_flow_graph;

        if ($graph instanceof TaintFlowGraph) {
            return $to_string_parent_nodes;
        }

        if (!$graph instanceof CombinedFlowGraph || $object_parent_nodes === []) {
            return array_merge($to_string_parent_nodes, $object_parent_nodes);
        }

        $conversion_node = DataFlowNode::getForAssignment(
            'string conversion',
            new CodeLocation($statements_analyzer->getSource(), $expr),
        );

        $graph->addNode($conversion_node);

        foreach ($to_string_parent_nodes as $parent_node) {
            $graph->addPath($parent_node, $conversion_node, '=');
        }

        foreach ($object_parent_nodes as $parent_node) {
            $graph->variable_use_graph->addPath($parent_node, $conversion_node, '=');
        }

        return [$conversion_node->id => $conversion_node];
    }

    /**
     * Route the parent nodes of a scalar cast through a pass-through node that strips the
     * taints which cannot survive the target scalar type (see Union::getTaintsToRemove()):
     * casting to int/float removes every non-numeric taint, casting to bool every
     * non-bool taint, and casting to string the array/object-only taints (e.g. nosql).
     *
     * The pass-through node is added to the active data-flow graph so variable-use tracking
     * stays intact in every mode; the removed_taints on the edge is ignored by the
     * variable-use graph and only takes effect for taint analysis.
     *
     * @param array<string, DataFlowNode> $parent_nodes
     */
    private static function stripCastTaints(
        StatementsAnalyzer $statements_analyzer,
        Union $result_type,
        PhpParser\Node\Expr $stmt,
        array $parent_nodes,
        string $cast_type,
    ): Union {
        if (!$graph = $statements_analyzer->data_flow_graph) {
            return $result_type;
        }

        $removed_taints = $result_type->getTaintsToRemove();

        if ($removed_taints !== 0 && $parent_nodes) {
            $cast_node = DataFlowNode::getForAssignment(
                $cast_type . '-cast',
                new CodeLocation($statements_analyzer->getSource(), $stmt),
            );
            $graph->addNode($cast_node);

            foreach ($parent_nodes as $parent_node) {
                $graph->addPath(
                    $parent_node,
                    $cast_node,
                    $cast_type . '-cast',
                    0,
                    $removed_taints,
                );
            }

            $parent_nodes = [$cast_node->id => $cast_node];
        }

        return $result_type->setParentNodes($parent_nodes);
    }

    private static function checkExprGeneralUse(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Cast $stmt,
        Context $context,
    ): bool {
        $was_inside_general_use = $context->inside_general_use;
        $context->inside_general_use = true;
        $retVal = ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context);
        $context->inside_general_use = $was_inside_general_use;
        return $retVal;
    }

    private static function handleRedundantCast(
        Union $maybe_type,
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Cast $stmt,
    ): void {
        $codebase = $statements_analyzer->getCodebase();
        $project_analyzer = $statements_analyzer->getProjectAnalyzer();

        $file_manipulation = null;
        if ($maybe_type->from_docblock) {
            $issue = new RedundantCastGivenDocblockType(
                'Redundant cast to ' . $maybe_type->getKey() . ' given docblock-provided type',
                new CodeLocation($statements_analyzer->getSource(), $stmt),
            );

            if ($codebase->alter_code
                && isset($project_analyzer->getIssuesToFix()['RedundantCastGivenDocblockType'])
            ) {
                $file_manipulation = new FileManipulation(
                    (int) $stmt->getAttribute('startFilePos'),
                    (int) $stmt->expr->getAttribute('startFilePos'),
                    '',
                );
            }
        } else {
            $issue = new RedundantCast(
                'Redundant cast to ' . $maybe_type->getKey(),
                new CodeLocation($statements_analyzer->getSource(), $stmt),
            );

            if ($codebase->alter_code
                && isset($project_analyzer->getIssuesToFix()['RedundantCast'])
            ) {
                $file_manipulation = new FileManipulation(
                    (int) $stmt->getAttribute('startFilePos'),
                    (int) $stmt->expr->getAttribute('startFilePos'),
                    '',
                );
            }
        }

        if ($file_manipulation) {
            FileManipulationBuffer::add($statements_analyzer->getFilePath(), [$file_manipulation]);
        }


        IssueBuffer::maybeAdd($issue, $statements_analyzer->getSuppressedIssues());
    }
}
