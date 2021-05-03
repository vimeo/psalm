<?php
namespace Psalm\Storage;

use Psalm\CodeLocation;

class EnumCaseStorage
{
    /**
     * @var CodeLocation
     */
    public $location;

    /**
     * @var int|string|null
     */
    public $value;

    /**
     * @var bool
     */
    public $deprecated = false;

    /**
     * @param int|string|null  $value
     */
    public function __construct(
        $value,
        CodeLocation $location
    ) {
        $this->value = $value;
        $this->location = $location;
    }
}
