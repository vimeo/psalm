<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Storage\Capabilities;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCapabilities;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

/**
 * The `_` purity of a closure type in a parameter (`Closure<_>(): int $f`): shorthand for a
 * purity template of the function-like, which inherits its purity from that parameter, like
 * Hack's `(function()[_]: int) $f` with `[ctx $f]`.
 *
 * @internal
 */
final class PurityWildcard
{
    public const NAME = '_';

    /**
     * The placeholder the type parser produces for `_`, bound to a real template once the
     * parameter it belongs to is known.
     *
     * @psalm-pure
     */
    public static function placeholder(bool $from_docblock): Union
    {
        return new Union(
            [new TTemplateParam(self::NAME, new Union([new TCapabilities(Capabilities::ALL)]), '')],
            ['from_docblock' => $from_docblock],
        );
    }

    /**
     * The name of the purity template a parameter's wildcard stands for.
     *
     * @psalm-pure
     */
    public static function templateName(string $param_name): string
    {
        return '_' . $param_name;
    }

    /**
     * @psalm-pure
     */
    public static function isPlaceholder(Union $purity): bool
    {
        foreach ($purity->getAtomicTypes() as $atomic) {
            if ($atomic instanceof TTemplateParam
                && $atomic->param_name === self::NAME
                && $atomic->defining_class === ''
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether a closure or callable in the type has the `_` purity.
     *
     * @psalm-pure
     */
    public static function contains(Union $type): bool
    {
        foreach ($type->getAtomicTypes() as $atomic) {
            if (($atomic instanceof TClosure || $atomic instanceof TCallable)
                && self::isPlaceholder($atomic->purity)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * The type with every `_` purity replaced by `impure`, where `_` has no parameter to stand for.
     *
     * @psalm-pure
     */
    public static function strip(Union $type): Union
    {
        $atomics = [];

        foreach ($type->getAtomicTypes() as $key => $atomic) {
            if (($atomic instanceof TClosure || $atomic instanceof TCallable)
                && self::isPlaceholder($atomic->purity)
            ) {
                $atomic = $atomic->setPurity(Capabilities::ALL);
            }

            $atomics[$key] = $atomic;
        }

        return $type->setTypes($atomics);
    }

    /**
     * The type with every `_` purity replaced by the template $template (already declared on
     * the function-like $defining_id).
     *
     * @psalm-pure
     */
    public static function bind(Union $type, string $template, string $defining_id): Union
    {
        $template_type = new Union([
            new TTemplateParam($template, new Union([new TCapabilities(Capabilities::ALL)]), $defining_id),
        ]);

        $atomics = [];

        foreach ($type->getAtomicTypes() as $key => $atomic) {
            if (($atomic instanceof TClosure || $atomic instanceof TCallable)
                && self::isPlaceholder($atomic->purity)
            ) {
                $atomic = $atomic->setPurity($template_type);
            }

            $atomics[$key] = $atomic;
        }

        return $type->setTypes($atomics);
    }
}
