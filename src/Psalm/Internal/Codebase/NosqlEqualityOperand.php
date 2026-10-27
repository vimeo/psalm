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
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Type\TaintKind;

use function array_search;
use function is_int;
use function str_starts_with;
use function strtolower;

/**
 * The operand of the `$eq` of a field in a MongoDB query filter, `['field' => ['$eq' => $value]]`, is no NoSQL
 * injection: MongoDB compares the field with it as it is, keys starting with `$` and regexes included. Elsewhere
 * `$eq` may take expressions: in `$expr` and in aggregation pipelines, `'$field'` is a field path and operators are
 * evaluated.
 *
 * So in an array literal given to a `$filter` parameter that is a `nosql` sink, the operand of an `$eq` that is the
 * value of a field at its top level (a key of a literal string not starting with `$`, unlike `$expr`, `$where`,
 * `$and`, `$or`, `$nor`, the stages of a pipeline and the update operators) loses the `nosql` taint on its way into
 * the array, when every method the call may run takes the argument as a `nosql` sink and has no body Psalm analyzes.
 * The taint is lost for wherever the array goes, not only for the sink: a body, of the method or of an override of
 * it, may put the argument anywhere, e.g. into `$expr`. A parameter named otherwise may take no filter at all, e.g.
 * the fields of `$addFields`, where the operands of `$eq` are expressions.
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
     */
    public static function markArgument(
        StatementsAnalyzer $statements_analyzer,
        ?string $method_id,
        ?array $function_params,
        ?FunctionLikeParameter $param,
        Array_ $argument,
    ): void {
        $is_sink = $param !== null
            && $function_params !== null
            && $param->name === 'filter'
            && self::isNosqlSink($param)
            && self::isSinkOfEveryMethodCalled($statements_analyzer, $method_id, $function_params, $param);

        foreach ($argument->items as $field) {
            if ($field === null
                || $field->key === null
                || $field->byRef
                || $field->unpack
                || !$field->value instanceof Array_
            ) {
                continue;
            }

            foreach ($field->value->items as $operator) {
                if ($operator !== null
                    && $operator->key instanceof String_
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
     * Whether $storage has no body Psalm analyzes: it is abstract or of an interface, or it is declared outside of the
     * project files, e.g. in a stub
     *
     * @psalm-capabilities read-props
     */
    private static function hasNoAnalyzedBody(Codebase $codebase, FunctionLikeStorage $storage): bool
    {
        if ($storage instanceof MethodStorage
            && ($storage->abstract
                || ($storage->defining_fqcln !== null
                    && $codebase->classlike_storage_provider->has($storage->defining_fqcln)
                    && $codebase->classlike_storage_provider->get($storage->defining_fqcln)->is_interface))
        ) {
            return true;
        }

        return $storage->location !== null && !$codebase->config->isInProjectDirs($storage->location->file_path);
    }

    /**
     * Whether the function or method a call of $method_id runs, and each override of the method it may run instead
     * in the classes extending or implementing its class, takes the argument as a `nosql` sink with no body Psalm
     * analyzes
     *
     * @param array<int, FunctionLikeParameter> $function_params
     */
    private static function isSinkOfEveryMethodCalled(
        StatementsAnalyzer $statements_analyzer,
        ?string $method_id,
        array $function_params,
        FunctionLikeParameter $param,
    ): bool {
        if ($method_id === null || $method_id === '') {
            return false;
        }

        $codebase = $statements_analyzer->getCodebase();

        // a function
        if (!MethodIdentifier::isValidMethodIdReference($method_id)) {
            $function_id = strtolower($method_id);

            return $codebase->functions->functionExists($statements_analyzer, $function_id)
                && self::hasNoAnalyzedBody(
                    $codebase,
                    $codebase->functions->getStorage($statements_analyzer, $function_id),
                );
        }

        $offset = array_search($param, $function_params, true);
        $method_id = MethodIdentifier::wrap($method_id);
        $fq_class_name = $codebase->classlikes->getUnAliasedName($method_id->fq_class_name);
        if (!is_int($offset) || !$codebase->classlike_storage_provider->has($fq_class_name)) {
            return false;
        }

        $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);
        if ($declaring_method_id === null
            || !self::hasNoAnalyzedBody($codebase, $codebase->methods->getStorage($declaring_method_id))
        ) {
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

            $override_storage = $codebase->methods->getStorage($override_id);
            $override_param = $override_storage->params[$offset] ?? null;
            if ($override_param === null
                || !self::isNosqlSink($override_param)
                || !self::hasNoAnalyzedBody($codebase, $override_storage)
            ) {
                return false;
            }
        }

        return true;
    }
}
