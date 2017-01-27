<?php
namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Type;

class PropertyStorage
{
    /**
     * @var bool
     */
    public $is_static;

    /**
     * @var int
     */
    public $visibility;

    /**
     * @var CodeLocation|null
     */
    public $location;

    /**
     * @var Type\Union|false
     */
    public $type;

    /**
     * @var bool
     */
    public $has_default = false;
}
