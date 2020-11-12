<?php
namespace Psalm\Internal\TypeVisitor;

use Psalm\Type\TypeNode;
use Psalm\Type\NodeVisitor;

class FromDocblockSetter extends NodeVisitor
{
    /**
     * @psalm-suppress MoreSpecificImplementedParamType
     *
     * @param  \Psalm\Type\Atomic|\Psalm\Type\Union $type
     * @return self::STOP_TRAVERSAL|self::DONT_TRAVERSE_CHILDREN|null
     */
    protected function enterNode(TypeNode $type) : ?int
    {
        $type->from_docblock = true;

        if ($type instanceof \Psalm\Type\Atomic\TTemplateParam
            && $type->as->isMixed()
        ) {
            return NodeVisitor::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }
}
