<?php

namespace CodeInspector\Type;

class ParseTree
{
    const GENERIC = '<>';
    const UNION = '|';

    /** @var array<ParseTree> */
    public $children;

    /** @var string */
    public $value;

    /** @var null|ParseTree */
    public $parent;

    /**
     * @param string         $value
     * @param ParseTree|null $parent
     */
    public function __construct($value, ParseTree $parent = null)
    {
        $this->value = $value;
        $this->parent = $parent;
        $this->children = [];
    }
}
