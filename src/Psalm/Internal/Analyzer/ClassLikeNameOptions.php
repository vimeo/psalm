<?php

namespace Psalm\Internal\Analyzer;

/**
 * @internal
 */
class ClassLikeNameOptions
{
    /** @var bool */
    public bool $inferred;

    /** @var bool */
    public bool $allow_trait;

    /** @var bool */
    public bool $allow_interface;

    /** @var bool */
    public bool $allow_enum;

    /** @var bool */
    public bool $from_docblock;

    /** @var bool */
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
