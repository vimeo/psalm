<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\FunctionLikeParameter;
use Psalm\Issue\InvalidReturnType;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Type;

class FunctionChecker extends FunctionLikeChecker
{
    /**
     * @var array<array<string,string>>|null
     */
    protected static $call_map = null;

    /** @var FileChecker|null */
    protected static $callmap_file_checker = null;

    /**
     * @var array<string, FunctionLikeStorage>
     */
    protected static $builtin_functions = [];

    /**
     * @var array<string, FunctionLikeStorage>
     */
    public static $stubbed_functions = [];

    /**
     * @param mixed                         $function
     * @param StatementsSource              $source
     */
    public function __construct($function, StatementsSource $source)
    {
        if (!$function instanceof PhpParser\Node\Stmt\Function_) {
            throw new \InvalidArgumentException('Bad');
        }

        parent::__construct($function, $source);
    }

    /**
     * @param  string $function_id
     * @param  string $file_path
     *
     * @return bool
     */
    public static function functionExists($function_id, $file_path)
    {
        if (isset(FileChecker::$storage[$file_path]->functions[$function_id])) {
            return true;
        }

        if (strpos($function_id, '::') !== false) {
            $function_id = strtolower(preg_replace('/^[^:]+::/', '', $function_id));
        }

        if (isset(self::$builtin_functions[$function_id])) {
            return true;
        }

        if (isset(self::$stubbed_functions[$function_id])) {
            return true;
        }

        if (self::extractReflectionInfo($function_id) === false) {
            return false;
        }

        return true;
    }

    /**
     * @param  string $function_id
     * @param  string $file_path
     *
     * @return FunctionLikeStorage
     */
    public static function getStorage($function_id, $file_path)
    {
        if (isset(self::$stubbed_functions[$function_id])) {
            return self::$stubbed_functions[$function_id];
        }

        if (isset(self::$builtin_functions[$function_id])) {
            return self::$builtin_functions[$function_id];
        }

        $file_storage = FileChecker::$storage[$file_path];

        if (!isset($file_storage->functions[$function_id])) {
            throw new \UnexpectedValueException(
                'Not expecting ' . $function_id . ' to not have storage in ' . $file_path
            );
        }

        return $file_storage->functions[$function_id];
    }

    /**
     * @param  string $function_id
     * @param  string $file_path
     *
     * @return bool
     */
    public static function isVariadic($function_id, $file_path)
    {
        $file_storage = FileChecker::$storage[$file_path];

        return isset($file_storage->functions[$function_id]) && $file_storage->functions[$function_id]->variadic;
    }

    /**
     * @param  string $function_id
     *
     * @return false|null
     */
    protected static function extractReflectionInfo($function_id)
    {
        try {
            $reflection_function = new \ReflectionFunction($function_id);

            $storage = self::$builtin_functions[$function_id] = new FunctionLikeStorage();

            $reflection_params = $reflection_function->getParameters();

            /** @var \ReflectionParameter $param */
            foreach ($reflection_params as $param) {
                $param_obj = self::getReflectionParamData($param);
                $storage->params[] = $param_obj;
            }

            $storage->cased_name = $reflection_function->getName();

            $config = \Psalm\Config::getInstance();

            if (version_compare(PHP_VERSION, '7.0.0dev', '>=')
                && $reflection_return_type = $reflection_function->getReturnType()
            ) {
                $storage->return_type = Type::parseString((string)$reflection_return_type);
            }

            if ($reflection_function->isUserDefined()) {
                $docblock_info = null;
                $doc_comment = $reflection_function->getDocComment();

                if (!$doc_comment) {
                    return;
                }

                try {
                    $docblock_info = CommentChecker::extractFunctionDocblockInfo(
                        (string)$doc_comment,
                        0
                    );
                } catch (\Psalm\Exception\DocblockParseException $e) {
                    // do nothing
                }

                if (!$docblock_info) {
                    return;
                }

                if ($docblock_info->deprecated) {
                    $storage->deprecated = true;
                }

                if ($docblock_info->variadic) {
                    $storage->variadic = true;
                }

                if ($docblock_info->ignore_nullable_return && $storage->return_type) {
                    $storage->return_type->ignore_nullable_issues = true;
                }

                $storage->suppressed_issues = $docblock_info->suppress;

                if (!$config->use_docblock_types) {
                    return;
                }

                if ($docblock_info->return_type) {
                    if (!$storage->return_type) {
                        $storage->return_type = Type::parseString($docblock_info->return_type);
                        $storage->return_type->setFromDocblock();

                        if ($docblock_info->ignore_nullable_return) {
                            $storage->return_type->ignore_nullable_issues = true;
                        }
                    }
                }
            }
        } catch (\ReflectionException $e) {
            return false;
        }
    }

    /**
     * @param  string $function_id
     * @param  string $file_path
     *
     * @return string
     */
    public static function getCasedFunctionId($function_id, $file_path)
    {
        $file_storage = FileChecker::$storage[$file_path];

        if (!isset($file_storage->functions[$function_id])) {
            throw new \InvalidArgumentException('Do not know function ' . $function_id . ' in file ' . $file_path);
        }

        return $file_storage->functions[$function_id]->cased_name;
    }

    /**
     * @param  Type\Union               $return_type
     * @param  array<string, string>    $template_types
     *
     * @return Type\Union
     */
    public static function replaceTemplateTypes(Type\Union $return_type, array $template_types)
    {
        $ignore_nullable_issues = $return_type->ignore_nullable_issues;
        $type_tokens = Type::tokenize((string)$return_type);

        foreach ($type_tokens as &$type_token) {
            if (isset($template_types[$type_token])) {
                $type_token = $template_types[$type_token];
            }
        }

        $result_type = Type::parseString(implode('', $type_tokens));

        $result_type->ignore_nullable_issues = $ignore_nullable_issues;

        return $result_type;
    }

    /**
     * @param  string $function_id
     *
     * @return array|null
     * @psalm-return array<int, array<int, FunctionLikeParameter>>|null
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

        for ($i = 1; $i < 10; ++$i) {
            if (!isset($call_map[$call_map_key . '\'' . $i])) {
                break;
            }

            $call_map_functions[] = $call_map[$call_map_key . '\'' . $i];
        }

        $function_type_options = [];

        if (!self::$callmap_file_checker) {
            self::$callmap_file_checker = new FileChecker(
                'callmap.php',
                \Psalm\Checker\ProjectChecker::getInstance(),
                [],
                false
            );
        }

        foreach ($call_map_functions as $call_map_function_args) {
            array_shift($call_map_function_args);

            $function_types = [];

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
                    ? TypeChecker::simplifyUnionType(Type::parseString($arg_type), self::$callmap_file_checker)
                    : Type::getMixed();

                $function_types[] = new FunctionLikeParameter(
                    $arg_name,
                    $by_reference,
                    $param_type,
                    null,
                    $optional,
                    false,
                    $variadic
                );
            }

            $function_type_options[] = $function_types;
        }

        return $function_type_options;
    }

    /**
     * @param  string  $function_id
     *
     * @return Type\Union
     */
    public static function getReturnTypeFromCallMap($function_id)
    {
        $call_map_key = strtolower($function_id);

        $call_map = self::getCallMap();

        if (!isset($call_map[$call_map_key])) {
            throw new \InvalidArgumentException('Function ' . $function_id . ' was not found in callmap');
        }

        if (!$call_map[$call_map_key][0]) {
            return Type::getMixed();
        }

        return Type::parseString($call_map[$call_map_key][0]);
    }

    /**
     * @param  string                      $function_id
     * @param  array<PhpParser\Node\Arg>   $call_args
     * @param  CodeLocation                $code_location
     * @param  array                       $suppressed_issues
     *
     * @return Type\Union
     */
    public static function getReturnTypeFromCallMapWithArgs(
        $function_id,
        array $call_args,
        CodeLocation $code_location,
        array $suppressed_issues
    ) {
        $call_map_key = strtolower($function_id);

        $call_map = self::getCallMap();

        if (!isset($call_map[$call_map_key])) {
            throw new \InvalidArgumentException('Function ' . $function_id . ' was not found in callmap');
        }

        if ($call_args) {
            if (in_array($call_map_key, ['str_replace', 'preg_replace', 'preg_replace_callback'], true)) {
                if (isset($call_args[2]->value->inferredType)) {

                    /** @var Type\Union */
                    $subject_type = $call_args[2]->value->inferredType;

                    if (!$subject_type->hasString() && $subject_type->hasArray()) {
                        return Type::getArray();
                    }

                    return Type::getString();
                }
            }

            if (in_array($call_map_key, ['pathinfo'], true)) {
                if (isset($call_args[1])) {
                    return Type::getString();
                }

                return Type::getArray();
            }

            if (substr($call_map_key, 0, 6) === 'array_') {
                $array_return_type = self::getArrayReturnType(
                    $call_map_key,
                    $call_args,
                    $code_location,
                    $suppressed_issues
                );

                if ($array_return_type) {
                    return $array_return_type;
                }
            }

            if ($call_map_key === 'explode' || $call_map_key === 'preg_split') {
                return Type::parseString('array<int, string>');
            }

            if ($call_map_key === 'min' || $call_map_key === 'max') {
                if (isset($call_args[0])) {
                    $first_arg = $call_args[0]->value;

                    if (isset($first_arg->inferredType)) {
                        if ($first_arg->inferredType->hasArray()) {
                            $array_type = $first_arg->inferredType->types['array'];
                            if ($array_type instanceof Type\Atomic\ObjectLike) {
                                return $array_type->getGenericTypeParam();
                            }

                            if ($array_type instanceof Type\Atomic\TArray) {
                                return clone $array_type->type_params[1];
                            }
                        } elseif ($first_arg->inferredType->hasScalarType() &&
                            ($second_arg = $call_args[1]->value) &&
                            isset($second_arg->inferredType) &&
                            $second_arg->inferredType->hasScalarType()
                        ) {
                            return Type::combineUnionTypes($first_arg->inferredType, $second_arg->inferredType);
                        }
                    }
                }
            }
        }

        if (!$call_map[$call_map_key][0]) {
            return Type::getMixed();
        }

        return Type::parseString($call_map[$call_map_key][0]);
    }

    /**
     * @param  string                       $call_map_key
     * @param  array<PhpParser\Node\Arg>    $call_args
     * @param  CodeLocation                 $code_location
     * @param  array                        $suppressed_issues
     *
     * @return Type\Union|null
     */
    protected static function getArrayReturnType(
        $call_map_key,
        $call_args,
        CodeLocation $code_location,
        array $suppressed_issues
    ) {
        if ($call_map_key === 'array_map') {
            return self::getArrayMapReturnType($call_map_key, $call_args, $code_location, $suppressed_issues);
        }

        $first_arg = isset($call_args[0]->value) ? $call_args[0]->value : null;
        $second_arg = isset($call_args[1]->value) ? $call_args[1]->value : null;

        if ($call_map_key === 'array_merge') {
            $inner_value_types = [];
            $inner_key_types = [];

            foreach ($call_args as $offset => $call_arg) {
                if (!isset($call_arg->value->inferredType)) {
                    return Type::getArray();
                }

                foreach ($call_arg->value->inferredType->types as $type_part) {
                    if (!$type_part instanceof Type\Atomic\TArray) {
                        if ($type_part instanceof Type\Atomic\ObjectLike) {
                            $type_part = new Type\Atomic\TArray([
                                Type::getString(),
                                $type_part->getGenericTypeParam(),
                            ]);
                        } else {
                            return Type::getArray();
                        }
                    }

                    if ($type_part->type_params[1]->isEmpty()) {
                        continue;
                    }

                    $inner_key_types = array_merge(array_values($type_part->type_params[0]->types), $inner_key_types);
                    $inner_value_types = array_merge(
                        array_values($type_part->type_params[1]->types),
                        $inner_value_types
                    );
                }
            }

            if ($inner_value_types) {
                return new Type\Union([
                    new Type\Atomic\TArray([
                        Type::combineTypes($inner_key_types),
                        Type::combineTypes($inner_value_types),
                    ]),
                ]);
            }

            return Type::getArray();
        }

        if ($call_map_key === 'array_filter') {
            $first_arg_array = $first_arg
                && isset($first_arg->inferredType)
                && isset($first_arg->inferredType->types['array'])
                && ($first_arg->inferredType->types['array'] instanceof Type\Atomic\TArray ||
                    $first_arg->inferredType->types['array'] instanceof Type\Atomic\ObjectLike)
            ? $first_arg->inferredType->types['array']
            : null;

            if (!$first_arg_array) {
                return Type::getArray();
            }

            $second_arg = isset($call_args[1]->value) ? $call_args[1]->value : null;

            if ($first_arg_array instanceof Type\Atomic\TArray) {
                $inner_type = $first_arg_array->type_params[1];
                $key_type = clone $first_arg_array->type_params[0];
            } else {
                $inner_type = $first_arg_array->getGenericTypeParam();
                $key_type = Type::getString();
            }

            if (!$second_arg) {
                $inner_type->removeType('null');
                $inner_type->removeType('false');
            }

            return new Type\Union([
                new Type\Atomic\TArray([
                    $key_type,
                    $inner_type,
                ]),
            ]);
        }

        return null;
    }

    /**
     * @param  string                       $call_map_key
     * @param  array<PhpParser\Node\Arg>    $call_args
     * @param  CodeLocation                 $code_location
     * @param  array                        $suppressed_issues
     *
     * @return Type\Union
     */
    protected static function getArrayMapReturnType(
        $call_map_key,
        $call_args,
        CodeLocation $code_location,
        array $suppressed_issues
    ) {
        $function_index = $call_map_key === 'array_map' ? 0 : 1;
        $array_index = $call_map_key === 'array_map' ? 1 : 0;

        $array_arg = isset($call_args[$array_index]->value) ? $call_args[$array_index]->value : null;

        $array_arg_type = $array_arg
                && isset($array_arg->inferredType)
                && isset($array_arg->inferredType->types['array'])
                && $array_arg->inferredType->types['array'] instanceof Type\Atomic\TArray
            ? $array_arg->inferredType->types['array']
            : null;

        if (isset($call_args[$function_index])) {
            $function_call_arg = $call_args[$function_index];

            if ($function_call_arg->value instanceof PhpParser\Node\Expr\Closure &&
                isset($function_call_arg->value->inferredType) &&
                $function_call_arg->value->inferredType->types['Closure'] instanceof Type\Atomic\Fn
            ) {
                $closure_return_type = $function_call_arg->value->inferredType->types['Closure']->return_type;

                if ($closure_return_type->isVoid()) {
                    IssueBuffer::accepts(
                        new InvalidReturnType(
                            'No return type could be found in the closure passed to ' . $call_map_key,
                            $code_location
                        ),
                        $suppressed_issues
                    );

                    return Type::getArray();
                }

                $key_type = $array_arg_type ? clone $array_arg_type->type_params[0] : Type::getMixed();

                if ($call_map_key === 'array_map') {
                    $inner_type = clone $closure_return_type;

                    return new Type\Union([
                        new Type\Atomic\TArray([
                            $key_type,
                            $inner_type,
                        ]),
                    ]);
                }

                if ($array_arg_type) {
                    $inner_type = clone $array_arg_type->type_params[1];

                    return new Type\Union([
                        new Type\Atomic\TArray([
                            $key_type,
                            $inner_type,
                        ]),
                    ]);
                }
            } elseif ($function_call_arg->value instanceof PhpParser\Node\Scalar\String_) {
                $mapped_function_id = strtolower($function_call_arg->value->value);

                $call_map = self::getCallMap();

                if (isset($call_map[$mapped_function_id][0])) {
                    if ($call_map[$mapped_function_id][0]) {
                        $mapped_function_return = Type::parseString($call_map[$mapped_function_id][0]);

                        return new Type\Union([
                            new Type\Atomic\TArray([
                                Type::getInt(),
                                $mapped_function_return,
                            ]),
                        ]);
                    }
                }
                    // @todo handle array_map('some_custom_function', $arr)
            }
        }

        return Type::getArray();
    }

    /**
     * Gets the method/function call map
     *
     * @return array<string, array<string, string>>
     * @psalm-suppress MixedInferredReturnType as the use of require buggers things up
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MoreSpecificReturnType
     */
    protected static function getCallMap()
    {
        if (self::$call_map !== null) {
            return self::$call_map;
        }

        /** @var array<string, array<string, string>> */
        $call_map = require_once(__DIR__ . '/../CallMap.php');

        self::$call_map = [];

        foreach ($call_map as $key => $value) {
            $cased_key = strtolower($key);
            self::$call_map[$cased_key] = $value;
        }

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

    /**
     * @param  string                   $function_name
     * @param  StatementsSource         $source
     *
     * @return string
     */
    public static function getFQFunctionNameFromString($function_name, StatementsSource $source)
    {
        if (empty($function_name)) {
            throw new \InvalidArgumentException('$function_name cannot be empty');
        }

        if ($function_name[0] === '\\') {
            return substr($function_name, 1);
        }

        $function_name_lcase = strtolower($function_name);

        $aliases = $source->getAliases();

        $imported_function_namespaces = $aliases->functions;
        $imported_namespaces = $aliases->uses;

        if (strpos($function_name, '\\') !== false) {
            $function_name_parts = explode('\\', $function_name);
            $first_namespace = array_shift($function_name_parts);
            $first_namespace_lcase = strtolower($first_namespace);

            if (isset($imported_namespaces[$first_namespace_lcase])) {
                return $imported_namespaces[$first_namespace_lcase] . '\\' . implode('\\', $function_name_parts);
            }

            if (isset($imported_function_namespaces[$first_namespace_lcase])) {
                return $imported_function_namespaces[$first_namespace_lcase] . '\\' .
                    implode('\\', $function_name_parts);
            }
        } elseif (isset($imported_namespaces[$function_name_lcase])) {
            return $imported_namespaces[$function_name_lcase];
        } elseif (isset($imported_function_namespaces[$function_name_lcase])) {
            return $imported_function_namespaces[$function_name_lcase];
        }

        $namespace = $source->getNamespace();

        return ($namespace ? $namespace . '\\' : '') . $function_name;
    }

    /**
     * @return void
     */
    public static function clearCache()
    {
        self::$builtin_functions = [];
        self::$stubbed_functions = [];
    }
}
