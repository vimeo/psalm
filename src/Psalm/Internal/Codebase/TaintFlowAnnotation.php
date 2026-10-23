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
use function str_replace;
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
 *   to the parameters, and returns what it returns with `-> return`;
 * - `($value) -> $this->$name` (PROPERTY_WRITE): a call of a method sets the property of the object it is called on
 *   named by what is given to a parameter, `$this->*` any of them (see ReceiverPropertyTaints);
 * - `$this->$name -> return` (PROPERTY_READ): a call of a method returns that property;
 * - `($values) -> $this->{key}` (PROPERTY_WRITE by key): a call sets each property to what is under its name in the
 *   arrays given to the parameters;
 * - `$this->{key} -> return[key]` (PROPERTY_READ by key): a call returns an array of the properties under their names;
 * - `($value) -> $this->data`, `($value) -> $this->data[$key]`, `($value) -> $this->data[*]` (PROPERTY_WRITE of a named
 *   property): a call sets the property `data` of the object it is called on, or the element of that array property
 *   under the key given to a parameter, or under any key;
 * - `$this->data -> return`, `$this->data[$key] -> return`, `$this->data[*] -> return` (PROPERTY_READ of a named
 *   property): a call returns that property, or its element under the key given to a parameter, or any of them.
 *
 * Writing the arrow as `-(path-type)->` gives the kind of the path its taints take (`arg` otherwise), except for the
 * flows by key and those of elements, whose paths are those of the keys. A key of a store
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
    public const PROPERTY_WRITE = 'property-write';
    public const PROPERTY_READ = 'property-read';

    private const STORE_REGEX = '/^([A-Za-z_\\\\][\w\\\\]*)::\$(\w+)\[(?:\*|\$(\w+)(\[\*\])?)\]((?:\[[^\]]*\])*)$/';

    private const PROPERTY_REGEX = '/^\$this->(?:\*|\$(\w+)|(\{key\})|([A-Za-z_]\w*)(?:\[(?:(\*)|\$(\w+))\])?)$/';

    private const PATH_TYPE_REGEX = '/-\(([a-z\-]+)\)->/';

    /**
     * @param self::RETURN|self::STORE_WRITE|self::STORE_READ|self::PROXY|self::PROPERTY_WRITE|self::PROPERTY_READ $kind
     * @param list<non-empty-string> $sources the parameters the data comes from: `this` for the object the call is
     *                                        on, `name()` for what the callable given to a parameter returns
     * @param ?non-empty-string $store_class the class of the store, as written (STORE_WRITE and STORE_READ)
     * @param ?non-empty-string $store_property the property of the class naming the store
     * @param ?non-empty-string $store_key the parameter the key of the store is given to, null for any key; for
     *                                     PROPERTY_WRITE and PROPERTY_READ, the one the name of the property is
     *                                     given to, null for any property
     * @param list<string> $other_key_params the parameters the other keys of a nested store name, which don't tell
     *                                       its values apart
     * @param ?non-empty-string $proxy_function the function-like a PROXY flow calls
     * @param bool $property_by_key whether a PROPERTY_WRITE sets the properties named by the keys of the arrays given
     *                              to the parameters, or a PROPERTY_READ returns the properties under their names
     * @param ?non-empty-string $property the property a PROPERTY_WRITE or a PROPERTY_READ names (`$this->data`)
     * @param ?non-empty-string $element_key the parameter the key of the element of $property is given to
     *                                       (`$this->data[$key]`)
     * @param bool $any_element whether the flow is of any element of $property (`$this->data[*]`)
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
        public readonly bool $property_by_key = false,
        public readonly ?string $property = null,
        public readonly ?string $element_key = null,
        public readonly bool $any_element = false,
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
        $has_path_type = false;
        if (preg_match(self::PATH_TYPE_REGEX, $flow, $matches) === 1 && isset($matches[1])) {
            $path_type = $matches[1];
            $has_path_type = true;
            $flow = (string) preg_replace(self::PATH_TYPE_REGEX, '->', $flow);
        }

        // the arrow of `$this->$name` is not one between the sides
        $sides = array_map(
            static fn(string $side): string => str_replace('$this>', '$this->', trim($side)),
            explode('->', str_replace('$this->', '$this>', $flow)),
        );

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

            if (str_starts_with($target, '$this->')) {
                return self::parseProperty($target, self::PROPERTY_WRITE, $path_type, $has_path_type, $sources)
                    ?? self::getMalformedMessage($annotation);
            }

            return self::parseStore($target, self::STORE_WRITE, $path_type, $sources)
                ?? self::getMalformedMessage($annotation);
        }

        if (($target === 'return' || $target === 'return[key]') && str_starts_with($source, '$this->')) {
            return self::parseProperty(
                $source,
                self::PROPERTY_READ,
                $path_type,
                $has_path_type,
                [],
                $target === 'return[key]',
            ) ?? self::getMalformedMessage($annotation);
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

        if ($this->element_key !== null) {
            $names[] = $this->element_key;
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
     * @param self::PROPERTY_WRITE|self::PROPERTY_READ $kind
     * @param list<non-empty-string> $sources
     * @param bool $returns_by_key whether a PROPERTY_READ returns `return[key]`
     * @psalm-pure
     */
    private static function parseProperty(
        string $property,
        string $kind,
        string $path_type,
        bool $has_path_type,
        array $sources,
        bool $returns_by_key = false,
    ): ?self {
        if (preg_match(self::PROPERTY_REGEX, $property, $matches) !== 1) {
            return null;
        }

        // `$this->{key}` is read into `return[key]`, through the paths of the keys, as the elements of a property are
        $by_key = ($matches[2] ?? '') !== '';
        $property_name = $matches[3] ?? '';
        $any_element = ($matches[4] ?? '') !== '';
        $element_key = $matches[5] ?? '';
        $of_element = $any_element || $element_key !== '';
        if (($kind === self::PROPERTY_READ && $by_key !== $returns_by_key)
            || (($by_key || $of_element) && $has_path_type)
        ) {
            return null;
        }

        // what the property is set to comes from the arguments
        foreach ($sources as $source) {
            if ($source === 'this' || str_ends_with($source, '()')) {
                return null;
            }
        }

        $name = $matches[1] ?? '';

        return new self(
            $kind,
            $path_type,
            $sources,
            store_key: $name === '' ? null : $name,
            property_by_key: $by_key,
            property: $property_name === '' ? null : $property_name,
            element_key: $element_key === '' ? null : $element_key,
            any_element: $any_element,
        );
    }

    /**
     * Whether the flow sets or returns a property of `$this`, which only a method that isn't static has
     *
     * @psalm-mutation-free
     */
    public function isOfProperty(): bool
    {
        return $this->kind === self::PROPERTY_WRITE || $this->kind === self::PROPERTY_READ;
    }

    /**
     * @return non-empty-string
     * @psalm-pure
     */
    private static function getMalformedMessage(string $annotation): string
    {
        return '@psalm-flow "' . trim($annotation) . '" is neither "(...) -> return", "(...) -> Store::$data[$key]",'
            . ' "Store::$data[$key] -> return", "(...) -> $this->$name", "$this->$name -> return",'
            . ' "(...) -> $this->{key}", "$this->{key} -> return[key]", "(...) -> $this->data[$key]",'
            . ' "$this->data[$key] -> return" nor "proxy ..."';
    }
}
