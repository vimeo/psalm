<?php
namespace Psalm\Storage;

use Psalm\CodeLocation;

class MethodStorage extends FunctionLikeStorage
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
     * @var bool
     */
    public $final;

    /**
     * @var bool
     */
    public $abstract;

    /**
     * @var array<int, CodeLocation>
     */
    public $unused_params = [];

    /**
     * @var array<int, bool>
     */
    public $used_params = [];

    /**
     * @var bool
     */
    public $overridden_downstream = false;

    /**
     * @var bool
     */
    public $overridden_somewhere = false;

    /**
     * @var bool
     */
    public $inheritdoc = false;

    /**
     * @var string
     */
    public $defining_fqcln;
}
