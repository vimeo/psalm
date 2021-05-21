<?php
namespace Psalm\Storage;

class EnumCaseStorage
{
    /**
     * @var int|string|null
     */
    public $value;

    /**
     * @param int|string|null  $value
     */
    public function __construct(
        $value
    ) {
        $this->value = $value;
    }
}
