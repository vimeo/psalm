<?php

namespace Psalm\Internal\Scope;

use Psalm\Type\Union;

/**
 * @internal
 */
class TryCatchScope
{
    /**
     * Maps variable ids to a list of all assignments of that variable in the `try` or `catch` scope.
     *
     * @var array<string, non-empty-list<Union>>
     */
    public $assignments_from_scope = [];

    /**
     * @var array<string, true>
     */
    public $unset_from_scope = [];

    /**
     * Add assignments and unsets from an inner scope to the outer scope.
     */
    public function applyInnerScope(self $other): void
    {
        foreach ($other->assignments_from_scope as $var_id => $assigned_types) {
            $outer_types = [];
            foreach ($assigned_types as $assigned_type) {
                $outer_type = clone $assigned_type;
                $outer_type->possibly_undefined = $outer_type->possibly_undefined_from_try = true;
                $outer_types[] = $outer_type;
            }
            $this->assignments_from_scope[$var_id] = [
                ...($this->assignments_from_scope[$var_id] ?? []),
                ...$outer_types,
            ];
        }
        $this->unset_from_scope += $other->unset_from_scope;
    }
}
