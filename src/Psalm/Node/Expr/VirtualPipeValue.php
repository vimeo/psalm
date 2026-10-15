<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use Override;
use PhpParser\Node\Expr;
use Psalm\Node\VirtualNode;
use Psalm\Type\Union;

/**
 * The already-analysed left-hand side of a pipe (`|>`) expression.
 *
 * PHP evaluates the left-hand side before any part of the right-hand side, so when that order matters
 * the value is analysed up front and handed to the piped call through this node instead of re-analysing it.
 *
 * @internal
 */
final class VirtualPipeValue extends Expr implements VirtualNode
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public readonly Union $value_type,
        array $attributes = [],
    ) {
        parent::__construct($attributes);
    }

    #[Override]
    public function getSubNodeNames(): array
    {
        return [];
    }

    #[Override]
    public function getType(): string
    {
        return 'Expr_VirtualPipeValue';
    }
}
