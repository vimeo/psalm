<?php

namespace Psalm\Checker;

use PhpParser;
use Psalm\StatementsSource;
use Psalm\Config;
use Psalm\FunctionLikeParameter;
use Psalm\IssueBuffer;
use Psalm\Issue\InvalidReturnType;
use Psalm\Type;

class FunctionChecker extends FunctionLikeChecker
{
    protected static $function_return_types = [];
    protected static $function_namespaces = [];
    protected static $existing_functions = [];
    protected static $deprecated_functions = [];
    protected static $have_registered_function = [];

    /**
     * @var array<string,array<string,array<FunctionLikeParameter>>>
     */
    protected static $file_function_params = [];

    /**
     * @var array<string,bool>
     */
    protected static $variadic_functions = [];

    protected static $builtin_function_params = [];
    protected static $builtin_functions = [];
    protected static $call_map = null;

    /**
     * @param PhpParser\Node\Stmt\Function_ $function
     * @param StatementsSource              $source
     * @param string                        $base_file_name
     */
    public function __construct(PhpParser\Node\Stmt\Function_ $function, StatementsSource $source, $base_file_name)
    {
        parent::__construct($function, $source);

        $this->registerFunction($function, $base_file_name);
    }

    /**
     * @param  string $function_id
     * @param  string $file_name
     * @return boolean
     */
    public static function functionExists($function_id, $file_name)
    {
        if (isset(self::$existing_functions[$file_name][$function_id])) {
            return true;
        }

        if (strpos($function_id, '::') !== false) {
            $function_id = strtolower(preg_replace('/^[^:]+::/', '', $function_id));
        }

        if (!isset(self::$builtin_functions[$function_id])) {
            self::extractReflectionInfo($function_id);
        }

        return self::$builtin_functions[$function_id];
    }

    /**
     * @param  string $function_id
     * @param  string $file_name
     * @return array<FunctionLikeParameter>
     */
    public static function getParams($function_id, $file_name)
    {
        if (isset(self::$builtin_functions[$function_id]) && self::$builtin_functions[$function_id]) {
            return self::$builtin_function_params[$function_id];
        }

        return self::$file_function_params[$file_name][$function_id];
    }

    /**
     * @param  string $function_id
     * @param  string $file_name
     * @return boolean
     */
    public static function isVariadic($function_id, $file_name)
    {
        return isset(self::$variadic_functions[$file_name][$function_id]);
    }

    /**
     * @param  string $function_id
     * @return void
     */
    protected static function extractReflectionInfo($function_id)
    {
        try {
            $reflection_function = new \ReflectionFunction($function_id);

            $reflection_params = $reflection_function->getParameters();

            self::$builtin_function_params[$function_id] = [];

            /** @var \ReflectionParameter $param */
            foreach ($reflection_params as $param) {
                self::$builtin_function_params[$function_id][] = self::getReflectionParamArray($param);
            }

            self::$builtin_functions[$function_id] = true;
        }
        catch (\ReflectionException $e) {
            self::$builtin_functions[$function_id] = false;
        }
    }

    /**
     * @param  string $function_id
     * @param  string $file_name
     * @return Type\Union|null
     */
    public static function getFunctionReturnTypes($function_id, $file_name)
    {
        if (!isset(self::$function_return_types[$file_name][$function_id])) {
            throw new \InvalidArgumentException('Do not know function ' . $function_id . ' in file ' . $file_name);
        }

        return self::$function_return_types[$file_name][$function_id]
            ? clone self::$function_return_types[$file_name][$function_id]
            : null;
    }

    /**
     * @param  PhpParser\Node\Stmt\Function_ $function
     * @param  string                        $file_name
     * @return void
     */
    protected function registerFunction(PhpParser\Node\Stmt\Function_ $function, $file_name)
    {
        $function_id = strtolower($function->name);

        if (isset(self::$have_registered_function[$file_name][$function_id])) {
            return;
        }

        self::$have_registered_function[$file_name][$function_id] = true;

        self::$function_namespaces[$file_name][$function_id] = $this->namespace;
        self::$existing_functions[$file_name][$function_id] = true;

        self::$file_function_params[$file_name][$function_id] = [];

        $function_param_names = [];

        /** @var PhpParser\Node\Param $param */
        foreach ($function->getParams() as $param) {
            $param_array = self::getParamArray($param, $this->absolute_class, $this->namespace, $this->getAliasedClasses());
            self::$file_function_params[$file_name][$function_id][] = $param_array;
            $function_param_names[$param->name] = $param_array->type;
        }

        $config = Config::getInstance();
        $return_type = null;

        $docblock_info = CommentChecker::extractDocblockInfo((string)$function->getDocComment());

        if ($docblock_info['deprecated']) {
            self::$deprecated_functions[$file_name][$function_id] = true;
        }

        if ($docblock_info['variadic']) {
            self::$variadic_functions[$file_name][$function_id] = true;
        }

        $this->suppressed_issues = $docblock_info['suppress'];

        if (isset($function->returnType)) {
            $return_type = Type::parseString($function->returnType);
        }

        if ($config->use_docblock_types) {
            if ($docblock_info['return_type']) {
                $return_type =
                    Type::parseString(
                        self::fixUpLocalType(
                            (string)$docblock_info['return_type'],
                            null,
                            $this->namespace,
                            $this->getAliasedClasses()
                        )
                    );
            }

            if ($docblock_info['params']) {
                $this->improveParamsFromDocblock(
                    $docblock_info['params'],
                    $function_param_names,
                    self::$file_function_params[$file_name][$function_id],
                    $function->getLine()
                );
            }
        }

        self::$function_return_types[$file_name][$function_id] = $return_type ?: false;
    }

    /**
     * @param  string $function_id
     * @psalm-return array<array<FunctionLikeParameter>>|null
     */
    public static function getParamsFromCallMap($function_id)
    {
        $call_map = self::getCallMap();

        $call_map_key = strtolower($function_id);

        if (!isset($call_map[$call_map_key])) {
            return null;
        }

        $call_map_functions = [];
        $call_map_functions[] = $call_map[$call_map_key];

        for ($i = 1; $i < 10; $i++) {
            if (isset($call_map[$call_map_key . '\'' . $i])) {
                $call_map_functions[] = $call_map[$call_map_key . '\'' . $i];
            }
            else {
                break;
            }
        }

        $function_type_options = [];

        foreach ($call_map_functions as $call_map_function_args) {
            array_shift($call_map_function_args);

            $function_types = [];

            foreach ($call_map_function_args as $arg_name => $arg_type) {
                $by_reference = false;

                if ($arg_name[0] === '&') {
                    $arg_name = substr($arg_name, 1);
                    $by_reference = true;
                }

                $function_types[] = new FunctionLikeParameter(
                    $arg_name,
                    $by_reference,
                    $arg_type ? Type::parseString($arg_type) : Type::getMixed(),
                    true // @todo - need to have non-optional parameters
                );
            }

            $function_type_options[] = $function_types;
        }

        return $function_type_options;
    }

    /**
     * @param  string                           $function_id
     * @param  array<PhpParser\Node\Arg>|null   $call_args
     * @param  string|null                      $file_name
     * @param  int|null                         $line_number
     * @param  array|null                       $suppressed_issues
     * @return Type\Union
     */
    public static function getReturnTypeFromCallMap(
        $function_id,
        array $call_args = null,
        $file_name = null,
        $line_number = null,
        array $suppressed_issues = null
    ) {
        $call_map_key = strtolower($function_id);

        if (in_array($call_map_key, ['str_replace', 'preg_replace', 'preg_replace_callback'])) {
            if (isset($call_args[2]->value->inferredType)) {

                /** @var Type\Union */
                $subject_type = $call_args[2]->value->inferredType;

                if (!$subject_type->hasString() && $subject_type->hasArray()) {
                    return Type::getArray();
                }

                return Type::getString();
            }
        }

        if (in_array($call_map_key, ['pathinfo'])) {
            if (isset($call_args[1])) {
                return Type::getString();
            }

            return Type::getArray();
        }

        $call_map = self::getCallMap();

        if (!isset($call_map[$call_map_key])) {
            throw new \InvalidArgumentException('Function ' . $function_id . ' was not found in callmap');
        }

        if ($call_args) {
            if ($call_map_key === 'array_map' || $call_map_key === 'array_filter') {
                $function_index = $call_map_key === 'array_map' ? 0 : 1;
                if (isset($call_args[$function_index])) {
                    $function_call_arg = $call_args[$function_index];

                    if ($function_call_arg->value instanceof PhpParser\Node\Expr\Closure) {
                        $closure_yield_types = [];
                        $closure_return_types = \Psalm\EffectsAnalyser::getReturnTypes($function_call_arg->value->stmts, $closure_yield_types, true);

                        if (!$closure_return_types) {
                            if (IssueBuffer::accepts(
                                new InvalidReturnType(
                                    'No return type could be found in the closure passed to ' . $call_map_key,
                                    $file_name,
                                    $line_number
                                ),
                                $suppressed_issues
                            )) {
                                return false;
                            }
                        }
                        else {
                            if ($call_map_key === 'array_map') {
                                $inner_type = new Type\Union($closure_return_types);
                                return new Type\Union([new Type\Generic('array', [Type::getInt(), $inner_type])]);
                            }
                            elseif (isset($call_args[0]->value->inferredType->types['array'])) {
                                $inner_type = clone $call_args[0]->value->inferredType->types['array']->type_params[1];
                                return new Type\Union([new Type\Generic('array', [Type::getInt(), $inner_type])]);
                            }

                        }
                    }
                    elseif ($function_call_arg->value instanceof PhpParser\Node\Scalar\String_) {
                        $mapped_function_id = strtolower($function_call_arg->value->value);

                        if (isset($call_map[$mapped_function_id][0])) {
                            if ($call_map[$mapped_function_id][0]) {
                                $mapped_function_return = Type::parseString($call_map[$mapped_function_id][0]);
                                return new Type\Union([new Type\Generic('array', [Type::getInt(), $mapped_function_return])]);
                            }
                        }
                        else {
                            // @todo handle array_map('some_custom_function', $arr)
                        }
                    }
                }

                // where there's no function passed to array_filter
                if ($call_map_key === 'array_filter' && isset($call_args[0]->value->inferredType) && $call_args[0]->value->inferredType->hasArray()) {
                    $inner_type = clone $call_args[0]->value->inferredType->types['array']->type_params[1];
                    return new Type\Union([new Type\Generic('array', [Type::getInt(), $inner_type])]);
                }

                return Type::getArray();
            }

            if ($call_map_key === 'array_values' || $call_map_key === 'array_unique') {
                if (isset($call_args[0]->value->inferredType) && $call_args[0]->value->inferredType->hasArray()) {
                    $inner_type = clone $call_args[0]->value->inferredType->types['array']->type_params[1];
                    return new Type\Union([new Type\Generic('array', [Type::getInt(), $inner_type])]);
                }
            }

            if ($call_map_key === 'array_keys') {
                if (isset($call_args[0]->value->inferredType) && $call_args[0]->value->inferredType->hasArray()) {
                    $inner_type = clone $call_args[0]->value->inferredType->types['array']->type_params[0];
                    return new Type\Union([new Type\Generic('array', [Type::getInt(), $inner_type])]);
                }
            }

            if ($call_map_key === 'array_merge') {
                $inner_value_types = [];
                $inner_key_types = [];

                foreach ($call_args as $offset => $call_arg) {
                    if (!isset($call_arg->value->inferredType)) {
                        return Type::getArray();
                    }

                    foreach ($call_arg->value->inferredType->types as $type_part) {
                        if (!$type_part instanceof Type\Generic) {
                            return Type::getArray();
                        }

                        if ($type_part->type_params[1]->isEmpty()) {
                            continue;
                        }

                        $inner_key_types = array_merge(array_values($type_part->type_params[0]->types), $inner_key_types);
                        $inner_value_types = array_merge(array_values($type_part->type_params[1]->types), $inner_value_types);
                    }

                    if ($inner_value_types) {
                        return new Type\Union([
                            new Type\Generic('array',
                                [
                                    Type::combineTypes($inner_key_types),
                                    Type::combineTypes($inner_value_types)
                                ]
                            )
                        ]);
                    }
                }

                return Type::getArray();
            }

            if ($call_map_key === 'array_diff') {
                if (!isset($call_args[0]->value->inferredType) || !$call_args[0]->value->inferredType->hasArray()) {
                    return Type::getArray();
                }

                return new Type\Union([
                    new Type\Generic('array',
                        [
                            Type::getInt(),
                            clone $call_args[0]->value->inferredType->types['array']->type_params[1]
                        ]
                    )
                ]);
            }

            if ($call_map_key === 'array_diff_key') {
                if (!isset($call_args[0]->value->inferredType) || !$call_args[0]->value->inferredType->hasArray()) {
                    return Type::getArray();
                }

                return clone $call_args[0]->value->inferredType;
            }

            if ($call_map_key === 'array_shift' || $call_map_key === 'array_pop') {
                if (!isset($call_args[0]->value->inferredType) || !$call_args[0]->value->inferredType->hasArray()) {
                    return Type::getMixed();
                }

                return clone $call_args[0]->value->inferredType->types['array']->type_params[1];
            }

            if ($call_map_key === 'explode' || $call_map_key === 'preg_split') {
                return Type::parseString('array<int, string>');
            }
        }


        if (!$call_map[$call_map_key][0]) {
            return Type::getMixed();
        }

        return Type::parseString($call_map[$call_map_key][0]);
    }

    /**
     * Gets the method/function call map
     *
     * @return array<array<string,string>>
     */
    protected static function getCallMap()
    {
        if (self::$call_map !== null) {
            return self::$call_map;
        }

        $call_map = require_once(__DIR__.'/../CallMap.php');

        self::$call_map = [];

        foreach ($call_map as $key => $value) {
            $cased_key = strtolower($key);
            self::$call_map[$cased_key] = $value;
        }

        return self::$call_map;
    }

    public static function inCallMap($key)
    {
        return isset(self::getCallMap()[strtolower($key)]);
    }

    public static function clearCache()
    {
        self::$function_return_types = [];
        self::$function_namespaces = [];
        self::$existing_functions = [];
        self::$deprecated_functions = [];
        self::$have_registered_function = [];

        self::$file_function_params = [];

        self::$variadic_functions = [];

        self::$builtin_function_params = [];
        self::$builtin_functions = [];
    }
}
