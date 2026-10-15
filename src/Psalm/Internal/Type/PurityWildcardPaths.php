<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use PhpParser\Node;
use Psalm\Exception\TypeParseTreeException;
use Psalm\Internal\Type\ParseTree\CallableTree;
use Psalm\Internal\Type\ParseTree\CallableWithReturnTypeTree;
use Psalm\Internal\Type\ParseTree\EncapsulationTree;
use Psalm\Internal\Type\ParseTree\GenericTree;
use Psalm\Internal\Type\ParseTree\IntersectionTree;
use Psalm\Internal\Type\ParseTree\KeyedArrayPropertyTree;
use Psalm\Internal\Type\ParseTree\KeyedArrayTree;
use Psalm\Internal\Type\ParseTree\NullableTree;
use Psalm\Internal\Type\ParseTree\PurityTree;
use Psalm\Internal\Type\ParseTree\UnionTree;
use Psalm\Internal\Type\ParseTree\Value;

use function array_fill_keys;
use function array_keys;
use function count;
use function explode;
use function implode;
use function in_array;
use function krsort;
use function ksort;
use function str_starts_with;
use function strlen;
use function strrpos;
use function strtolower;
use function substr;
use function substr_replace;

/**
 * Where a function-like's parameter would take the `_` purity ({@see PurityWildcard}), for
 * `--alter` to write it into the parameter's docblock type.
 *
 * A path goes from the parameter's type to a closure or callable type (`e|c` for the closures in
 * `list<Closure(): int>`), or to an iterable or generic object whose purity arguments take the `_`
 * (`|p|traversable|0|impure` for `Traversable<int, int>`). Its steps are `e`, into the type
 * arguments or the values of an array shape, and `r`, into the return type of a callable; never
 * into the parameters of a callable, which the function-like does not call. The purity arguments
 * of a generic object are given by the short name of its class, the index of the purity argument
 * that takes the `_`, the default of each of them, written when the type has none, and `t` if the
 * type must have type arguments written for the `_` to be added.
 *
 * @internal
 */
final class PurityWildcardPaths
{
    private const CALLABLE_KEYWORDS = ['closure', '\\closure', 'callable'];

    /**
     * @psalm-pure
     */
    public static function closure(string $steps): string
    {
        return $steps . '|c';
    }

    /**
     * @param list<string> $defaults the default of each purity argument of the class
     * @param bool $type_args_required whether the class has an invariant type template: written
     *        without type arguments, the class takes any of them, but given purity arguments it takes
     *        those of the template bounds only (`Foo[_]` is `Foo[_]<mixed>`), so the `_` can't be
     *        added there
     * @psalm-pure
     */
    public static function purityArgument(
        string $steps,
        string $class,
        int $index,
        array $defaults,
        bool $type_args_required = false,
    ): string {
        $short_name = strtolower(substr($class, (int) strrpos('\\' . $class, '\\')));

        return $steps . '|p|' . $short_name . '|' . $index . '|' . implode(',', $defaults)
            . ($type_args_required ? '|t' : '');
    }

    /**
     * The type string with the `_` purity added at the paths, keeping the rest as written, or null
     * if one of the paths can't be found in it (e.g. behind a type alias) or has a purity other
     * than the default already.
     *
     * @param list<string> $paths
     */
    public static function addToTypeString(string $type, array $paths): ?string
    {
        if ($paths === []) {
            return null;
        }

        try {
            $tree = (new ParseTreeCreator(TypeTokenizer::tokenize($type)))->create();
        } catch (TypeParseTreeException) {
            return null;
        }

        /** @var array<string, true> $found */
        $found = [];
        /** @var array<int, array{int, int, string}> $edits keyed by the start offset */
        $edits = [];
        $wanted = array_fill_keys($paths, true);

        if (!self::collectEdits($tree, '', $wanted, $found, $edits)) {
            return null;
        }

        if (count($found) !== count($wanted)) {
            return null;
        }

        // later edits first, so that the offsets of the earlier ones stay right
        krsort($edits);

        foreach ($edits as [$start, $end, $replacement]) {
            $type = substr_replace($type, $replacement, $start, $end - $start);
        }

        return $type;
    }

    /**
     * @param array<string, true> $wanted
     * @param array<string, true> $found
     * @param array<int, array{int, int, string}> $edits
     * @return bool false if a wanted path can't take the `_`
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private static function collectEdits(
        ParseTree $node,
        string $steps,
        array $wanted,
        array &$found,
        array &$edits,
    ): bool {
        if ($node instanceof UnionTree
            || $node instanceof NullableTree
            || $node instanceof EncapsulationTree
            || $node instanceof IntersectionTree
        ) {
            foreach ($node->children as $child) {
                if (!self::collectEdits($child, $steps, $wanted, $found, $edits)) {
                    return false;
                }
            }

            return true;
        }

        if ($node instanceof CallableWithReturnTypeTree) {
            $callable = $node->children[0] ?? null;
            $return_type = $node->children[1] ?? null;

            return ($callable === null || self::collectEdits($callable, $steps, $wanted, $found, $edits))
                && ($return_type === null || self::collectEdits($return_type, $steps . 'r', $wanted, $found, $edits));
        }

        if ($node instanceof KeyedArrayTree) {
            foreach ($node->children as $child) {
                $value = $child instanceof KeyedArrayPropertyTree ? ($child->children[0] ?? null) : $child;

                if ($value !== null && !self::collectEdits($value, $steps . 'e', $wanted, $found, $edits)) {
                    return false;
                }
            }

            return true;
        }

        if ($node instanceof CallableTree) {
            // the parameters of a callable are not called by the function-like
            return self::editKeyword(
                $node->value,
                $node->offset_start,
                $node->offset_end,
                $node->purity === null ? null : [$node->purity],
                false,
                $steps,
                $wanted,
                $found,
                $edits,
            );
        }

        if ($node instanceof GenericTree) {
            if (!self::editKeyword(
                $node->value,
                $node->offset_start,
                $node->offset_end,
                $node->purity instanceof PurityTree ? $node->purity->children : null,
                $node->children !== [],
                $steps,
                $wanted,
                $found,
                $edits,
            )) {
                return false;
            }

            foreach ($node->children as $child) {
                if (!self::collectEdits($child, $steps . 'e', $wanted, $found, $edits)) {
                    return false;
                }
            }

            return true;
        }

        if ($node instanceof Value) {
            return self::editKeyword(
                $node->value,
                $node->offset_start,
                $node->offset_end,
                null,
                false,
                $steps,
                $wanted,
                $found,
                $edits,
            );
        }

        return true;
    }

    /**
     * Adds the `_` to a closure, callable, iterable or generic object type if one of the paths
     * wants it there.
     *
     * @param list<ParseTree>|null $purity_arguments the purity in brackets after the keyword, if any
     * @param bool $has_type_args whether type arguments follow the keyword (`<...>`)
     * @param array<string, true> $wanted
     * @param array<string, true> $found
     * @param array<int, array{int, int, string}> $edits
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private static function editKeyword(
        string $keyword,
        ?int $offset_start,
        ?int $offset_end,
        ?array $purity_arguments,
        bool $has_type_args,
        string $steps,
        array $wanted,
        array &$found,
        array &$edits,
    ): bool {
        $lc_keyword = strtolower($keyword);
        $impure_prefixed = str_starts_with($lc_keyword, 'impure-');
        $base_keyword = $impure_prefixed ? substr($keyword, strlen('impure-')) : $keyword;

        if (in_array(strtolower($base_keyword), self::CALLABLE_KEYWORDS, true)) {
            $path = self::closure($steps);

            if (!isset($wanted[$path])) {
                return true;
            }

            $found[$path] = true;

            return self::editPurity(
                $base_keyword,
                $impure_prefixed,
                $offset_start,
                $offset_end,
                $purity_arguments,
                [0],
                ['impure'],
                $edits,
            );
        }

        $short_name = strtolower(substr($keyword, (int) strrpos('\\' . $keyword, '\\')));
        /** @var array<int, true> $indices */
        $indices = [];
        $defaults = [];

        foreach (array_keys($wanted) as $path) {
            $parts = explode('|', $path);

            if ($parts[0] !== $steps || ($parts[1] ?? null) !== 'p' || ($parts[2] ?? null) !== $short_name) {
                continue;
            }

            if (($parts[5] ?? null) === 't' && !$has_type_args) {
                return false;
            }

            $found[$path] = true;
            $indices[(int) ($parts[3] ?? 0)] = true;
            $defaults = explode(',', $parts[4] ?? 'impure');
        }

        if ($indices === []) {
            return true;
        }

        ksort($indices);

        return self::editPurity(
            $keyword,
            false,
            $offset_start,
            $offset_end,
            $purity_arguments,
            array_keys($indices),
            $defaults,
            $edits,
        );
    }

    /**
     * @param list<ParseTree>|null $purity_arguments
     * @param list<int> $indices the purity arguments that take the `_`
     * @param list<string> $defaults
     * @param array<int, array{int, int, string}> $edits
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private static function editPurity(
        string $keyword,
        bool $impure_prefixed,
        ?int $offset_start,
        ?int $offset_end,
        ?array $purity_arguments,
        array $indices,
        array $defaults,
        array &$edits,
    ): bool {
        if ($offset_start === null || $offset_end === null) {
            return false;
        }

        if ($purity_arguments !== null) {
            if ($impure_prefixed) {
                return false;
            }

            foreach ($indices as $index) {
                $argument = $purity_arguments[$index] ?? null;

                // only the default purity spelled out is replaced: another one was chosen deliberately
                if (!$argument instanceof Value
                    || !in_array(strtolower($argument->value), ['impure', PurityWildcard::NAME], true)
                ) {
                    return false;
                }

                $edits[$argument->offset_start] = [
                    $argument->offset_start,
                    $argument->offset_end,
                    PurityWildcard::NAME,
                ];
            }

            return true;
        }

        if (str_starts_with(strtolower($keyword), 'pure-')) {
            return false;
        }

        $slots = $defaults;

        foreach ($indices as $index) {
            if (!isset($slots[$index])) {
                return false;
            }

            $slots[$index] = PurityWildcard::NAME;
        }

        $edits[$offset_start] = [$offset_start, $offset_end, $keyword . '[' . implode(', ', $slots) . ']'];

        return true;
    }

    /**
     * A native type as written, if it is one a docblock type can be made from.
     */
    public static function getNativeTypeString(Node $type): ?string
    {
        if ($type instanceof Node\Identifier) {
            return $type->toString();
        }

        if ($type instanceof Node\Name) {
            return $type->toCodeString();
        }

        if ($type instanceof Node\NullableType) {
            $inner = self::getNativeTypeString($type->type);

            return $inner === null ? null : '?' . $inner;
        }

        if ($type instanceof Node\UnionType) {
            $types = [];

            foreach ($type->types as $inner_type) {
                $inner = self::getNativeTypeString($inner_type);

                if ($inner === null) {
                    return null;
                }

                $types[] = $inner;
            }

            return implode('|', $types);
        }

        return null;
    }
}
