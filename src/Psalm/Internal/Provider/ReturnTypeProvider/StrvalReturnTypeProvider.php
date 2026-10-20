<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Internal\Analyzer\Statements\Expression\CastAnalyzer;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function array_pop;
use function count;

/**
 * strval() returns the string a scalar or null is cast to, as (string) does, e.g. a decimal-int-string for an int
 *
 * @internal
 */
final class StrvalReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['strval'];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $call_args = $event->getCallArgs();
        if (count($call_args) !== 1 || $call_args[0]->unpack) {
            return null;
        }

        $statements_source = $event->getStatementsSource();
        $value_type = $statements_source->getNodeTypeProvider()->getType($call_args[0]->value);
        if ($value_type === null) {
            return null;
        }

        $atomic_types = $value_type->getAtomicTypes();
        $strings = [];

        while ($atomic_types) {
            $atomic_type = array_pop($atomic_types);

            if ($atomic_type instanceof TTemplateParam) {
                $atomic_types = [...$atomic_types, ...$atomic_type->as->getAtomicTypes()];

                continue;
            }

            $scalar_strings = CastAnalyzer::castScalarToString($atomic_type);

            // leave the strings of objects to the signature
            if ($scalar_strings === null) {
                return null;
            }

            $strings = [...$strings, ...$scalar_strings];
        }

        if ($strings === []) {
            return null;
        }

        return TypeCombiner::combine($strings, $statements_source->getCodebase());
    }
}
