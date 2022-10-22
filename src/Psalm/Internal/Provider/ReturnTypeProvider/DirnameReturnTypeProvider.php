<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\Expression\IncludeAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Union;

use function count;
use function dirname;

class DirnameReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['dirname'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $call_args = $event->getCallArgs();
        if (count($call_args) === 0) {
            return null;
        }

        $dir_level = 1;

        if (isset($call_args[1])) {
            if ($call_args[1]->value instanceof PhpParser\Node\Scalar\LNumber) {
                $dir_level = $call_args[1]->value->value;
            } else {
                return null;
            }
        }

        $statement_source = $event->getStatementsSource();

        $evaled_path = IncludeAnalyzer::getPathTo(
            $call_args[0]->value,
            null,
            null,
            $statement_source->getFileName(),
            $statement_source->getCodebase()->config
        );

        if (!$evaled_path) {
            return null;
        }

        $path_to_file = dirname($evaled_path, $dir_level);

        return new Union([
            new TLiteralString($path_to_file),
        ]);
    }
}
