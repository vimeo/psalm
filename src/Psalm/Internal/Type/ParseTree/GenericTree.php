<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\ParseTree;

use Override;
use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
final class GenericTree extends ParseTree
{
    public bool $terminated = false;

    /**
     * The purity arguments given in brackets before the type parameters, if any
     * (`Traversable[pure]<int, string>`).
     */
    public ?PurityTree $purity = null;

    /**
     * @psalm-mutation-free
     */
    public function __construct(public string $value, ?ParseTree $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    #[Override]
    public function cleanParents(): void
    {
        $this->purity?->cleanParents();

        parent::cleanParents();
    }
}
