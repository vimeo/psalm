<?php

namespace Psalm\Tests\Config\Plugin\Hook;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Plugin\EventHandler\Event\FunctionDynamicStorageProviderEvent;
use Psalm\Plugin\EventHandler\FunctionDynamicStorageProviderInterface;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\FunctionStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function array_keys;
use function array_map;
use function count;

class ArrayMapStorageProvider implements FunctionDynamicStorageProviderInterface
{
    public static function getFunctionIds(): array
    {
        return ['custom_array_map'];
    }

    public static function getFunctionStorage(FunctionDynamicStorageProviderEvent $event): ?FunctionStorage
    {
        $func_call = $event->getExpr();

        if ($func_call->isFirstClassCallable()) {
            return null;
        }

        $context = $event->getContext();
        $statements_analyzer = $event->getStatementsAnalyzer();
        $call_args = $func_call->getArgs();
        $args_count = count($call_args);
        $expected_callable_args_count = $args_count - 1;

        if ($expected_callable_args_count < 1) {
            return null;
        }

        $last_array_arg = $call_args[$args_count - 1];

        if (ExpressionAnalyzer::analyze($statements_analyzer, $last_array_arg->value, $context) === false) {
            return null;
        }

        $input_array_type = $statements_analyzer->node_data->getType($last_array_arg->value);

        if (!$input_array_type) {
            return null;
        }

        $input_value_type = self::getInputValueType($statements_analyzer->getCodebase(), $input_array_type);

        $all_expected_callables = [
            self::createExpectedCallable($input_value_type),
            ...self::createRestCallables($expected_callable_args_count),
        ];

        $custom_array_map_storage = new FunctionStorage();
        $custom_array_map_storage->cased_name = 'custom_array_map';
        $custom_array_map_storage->template_types = self::createTemplates($expected_callable_args_count);
        $custom_array_map_storage->return_type = self::getReturnType($all_expected_callables);

        $input_array_param = new FunctionLikeParameter('input', false, $input_array_type);
        $input_array_param->is_optional = false;

        $custom_array_map_storage->setParams(
            [
                ...array_map(
                    function (TCallable $expected, int $offset) {
                        $param = new FunctionLikeParameter('fn' . $offset, false, new Union([$expected]));
                        $param->is_optional = false;

                        return $param;
                    },
                    $all_expected_callables,
                    array_keys($all_expected_callables)
                ),
                $input_array_param
            ]
        );

        return $custom_array_map_storage;
    }

    /**
     * Resolve value type from array-like type:
     *     list<int> -> int
     *     list<int|string> -> int|string
     */
    private static function getInputValueType(Codebase $codebase, Union $array_like_type): Union
    {
        $input_template = self::createTemplate('TIn');

        // Template type that will be inferred via TemplateInferredTypeReplacer
        $value_type = new Union([$input_template]);

        $templated_array = new Union([
            new Type\Atomic\TArray([Type::getArrayKey(), $value_type])
        ]);

        $template_result = new TemplateResult(
            [
                $input_template->param_name => [
                    $input_template->defining_class => new Union([$input_template])
                ],
            ],
            []
        );

        TemplateStandinTypeReplacer::replace(
            $templated_array,
            $template_result,
            $codebase,
            null,
            $array_like_type
        );

        TemplateInferredTypeReplacer::replace($templated_array, $template_result, $codebase);

        return $value_type;
    }

    private static function createExpectedCallable(Union $input_type, int $return_template_offset = 0): TCallable
    {
        $first_expected_callable = new TCallable('callable');
        $first_expected_callable->params = [new FunctionLikeParameter('a', false, $input_type)];
        $first_expected_callable->return_type = self::createTemplateType($return_template_offset);

        return $first_expected_callable;
    }

    /**
     * @return list<TCallable>
     */
    private static function createRestCallables(int $expected_callable_args_count): array
    {
        $rest_callable_params = [];

        for ($template_offset = 0; $template_offset < $expected_callable_args_count - 1; $template_offset++) {
            $next_template_type = self::createTemplateType($template_offset);
            $rest_callable_params[] = self::createExpectedCallable($next_template_type, $template_offset + 1);
        }

        return $rest_callable_params;
    }

    /**
     * @param list<TCallable> $all_expected_callables
     */
    private static function getReturnType(array $all_expected_callables): Union
    {
        $last_callable_arg = $all_expected_callables[count($all_expected_callables) - 1];

        return  new Union([
            new Type\Atomic\TList($last_callable_arg->return_type ?? Type::getMixed())
        ]);
    }

    /**
     * @param positive-int $expected_callable_count
     * @return array<string, non-empty-array<string, Union>>
     */
    private static function createTemplates(int $expected_callable_count): array
    {
        $template_params = [];

        for ($i = 0; $i < $expected_callable_count; $i++) {
            $template = self::createTemplate('T', $i);

            $template_params[$template->param_name] = [
                $template->defining_class => $template->as
            ];
        }

        return $template_params;
    }

    private static function createTemplateType(int $offset = 0): Union
    {
        return new Union([self::createTemplate('T', $offset)]);
    }

    private static function createTemplate(string $prefix, int $offset = 0): TTemplateParam
    {
        return new TTemplateParam($prefix . $offset, Type::getMixed(), 'custom_array_map');
    }
}
