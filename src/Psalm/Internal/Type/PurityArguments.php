<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Storage\Capabilities;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type\Union;

use function array_slice;
use function array_values;
use function count;
use function reset;

/**
 * The purity arguments of a generic object type: the arguments of the purity templates of its
 * class, which come after its type templates. They are written in brackets before the type
 * parameters (`Traversable[pure]<int, string>`) and stored after them.
 *
 * @internal
 * @psalm-immutable
 */
final class PurityArguments
{
    /**
     * Splits type parameters into the leading type arguments and the trailing purity arguments.
     *
     * @param array<Union> $type_params
     * @return array{list<Union>, list<Union>}
     * @psalm-pure
     */
    public static function split(array $type_params): array
    {
        $type_params = array_values($type_params);
        $type_arg_count = count($type_params);

        while ($type_arg_count > 0 && Capabilities::isPurityArgument($type_params[$type_arg_count - 1])) {
            $type_arg_count--;
        }

        return [array_slice($type_params, 0, $type_arg_count), array_slice($type_params, $type_arg_count)];
    }

    /**
     * How many of the templates of a class are type templates, i.e. not purity templates.
     *
     * @psalm-mutation-free
     */
    public static function countTypeTemplates(ClassLikeStorage $storage): int
    {
        $count = 0;

        foreach ($storage->template_types ?? [] as $type_map) {
            $bound = reset($type_map);

            if (!Capabilities::isPurityType($bound)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Whether the type arguments and the purity arguments given to a class are no more than its
     * type templates and its purity templates.
     *
     * @param array<Union> $type_params
     * @psalm-mutation-free
     */
    public static function fit(array $type_params, ClassLikeStorage $storage): bool
    {
        [$type_args, $purity_args] = self::split($type_params);
        $type_template_count = self::countTypeTemplates($storage);

        return count($type_args) <= $type_template_count
            && count($purity_args) <= count($storage->template_types ?? []) - $type_template_count;
    }

    /**
     * The type parameters of a use of the class without the purity arguments it has no purity
     * templates for: what `static[P]<K, V>` becomes for a late static class that binds `P` itself
     * (`@extends Base[pure]<K, V>`) and so declares no purity template of its own.
     *
     * @param array<Union> $type_params
     * @return list<Union>
     * @psalm-mutation-free
     */
    public static function trim(array $type_params, ClassLikeStorage $storage): array
    {
        [$type_args, $purity_args] = self::split($type_params);
        $purity_template_count = count($storage->template_types ?? []) - self::countTypeTemplates($storage);

        return [...$type_args, ...array_slice($purity_args, 0, $purity_template_count)];
    }

    /**
     * The type parameters of a use of the class, with the purity arguments in the positions of the
     * purity templates: `Foo[pure]`, for a class with type templates, gives them their bounds.
     * Parameters that do not fit the templates are left alone, for the checks to report them.
     *
     * @param list<Union> $type_params
     * @return list<Union>
     * @psalm-mutation-free
     */
    public static function align(array $type_params, ClassLikeStorage $storage): array
    {
        [$type_args, $purity_args] = self::split($type_params);

        if ($purity_args === []) {
            return $type_params;
        }

        $type_template_count = self::countTypeTemplates($storage);

        if (count($type_args) >= $type_template_count
            || count($purity_args) > count($storage->template_types ?? []) - $type_template_count
        ) {
            return $type_params;
        }

        $i = 0;

        foreach ($storage->template_types ?? [] as $template_name => $type_map) {
            if ($i++ < count($type_args)) {
                continue;
            }

            $bound = reset($type_map);

            if (Capabilities::isPurityType($bound)) {
                break;
            }

            $type_args[] = self::getOmittedArgument($storage, $template_name, $bound);
        }

        return [...$type_args, ...$purity_args];
    }

    /**
     * What a template argument left out of a use of the class stands for: its bound. An unbounded template's
     * (`mixed`), a purity template's, and that of a template the class never writes (see
     * ClassLikeStorage::$write_free_templates) is no more specific than a use without arguments, which any instance
     * fits: it stands for whatever the instance binds the template to, and is not compared invariantly. The bound of
     * a bounded template that code could write a value of into the instance (`@template T as object` with a
     * `setT(T $t)`) is still the argument: an instance bound to a narrower type would take a value it does not allow.
     *
     * @psalm-mutation-free
     */
    public static function getOmittedArgument(ClassLikeStorage $storage, string $template_name, Union $bound): Union
    {
        return $bound->isMixed()
            || Capabilities::isPurityType($bound)
            || isset($storage->write_free_templates[$template_name])
            ? $bound->setProperties(['had_template' => true])
            : $bound;
    }
}
