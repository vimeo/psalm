<?php

declare(strict_types=1);

namespace Psalm\Internal\Scope;

use Psalm\Context;
use Psalm\Type\Union;

use function array_intersect_key;
use function max;
use function str_contains;

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
     * The properties and memoized calls forgotten (not unset) at a `break` out of the switch in the case, with the
     * stamp of the change
     *
     * @var array<string, int>
     */
    public array $forgotten_at_breaks = [];

    /**
     * @psalm-external-mutation-free
     */
    public function addBreak(Context $context): void
    {
        foreach ($context->vars_possibly_in_scope as $var_id => $_) {
            if (!isset($context->vars_in_scope[$var_id])
                && isset($context->parent_remove_vars[$var_id])
                && (str_contains($var_id, '->') || str_contains($var_id, '::'))
            ) {
                $this->forgotten_at_breaks[$var_id] = $context->parent_remove_vars[$var_id];
            }
        }

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
