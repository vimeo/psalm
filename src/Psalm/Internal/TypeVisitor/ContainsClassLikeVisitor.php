<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;

use function strtolower;

/**
 * @internal
 */
final class ContainsClassLikeVisitor extends TypeVisitor
{
    private bool $contains_classlike = false;

    /**
     * @psalm-external-mutation-free
     * @param lowercase-string $fq_classlike_name
     */
    public function __construct(
        private readonly string $fq_classlike_name,
    ) {
    }

    /**
     * @psalm-external-mutation-free
     */
    protected function enterNode(TypeNode $type): ?int
    {
        if ($type instanceof TNamedObject) {
            if (strtolower($type->value) === $this->fq_classlike_name) {
                $this->contains_classlike = true;
                return self::STOP_TRAVERSAL;
            }
        }

        if ($type instanceof TClassConstant) {
            if (strtolower($type->fq_classlike_name) === $this->fq_classlike_name) {
                $this->contains_classlike = true;
                return self::STOP_TRAVERSAL;
            }
        }

        if ($type instanceof TLiteralClassString) {
            if (strtolower($type->value) === $this->fq_classlike_name) {
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
