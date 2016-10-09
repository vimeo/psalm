<?php

namespace Psalm\Checker;

use PhpParser;
use Psalm\StatementsSource;
use Psalm\Config;
use Psalm\FunctionLikeParameter;
use Psalm\IssueBuffer;
use Psalm\Type;

class FunctionChecker extends FunctionLikeChecker
{
    protected static $function_return_types = [];
    protected static $function_namespaces = [];
    protected static $existing_functions = [];
    protected static $deprecated_functions = [];
    protected static $have_registered_function = [];
    protected static $file_function_params = [];
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

    public static function getParams($function_id, $file_name)
    {
        if (isset(self::$builtin_functions[$function_id]) && self::$builtin_functions[$function_id]) {
            return self::$builtin_function_params[$function_id];
        }

        return self::$file_function_params[$file_name][$function_id];
    }

    protected static function extractReflectionInfo($function_id)
    {
        try {
            $reflection_function = new \ReflectionFunction($function_id);

            $reflection_params = $reflection_function->getParameters();

            self::$builtin_function_params[$function_id] = [];

            foreach ($reflection_params as $param) {
                self::$builtin_function_params[$function_id][] = self::getReflectionParamArray($param);
            }

            self::$builtin_functions[$function_id] = true;
        }
        catch (\ReflectionException $e) {
            self::$builtin_functions[$function_id] = false;
        }
    }

    public static function getFunctionReturnTypes($function_id, $file_name)
    {
        if (!isset(self::$function_return_types[$file_name][$function_id])) {
            throw new \InvalidArgumentException('Do not know function');
        }

        return self::$function_return_types[$file_name][$function_id]
            ? clone self::$function_return_types[$file_name][$function_id]
            : null;
    }

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

        foreach ($function->getParams() as $param) {
            $param_array = $this->getParamArray($param);
            self::$file_function_params[$file_name][$function_id][] = $param_array;
            $function_param_names[$param->name] = $param_array->type;
        }

        $config = Config::getInstance();
        $return_type = null;

        $docblock_info = CommentChecker::extractDocblockInfo($function->getDocComment());

        if ($docblock_info['deprecated']) {
            self::$deprecated_functions[$file_name][$function_id] = true;
        }

        $this->suppressed_issues = $docblock_info['suppress'];

        if ($config->use_docblock_types) {
            if ($docblock_info['return_type']) {
                $return_type =
                    Type::parseString(
                        self::fixUpLocalType(
                            $docblock_info['return_type'],
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

        self::$function_return_types[$file_name][$function_id] = $return_type;
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

    public static function getReturnTypeFromCallMap($function_id, array $call_args, $file_name, $line_number, array $suppressed_issues)
    {
        $call_map_key = strtolower($function_id);

        if (in_array($call_map_key, ['str_replace', 'preg_replace', 'preg_replace_callback'])) {
            if (isset($call_args[2]->value->inferredType)) {

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

        if ($call_map_key === 'array_map') {
            if (isset($call_args[0])) {
                if ($call_args[0]->value instanceof PhpParser\Node\Expr\Closure) {
                    $closure_return_types = \Psalm\EffectsAnalyser::getReturnTypes($call_args[0]->value->stmts, true);

                    if (!$closure_return_types) {
                        if (IssueBuffer::accepts(
                            new InvalidReturnType(
                                'No return type could be found in the closure passed to array_map',
                                $file_name,
                                $line_number
                            ),
                            $suppressed_issues
                        )) {
                            return false;
                        }
                    }
                    else {
                        $inner_type = new Type\Union($closure_return_types);

                        return new Type\Union([new Type\Generic('array', [Type::getInt(), $inner_type])]);
                    }
                }
                elseif ($call_args[0]->value instanceof PhpParser\Node\Scalar\String_) {
                    $mapped_function_id = strtolower($call_args[0]->value->value);

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

            return Type::getArray();
        }

        if (in_array($call_map_key, ['array_filter', 'array_values'])) {
            if (isset($call_args[0]->value->inferredType) && $call_args[0]->value->inferredType->hasArray()) {
                return clone $call_args[0]->value->inferredType;
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
                        clone $call_args[0]->value->inferredType->type_paams[1]
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

        if ($call_map_key === 'explode' || $call_map_key === 'preg_split') {
            return Type::parseString('array<int, string>');
        }

        if (!isset($call_map[$call_map_key]) || !$call_map[$call_map_key][0]) {
            return Type::getMixed();
        }

        return Type::parseString($call_map[$call_map_key][0]);
    }

    /**
     * Gets the method/function call map
     *
     * @return array<array<string>>
     */
    protected static function getCallMap()
    {
        if (self::$call_map !== null) {
            return self::$call_map;
        }

        $call_map = require_once(__DIR__.'/../CallMap.php');

        self::$call_map = [];

        foreach ($call_map as $key => $value) {
            self::$call_map[strtolower($key)] = $value;
        }

        return self::$call_map;
    }
}
