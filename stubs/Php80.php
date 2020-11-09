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

    /**
     * @param 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27|28|29|30|31|32|33|34|35|36|37|38|39|40|41|42|43|44|45|46|47|48|49|50|51|52|53|54|55|56|57|58|59|60|61|62|63 $flags
     */
    public function __construct(int $flags = self::TARGET_ALL)
    {
    }
}

class ReflectionUnionType extends ReflectionType {
    /**
     * @return non-empty-list<ReflectionType>
     */
    public function getTypes() {}
}

class UnhandledMatchError extends Error {}
