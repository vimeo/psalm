<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Union;

use function assert;
use function count;

/**
 * @internal
 */
class MbInternalEncodingReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['mb_internal_encoding'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $call_args = $event->getCallArgs();
        if (count($call_args) === 0) {
            return Type::getString();
        }

        $statements_source = $event->getStatementsSource();
        $nodeTypeProvider = $statements_source->getNodeTypeProvider();
        $codebase = $statements_source->getCodebase();

        $first_arg_type = $nodeTypeProvider->getType($call_args[0]->value);
        if ($first_arg_type === null) {
            if ($codebase->analysis_php_version_id >= 8_00_00) {
                return new Union([new TString(), new TTrue()]);
            } else {
                return new Union([new TString(), new TBool()]);
            }
        }

        $has_stringable = false;
        $has_tostring = false;
        $has_string = false;
        $has_null = false;
        $has_unknown = false;

        foreach ($first_arg_type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof Type\Atomic\TNamedObject
                && $codebase->classlikes->classImplements($atomic_type->value, 'Stringable')
            ) {
                $has_stringable = true;
                continue;
            }

            if ($atomic_type instanceof Type\Atomic\TObjectWithProperties
                && isset($atomic_type->methods['__tostring'])
            ) {
                $has_tostring = true;
                continue;
            }

            if ($atomic_type instanceof TString) {
                $has_string = true;
                continue;
            }

            if ($atomic_type instanceof TNull) {
                $has_null = true;
                continue;
            }

            $has_unknown = true;
        }

        $list_return_atomics = [];
        if ($has_string || $has_stringable || $has_tostring) {
            if ($codebase->analysis_php_version_id >= 8_00_00) {
                $list_return_atomics[] = new TTrue();
            } else {
                $list_return_atomics[] = new TBool();
            }
        }

        if ($has_null) {
            if ($codebase->analysis_php_version_id >= 8_00_00) {
                $list_return_atomics[] = new TString();
            } else {
                $list_return_atomics[] = new TFalse();
            }
        }

        if ($has_unknown) {
            if ($codebase->analysis_php_version_id >= 8_00_00) {
                $list_return_atomics[] = new TNever();
            } else {
                $list_return_atomics[] = new TNull();
            }
        }

        assert($list_return_atomics !== []);
        return new Union($list_return_atomics);
    }
}
