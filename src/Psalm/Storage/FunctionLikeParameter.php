<?php
namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Type;

class FunctionLikeParameter
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var bool
     */
    public $by_ref;

    /**
     * @var Type\Union|null
     */
    public $type;

    /**
     * @var Type\Union|null
     */
    public $signature_type;

    /**
     * @var bool
     */
    public $has_docblock_type = false;

    /**
     * @var bool
     */
    public $is_optional;

    /**
     * @var bool
     */
    public $is_nullable;

    /**
     * @var Type\Union|null
     */
    public $default_type;

    /**
     * @var CodeLocation|null
     */
    public $location;

    /**
     * @var CodeLocation|null
     */
    public $type_location;

    /**
     * @var CodeLocation|null
     */
    public $signature_type_location;

    /**
     * @var bool
     */
    public $is_variadic;

    /**
     * @param string        $name
     * @param bool       $by_ref
     * @param Type\Union|null    $type
     * @param CodeLocation|null  $location
     * @param bool       $is_optional
     * @param bool       $is_nullable
     * @param bool       $is_variadic
     * @param Type\Union|null    $default_type
     */
    public function __construct(
        $name,
        $by_ref,
        Type\Union $type = null,
        CodeLocation $location = null,
        CodeLocation $type_location = null,
        $is_optional = true,
        $is_nullable = false,
        $is_variadic = false,
        $default_type = null
    ) {
        $this->name = $name;
        $this->by_ref = $by_ref;
        $this->type = $type;
        $this->signature_type = $type;
        $this->is_optional = $is_optional;
        $this->is_nullable = $is_nullable;
        $this->is_variadic = $is_variadic;
        $this->location = $location;
        $this->type_location = $type_location;
        $this->signature_type_location = $type_location;
        $this->default_type = $default_type;
    }

    public function __toString()
    {
        return ($this->type ?: 'mixed')
            . ($this->is_variadic ? '...' : '')
            . ($this->is_optional ? '=' : '');
    }

    public function __clone()
    {
        if ($this->type) {
            $this->type = clone $this->type;
        }
    }
}
