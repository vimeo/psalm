<?php

interface Stringable
{
    /** @return string */
    function __toString();
}

/**
 * @template TClass as object
 */
class ReflectionAttribute
{
    const IS_INSTANCEOF = 2;

    private function __construct()
    {
    }

    public function getName() : string
    {
    }

    public function getTarget() : int
    {
    }

    public function isRepeated() : bool
    {
    }

    public function getArguments() : array
    {
    }

    /**
     * @return TClass
     */
    public function newInstance() : object
    {
    }

    /**
     * @return never-return
     */
    private function __clone()
    {
    }
}

class Attribute
{
    public const TARGET_CLASS = 1;
    public const TARGET_FUNCTION = 2;
    public const TARGET_METHOD = 4;
    public const TARGET_PROPERTY = 8;
    public const TARGET_CLASS_CONSTANT = 16;
    public const TARGET_PARAMETER = 32;
    public const TARGET_ALL = 63;
    public const IS_REPEATABLE = 64;

    /**
     * @param int-mask-of<self::*> $flags
     */
    public function __construct(int $flags = self::TARGET_ALL)
    {
    }
}

class ReflectionUnionType extends ReflectionType {
    /**
     * @return non-empty-list<ReflectionNamedType>
     */
    public function getTypes() {}
}

class UnhandledMatchError extends Error {}
