<?php declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Type\TypeCombiner;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Type;

use function in_array;

use const E_USER_DEPRECATED;
use const E_USER_ERROR;
use const E_USER_NOTICE;
use const E_USER_WARNING;

class TriggerErrorReturnTypeProvider implements \Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['trigger_error'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Type\Union
    {
        $codebase = $event->getStatementsSource()->getCodebase();
        $config = $codebase->config;
        if ($config->trigger_error_exits === 'always') {
            return new Type\Union([new Type\Atomic\TNever()]);
        }

        if ($config->trigger_error_exits === 'never') {
            return new Type\Union([new Type\Atomic\TTrue()]);
        }

        //default behaviour
        $call_args = $event->getCallArgs();
        $statements_source = $event->getStatementsSource();
        if (isset($call_args[1])
            && ($array_arg_type = $statements_source->getNodeTypeProvider()->getType($call_args[1]->value))
        ) {
            $return_types = [];
            foreach ($array_arg_type->getAtomicTypes() as $atomicType) {
                if ($atomicType instanceof Type\Atomic\TLiteralInt) {
                    if (in_array($atomicType->value, [E_USER_WARNING, E_USER_DEPRECATED, E_USER_NOTICE], true)) {
                        $return_types[] = new Type\Atomic\TTrue();
                    } elseif ($atomicType->value === E_USER_ERROR) {
                        $return_types[] = new Type\Atomic\TNever();
                    } else {
                        // not recognized int literal. return false before PHP8, fatal error since
                        $return_types[] = new Type\Atomic\TFalse();
                    }
                } else {
                    $return_types[] = new Type\Atomic\TBool();
                }
            }

            return TypeCombiner::combine($return_types, $codebase);
        }

        //default value is E_USER_NOTICE, so return true
        return new Type\Union([new Type\Atomic\TTrue()]);
    }
}
