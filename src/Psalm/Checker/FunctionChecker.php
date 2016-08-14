<?php

namespace Psalm\Checker;

use PhpParser;
use Psalm\StatementsSource;
use Psalm\Config;
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
            $function_id = preg_replace('/^[^:]+::/', '', $function_id);
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
        $function_id = $function->name;

        if (isset(self::$have_registered_function[$file_name][$function_id])) {
            throw new \LogicException('Cannot re-register function twice');
        }

        self::$have_registered_function[$file_name][$function_id] = true;

        self::$function_namespaces[$file_name][$function_id] = $this->namespace;
        self::$existing_functions[$file_name][$function_id] = true;

        self::$file_function_params[$file_name][$function_id] = [];

        $function_param_names = [];

        foreach ($function->getParams() as $param) {
            $param_array = $this->getParamArray($param);
            self::$file_function_params[$file_name][$function_id][] = $param_array;
            $function_param_names[$param->name] = $param_array['type'];
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
                            $this->aliased_classes
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
}
