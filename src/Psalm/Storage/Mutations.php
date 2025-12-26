<?php

declare(strict_types=1);

namespace Psalm\Storage;

final class Mutations
{
    /**
     * No mutations allowed (pure)
     *
     * psalm-immutable on classes implies this level
     * psalm-pure on methods implies this level
     * psalm-pure on functions implies this level
     *
     * psalm-mutation-free on classes implies this level
     * psalm-mutation-free on methods implies this level
     * psalm-mutation-free on functions implies this level
     */
    const NONE = 0;
    /**
     * Writing properties of $this or self (or NONE)
     *
     * Only used for methods, ignored for functions.
     *
     * psalm-external-mutation-free on classes implies this level
     * psalm-external-mutation-free on methods implies this level
     * psalm-external-mutation-free on functions implies this level
     */
    const INTERNAL = 1;
    /**
     * Writing properties of non-$this objects, echo, etc (or INTERNAL, or NONE)
     *
     * Default allowance level on classes, methods, and functions.
     */
    const EXTERNAL = 2;

    const PURE = self::NONE;
    const INTERNALLY_MUTATING = self::INTERNAL;
    const IMPURE = self::EXTERNAL;

    const ALL = self::IMPURE;

    const TO_STRING = [
        self::NONE => 'pure',
        self::INTERNAL => 'internally mutating',
        self::EXTERNAL => 'impure',
    ];

    const TO_ATTRIBUTE_CLASS = [
        self::NONE => '@psalm-immutable',
        self::INTERNAL => '@psalm-external-mutation-free',
        self::EXTERNAL => 'no annotation (impure)',
    ];
    const TO_ATTRIBUTE_METHOD = [
        self::NONE => '@psalm-pure',
        self::INTERNAL => '@psalm-external-mutation-free',
        self::EXTERNAL => 'no annotation (impure)',
    ];
    const TO_ATTRIBUTE_FUNCTION = [
        self::NONE => '@psalm-pure',
        self::INTERNAL => 'no annotation (internally mutating)',
        self::EXTERNAL => 'no annotation (impure)',
    ];
}
