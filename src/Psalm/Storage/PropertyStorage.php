<?php
namespace Psalm\Storage;

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
     * @var Type\Union|false
     */
    public $type;

    /**
     * @var bool
     */
    public $has_default = false;
}
