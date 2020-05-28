<?php
namespace Psalm\Internal\Type;

use function array_pop;
use function count;
use function in_array;
use function preg_match;
use Psalm\Exception\TypeParseTreeException;
use function strlen;
use function strtolower;

/**
 * @internal
 */
class ParseTree
{
    /**
     * @var list<ParseTree>
     */
    public $children = [];

    /**
     * @var null|ParseTree
     */
    public $parent;

    /**
     * @var bool
     */
    public $possibly_undefined = false;

    /**
     * @param ParseTree|null $parent
     */
    public function __construct(ParseTree $parent = null)
    {
        $this->parent = $parent;
    }

    public function __destruct()
    {
        $this->parent = null;
    }

    public function cleanParents() : void
    {
        foreach ($this->children as $child) {
            $child->cleanParents();
        }

        $this->parent = null;
    }
}
