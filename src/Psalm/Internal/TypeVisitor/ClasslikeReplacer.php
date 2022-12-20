<?php

namespace Psalm\Internal\TypeVisitor;

use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;

use function strtolower;

/**
 * @internal
 */
class ClasslikeReplacer extends MutableTypeVisitor
{
    private string $old;
    private string $new;

    public function __construct(
        string $old,
        string $new
    ) {
        $this->old = strtolower($old);
        $this->new = $new;
    }

    protected function enterNode(TypeNode &$type): ?int
    {
        if ($type instanceof TClassConstant) {
            if (strtolower($type->fq_classlike_name) === $this->old) {
                $type = new TClassConstant(
                    $this->new,
                    $type->const_name,
                    $type->from_docblock,
                );
            }
        } elseif ($type instanceof TClassString) {
            if ($type->as !== 'object' && strtolower($type->as) === $this->old) {
                $type = new TClassString(
                    $this->new,
                    $type->as_type,
                    $type->is_loaded,
                    $type->is_interface,
                    $type->is_enum,
                    $type->from_docblock,
                );
            }
        } elseif ($type instanceof TNamedObject || $type instanceof TLiteralClassString) {
            if (strtolower($type->value) === $this->old) {
                $type = $type->setValue($this->new);
            }
        }
        return null;
    }
}
