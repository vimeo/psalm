<?php

declare(strict_types=1);

namespace Psalm\Internal\Scope;

use Psalm\Context;
use Psalm\Type\Union;

use function array_intersect_key;
use function max;

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
     * @var array<string, bool>
     */
    public array $vars_possibly_in_scope_at_breaks = [];

    /**
     * The latest stamps (see Context::$parent_remove_vars) of the variables changed before a `break` out of the switch
     *
     * @var array<string, int>
     */
    public array $parent_remove_vars_at_breaks = [];

    /**
     * The case of the enclosing switch, which `break 2` or more can leave
     */
    public ?CaseScope $outer_case_scope = null;

    /**
     * Whether the case reaches a nested switch every case of which leaves it, so that only its breaks leave the switch
     */
    public bool $has_nested_switch_leaving_it = false;

    /**
     * @psalm-external-mutation-free
     */
    public function addBreak(Context $context): void
    {
        $this->vars_possibly_in_scope_at_breaks += $context->vars_possibly_in_scope;

        foreach ($context->parent_remove_vars as $var_id => $stamp) {
            $this->parent_remove_vars_at_breaks[$var_id] = max(
                $stamp,
                $this->parent_remove_vars_at_breaks[$var_id] ?? 0,
            );
        }
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
