<?php
namespace Psalm\Internal\Analyzer;

class ClassLikeNameOptions
{
    /** @var bool */
    public $inferred;

    /** @var bool */
    public $allow_trait;

    /** @var bool */
    public $allow_interface;

    /** @var bool */
    public $from_docblock;

    /** @var bool */
    public $from_attribute;

    public function __construct(
        bool $inferred = false,
        bool $allow_trait = false,
        bool $allow_interface = true,
        bool $from_docblock = false,
        bool $from_attribute = false
    ) {
        $this->inferred = $inferred;
        $this->allow_trait = $allow_trait;
        $this->allow_interface = $allow_interface;
        $this->from_docblock = $from_docblock;
        $this->from_attribute = $from_attribute;
    }
}
