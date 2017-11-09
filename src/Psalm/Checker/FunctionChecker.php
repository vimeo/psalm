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
     *
     * @return bool
     */
    public static function functionExists(StatementsChecker $statements_checker, $function_id)
    {
        $project_checker = $statements_checker->getFileChecker()->project_checker;

        $file_storage = $project_checker->file_storage_provider->get($statements_checker->getFilePath());

        if (isset($file_storage->declaring_function_ids[$function_id])) {
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

        if (isset($statements_checker->getFunctionCheckers()[$function_id])) {
            return true;
        }

        if (self::extractReflectionInfo($function_id) === false) {
            return false;
        }

        return true;
    }

    /**
     * @param  string $function_id
     *
     * @return FunctionLikeStorage
     */
    public static function getStorage(StatementsChecker $statements_checker, $function_id)
    {
        if (isset(self::$stubbed_functions[$function_id])) {
            return self::$stubbed_functions[$function_id];
        }

        if (isset(self::$builtin_functions[$function_id])) {
            return self::$builtin_functions[$function_id];
        }

        $project_checker = $statements_checker->getFileChecker()->project_checker;
        $file_path = $statements_checker->getFilePath();

        $file_storage = $project_checker->file_storage_provider->get($file_path);

        $function_checkers = $statements_checker->getFunctionCheckers();

        if (isset($function_checkers[$function_id])) {
            $function_id = $function_checkers[$function_id]->getMethodId();

            if (!isset($file_storage->functions[$function_id])) {
                throw new \UnexpectedValueException(
                    'Expecting ' . $function_id . ' to have storage in ' . $file_path
                );
            }

            return $file_storage->functions[$function_id];
        }

        // closures can be returned here
        if (isset($file_storage->functions[$function_id])) {
            return $file_storage->functions[$function_id];
        }

        if (!isset($file_storage->declaring_function_ids[$function_id])) {
            throw new \UnexpectedValueException(
                'Expecting ' . $function_id . ' to have storage in ' . $file_path
            );
        }

        $declaring_file_path = $file_storage->declaring_function_ids[$function_id];

        $declaring_file_storage = $project_checker->file_storage_provider->get($declaring_file_path);

        if (!isset($declaring_file_storage->functions[$function_id])) {
            throw new \UnexpectedValueException(
                'Not expecting ' . $function_id . ' to not have storage in ' . $declaring_file_path
            );
        }

        return $declaring_file_storage->functions[$function_id];
    }

    /**
     * @param  string $function_id
     * @param  string $file_path
     *
     * @return bool
     */
    public static function isVariadic(ProjectChecker $project_checker, $function_id, $file_path)
    {
        $file_storage = $project_checker->file_storage_provider->get($file_path);

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
                    ? Type::parseString($arg_type)
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
        StatementsChecker $statements_checker,
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
                    $statements_checker,
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
        StatementsChecker $statements_checker,
        $call_map_key,
        $call_args,
        CodeLocation $code_location,
        array $suppressed_issues
    ) {
        if ($call_map_key === 'array_map') {
            return self::getArrayMapReturnType(
                $statements_checker,
                $call_map_key,
                $call_args,
                $code_location,
                $suppressed_issues
            );
        }

        $first_arg = isset($call_args[0]->value) ? $call_args[0]->value : null;
        $second_arg = isset($call_args[1]->value) ? $call_args[1]->value : null;

        if ($call_map_key === 'array_merge') {
            $inner_value_types = [];
            $inner_key_types = [];

            foreach ($call_args as $call_arg) {
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

        if ($call_map_key === 'array_rand') {
            $first_arg_array = $first_arg
                && isset($first_arg->inferredType)
                && isset($first_arg->inferredType->types['array'])
                && ($first_arg->inferredType->types['array'] instanceof Type\Atomic\TArray ||
                    $first_arg->inferredType->types['array'] instanceof Type\Atomic\ObjectLike)
            ? $first_arg->inferredType->types['array']
            : null;

            if (!$first_arg_array) {
                return Type::getMixed();
            }

            $second_arg = isset($call_args[1]->value) ? $call_args[1]->value : null;

            if ($first_arg_array instanceof Type\Atomic\TArray) {
                $key_type = clone $first_arg_array->type_params[0];
            } else {
                $key_type = Type::getString();
            }

            if (!$second_arg
                || ($second_arg instanceof PhpParser\Node\Scalar\LNumber && $second_arg->value === 1)
            ) {
                return $key_type;
            }

            $arr_type = new Type\Union([
                new Type\Atomic\TArray([
                    Type::getInt(),
                    $key_type,
                ]),
            ]);

            if ($second_arg instanceof PhpParser\Node\Scalar\LNumber) {
                return $arr_type;
            }

            return Type::combineUnionTypes($key_type, $arr_type);
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
        StatementsChecker $statements_checker,
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
            } elseif ($function_call_arg->value instanceof PhpParser\Node\Scalar\String_
                || $function_call_arg->value instanceof PhpParser\Node\Expr\Array_
            ) {
                $mapping_function_ids = Statements\Expression\CallChecker::getFunctionIdsFromCallableArg(
                    $statements_checker,
                    $function_call_arg->value
                );

                $call_map = self::getCallMap();

                $mapping_return_type = null;

                $project_checker = $statements_checker->getFileChecker()->project_checker;

                foreach ($mapping_function_ids as $mapping_function_id) {
                    if (isset($call_map[$mapping_function_id][0])) {
                        if ($call_map[$mapping_function_id][0]) {
                            $mapped_function_return = Type::parseString($call_map[$mapping_function_id][0]);

                            if ($mapping_return_type) {
                                $mapping_return_type = Type::combineUnionTypes(
                                    $mapping_return_type,
                                    $mapped_function_return
                                );
                            } else {
                                $mapping_return_type = $mapped_function_return;
                            }
                        }
                    } else {
                        if (strpos($mapping_function_id, '::') !== false) {
                            list($callable_fq_class_name) = explode('::', $mapping_function_id);

                            if (in_array($callable_fq_class_name, ['self', 'static', 'parent'], true)) {
                                $mapping_return_type = Type::getMixed();
                                continue;
                            }

                            if (!MethodChecker::methodExists($project_checker, $mapping_function_id)) {
                                $mapping_return_type = Type::getMixed();
                                continue;
                            }

                            $return_type = MethodChecker::getMethodReturnType(
                                $project_checker,
                                $mapping_function_id
                            ) ?: Type::getMixed();

                            if ($mapping_return_type) {
                                $mapping_return_type = Type::combineUnionTypes(
                                    $mapping_return_type,
                                    $return_type
                                );
                            } else {
                                $mapping_return_type = $return_type;
                            }
                        } else {
                            if (!FunctionChecker::functionExists($statements_checker, $mapping_function_id)) {
                                $mapping_return_type = Type::getMixed();
                                continue;
                            }

                            $function_storage = FunctionChecker::getStorage(
                                $statements_checker,
                                $mapping_function_id
                            );

                            $return_type = $function_storage->return_type ?: Type::getMixed();

                            if ($mapping_return_type) {
                                $mapping_return_type = Type::combineUnionTypes(
                                    $mapping_return_type,
                                    $return_type
                                );
                            } else {
                                $mapping_return_type = $return_type;
                            }
                        }
                    }
                }

                if ($mapping_return_type) {
                    return new Type\Union([
                        new Type\Atomic\TArray([
                            Type::getInt(),
                            $mapping_return_type,
                        ]),
                    ]);
                }
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
