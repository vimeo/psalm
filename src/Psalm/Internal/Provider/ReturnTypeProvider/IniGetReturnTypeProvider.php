<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Union;

/**
 * ini_get() returns false only for a setting nothing has registered, which can't happen to these core settings.
 *
 * @internal
 */
final class IniGetReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    private const STRING_SETTINGS = [
        'date.timezone' => true,
        'memory_limit' => true,
    ];

    private const NUMERIC_SETTINGS = [
        'default_socket_timeout' => true,
        'max_execution_time' => true,
        'max_input_time' => true,
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

        $return_atomics = [];
        foreach ($setting_type->getAtomicTypes() as $atomic_type) {
            if (!$atomic_type instanceof TLiteralString) {
                return null;
            }

            $setting = $atomic_type->value;
            if (isset(self::NUMERIC_SETTINGS[$setting])) {
                $return_atomics[] = new TNumericString();
            } elseif (isset(self::STRING_SETTINGS[$setting])
                || ($setting === 'max_memory_limit' && $has_max_memory_limit)
            ) {
                $return_atomics[] = new TString();
            } else {
                return null;
            }
        }

        return TypeCombiner::combine($return_atomics);
    }
}
