<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

use function array_values;
use function count;

/**
 * Replaces the type a conditional type is on, as `value-of<T>` in `(value-of<T> is string ? A : B)`, with the types
 * it has in a branch of it
 *
 * @internal
 */
final class ConditionalSubjectReplacer extends MutableTypeVisitor
{
    /** @var array<string, true> */
    private readonly array $subject_ids;

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        Union $subject_type,
        private readonly Union $replacement_type,
        private readonly Codebase $codebase,
    ) {
        $subject_ids = [];

        foreach ($subject_type->getAtomicTypes() as $subject_atomic_type) {
            $subject_ids[$subject_atomic_type->getId()] = true;
        }

        $this->subject_ids = $subject_ids;
    }

    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if (!$type instanceof Union) {
            return null;
        }

        $kept_types = [];

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if (!isset($this->subject_ids[$atomic_type->getId()])) {
                $kept_types[] = $atomic_type;
            }
        }

        // only the whole subject, e.g. all of `key-of<T>|value-of<T>`
        if (count($type->getAtomicTypes()) - count($kept_types) !== count($this->subject_ids)) {
            return null;
        }

        $type = $type->setTypes(TypeCombiner::combine(
            [...$kept_types, ...array_values($this->replacement_type->getAtomicTypes())],
            $this->codebase,
        )->getAtomicTypes());

        return null;
    }
}
