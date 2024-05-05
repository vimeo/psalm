<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Issue\InvalidArgument;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Union;

use function array_combine;
use function count;

/**
 * @internal
 */
final class ArrayCombineReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['array_combine'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof StatementsAnalyzer
            || count($call_args) < 2
        ) {
            return Type::getNever();
        }

        if (!$keys_type = $statements_source->node_data->getType($call_args[0]->value)) {
            return null;
        }
        if (!$keys_type->isArray()) {
            return null;
        }

        if (!$values_type = $statements_source->node_data->getType($call_args[1]->value)) {
            return null;
        }
        if (!$values_type->isArray()) {
            return null;
        }

        $has_fallback = false;
        $result = [];
        foreach ($values_type->getArrays() as $values) {
            if ($values instanceof TArray && $values->isEmptyArray()) {
                $values = [];
            } elseif (!$values instanceof TKeyedArray || $values->fallback_params) {
                $has_fallback = true;
                continue;
            } else {
                $values = $values->properties;
                foreach ($values as $value) {
                    if ($value->possibly_undefined) {
                        $has_fallback = true;
                        continue;
                    }
                }
            }

            foreach ($keys_type->getArrays() as $keys) {
                $keys_array = [];

                if ($keys instanceof TArray && $keys->isEmptyArray()) {
                    $keys = [];
                } elseif (!$keys instanceof TKeyedArray || $keys->fallback_params) {
                    $has_fallback = true;
                    continue;
                } else {
                    $keys = $keys->properties;
                    $is_list = true;
                    $prev_key = -1;

                    foreach ($keys as $key) {
                        if ($key->possibly_undefined) {
                            $has_fallback = true;
                            continue;
                        }
                        if ($key->isSingleIntLiteral()) {
                            $key = $key->getSingleIntLiteral()->value;
                            $keys_array []= $key;
                            if ($is_list && $key-1 !== $prev_key) {
                                $is_list = false;
                            }
                            $prev_key = $key;
                        } elseif ($key->isSingleStringLiteral()) {
                            $keys_array []= $key->getSingleStringLiteral()->value;
                            $is_list = false;
                        } else {
                            $has_fallback = true;
                            continue;
                        }
                    }
                }

                if (count($keys_array) !== count($values)) {
                    IssueBuffer::maybeAdd(
                        new InvalidArgument(
                            'The keys array ' . $keys_type->getId() . ' must have exactly the same '
                            . 'number of elements as the values array '
                            . $values_type->getId(),
                            $event->getCodeLocation(),
                            'array_combine',
                        ),
                        $statements_source->getSuppressedIssues(),
                    );
                    return $statements_source->getCodebase()->analysis_php_version_id >= 8_00_00
                        ? Type::getNever()
                        : Type::getFalse();
                }

                $temp = array_combine(
                    $keys_array,
                    $values,
                );

                if ($temp) {
                    $result []= Type::getEmptyArrayAtomic();
                } else {
                    $result []= new TKeyedArray($result, null, null, $is_list);
                }
            }
        }

        if ($has_fallback) {
            $result []= new TArray([Type::getArrayKey(), Type::getMixed()]);
        }

        return new Union($result);
    }
}
