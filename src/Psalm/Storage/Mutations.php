<?php

declare(strict_types=1);

namespace Psalm\Storage;

/**
 * @psalm-immutable
 */
final class Mutations
{
    /**
     * No mutations allowed (pure)
     *
     * psalm-pure on methods implies this level
     * psalm-pure on functions implies this level
     */
    const LEVEL_NONE = 0;

    /**
     * Reading properties of $this.
     *
     * Only used for methods, ignored for functions.
     *
     * psalm-immutable on classes implies this level (applied to methods)
     * psalm-mutation-free on classes implies this level (applied to methods)
     * psalm-mutation-free on methods implies this level
     * psalm-mutation-free on functions implies this level
     */
    const LEVEL_INTERNAL_READ = 1;

    /**
     * Writing properties of $this or self.
     * Reading properties of $this or self.
     *
     * Writing params passed by reference.
     *
     * Only used for methods, ignored for functions.
     *
     * psalm-external-mutation-free on classes implies this level (applied to methods)
     * psalm-external-mutation-free on methods implies this level
     * psalm-external-mutation-free on functions implies this level
     */
    const LEVEL_INTERNAL_READ_WRITE = 2;

    /**
     * Writing properties of $this or self.
     * Reading properties of $this or self.
     * Reading and writing properties of non-$this objects, echo.
     * Any I/O operations, global state mutations, etc.
     *
     * Default allowance level on classes, methods, and functions.
     */
    const LEVEL_EXTERNAL = 3;

    const LEVEL_ALL = self::LEVEL_EXTERNAL;

    const TO_ATTRIBUTE_CLASS = [
        self::LEVEL_NONE => 'psalm-pure',
        self::LEVEL_INTERNAL_READ => 'psalm-immutable',
        self::LEVEL_INTERNAL_READ_WRITE => 'psalm-external-mutation-free',
        self::LEVEL_EXTERNAL => 'psalm-mutable',
    ];
    const TO_ATTRIBUTE_FUNCTIONLIKE = [
        self::LEVEL_NONE => 'psalm-pure',
        self::LEVEL_INTERNAL_READ => 'psalm-mutation-free',
        self::LEVEL_INTERNAL_READ_WRITE => 'psalm-external-mutation-free',
        self::LEVEL_EXTERNAL => 'psalm-impure',
    ];
}
