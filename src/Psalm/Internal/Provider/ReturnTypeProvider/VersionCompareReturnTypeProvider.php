<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use function count;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\StatementsSource;
use Psalm\Type;

class VersionCompareReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return ['version_compare'];
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     */
    public static function getFunctionReturnType(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) : Type\Union {
        if (count($call_args) > 2) {
            if (isset($call_args[2]->value->inferredType)) {
                $operator_type = $call_args[2]->value->inferredType;

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

                    if (TypeAnalyzer::isContainedBy(
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
