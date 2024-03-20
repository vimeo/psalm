<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\Statements\Expression\Fetch\VariableFetchAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_flip;
use function array_search;
use function in_array;
use function is_array;
use function is_int;

use const FILTER_CALLBACK;
use const FILTER_DEFAULT;
use const FILTER_FLAG_NONE;
use const FILTER_REQUIRE_ARRAY;
use const FILTER_VALIDATE_REGEXP;
use const INPUT_COOKIE;
use const INPUT_ENV;
use const INPUT_GET;
use const INPUT_POST;
use const INPUT_SERVER;

/**
 * @internal
 */
class FilterInputReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['filter_input'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $statements_analyzer = $event->getStatementsSource();
        if (! $statements_analyzer instanceof StatementsAnalyzer) {
            throw new UnexpectedValueException('Expected StatementsAnalyzer not StatementsSource');
        }

        $arg_names = array_flip(['type', 'var_name', 'filter', 'options']);
        $call_args = [];
        foreach ($event->getCallArgs() as $idx => $arg) {
            if (isset($arg->name)) {
                $call_args[$arg_names[$arg->name->name]] = $arg;
            } else {
                $call_args[$idx] = $arg;
            }
        }

        $function_id   = $event->getFunctionId();
        $code_location = $event->getCodeLocation();
        $codebase      = $statements_analyzer->getCodebase();

        if (! isset($call_args[0]) || ! isset($call_args[1])) {
            return FilterUtils::missingFirstArg($codebase);
        }

        $first_arg_type = $statements_analyzer->node_data->getType($call_args[0]->value);
        if ($first_arg_type && ! $first_arg_type->isInt()) {
            if ($codebase->analysis_php_version_id >= 8_00_00) {
                // throws
                return Type::getNever();
            }

            // default option won't be used in this case
            return Type::getNull();
        }

        $filter_int_used = FILTER_DEFAULT;
        if (isset($call_args[2])) {
            $filter_int_used = FilterUtils::getFilterArgValueOrError(
                $call_args[2],
                $statements_analyzer,
                $codebase,
            );

            if (!is_int($filter_int_used)) {
                return $filter_int_used;
            }
        }

        $options = null;
        $flags_int_used = FILTER_FLAG_NONE;
        if (isset($call_args[3])) {
            $helper = FilterUtils::getOptionsArgValueOrError(
                $call_args[3],
                $statements_analyzer,
                $codebase,
                $code_location,
                $function_id,
                $filter_int_used,
            );

            if (!is_array($helper)) {
                return $helper;
            }

            $flags_int_used = $helper['flags_int_used'];
            $options = $helper['options'];
        }

        // if we reach this point with callback, the callback is missing
        if ($filter_int_used === FILTER_CALLBACK) {
            return FilterUtils::missingFilterCallbackCallable(
                $function_id,
                $code_location,
                $statements_analyzer,
                $codebase,
            );
        }

        [$default, $min_range, $max_range, $has_range, $regexp] = FilterUtils::getOptions(
            $filter_int_used,
            $flags_int_used,
            $options,
            $statements_analyzer,
            $code_location,
            $codebase,
            $function_id,
        );

        // only return now, as we still want to report errors above
        if (!$first_arg_type) {
            return null;
        }

        if (! $first_arg_type->isSingleIntLiteral()) {
            // eventually complex cases can be handled too, however practically this is irrelevant
            return null;
        }

        if (!$default) {
            [$fails_type, $not_set_type, $fails_or_not_set_type] = FilterUtils::getFailsNotSetType($flags_int_used);
        } else {
            $fails_type = $default;
            $not_set_type = $default;
            $fails_or_not_set_type = $default;
        }

        if ($filter_int_used === FILTER_VALIDATE_REGEXP && $regexp === null) {
            if ($codebase->analysis_php_version_id >= 8_00_00) {
                // throws
                return Type::getNever();
            }

            // any "array" flags are ignored by this filter!
            return $fails_or_not_set_type;
        }

        $possible_types = array(
            '$_GET'    => INPUT_GET,
            '$_POST'   => INPUT_POST,
            '$_COOKIE' => INPUT_COOKIE,
            '$_SERVER' => INPUT_SERVER,
            '$_ENV'    => INPUT_ENV,
        );

        $first_arg_type_type = $first_arg_type->getSingleIntLiteral();
        $global_name = array_search($first_arg_type_type->value, $possible_types);
        if (!$global_name) {
            // invalid
            if ($codebase->analysis_php_version_id >= 8_00_00) {
                // throws
                return Type::getNever();
            }

            // the "not set type" is never in an array, even if FILTER_FORCE_ARRAY is set!
            return $not_set_type;
        }

        $second_arg_type = $statements_analyzer->node_data->getType($call_args[1]->value);
        if (!$second_arg_type) {
            return null;
        }

        if (! $second_arg_type->hasString()) {
            // for filter_input there can only be string array keys
            return $not_set_type;
        }

        if (! $second_arg_type->isString()) {
            // already reports an error by default
            return null;
        }

        // in all these cases it can fail or be not set, depending on whether the variable is set or not
        $redundant_error_return_type = FilterUtils::checkRedundantFlags(
            $filter_int_used,
            $flags_int_used,
            $fails_or_not_set_type,
            $statements_analyzer,
            $code_location,
            $codebase,
        );
        if ($redundant_error_return_type !== null) {
            return $redundant_error_return_type;
        }

        if (FilterUtils::hasFlag($flags_int_used, FILTER_REQUIRE_ARRAY)
            && in_array($first_arg_type_type->value, array(INPUT_COOKIE, INPUT_SERVER, INPUT_ENV), true)) {
            // these globals can never be an array
            return $fails_or_not_set_type;
        }

        // @todo eventually this needs to be changed when we fully support filter_has_var
        $global_type = VariableFetchAnalyzer::getGlobalType($global_name, $codebase->analysis_php_version_id);

        $input_type = null;
        if ($global_type->isArray() && $global_type->getArray() instanceof TKeyedArray) {
            $array_instance = $global_type->getArray();
            if ($second_arg_type->isSingleStringLiteral()) {
                $key = $second_arg_type->getSingleStringLiteral()->value;

                if (isset($array_instance->properties[ $key ])) {
                    $input_type = $array_instance->properties[ $key ];
                }
            }

            if ($input_type === null) {
                $input_type = $array_instance->getGenericValueType();
                $input_type = $input_type->setPossiblyUndefined(true);
            }
        } elseif ($global_type->isArray()
            && ($array_atomic = $global_type->getArray())
            && $array_atomic instanceof TArray) {
            [$_, $input_type] = $array_atomic->type_params;
            $input_type = $input_type->setPossiblyUndefined(true);
        } else {
            // this is impossible
            throw new UnexpectedValueException('This should not happen');
        }

        return FilterUtils::getReturnType(
            $filter_int_used,
            $flags_int_used,
            $input_type,
            $fails_type,
            $not_set_type,
            $statements_analyzer,
            $code_location,
            $codebase,
            $function_id,
            $has_range,
            $min_range,
            $max_range,
            $regexp,
        );
    }
}
