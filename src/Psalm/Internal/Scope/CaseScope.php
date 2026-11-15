<?php

declare(strict_types=1);

namespace Psalm\Internal\Scope;

use Psalm\Context;
use Psalm\Type\Union;

use function array_intersect_key;

/**
 * @internal
 */
final class CaseScope
{
    /**
     * @var array<string, Union>|null
     */
    public ?array $break_vars = null;

    /**
     * The offsets of nullable arrays read on every `break` out of the switch in the case, null before there is one
     *
     * @var array<string, true>|null
     */
    public ?array $null_array_offsets_read_at_breaks = null;

    /**
     * @psalm-external-mutation-free
     */
    public function addBreak(Context $context): void
    {
        $this->null_array_offsets_read_at_breaks = $this->null_array_offsets_read_at_breaks === null
            ? $context->null_array_offsets_read
            : array_intersect_key($this->null_array_offsets_read_at_breaks, $context->null_array_offsets_read);
    }

    /**
     * @psalm-mutation-free
     */
    public function __construct(public Context $parent_context)
    {
    }

    /**
     * @psalm-external-mutation-free
     */
    public function __destruct()
    {
        unset($this->parent_context);
    }
}
