<?php

declare(strict_types=1);

namespace Psalm\Tests\Config\Plugin\Hook;

use Psalm\Codebase;
use Psalm\Plugin\DynamicFunctionStorage;
use Psalm\Plugin\DynamicTemplateProvider;
use Psalm\Plugin\EventHandler\DynamicFunctionStorageProviderInterface;
use Psalm\Plugin\EventHandler\Event\DynamicFunctionStorageProviderEvent;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function array_keys;
use function array_map;
use function count;

class CustomArrayMapFunctionStorageProvider implements DynamicFunctionStorageProviderInterface
{
    public static function getFunctionIds(): array
    {
        return ['custom_array_map'];
    }

    public static function getFunctionStorage(DynamicFunctionStorageProviderEvent $event): ?DynamicFunctionStorage
    {
        $template_provider = $event->getTemplateProvider();
        $arg_type_inferer = $event->getArgTypeInferer();
        $call_args = $event->getArgs();

        if (count($call_args) < 2) {
            return null;
        }
        $args_count = count($call_args);
        $expected_callable_args_count = $args_count - 1;

        $last_arg = $call_args[$args_count - 1];

        if (!($input_array_type = $arg_type_inferer->infer($last_arg)) ||
            !($input_value_type = self::toValueType($event->getCodebase(), $input_array_type))
        ) {
            return null;
        }

        $all_expected_callables = [
            self::createExpectedCallable($input_value_type, $template_provider),
            ...self::createRestCallables($template_provider, $expected_callable_args_count),
        ];

        $custom_array_map_storage = new DynamicFunctionStorage();
        $custom_array_map_storage->templates = self::createTemplates($template_provider, $expected_callable_args_count);
        $custom_array_map_storage->return_type = self::createReturnType($all_expected_callables);
        $custom_array_map_storage->params = [
            ...array_map(
                static function (TCallable $expected, int $offset) {
                    $t = new Union([$expected]);
                    $param = new FunctionLikeParameter('fn' . $offset, false, $t, $t);
                    $param->is_optional = false;
                    return $param;
                },
                $all_expected_callables,
                array_keys($all_expected_callables),
            ),
            self::createLastArrayMapParam($input_array_type),
        ];

        return $custom_array_map_storage;
    }

    private static function createLastArrayMapParam(Union $input_array_type): FunctionLikeParameter
    {
        return new FunctionLikeParameter(
            'input',
            false,
            $input_array_type,
            $input_array_type,
            null,
            null,
            false,
        );
    }

    /**
     * Resolves value type from array-like type:
     *     list<int> -> int
     *     list<int|string> -> int|string
     */
    private static function toValueType(Codebase $codebase, Union $array_like_type): ?Union
    {
        $value_types = [];

        foreach ($array_like_type->getAtomicTypes() as $atomic) {
            if ($atomic instanceof Type\Atomic\TArray) {
                $value_types[] = $atomic->type_params[1];
            } elseif ($atomic instanceof Type\Atomic\TKeyedArray) {
                $value_types[] = $atomic->getGenericValueType();
            } else {
                return null;
            }
        }

        return Type::combineUnionTypeArray($value_types, $codebase);
    }

    private static function createExpectedCallable(
        Union $input_type,
        DynamicTemplateProvider $template_provider,
        int $return_template_offset = 0,
    ): TCallable {
        return new TCallable(
            'callable',
            [new FunctionLikeParameter('a', false, $input_type, $input_type)],
            new Union([
                $template_provider->createTemplate('T' . $return_template_offset),
            ]),
        );
    }

    /**
     * @return list<TCallable>
     */
    private static function createRestCallables(
        DynamicTemplateProvider $template_provider,
        int $expected_callable_args_count,
    ): array {
        $rest_callable_params = [];

        for ($template_offset = 0; $template_offset < $expected_callable_args_count - 1; $template_offset++) {
            $rest_callable_params[] = self::createExpectedCallable(
                new Union([
                    $template_provider->createTemplate('T' . $template_offset),
                ]),
                $template_provider,
                $template_offset + 1,
            );
        }

        return $rest_callable_params;
    }

    /**
     * Extracts return type for custom_array_map from last callable arg.
     *
     * @param non-empty-list<TCallable> $all_expected_callables
     */
    private static function createReturnType(array $all_expected_callables): Union
    {
        $last_callable_arg = $all_expected_callables[count($all_expected_callables) - 1];

        return Type::getList($last_callable_arg->return_type ?? Type::getMixed());
    }

    /**
     * Creates variadic template list for custom_array_map function.
     *
     * @return list<TTemplateParam>
     */
    private static function createTemplates(
        DynamicTemplateProvider $template_provider,
        int $expected_callable_count,
    ): array {
        $template_params = [];

        for ($i = 0; $i < $expected_callable_count; $i++) {
            $template_params[] = $template_provider->createTemplate('T' . $i);
        }

        return $template_params;
    }
}
