<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Interner;
use Psalm\StrId;
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
    /** lowercase class name id */
    private readonly int $old;

    /**
     * @param int $old class name id
     * @param int $new class name id
     * @psalm-mutation-free
     */
    public function __construct(
        int $old,
        private readonly int $new,
    ) {
        $this->old = Interner::lower($old);
    }

    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if ($type instanceof TClassConstant) {
            if (Interner::lower($type->fq_classlike_name) === $this->old) {
                $type = new TClassConstant(
                    $this->new,
                    $type->const_name,
                    $type->from_docblock,
                );
            }
        } elseif ($type instanceof TClassString) {
            if ($type->as !== StrId::object && Interner::lower($type->as) === $this->old) {
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
            if (Interner::lower($type->value) === $this->old) {
                $type = $type->setValue($this->new);
            }
        } elseif ($type instanceof TLiteralClassString) {
            if (Interner::lower($type->class_name) === $this->old) {
                $type = $type->setClassName($this->new);
            }
        }
        return null;
    }
}
