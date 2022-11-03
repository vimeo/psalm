<?php

namespace Psalm\Internal\TypeVisitor;

use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\NodeVisitor;
use Psalm\Type\TypeNode;

class ContainsObjectTypeVisitor extends NodeVisitor
{
    /**
     * @var bool
     */
    private $contains_object_type = false;

    protected function enterNode(TypeNode $type): ?int
    {
        if ($type instanceof TObject
            || $type instanceof TNamedObject
            || ($type instanceof TTemplateParam
                && $type->as->hasObjectType())
        ) {
            $this->contains_object_type = true;
            return NodeVisitor::STOP_TRAVERSAL;
        }

        return null;
    }

    public function matches(): bool
    {
        return $this->contains_object_type;
    }
}
