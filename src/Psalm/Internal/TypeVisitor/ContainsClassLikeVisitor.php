<?php

namespace Psalm\Internal\TypeVisitor;

use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\ImmutableTypeVisitor;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;

use function strtolower;

/**
 * @internal
 */
class ContainsClassLikeVisitor extends ImmutableTypeVisitor
{
    /**
     * @var lowercase-string
     */
    private $fq_classlike_name;

    /**
     * @var bool
     */
    private $contains_classlike = false;

    /**
     * @param lowercase-string $fq_classlike_name
     */
    public function __construct(string $fq_classlike_name)
    {
        $this->fq_classlike_name = $fq_classlike_name;
    }

    protected function enterNode(TypeNode $type): ?int
    {
        if ($type instanceof TNamedObject) {
            if (strtolower($type->value) === $this->fq_classlike_name) {
                $this->contains_classlike = true;
                return TypeVisitor::STOP_TRAVERSAL;
            }
        }

        if ($type instanceof TClassConstant) {
            if (strtolower($type->fq_classlike_name) === $this->fq_classlike_name) {
                $this->contains_classlike = true;
                return TypeVisitor::STOP_TRAVERSAL;
            }
        }

        if ($type instanceof TLiteralClassString) {
            if (strtolower($type->value) === $this->fq_classlike_name) {
                $this->contains_classlike = true;
                return TypeVisitor::STOP_TRAVERSAL;
            }
        }

        return null;
    }

    public function matches(): bool
    {
        return $this->contains_classlike;
    }
}
