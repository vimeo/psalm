<?php declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use function count;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type;

class SScanFReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds(): array
    {
        return ['sscanf'];
    }

    public static function getFunctionReturnType(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) : Type\Union {
        if (count($call_args) === 2) {
            return new Type\Union([
                new Type\Atomic\TList(Type::parseString('float|int|string')),
            ]);
        }

        return new Type\Union([
            new Type\Atomic\TInt(),
        ]);
    }
}
