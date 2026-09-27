<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;

/**
 * @internal
 */
final class ClasslikeReplacer extends MutableTypeVisitor
{
    /**
     * @param int $old class name id
     * @param int $new class name id
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly int $old,
        private readonly int $new,
    ) {
    }

    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if ($type instanceof TClassConstant) {
            if ($type->fq_classlike_name === $this->old) {
                $type = new TClassConstant(
                    $this->new,
                    $type->const_name,
                    $type->from_docblock,
                );
            }
        } elseif ($type instanceof TClassString) {
            if ($type->as === $this->old) {
                $type = new TClassString(
                    $this->new,
                    $type->as_type,
                    $type->is_loaded,
                    $type->is_interface,
                    $type->is_enum,
                    $type->from_docblock,
                );
            }
        } elseif ($type instanceof TNamedObject) {
            if ($type->value === $this->old) {
                $type = $type->setValue($this->new);
            }
        } elseif ($type instanceof TLiteralClassString) {
            if ($type->class_name === $this->old) {
                $type = $type->setClassName($this->new);
            }
        }
        return null;
    }
}
