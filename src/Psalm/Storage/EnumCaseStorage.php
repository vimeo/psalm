<?php
namespace Psalm\Storage;

use Psalm\CodeLocation;

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
