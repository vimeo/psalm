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
final class BasenameReturnTypeProvider implements FunctionReturnTypeProviderInterface
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
            $union = $statements_source->getNodeTypeProvider()->getType($call_args[0]->value);
            $generic = false;
            $non_empty = false;
            if ($union !== null) {
                foreach ($union->getAtomicTypes() as $atomic) {
                    if ($atomic instanceof Type\Atomic\TNonFalsyString) {
                        continue;
                    }

                    if ($atomic instanceof Type\Atomic\TLiteralString) {
                        if ($atomic->value === '') {
                            $generic = true;
                            break;
                        }

                        if ($atomic->value === '0') {
                            $non_empty = true;
                            continue;
                        }

                        continue;
                    }

                    if ($atomic instanceof Type\Atomic\TNonEmptyString) {
                        $non_empty = true;
                        continue;
                    }

                    $generic = true;
                    break;
                }
            }

            if ($union === null || $generic) {
                return Type::getString();
            }

            if ($non_empty) {
                return Type::getNonEmptyString();
            }

            return Type::getNonFalsyString();
        }

        $basename = basename($evaled_path);

        return Type::getString($basename);
    }
}
