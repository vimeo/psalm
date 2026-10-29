<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Issue\InvalidArgument;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Union;

use function call_user_func;
use function count;

/**
 * @internal
 */
final class StrReplaceReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return [
            'str_replace',
            'str_ireplace',
        ];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        $function_id = $event->getFunctionId();
        if (!$statements_source instanceof StatementsAnalyzer
            || count($call_args) < 3
        ) {
            // use the defaults, it will already report an error for the invalid params
            return null;
        }

        foreach ([$call_args[0], $call_args[1], $call_args[2]] as $call_arg) {
            if ($call_arg->unpack || $call_arg->name !== null) {
                return null;
            }
        }

        $search_count = self::getElementCount($statements_source->node_data->getType($call_args[0]->value));
        $replace_count = self::getElementCount($statements_source->node_data->getType($call_args[1]->value));
        if ($search_count !== null && $replace_count !== null && $replace_count < $search_count) {
            IssueBuffer::maybeAdd(
                new InvalidArgument(
                    'The replace array has fewer elements than the search array, the remaining searches '
                        . 'are replaced with an empty string',
                    $event->getCodeLocation(),
                    $function_id,
                ),
                $statements_source->getSuppressedIssues(),
            );
        }

        if ($subject_type = $statements_source->node_data->getType($call_args[2]->value)) {
            if (!$subject_type->isSingleStringLiteral()) {
                return null;
            }

            $first_arg = $statements_source->node_data->getType($call_args[0]->value);
            $second_arg = $statements_source->node_data->getType($call_args[1]->value);
            if ($first_arg
                && $second_arg && $first_arg->isSingleStringLiteral()
                && $second_arg->isSingleStringLiteral()
            ) {
                /**
                 * @var string $replaced_string
                 */
                $replaced_string = call_user_func(
                    $function_id,
                    $first_arg->getSingleStringLiteral()->value,
                    $second_arg->getSingleStringLiteral()->value,
                    $subject_type->getSingleStringLiteral()->value,
                );
                return Type::getString($replaced_string);
            }
        }

        return null;
    }

    /**
     * The number of elements of an array with a known size, if it has some
     *
     * @psalm-mutation-free
     */
    private static function getElementCount(?Union $type): ?int
    {
        if (!$type || !$type->isArray()) {
            return null;
        }

        $array = $type->getArray();
        if (!$array instanceof TKeyedArray || $array->fallback_params) {
            return null;
        }

        foreach ($array->properties as $property) {
            if ($property->possibly_undefined) {
                return null;
            }
        }

        return count($array->properties);
    }
}
