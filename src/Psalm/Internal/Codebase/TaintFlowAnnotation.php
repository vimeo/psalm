<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Psalm\Storage\ImmutableNonCloneableTrait;

use function array_map;
use function count;
use function explode;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use function preg_split;
use function str_ends_with;
use function str_starts_with;
use function substr;
use function trim;

/**
 * A `@psalm-flow` annotation, or a flow of dictionaries/InternalTaintStoreMap.php, parsed. This is the one place their
 * syntax is read: the docblock check that reports a malformed one and the code applying them both use it, so that one
 * can't accept what the other ignores.
 *
 * - `($a, $b) -> return` (RETURN): what a call returns holds what is given to the parameters, what the object it is
 *   called on holds for `$this`, and what the callable given to a parameter returns for `$name()`;
 * - `($value) -> Store::$data[$key]` (STORE_WRITE): a call stores what is given to the parameters under a key (see
 *   TaintStore);
 * - `Store::$data[$key] -> return` (STORE_READ): a call returns what is stored under a key;
 * - `proxy function($a, $b)`, `proxy function($a) -> return` (PROXY): a call calls the function-like with what is given
 *   to the parameters, and returns what it returns with `-> return`.
 *
 * Writing the arrow as `-(path-type)->` gives the kind of the path its taints take (`arg` otherwise). A key of a store
 * is a parameter (`$key`), `*` for any key, or `$keys[*]` for each key in a list; a store may be nested
 * (`Store::$data[$ns][$key]`), but only its first key tells its values apart.
 *
 * @internal
 * @psalm-immutable
 */
final class TaintFlowAnnotation
{
    use ImmutableNonCloneableTrait;

    public const RETURN = 'return';
    public const STORE_WRITE = 'store-write';
    public const STORE_READ = 'store-read';
    public const PROXY = 'proxy';

    private const STORE_REGEX = '/^([A-Za-z_\\\\][\w\\\\]*)::\$(\w+)\[(?:\*|\$(\w+)(\[\*\])?)\]((?:\[[^\]]*\])*)$/';

    private const PATH_TYPE_REGEX = '/-\(([a-z\-]+)\)->/';

    /**
     * @param self::RETURN|self::STORE_WRITE|self::STORE_READ|self::PROXY $kind
     * @param list<non-empty-string> $sources the parameters the data comes from: `this` for the object the call is
     *                                        on, `name()` for what the callable given to a parameter returns
     * @param ?non-empty-string $store_class the class of the store, as written (STORE_WRITE and STORE_READ)
     * @param ?non-empty-string $store_property the property of the class naming the store
     * @param ?non-empty-string $store_key the parameter the key of the store is given to, null for any key
     * @param list<string> $other_key_params the parameters the other keys of a nested store name, which don't tell
     *                                       its values apart
     * @param ?non-empty-string $proxy_function the function-like a PROXY flow calls
     * @psalm-mutation-free
     */
    private function __construct(
        public readonly string $kind,
        public readonly string $path_type,
        public readonly array $sources = [],
        public readonly ?string $store_class = null,
        public readonly ?string $store_property = null,
        public readonly ?string $store_key = null,
        public readonly bool $store_key_is_list = false,
        public readonly array $other_key_params = [],
        public readonly ?string $proxy_function = null,
        public readonly bool $proxy_returns = false,
    ) {
    }

    /**
     * $flow parsed, or what is wrong with it
     *
     * @return self|non-empty-string
     * @psalm-pure
     */
    public static function parse(string $annotation): self|string
    {
        $flow = trim($annotation);

        $path_type = 'arg';
        if (preg_match(self::PATH_TYPE_REGEX, $flow, $matches) === 1 && isset($matches[1])) {
            $path_type = $matches[1];
            $flow = (string) preg_replace(self::PATH_TYPE_REGEX, '->', $flow);
        }

        $sides = array_map('trim', explode('->', $flow));

        if (str_starts_with($flow, 'proxy')) {
            if (count($sides) > 2
                || (isset($sides[1]) && $sides[1] !== 'return')
                || preg_match('/^proxy\s+([^\s(]+)\s*(\(.*\))$/', $sides[0], $matches) !== 1
                || !isset($matches[1], $matches[2])
                || $matches[1] === ''
            ) {
                return self::getMalformedMessage($annotation);
            }

            $sources = self::parseSources($matches[2]);
            if ($sources === null) {
                return self::getMalformedMessage($annotation);
            }

            return new self(
                self::PROXY,
                $path_type,
                $sources,
                proxy_function: $matches[1],
                proxy_returns: isset($sides[1]),
            );
        }

        if (count($sides) !== 2) {
            return self::getMalformedMessage($annotation);
        }

        [$source, $target] = $sides;

        if (str_starts_with($source, '(')) {
            $sources = self::parseSources($source);
            if ($sources === null) {
                return self::getMalformedMessage($annotation);
            }

            if ($target === 'return') {
                return new self(self::RETURN, $path_type, $sources);
            }

            return self::parseStore($target, self::STORE_WRITE, $path_type, $sources)
                ?? self::getMalformedMessage($annotation);
        }

        if ($target === 'return') {
            return self::parseStore($source, self::STORE_READ, $path_type, [])
                ?? self::getMalformedMessage($annotation);
        }

        return self::getMalformedMessage($annotation);
    }

    /**
     * The parameters the flow names: the sources and the keys of the store
     *
     * @return list<string>
     * @psalm-mutation-free
     */
    public function getParamNames(): array
    {
        $names = [];

        foreach ($this->sources as $source) {
            $names[] = str_ends_with($source, '()') ? substr($source, 0, -2) : $source;
        }

        if ($this->store_key !== null) {
            $names[] = $this->store_key;
        }

        return [...$names, ...$this->other_key_params];
    }

    /**
     * The parameter names in `($a, $b())`, or null if it isn't a list of them
     *
     * @return ?list<non-empty-string>
     * @psalm-pure
     */
    private static function parseSources(string $list): ?array
    {
        if (!str_starts_with($list, '(') || !str_ends_with($list, ')')) {
            return null;
        }

        $list = trim(substr($list, 1, -1));
        if ($list === '') {
            return [];
        }

        $sources = [];
        foreach (preg_split('/\s*,\s*/', $list) ?: [] as $source) {
            if (preg_match('/^\$(\w+(?:\(\))?)$/', $source, $matches) !== 1
                || !isset($matches[1])
                || $matches[1] === ''
            ) {
                return null;
            }

            $sources[] = $matches[1];
        }

        return $sources;
    }

    /**
     * @param self::STORE_WRITE|self::STORE_READ $kind
     * @param list<non-empty-string> $sources
     * @psalm-pure
     */
    private static function parseStore(string $store, string $kind, string $path_type, array $sources): ?self
    {
        if (preg_match(self::STORE_REGEX, $store, $matches) !== 1
            || !isset($matches[1], $matches[2], $matches[3])
            || $matches[1] === ''
            || $matches[2] === ''
        ) {
            return null;
        }

        $key = $matches[3];
        if ($key === '') {
            $key = null;
        }

        preg_match_all('/\$(\w+)/', $matches[5] ?? '', $other_key_matches);

        return new self(
            $kind,
            $path_type,
            $sources,
            $matches[1],
            $matches[2],
            $key,
            $key !== null && ($matches[4] ?? '') !== '',
            $other_key_matches[1] ?? [],
        );
    }

    /**
     * @return non-empty-string
     * @psalm-pure
     */
    private static function getMalformedMessage(string $annotation): string
    {
        return '@psalm-flow "' . trim($annotation) . '" is neither "(...) -> return", "(...) -> Store::$data[$key]",'
            . ' "Store::$data[$key] -> return" nor "proxy ..."';
    }
}
