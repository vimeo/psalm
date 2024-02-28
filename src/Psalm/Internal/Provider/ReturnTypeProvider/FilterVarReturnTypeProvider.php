<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Union;
use UnexpectedValueException;

use function is_array;
use function is_int;

use const FILTER_CALLBACK;
use const FILTER_DEFAULT;
use const FILTER_FLAG_NONE;
use const FILTER_VALIDATE_REGEXP;

/**
 * @internal
 */
final class FilterVarReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['filter_var'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $statements_analyzer = $event->getStatementsSource();
        if (!$statements_analyzer instanceof StatementsAnalyzer) {
            throw new UnexpectedValueException();
        }

        $call_args = $event->getCallArgs();
        $function_id = $event->getFunctionId();
        $code_location = $event->getCodeLocation();
        $codebase      = $statements_analyzer->getCodebase();

        if (! isset($call_args[0])) {
            return FilterUtils::missingFirstArg($codebase);
        }

        $filter_int_used = FILTER_DEFAULT;
        if (isset($call_args[1])) {
            $filter_int_used = FilterUtils::getFilterArgValueOrError(
                $call_args[1],
                $statements_analyzer,
                $codebase,
            );

            if (!is_int($filter_int_used)) {
                return $filter_int_used;
            }
        }

        $options = null;
        $flags_int_used = FILTER_FLAG_NONE;
        if (isset($call_args[2])) {
            $helper = FilterUtils::getOptionsArgValueOrError(
                $call_args[2],
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

        if (!$default) {
            [$fails_type] = FilterUtils::getFailsNotSetType($flags_int_used);
        } else {
            $fails_type = $default;
        }

        if ($filter_int_used === FILTER_VALIDATE_REGEXP && $regexp === null) {
            if ($codebase->analysis_php_version_id >= 8_00_00) {
                // throws
                return Type::getNever();
            }

            // any "array" flags are ignored by this filter!
            return $fails_type;
        }

        $input_type = $statements_analyzer->node_data->getType($call_args[0]->value);

        // only return now, as we still want to report errors above
        if (!$input_type) {
            return null;
        }

        $redundant_error_return_type = FilterUtils::checkRedundantFlags(
            $filter_int_used,
            $flags_int_used,
            $fails_type,
            $statements_analyzer,
            $code_location,
            $codebase,
        );
        if ($redundant_error_return_type !== null) {
            return $redundant_error_return_type;
        }

        return FilterUtils::getReturnType(
            $filter_int_used,
            $flags_int_used,
            $input_type,
            $fails_type,
            null,
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
