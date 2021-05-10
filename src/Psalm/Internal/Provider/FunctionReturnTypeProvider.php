<?php
namespace Psalm\Internal\Provider;

use PhpParser;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface as LegacyFunctionReturnTypeProviderInterface;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\StatementsSource;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Union;

use function count;
use function strtolower;
use function is_subclass_of;
use function function_exists;
use function is_array;
use function strlen;

class FunctionReturnTypeProvider
{
    /**
     * Whitelisted methods for execution
     *
     * @var array<lowercase-string, true>
     */
    private const WHITELIST = [
        // Core
        'strlen' => true,
        'strcmp' => true,
        'strncmp' => true,
        'strcasecmp' => true,
        'strncasecmp' => true,

        // ctype
        'ctype_alnum' => true,
        'ctype_alpha' => true,
        'ctype_cntrl' => true,
        'ctype_digit' => true,
        'ctype_lower' => true,
        'ctype_graph' => true,
        'ctype_print' => true,
        'ctype_punct' => true,
        'ctype_space' => true,
        'ctype_upper' => true,
        'ctype_xdigit' => true,

        // standard (escaping)
        'addcslashes' => true,
        'addslashes' => true,
        'escapeshellarg' => true,
        'escapeshellcmd' => true,
        'html_entity_decode' => true,
        'htmlentities' => true,
        'htmlspecialchars' => true,
        'htmlspecialchars_decode' => true,
        'http_build_query' => true,

        // standard (arrays except sorting functions)
        'array_change_key_case' => true,
        'array_chunk' => true,
        'array_column' => true,
        'array_combine' => true,
        'array_count_values' => true,
        'array_diff' => true,
        'array_diff_assoc' => true,
        'array_diff_key' => true,
        'array_diff_uassoc' => true,
        'array_diff_ukey' => true,
        'array_fill' => true,
        'array_fill_keys' => true,
        'array_filter' => true,
        'array_flip' => true,
        'array_intersect' => true,
        'array_intersect_assoc' => true,
        'array_intersect_key' => true,
        'array_intersect_uassoc' => true,
        'array_intersect_ukey' => true,
        'array_key_exists' => true,
        'array_key_first' => true,
        'array_key_last' => true,
        'array_keys' => true,
        'array_map' => true,
        'array_merge' => true,
        'array_merge_recursive' => true,
        'array_multisort' => true,
        'array_pad' => true,
        'array_pop' => true,
        'array_product' => true,
        'array_push' => true,
        'array_reduce' => true,
        'array_replace' => true,
        'array_replace_recursive' => true,
        'array_reverse' => true,
        'array_search' => true,
        'array_shift' => true,
        'array_slice' => true,
        'array_splice' => true,
        'array_sum' => true,
        'array_udiff' => true,
        'array_udiff_assoc' => true,
        'array_udiff_uassoc' => true,
        'array_uintersect' => true,
        'array_uintersect_assoc' => true,
        'array_uintersect_uassoc' => true,
        'array_unique' => true,
        'array_unshift' => true,
        'array_values' => true,
        'compact' => true,
        'count' => true,
        'sizeof' => true,
        'end' => true,
        'explode' => true,
        'implode' => true,
        'in_array' => true,
        'join' => true,
        'range' => true,

        // standard (strings)
        'chop' => true,
        'chunk_split' => true,
        'count_chars' => true,
        'lcfirst' => true,
        'ltrim' => true,
        'rtrim' => true,
        'str_contains' => true,
        'str_ends_with' => true,
        'str_getcsv' => true,
        'str_ireplace' => true,
        'str_pad' => true,
        'str_repeat' => true,
        'str_replace' => true,
        'str_rot13' => true,
        'str_split' => true,
        'str_starts_with' => true,
        'str_word_count' => true,
        'strchr' => true,
        'strcoll' => true,
        'strcspn' => true,
        'strip_tags' => true,
        'stripcslashes' => true,
        'stripos' => true,
        'stripslashes' => true,
        'stristr' => true,
        'strnatcasecmp' => true,
        'strnatcmp' => true,
        'strpbrk' => true,
        'strpos' => true,
        'strptime' => true,
        'strrchr' => true,
        'strrev' => true,
        'strripos' => true,
        'strrpos' => true,
        'strspn' => true,
        'strstr' => true,
        'strtok' => true,
        'strtolower' => true,
        'strtoupper' => true,
        'strtr' => true,
        'strval' => true,
        'substr' => true,
        'substr_compare' => true,
        'substr_count' => true,
        'substr_replace' => true,
        'trim' => true,
        'ucfirst' => true,
        'ucwords' => true,
        'wordwrap' => true,

        // standard (string formatting)
        'number_format' => true,
        'sprintf' => true,
        'vsprintf' => true,

        // standard (encoding)
        'chr' => true,
        'base64_decode' => true,
        'base64_encode' => true,
        'base_convert' => true,
        'bin2hex' => true,
        'bindec' => true,
        'convert_uudecode' => true,
        'convert_uuencode' => true,
        'decbin' => true,
        'dechex' => true,
        'decoct' => true,
        'hex2bin' => true,
        'hexdec' => true,
        'octdec' => true,
        'ord' => true,

        'pack' => true,
        'unpack' => true,

        'nl2br' => true,
        'parse_url' => true,
        'php_strip_whitespace' => true,
        'quoted_printable_decode' => true,
        'quoted_printable_encode' => true,
        'quotemeta' => true,
        'rawurldecode' => true,
        'rawurlencode' => true,
        'urldecode' => true,
        'urlencode' => true,
        'utf8_decode' => true,
        'utf8_encode' => true,

        'hebrev' => true,

        'ip2long' => true,
        'long2ip' => true,

        'highlight_string' => true,

        // standard (filesystem, pure)
        'basename' => true,
        'dirname' => true,
        'fnmatch' => true,

        // standard (math)
        'abs' => true,
        'acos' => true,
        'acosh' => true,
        'asin' => true,
        'asinh' => true,
        'atan' => true,
        'atan2' => true,
        'atanh' => true,
        'ceil' => true,
        'cos' => true,
        'cosh' => true,
        'deg2rad' => true,
        'exp' => true,
        'expm1' => true,
        'fdiv' => true,
        'floor' => true,
        'fmod' => true,
        'hypot' => true,
        'intdiv' => true,
        'intval' => true,
        'is_finite' => true,
        'is_infinite' => true,
        'is_nan' => true,
        'log' => true,
        'log10' => true,
        'log1p' => true,
        'max' => true,
        'min' => true,
        'pi' => true,
        'pow' => true,
        'rad2deg' => true,
        'round' => true,
        'sin' => true,
        'sinh' => true,
        'sqrt' => true,
        'tan' => true,
        'tanh' => true,

        // standard (hashes)
        'crc32' => true,
        'sha1' => true,
        'md5' => true,

        // standard (type juggling)
        'boolval' => true,
        'doubleval' => true,
        'floatval' => true,
        'get_debug_type' => true,
        'gettype' => true,
        'is_array' => true,
        'is_bool' => true,
        'is_callable' => true,
        'is_countable' => true,
        'is_double' => true,
        'is_float' => true,
        'is_int' => true,
        'is_integer' => true,
        'is_iterable' => true,
        'is_long' => true,
        'is_null' => true,
        'is_numeric' => true,
        'is_object' => true,
        'is_resource' => true,
        'is_scalar' => true,
        'is_string' => true,

        // standard (phonetic string manipulation)
        'levenshtein' => true,
        'metaphone' => true,
        'soundex' => true,

        // standard (misc)
        'version_compare' => true,
    ];

    /**
     * @var array<
     *   lowercase-string,
     *   array<\Closure(FunctionReturnTypeProviderEvent) : ?Type\Union>
     * >
     */
    private static $handlers = [];

    /**
     * @var array<
     *   lowercase-string,
     *   array<\Closure(
     *     StatementsSource,
     *     non-empty-string,
     *     list<PhpParser\Node\Arg>,
     *     Context,
     *     CodeLocation
     *   ) : ?Type\Union>
     * >
     */
    private static $legacy_handlers = [];

    public function __construct()
    {
        self::$handlers = [];
        self::$legacy_handlers = [];

        $this->registerClass(ReturnTypeProvider\ArrayChunkReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayColumnReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayFilterReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayMapReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayMergeReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayPadReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayPointerAdjustmentReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayPopReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayRandReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayReduceReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArraySliceReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayReverseReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayUniqueReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayValuesReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayFillReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\FilterVarReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\IteratorToArrayReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ParseUrlReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\StrReplaceReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\StrTrReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\VersionCompareReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\MktimeReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ExplodeReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\GetObjectVarsReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\GetClassMethodsReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\FirstArgStringReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\HexdecReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\MinMaxReturnTypeProvider::class);
    }

    /**
     * @param class-string $class
     */
    public function registerClass(string $class): void
    {
        if (is_subclass_of($class, LegacyFunctionReturnTypeProviderInterface::class, true)) {
            $callable = \Closure::fromCallable([$class, 'getFunctionReturnType']);

            foreach ($class::getFunctionIds() as $function_id) {
                $this->registerLegacyClosure($function_id, $callable);
            }
        } elseif (is_subclass_of($class, FunctionReturnTypeProviderInterface::class, true)) {
            $callable = \Closure::fromCallable([$class, 'getFunctionReturnType']);

            foreach ($class::getFunctionIds() as $function_id) {
                $this->registerClosure($function_id, $callable);
            }
        }
    }

    /**
     * @param lowercase-string $function_id
     * @param \Closure(FunctionReturnTypeProviderEvent) : ?Type\Union $c
     */
    public function registerClosure(string $function_id, \Closure $c): void
    {
        self::$handlers[$function_id][] = $c;
    }

    /**
     * @param lowercase-string $function_id
     * @param \Closure(
     *     StatementsSource,
     *     non-empty-string,
     *     list<PhpParser\Node\Arg>,
     *     Context,
     *     CodeLocation
     *   ) : ?Type\Union $c
     */
    public function registerLegacyClosure(string $function_id, \Closure $c): void
    {
        self::$legacy_handlers[$function_id][] = $c;
    }

    public function has(string $function_id) : bool
    {
        return isset(self::$handlers[strtolower($function_id)]) ||
            isset(self::$legacy_handlers[strtolower($function_id)]) ||
            isset(self::WHITELIST[strtolower($function_id)]);
    }

    /**
     * @param  non-empty-string $function_id
     * @param  list<PhpParser\Node\Arg>  $call_args
     */
    public function getReturnType(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ): ?Type\Union {
        $codebase = $statements_source->getCodebase();
        $types = null;
        if ($statements_source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer &&
            isset(self::WHITELIST[$function_id]) &&
            function_exists($function_id) &&
            InternalCallMapHandler::getCallablesFromCallMap($function_id) &&
            $codebase->functions->isCallMapFunctionPure(
                $codebase,
                $statements_source->getNodeTypeProvider(),
                $function_id,
                $call_args
            )
        ) {
            $node_data = $statements_source->node_data;
            $callable = InternalCallMapHandler::getCallableFromCallMapById(
                $codebase,
                $function_id,
                $call_args,
                $node_data
            );
            $required = 0;
            $byRef = false;
            $variadic = false;
            foreach ($callable->params ?? [] as $param) {
                if (!$param->is_optional) {
                    $required++;
                }
                if ($param->by_ref) {
                    $byRef = true;
                }
                if ($param->is_variadic) {
                    $variadic = true;
                }
            }
            if ($byRef && $function_id === 'end') {
                $byRef = false;
            }
            $has_leftover = false;
            $type_args = [];
            foreach ($call_args as $arg) {
                $type = $node_data->getType($arg->value);
                if ($arg->unpack) {
                    if (!$type) {
                        $type_args []= null;
                        $has_leftover = true;
                        continue;
                    }
                    $had_array = false;
                    foreach (self::extractLiterals($type, $has_leftover) as $elem) {
                        if (is_array($elem)) {
                            if ($had_array) {
                                $has_leftover = true;
                                continue;
                            }
                            $had_array = true;
                            /**
                             * @psalm-suppress MixedAssignment
                             * @psalm-suppress MixedArgument
                             */
                            foreach ($elem as $sub) {
                                $type_args []= Type::fromLiteral($sub);
                            }
                        } else {
                            $has_leftover = true;
                        }
                    }
                } else {
                    $type_args []= $type;
                }
            }
            if (!$byRef &&
                $callable->params &&
                $required <= count($type_args) &&
                (count($type_args) <= count($callable->params) || $variadic)
            ) {
                $maxStrLen = \Psalm\Config::getInstance()->max_string_length;
                foreach ($this->permutateArguments(
                    $callable->params,
                    $type_args,
                    $codebase,
                    $has_leftover
                ) as $args) {
                    try {
                        /**
                         * @psalm-suppress PossiblyInvalidArgument
                         * @psalm-suppress PossiblyUndefinedIntArrayOffset
                         * @psalm-suppress PossiblyInvalidOperand
                         * @psalm-suppress PossiblyInvalidCast
                         */
                        if (($function_id === 'array_combine' && count($args[0]) !== count($args[1])) ||
                            ($function_id === 'str_repeat' && (strlen($args[0]) * $args[1]) >= $maxStrLen) ||
                            ($function_id === 'str_pad' && $args[1] >= $maxStrLen) ||
                            ($function_id === 'array_pad' && $args[1] > 100) ||
                            ($function_id === 'array_fill' && $args[1] > 100)
                        ) {
                            $has_leftover = true;
                            continue;
                        }
                        /** @psalm-suppress MixedArgument */
                        $newTypes = Type::fromLiteral($function_id(...$args));
                        $types = $types ? Type::combineUnionTypes($types, $newTypes) : $newTypes;
                    } catch (\Throwable $e) {
                    }
                }

                if (!$has_leftover && $types) {
                    return $types;
                }
            }
        }

        foreach (self::$legacy_handlers[strtolower($function_id)] ?? [] as $function_handler) {
            $return_type = $function_handler(
                $statements_source,
                $function_id,
                $call_args,
                $context,
                $code_location
            );

            if ($return_type) {
                return $types ? Type::combineUnionTypes($types, $return_type) : $return_type;
            }
        }

        foreach (self::$handlers[strtolower($function_id)] ?? [] as $function_handler) {
            $event = new FunctionReturnTypeProviderEvent(
                $statements_source,
                $function_id,
                $call_args,
                $context,
                $code_location
            );
            $return_type = $function_handler($event);

            if ($return_type) {
                return $types ? Type::combineUnionTypes($types, $return_type) : $return_type;
            }
        }

        return null;
    }


    /**
     * Permutate arguments
     *
     * @param list<FunctionLikeParameter> $params
     * @param list<?Union> $args
     * @param NodeDataProvider $node_data
     * @param Codebase $codebase
     * @param bool $has_leftover
     * @param array<int, array|float|int|string> $current
     * @param integer $index
     * @return \Generator<int, array<int, array|float|int|string>, null, void>
     */
    public static function permutateArguments(
        array $params,
        array $args,
        Codebase $codebase,
        bool &$has_leftover,
        array $current = [],
        int $index = 0,
        int $paramsIndex = 0
    ): \Generator {
        if ($index === count($args)) {
            yield $current;
            return;
        }

        $cur_leftover = false;
        foreach (self::getAllowedLiteralParamValues(
            $args[$index],
            $params[$paramsIndex]->type,
            $codebase,
            $cur_leftover
        ) as $value) {
            $current[$index] = $value;
            yield from self::permutateArguments(
                $params,
                $args,
                $codebase,
                $has_leftover,
                $current,
                $index+1,
                $params[$paramsIndex]->is_variadic ? $paramsIndex : $paramsIndex+1
            );
        }
        if ($cur_leftover) {
            $has_leftover = true;
        }
    }

    /**
     * Get all allowed literal values for the specified parameter
     *
     * @param ?Union $type
     * @param ?Union $required_type
     * @param boolean $has_leftover
     * @return list<array|float|int|string>
     */
    public static function getAllowedLiteralParamValues(
        ?Union $type,
        ?Union $required_type,
        Codebase $codebase,
        bool &$has_leftover
    ): array {
        if (!$type) {
            return [];
        }
        if (!$required_type || $required_type->hasMixed()) {
            $has_leftover = false;
            $accepted = $type;
        } else {
            $acceptedKeys = [];
            if (!UnionTypeComparator::canBeContainedBy($codebase, $type, $required_type, false, false, $acceptedKeys)) {
                return [];
            }
            $accepted = clone $type;
            $allKeys = \array_keys($type->getAtomicTypes());
            foreach ($allKeys as $atomic_key) {
                if (!isset($acceptedKeys[$atomic_key])) {
                    $has_leftover = true;
                    $accepted->removeType($atomic_key);
                }
            }
        }
        $res = self::extractLiterals($accepted, $has_leftover);
        return $res;
    }


    /**
     * Extract literal types
     *
     * @param Union $type
     * @param boolean $has_leftover
     *
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
     *
     * @return list<array|float|int|string>
     */
    public static function extractLiterals(
        Union $type,
        bool &$has_leftover
    ): array {
        $values = [];
        if ($type->possibly_undefined) {
            $has_leftover = true;
        }
        foreach ($type->getAtomicTypes() as $atomic_key_type) {
            if ($atomic_key_type instanceof TLiteralString) {
                $values []= $atomic_key_type->value;
            } elseif ($atomic_key_type instanceof TLiteralClassString) {
                $values []= $atomic_key_type->value;
            } elseif ($atomic_key_type instanceof TLiteralInt) {
                $values []= $atomic_key_type->value;
            } elseif ($atomic_key_type instanceof TLiteralFloat) {
                $values []= $atomic_key_type->value;
            } elseif ($atomic_key_type instanceof TTrue || $atomic_key_type instanceof TFalse) {
                $values []= $atomic_key_type instanceof TTrue;
            } elseif ($atomic_key_type instanceof TNull) {
                $values []= null;
            } elseif ($atomic_key_type instanceof TKeyedArray) {
                $skip = false;
                $possibly_undefined = false;
                $array = [];
                foreach ($atomic_key_type->properties as $key => $sub) {
                    if ($sub->possibly_undefined) {
                        $possibly_undefined = true;
                        continue;
                    }
                    $res = self::extractLiterals($sub, $skip);
                    if (count($res) !== 1) {
                        $skip = true;
                        break;
                    }
                    $array[$key] = $res[0];
                }
                if ($skip) {
                    $has_leftover = true;
                } elseif ($array || !$possibly_undefined) {
                    $values []= $array;
                }
            } elseif ($atomic_key_type instanceof TList) {
                foreach ($atomic_key_type->type_param->getAtomicTypes() as $sub) {
                    foreach (self::extractLiterals(new Union([$sub]), $has_leftover) as $subsub) {
                        $values[] = [$subsub];
                    }
                }
            } elseif ($atomic_key_type instanceof TArray && $atomic_key_type->type_params[1]->isEmpty()) {
                $values []= [];
            } else {
                $has_leftover = true;
            }
        }
        return $values;
    }
}
