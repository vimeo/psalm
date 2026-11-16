<?php

declare(strict_types=1);

namespace Psalm\Internal\Scope;

use PhpParser;
use Psalm\Internal\Clause;
use Psalm\Type\Union;

use function array_intersect_key;

/**
 * @internal
 */
final class SwitchScope
{
    /**
     * @var array<string, Union>|null
     */
    public ?array $new_vars_in_scope = null;

    /**
     * Offsets some cases reaching the end know to be set and others not (e.g. by `isset()`): the type of the
     * array tells as much
     *
     * @var array<string, true>
     */
    public array $unknown_offsets = [];

    /**
     * @var array<string, bool>
     */
    public array $new_vars_possibly_in_scope = [];

    /**
     * @var array<string, Union>|null
     */
    public ?array $redefined_vars = null;

    /**
     * @var array<string, Union>|null
     */
    public ?array $possibly_redefined_vars = null;

    /**
     * @var array<PhpParser\Node\Stmt>
     */
    public array $leftover_statements = [];

    public ?PhpParser\Node\Expr $leftover_case_equality_expr = null;

    /**
     * @var list<Clause>
     */
    public array $negated_clauses = [];

    /**
     * @var array<string, bool>|null
     */
    public ?array $new_assigned_var_ids = null;

    /**
     * The offsets of nullable arrays read on every way out of the cases so far, null before there is one
     *
     * @var array<string, true>|null
     */
    public ?array $null_array_offsets_read = null;

    /**
     * The properties and memoized calls a way out of the switch removed
     *
     * @var array<string, true>
     */
    public array $removed_var_ids = [];

    /**
     * @param array<string, true> $null_array_offsets_read
     * @psalm-external-mutation-free
     */
    public function addWayOut(array $null_array_offsets_read): void
    {
        $this->null_array_offsets_read = $this->null_array_offsets_read === null
            ? $null_array_offsets_read
            : array_intersect_key($this->null_array_offsets_read, $null_array_offsets_read);
    }
}
