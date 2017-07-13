<?php
namespace Psalm;

class VarDocblockComment
{
    /**
     * @var string
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
     *
     * @var bool
     */
    public $deprecated = false;
}
