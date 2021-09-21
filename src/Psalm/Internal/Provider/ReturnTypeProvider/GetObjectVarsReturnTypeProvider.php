<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\ClassAnalyzer;
use Psalm\Internal\Analyzer\SourceAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use stdClass;

use function reset;
use function strtolower;

class GetObjectVarsReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds() : array
    {
        return [
            'get_object_vars',
        ];
    }

    public static function getGetObjectVarsReturnType(
        Type\Union $first_arg_type,
        SourceAnalyzer $statements_source,
        Context $context,
        CodeLocation $location
    ): Type\Union {
        if ($first_arg_type->isSingle()) {
            $atomics = $first_arg_type->getAtomicTypes();
            $object_type = reset($atomics);

            if ($object_type instanceof Type\Atomic\TObjectWithProperties) {
                if ([] === $object_type->properties) {
                    return Type::getEmptyArray();
                }
                return new Type\Union([
                    new Type\Atomic\TKeyedArray($object_type->properties)
                ]);
            } elseif ($object_type instanceof Type\Atomic\TNamedObject) {
                if (strtolower($object_type->value) === strtolower(stdClass::class)) {
                    return Type::parseString('array<string, mixed>');
                }
                $codebase = $statements_source->getCodebase();
                $class_storage = $codebase->classlikes->getStorageFor($object_type->value);

                if (null === $class_storage) {
                    return Type::parseString('array<string, mixed>');
                }

                if ([] === $class_storage->appearing_property_ids) {
                    return Type::getEmptyArray();
                }

                $properties = [];
                foreach ($class_storage->appearing_property_ids as $name => $property_id) {
                    if (ClassAnalyzer::checkPropertyVisibility(
                        $property_id,
                        $context,
                        $statements_source,
                        $location,
                        $statements_source->getSuppressedIssues(),
                        false
                    ) === true) {
                        $property_type = $codebase->properties->getPropertyType(
                            $property_id,
                            false,
                            $statements_source,
                            $context
                        );
                        $properties[$name] = $property_type ?: Type::getMixed();
                    }
                }

                if ([] === $properties) {
                    return Type::getEmptyArray();
                }

                return new Type\Union([
                    new Type\Atomic\TKeyedArray($properties)
                ]);
            }
        }
        return Type::parseString('array<string, mixed>');
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Type\Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer
            || !$call_args
        ) {
            return Type::getMixed();
        }

        if (($first_arg_type = $statements_source->node_data->getType($call_args[0]->value))
             && $first_arg_type->isObjectType()
        ) {
            return self::getGetObjectVarsReturnType(
                $first_arg_type,
                $statements_source,
                $event->getContext(),
                $event->getCodeLocation()
            );
        }

        return null;
    }
}
