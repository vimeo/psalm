<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

/**
 * @internal
 */
class ParseTree
{
    /**
     * @var list<ParseTree>
     */
    public array $children = [];

    public bool $possibly_undefined = false;

    public function __construct(public ?ParseTree $parent = null)
    {
    }

    public function __destruct()
    {
        $this->parent = null;
    }

    public function cleanParents(): void
    {
        foreach ($this->children as $child) {
            $child->cleanParents();
        }

        $this->parent = null;
    }
}
