<?php
namespace Psalm\Storage;


class MethodStorage extends FunctionLikeStorage
{
    /**
     * @var string
     */
    public $fq_class_name;

    /**
     * @var bool
     */
    public $is_static;

    /**
     * @var bool
     */
    public $reflected;

    /**
     * @var bool
     */
    public $registered;

    /**
     * @var int
     */
    public $visibility;
}
