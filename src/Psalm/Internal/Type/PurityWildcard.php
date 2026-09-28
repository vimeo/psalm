<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Storage\Capabilities;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCapabilities;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function array_pop;
use function ctype_space;
use function end;
use function in_array;
use function preg_match;
use function strlen;

/**
 * The `_` purity of a closure type in a parameter (`Closure[_](): int $f`): shorthand for a
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
     * The docblock type with the `_` purity added to its closure and callable types, or null if it
     * has none without a purity: those it is a union of, not those of their parameters, return
     * types or type arguments (`Closure(Closure(): int): int|null` becomes
     * `Closure[_](Closure(): int): int|null`).
     *
     * @psalm-pure
     */
    public static function addToTypeString(string $type): ?string
    {
        $result = '';
        $changed = false;
        /** @var list<'params'|'group'|'generic'> $stack */
        $stack = [];
        // whether we are in the return type of a callable, and whether the enclosing groups are
        $in_return = false;
        /** @var list<bool> $group_in_return */
        $group_in_return = [];
        $previous = '';
        $length = strlen($type);

        for ($i = 0; $i < $length; $i++) {
            // `impure-` and `[impure]` spell out the default purity: they are replaced by `_` too
            if (preg_match(
                '/\G(?<![\w\\\\-])(impure-)?(\\\\?Closure|callable)(?![\w-])(\s*\[\s*impure\s*\])?/',
                $type,
                $matches,
                0,
                $i,
            )) {
                $match = $matches[0] ?? '';
                $i += strlen($match) - 1;

                $has_other_purity = !isset($matches[3]) && preg_match('/\G\s*\[/', $type, $_, 0, $i + 1);

                if (!$has_other_purity
                    && !$in_return
                    && !in_array('params', $stack, true)
                    && !in_array('generic', $stack, true)
                ) {
                    $result .= ($matches[2] ?? '') . '[' . self::NAME . ']';
                    $changed = true;
                } else {
                    $result .= $match;
                }

                $previous = 'keyword';
                continue;
            }

            $char = $type[$i];
            $top_level = !in_array('params', $stack, true) && !in_array('generic', $stack, true);

            if ($char === '(') {
                if ($previous === 'keyword' || $previous === ']') {
                    $stack[] = 'params';
                } else {
                    $stack[] = 'group';
                    $group_in_return[] = $in_return;
                }
            } elseif ($char === '<' || $char === '{' || $char === '[') {
                $stack[] = 'generic';
            } elseif ($char === ')' || $char === '>' || $char === '}' || $char === ']') {
                $popped = array_pop($stack);

                if ($popped === 'params') {
                    if (preg_match('/\G\)\s*:/', $type, $_, 0, $i)
                        && !in_array('params', $stack, true)
                        && !in_array('generic', $stack, true)
                    ) {
                        $in_return = true;
                    }
                } elseif ($popped === 'group') {
                    $in_return = (bool) array_pop($group_in_return);
                }
            } elseif ($char === '|' && $top_level) {
                $in_return = (bool) end($group_in_return);
            }

            if (!ctype_space($char)) {
                $previous = $char;
            }

            $result .= $char;
        }

        return $changed ? $result : null;
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
