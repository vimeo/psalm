<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
final class Value extends ParseTree
{
    public ?string $text = null;

    public function __construct(
        public string $value,
        public int $offset_start,
        public int $offset_end,
        ?string $text,
        ?ParseTree $parent = null,
    ) {
        $this->parent = $parent;
        $this->text = $text === $value ? null : $text;
    }
}
