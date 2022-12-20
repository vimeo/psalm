<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\ClassAnalyzer;
use Psalm\Internal\Analyzer\SourceAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\AtomicPropertyFetchAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Union;
use stdClass;

use function reset;
use function strtolower;

/**
 * @internal
 */
class GetObjectVarsReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return [
            'get_object_vars',
        ];
    }

    private static ?TArray $fallback = null;

    /**
     * @return TArray|TKeyedArray
     */
    public static function getGetObjectVarsReturnType(
        Union $first_arg_type,
        SourceAnalyzer $statements_source,
        Context $context,
        CodeLocation $location
    ): Atomic {
        self::$fallback ??= new TArray([Type::getString(), Type::getMixed()]);

        if ($first_arg_type->isSingle()) {
            $atomics = $first_arg_type->getAtomicTypes();
            $object_type = reset($atomics);

            if ($object_type instanceof TObjectWithProperties) {
                if ([] === $object_type->properties) {
                    return self::$fallback;
                }
                return new TKeyedArray($object_type->properties);
            }

            if ($object_type instanceof TNamedObject) {
                if (strtolower($object_type->value) === strtolower(stdClass::class)) {
                    return self::$fallback;
                }
                $codebase = $statements_source->getCodebase();
                $class_storage = $codebase->classlikes->getStorageFor($object_type->value);

                if (null === $class_storage) {
                    return self::$fallback;
                }

                if ([] === $class_storage->appearing_property_ids) {
                    if ($class_storage->final) {
                        return Type::getEmptyArrayAtomic();
                    }

                    return self::$fallback;
                }

                $properties = [];
                foreach ($class_storage->appearing_property_ids as $name => $property_id) {
                    if (ClassAnalyzer::checkPropertyVisibility(
                        $property_id,
                        $context,
                        $statements_source,
                        $location,
                        $statements_source->getSuppressedIssues(),
                        false,
                    ) === true) {
                        $property_type = $codebase->properties->getPropertyType(
                            $property_id,
                            false,
                            $statements_source,
                            $context,
                        );
                        if (!$property_type) {
                            continue;
                        }

                        $property_type = $object_type instanceof TGenericObject
                            ? AtomicPropertyFetchAnalyzer::localizePropertyType(
                                $codebase,
                                $property_type,
                                $object_type,
                                $class_storage,
                                $class_storage,
                            )
                            : $property_type
                        ;
                        $properties[$name] = $property_type;
                    }
                }

                if ([] === $properties) {
                    if ($class_storage->final) {
                        return Type::getEmptyArrayAtomic();
                    }

                    return self::$fallback;
                }

                return new TKeyedArray(
                    $properties,
                    null,
                    $class_storage->final ? null : [Type::getString(), Type::getMixed()],
                );
            }
        }
        return self::$fallback;
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof StatementsAnalyzer
            || !$call_args
        ) {
            return Type::getMixed();
        }

        if (($first_arg_type = $statements_source->node_data->getType($call_args[0]->value))
             && $first_arg_type->isObjectType()
        ) {
            return new Union([self::getGetObjectVarsReturnType(
                $first_arg_type,
                $statements_source,
                $event->getContext(),
                $event->getCodeLocation(),
            )]);
        }

        return null;
    }
}
