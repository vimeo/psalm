<?php
namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\Internal\Codebase\CallMap;
use Psalm\Context;
use Psalm\CodeLocation;
use Psalm\Type;

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
     * @param  CodeLocation                $code_location
     * @param  array                       $suppressed_issues
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

                case 'get_called_class':
                    return new Type\Union([new Type\Atomic\TClassString($context->self ?: 'object')]);

                case 'get_parent_class':
                    $codebase = $statements_analyzer->getCodebase();

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

                        if (count($atomic_types) === 1 && isset($atomic_types['array'])) {
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

                        $codebase = $statements_analyzer->getCodebase();

                        if ($codebase->config->ignore_internal_falsable_issues) {
                            $falsable_array->ignore_falsable_issues = true;
                        }

                        return $falsable_array;
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
                $codebase = $statements_analyzer->getCodebase();

                if ($call_map_return_type->isFalsable()
                    && $codebase->config->ignore_internal_falsable_issues
                ) {
                    $call_map_return_type->ignore_falsable_issues = true;
                }
        }

        return $call_map_return_type;
    }
}
