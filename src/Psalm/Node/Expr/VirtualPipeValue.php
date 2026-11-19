<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use Override;
use PhpParser\Node\Expr;
use Psalm\Node\VirtualNode;
use Psalm\Type\Union;

/**
 * The left-hand side of a pipe (`|>`), analysed before the right-hand side as PHP evaluates it, passed on as the
 * argument of the call the pipe makes. It only exists for the analysis: php-parser's pretty printer can't print it.
 *
 * @internal
 */
final class VirtualPipeValue extends Expr implements VirtualNode
{
    /**
     * @param array<string, mixed> $attributes
     * @psalm-capabilities read-props
     */
    public function __construct(
        public readonly Union $value_type,
        array $attributes = [],
    ) {
        parent::__construct($attributes);
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function getSubNodeNames(): array
    {
        return [];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function getType(): string
    {
        return 'Expr_VirtualPipeValue';
    }
}
