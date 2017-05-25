<?php
namespace Psalm;

class VarDocblockComment
{
    /**
     * @var Type\Union
     */
    public $type;

    /**
     * @var string|null
     */
    public $var_id = null;

    /**
     * @var int|null
     */
    public $line_number;

    /**
     * Whether or not the function is deprecated
     * @var boolean
     */
    public $deprecated = false;
}
