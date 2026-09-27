<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;

/**
 * @internal
 */
final class ContainsClassLikeVisitor extends TypeVisitor
{
    private bool $contains_classlike = false;

    /**
     * @param int $fq_classlike_name class name id
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly int $fq_classlike_name,
    ) {
    }

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    protected function enterNode(TypeNode $type): ?int
    {
        if ($type instanceof TNamedObject) {
            if ($type->value === $this->fq_classlike_name) {
                $this->contains_classlike = true;
                return self::STOP_TRAVERSAL;
            }
        }

        if ($type instanceof TClassConstant) {
            if ($type->fq_classlike_name === $this->fq_classlike_name) {
                $this->contains_classlike = true;
                return self::STOP_TRAVERSAL;
            }
        }

        if ($type instanceof TLiteralClassString) {
            if ($type->class_name === $this->fq_classlike_name) {
                $this->contains_classlike = true;
                return self::STOP_TRAVERSAL;
            }
        }

        return null;
    }

    /**
     * @psalm-mutation-free
     */
    public function matches(): bool
    {
        return $this->contains_classlike;
    }
}
