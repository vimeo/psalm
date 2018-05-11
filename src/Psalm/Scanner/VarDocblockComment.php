<?php
namespace Psalm\Scanner;

use Psalm\Type;

class VarDocblockComment
{
    /**
     * @var Type\Union
     */
    public $type;

    /**
     * @var string
     */
    public $original_type;

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
