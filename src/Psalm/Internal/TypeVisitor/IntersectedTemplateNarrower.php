<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Type\TypeParser;
use Psalm\Type\Atomic\TIntersection;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

/**
 * Where its templates aren't replaced, gives each template intersected with an array, such as the `T&array{id: int}`
 * of `list<T&array{id: int}>`, as TypeParser::getIntersectedTemplate() does
 *
 * @internal
 */
final class IntersectedTemplateNarrower extends MutableTypeVisitor
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly Codebase $codebase,
    ) {
    }

    /**
     * @return self::STOP_TRAVERSAL|self::DONT_TRAVERSE_CHILDREN|null
     */
    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if (!$type instanceof Union) {
            return null;
        }

        $atomic_types = [];
        $changed = false;

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TIntersection
                && ($template = TypeParser::getIntersectedTemplate($atomic_type, $this->codebase))
            ) {
                $atomic_type = $template;
                $changed = true;
            }

            $atomic_types[] = $atomic_type;
        }

        if ($changed) {
            $type = $type->getBuilder()->setTypes($atomic_types)->freeze();
        }

        return null;
    }
}
