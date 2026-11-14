<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Codebase;
use Psalm\Type;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TStarProjection;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function array_any;
use function array_keys;
use function reset;

/**
 * The `*` type argument of a generic object (`Pair<int, *>`), see {@see TStarProjection}.
 *
 * @internal
 * @psalm-immutable
 */
final class StarProjection
{
    public const NAME = '*';

    /**
     * @psalm-pure
     */
    public static function create(bool $from_docblock): Union
    {
        return new Union([new TStarProjection($from_docblock)], ['from_docblock' => $from_docblock]);
    }

    /**
     * @psalm-pure
     */
    public static function isStar(Union $type): bool
    {
        return $type->isSingle() && $type->getSingleAtomic() instanceof TStarProjection;
    }

    /**
     * What reading a template bound to $argument gives: $bound, the template's bound, for a `*`
     *
     * @psalm-pure
     */
    public static function read(Union $argument, Union $bound): Union
    {
        return self::isStar($argument) ? $bound : $argument;
    }

    /**
     * The generic object with each `*` argument read as the bound of its template, as a use of the class leaving
     * the argument out does: a bound that is an earlier template (`@template B of A`) takes its argument.
     *
     * @psalm-mutation-free
     */
    public static function readArguments(TGenericObject $type, Codebase $codebase): TGenericObject
    {
        if (!array_any($type->type_params, self::isStar(...))) {
            return $type;
        }

        $template_types = $codebase->classlike_storage_provider->has($type->value)
            ? $codebase->classlike_storage_provider->get($type->value)->template_types ?? []
            : [];
        $template_names = array_keys($template_types);
        $read = [];
        $type_params = [];

        foreach ($type->type_params as $offset => $type_param) {
            $template_name = $template_names[$offset] ?? null;

            if (self::isStar($type_param)) {
                $type_param = $template_name === null ? Type::getMixed() : reset($template_types[$template_name]);
                $bound = $type_param->isSingle() ? $type_param->getSingleAtomic() : null;

                if ($bound instanceof TTemplateParam && isset($read[$bound->param_name])) {
                    $type_param = $read[$bound->param_name];
                }
            }

            if ($template_name !== null) {
                $read[$template_name] = $type_param;
            }

            $type_params[] = $type_param;
        }

        return $type->setTypeParams($type_params);
    }
}
