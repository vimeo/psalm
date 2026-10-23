<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type\TaintKind;

use function array_search;
use function is_int;
use function str_starts_with;

/**
 * The operand of the `$eq` of a field in a MongoDB query filter, `['field' => ['$eq' => $value]]`, is no NoSQL
 * injection: MongoDB compares the field with it as it is, keys starting with `$` and regexes included. Elsewhere `$eq`
 * may take expressions: in `$expr` and in aggregation pipelines, `'$field'` is a field path and operators are evaluated.
 *
 * So in an array literal given to a parameter that is a `nosql` sink, the operand of an `$eq` that is the value of a
 * field at its top level (a key of a literal string not starting with `$`, unlike `$expr`, `$where`, `$and`, `$or`,
 * `$nor`, the stages of a pipeline and the update operators) loses the `nosql` taint on its way into the array, when
 * every method the call may run takes the argument as a `nosql` sink: an override of the method that doesn't may put
 * it anywhere, e.g. into `$expr`.
 *
 * @internal
 */
final class NosqlEqualityOperand
{
    /**
     * The attribute of an `$eq` item: the key of its field while the call takes the array as a `nosql` sink, false
     * once a method the call may run doesn't (the arguments of a call are analyzed for each type of its object)
     */
    private const FIELD_ATTRIBUTE = 'nosqlEqualityOperandField';

    /**
     * Marks the operands of the `$eq` of the fields of $argument, an argument of a call of $method_id given to $param
     * (null if unknown) of $function_params
     *
     * @param array<int, FunctionLikeParameter>|null $function_params
     * @psalm-capabilities read-props|write-props
     */
    public static function markArgument(
        Codebase $codebase,
        ?string $method_id,
        ?array $function_params,
        ?FunctionLikeParameter $param,
        Array_ $argument,
    ): void {
        $is_sink = $param !== null
            && $function_params !== null
            && self::isNosqlSink($param)
            && self::isSinkOfEveryMethodCalled($codebase, $method_id, $function_params, $param);

        foreach ($argument->items as $field) {
            if ($field->key === null || $field->byRef || $field->unpack || !$field->value instanceof Array_) {
                continue;
            }

            foreach ($field->value->items as $operator) {
                if ($operator->key instanceof String_
                    && $operator->key->value === '$eq'
                    && !$operator->byRef
                    && !$operator->unpack
                    && $operator->getAttribute(self::FIELD_ATTRIBUTE) !== false
                ) {
                    $operator->setAttribute(self::FIELD_ATTRIBUTE, $is_sink ? $field->key : false);
                }
            }
        }
    }

    /**
     * The taints the value of $item loses on its way into its array: `nosql` for a marked operand of a field
     *
     * @psalm-mutation-free
     */
    public static function getRemovedTaints(StatementsAnalyzer $statements_analyzer, ArrayItem $item): int
    {
        $field = $item->getAttribute(self::FIELD_ATTRIBUTE);
        if (!$field instanceof Expr) {
            return 0;
        }

        $field_type = $statements_analyzer->node_data->getType($field);

        return $field_type !== null
            && $field_type->isSingleStringLiteral()
            && !str_starts_with($field_type->getSingleStringLiteral()->value, '$')
            ? TaintKind::INPUT_NOSQL
            : 0;
    }

    /**
     * @psalm-mutation-free
     */
    private static function isNosqlSink(FunctionLikeParameter $param): bool
    {
        return (($param->sinks | $param->value_sinks) & TaintKind::INPUT_NOSQL) !== 0;
    }

    /**
     * Whether the parameter of each override of the method a call of $method_id may run instead, in the classes
     * extending or implementing its class, is a `nosql` sink too
     *
     * @param array<int, FunctionLikeParameter> $function_params
     * @psalm-capabilities read-props
     */
    private static function isSinkOfEveryMethodCalled(
        Codebase $codebase,
        ?string $method_id,
        array $function_params,
        FunctionLikeParameter $param,
    ): bool {
        if ($method_id === null) {
            return false;
        }

        // a function
        if (!MethodIdentifier::isValidMethodIdReference($method_id)) {
            return true;
        }

        $offset = array_search($param, $function_params, true);
        $method_id = MethodIdentifier::wrap($method_id);
        $fq_class_name = $codebase->classlikes->getUnAliasedName($method_id->fq_class_name);
        if (!is_int($offset) || !$codebase->classlike_storage_provider->has($fq_class_name)) {
            return false;
        }

        $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);
        if ($declaring_method_id === null) {
            return false;
        }

        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);
        foreach ($class_storage->dependent_classlikes as $dependent_class => $_) {
            $override_id = $codebase->methods->getDeclaringMethodId(
                new MethodIdentifier($dependent_class, $method_id->method_name),
            );

            if ($override_id === null || (string) $override_id === (string) $declaring_method_id) {
                continue;
            }

            $override_param = $codebase->methods->getStorage($override_id)->params[$offset] ?? null;
            if ($override_param === null || !self::isNosqlSink($override_param)) {
                return false;
            }
        }

        return true;
    }
}
