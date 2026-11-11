<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;

/**
 * @internal
 */
final class ContainsStaticVisitor extends TypeVisitor
{
    private bool $contains_static = false;

    /**
     * @param bool $declared_only whether only `static` as declared counts, not a type it was resolved to
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly bool $declared_only = false,
    ) {
    }

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    protected function enterNode(TypeNode $type): ?int
    {
        if ($type instanceof TNamedObject
            && ($type->value === 'static' || (!$this->declared_only && $type->is_static))
        ) {
            $this->contains_static = true;
            return self::STOP_TRAVERSAL;
        }
        return null;
    }

    public function matches(): bool
    {
        return $this->contains_static;
    }
}
