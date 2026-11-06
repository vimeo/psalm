<?php

declare(strict_types=1);

namespace Psalm\Internal\Scope;

use function array_slice;
use function count;
use function spl_object_id;

/**
 * Which variables had their clauses removed, and in which context. A context and the contexts cloned from it
 * share one log, so a statement can learn what its nested contexts (branches, loop bodies, operands of && and
 * ||, ...) invalidated, even though those contexts never hand their clauses back.
 *
 * @internal
 * @psalm-capabilities read-props|write-this-props|write-props|write-refs
 */
final class RemovedVarLog
{
    /**
     * @var list<array{string, int}>
     */
    private array $entries = [];

    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    public function record(string $var_id, int $context_id): void
    {
        $entry = [$var_id, $context_id];

        if ($this->entries === [] || $entry !== $this->entries[count($this->entries) - 1]) {
            $this->entries[] = $entry;
        }
    }

    /**
     * Forgets what was recorded since $position, when that code can no longer affect what follows it.
     *
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    public function discardSince(int $position): void
    {
        $this->entries = array_slice($this->entries, 0, $position);
    }

    /**
     * @psalm-capabilities read-props
     */
    public function position(): int
    {
        return count($this->entries);
    }

    /**
     * @psalm-capabilities read-props
     * @return array<string, true>
     */
    public function removedSince(int $position, ?object $except_context = null): array
    {
        $except_context_id = $except_context !== null ? spl_object_id($except_context) : null;
        $var_ids = [];

        for ($i = $position, $count = count($this->entries); $i < $count; $i++) {
            [$var_id, $context_id] = $this->entries[$i];

            if ($context_id !== $except_context_id) {
                $var_ids[$var_id] = true;
            }
        }

        return $var_ids;
    }
}
