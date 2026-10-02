<?php

declare(strict_types=1);

namespace Psalm\Internal\DataFlow;

use Psalm\Type\TaintKind;

use function array_slice;
use function array_splice;
use function count;
use function implode;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * What a taint flow went through, as far as where it can go next depends on it: the taints it
 * carries, and its open assignments -- the assignments to array keys, array values and properties
 * that no later fetch has matched yet: a fetch of a key is not taken if the latest open assignment
 * of its kind is to another key.
 *
 * A flow from a taint source starts from nothing. The flow through the body of a specialized
 * function-like is resolved once for all calls (see TaintFlowGraph), so it starts from the flow
 * entering it, which it doesn't know: its state is relative to that flow -- the taints of it that
 * it keeps, the fetches it makes of its open assignments, and whether it forgets them. A call
 * applies it to the flow entering it (see then()).
 *
 * @internal
 * @psalm-immutable
 */
final class TaintFlowState
{
    /**
     * How many open assignments, and fetches of the open assignments of the flow it starts from,
     * a state keeps. Past those, the flow forgets the open assignments it starts from, so that no
     * fetch of them is ignored: it may take taints it doesn't have there, but takes all those it
     * has. This keeps the states finitely many, though a recursion or a loop can wrap a value
     * deeper every time.
     */
    private const DEPTH = 8;

    private const FAMILIES = ['arraykey', 'arrayvalue', 'property'];

    /**
     * Tells the states that can go on differently apart.
     */
    public readonly string $key;

    /**
     * @param list<string> $fetches the fetches made of the open assignments of the flow it starts
     *                              from, in order: '?' and a fetch path type for checking whether
     *                              that flow takes it, '-' and a kind of assignment for matching the
     *                              latest of that kind
     * @param list<string> $assignments its open assignments, above those of the flow it starts from
     */
    private function __construct(
        public readonly int $kept_taints,
        public readonly int $taints,
        private readonly array $fetches,
        private readonly array $assignments,
        private readonly bool $forgets,
    ) {
        $this->key = $kept_taints . ' ' . $taints . ' ' . implode(',', $fetches)
            . ($forgets ? ' | ' : ' : ') . implode(',', $assignments);
    }

    /**
     * The state of a flow from a taint source of $taints.
     *
     * @psalm-pure
     */
    public static function fromSource(int $taints): self
    {
        return new self(0, $taints, [], [], true);
    }

    /**
     * The state of a flow entering the body of a specialized function-like: it keeps everything of
     * the flow entering it.
     *
     * @psalm-pure
     */
    public static function fromEntry(): self
    {
        return new self(TaintKind::ALL, 0, [], [], false);
    }

    /**
     * The state of a flow that took $path from this state, or null if no taint goes there.
     */
    public function withPath(Path $path): ?self
    {
        $kept_taints = $this->kept_taints & ~$path->removed_taints;
        $taints = ($this->taints | $path->added_taints) & ~$path->removed_taints;

        if ($kept_taints === 0 && $taints === 0) {
            return null;
        }

        $structural = self::getFamily($path->type, '-');

        if ($structural === null && $kept_taints === $this->kept_taints && $taints === $this->taints) {
            return $this;
        }

        $state = new self($kept_taints, $taints, $this->fetches, $this->assignments, $this->forgets);

        if ($structural === null) {
            return $state;
        }

        foreach (self::FAMILIES as $family) {
            if (!str_starts_with($path->type, $family . '-fetch')) {
                continue;
            }

            // fetching a key of the array also fetches its values: see the literal key check in fetch()
            if (str_starts_with($path->type, $family . '-fetch-') || $path->type === 'arraykey-fetch') {
                $state = $state->fetch('?' . $path->type);

                if ($state === null) {
                    return null;
                }
            }

            return $state->fetch('-' . $family);
        }

        if (self::getFamily($path->type, '-assignment') !== null) {
            return $state->assign($path->type);
        }

        return $state;
    }

    /**
     * The state of a flow from this state that went on as $next, a state relative to it, or null if
     * no taint goes there.
     */
    public function then(self $next): ?self
    {
        $kept_taints = $this->kept_taints & $next->kept_taints;
        $taints = ($this->taints & $next->kept_taints) | $next->taints;

        if ($kept_taints === 0 && $taints === 0) {
            return null;
        }

        $state = new self($kept_taints, $taints, $this->fetches, $this->assignments, $this->forgets);

        foreach ($next->fetches as $fetch) {
            $state = $state->fetch($fetch);

            if ($state === null) {
                return null;
            }
        }

        if ($next->forgets) {
            $state = new self($kept_taints, $taints, $state->fetches, [], true);
        }

        foreach ($next->assignments as $assignment) {
            $state = $state->assign($assignment);
        }

        return $state;
    }

    /**
     * This state, forgetting all but its innermost open assignment: from there on, no fetch of
     * the others is ignored. It may take taints it doesn't have, but takes all those it has.
     */
    public function widened(): self
    {
        return new self($this->kept_taints, $this->taints, [], array_slice($this->assignments, -1), true);
    }

    /**
     * Applies $fetch (see the constructor) to the open assignments, or to those of the flow this
     * state starts from if none of its kind is open here. Null if the flow doesn't take it.
     */
    private function fetch(string $fetch): ?self
    {
        $fetch_type = substr($fetch, 1);

        if ($fetch[0] !== '?') {
            $family = $fetch_type;
        } elseif ($fetch_type === 'arraykey-fetch') {
            // a key fetch matches the latest open assignment to a value
            $family = 'arrayvalue';
        } else {
            $family = self::getFamily($fetch_type, '-fetch-') ?? 'arrayvalue';
        }

        for ($i = count($this->assignments) - 1; $i >= 0; $i--) {
            $assignment = $this->assignments[$i];

            if (!str_starts_with($assignment, $family . '-assignment')) {
                continue;
            }

            if ($fetch[0] === '?') {
                return self::isFetchOf($fetch_type, $family, $assignment) ? $this : null;
            }

            $assignments = $this->assignments;
            array_splice($assignments, $i, 1);

            return new self($this->kept_taints, $this->taints, $this->fetches, $assignments, $this->forgets);
        }

        if ($this->forgets) {
            return $this;
        }

        if (count($this->fetches) === self::DEPTH) {
            return new self($this->kept_taints, $this->taints, [], $this->assignments, true);
        }

        $fetches = $this->fetches;
        $fetches[] = $fetch;

        return new self($this->kept_taints, $this->taints, $fetches, $this->assignments, false);
    }

    private function assign(string $assignment): self
    {
        $assignments = $this->assignments;
        $assignments[] = $assignment;

        if (count($assignments) > self::DEPTH) {
            return new self(
                $this->kept_taints,
                $this->taints,
                $this->fetches,
                array_slice($assignments, -self::DEPTH),
                true,
            );
        }

        return new self($this->kept_taints, $this->taints, $this->fetches, $assignments, $this->forgets);
    }

    /**
     * Whether the flow takes the fetch of type $fetch_type of the value $assignment, the latest
     * open assignment of $family: unless both are to a known key, and not the same one. A fetch of
     * the keys of an array whose latest value went at a known key doesn't take its taints, as that
     * key is a literal.
     *
     * @psalm-pure
     */
    private static function isFetchOf(string $fetch_type, string $family, string $assignment): bool
    {
        $assigned_key = substr($assignment, strlen($family . '-assignment'));

        if ($assigned_key === '') {
            return true;
        }

        if ($fetch_type === 'arraykey-fetch') {
            return false;
        }

        return substr($fetch_type, strlen($family . '-fetch')) === $assigned_key;
    }

    /**
     * The kind of $path_type if it is $suffix of one, e.g. 'arrayvalue' for an assignment to an array value.
     *
     * @psalm-pure
     */
    private static function getFamily(string $path_type, string $suffix): ?string
    {
        foreach (self::FAMILIES as $family) {
            if (str_starts_with($path_type, $family . $suffix)) {
                return $family;
            }
        }

        return null;
    }
}
