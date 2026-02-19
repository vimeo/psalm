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

    /**
     * @psalm-mutation-free
     */
    public function __construct(public ?ParseTree $parent = null)
    {
    }

    /**
     * @psalm-external-mutation-free
     */
    public function __destruct()
    {
        $this->parent = null;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function cleanParents(): void
    {
        foreach ($this->children as $child) {
            $child->cleanParents();
        }

        $this->parent = null;
    }
}
