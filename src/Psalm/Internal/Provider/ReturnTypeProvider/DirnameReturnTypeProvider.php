<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\Statements\Expression\IncludeAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Union;

use function array_values;
use function count;
use function dirname;

/**
 * @internal
 */
final class DirnameReturnTypeProvider implements FunctionReturnTypeProviderInterface
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

        $statements_source = $event->getStatementsSource();
        $node_type_provider = $statements_source->getNodeTypeProvider();

        $union = $node_type_provider->getType($call_args[0]->value);
        $generic = false;
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

                    // 0 will be non-falsy too (.)
                    continue;
                }

                if ($atomic instanceof Type\Atomic\TNonEmptyString
                    || $atomic instanceof Type\Atomic\TEmptyNumeric) {
                    continue;
                }

                // generic string is the only other possible case of empty string
                // which would result in a generic string
                $generic = true;
                break;
            }
        }

        $fallback_type = Type::getNonFalsyString();
        if ($union === null || $generic) {
            $fallback_type = Type::getString();
        }

        $dir_level = 1;
        if (isset($call_args[1])) {
            $type = $node_type_provider->getType($call_args[1]->value);

            if ($type !== null && $type->isSingle()) {
                $atomic_type = array_values($type->getAtomicTypes())[0];
                if ($atomic_type instanceof TLiteralInt &&
                    $atomic_type->value > 0) {
                    $dir_level = $atomic_type->value;
                } else {
                    return $fallback_type;
                }
            }
        }

        $evaled_path = IncludeAnalyzer::getPathTo(
            $call_args[0]->value,
            null,
            null,
            $statements_source->getFileName(),
            $statements_source->getCodebase()->config,
        );

        if ($evaled_path === null) {
            return $fallback_type;
        }

        $path_to_file = dirname($evaled_path, $dir_level);

        return Type::getString($path_to_file);
    }
}
