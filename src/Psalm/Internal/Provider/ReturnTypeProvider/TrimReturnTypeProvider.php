<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Union;

use function count;
use function ltrim;
use function rtrim;
use function str_contains;
use function strtolower;
use function trim;

/**
 * Trimming a literal string gives a literal string
 *
 * @internal
 */
final class TrimReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return [
            'trim',
            'ltrim',
            'rtrim',
            'chop',
        ];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof StatementsAnalyzer
            || (count($call_args) !== 1 && count($call_args) !== 2)
        ) {
            // use the defaults, it will already report an error for the invalid params
            return null;
        }

        foreach ($call_args as $call_arg) {
            if ($call_arg->unpack || $call_arg->name !== null) {
                return null;
            }
        }

        $string_type = $statements_source->node_data->getType($call_args[0]->value);
        if (!$string_type || !$string_type->isSingleStringLiteral()) {
            return null;
        }

        // the same characters the stubs have as the default
        $characters = " \t\n\r\0\x0B";
        if (isset($call_args[1])) {
            $characters_type = $statements_source->node_data->getType($call_args[1]->value);
            if (!$characters_type || !$characters_type->isSingleStringLiteral()) {
                return null;
            }

            $characters = $characters_type->getSingleStringLiteral()->value;

            // a range in the characters can be invalid
            if (str_contains($characters, '..')) {
                return null;
            }
        }

        $string = $string_type->getSingleStringLiteral()->value;

        return Type::getString(
            match (strtolower($event->getFunctionId())) {
                'ltrim' => ltrim($string, $characters),
                'rtrim', 'chop' => rtrim($string, $characters),
                default => trim($string, $characters),
            },
        );
    }
}
