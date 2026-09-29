<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Internal\TypeVisitor\PurityWildcardBinder;
use Psalm\Internal\TypeVisitor\PurityWildcardFinder;
use Psalm\Storage\Capabilities;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TCapabilities;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

/**
 * The `_` purity in a parameter's type (`Closure[_](): int $f`, `array<Closure[_](): int> $fs`,
 * `Traversable[_]<int, int> $t`): shorthand for a purity template of the function-like, which
 * inherits its purity from that parameter, like Hack's `(function()[_]: int) $f` with `[ctx $f]`.
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
     * The name of the purity template a parameter's wildcard stands for: not a valid template
     * name, so that it can't clash with one declared in the docblock.
     *
     * @psalm-pure
     */
    public static function templateName(string $param_name): string
    {
        return '_$' . $param_name;
    }

    /**
     * @psalm-pure
     */
    public static function isPlaceholder(Atomic $atomic): bool
    {
        return $atomic instanceof TTemplateParam
            && $atomic->param_name === self::NAME
            && $atomic->defining_class === '';
    }

    /**
     * Whether the type has the `_` purity anywhere.
     */
    public static function contains(Union $type): bool
    {
        $finder = new PurityWildcardFinder();
        $finder->traverse($type);

        return $finder->matches();
    }

    /**
     * The type with every `_` purity replaced by the template $template (already declared on the
     * function-like $defining_id).
     */
    public static function bind(Union $type, string $template, string $defining_id): Union
    {
        (new PurityWildcardBinder(
            new TTemplateParam($template, new Union([new TCapabilities(Capabilities::ALL)]), $defining_id),
        ))->traverse($type);

        return $type;
    }
}
