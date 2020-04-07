<?php
namespace Psalm\Internal\Codebase;

use function array_shift;
use function assert;
use function count;
use function file_exists;
use PhpParser;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic\TCallable;
use function strtolower;
use function substr;
use function version_compare;

/**
 * @internal
 *
 * Gets values from the call map array, which stores data about native functions and methods
 */
class CallMap
{
    const PHP_MAJOR_VERSION = 7;
    const PHP_MINOR_VERSION = 4;
    const LOWEST_AVAILABLE_DELTA = 71;

    /**
     * @var ?int
     */
    private static $loaded_php_major_version = null;
    /**
     * @var ?int
     */
    private static $loaded_php_minor_version = null;

    /**
     * @var array<array<int|string,string>>|null
     */
    private static $call_map = null;

    /**
     * @var array<array<int, TCallable>>|null
     */
    private static $call_map_callables = [];

    /**
     * @param  string                           $method_id
     * @param  array<int, PhpParser\Node\Arg>   $args
     *
     * @return TCallable
     */
    public static function getCallableFromCallMapById(
        Codebase $codebase,
        $method_id,
        array $args,
        ?\Psalm\Internal\Provider\NodeDataProvider $nodes
    ) {
        $possible_callables = self::getCallablesFromCallMap($method_id);

        if ($possible_callables === null) {
            throw new \UnexpectedValueException(
                'Not expecting $function_param_options to be null for ' . $method_id
            );
        }

        return self::getMatchingCallableFromCallMapOptions(
            $codebase,
            $possible_callables,
            $args,
            $nodes
        );
    }

    /**
     * @param  array<int, TCallable>  $callables
     * @param  array<int, PhpParser\Node\Arg>                 $args
     *
     * @return TCallable
     */
    public static function getMatchingCallableFromCallMapOptions(
        Codebase $codebase,
        array $callables,
        array $args,
        ?\Psalm\NodeTypeProvider $nodes
    ) {
        if (count($callables) === 1) {
            return $callables[0];
        }

        $matching_param_count_callable = null;

        foreach ($callables as $possible_callable) {
            $possible_function_params = $possible_callable->params;

            assert($possible_function_params !== null);

            $all_args_match = true;

            $last_param = count($possible_function_params)
                ? $possible_function_params[count($possible_function_params) - 1]
                : null;

            $mandatory_param_count = count($possible_function_params);

            foreach ($possible_function_params as $i => $possible_function_param) {
                if ($possible_function_param->is_optional) {
                    $mandatory_param_count = $i;
                    break;
                }
            }

            if ($mandatory_param_count > count($args) && !($last_param && $last_param->is_variadic)) {
                continue;
            }

            foreach ($args as $argument_offset => $arg) {
                if ($argument_offset >= count($possible_function_params)) {
                    if (!$last_param || !$last_param->is_variadic) {
                        $all_args_match = false;
                        break;
                    }

                    $function_param = $last_param;
                } else {
                    $function_param = $possible_function_params[$argument_offset];
                }

                $param_type = $function_param->type;

                if (!$param_type) {
                    continue;
                }

                $arg_type = null;

                if (!$nodes
                    || !($arg_type = $nodes->getType($arg->value))
                ) {
                    continue;
                }

                if ($arg_type->hasMixed()) {
                    continue;
                }

                if ($arg->unpack && !$function_param->is_variadic) {
                    if ($arg_type->hasArray()) {
                        /**
                         * @psalm-suppress PossiblyUndefinedStringArrayOffset
                         * @var Type\Atomic\TArray|Type\Atomic\ObjectLike|Type\Atomic\TList
                         */
                        $array_atomic_type = $arg_type->getAtomicTypes()['array'];

                        if ($array_atomic_type instanceof Type\Atomic\ObjectLike) {
                            $arg_type = $array_atomic_type->getGenericValueType();
                        } elseif ($array_atomic_type instanceof Type\Atomic\TList) {
                            $arg_type = $array_atomic_type->type_param;
                        } else {
                            $arg_type = $array_atomic_type->type_params[1];
                        }
                    }
                }

                if (TypeAnalyzer::isContainedBy(
                    $codebase,
                    $arg_type,
                    $param_type,
                    true,
                    true
                )) {
                    continue;
                }

                $all_args_match = false;
                break;
            }

            if (count($args) === count($possible_function_params)) {
                $matching_param_count_callable = $possible_callable;
            }

            if ($all_args_match) {
                return $possible_callable;
            }
        }

        if ($matching_param_count_callable) {
            return $matching_param_count_callable;
        }

        // if we don't succeed in finding a match, set to the first possible and wait for issues below
        return $callables[0];
    }

    /**
     * @param  string $function_id
     *
     * @return array|null
     * @psalm-return array<int, TCallable>|null
     */
    public static function getCallablesFromCallMap($function_id)
    {
        $call_map_key = strtolower($function_id);

        if (isset(self::$call_map_callables[$call_map_key])) {
            return self::$call_map_callables[$call_map_key];
        }

        $call_map = self::getCallMap();

        if (!isset($call_map[$call_map_key])) {
            return null;
        }

        $call_map_functions = [];
        $call_map_functions[] = $call_map[$call_map_key];

        for ($i = 1; $i < 10; ++$i) {
            if (!isset($call_map[$call_map_key . '\'' . $i])) {
                break;
            }

            $call_map_functions[] = $call_map[$call_map_key . '\'' . $i];
        }

        $possible_callables = [];

        foreach ($call_map_functions as $call_map_function_args) {
            $return_type_string = array_shift($call_map_function_args);

            if (!$return_type_string) {
                $return_type = Type::getMixed();
            } else {
                $return_type = Type::parseString($return_type_string);
            }

            $function_params = [];

            $arg_offset = 0;

            /** @var string $arg_name - key type changed with above array_shift */
            foreach ($call_map_function_args as $arg_name => $arg_type) {
                $by_reference = false;
                $optional = false;
                $variadic = false;

                if ($arg_name[0] === '&') {
                    $arg_name = substr($arg_name, 1);
                    $by_reference = true;
                }

                if (substr($arg_name, -1) === '=') {
                    $arg_name = substr($arg_name, 0, -1);
                    $optional = true;
                }

                if (substr($arg_name, 0, 3) === '...') {
                    $arg_name = substr($arg_name, 3);
                    $variadic = true;
                }

                $param_type = $arg_type
                    ? Type::parseString($arg_type)
                    : Type::getMixed();

                $function_param = new FunctionLikeParameter(
                    $arg_name,
                    $by_reference,
                    $param_type,
                    null,
                    null,
                    $optional,
                    false,
                    $variadic
                );

                if ($arg_offset === 0
                    && ($call_map_key === 'exec'
                        || $call_map_key === 'shell_exec'
                        || $call_map_key === 'passthru'
                        || $call_map_key === 'system'
                        || $call_map_key === 'pcntl_exec'
                        || $call_map_key === 'file_put_contents'
                        || $call_map_key === 'fopen')
                ) {
                    $function_param->sink = Type\Union::TAINTED_INPUT_SHELL;
                }

                if ($arg_offset === 0
                    && ($call_map_key === 'print_r')
                ) {
                    $function_param->sink = Type\Union::TAINTED_INPUT_HTML;
                }

                $function_param->signature_type = null;

                $function_params[] = $function_param;

                $arg_offset++;
            }

            $possible_callables[] = new TCallable('callable', $function_params, $return_type);
        }

        self::$call_map_callables[$call_map_key] = $possible_callables;

        return $possible_callables;
    }

    /**
     * Gets the method/function call map
     *
     * @return array<string, array<int|string, string>>
     * @psalm-suppress MixedInferredReturnType as the use of require buggers things up
     * @psalm-suppress MixedTypeCoercion
     * @psalm-suppress MixedReturnStatement
     */
    public static function getCallMap()
    {
        $codebase = ProjectAnalyzer::getInstance()->getCodebase();
        $analyzer_major_version = $codebase->php_major_version;
        $analyzer_minor_version = $codebase->php_minor_version;

        $analyzer_version = $analyzer_major_version . '.' . $analyzer_minor_version;
        $current_version = self::PHP_MAJOR_VERSION . '.' . self::PHP_MINOR_VERSION;

        $analyzer_version_int = (int) ($analyzer_major_version . $analyzer_minor_version);
        $current_version_int = (int) (self::PHP_MAJOR_VERSION . self::PHP_MINOR_VERSION);

        if (self::$call_map !== null
            && $analyzer_major_version === self::$loaded_php_major_version
            && $analyzer_minor_version === self::$loaded_php_minor_version
        ) {
            return self::$call_map;
        }

        /** @var array<string, array<int|string, string>> */
        $call_map = require(__DIR__ . '/../CallMap.php');

        self::$call_map = [];

        foreach ($call_map as $key => $value) {
            $cased_key = strtolower($key);
            self::$call_map[$cased_key] = $value;
        }

        if (version_compare($analyzer_version, $current_version, '<')) {
            // the following assumes both minor and major versions a single digits
            for ($i = $current_version_int; $i > $analyzer_version_int && $i >= self::LOWEST_AVAILABLE_DELTA; --$i) {
                $delta_file = __DIR__ . '/../CallMap_' . $i . '_delta.php';
                if (!file_exists($delta_file)) {
                    continue;
                }
                /**
                 * @var array{
                 *     old: array<string, array<int|string, string>>,
                 *     new: array<string, array<int|string, string>>
                 * }
                 * @psalm-suppress UnresolvableInclude
                 */
                $diff_call_map = require($delta_file);

                foreach ($diff_call_map['new'] as $key => $_) {
                    $cased_key = strtolower($key);
                    unset(self::$call_map[$cased_key]);
                }

                foreach ($diff_call_map['old'] as $key => $value) {
                    $cased_key = strtolower($key);
                    self::$call_map[$cased_key] = $value;
                }
            }
        }

        self::$loaded_php_major_version = $analyzer_major_version;
        self::$loaded_php_minor_version = $analyzer_minor_version;

        return self::$call_map;
    }

    /**
     * @param   string $key
     *
     * @return  bool
     */
    public static function inCallMap($key)
    {
        return isset(self::getCallMap()[strtolower($key)]);
    }
}
