<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type;

class FilterVarReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return ['filter_var'];
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     */
    public static function getFunctionReturnType(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) : Type\Union {
        if (isset($call_args[1]->value->inferredType)
            && $call_args[1]->value->inferredType->isSingleIntLiteral()
        ) {
            $filter_type_type = $call_args[1]->value->inferredType->getSingleIntLiteral();

            $filter_type = null;

            switch ($filter_type_type->value) {
                case \FILTER_VALIDATE_INT:
                    $filter_type = Type::getInt();
                    break;

                case \FILTER_VALIDATE_FLOAT:
                    $filter_type = Type::getFloat();
                    break;

                case \FILTER_VALIDATE_BOOLEAN:
                    $filter_type = Type::getBool();

                    break;

                case \FILTER_VALIDATE_IP:
                case \FILTER_VALIDATE_MAC:
                case \FILTER_VALIDATE_REGEXP:
                case \FILTER_VALIDATE_URL:
                case \FILTER_VALIDATE_EMAIL:
                case \FILTER_VALIDATE_DOMAIN:
                    $filter_type = Type::getString();
                    break;
            }

            $has_object_like = false;

            if ($filter_type && isset($call_args[2]->value->inferredType)) {
                foreach ($call_args[2]->value->inferredType->getTypes() as $atomic_type) {
                    if ($atomic_type instanceof Type\Atomic\ObjectLike) {
                        $has_object_like = true;

                        if (isset($atomic_type->properties['options'])
                            && $atomic_type->properties['options']->hasArray()
                            && ($options_array = $atomic_type->properties['options']->getTypes()['array'])
                            && $options_array instanceof Type\Atomic\ObjectLike
                            && isset($options_array->properties['default'])
                        ) {
                            $filter_type = Type::combineUnionTypes(
                                $filter_type,
                                $options_array->properties['default']
                            );
                        } else {
                            $filter_type->addType(new Type\Atomic\TFalse);
                        }

                        if (isset($atomic_type->properties['flags'])
                            && $atomic_type->properties['flags']->isSingleIntLiteral()
                        ) {
                            $filter_flag_type = $atomic_type->properties['flags']->getSingleIntLiteral();

                            if (
                                $filter_flag_type->value === \FILTER_NULL_ON_FAILURE
                                &&
                                $filter_type->hasBool()
                            ) {
                                $filter_type->addType(new Type\Atomic\TNull);
                            }
                        }
                    } elseif ($atomic_type instanceof Type\Atomic\TLiteralInt) {
                        if ($atomic_type->value === \FILTER_NULL_ON_FAILURE && $filter_type->hasBool()) {
                            $filter_type->addType(new Type\Atomic\TNull);
                        }
                    }
                }
            }

            if (!$has_object_like && $filter_type) {
                $filter_type->addType(new Type\Atomic\TFalse);
            }

            return $filter_type ?: Type::getMixed();
        }

        return Type::getMixed();
    }
}
