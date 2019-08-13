<?php
namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\Internal\Codebase\CallMap;
use Psalm\Context;
use Psalm\Type;
use function strtolower;
use function array_values;
use function count;

/**
 * @internal
 */
class FunctionAnalyzer extends FunctionLikeAnalyzer
{
    public function __construct(PhpParser\Node\Stmt\Function_ $function, SourceAnalyzer $source)
    {
        $codebase = $source->getCodebase();

        $file_storage_provider = $codebase->file_storage_provider;

        $file_storage = $file_storage_provider->get($source->getFilePath());

        $namespace = $source->getNamespace();

        $function_id = ($namespace ? strtolower($namespace) . '\\' : '') . strtolower($function->name->name);

        if (!isset($file_storage->functions[$function_id])) {
            throw new \UnexpectedValueException(
                'Function ' . $function_id . ' should be defined in ' . $source->getFilePath()
            );
        }

        $storage = $file_storage->functions[$function_id];

        parent::__construct($function, $source, $storage);
    }

    /**
     * @param  string                      $function_id
     * @param  array<PhpParser\Node\Arg>   $call_args
     *
     * @return Type\Union
     */
    public static function getReturnTypeFromCallMapWithArgs(
        StatementsAnalyzer $statements_analyzer,
        $function_id,
        array $call_args,
        Context $context
    ) {
        $call_map_key = strtolower($function_id);

        $call_map = CallMap::getCallMap();

        $codebase = $statements_analyzer->getCodebase();

        if (!isset($call_map[$call_map_key])) {
            throw new \InvalidArgumentException('Function ' . $function_id . ' was not found in callmap');
        }

        if (!$call_args) {
            switch ($call_map_key) {
                case 'getenv':
                    return new Type\Union([new Type\Atomic\TArray([Type::getArrayKey(), Type::getString()])]);

                case 'gettimeofday':
                    return new Type\Union([
                        new Type\Atomic\TArray([
                            Type::getString(),
                            Type::getInt()
                        ])
                    ]);

                case 'microtime':
                    return Type::getString();

                case 'hrtime':
                    return new Type\Union([
                        new Type\Atomic\ObjectLike([
                            Type::getInt(),
                            Type::getInt()
                        ])
                    ]);

                case 'get_called_class':
                    return new Type\Union([new Type\Atomic\TClassString($context->self ?: 'object')]);

                case 'get_parent_class':
                    if ($context->self && $codebase->classExists($context->self)) {
                        $classlike_storage = $codebase->classlike_storage_provider->get($context->self);

                        if ($classlike_storage->parent_classes) {
                            return new Type\Union([
                                new Type\Atomic\TClassString(
                                    array_values($classlike_storage->parent_classes)[0]
                                )
                            ]);
                        }
                    }
            }
        } else {
            switch ($call_map_key) {
                case 'pathinfo':
                    if (isset($call_args[1])) {
                        return Type::getString();
                    }

                    return Type::getArray();

                case 'count':
                    if (isset($call_args[0]->value->inferredType)) {
                        $atomic_types = $call_args[0]->value->inferredType->getTypes();

                        if (count($atomic_types) === 1) {
                            if (isset($atomic_types['array'])) {
                                if ($atomic_types['array'] instanceof Type\Atomic\TNonEmptyArray) {
                                    return new Type\Union([
                                        $atomic_types['array']->count !== null
                                            ? new Type\Atomic\TLiteralInt($atomic_types['array']->count)
                                            : new Type\Atomic\TInt
                                    ]);
                                } elseif ($atomic_types['array'] instanceof Type\Atomic\ObjectLike
                                    && $atomic_types['array']->sealed
                                ) {
                                    return new Type\Union([
                                        new Type\Atomic\TLiteralInt(count($atomic_types['array']->properties))
                                    ]);
                                }
                            } elseif (isset($atomic_types['callable-array'])) {
                                return Type::getInt(false, 2);
                            }
                        }
                    }

                    break;

                case 'var_export':
                case 'highlight_string':
                case 'highlight_file':
                    if (isset($call_args[1]->value->inferredType)) {
                        $subject_type = $call_args[1]->value->inferredType;

                        if ((string) $subject_type === 'true') {
                            return Type::getString();
                        }

                        return new Type\Union([
                            new Type\Atomic\TString,
                            $call_map_key === 'var_export' ? new Type\Atomic\TNull : new Type\Atomic\TBool
                        ]);
                    }

                    return $call_map_key === 'var_export' ? Type::getVoid() : Type::getBool();

                case 'print_r':
                    if (isset($call_args[1]->value->inferredType)) {
                        $subject_type = $call_args[1]->value->inferredType;

                        if ((string) $subject_type === 'true') {
                            return Type::getString();
                        }
                    }

                    return new Type\Union([
                        new Type\Atomic\TString,
                        new Type\Atomic\TTrue
                    ]);

                case 'microtime':
                    if (isset($call_args[0]->value->inferredType)) {
                        $subject_type = $call_args[0]->value->inferredType;

                        if ((string) $subject_type === 'true') {
                            return Type::getFloat();
                        }

                        if ((string) $subject_type === 'false') {
                            return Type::getString();
                        }
                    }

                    return new Type\Union([
                        new Type\Atomic\TFloat,
                        new Type\Atomic\TString
                    ]);

                case 'hrtime':
                    if (isset($call_args[0]->value->inferredType)) {
                        $subject_type = $call_args[0]->value->inferredType;

                        if ((string) $subject_type === 'true') {
                            $int = Type::getInt();
                            $int->from_calculation = true;
                            return $int;
                        }

                        if ((string) $subject_type === 'false') {
                            return new Type\Union([
                                new Type\Atomic\ObjectLike([
                                    Type::getInt(),
                                    Type::getInt()
                                ])
                            ]);
                        }

                        return new Type\Union([
                            new Type\Atomic\ObjectLike([
                                Type::getInt(),
                                Type::getInt()
                            ]),
                            new Type\Atomic\TInt()
                        ]);
                    }

                    $int = Type::getInt();
                    $int->from_calculation = true;
                    return $int;

                case 'getenv':
                    return new Type\Union([new Type\Atomic\TString, new Type\Atomic\TFalse]);

                case 'gettimeofday':
                    if (isset($call_args[0]->value->inferredType)) {
                        $subject_type = $call_args[0]->value->inferredType;

                        if ((string) $subject_type === 'true') {
                            return Type::getFloat();
                        }

                        if ((string) $subject_type === 'false') {
                            return new Type\Union([
                                new Type\Atomic\TArray([
                                    Type::getString(),
                                    Type::getInt()
                                ])
                            ]);
                        }
                    }

                    break;

                case 'explode':
                    if (count($call_args) === 2) {
                        if ($call_args[0]->value instanceof PhpParser\Node\Scalar\String_) {
                            if ($call_args[0]->value->value === '') {
                                return Type::getFalse();
                            }

                            return new Type\Union([
                                new Type\Atomic\TNonEmptyArray([
                                    Type::getInt(),
                                    Type::getString()
                                ])
                            ]);
                        } elseif (isset($call_args[0]->value->inferredType)
                            && $call_args[0]->value->inferredType->hasString()
                        ) {
                            $falsable_array = new Type\Union([
                                new Type\Atomic\TNonEmptyArray([
                                    Type::getInt(),
                                    Type::getString()
                                ]),
                                new Type\Atomic\TFalse
                            ]);

                            if ($codebase->config->ignore_internal_falsable_issues) {
                                $falsable_array->ignore_falsable_issues = true;
                            }

                            return $falsable_array;
                        }
                    }

                    break;

                case 'abs':
                    if (isset($call_args[0]->value)) {
                        $first_arg = $call_args[0]->value;

                        if (isset($first_arg->inferredType)) {
                            $numeric_types = [];

                            foreach ($first_arg->inferredType->getTypes() as $inner_type) {
                                if ($inner_type->isNumericType()) {
                                    $numeric_types[] = $inner_type;
                                }
                            }

                            if ($numeric_types) {
                                return new Type\Union($numeric_types);
                            }
                        }
                    }

                    break;

                case 'min':
                case 'max':
                    if (isset($call_args[0])) {
                        $first_arg = $call_args[0]->value;

                        if (isset($first_arg->inferredType)) {
                            if ($first_arg->inferredType->hasArray()) {
                                $array_type = $first_arg->inferredType->getTypes()['array'];
                                if ($array_type instanceof Type\Atomic\ObjectLike) {
                                    return $array_type->getGenericValueType();
                                }

                                if ($array_type instanceof Type\Atomic\TArray) {
                                    return clone $array_type->type_params[1];
                                }
                            } elseif ($first_arg->inferredType->hasScalarType() &&
                                isset($call_args[1]) &&
                                ($second_arg = $call_args[1]->value) &&
                                isset($second_arg->inferredType) &&
                                $second_arg->inferredType->hasScalarType()
                            ) {
                                return Type::combineUnionTypes($first_arg->inferredType, $second_arg->inferredType);
                            }
                        }
                    }

                    break;

                case 'round':
                    if (isset($call_args[1])) {
                        $second_arg = $call_args[1]->value;

                        if (isset($second_arg->inferredType)
                            && $second_arg->inferredType->isSingleIntLiteral()
                        ) {
                            switch ($second_arg->inferredType->getSingleIntLiteral()->value) {
                                case 0:
                                    return Type::getInt(true);
                                default:
                                    return Type::getFloat();
                            }
                        }

                        return new Type\Union([new Type\Atomic\TInt, new Type\Atomic\TFloat]);
                    }

                    return Type::getInt(true);

                case 'get_parent_class':
                    // this is unreliable, as it's hard to know exactly what's wanted - attempted this in
                    // https://github.com/vimeo/psalm/commit/355ed831e1c69c96bbf9bf2654ef64786cbe9fd7
                    // but caused problems where it didn’t know exactly what level of child we
                    // were receiving.
                    //
                    // Really this should only work on instances we've created with new Foo(),
                    // but that requires more work
                    break;
            }
        }

        if (!$call_map[$call_map_key][0]) {
            return Type::getMixed();
        }

        $call_map_return_type = Type::parseString($call_map[$call_map_key][0]);

        switch ($call_map_key) {
            case 'mb_strpos':
            case 'mb_strrpos':
            case 'mb_stripos':
            case 'mb_strripos':
            case 'strpos':
            case 'strrpos':
            case 'stripos':
            case 'strripos':
            case 'strstr':
            case 'stristr':
            case 'strrchr':
            case 'strpbrk':
            case 'array_search':
                break;

            default:
                if ($call_map_return_type->isFalsable()
                    && $codebase->config->ignore_internal_falsable_issues
                ) {
                    $call_map_return_type->ignore_falsable_issues = true;
                }
        }

        return $call_map_return_type;
    }

    /**
     * @param  array<PhpParser\Node\Arg>   $call_args
     */
    public static function taintBuiltinFunctionReturn(
        StatementsAnalyzer $statements_analyzer,
        string $function_id,
        array $call_args,
        Type\Union $return_type
    ) : void {
        $codebase = $statements_analyzer->getCodebase();

        if (!$codebase->taint) {
            return;
        }

        switch ($function_id) {
            case 'htmlspecialchars':
                if (isset($call_args[0]->value->inferredType)
                    && $call_args[0]->value->inferredType->tainted
                ) {
                    // input is now safe from tainted sql and html
                    $return_type->tainted = $call_args[0]->value->inferredType->tainted
                        & ~(Type\Union::TAINTED_INPUT_SQL | Type\Union::TAINTED_INPUT_HTML);
                    $return_type->sources = $call_args[0]->value->inferredType->sources;
                }
                break;

            case 'strtolower':
            case 'strtoupper':
            case 'print_r':
            case 'substr':
                if (isset($call_args[0]->value->inferredType)
                    && $call_args[0]->value->inferredType->tainted
                ) {
                    $return_type->tainted = $call_args[0]->value->inferredType->tainted;
                    $return_type->sources = $call_args[0]->value->inferredType->sources;
                }

                break;

            case 'str_replace':
            case 'preg_replace':
                $first_arg_taint = $call_args[0]->value->inferredType->tainted ?? 0;
                $third_arg_taint = $call_args[2]->value->inferredType->tainted ?? 0;
                if ($first_arg_taint || $third_arg_taint) {
                    $return_type->tainted = $first_arg_taint | $third_arg_taint;
                    $return_type->sources = $call_args[0]->value->inferredType->sources;
                }

                break;

            case 'htmlentities':
            case 'striptags':
                if (isset($call_args[0]->value->inferredType)
                    && $call_args[0]->value->inferredType->tainted
                ) {
                    // input is now safe from tainted html
                    $return_type->tainted = $call_args[0]->value->inferredType->tainted
                        & ~Type\Union::TAINTED_INPUT_HTML;
                    $return_type->sources = $call_args[0]->value->inferredType->sources;
                }
                break;
        }
    }
}
