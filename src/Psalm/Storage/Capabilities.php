<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCapabilities;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTypeAlias;
use Psalm\Type\Union;

use function array_keys;
use function implode;
use function preg_split;
use function strtolower;
use function trim;

/**
 * The capabilities (permissions to perform side effects) a function-like, a class or a
 * closure type may use, as a bitmask.
 *
 * A function-like may only perform an operation, or call another function-like, when its own
 * capabilities include every capability the operation or callee requires. The empty set is
 * "pure": the return value depends only on the arguments.
 *
 * Being sets, capabilities may only be compared with {@see self::NONE} and {@see self::ALL}:
 * test them with {@see self::allows()}, or with {@see self::equals()} for an exact set.
 *
 * @psalm-type CapabilitySet = int-mask<
 *     Capabilities::READ_PROPS,
 *     Capabilities::WRITE_THIS_PROPS,
 *     Capabilities::WRITE_PROPS,
 *     Capabilities::READ_GLOBALS,
 *     Capabilities::WRITE_GLOBALS,
 *     Capabilities::WRITE_REFS,
 *     Capabilities::IO
 * >
 * @psalm-immutable
 * @api
 */
final class Capabilities
{
    /** No side effects at all: the classic pure function. */
    public const NONE = 0;

    /**
     * Reading instance properties of mutable objects (including `$this`).
     * Reading the properties of `@psalm-immutable` objects never needs a capability.
     */
    public const READ_PROPS = 1 << 0;

    /** Writing or unsetting properties of `$this`. */
    public const WRITE_THIS_PROPS = 1 << 1;

    /** Writing or unsetting properties of objects other than `$this`. */
    public const WRITE_PROPS = 1 << 2;

    /** Reading static properties, superglobals and variables imported with `global`. */
    public const READ_GLOBALS = 1 << 3;

    /** Writing static properties, superglobals, `global` variables, and using `static` variables. */
    public const WRITE_GLOBALS = 1 << 4;

    /** Writing through by-reference parameters. Writing a variable captured by reference from an outer scope is impure. */
    public const WRITE_REFS = 1 << 5;

    /** Input/output and other side effects: `echo`, `print`, `exit` with a message, impure builtins. */
    public const IO = 1 << 6;

    /** Every capability: the default for unannotated code. */
    public const ALL = (1 << 7) - 1;

    /** What `@psalm-mutation-free` allows. */
    public const MUTATION_FREE = self::READ_PROPS;

    /** What `@psalm-external-mutation-free` allows. */
    public const EXTERNAL_MUTATION_FREE = self::READ_PROPS | self::WRITE_THIS_PROPS | self::WRITE_REFS;

    /**
     * What the methods of a class with `@psalm-taint-specialize` may do. Each instance of such a class
     * holds its own taints, which only follow the variables the instance is assigned to: the instance
     * may not change once constructed, as the change would not reach the other variables holding it,
     * and its methods may not write other objects or global state, from which another call could read
     * back what this one wrote, as only this call's taints come out of it.
     */
    public const TAINT_SPECIALIZED = self::READ_PROPS | self::READ_GLOBALS | self::WRITE_REFS | self::IO;

    /**
     * The capabilities a method call still requires from its caller when the receiver's own state
     * may be mutated freely (the receiver is freshly created or otherwise pure-compatible):
     * everything except what only concerns the receiver.
     */
    public const RECEIVER_LOCAL = self::READ_PROPS | self::WRITE_THIS_PROPS | self::WRITE_REFS;

    /**
     * The names usable in `@psalm-capabilities`, in `Closure[...]`/`callable[...]` types and as
     * class template arguments, with the capability set each one denotes. Each capability name
     * denotes that capability alone: `write-props` does not allow reading properties, which
     * needs `read-props` as well.
     *
     * @var array<non-empty-string, int>
     */
    public const NAMES = [
        'pure' => self::NONE,
        'read-props' => self::READ_PROPS,
        'write-this-props' => self::WRITE_THIS_PROPS,
        'write-props' => self::WRITE_PROPS,
        'read-globals' => self::READ_GLOBALS,
        'write-globals' => self::WRITE_GLOBALS,
        'write-refs' => self::WRITE_REFS,
        'io' => self::IO,
        'impure' => self::ALL,
    ];

    /**
     * The name of each single capability, in the order they are printed.
     *
     * @var array<int, non-empty-string>
     */
    private const BIT_NAMES = [
        self::READ_PROPS => 'read-props',
        self::WRITE_THIS_PROPS => 'write-this-props',
        self::WRITE_PROPS => 'write-props',
        self::READ_GLOBALS => 'read-globals',
        self::WRITE_GLOBALS => 'write-globals',
        self::WRITE_REFS => 'write-refs',
        self::IO => 'io',
    ];

    /**
     * The smallest of the named purity levels (pure, mutation-free, external-mutation-free,
     * impure) that allows a capability set: the granularity at which annotations are suggested.
     *
     * @param CapabilitySet $capabilities
     * @return CapabilitySet
     * @psalm-pure
     */
    public static function toNamedLevel(int $capabilities): int
    {
        foreach ([self::NONE, self::MUTATION_FREE, self::EXTERNAL_MUTATION_FREE] as $level) {
            if (self::allows($level, $capabilities)) {
                return $level;
            }
        }

        return self::ALL;
    }

    /**
     * Whether a context with $available capabilities may perform an operation
     * that requires $required.
     *
     * @param CapabilitySet $available
     * @param CapabilitySet $required
     * @psalm-pure
     */
    public static function allows(int $available, int $required): bool
    {
        return ($required & ~$available) === 0;
    }

    /**
     * Whether two capability sets are exactly the same set, e.g. to tell whether a fixpoint was
     * reached; use {@see self::allows()} to test whether a set is enough for another.
     *
     * The parameters are plain ints: comparing sets by value is what this function is for.
     *
     * @psalm-pure
     */
    public static function equals(int $capabilities, int $other_capabilities): bool
    {
        return $capabilities === $other_capabilities;
    }

    /**
     * Parses the value of a `@psalm-capabilities` tag, e.g. `write-props, io` or `read-globals|io`.
     *
     * @return CapabilitySet
     * @throws CapabilitiesParseException
     * @psalm-pure
     */
    public static function fromList(string $list): int
    {
        $capabilities = self::NONE;

        foreach (preg_split('/[\s,|]+/', trim($list)) ?: [] as $name) {
            if ($name === '') {
                continue;
            }

            $capabilities |= self::fromName(strtolower($name));
        }

        return $capabilities;
    }

    /**
     * @return CapabilitySet
     * @throws CapabilitiesParseException
     * @psalm-pure
     */
    private static function fromName(string $name): int
    {
        if (!isset(self::NAMES[$name])) {
            throw new CapabilitiesParseException(
                'Unknown capability ' . $name . ', expected one of ' . implode(', ', array_keys(self::NAMES)),
            );
        }

        return self::NAMES[$name];
    }

    /**
     * The canonical name of a capability set, e.g. `pure`, `impure` or `read-props|write-props|io`;
     * with $besides, of what the set has beyond those capabilities (`write-props|io` for
     * `read-props|write-props|io` beyond `read-props`).
     *
     * @param CapabilitySet $capabilities
     * @param CapabilitySet $besides
     * @psalm-pure
     */
    public static function toString(int $capabilities, int $besides = self::NONE): string
    {
        if ($besides === self::NONE) {
            if ($capabilities === self::NONE) {
                return 'pure';
            }

            if ($capabilities === self::ALL) {
                return 'impure';
            }
        }

        $names = [];

        foreach (self::BIT_NAMES as $bit => $name) {
            if (($capabilities & $bit) !== 0 && ($besides & $bit) === 0) {
                $names[] = $name;
            }
        }

        return implode('|', $names);
    }

    /**
     * The docblock annotation describing a capability set, without the leading `@`:
     * `@psalm-mutation-free` and `@psalm-external-mutation-free` are only accepted.
     *
     * @param CapabilitySet $capabilities
     * @return non-empty-string
     * @psalm-pure
     */
    public static function toFunctionAnnotation(int $capabilities): string
    {
        return match ($capabilities) {
            self::NONE => 'psalm-pure',
            self::ALL => 'psalm-impure',
            default => 'psalm-capabilities ' . self::toString($capabilities),
        };
    }

    /**
     * The docblock annotation describing a capability set on a class, without the leading `@`.
     *
     * @param CapabilitySet $capabilities
     * @return non-empty-string
     * @psalm-pure
     */
    public static function toClassAnnotation(int $capabilities): string
    {
        // exactly read-props, which also makes the properties readonly
        if (self::equals($capabilities, self::MUTATION_FREE)) {
            return 'psalm-immutable';
        }

        return match ($capabilities) {
            self::NONE => 'psalm-pure',
            self::ALL => 'psalm-mutable',
            default => 'psalm-capabilities ' . self::toString($capabilities),
        };
    }

    /**
     * The capabilities denoted by a purity type: the purity slot of a closure type, the argument
     * of a purity template, or a purity template bound. Unbound purity templates count as their
     * upper bound; anything that is not a purity type counts as impure.
     *
     * @return CapabilitySet
     * @psalm-pure
     */
    public static function fromType(Union $type): int
    {
        $capabilities = self::NONE;

        foreach ($type->getAtomicTypes() as $atomic) {
            if ($atomic instanceof TCapabilities) {
                $capabilities |= $atomic->capabilities;
            } elseif ($atomic instanceof TTemplateParam) {
                $capabilities |= self::fromType($atomic->as);
            } elseif ($atomic instanceof TClosure || $atomic instanceof TCallable) {
                // a type template bound to a closure type carries the closure's purity
                $capabilities |= self::fromType($atomic->purity);
            } else {
                // including a type alias that has not been expanded yet
                return self::ALL;
            }
        }

        return $capabilities;
    }

    /**
     * Whether a type only denotes capabilities (directly, or through purity templates), i.e. can
     * be used where a purity is expected.
     *
     * @psalm-pure
     */
    public static function isPurityType(Union $type): bool
    {
        foreach ($type->getAtomicTypes() as $atomic) {
            if ($atomic instanceof TCapabilities) {
                continue;
            }

            // an imported type alias (`@psalm-import-type`), resolved when the type is expanded
            if ($atomic instanceof TTypeAlias) {
                continue;
            }

            if ($atomic instanceof TTemplateParam && self::isPurityType($atomic->as)) {
                continue;
            }

            // `[$param is array ? pure : P]`, resolved per call like a conditional return type
            if ($atomic instanceof TConditional
                && self::isPurityType($atomic->if_type)
                && self::isPurityType($atomic->else_type)
            ) {
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * Whether a type is a purity argument: capability sets and purity templates only, no type alias.
     * Purity arguments are the trailing type parameters of a generic object, and are written apart
     * from the others (`Traversable[pure]<int, string>`).
     *
     * @psalm-pure
     */
    public static function isPurityArgument(Union $type): bool
    {
        foreach ($type->getAtomicTypes() as $atomic) {
            if ($atomic instanceof TCapabilities) {
                continue;
            }

            if ($atomic instanceof TTemplateParam && self::isPurityArgument($atomic->as)) {
                continue;
            }

            if ($atomic instanceof TConditional
                && self::isPurityArgument($atomic->if_type)
                && self::isPurityArgument($atomic->else_type)
            ) {
                continue;
            }

            return false;
        }

        return true;
    }
}
