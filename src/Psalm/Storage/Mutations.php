<?php

namespace Psalm\Storage;

use InvalidArgumentException;

final class Mutations
{
    /** 
     * No mutations allowed (pure)
     */
    const NONE = 0;
    /**
     * Writing properties of $this or self (or NONE)
     */
    const INTERNAL = 1;
    /**
     * Writing properties of non-$this objects, (or INTERNAL, or NONE)
     */
    const EXTERNAL = 2;
    /**
     * I/O access, etc (or EXTERNAL, or INTERNAL, or NONE)
     */
    const EXTERNAL_OTHER = 3;

    const PURE = self::NONE;
    const INTERNALLY_MUTATING = self::INTERNAL;
    const EXTERNALLY_MUTATING = self::EXTERNAL;
    const IMPURE = self::EXTERNAL_OTHER;

    const ALL = self::IMPURE;

    const TO_STRING = [
        self::NONE => 'pure',
        self::INTERNAL => 'internally mutating',
        self::EXTERNAL => 'externally mutating',
        self::EXTERNAL_OTHER => 'impure',
    ];
}