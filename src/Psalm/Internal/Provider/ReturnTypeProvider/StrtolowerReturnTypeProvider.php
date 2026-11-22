<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Union;

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

        if (!isset($call_args[0])) {
            return null;
        }

        $string_type = $event->getStatementsSource()->getNodeTypeProvider()->getType($call_args[0]->value);

        if ($string_type === null || !$string_type->allStringLiterals()) {
            return null;
        }

        // before PHP 8.2 the case of a byte outside ASCII depends on the locale
        $locale_insensitive = $event->getStatementsSource()->getCodebase()->analysis_php_version_id >= 8_02_00;

        $results = [];

        foreach ($string_type->getLiteralStrings() as $literal) {
            if (!$locale_insensitive && preg_match('/[\x80-\xff]/', $literal->value) === 1) {
                return null;
            }

            $results[] = TLiteralString::make(
                $event->getFunctionId() === 'strtolower'
                    ? strtolower($literal->value)
                    : strtoupper($literal->value),
            );
        }

        return $results ? new Union($results) : null;
    }
}
