<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Union;

use function mb_strtolower;
use function strtolower;

/**
 * strtolower() of string literals is the lowercased literals
 *
 * @internal
 */
final class StrtolowerReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['strtolower'];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $call_args = $event->getCallArgs();
        if (!isset($call_args[0]) || $call_args[0]->unpack) {
            return null;
        }

        $string_arg_type = $event->getStatementsSource()->getNodeTypeProvider()->getType($call_args[0]->value);

        return $string_arg_type !== null
            ? self::lowercaseLiterals($string_arg_type, false)
            : null;
    }

    public static function lowercaseLiterals(Union $type, bool $multibyte): ?Union
    {
        $literals = [];

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type::class !== TLiteralString::class) {
                return null;
            }

            $literals[] = Type::getAtomicStringFromLiteral(
                $multibyte ? mb_strtolower($atomic_type->value, 'UTF-8') : strtolower($atomic_type->value),
            );
        }

        return new Union($literals, ['from_docblock' => $type->from_docblock]);
    }
}
