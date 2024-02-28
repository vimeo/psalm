<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser\Node\Arg;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\Type\Comparator\CallableTypeComparator;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Issue\InvalidArgument;
use Psalm\Issue\RedundantFlag;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNonFalsyString;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_diff;
use function array_keys;
use function array_merge;
use function filter_var;
use function get_class;
use function implode;
use function in_array;
use function preg_match;
use function strtolower;

use const FILTER_CALLBACK;
use const FILTER_DEFAULT;
use const FILTER_FLAG_ALLOW_FRACTION;
use const FILTER_FLAG_ALLOW_HEX;
use const FILTER_FLAG_ALLOW_OCTAL;
use const FILTER_FLAG_ALLOW_SCIENTIFIC;
use const FILTER_FLAG_ALLOW_THOUSAND;
use const FILTER_FLAG_EMAIL_UNICODE;
use const FILTER_FLAG_ENCODE_AMP;
use const FILTER_FLAG_ENCODE_HIGH;
use const FILTER_FLAG_ENCODE_LOW;
use const FILTER_FLAG_HOSTNAME;
use const FILTER_FLAG_IPV4;
use const FILTER_FLAG_IPV6;
use const FILTER_FLAG_NONE;
use const FILTER_FLAG_NO_ENCODE_QUOTES;
use const FILTER_FLAG_NO_PRIV_RANGE;
use const FILTER_FLAG_NO_RES_RANGE;
use const FILTER_FLAG_PATH_REQUIRED;
use const FILTER_FLAG_QUERY_REQUIRED;
use const FILTER_FLAG_STRIP_BACKTICK;
use const FILTER_FLAG_STRIP_HIGH;
use const FILTER_FLAG_STRIP_LOW;
use const FILTER_FORCE_ARRAY;
use const FILTER_NULL_ON_FAILURE;
use const FILTER_REQUIRE_ARRAY;
use const FILTER_REQUIRE_SCALAR;
use const FILTER_SANITIZE_ADD_SLASHES;
use const FILTER_SANITIZE_EMAIL;
use const FILTER_SANITIZE_ENCODED;
use const FILTER_SANITIZE_FULL_SPECIAL_CHARS;
use const FILTER_SANITIZE_NUMBER_FLOAT;
use const FILTER_SANITIZE_NUMBER_INT;
use const FILTER_SANITIZE_SPECIAL_CHARS;
use const FILTER_SANITIZE_URL;
use const FILTER_UNSAFE_RAW;
use const FILTER_VALIDATE_BOOLEAN;
use const FILTER_VALIDATE_DOMAIN;
use const FILTER_VALIDATE_EMAIL;
use const FILTER_VALIDATE_FLOAT;
use const FILTER_VALIDATE_INT;
use const FILTER_VALIDATE_IP;
use const FILTER_VALIDATE_MAC;
use const FILTER_VALIDATE_REGEXP;
use const FILTER_VALIDATE_URL;

/**
 * @internal
 */
final class FilterUtils
{
    public static function missingFirstArg(Codebase $codebase): Union
    {
        if ($codebase->analysis_php_version_id >= 8_00_00) {
            // throws
            return Type::getNever();
        }

        return Type::getNull();
    }

    /** @return int|Union|null */
    public static function getFilterArgValueOrError(
        Arg $filter_arg,
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase
    ) {
        $filter_arg_type = $statements_analyzer->node_data->getType($filter_arg->value);
        if (!$filter_arg_type) {
            return null;
        }

        if (! $filter_arg_type->isInt()) {
            // invalid
            if ($codebase->analysis_php_version_id >= 8_00_00) {
                // throws
                return Type::getNever();
            }

            // will return null independent of FILTER_NULL_ON_FAILURE or default option
            return Type::getNull();
        }

        if (! $filter_arg_type->isSingleIntLiteral()) {
            // too complex for now
            return null;
        }

        $all_filters = self::getFilters($codebase);
        $filter_int_used = $filter_arg_type->getSingleIntLiteral()->value;
        if (! isset($all_filters[ $filter_int_used ])) {
            // inconsistently, this will always return false, even when FILTER_NULL_ON_FAILURE
            // or a default option is set
            // and will also not use any default set
            return Type::getFalse();
        }

        return $filter_int_used;
    }

    /** @return array{flags_int_used: int, options: TKeyedArray|null}|Union|null */
    public static function getOptionsArgValueOrError(
        Arg $options_arg,
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        CodeLocation $code_location,
        string $function_id,
        int $filter_int_used
    ) {
        $options_arg_type = $statements_analyzer->node_data->getType($options_arg->value);
        if (!$options_arg_type) {
            return null;
        }

        if ($options_arg_type->isArray()) {
            $return_null = false;
            $defaults = array(
                'flags_int_used' => FILTER_FLAG_NONE,
                'options' => null,
            );

            $atomic_type = $options_arg_type->getArray();
            if ($atomic_type instanceof TKeyedArray) {
                $redundant_keys = array_diff(array_keys($atomic_type->properties), array('flags', 'options'));
                if ($redundant_keys !== array()) {
                    // reported as it's usually an oversight/misunderstanding of how the function works
                    // it's silently ignored by the function though
                    IssueBuffer::maybeAdd(
                        new RedundantFlag(
                            'The options array contains unused keys '
                            . implode(', ', $redundant_keys),
                            $code_location,
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }

                if (isset($atomic_type->properties['options'])) {
                    if ($filter_int_used === FILTER_CALLBACK) {
                        $only_callables = true;
                        foreach ($atomic_type->properties['options']->getAtomicTypes() as $option_atomic) {
                            if ($option_atomic->isCallableType()) {
                                continue;
                            }

                            if (CallableTypeComparator::getCallableFromAtomic(
                                $codebase,
                                $option_atomic,
                                null,
                                $statements_analyzer,
                            )) {
                                continue;
                            }

                            $only_callables = false;
                        }
                        if ($atomic_type->properties['options']->possibly_undefined) {
                            $only_callables = false;
                        }

                        if (!$only_callables) {
                            return self::missingFilterCallbackCallable(
                                $function_id,
                                $code_location,
                                $statements_analyzer,
                                $codebase,
                            );
                        }

                        // eventually can improve it to return the type from the callback
                        // there are no flags or other options/flags, so it can be handled here directly
                        // @todo
                        $return_type = Type::getMixed();
                        return self::addReturnTaint(
                            $statements_analyzer,
                            $code_location,
                            $return_type,
                            $function_id,
                        );
                    }

                    if (! $atomic_type->properties['options']->isArray()) {
                        // silently ignored by the function, but this usually indicates a bug
                        IssueBuffer::maybeAdd(
                            new InvalidArgument(
                                'The "options" key in ' . $function_id
                                . ' must be a an array',
                                $code_location,
                                $function_id,
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                    } elseif (($options_array = $atomic_type->properties['options']->getArray())
                              && $options_array instanceof TKeyedArray) {
                        $defaults['options'] = $options_array;
                    } else {
                        // cannot infer a 100% correct specific return type
                        $return_null = true;
                    }
                }

                if (isset($atomic_type->properties['flags'])) {
                    if ($atomic_type->properties['flags']->isSingleIntLiteral()) {
                        $defaults['flags_int_used'] = $atomic_type->properties['flags']->getSingleIntLiteral()->value;
                    } elseif ($atomic_type->properties['flags']->isInt()) {
                        // cannot infer a 100% correct specific return type
                        $return_null = true;
                    } else {
                        // silently ignored by the function, but this usually indicates a bug
                        IssueBuffer::maybeAdd(
                            new InvalidArgument(
                                'The "flags" key in ' .
                                $function_id . ' must be a valid flag',
                                $code_location,
                                $function_id,
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );

                        $defaults['flags_int_used'] = FILTER_FLAG_NONE;
                    }
                }

                return $return_null ? null : $defaults;
            }

            // cannot infer a 100% correct specific return type
            return null;
        }

        if ($filter_int_used === FILTER_CALLBACK) {
            return self::missingFilterCallbackCallable(
                $function_id,
                $code_location,
                $statements_analyzer,
                $codebase,
            );
        }

        if ($options_arg_type->isSingleIntLiteral()) {
            return array(
                'flags_int_used' => $options_arg_type->getSingleIntLiteral()->value,
                'options' => null,
            );
        }

        if ($options_arg_type->isInt()) {
            // in most cases we cannot infer a 100% correct specific return type though
            // unless all int are literal
            // @todo could handle all literal int cases
            return null;
        }

        foreach ($options_arg_type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TArray) {
                continue;
            }

            if ($atomic_type instanceof TInt) {
                continue;
            }

            if ($atomic_type instanceof TFloat) {
                // ignored
                continue;
            }

            if ($atomic_type instanceof TBool) {
                // ignored
                continue;
            }

            if ($codebase->analysis_php_version_id >= 8_00_00) {
                // throws for the invalid type
                // for the other types it will still work correctly
                // however "never" is a bottom type
                // and will be lost, therefore it's better to return it here
                // to identify hard to find bugs in the code
                return Type::getNever();
            }
            // before PHP 8, it's ignored but gives a PHP notice
        }

        // array|int type which is too complex for now
        // or any other invalid type
        return null;
    }

    public static function missingFilterCallbackCallable(
        string $function_id,
        CodeLocation $code_location,
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase
    ): Union {
        IssueBuffer::maybeAdd(
            new InvalidArgument(
                'The "options" key in ' . $function_id
                . ' must be a callable for FILTER_CALLBACK',
                $code_location,
                $function_id,
            ),
            $statements_analyzer->getSuppressedIssues(),
        );

        if ($codebase->analysis_php_version_id >= 8_00_00) {
            // throws
            return Type::getNever();
        }

        // flags are ignored here
        return Type::getNull();
    }

    /** @return array{Union, Union, Union} */
    public static function getFailsNotSetType(int $flags_int_used): array
    {
        $fails_type   = Type::getFalse();
        $not_set_type = Type::getNull();
        if (self::hasFlag($flags_int_used, FILTER_NULL_ON_FAILURE)) {
            $fails_type   = Type::getNull();
            $not_set_type = Type::getFalse();
        }

        $fails_or_not_set_type = new Union([new TNull(), new TFalse()]);
        return array(
            $fails_type,
            $not_set_type,
            $fails_or_not_set_type,
        );
    }

    public static function hasFlag(int $flags, int $flag): bool
    {
        if ($flags === 0) {
            return false;
        }

        if (($flags & $flag) === $flag) {
            return true;
        }

        return false;
    }

    public static function checkRedundantFlags(
        int $filter_int_used,
        int $flags_int_used,
        Union $fails_type,
        StatementsAnalyzer $statements_analyzer,
        CodeLocation $code_location,
        Codebase $codebase
    ): ?Union {
        $all_filters = self::getFilters($codebase);
        $flags_int_used_rest = $flags_int_used;
        foreach ($all_filters[ $filter_int_used ]['flags'] as $flag) {
            if ($flags_int_used_rest === 0) {
                break;
            }

            if (self::hasFlag($flags_int_used_rest, $flag)) {
                $flags_int_used_rest = $flags_int_used_rest ^ $flag;
            }
        }

        if ($flags_int_used_rest !== 0) {
            // invalid flags used
            // while they are silently ignored
            // usually it means there's a mistake and the filter doesn't actually do what one expects
            // as otherwise the flag wouldn't have been provided
            IssueBuffer::maybeAdd(
                new RedundantFlag(
                    'Not all flags used are supported by the filter used',
                    $code_location,
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        if (self::hasFlag($flags_int_used, FILTER_REQUIRE_ARRAY)
            && self::hasFlag($flags_int_used, FILTER_FORCE_ARRAY)) {
            IssueBuffer::maybeAdd(
                new RedundantFlag(
                    'Flag FILTER_FORCE_ARRAY is ignored when using FILTER_REQUIRE_ARRAY',
                    $code_location,
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        if ($filter_int_used === FILTER_VALIDATE_REGEXP
            && (
                self::hasFlag($flags_int_used, FILTER_REQUIRE_ARRAY)
                || self::hasFlag($flags_int_used, FILTER_FORCE_ARRAY)
                || self::hasFlag($flags_int_used, FILTER_REQUIRE_SCALAR))
        ) {
            IssueBuffer::maybeAdd(
                new RedundantFlag(
                    'FILTER_VALIDATE_REGEXP will ignore ' .
                    'FILTER_REQUIRE_ARRAY/FILTER_FORCE_ARRAY/FILTER_REQUIRE_SCALAR ' .
                    'as it only works on scalar types',
                    $code_location,
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        if (self::hasFlag($flags_int_used, FILTER_FLAG_STRIP_LOW)
            && self::hasFlag($flags_int_used, FILTER_FLAG_ENCODE_LOW)) {
            IssueBuffer::maybeAdd(
                new RedundantFlag(
                    'Using flag FILTER_FLAG_ENCODE_LOW is redundant when using FILTER_FLAG_STRIP_LOW',
                    $code_location,
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        if (self::hasFlag($flags_int_used, FILTER_FLAG_STRIP_HIGH)
            && self::hasFlag($flags_int_used, FILTER_FLAG_ENCODE_HIGH)) {
            IssueBuffer::maybeAdd(
                new RedundantFlag(
                    'Using flag FILTER_FLAG_ENCODE_HIGH is redundant when using FILTER_FLAG_STRIP_HIGH',
                    $code_location,
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        if (self::hasFlag($flags_int_used, FILTER_REQUIRE_ARRAY)
            && self::hasFlag($flags_int_used, FILTER_REQUIRE_SCALAR)) {
            IssueBuffer::maybeAdd(
                new RedundantFlag(
                    'You cannot use FILTER_REQUIRE_ARRAY together with FILTER_REQUIRE_SCALAR flag',
                    $code_location,
                ),
                $statements_analyzer->getSuppressedIssues(),
            );

            // FILTER_REQUIRE_ARRAY will make PHP ignore FILTER_FORCE_ARRAY
            return $fails_type;
        }

        return null;
    }

    /** @return array{Union|null, float|int|null, float|int|null, bool, non-falsy-string|true|null} */
    public static function getOptions(
        int $filter_int_used,
        int $flags_int_used,
        ?TKeyedArray $options,
        StatementsAnalyzer $statements_analyzer,
        CodeLocation $code_location,
        Codebase $codebase,
        string $function_id
    ): array {
        $default = null;
        $min_range = null;
        $max_range = null;
        $has_range = false;
        $regexp = null;

        if (!$options) {
            return [$default, $min_range, $max_range, $has_range, $regexp];
        }

        $all_filters = self::getFilters($codebase);
        foreach ($options->properties as $option => $option_value) {
            if (! isset($all_filters[ $filter_int_used ]['options'][ $option ])) {
                IssueBuffer::maybeAdd(
                    new RedundantFlag(
                        'The option ' . $option . ' is not valid for the filter used',
                        $code_location,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );

                continue;
            }

            if (! UnionTypeComparator::isContainedBy(
                $codebase,
                $option_value,
                $all_filters[ $filter_int_used ]['options'][ $option ],
            )) {
                // silently ignored by the function, but it's a bug in the code
                // since the filtering/option will not do what you expect
                IssueBuffer::maybeAdd(
                    new InvalidArgument(
                        'The option "' . $option . '" of ' . $function_id . ' expects '
                        . $all_filters[ $filter_int_used ]['options'][ $option ]->getId()
                        . ', but ' . $option_value->getId() . ' provided',
                        $code_location,
                        $function_id,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );

                continue;
            }

            if ($option === 'default') {
                $default = $option_value;

                if (self::hasFlag($flags_int_used, FILTER_NULL_ON_FAILURE)) {
                    IssueBuffer::maybeAdd(
                        new RedundantFlag(
                            'Redundant flag FILTER_NULL_ON_FAILURE when using the "default" option',
                            $code_location,
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }

                continue;
            }

            // currently only int ranges are supported
            // must be numeric, otherwise we would have continued above already
            if ($option === 'min_range' && $option_value->isSingleLiteral()) {
                if ($filter_int_used === FILTER_VALIDATE_INT) {
                    $min_range = (int) $option_value->getSingleLiteral()->value;
                } elseif ($filter_int_used === FILTER_VALIDATE_FLOAT) {
                    $min_range = (float) $option_value->getSingleLiteral()->value;
                }
            }

            if ($option === 'max_range' && $option_value->isSingleLiteral()) {
                if ($filter_int_used === FILTER_VALIDATE_INT) {
                    $max_range = (int) $option_value->getSingleLiteral()->value;
                } elseif ($filter_int_used === FILTER_VALIDATE_FLOAT) {
                    $max_range = (float) $option_value->getSingleLiteral()->value;
                }
            }

            if (($filter_int_used === FILTER_VALIDATE_INT || $filter_int_used === FILTER_VALIDATE_FLOAT)
                && ($option === 'min_range' || $option === 'max_range')
            ) {
                $has_range = true;
            }

            if ($filter_int_used === FILTER_VALIDATE_REGEXP
                && $option === 'regexp'
            ) {
                if ($option_value->isSingleStringLiteral()) {
                    /**
                     * if it's another type, we would have reported an error above already
                     * @var non-falsy-string $regexp
                     */
                    $regexp = $option_value->getSingleStringLiteral()->value;
                } elseif ($option_value->isString()) {
                    $regexp = true;
                }
            }
        }

        return [$default, $min_range, $max_range, $has_range, $regexp];
    }

    /**
     * @param float|int|null $min_range
     * @param float|int|null $max_range
     */
    protected static function isRangeValid(
        $min_range,
        $max_range,
        StatementsAnalyzer $statements_analyzer,
        CodeLocation $code_location,
        string $function_id
    ): bool {
        if ($min_range !== null && $max_range !== null && $min_range > $max_range) {
            IssueBuffer::maybeAdd(
                new InvalidArgument(
                    'min_range cannot be larger than max_range',
                    $code_location,
                    $function_id,
                ),
                $statements_analyzer->getSuppressedIssues(),
            );

            return false;
        }

        return true;
    }

    /**
     * can't split this because the switch is complex since there are too many possibilities
     *
     * @psalm-suppress ComplexMethod
     * @param Union|null $not_set_type null if undefined filtered variable will return $fails_type
     * @param float|int|null $min_range
     * @param float|int|null $max_range
     * @param non-falsy-string|true|null $regexp
     */
    public static function getReturnType(
        int $filter_int_used,
        int $flags_int_used,
        Union $input_type,
        Union $fails_type,
        ?Union $not_set_type,
        StatementsAnalyzer $statements_analyzer,
        CodeLocation $code_location,
        Codebase $codebase,
        string $function_id,
        bool $has_range,
        $min_range,
        $max_range,
        $regexp,
        bool $in_array_recursion = false
    ): Union {
        // if we are inside a recursion of e.g. array<never, never>
        // it will never fail or change the type, so we can immediately return
        if ($in_array_recursion && $input_type->isNever()) {
            return $input_type;
        }

        $from_array = [];
        // will only handle arrays correctly if either flag is set, otherwise always error
        // regexp doesn't work on arrays
        if ((self::hasFlag($flags_int_used, FILTER_FORCE_ARRAY) || self::hasFlag($flags_int_used, FILTER_REQUIRE_ARRAY))
            && $filter_int_used !== FILTER_VALIDATE_REGEXP
            && !self::hasFlag($flags_int_used, FILTER_REQUIRE_SCALAR)
        ) {
            foreach ($input_type->getAtomicTypes() as $key => $atomic_type) {
                if ($atomic_type instanceof TList) {
                    $atomic_type = $atomic_type->getKeyedArray();
                }

                if ($atomic_type instanceof TKeyedArray) {
                    $input_type = $input_type->getBuilder();
                    $input_type->removeType($key);
                    $input_type = $input_type->freeze();

                    $new = [];
                    foreach ($atomic_type->properties as $k => $property) {
                        if ($property->isNever()) {
                            $new[$k] = $property;
                            continue;
                        }

                        $new[$k] = self::getReturnType(
                            $filter_int_used,
                            $flags_int_used,
                            $property,
                            $fails_type,
                            // irrelevant in nested elements
                            null,
                            $statements_analyzer,
                            $code_location,
                            $codebase,
                            $function_id,
                            $has_range,
                            $min_range,
                            $max_range,
                            $regexp,
                            true,
                        );
                    }

                    // false positive error in psalm when we loop over a non-empty array
                    if ($new === array()) {
                        throw new UnexpectedValueException('This is impossible');
                    }

                    $fallback_params = null;
                    if ($atomic_type->fallback_params) {
                        [$keys_union, $values_union] = $atomic_type->fallback_params;
                        $values_union = self::getReturnType(
                            $filter_int_used,
                            $flags_int_used,
                            $values_union,
                            $fails_type,
                            // irrelevant in nested elements
                            null,
                            $statements_analyzer,
                            $code_location,
                            $codebase,
                            $function_id,
                            $has_range,
                            $min_range,
                            $max_range,
                            $regexp,
                            true,
                        );
                        $fallback_params = [$keys_union, $values_union];
                    }

                    $from_array[] = new TKeyedArray(
                        $new,
                        $atomic_type->class_strings,
                        $fallback_params,
                        $atomic_type->is_list,
                    );

                    continue;
                }

                if ($atomic_type instanceof TArray) {
                    $input_type = $input_type->getBuilder();
                    $input_type->removeType($key);
                    $input_type = $input_type->freeze();

                    [$keys_union, $values_union] = $atomic_type->type_params;
                    $values_union = self::getReturnType(
                        $filter_int_used,
                        $flags_int_used,
                        $values_union,
                        $fails_type,
                        // irrelevant in nested elements
                        null,
                        $statements_analyzer,
                        $code_location,
                        $codebase,
                        $function_id,
                        $has_range,
                        $min_range,
                        $max_range,
                        $regexp,
                        true,
                    );

                    if ($atomic_type instanceof TNonEmptyArray) {
                        $from_array[] = new TNonEmptyArray([$keys_union, $values_union]);
                    } else {
                        $from_array[] = new TArray([$keys_union, $values_union]);
                    }

                    continue;
                }

                // can be an array too
                if ($atomic_type instanceof TMixed) {
                    $from_array[] = new TArray(
                        [
                            new Union([new TArrayKey]),
                            new Union([new TMixed]),
                        ],
                    );
                }
            }
        }

        $can_fail = false;
        $filter_types = array();
        switch ($filter_int_used) {
            case FILTER_VALIDATE_FLOAT:
                if (!self::isRangeValid(
                    $min_range,
                    $max_range,
                    $statements_analyzer,
                    $code_location,
                    $function_id,
                )) {
                    $can_fail = true;
                    break;
                }

                foreach ($input_type->getAtomicTypes() as $atomic_type) {
                    if ($atomic_type instanceof TLiteralFloat) {
                        if ($min_range !== null && $min_range > $atomic_type->value) {
                            $can_fail = true;
                            continue;
                        }

                        if ($max_range !== null && $max_range < $atomic_type->value) {
                            $can_fail = true;
                            continue;
                        }

                        if ($min_range !== null || $max_range !== null || $has_range === false) {
                            $filter_types[] = $atomic_type;
                            continue;
                        }

                        // we don't know what the min/max of the range are
                        // and it might be out of the range too
                        // float ranges aren't supported yet
                        $filter_types[] = new TFloat();
                    } elseif ($atomic_type instanceof TFloat) {
                        if ($has_range === false) {
                            $filter_types[] = $atomic_type;
                            continue;
                        }

                        // float ranges aren't supported yet
                        $filter_types[] = new TFloat();
                    }

                    if ($atomic_type instanceof TLiteralInt) {
                        if ($min_range !== null && $min_range > $atomic_type->value) {
                            $can_fail = true;
                            continue;
                        }

                        if ($max_range !== null && $max_range < $atomic_type->value) {
                            $can_fail = true;
                            continue;
                        }

                        if ($min_range !== null || $max_range !== null || $has_range === false) {
                            $filter_types[] = new TLiteralFloat((float) $atomic_type->value);
                            continue;
                        }

                        // we don't know what the min/max of the range are
                        // and it might be out of the range too
                        $filter_types[] = new TFloat();
                    } elseif ($atomic_type instanceof TInt) {
                        $filter_types[] = new TFloat();

                        if ($has_range === false) {
                            continue;
                        }
                    }

                    if ($atomic_type instanceof TLiteralString) {
                        if (($string_to_float = filter_var($atomic_type->value, FILTER_VALIDATE_FLOAT)) === false) {
                            $can_fail = true;
                            continue;
                        }

                        if ($min_range !== null && $min_range > $string_to_float) {
                            $can_fail = true;
                            continue;
                        }

                        if ($max_range !== null && $max_range < $string_to_float) {
                            $can_fail = true;
                            continue;
                        }

                        if ($min_range !== null || $max_range !== null || $has_range === false) {
                            $filter_types[] = new TLiteralFloat($string_to_float);
                            continue;
                        }

                        // we don't know what the min/max of the range are
                        // and it might be out of the range too
                        $filter_types[] = new TFloat();
                    } elseif ($atomic_type instanceof TString) {
                        $filter_types[] = new TFloat();
                    }

                    if ($atomic_type instanceof TBool) {
                        if ($min_range !== null && $min_range > 1) {
                            $can_fail = true;
                            continue;
                        }

                        if ($max_range !== null && $max_range < 1) {
                            $can_fail = true;
                            continue;
                        }

                        if ($atomic_type instanceof TFalse) {
                            $can_fail = true;
                            continue;
                        }

                        if ($min_range !== null || $max_range !== null || $has_range === false) {
                            $filter_types[] = new TLiteralFloat(1.0);

                            if ($atomic_type instanceof TTrue) {
                                continue;
                            }
                        }

                        // we don't know what the min/max of the range are
                        // and it might be out of the range too
                        $filter_types[] = new TFloat();
                    }

                    // only these specific classes, not any class that extends either
                    // to avoid matching already better handled cases from above, e.g. float is numeric and scalar
                    if ($atomic_type instanceof TMixed
                        || get_class($atomic_type) === TNumeric::class
                        || get_class($atomic_type) === TScalar::class) {
                        $filter_types[] = new TFloat();
                    }

                    $can_fail = true;
                }
                break;
            case FILTER_VALIDATE_BOOLEAN:
                foreach ($input_type->getAtomicTypes() as $atomic_type) {
                    if ($atomic_type instanceof TBool) {
                        $filter_types[] = $atomic_type;
                        continue;
                    }

                    if (($atomic_type instanceof TLiteralInt && $atomic_type->value === 1)
                        || ($atomic_type instanceof TLiteralFloat && $atomic_type->value === 1.0)
                        || ($atomic_type instanceof TLiteralString
                            && in_array(strtolower($atomic_type->value), ['1', 'true', 'on', 'yes'], true))
                    ) {
                        $filter_types[] = new TTrue();
                        continue;
                    }

                    if (self::hasFlag($flags_int_used, FILTER_NULL_ON_FAILURE)
                        && (
                            ($atomic_type instanceof TLiteralInt && $atomic_type->value === 0)
                            || ($atomic_type instanceof TLiteralFloat && $atomic_type->value === 0.0)
                            || ($atomic_type instanceof TLiteralString
                                && in_array(strtolower($atomic_type->value), ['0', 'false', 'off', 'no', ''], true)
                            )
                        )
                    ) {
                        $filter_types[] = new TFalse();
                        continue;
                    }

                    if ($atomic_type instanceof TLiteralInt
                        || $atomic_type instanceof TLiteralFloat
                        || $atomic_type instanceof TLiteralString
                    ) {
                        // all other literals will fail
                        $can_fail = true;
                        continue;
                    }

                    if ($atomic_type instanceof TMixed
                        || $atomic_type instanceof TString
                        || $atomic_type instanceof TInt
                        || $atomic_type instanceof TFloat
                        || $atomic_type instanceof TNumeric
                        || $atomic_type instanceof TScalar) {
                        $filter_types[] = new TBool();
                    }

                    $can_fail = true;
                }
                break;
            case FILTER_VALIDATE_INT:
                if (!self::isRangeValid(
                    $min_range,
                    $max_range,
                    $statements_analyzer,
                    $code_location,
                    $function_id,
                )) {
                    $can_fail = true;
                    break;
                }

                $min_range = $min_range !== null ? (int) $min_range : null;
                $max_range = $max_range !== null ? (int) $max_range : null;

                if ($min_range !== null || $max_range !== null) {
                    $int_type = new TIntRange($min_range, $max_range);
                } else {
                    $int_type = new TInt();
                }

                foreach ($input_type->getAtomicTypes() as $atomic_type) {
                    if ($atomic_type instanceof TLiteralInt) {
                        if ($min_range !== null && $min_range > $atomic_type->value) {
                            $can_fail = true;
                            continue;
                        }

                        if ($max_range !== null && $max_range < $atomic_type->value) {
                            $can_fail = true;
                            continue;
                        }

                        if ($min_range !== null || $max_range !== null || $has_range === false) {
                            $filter_types[] = $atomic_type;
                            continue;
                        }

                        // we don't know what the min/max of the range are
                        // and it might be out of the range too
                        $filter_types[] = new TInt();
                    } elseif ($atomic_type instanceof TInt) {
                        if ($has_range === false) {
                            $filter_types[] = $atomic_type;
                            continue;
                        }

                        $filter_types[] = $int_type;
                    }

                    if ($atomic_type instanceof TLiteralFloat) {
                        if ((float) (int) $atomic_type->value !== $atomic_type->value) {
                            $can_fail = true;
                            continue;
                        }

                        if ($min_range !== null && $min_range > $atomic_type->value) {
                            $can_fail = true;
                            continue;
                        }

                        if ($max_range !== null && $max_range < $atomic_type->value) {
                            $can_fail = true;
                            continue;
                        }

                        if ($min_range !== null || $max_range !== null || $has_range === false) {
                            $filter_types[] = new TLiteralInt((int) $atomic_type->value);
                            continue;
                        }

                        // we don't know what the min/max of the range are
                        // and it might be out of the range too
                        $filter_types[] = $int_type;
                    } elseif ($atomic_type instanceof TFloat) {
                        $filter_types[] = $int_type;
                    }

                    if ($atomic_type instanceof TLiteralString) {
                        if (($string_to_int = filter_var($atomic_type->value, FILTER_VALIDATE_INT)) === false) {
                            $can_fail = true;
                            continue;
                        }

                        if ($min_range !== null && $min_range > $string_to_int) {
                            $can_fail = true;
                            continue;
                        }

                        if ($max_range !== null && $max_range < $string_to_int) {
                            $can_fail = true;
                            continue;
                        }

                        if ($min_range !== null || $max_range !== null || $has_range === false) {
                            $filter_types[] = new TLiteralInt($string_to_int);
                            continue;
                        }

                        // we don't know what the min/max of the range are
                        // and it might be out of the range too
                        $filter_types[] = $int_type;
                    } elseif ($atomic_type instanceof TString) {
                        $filter_types[] = $int_type;
                    }

                    if ($atomic_type instanceof TBool) {
                        if ($min_range !== null && $min_range > 1) {
                            $can_fail = true;
                            continue;
                        }

                        if ($max_range !== null && $max_range < 1) {
                            $can_fail = true;
                            continue;
                        }

                        if ($atomic_type instanceof TFalse) {
                            $can_fail = true;
                            continue;
                        }

                        if ($min_range !== null || $max_range !== null || $has_range === false) {
                            $filter_types[] = new TLiteralInt(1);

                            if ($atomic_type instanceof TTrue) {
                                continue;
                            }
                        }

                        // we don't know what the min/max of the range are
                        // and it might be out of the range too
                        $filter_types[] = $int_type;
                    }

                    if ($atomic_type instanceof TMixed
                        || get_class($atomic_type) === TNumeric::class
                        || get_class($atomic_type) === TScalar::class) {
                        $filter_types[] = $int_type;
                    }

                    $can_fail = true;
                }
                break;
            case FILTER_VALIDATE_IP:
            case FILTER_VALIDATE_MAC:
            case FILTER_VALIDATE_URL:
            case FILTER_VALIDATE_EMAIL:
                foreach ($input_type->getAtomicTypes() as $atomic_type) {
                    if ($atomic_type instanceof TNumericString) {
                        $can_fail = true;
                        continue;
                    }

                    if ($atomic_type instanceof TNonFalsyString) {
                        $filter_types[] = $atomic_type;
                    } elseif ($atomic_type instanceof TString) {
                        $filter_types[] = new TNonFalsyString();
                    } elseif ($atomic_type instanceof TMixed || $atomic_type instanceof TScalar) {
                        $filter_types[] = new TNonFalsyString();
                    }

                    $can_fail = true;
                }
                break;
            case FILTER_VALIDATE_REGEXP:
                // the regexp key is mandatory for this filter
                // it will only fail if the value exists, therefore it's after the checks above
                // this must be (and is) handled BEFORE calling this function though
                // since PHP 8+ throws instead of returning the fails case
                if ($regexp === null) {
                    $can_fail = true;
                    break;
                }

                // invalid regex
                if ($regexp !== true && @preg_match($regexp, 'placeholder') === false) {
                    $can_fail = true;
                    break;
                }

                foreach ($input_type->getAtomicTypes() as $atomic_type) {
                    if ($atomic_type instanceof TString
                        || $atomic_type instanceof TInt
                        || $atomic_type instanceof TFloat
                        || $atomic_type instanceof TNumeric
                        || $atomic_type instanceof TScalar
                        || $atomic_type instanceof TMixed) {
                        $filter_types[] = new TString();
                    }

                    $can_fail = true;
                }

                break;
            case FILTER_VALIDATE_DOMAIN:
                if (self::hasFlag($flags_int_used, FILTER_FLAG_HOSTNAME)) {
                    $string_type = new TNonEmptyString();
                } else {
                    $string_type = new TString();
                }

                foreach ($input_type->getAtomicTypes() as $atomic_type) {
                    if ($atomic_type instanceof TNonEmptyString) {
                        $filter_types[] = $atomic_type;
                    } elseif ($atomic_type instanceof TString) {
                        if (self::hasFlag($flags_int_used, FILTER_FLAG_HOSTNAME)) {
                            $filter_types[] = $string_type;
                        } else {
                            $filter_types[] = $atomic_type;
                        }
                    } elseif ($atomic_type instanceof TMixed
                        || $atomic_type instanceof TInt
                        || $atomic_type instanceof TFloat
                        || $atomic_type instanceof TScalar) {
                        $filter_types[] = $string_type;
                    }

                    $can_fail = true;
                }
                break;
            case FILTER_SANITIZE_EMAIL:
            case FILTER_SANITIZE_URL:
                foreach ($input_type->getAtomicTypes() as $atomic_type) {
                    if ($atomic_type instanceof TNumericString) {
                        $filter_types[] = $atomic_type;
                        continue;
                    }

                    if ($atomic_type instanceof TString) {
                        $filter_types[] = new TString();
                        continue;
                    }

                    if ($atomic_type instanceof TFloat
                        || $atomic_type instanceof TInt
                        || $atomic_type instanceof TNumeric) {
                        $filter_types[] = new TNumericString();
                        continue;
                    }

                    if ($atomic_type instanceof TTrue) {
                        $filter_types[] = Type::getAtomicStringFromLiteral('1');
                        continue;
                    }

                    if ($atomic_type instanceof TFalse) {
                        $filter_types[] = Type::getAtomicStringFromLiteral('');
                        continue;
                    }

                    if ($atomic_type instanceof TBool) {
                        $filter_types[] = Type::getAtomicStringFromLiteral('1');
                        $filter_types[] = Type::getAtomicStringFromLiteral('');
                        continue;
                    }

                    if ($atomic_type instanceof TMixed || $atomic_type instanceof TScalar) {
                        $filter_types[] = new TString();
                    }

                    $can_fail = true;
                }
                break;
            case FILTER_SANITIZE_ENCODED:
            case FILTER_SANITIZE_ADD_SLASHES:
            case 521: // 8.0.0 FILTER_SANITIZE_MAGIC_QUOTES has been removed.
            case FILTER_SANITIZE_SPECIAL_CHARS:
            case FILTER_SANITIZE_FULL_SPECIAL_CHARS:
            case FILTER_DEFAULT:
                foreach ($input_type->getAtomicTypes() as $atomic_type) {
                    if ($filter_int_used === FILTER_DEFAULT
                        && $flags_int_used === 0
                        && $atomic_type instanceof TString
                    ) {
                        $filter_types[] = $atomic_type;
                        continue;
                    }

                    if ($atomic_type instanceof TNumericString) {
                        $filter_types[] = $atomic_type;
                        continue;
                    }

                    if (in_array(
                        $filter_int_used,
                        [FILTER_SANITIZE_ENCODED, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_DEFAULT],
                        true,
                    )
                        && $atomic_type instanceof TNonEmptyString
                        && (self::hasFlag($flags_int_used, FILTER_FLAG_STRIP_LOW)
                            || self::hasFlag($flags_int_used, FILTER_FLAG_STRIP_HIGH)
                            || self::hasFlag($flags_int_used, FILTER_FLAG_STRIP_BACKTICK)
                        )
                    ) {
                        $filter_types[] = new TString();
                        continue;
                    }

                    if ($atomic_type instanceof TNonFalsyString) {
                        $filter_types[] = new TNonFalsyString();
                        continue;
                    }

                    if ($atomic_type instanceof TNonEmptyString) {
                        $filter_types[] = new TNonEmptyString();
                        continue;
                    }

                    if ($atomic_type instanceof TString) {
                        $filter_types[] = new TString();
                        continue;
                    }

                    if ($atomic_type instanceof TFloat
                        || $atomic_type instanceof TInt
                        || $atomic_type instanceof TNumeric) {
                        $filter_types[] = new TNumericString();
                        continue;
                    }

                    if ($atomic_type instanceof TTrue) {
                        $filter_types[] = Type::getAtomicStringFromLiteral('1');
                        continue;
                    }

                    if ($atomic_type instanceof TFalse) {
                        $filter_types[] = Type::getAtomicStringFromLiteral('');
                        continue;
                    }

                    if ($atomic_type instanceof TBool) {
                        $filter_types[] = Type::getAtomicStringFromLiteral('1');
                        $filter_types[] = Type::getAtomicStringFromLiteral('');
                        continue;
                    }

                    if ($atomic_type instanceof TMixed || $atomic_type instanceof TScalar) {
                        $filter_types[] = new TString();
                    }

                    $can_fail = true;
                }
                break;
            case 513: // 8.1.0 FILTER_SANITIZE_STRING and FILTER_SANITIZE_STRIPPED (alias) have been deprecated.
                foreach ($input_type->getAtomicTypes() as $atomic_type) {
                    if ($atomic_type instanceof TBool
                        || $atomic_type instanceof TString
                        || $atomic_type instanceof TInt
                        || $atomic_type instanceof TFloat
                        || $atomic_type instanceof TNumeric) {
                        // only basic checking since it's deprecated anyway and not worth the time
                        $filter_types[] = new TString();
                        continue;
                    }

                    if ($atomic_type instanceof TMixed || $atomic_type instanceof TScalar) {
                        $filter_types[] = new TString();
                    }

                    $can_fail = true;
                }
                break;
            case FILTER_SANITIZE_NUMBER_INT:
            case FILTER_SANITIZE_NUMBER_FLOAT:
                foreach ($input_type->getAtomicTypes() as $atomic_type) {
                    if ($atomic_type instanceof TLiteralString
                        || $atomic_type instanceof TLiteralInt
                        || $atomic_type instanceof TLiteralFloat
                    ) {
                        /** @var string|false $literal */
                        $literal = filter_var($atomic_type->value, $filter_int_used);
                        if ($literal === false) {
                            $can_fail = true;
                        } else {
                            $filter_types[] = Type::getAtomicStringFromLiteral($literal);
                        }

                        continue;
                    }

                    if ($atomic_type instanceof TFloat
                        || $atomic_type instanceof TNumericString
                        || $atomic_type instanceof TInt
                        || $atomic_type instanceof TNumeric) {
                        $filter_types[] = new TNumericString();
                        continue;
                    }

                    if ($atomic_type instanceof TString) {
                        $filter_types[] = new TNumericString();
                        // for numeric-string it won't collapse since https://github.com/vimeo/psalm/pull/10459
                        // therefore we can add both
                        $filter_types[] = Type::getAtomicStringFromLiteral('');
                        continue;
                    }

                    if ($atomic_type instanceof TTrue) {
                        $filter_types[] = Type::getAtomicStringFromLiteral('1');
                        continue;
                    }

                    if ($atomic_type instanceof TFalse) {
                        $filter_types[] = Type::getAtomicStringFromLiteral('');
                        continue;
                    }

                    if ($atomic_type instanceof TBool) {
                        $filter_types[] = Type::getAtomicStringFromLiteral('1');
                        $filter_types[] = Type::getAtomicStringFromLiteral('');
                        continue;
                    }

                    if ($atomic_type instanceof TMixed || $atomic_type instanceof TScalar) {
                        $filter_types[] = new TNumericString();
                        $filter_types[] = Type::getAtomicStringFromLiteral('');
                    }

                    $can_fail = true;
                }
                break;
        }

        if ($input_type->hasMixed()) {
            // can always fail if we have mixed
            // only for redundancy in case there's a mistake in the switch above
            $can_fail = true;
        }

        // if an array is required, ignore all types we created from non-array on first level
        if (!$in_array_recursion && self::hasFlag($flags_int_used, FILTER_REQUIRE_ARRAY)) {
            $filter_types = array();
        }

        $return_type = $fails_type;
        if ($filter_types !== array()
            && ($can_fail === true ||
                (!$in_array_recursion && !$not_set_type && $input_type->possibly_undefined)
            )) {
            $return_type = Type::combineUnionTypes(
                $return_type,
                TypeCombiner::combine($filter_types, $codebase),
                $codebase,
            );
        } elseif ($filter_types !== array()) {
            $return_type = TypeCombiner::combine($filter_types, $codebase);
        }

        if (!$in_array_recursion
            && !self::hasFlag($flags_int_used, FILTER_REQUIRE_ARRAY)
            && self::hasFlag($flags_int_used, FILTER_FORCE_ARRAY)) {
            $return_type = new Union([new TKeyedArray(
                [$return_type],
                null,
                null,
                true,
            )]);
        }

        if ($from_array !== array()) {
            $from_array_union = TypeCombiner::combine($from_array, $codebase);

            $return_type = Type::combineUnionTypes(
                $return_type,
                $from_array_union,
                $codebase,
            );
        }

        if ($in_array_recursion && $input_type->possibly_undefined) {
            $return_type = $return_type->setPossiblyUndefined(true);
        } elseif (!$in_array_recursion && $not_set_type && $input_type->possibly_undefined) {
            // in case of PHP CLI it will always fail for all filter_input even when they're set
            // to fix this we would have to add support for environments in Context
            // e.g. if php_sapi_name() === 'cli'
            $return_type = Type::combineUnionTypes(
                $return_type,
                // the not set type is not coerced into an array when FILTER_FORCE_ARRAY is used
                $not_set_type,
                $codebase,
            );
        }

        if (!$in_array_recursion) {
            $return_type = self::addReturnTaint(
                $statements_analyzer,
                $code_location,
                $return_type,
                $function_id,
            );
        }

        return $return_type;
    }

    private static function addReturnTaint(
        StatementsAnalyzer $statements_analyzer,
        CodeLocation $code_location,
        Union $return_type,
        string $function_id
    ): Union {
        if ($statements_analyzer->data_flow_graph
            && !in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
        ) {
            $function_return_sink = DataFlowNode::getForMethodReturn(
                $function_id,
                $function_id,
                null,
                $code_location,
            );

            $statements_analyzer->data_flow_graph->addNode($function_return_sink);

            $function_param_sink = DataFlowNode::getForMethodArgument(
                $function_id,
                $function_id,
                0,
                null,
                $code_location,
            );

            $statements_analyzer->data_flow_graph->addNode($function_param_sink);

            $statements_analyzer->data_flow_graph->addPath(
                $function_param_sink,
                $function_return_sink,
                'arg',
            );

            $return_type = $return_type->setParentNodes([$function_return_sink->id => $function_return_sink]);
        }

        return $return_type;
    }

    /** @return array<int, array{flags: list<int>, options: array<string, Union>}> */
    public static function getFilters(Codebase $codebase): array
    {
        $general_filter_flags = array(
            FILTER_REQUIRE_SCALAR,
            FILTER_REQUIRE_ARRAY,
            FILTER_FORCE_ARRAY,
            FILTER_FLAG_NONE, // does nothing, default
        );

        // https://www.php.net/manual/en/filter.filters.sanitize.php
        $sanitize_filters = array(
            FILTER_SANITIZE_EMAIL => array(
                'flags' => array(),
                'options' => array(),
            ),
            FILTER_SANITIZE_ENCODED => array(
                'flags' => array(
                    FILTER_FLAG_STRIP_LOW,
                    FILTER_FLAG_STRIP_HIGH,
                    FILTER_FLAG_STRIP_BACKTICK,
                    FILTER_FLAG_ENCODE_LOW,
                    FILTER_FLAG_ENCODE_HIGH,
                ),
                'options' => array(),
            ),
            FILTER_SANITIZE_NUMBER_FLOAT => array(
                'flags' => array(
                    FILTER_FLAG_ALLOW_FRACTION,
                    FILTER_FLAG_ALLOW_THOUSAND,
                    FILTER_FLAG_ALLOW_SCIENTIFIC,
                ),
                'options' => array(),
            ),
            FILTER_SANITIZE_NUMBER_INT => array(
                'flags' => array(),
                'options' => array(),
            ),
            FILTER_SANITIZE_SPECIAL_CHARS => array(
                'flags' => array(
                    FILTER_FLAG_STRIP_LOW,
                    FILTER_FLAG_STRIP_HIGH,
                    FILTER_FLAG_STRIP_BACKTICK,
                    FILTER_FLAG_ENCODE_HIGH,
                ),
                'options' => array(),
            ),
            FILTER_SANITIZE_FULL_SPECIAL_CHARS => array(
                'flags' => array(
                    FILTER_FLAG_NO_ENCODE_QUOTES,
                ),
                'options' => array(),
            ),
            FILTER_SANITIZE_URL => array(
                'flags' => array(),
                'options' => array(),
            ),
            FILTER_UNSAFE_RAW => array(
                'flags' => array(
                    FILTER_FLAG_STRIP_LOW,
                    FILTER_FLAG_STRIP_HIGH,
                    FILTER_FLAG_STRIP_BACKTICK,
                    FILTER_FLAG_ENCODE_LOW,
                    FILTER_FLAG_ENCODE_HIGH,
                    FILTER_FLAG_ENCODE_AMP,
                ),
                'options' => array(),
            ),

        );

        if ($codebase->analysis_php_version_id <= 7_03_00) {
            // FILTER_SANITIZE_MAGIC_QUOTES
            $sanitize_filters[521] = array(
                'flags' => array(),
                'options' => array(),
            );
        }

        if ($codebase->analysis_php_version_id <= 8_01_00) {
            // FILTER_SANITIZE_STRING
            $sanitize_filters[513] = array(
                'flags' => array(
                    FILTER_FLAG_NO_ENCODE_QUOTES,
                    FILTER_FLAG_STRIP_LOW,
                    FILTER_FLAG_STRIP_HIGH,
                    FILTER_FLAG_STRIP_BACKTICK,
                    FILTER_FLAG_ENCODE_LOW,
                    FILTER_FLAG_ENCODE_HIGH,
                    FILTER_FLAG_ENCODE_AMP,
                ),
                'options' => array(),
            );
        }

        if ($codebase->analysis_php_version_id >= 7_03_00) {
            // was added as a replacement for FILTER_SANITIZE_MAGIC_QUOTES
            $sanitize_filters[FILTER_SANITIZE_ADD_SLASHES] = array(
                'flags' => array(),
                'options' => array(),
            );
        }

        foreach ($sanitize_filters as $filter_int => $filter_data) {
            $sanitize_filters[$filter_int]['flags'] = array_merge($filter_data['flags'], $general_filter_flags);
        }

        // https://www.php.net/manual/en/filter.filters.validate.php
        // validation filters all match bitmask 0x100
        // all support FILTER_NULL_ON_FAILURE flag https://www.php.net/manual/en/filter.filters.flags.php
        $general_filter_flags_validate = array_merge($general_filter_flags, array(FILTER_NULL_ON_FAILURE));

        $validate_filters = array(
            FILTER_VALIDATE_BOOLEAN => array(
                'flags' => array(),
                'options' => array(),
            ),
            FILTER_VALIDATE_EMAIL => array(
                'flags' => array(
                    FILTER_FLAG_EMAIL_UNICODE,
                ),
                'options' => array(),
            ),
            FILTER_VALIDATE_FLOAT => array(
                'flags' => array(
                    FILTER_FLAG_ALLOW_THOUSAND,
                ),
                'options' => array(
                    'decimal' => new Union([
                        Type::getAtomicStringFromLiteral('.'),
                        Type::getAtomicStringFromLiteral(','),
                    ]),
                ),
            ),
            FILTER_VALIDATE_INT => array(
                'flags' => array(
                    FILTER_FLAG_ALLOW_OCTAL,
                    FILTER_FLAG_ALLOW_HEX,
                ),
                'options' => array(
                    'min_range' => Type::getNumeric(),
                    'max_range' => Type::getNumeric(),
                ),
            ),
            FILTER_VALIDATE_IP => array(
                'flags' => array(
                    FILTER_FLAG_IPV4,
                    FILTER_FLAG_IPV6,
                    FILTER_FLAG_NO_PRIV_RANGE,
                    FILTER_FLAG_NO_RES_RANGE,

                ),
                'options' => array(),
            ),
            FILTER_VALIDATE_MAC => array(
                'flags' => array(),
                'options' => array(),
            ),
            FILTER_VALIDATE_REGEXP => array(
                'flags' => array(),
                'options' => array(
                    'regexp' => Type::getNonFalsyString(),
                ),
            ),
            FILTER_VALIDATE_URL => array(
                'flags' => array(
                    FILTER_FLAG_PATH_REQUIRED,
                    FILTER_FLAG_QUERY_REQUIRED,
                ),
                'options' => array(),
            ),

        );

        if ($codebase->analysis_php_version_id >= 7_04_00) {
            $validate_filters[FILTER_VALIDATE_FLOAT]['options']['min_range'] = Type::getNumeric();
            $validate_filters[FILTER_VALIDATE_FLOAT]['options']['max_range'] = Type::getNumeric();
        }

        if ($codebase->analysis_php_version_id < 8_00_00) {
            // phpcs:ignore SlevomatCodingStandard.Numbers.RequireNumericLiteralSeparator.RequiredNumericLiteralSeparator
            $validate_filters[FILTER_VALIDATE_URL]['flags'][] = 65536; // FILTER_FLAG_SCHEME_REQUIRED
            // phpcs:ignore SlevomatCodingStandard.Numbers.RequireNumericLiteralSeparator.RequiredNumericLiteralSeparator
            $validate_filters[FILTER_VALIDATE_URL]['flags'][] = 131072; // FILTER_FLAG_HOST_REQUIRED
        }

        if ($codebase->analysis_php_version_id >= 8_02_00) {
            // phpcs:ignore SlevomatCodingStandard.Numbers.RequireNumericLiteralSeparator.RequiredNumericLiteralSeparator
            $validate_filters[FILTER_VALIDATE_IP]['flags'][] = 268435456; // FILTER_FLAG_GLOBAL_RANGE
        }

        if ($codebase->analysis_php_version_id >= 7_00_00) {
            $validate_filters[FILTER_VALIDATE_DOMAIN] = array(
                'flags' => array(
                    FILTER_FLAG_HOSTNAME,
                ),
                'options' => array(),
            );
        }

        foreach ($validate_filters as $filter_int => $filter_data) {
            $validate_filters[$filter_int]['flags'] = array_merge(
                $filter_data['flags'],
                $general_filter_flags_validate,
            );

            $default_options = array(
                'default' => Type::getMixed(),
            );
            $validate_filters[$filter_int]['options'] = array_merge($filter_data['options'], $default_options);
        }

        // https://www.php.net/manual/en/filter.filters.misc.php
        $other_filters = array(
            FILTER_CALLBACK => array(
                // the docs say that all flags are ignored
                // however this seems to be incorrect https://github.com/php/doc-en/issues/2708
                // however they can only be used in the options array, not as a param directly
                'flags' => $general_filter_flags_validate,
                // the options array is required for this filter
                // and must be a valid callback instead of an array like in other cases
                'options' => array(),
            ),
        );

        return $sanitize_filters + $validate_filters + $other_filters;
    }
}
