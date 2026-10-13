<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Psalm\Codebase;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TPropertiesOf;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplatePropertiesOf;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

/**
 * @internal
 */
final class RecursivePropertiesOfReplacer extends MutableTypeVisitor
{
    public function __construct(
        private readonly Codebase $codebase,
        private readonly ?string $self_class,
        private readonly string|TNamedObject|TTemplateParam|null $static_class_type,
        private readonly ?string $parent_class,
    ) {}

    protected function enterNode(TypeNode &$type): ?int
    {
        if ($type instanceof TNamedObject) {
            $properties_of = new TPropertiesOf($type, TPropertiesOf::VISIBILITY_PUBLIC);
            $type = TypeExpander::expandAtomic(
                $this->codebase,
                $properties_of,
                $this->self_class,
                $this->static_class_type,
                $this->parent_class,
            )[0];

            if (!($type instanceof TKeyedArray)) {
                return self::DONT_TRAVERSE_CHILDREN;
            }
        }

        return null;
    }
}
