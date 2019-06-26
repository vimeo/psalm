<?php
namespace Psalm\Internal\Codebase;

use PhpParser;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\Type;
use Psalm\Type\Atomic\TCallable;
use Psalm\Storage\FunctionLikeParameter;
use function count;
use function assert;
use function strtolower;
use function array_shift;
use function substr;

/**
 * @internal
 *
 * Gets values from the call map array, which stores data about native functions and methods
 */
class CallMap
{
    const PHP_MAJOR_VERSION = 7;
    const PHP_MINOR_VERSION = 3;

    /**
     * @var ?int
     */
    private static $loaded_php_major_version = null;
    /**
     * @var ?int
     */
    private static $loaded_php_minor_version = null;

    /**
     * @var array<array<string,string>>|null
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
    public static function getCallableFromCallMapById(Codebase $codebase, $method_id, array $args)
    {
        $possible_callables = self::getCallablesFromCallMap($method_id);

        if ($possible_callables === null) {
            throw new \UnexpectedValueException(
                'Not expecting $function_param_options to be null for ' . $method_id
            );
        }

        return self::getMatchingCallableFromCallMapOptions($codebase, $possible_callables, $args);
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
        array $args
    ) {
        if (count($callables) === 1) {
            return $callables[0];
        }

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

                if (!isset($arg->value->inferredType)) {
                    continue;
                }

                $arg_type = $arg->value->inferredType;

                if ($arg_type->hasMixed()) {
                    continue;
                }

                if ($arg->unpack && !$function_param->is_variadic) {
                    if ($arg_type->hasArray()) {
                        /** @var Type\Atomic\TArray|Type\Atomic\ObjectLike */
                        $array_atomic_type = $arg_type->getTypes()['array'];

                        if ($array_atomic_type instanceof Type\Atomic\ObjectLike) {
                            $array_atomic_type = $array_atomic_type->getGenericArrayType();
                        }

                        $arg_type = $array_atomic_type->type_params[1];
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

            if ($all_args_match) {
                return $possible_callable;
            }
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
        if (isset(self::$call_map_callables[$function_id])) {
            return self::$call_map_callables[$function_id];
        }

        $call_map = self::getCallMap();

        $call_map_key = strtolower($function_id);

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

                $function_param->signature_type = null;

                $function_params[] = $function_param;
            }

            $possible_callables[] = new TCallable('callable', $function_params, $return_type);
        }

        self::$call_map_callables[$function_id] = $possible_callables;

        return $possible_callables;
    }

    /**
     * Gets the method/function call map
     *
     * @return array<string, array<int|string, string>>
     * @psalm-suppress MixedInferredReturnType as the use of require buggers things up
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedTypeCoercion
     * @psalm-suppress MixedReturnStatement
     */
    public static function getCallMap()
    {
        $codebase = ProjectAnalyzer::getInstance()->getCodebase();
        $analyzer_major_version = $codebase->php_major_version;
        $analyzer_minor_version = $codebase->php_minor_version;

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

        if ($analyzer_minor_version < self::PHP_MINOR_VERSION) {
            for ($i = self::PHP_MINOR_VERSION; $i > $analyzer_minor_version; $i--) {
                /**
                 * @var array{
                 *     old: array<string, array<int|string, string>>,
                 *     new: array<string, array<int|string, string>>
                 * }
                 * @psalm-suppress UnresolvableInclude
                 */
                $diff_call_map = require(__DIR__ . '/../CallMap_7' . $i . '_delta.php');

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
