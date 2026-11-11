<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use PhpParser\Node\Expr\ConstFetch;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ConstFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\SimpleTypeInferer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Issue\InvalidArgument;
use Psalm\Issue\PossiblyInvalidArgument;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Union;

use function array_unique;
use function count;
use function implode;

use const SORT_LOCALE_STRING;
use const SORT_STRING;

/**
 * array_unique() compares the values as strings by default, and an object that can't be converted to a string
 * throws an Error as soon as there is another value to compare it with. The type of the result is the one of the stub.
 *
 * @internal
 */
final class ArrayUniqueReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return [
            'array_unique',
        ];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $statements_source = $event->getStatementsSource();
        if (!$statements_source instanceof StatementsAnalyzer) {
            return null;
        }

        $array_arg = null;
        $flags_arg = null;
        foreach ($event->getCallArgs() as $position => $call_arg) {
            if ($call_arg->unpack) {
                return null;
            }

            $name = $call_arg->name?->toString();
            if ($name !== null ? $name === 'array' : $position === 0) {
                $array_arg = $call_arg;
            } elseif ($name !== null ? $name === 'flags' : $position === 1) {
                $flags_arg = $call_arg;
            }
        }

        if ($array_arg === null) {
            return null;
        }

        if ($flags_arg !== null) {
            $flags_type = SimpleTypeInferer::infer(
                $statements_source->getCodebase(),
                $statements_source->node_data,
                $flags_arg->value,
                $statements_source->getAliases(),
                $statements_source,
            );

            if (!$flags_type && $flags_arg->value instanceof ConstFetch) {
                $flags_type = ConstFetchAnalyzer::getConstType(
                    $statements_source,
                    $flags_arg->value->name->toString(),
                    true,
                    $event->getContext(),
                );
            }

            if (!$flags_type
                || !$flags_type->isSingleIntLiteral()
                || ($flags_type->getSingleIntLiteral()->value !== SORT_STRING
                    && $flags_type->getSingleIntLiteral()->value !== SORT_LOCALE_STRING)
            ) {
                return null;
            }
        }

        $array_type = $statements_source->node_data->getType($array_arg->value);
        if (!$array_type) {
            return null;
        }

        $codebase = $statements_source->getCodebase();
        $bad_values = [];
        $other_values = false;
        foreach ($array_type->getAtomicTypes() as $array_atomic) {
            // nothing is compared with another value in an array of at most one known value
            if ($array_atomic instanceof TKeyedArray
                && $array_atomic->fallback_params === null
                && count($array_atomic->properties) <= 1
            ) {
                $other_values = true;
                continue;
            }

            if (!$array_atomic instanceof TArray && !$array_atomic instanceof TKeyedArray) {
                $other_values = true;
                continue;
            }

            $value_type = $array_atomic instanceof TKeyedArray
                ? $array_atomic->getGenericValueType()
                : $array_atomic->type_params[1];

            foreach ($value_type->getAtomicTypes() as $value_atomic) {
                if (self::isObjectWithoutToString($codebase, $value_atomic)) {
                    $bad_values[] = $value_atomic->getId();
                } else {
                    $other_values = true;
                }
            }
        }

        if (!$bad_values) {
            return null;
        }

        $message = 'Argument 1 of array_unique has the values ' . $array_type->getId() . ', and ' . ($other_values
            ? 'some of them are'
            : 'they are') . ' objects that can\'t be converted to a string ('
            . implode(', ', array_unique($bad_values)) . '): pass SORT_REGULAR as the flags';

        $location = new CodeLocation($statements_source, $array_arg->value);

        IssueBuffer::maybeAdd(
            $other_values
                ? new PossiblyInvalidArgument($message, $location, $event->getFunctionId())
                : new InvalidArgument($message, $location, $event->getFunctionId()),
            $statements_source->getSuppressedIssues(),
        );

        return null;
    }

    private static function isObjectWithoutToString(Codebase $codebase, Atomic $atomic): bool
    {
        if ($atomic instanceof TObjectWithProperties) {
            return !isset($atomic->methods['__tostring']);
        }

        if ($atomic instanceof TObject) {
            return true;
        }

        return $atomic instanceof TNamedObject
            && !$codebase->methodExists($atomic->value . '::__tostring');
    }
}
