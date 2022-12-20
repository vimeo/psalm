<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\Statements\Expression\IncludeAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Union;

use function basename;
use function count;

/**
 * @internal
 */
class BasenameReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['basename'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $call_args = $event->getCallArgs();
        if (count($call_args) === 0) {
            return null;
        }

        $statements_source = $event->getStatementsSource();

        $evaled_path = IncludeAnalyzer::getPathTo(
            $call_args[0]->value,
            null,
            null,
            $statements_source->getFileName(),
            $statements_source->getCodebase()->config,
        );

        if ($evaled_path === null) {
            return Type::getString();
        }

        $basename = basename($evaled_path);

        return Type::getString($basename);
    }
}
