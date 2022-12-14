<?php

namespace Psalm\Internal\Analyzer;

/**
 * @internal
 */
class ClassLikeNameOptions
{
    public bool $inferred;

    public bool $allow_trait;

    public bool $allow_interface;

    public bool $allow_enum;

    public bool $from_docblock;

    public bool $from_attribute;

    public function __construct(
        bool $inferred = false,
        bool $allow_trait = false,
        bool $allow_interface = true,
        bool $allow_enum = true,
        bool $from_docblock = false,
        bool $from_attribute = false
    ) {
        $this->inferred = $inferred;
        $this->allow_trait = $allow_trait;
        $this->allow_interface = $allow_interface;
        $this->allow_enum = $allow_enum;
        $this->from_docblock = $from_docblock;
        $this->from_attribute = $from_attribute;
    }
}
