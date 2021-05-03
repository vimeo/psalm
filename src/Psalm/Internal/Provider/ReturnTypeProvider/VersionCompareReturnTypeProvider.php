<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use function count;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Type;

class VersionCompareReturnTypeProvider implements \Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds() : array
    {
        return ['version_compare'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event) : Type\Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
            return Type::getMixed();
        }

        if (count($call_args) > 2) {
            $operator_type = $statements_source->node_data->getType($call_args[2]->value);

            if ($operator_type) {
                if (!$operator_type->hasMixed()) {
                    $acceptable_operator_type = new Type\Union([
                        new Type\Atomic\TLiteralString('<'),
                        new Type\Atomic\TLiteralString('lt'),
                        new Type\Atomic\TLiteralString('<='),
                        new Type\Atomic\TLiteralString('le'),
                        new Type\Atomic\TLiteralString('>'),
                        new Type\Atomic\TLiteralString('gt'),
                        new Type\Atomic\TLiteralString('>='),
                        new Type\Atomic\TLiteralString('ge'),
                        new Type\Atomic\TLiteralString('=='),
                        new Type\Atomic\TLiteralString('='),
                        new Type\Atomic\TLiteralString('eq'),
                        new Type\Atomic\TLiteralString('!='),
                        new Type\Atomic\TLiteralString('<>'),
                        new Type\Atomic\TLiteralString('ne'),
                    ]);

                    $codebase = $statements_source->getCodebase();

                    if (UnionTypeComparator::isContainedBy(
                        $codebase,
                        $operator_type,
                        $acceptable_operator_type
                    )) {
                        return Type::getBool();
                    }
                }
            }

            return new Type\Union([
                new Type\Atomic\TBool,
                new Type\Atomic\TNull,
            ]);
        }

        return new Type\Union([
            new Type\Atomic\TLiteralInt(-1),
            new Type\Atomic\TLiteralInt(0),
            new Type\Atomic\TLiteralInt(1),
        ]);
    }
}
