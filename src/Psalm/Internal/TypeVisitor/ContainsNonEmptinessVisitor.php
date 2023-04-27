<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyScalar;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;

/**
 * @internal
 */
final class ContainsNonEmptinessVisitor extends TypeVisitor
{
    private bool $contains_non_emptiness = false;

    protected function enterNode(TypeNode $type): ?int
    {
        if ($type instanceof TNonEmptyString
            || $type instanceof TNonEmptyScalar
            || $type instanceof TNonEmptyArray
        ) {
            $this->contains_non_emptiness = true;
            return self::STOP_TRAVERSAL;
        }

        if ($type instanceof TKeyedArray && $type->isNonEmpty()) {
            $this->contains_non_emptiness = true;
            return self::STOP_TRAVERSAL;
        }

        return null;
    }

    public function matches(): bool
    {
        return $this->contains_non_emptiness;
    }
}
