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
use function preg_match;
use function strtolower;
use function strtoupper;

/**
 * The literal strings strtolower() and strtoupper() return for literal strings
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
        return ['strtolower', 'strtoupper'];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $call_args = $event->getCallArgs();
        if (!isset($call_args[0]) || $call_args[0]->unpack) {
            return null;
        }

        $string_arg_type = $event->getStatementsSource()->getNodeTypeProvider()->getType($call_args[0]->value);

        if ($string_arg_type === null) {
            return null;
        }

        // before PHP 8.2 the case of a byte outside ASCII depends on the locale
        if ($event->getStatementsSource()->getCodebase()->analysis_php_version_id < 8_02_00) {
            foreach ($string_arg_type->getLiteralStrings() as $literal) {
                if (preg_match('/[\x80-\xff]/', $literal->value) === 1) {
                    return null;
                }
            }
        }

        return $event->getFunctionId() === 'strtolower'
            ? self::lowercaseLiterals($string_arg_type, false)
            : self::mapLiterals($string_arg_type, static fn(string $value): string => strtoupper($value));
    }

    public static function lowercaseLiterals(Union $type, bool $multibyte): ?Union
    {
        return self::mapLiterals(
            $type,
            static fn(string $value): string => $multibyte ? mb_strtolower($value, 'UTF-8') : strtolower($value),
        );
    }

    /**
     * @param pure-callable(string): string $map
     */
    private static function mapLiterals(Union $type, callable $map): ?Union
    {
        $literals = [];

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type::class !== TLiteralString::class) {
                return null;
            }

            $literals[] = Type::getAtomicStringFromLiteral($map($atomic_type->value));
        }

        return new Union($literals, ['from_docblock' => $type->from_docblock]);
    }
}
