<?php
namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Type;
use Psalm\Checker\ClassLikeChecker;

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
     * @var CodeLocation|null
     */
    public $type_location;

    /**
     * @var Type\Union|false
     */
    public $type;

    /**
     * @var Type\Union|null
     */
    public $suggested_type;

    /**
     * @var bool
     */
    public $has_default = false;

    /**
     * @var bool
     */
    public $deprecated = false;

    /**
     * @var array<string, array<int, CodeLocation>>|null
     */
    public $referencing_locations;
}
