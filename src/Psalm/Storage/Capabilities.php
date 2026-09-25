<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCapabilities;
use Psalm\Type\Atomic\TClosure;
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
     * The capabilities a method call still requires from its caller when the receiver's own state
     * may be mutated freely (the receiver is freshly created or otherwise pure-compatible):
     * everything except what only concerns the receiver.
     */
    public const RECEIVER_LOCAL = self::READ_PROPS | self::WRITE_THIS_PROPS | self::WRITE_REFS;

    /**
     * The names usable in `@psalm-capabilities`, in `Closure<...>`/`callable<...>` types and as
     * class template arguments, with the capability set each one denotes.
     *
     * @var array<non-empty-string, int>
     */
    public const NAMES = [
        'pure' => self::NONE,
        'read-props' => self::READ_PROPS,
        'write-this-props' => self::READ_PROPS | self::WRITE_THIS_PROPS,
        'write-props' => self::READ_PROPS | self::WRITE_THIS_PROPS | self::WRITE_PROPS,
        'read-globals' => self::READ_GLOBALS,
        'write-globals' => self::READ_GLOBALS | self::WRITE_GLOBALS,
        'write-refs' => self::WRITE_REFS,
        'io' => self::IO,
        'impure' => self::ALL,
    ];

    /**
     * The name describing each single capability, for messages.
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
     * @psalm-pure
     */
    public static function allows(int $available, int $required): bool
    {
        return ($required & ~$available) === 0;
    }

    /**
     * Parses the value of a `@psalm-capabilities` tag, e.g. `write-props, io` or `read-globals|io`.
     *
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

            $name = strtolower($name);

            if (!isset(self::NAMES[$name])) {
                throw new CapabilitiesParseException(
                    'Unknown capability ' . $name . ', expected one of ' . implode(', ', array_keys(self::NAMES)),
                );
            }

            $capabilities |= self::NAMES[$name];
        }

        return $capabilities;
    }

    /**
     * The canonical name of a capability set, e.g. `pure`, `impure` or `write-props|io`; with
     * $besides, of what the set has beyond those capabilities (`write-props|io` beyond `read-props`).
     *
     * @psalm-pure
     */
    public static function toString(int $capabilities, int $besides = self::NONE): string
    {
        // a set may lack the bits its widest capability implies (write-props without read-props,
        // as in the type of a closure): named by the capabilities it has, which imply those
        $capabilities = self::withImplied($capabilities);
        $besides = self::withImplied($besides);

        if ($besides === self::NONE) {
            if ($capabilities === self::NONE) {
                return 'pure';
            }

            if ($capabilities === self::ALL) {
                return 'impure';
            }
        }

        $names = [];
        $covered = self::NONE;

        // the widest names first, so that e.g. write-props is not spelt out as three names
        $ordered_names = [
            'write-props',
            'write-this-props',
            'write-globals',
            'read-globals',
            'write-refs',
            'io',
        ];

        foreach ($ordered_names as $name) {
            $bits = self::NAMES[$name];

            if (($bits & ~$capabilities) === 0
                && ($bits & ~$covered) !== 0
                && ($bits & ~$besides) !== 0
            ) {
                $names[] = $name;
                $covered |= $bits;
            }
        }

        // bits no name covers on its own (read-props)
        foreach (self::BIT_NAMES as $bit => $name) {
            if (($capabilities & $bit) !== 0 && (($covered | $besides) & $bit) === 0) {
                $names[] = $name;
                $covered |= $bit;
            }
        }

        return implode('|', $names);
    }

    /**
     * A capability set with the capabilities its capabilities imply: writing properties implies
     * writing those of `$this`, which implies reading properties; writing globals implies
     * reading them.
     *
     * @psalm-pure
     */
    private static function withImplied(int $capabilities): int
    {
        if (($capabilities & self::WRITE_PROPS) !== 0) {
            $capabilities |= self::WRITE_THIS_PROPS;
        }

        if (($capabilities & self::WRITE_THIS_PROPS) !== 0) {
            $capabilities |= self::READ_PROPS;
        }

        if (($capabilities & self::WRITE_GLOBALS) !== 0) {
            $capabilities |= self::READ_GLOBALS;
        }

        return $capabilities;
    }

    /**
     * The docblock annotation describing a capability set, without the leading `@`:
     * `@psalm-mutation-free` and `@psalm-external-mutation-free` are only accepted.
     *
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
     * @return non-empty-string
     * @psalm-pure
     */
    public static function toClassAnnotation(int $capabilities): string
    {
        return match ($capabilities) {
            self::NONE => 'psalm-pure',
            // also makes the properties readonly
            self::MUTATION_FREE => 'psalm-immutable',
            self::ALL => 'psalm-mutable',
            default => 'psalm-capabilities ' . self::toString($capabilities),
        };
    }

    /**
     * The capabilities denoted by a purity type: the purity slot of a closure type, the argument
     * of a purity template, or a purity template bound. Unbound purity templates count as their
     * upper bound; anything that is not a purity type counts as impure.
     *
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
                $capabilities |= self::ALL;
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

            return false;
        }

        return true;
    }
}
