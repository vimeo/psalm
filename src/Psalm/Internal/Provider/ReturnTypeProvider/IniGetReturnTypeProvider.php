<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Union;

/**
 * ini_get() returns false only for a setting nothing has registered, which can't happen to these core settings.
 * The value is the raw INI text, so even numeric settings can hold "30s" or "": the result is string, not
 * numeric-string.
 *
 * @internal
 */
final class IniGetReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    private const CORE_SETTINGS = [
        'date.timezone' => true,
        'default_socket_timeout' => true,
        'max_execution_time' => true,
        'max_input_time' => true,
        'memory_limit' => true,
        'precision' => true,
    ];

    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['ini_get'];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $call_args = $event->getCallArgs();
        if (!isset($call_args[0]) || $call_args[0]->unpack) {
            return null;
        }

        $statements_source = $event->getStatementsSource();
        $setting_type = $statements_source->getNodeTypeProvider()->getType($call_args[0]->value);
        if ($setting_type === null) {
            return null;
        }

        $has_max_memory_limit = $statements_source->getCodebase()->analysis_php_version_id >= 8_05_00;

        foreach ($setting_type->getAtomicTypes() as $atomic_type) {
            if (!$atomic_type instanceof TLiteralString) {
                return null;
            }

            $setting = $atomic_type->value;
            if (!isset(self::CORE_SETTINGS[$setting])
                && !($setting === 'max_memory_limit' && $has_max_memory_limit)
            ) {
                return null;
            }
        }

        return Type::getString();
    }
}
