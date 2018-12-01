<?php
namespace Psalm\Internal\Scanner;

use Psalm\Type;

/**
 * @internal
 */
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
     * Whether or not the property is deprecated
     *
     * @var bool
     */
    public $deprecated = false;

    /**
     * Whether or not the property is internal
     *
     * @var bool
     */
    public $internal = false;
}
